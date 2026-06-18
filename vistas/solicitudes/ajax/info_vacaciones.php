<?php
/**
 * AJAX: Info de Vacaciones del Empleado — Sistema de Períodos LOTTT
 * Retorna períodos vacacionales generados y su estado (tomado/disponible).
 *
 * Regla LOTTT: Año 1 → 15d, Año 2 → 18d, Año N≥2 → min(18+(N-2), 30)
 */
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/database.php';

verificarSesion();

/**
 * Días hábiles del período vacacional para el año N de servicio
 */
function diasPeriodo(int $n): int {
    if ($n === 1) return 15;
    return min(18 + ($n - 2), 30);
}

try {
    $pdo            = getDB();
    $funcionario_id = (int)($_SESSION['funcionario_id'] ?? 0);

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
    $hoy            = new DateTime('today');
    $fecha_ingreso  = new DateTime($func['fecha_ingreso']);
    $diff           = $hoy->diff($fecha_ingreso);
    $anios_servicio = $diff->y;
    $meses_parciales = $diff->m;
    $tiene_derecho  = $anios_servicio >= 1;

    // ── 3. Períodos ya tomados (identificados por periodo_año en detalles) ─────
    $periodos_tomados = [];
    if ($tiene_derecho) {
        $stmt = $pdo->prepare("
            SELECT
                CAST(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.periodo_año')) AS UNSIGNED) AS periodo_año,
                fecha_evento AS fecha_inicio,
                fecha_fin
            FROM historial_administrativo
            WHERE funcionario_id = ?
              AND tipo_evento = 'VACACION'
              AND JSON_EXTRACT(detalles, '$.periodo_año') IS NOT NULL
        ");
        $stmt->execute([$fid]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pt) {
            $key = (int)$pt['periodo_año'];
            $periodos_tomados[$key] = [
                'fecha_inicio' => $pt['fecha_inicio'],
                'fecha_fin'    => $pt['fecha_fin'],
            ];
        }
    }

    // ── 4. Construir lista de períodos ────────────────────────────────────────
    $periodos = [];
    $periodos_disponibles_count  = 0;
    $total_dias_disponibles      = 0;
    $total_dias_correspondientes = 0;
    $dias_tomados_total          = 0;

    for ($n = 1; $n <= $anios_servicio; $n++) {
        $dias   = diasPeriodo($n);
        $tomado = isset($periodos_tomados[$n]);

        $total_dias_correspondientes += $dias;

        $periodo = [
            'año'    => $n,
            'dias'   => $dias,
            'tomado' => $tomado,
        ];

        if ($tomado) {
            $periodo['fecha_inicio'] = $periodos_tomados[$n]['fecha_inicio'];
            $periodo['fecha_fin']    = $periodos_tomados[$n]['fecha_fin'];
            $dias_tomados_total += $dias;
        } else {
            $periodos_disponibles_count++;
            $total_dias_disponibles += $dias;
        }

        $periodos[] = $periodo;
    }

    // ── 5. Solicitudes pendientes (por compatibilidad) ────────────────────────
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM solicitudes_empleados
        WHERE  funcionario_id = ?
          AND  tipo_solicitud = 'vacaciones'
          AND  estado = 'pendiente'
    ");
    $stmt->execute([$fid]);
    $solicitudes_pendientes = (int)$stmt->fetchColumn();

    ob_end_clean();
    echo json_encode([
        'success'          => true,
        'anios_servicio'   => $anios_servicio,
        'meses_parciales'  => $meses_parciales,
        'fecha_ingreso'    => $fecha_ingreso->format('d/m/Y'),
        'tiene_derecho'    => $tiene_derecho,
        'periodos'         => $periodos,
        'periodos_disponibles'   => $periodos_disponibles_count,
        'total_dias_disponibles' => $total_dias_disponibles,
        'total_dias_correspondientes' => $total_dias_correspondientes,
        'dias_tomados'     => $dias_tomados_total,
        'solicitudes_pendientes' => $solicitudes_pendientes,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    $code = $e->getCode();
    http_response_code($code >= 400 && $code < 600 ? $code : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
