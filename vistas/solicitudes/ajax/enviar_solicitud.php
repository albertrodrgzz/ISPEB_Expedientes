<?php
/**
 * AJAX: Enviar Solicitud (Nivel 3 – Empleado Base)
 * Sistema ISPEB - Portal de Autogestión
 */
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/database.php';

verificarSesion();

// Solo Nivel 3
if ($_SESSION['nivel_acceso'] != 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

// Verificar CSRF
if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido. Recarga la página.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $funcionario_id = (int) ($_SESSION['funcionario_id'] ?? 0);
    $tipo_solicitud = trim($_POST['tipo_solicitud'] ?? '');
    $fecha_inicio   = trim($_POST['fecha_inicio']   ?? '');
    $motivo         = trim($_POST['motivo']          ?? '');
    $periodos_raw   = trim($_POST['periodos_años']   ?? '');

    // ── Validaciones comunes ──────────────────────────────────────────────────
    if (!$funcionario_id) {
        throw new Exception('Sesión inválida. Vuelve a iniciar sesión.', 400);
    }
    if (!in_array($tipo_solicitud, ['vacaciones', 'permiso'])) {
        throw new Exception('Tipo de solicitud no válido.', 400);
    }
    if (empty($motivo)) {
        throw new Exception('El motivo es obligatorio.', 400);
    }
    if (strlen($motivo) < 10) {
        throw new Exception('El motivo es demasiado breve (mínimo 10 caracteres).', 400);
    }

    $pdo = getDB();

    if ($tipo_solicitud === 'vacaciones') {
        // ── Validación por períodos (LOTTT) ───────────────────────────────────
        $periodos_anios = json_decode($periodos_raw, true);
        if (empty($periodos_anios) || !is_array($periodos_anios)) {
            throw new Exception('Debes seleccionar al menos un período vacacional.', 400);
        }
        if (empty($fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
            throw new Exception('La fecha de inicio es obligatoria.', 400);
        }

        // Verificar antigüedad mínima (1 año)
        $stmtFec = $pdo->prepare("SELECT fecha_ingreso FROM funcionarios WHERE id = ?");
        $stmtFec->execute([$funcionario_id]);
        $fecha_ingreso_str = $stmtFec->fetchColumn();
        if ($fecha_ingreso_str) {
            $anios = (new DateTime('today'))->diff(new DateTime($fecha_ingreso_str))->y;
            if ($anios < 1) {
                throw new Exception('Según la LOTTT, debes cumplir al menos 1 año de servicio para solicitar vacaciones.', 400);
            }
            $max_periodo = max($periodos_anios);
            if ($max_periodo > $anios) {
                throw new Exception("No tienes suficientes años de servicio para el período Año $max_periodo.", 400);
            }
        }

        // Verificar que los períodos solicitados no hayan sido ya tomados
        $stmtTomados = $pdo->prepare("
            SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.periodo_año')) AS UNSIGNED) AS periodo_año
            FROM historial_administrativo
            WHERE funcionario_id = ?
              AND tipo_evento = 'VACACION'
              AND JSON_EXTRACT(detalles, '$.periodo_año') IS NOT NULL
        ");
        $stmtTomados->execute([$funcionario_id]);
        $tomados = array_map('intval', array_column($stmtTomados->fetchAll(PDO::FETCH_ASSOC), 'periodo_año'));
        foreach ($periodos_anios as $pa) {
            if (in_array((int)$pa, $tomados)) {
                throw new Exception("El período del Año $pa ya fue disfrutado anteriormente.", 409);
            }
        }

        // Prevenir solicitudes duplicadas pendientes
        $stmtDup = $pdo->prepare("
            SELECT COUNT(*) FROM solicitudes_empleados
            WHERE funcionario_id = ? AND tipo_solicitud = 'vacaciones' AND estado = 'pendiente'
        ");
        $stmtDup->execute([$funcionario_id]);
        if ($stmtDup->fetchColumn() > 0) {
            throw new Exception("Ya tienes una solicitud de vacaciones pendiente de revisión.", 409);
        }

        // Insertar con los períodos indicados en el motivo
        $periodos_label  = implode(', ', array_map(fn($a) => "Año $a", $periodos_anios));
        $motivo_completo = "[Períodos: $periodos_label] $motivo";
        $fecha_fin       = $fecha_inicio; // RRHH fija la fecha real al aprobar

        $stmt = $pdo->prepare("
            INSERT INTO solicitudes_empleados
                (funcionario_id, tipo_solicitud, fecha_inicio, fecha_fin, motivo, estado, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'pendiente', NOW(), NOW())
        ");
        $stmt->execute([
            $funcionario_id,
            $tipo_solicitud,
            $fecha_inicio,
            $fecha_fin,
            htmlspecialchars($motivo_completo, ENT_QUOTES, 'UTF-8')
        ]);

    } else {
        // ── Permiso especial: validación de fechas libre ───────────────────────
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');
        if (empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception('Las fechas son obligatorias.', 400);
        }
        if ($fecha_fin < $fecha_inicio) {
            throw new Exception('La fecha de fin no puede ser anterior a la de inicio.', 400);
        }

        $stmtDup = $pdo->prepare("
            SELECT COUNT(*) FROM solicitudes_empleados
            WHERE funcionario_id = ? AND tipo_solicitud = ? AND estado = 'pendiente'
        ");
        $stmtDup->execute([$funcionario_id, $tipo_solicitud]);
        if ($stmtDup->fetchColumn() > 0) {
            throw new Exception("Ya tienes una solicitud de {$tipo_solicitud} pendiente de revisión.", 409);
        }

        $stmt = $pdo->prepare("
            INSERT INTO solicitudes_empleados
                (funcionario_id, tipo_solicitud, fecha_inicio, fecha_fin, motivo, estado, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'pendiente', NOW(), NOW())
        ");
        $stmt->execute([
            $funcionario_id,
            $tipo_solicitud,
            $fecha_inicio,
            $fecha_fin,
            htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')
        ]);
    }

    $solicitud_id = $pdo->lastInsertId();

    // Auditoría
    registrarAuditoria('NUEVA_SOLICITUD', 'solicitudes_empleados', $solicitud_id, null, [
        'tipo'          => $tipo_solicitud,
        'fecha_inicio'  => $fecha_inicio,
        'periodos_años' => $periodos_raw ?: null
    ]);

    ob_end_clean();
    echo json_encode([
        'success'      => true,
        'message'      => 'Solicitud enviada correctamente. RRHH la revisará pronto.',
        'solicitud_id' => $solicitud_id
    ]);

} catch (Exception $e) {
    ob_end_clean();
    $code = $e->getCode() ?: 500;
    http_response_code($code >= 400 && $code < 600 ? $code : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
