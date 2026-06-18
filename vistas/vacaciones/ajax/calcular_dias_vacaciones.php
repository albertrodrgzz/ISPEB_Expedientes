<?php
/**
 * Endpoint: Calcular períodos de vacaciones disponibles según LOTTT
 * Ley Orgánica del Trabajo de Venezuela (LOTTT)
 *
 * Regla LOTTT:
 *  - Año 1 de servicio → 15 días hábiles
 *  - Año 2 de servicio → 18 días hábiles
 *  - Año N (N>=2)      → min(18 + (N-2), 30) días hábiles
 *
 * Cada año de servicio genera UN período vacacional.
 * Los períodos tomados se identifican por el campo "periodo_año" en el JSON detalles.
 */

ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

verificarSesion();

/**
 * Calcula los días hábiles correspondientes a un período (año N de servicio)
 */
function calcularDiasPorPeriodo(int $anio_servicio): int {
    if ($anio_servicio === 1) return 15;
    return min(18 + ($anio_servicio - 2), 30);
}

try {
    if (!isset($_GET['funcionario_id']) || empty($_GET['funcionario_id'])) {
        throw new Exception('ID de funcionario requerido');
    }

    $funcionario_id = (int)$_GET['funcionario_id'];
    $db = getDB();
    date_default_timezone_set('America/Caracas');

    $stmt = $db->prepare("
        SELECT id, nombres, apellidos, fecha_ingreso, estado
        FROM funcionarios WHERE id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch();

    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado');
    }

    $fecha_ingreso  = new DateTime($funcionario['fecha_ingreso']);
    $fecha_actual   = new DateTime();
    $intervalo      = $fecha_ingreso->diff($fecha_actual);
    $años_servicio  = $intervalo->y;

    if ($años_servicio < 1) {
        $fecha_cumple = clone $fecha_ingreso;
        $fecha_cumple->modify('+1 year');
        echo json_encode([
            'success'          => true,
            'cumple_requisito' => false,
            'mensaje'          => 'El funcionario aún no cumple 1 año de servicio',
            'data'             => [
                'años_servicio'    => 0,
                'fecha_cumple_año' => $fecha_cumple->format('Y-m-d'),
                'periodos_totales'    => [],
                'periodos_disponibles' => 0,
                'total_dias_disponibles' => 0,
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Obtener los períodos ya tomados (guardados con campo periodo_año en detalles)
    $stmt = $db->prepare("
        SELECT
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.periodo_año')) as periodo_año,
            fecha_evento as fecha_inicio,
            fecha_fin
        FROM historial_administrativo
        WHERE funcionario_id = ?
          AND tipo_evento = 'VACACION'
          AND JSON_EXTRACT(detalles, '$.periodo_año') IS NOT NULL
    ");
    $stmt->execute([$funcionario_id]);
    $periodos_tomados_raw = $stmt->fetchAll();

    // Mapear períodos tomados por número de año
    $periodos_tomados = [];
    foreach ($periodos_tomados_raw as $pt) {
        $anio_key = (int)$pt['periodo_año'];
        $periodos_tomados[$anio_key] = [
            'fecha_inicio' => $pt['fecha_inicio'],
            'fecha_fin'    => $pt['fecha_fin'],
        ];
    }

    // Construir lista de todos los períodos
    $periodos = [];
    $total_dias_disponibles = 0;

    for ($n = 1; $n <= $años_servicio; $n++) {
        $dias = calcularDiasPorPeriodo($n);
        $tomado = isset($periodos_tomados[$n]);

        $periodo = [
            'año'  => $n,
            'dias' => $dias,
            'tomado' => $tomado,
        ];

        if ($tomado) {
            $periodo['fecha_inicio'] = $periodos_tomados[$n]['fecha_inicio'];
            $periodo['fecha_fin']    = $periodos_tomados[$n]['fecha_fin'];
        } else {
            $total_dias_disponibles += $dias;
        }

        $periodos[] = $periodo;
    }

    $periodos_disponibles = count(array_filter($periodos, fn($p) => !$p['tomado']));

    echo json_encode([
        'success'          => true,
        'cumple_requisito' => true,
        'data'             => [
            'años_servicio'          => $años_servicio,
            'periodos_totales'       => $periodos,
            'periodos_disponibles'   => $periodos_disponibles,
            'total_dias_disponibles' => $total_dias_disponibles,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();