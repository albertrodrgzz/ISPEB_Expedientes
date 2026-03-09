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
    $funcionario_id  = (int) ($_SESSION['funcionario_id'] ?? 0);
    $tipo_solicitud  = trim($_POST['tipo_solicitud'] ?? '');
    $fecha_inicio    = trim($_POST['fecha_inicio']   ?? '');
    $fecha_fin       = trim($_POST['fecha_fin']       ?? '');
    $motivo          = trim($_POST['motivo']          ?? '');

    // ── Validaciones ─────────────────────────────────────────────────────────
    if (!$funcionario_id) {
        throw new Exception('Sesión inválida. Vuelve a iniciar sesión.', 400);
    }
    if (!in_array($tipo_solicitud, ['vacaciones', 'permiso'])) {
        throw new Exception('Tipo de solicitud no válido.', 400);
    }
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        throw new Exception('Las fechas son obligatorias.', 400);
    }
    if ($fecha_fin < $fecha_inicio) {
        throw new Exception('La fecha de fin no puede ser anterior a la de inicio.', 400);
    }
    if (empty($motivo)) {
        throw new Exception('El motivo es obligatorio.', 400);
    }
    if (strlen($motivo) < 10) {
        throw new Exception('El motivo es demasiado breve (mínimo 10 caracteres).', 400);
    }

    $pdo = getDB();

    // Validar LOTTT: vacaciones solo tras 1 año de servicio
    if ($tipo_solicitud === 'vacaciones') {
        $stmtFec = $pdo->prepare("SELECT fecha_ingreso FROM funcionarios WHERE id = ?");
        $stmtFec->execute([$funcionario_id]);
        $fecha_ingreso_str = $stmtFec->fetchColumn();
        if ($fecha_ingreso_str) {
            $anios = (new DateTime('today'))->diff(new DateTime($fecha_ingreso_str))->y;
            if ($anios < 1) {
                throw new Exception('Según la LOTTT, debes cumplir al menos 1 año de servicio para solicitar vacaciones.', 400);
            }
        }
    }

    // Prevenir solicitudes duplicadas activas del mismo tipo
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM solicitudes_empleados
        WHERE funcionario_id = ? AND tipo_solicitud = ? AND estado = 'pendiente'
    ");
    $stmt->execute([$funcionario_id, $tipo_solicitud]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Ya tienes una solicitud de {$tipo_solicitud} pendiente de revisión.", 409);
    }

    // Insertar solicitud
    $stmt = $pdo->prepare("
        INSERT INTO solicitudes_empleados
            (funcionario_id, tipo_solicitud, fecha_inicio, fecha_fin, motivo, estado, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, 'pendiente', NOW(), NOW())
    ");
    $stmt->execute([
        $funcionario_id,
        $tipo_solicitud,
        $fecha_inicio,
        $fecha_fin,
        htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')
    ]);

    $solicitud_id = $pdo->lastInsertId();

    // Auditoría
    registrarAuditoria('NUEVA_SOLICITUD', 'solicitudes_empleados', $solicitud_id, null, [
        'tipo'        => $tipo_solicitud,
        'fecha_inicio'=> $fecha_inicio,
        'fecha_fin'   => $fecha_fin
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
