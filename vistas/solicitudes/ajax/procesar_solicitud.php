<?php
/**
 * AJAX: Procesar Solicitud – Aprobar o Rechazar (Niveles 1 y 2)
 * Sistema ISPEB - Bandeja de Aprobación Documental
 *
 * Transacción ACID al APROBAR:
 *   1. Subir memo/aval al servidor
 *   2. Actualizar solicitudes_empleados (estado, revisor, ruta_archivo)
 *   3. Insertar evento en historial_administrativo
 * Todo dentro de un único beginTransaction / commit.
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/database.php';

// ── Seguridad ────────────────────────────────────────────────────────────────
verificarSesion();

if ($_SESSION['nivel_acceso'] > 2) {
    http_response_code(403);
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido. Recarga la página.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ── Parsear Inputs ────────────────────────────────────────────────────────────
$accion = trim($_POST['accion'] ?? '');
$solicitud_id = filter_var($_POST['solicitud_id'] ?? 0, FILTER_VALIDATE_INT);
$observaciones = htmlspecialchars(trim($_POST['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8');
$revisor_id = (int) ($_SESSION['usuario_id'] ?? 0);

try {
    // Validaciones comunes
    if (!in_array($accion, ['aprobar', 'rechazar'])) {
        throw new Exception('Acción no válida.', 400);
    }
    if (!$solicitud_id) {
        throw new Exception('ID de solicitud no válido.', 400);
    }

    $pdo = getDB();

    // Obtener la solicitud y verificar que sigue pendiente
    $stmt = $pdo->prepare("
        SELECT se.*, f.id AS func_id
        FROM   solicitudes_empleados se
        INNER  JOIN funcionarios f ON se.funcionario_id = f.id
        WHERE  se.id = ? AND se.estado = 'pendiente'
        LIMIT  1
    ");
    $stmt->execute([$solicitud_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        throw new Exception('Solicitud no encontrada o ya fue procesada.', 404);
    }

    // ════════════════════════════════════════════════
    //   FLUJO: RECHAZAR
    // ════════════════════════════════════════════════
    if ($accion === 'rechazar') {
        if (empty($observaciones) || strlen($observaciones) < 15) {
            throw new Exception('Debe proporcionar un motivo de rechazo detallado (mín. 15 caracteres).', 400);
        }

        $stmt = $pdo->prepare("
            UPDATE solicitudes_empleados
            SET    estado                 = 'rechazada',
                   revisado_por           = ?,
                   observaciones_respuesta = ?,
                   updated_at            = NOW()
            WHERE  id = ?
        ");
        $stmt->execute([$revisor_id, $observaciones, $solicitud_id]);

        registrarAuditoria('RECHAZAR_SOLICITUD', 'solicitudes_empleados', $solicitud_id, ['estado' => 'pendiente'], [
            'estado' => 'rechazada',
            'revisado_por' => $revisor_id,
            'observaciones' => $observaciones
        ]);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Solicitud rechazada correctamente.']);
        exit;
    }

    // ════════════════════════════════════════════════
    //   FLUJO: APROBAR (Transacción ACID)
    // ════════════════════════════════════════════════

    // Archivo de aprobación (opcional: PDF, JPG o PNG)
    $func_id = (int) $solicitud['funcionario_id'];
    $ruta_relativa = null;
    $nombre_archivo_orig = null;

    $archivo_subido = isset($_FILES['archivo_aprobacion']) && $_FILES['archivo_aprobacion']['error'] === UPLOAD_ERR_OK;

    if ($archivo_subido) {
        $archivo = $_FILES['archivo_aprobacion'];

        // Validar extensión
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            throw new Exception('Formato de archivo no permitido. Use PDF, JPG o PNG.', 400);
        }

        // Validar tamaño (máx 5 MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            throw new Exception('El archivo excede el tamaño máximo de 5 MB.', 400);
        }

        // Validar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        $mimes_ok = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mime, $mimes_ok)) {
            throw new Exception('El contenido del archivo no corresponde a PDF/JPG/PNG.', 400);
        }

        // Preparar directorio de destino
        $dir_rel = 'subidas/solicitudes/' . $func_id;
        $dir_abs = ROOT_PATH . '/' . $dir_rel;
        if (!is_dir($dir_abs) && !mkdir($dir_abs, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de destino.', 500);
        }

        $nombre_guardar = 'aval_' . $solicitud_id . '_' . date('Ymd_His') . '.' . $ext;
        $ruta_abs = $dir_abs . '/' . $nombre_guardar;
        $ruta_relativa = $dir_rel . '/' . $nombre_guardar;
        $nombre_archivo_orig = basename($archivo['name']);

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_abs)) {
            throw new Exception('Error al guardar el archivo en el servidor.', 500);
        }
        chmod($ruta_abs, 0644);
    }

    // ── INICIO DE TRANSACCIÓN ACID ────────────────────────────────────────────
    $pdo->beginTransaction();

    try {
        // 1) Actualizar estado en solicitudes_empleados
        $stmt = $pdo->prepare("
            UPDATE solicitudes_empleados
            SET    estado                   = 'aprobada',
                   revisado_por             = ?,
                   observaciones_respuesta  = ?,
                   ruta_archivo_aprobacion  = ?,
                   updated_at              = NOW()
            WHERE  id = ?
        ");
        $stmt->execute([$revisor_id, $observaciones, $ruta_relativa, $solicitud_id]);

        // 2) Insertar evento(s) en historial_administrativo
        // Para VACACION: un registro por período solicitado (con periodo_año)
        $historial_id = null;

        $tipo_evento = strtoupper($solicitud['tipo_solicitud'] === 'vacaciones' ? 'VACACION' : 'PERMISO');
        $dias_habiles_solicitud = 0;

        if ($tipo_evento === 'VACACION') {
            // Función LOTTT inline
            $fnDiasLOTTT = function (int $n): int {
                if ($n === 1)
                    return 15;
                return min(18 + ($n - 2), 30);
            };

            // Parsear períodos del motivo: [Períodos: Año 1, Año 2] motivo...
            $motivo_raw = $solicitud['motivo'];
            $anios_sol = [];
            if (preg_match('/^\[Períodos:\s*([^\]]+)\]/u', $motivo_raw, $mp)) {
                preg_match_all('/\d+/', $mp[1], $numMatch);
                $anios_sol = array_map('intval', $numMatch[0]);
            }

            $fechaCurr = new DateTime($solicitud['fecha_inicio']);

            foreach ($anios_sol as $i => $anio) {
                $diasAnio = $fnDiasLOTTT($anio);
                $fechaFin_p = clone $fechaCurr;
                $fechaFin_p->modify('+' . ($diasAnio - 1) . ' days');

                $det_p = json_encode([
                    'origen' => 'solicitud_empleado',
                    'solicitud_id' => $solicitud_id,
                    'tipo_solicitud' => 'vacaciones',
                    'periodo_año' => $anio,
                    'dias_periodo' => $diasAnio,
                    'fecha_inicio' => $fechaCurr->format('Y-m-d'),
                    'fecha_fin' => $fechaFin_p->format('Y-m-d'),
                    'motivo' => $motivo_raw,
                    'aprobado_por' => $revisor_id,
                    'observaciones' => $observaciones,
                ], JSON_UNESCAPED_UNICODE);

                $stmtH = $pdo->prepare("
                    INSERT INTO historial_administrativo
                        (funcionario_id, tipo_evento, fecha_evento, fecha_fin,
                         detalles, ruta_archivo_pdf, nombre_archivo_original,
                         registrado_por, created_at, updated_at)
                    VALUES (?, 'VACACION', ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmtH->execute([
                    $func_id,
                    $fechaCurr->format('Y-m-d'),
                    $fechaFin_p->format('Y-m-d'),
                    $det_p,
                    $ruta_relativa,
                    $nombre_archivo_orig,
                    $revisor_id,
                ]);
                if ($i === 0)
                    $historial_id = $pdo->lastInsertId();

                // Avanzar al inicio del siguiente período
                $fechaCurr->modify('+' . $diasAnio . ' days');
            }

            // Si no se pudieron parsear períodos, insertar un registro genérico
            if (empty($anios_sol)) {
                $detalles = json_encode([
                    'origen' => 'solicitud_empleado',
                    'solicitud_id' => $solicitud_id,
                    'tipo_solicitud' => 'vacaciones',
                    'fecha_inicio' => $solicitud['fecha_inicio'],
                    'fecha_fin' => $solicitud['fecha_fin'],
                    'motivo' => $motivo_raw,
                    'aprobado_por' => $revisor_id,
                    'observaciones' => $observaciones,
                ], JSON_UNESCAPED_UNICODE);
                $stmtH = $pdo->prepare("
                    INSERT INTO historial_administrativo
                        (funcionario_id, tipo_evento, fecha_evento, fecha_fin,
                         detalles, ruta_archivo_pdf, nombre_archivo_original,
                         registrado_por, created_at, updated_at)
                    VALUES (?, 'VACACION', ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmtH->execute([
                    $func_id,
                    $solicitud['fecha_inicio'],
                    $solicitud['fecha_fin'],
                    $detalles,
                    $ruta_relativa,
                    $nombre_archivo_orig,
                    $revisor_id,
                ]);
                $historial_id = $pdo->lastInsertId();
            }

        } else {
            // PERMISO: un único registro
            $d1 = new DateTime($solicitud['fecha_inicio']);
            $d2 = new DateTime($solicitud['fecha_fin']);
            if ($d2 >= $d1) {
                $curr = clone $d1;
                while ($curr <= $d2) {
                    $dia_semana_num = (int) $curr->format('N');
                    if ($dia_semana_num >= 1 && $dia_semana_num <= 5) {
                        $dias_habiles_solicitud++;
                    }
                    $curr->modify('+1 day');
                }
            }

            $detalles = json_encode([
                'origen' => 'solicitud_empleado',
                'solicitud_id' => $solicitud_id,
                'tipo_solicitud' => $solicitud['tipo_solicitud'],
                'fecha_inicio' => $solicitud['fecha_inicio'],
                'fecha_fin' => $solicitud['fecha_fin'],
                'dias_habiles' => $dias_habiles_solicitud,
                'motivo' => $solicitud['motivo'],
                'aprobado_por' => $revisor_id,
                'observaciones' => $observaciones,
            ], JSON_UNESCAPED_UNICODE);

            $stmtH = $pdo->prepare("
                INSERT INTO historial_administrativo
                    (funcionario_id, tipo_evento, fecha_evento, fecha_fin,
                     detalles, ruta_archivo_pdf, nombre_archivo_original,
                     registrado_por, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmtH->execute([
                $func_id,
                $tipo_evento,
                $solicitud['fecha_inicio'],
                $solicitud['fecha_fin'],
                $detalles,
                $ruta_relativa,
                $nombre_archivo_orig,
                $revisor_id,
            ]);
            $historial_id = $pdo->lastInsertId();
        }

        // 3) Opcional: actualizar estado del funcionario si son vacaciones
        if ($solicitud['tipo_solicitud'] === 'vacaciones') {
            $hoy = date('Y-m-d');
            if ($solicitud['fecha_inicio'] <= $hoy && $solicitud['fecha_fin'] >= $hoy) {
                $pdo->prepare("UPDATE funcionarios SET estado = 'vacaciones', updated_at = NOW() WHERE id = ?")
                    ->execute([$func_id]);
            }
        }

        // Registrar auditoría (dentro de la transacción)
        registrarAuditoria(
            'APROBAR_SOLICITUD',
            'solicitudes_empleados',
            $solicitud_id,
            ['estado' => 'pendiente'],
            [
                'estado' => 'aprobada',
                'historial_id' => $historial_id,
                'ruta_archivo' => $ruta_relativa,
                'tipo_evento' => $tipo_evento
            ]
        );

        // ── COMMIT ────────────────────────────────────────────────────────────
        $pdo->commit();

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud aprobada. Evento registrado en historial administrativo.',
            'historial_id' => $historial_id,
            'ruta_archivo' => $ruta_relativa
        ]);

    } catch (Exception $e) {
        // ── ROLLBACK ──────────────────────────────────────────────────────────
        $pdo->rollBack();

        // Limpiar el archivo físico si ya fue subido
        if (file_exists($ruta_abs)) {
            @unlink($ruta_abs);
        }

        throw $e; // propagar al catch exterior
    }

} catch (Exception $e) {
    ob_end_clean();
    $code = $e->getCode();
    http_response_code($code >= 400 && $code < 600 ? $code : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
