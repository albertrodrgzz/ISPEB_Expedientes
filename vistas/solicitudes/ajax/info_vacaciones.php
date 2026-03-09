<?php
/**
 * AJAX: Info de Vacaciones del Empleado (Nivel 3)
 * Calcula días disponibles, tomados y período actual según la Ley Orgánica del Trabajo
 * venezolana (LOTTT): 15 días hábiles el primer año + 1 día adicional por año de servicio.
 */
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/database.php';

verificarSesion();

try {
    $pdo            = getDB();
    $funcionario_id = (int)($_SESSION['funcionario_id'] ?? 0);

    // Nivel 3 solo puede consultar sus propios datos
    if ($_SESSION['nivel_acceso'] >= 3) {
        $fid = $funcionario_id;
    } else {
        $fid = (int)($_GET['funcionario_id'] ?? $funcionario_id);
    }

    if ($fid <= 0) {
        throw new Exception('ID de funcionario inválido.', 400);
    }

    // ── 1. Datos del funcionario ──────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT f.id,
               f.fecha_ingreso,
               f.nombres,
               f.apellidos,
               c.nombre_cargo
        FROM   funcionarios f
        LEFT   JOIN cargos c ON f.cargo_id = c.id
        WHERE  f.id = ?
        LIMIT  1
    ");
    $stmt->execute([$fid]);
    $func = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$func) {
        throw new Exception('Funcionario no encontrado.', 404);
    }

    // ── 2. Antigüedad ─────────────────────────────────────────────────────────
    $hoy          = new DateTime('today');
    $fecha_ingreso = new DateTime($func['fecha_ingreso']);
    $diff          = $hoy->diff($fecha_ingreso);
    $anios_servicio = $diff->y;
    $meses_parciales = $diff->m;

    // Período de vacaciones actual (año en curso desde fecha_ingreso)
    // El período va desde el aniversario anterior al próximo
    $aniversario = clone $fecha_ingreso;
    $aniversario->modify("+{$anios_servicio} years");
    if ($aniversario > $hoy) {
        $aniversario->modify('-1 year');
    }
    $fin_periodo = clone $aniversario;
    $fin_periodo->modify('+1 year -1 day');

    $inicio_periodo_str = $aniversario->format('Y-m-d');
    $fin_periodo_str    = $fin_periodo->format('Y-m-d');

    // ── 3. Días que le corresponden (LOTTT) ───────────────────────────────────
    // 15 días el primer año + 1 día adicional por cada año de antigüedad
    // Máximo referencial: 15 + años de servicio
    // LOTTT: requiere 1 año de servicio mínimo. Año 1: 15 días. +1 día por año adicional (máx 30).
    $dias_base        = 15;
    $dias_adicionales = ($anios_servicio >= 1) ? min($anios_servicio - 1, 15) : 0;
    $dias_derecho     = ($anios_servicio >= 1) ? ($dias_base + $dias_adicionales) : 0;

    // ── 4. Días ya gozados (historial_administrativo aprobado, período actual) ─
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(DATEDIFF(
                    LEAST(fecha_fin, ?),
                    GREATEST(fecha_evento, ?)
               ) + 1), 0) AS dias_gozados
        FROM   historial_administrativo
        WHERE  funcionario_id = ?
          AND  tipo_evento    = 'VACACION'
          AND  fecha_evento   <= ?
          AND  fecha_fin      >= ?
    ");
    $stmt->execute([
        $fin_periodo_str,
        $inicio_periodo_str,
        $fid,
        $fin_periodo_str,
        $inicio_periodo_str,
    ]);
    $dias_gozados_hist = (int)$stmt->fetchColumn();

    // ── 5. Días en solicitudes APROBADAS del período actual (que ya no están en historial) ─
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1), 0) AS dias_aprobados
        FROM   solicitudes_empleados
        WHERE  funcionario_id  = ?
          AND  tipo_solicitud  = 'vacaciones'
          AND  estado          = 'aprobada'
          AND  fecha_inicio    >= ?
          AND  fecha_fin       <= ?
    ");
    $stmt->execute([$fid, $inicio_periodo_str, $fin_periodo_str]);
    $dias_aprobados_sol = (int)$stmt->fetchColumn();

    // ── 6. Días en solicitudes PENDIENTES del período actual ──────────────────
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1), 0) AS dias_pendientes
        FROM   solicitudes_empleados
        WHERE  funcionario_id  = ?
          AND  tipo_solicitud  = 'vacaciones'
          AND  estado          = 'pendiente'
          AND  fecha_inicio    >= ?
    ");
    $stmt->execute([$fid, $inicio_periodo_str]);
    $dias_en_tramite = (int)$stmt->fetchColumn();

    // Total tomados/comprometidos
    $dias_tomados    = $dias_gozados_hist + $dias_aprobados_sol;
    $dias_disponibles = max(0, $dias_derecho - $dias_tomados);

    // ── 7. Historial de solicitudes previas ───────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT tipo_solicitud, fecha_inicio, fecha_fin, estado,
               DATEDIFF(fecha_fin, fecha_inicio) + 1 AS dias
        FROM   solicitudes_empleados
        WHERE  funcionario_id = ?
          AND  tipo_solicitud = 'vacaciones'
        ORDER  BY created_at DESC
        LIMIT  5
    ");
    $stmt->execute([$fid]);
    $historial_reciente = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success'          => true,
        'anios_servicio'   => $anios_servicio,
        'meses_parciales'  => $meses_parciales,
        'fecha_ingreso'    => $fecha_ingreso->format('d/m/Y'),
        'periodo_actual'   => [
            'inicio' => $aniversario->format('d/m/Y'),
            'fin'    => $fin_periodo->format('d/m/Y'),
        ],
        'dias_derecho'     => $dias_derecho,
        'dias_base'        => $dias_base,
        'dias_adicionales' => $dias_adicionales,
        'tiene_derecho'    => ($anios_servicio >= 1),
        'dias_tomados'     => $dias_tomados,
        'dias_en_tramite'  => $dias_en_tramite,
        'dias_disponibles' => $dias_disponibles,
        'historial'        => $historial_reciente,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    $code = $e->getCode();
    http_response_code($code >= 400 && $code < 600 ? $code : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
