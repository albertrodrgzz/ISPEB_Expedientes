<?php
/**
 * Calculadora de Vacaciones — Sistema de Períodos LOTTT
 * Usada desde la vista de expediente del funcionario (ver.php)
 *
 * Regla LOTTT:
 *   Año 1 → 15 días | Año 2 → 18 | Año N≥2 → min(18+(N-2), 30)
 * Cada año de servicio genera un período vacacional identificado por periodo_año.
 */
ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../config/sesiones.php';
    require_once '../../../config/database.php';
    require_once '../../../config/seguridad.php';

    verificarSesion();
} catch (Exception $e) {
    error_log("Error en calcular_vacaciones.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de configuración: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

/**
 * Días hábiles correspondientes al período del año N de servicio
 */
function diasPeriodo(int $n): int {
    if ($n === 1) return 15;
    return min(18 + ($n - 2), 30);
}

try {
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido', 400);
    }

    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT id, nombres, apellidos, fecha_ingreso, estado
        FROM funcionarios
        WHERE id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado', 404);
    }

    $fecha_ingreso  = new DateTime($funcionario['fecha_ingreso']);
    $fecha_actual   = new DateTime();
    $antiguedad     = $fecha_ingreso->diff($fecha_actual);
    $anos_servicio  = $antiguedad->y;
    $tiene_derecho  = $anos_servicio >= 1;

    // Obtener períodos ya tomados
    $periodos_tomados = [];
    if ($tiene_derecho) {
        $stmt = $pdo->prepare("
            SELECT
                CAST(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.periodo_año')) AS UNSIGNED) AS periodo_año,
                fecha_evento  AS fecha_inicio,
                fecha_fin
            FROM historial_administrativo
            WHERE funcionario_id = ?
              AND tipo_evento = 'VACACION'
              AND JSON_EXTRACT(detalles, '$.periodo_año') IS NOT NULL
        ");
        $stmt->execute([$funcionario_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pt) {
            $key = (int)$pt['periodo_año'];
            $periodos_tomados[$key] = [
                'fecha_inicio' => $pt['fecha_inicio'],
                'fecha_fin'    => $pt['fecha_fin'],
            ];
        }
    }

    // Construir lista de períodos
    $periodos = [];
    $periodos_disponibles_count = 0;
    $total_dias_disponibles     = 0;
    $total_dias_correspondientes = 0;
    $dias_tomados_total         = 0;

    for ($n = 1; $n <= $anos_servicio; $n++) {
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

    // Alerta
    $alerta = null;
    if ($tiene_derecho) {
        if ($periodos_disponibles_count === 0) {
            $alerta = ['tipo' => 'critico', 'mensaje' => 'Ha consumido todos sus períodos vacacionales disponibles'];
        } elseif ($periodos_disponibles_count === 1) {
            $alerta = ['tipo' => 'advertencia', 'mensaje' => 'Solo queda 1 período vacacional disponible'];
        } elseif ($dias_tomados_total === 0) {
            $alerta = ['tipo' => 'info', 'mensaje' => 'No ha tomado vacaciones aún'];
        }
    } else {
        $alerta = ['tipo' => 'info', 'mensaje' => 'El funcionario aún no cumple el año de servicio requerido para tener derecho a vacaciones según LOTTT'];
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'funcionario'   => [
                'id'             => $funcionario['id'],
                'nombre_completo'=> $funcionario['nombres'] . ' ' . $funcionario['apellidos'],
                'fecha_ingreso'  => $funcionario['fecha_ingreso'],
                'estado'         => $funcionario['estado'],
            ],
            'antiguedad'    => [
                'anos'   => $anos_servicio,
                'meses'  => $antiguedad->m,
                'texto'  => "$anos_servicio años, {$antiguedad->m} meses",
            ],
            'vacaciones'    => [
                'tiene_derecho'           => $tiene_derecho,
                'periodos_totales'        => $periodos,
                'periodos_disponibles'    => $periodos_disponibles_count,
                'total_dias_corresponde'  => $total_dias_correspondientes,
                'total_dias_disponibles'  => $total_dias_disponibles,
                'dias_tomados'            => $dias_tomados_total,
            ],
            'alerta'        => $alerta,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
