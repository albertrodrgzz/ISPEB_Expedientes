<?php
/**
 * Endpoint: Calcular días de vacaciones disponibles según LOTTT
 * Ley Orgánica del Trabajo de Venezuela
 * * Regla: 15 días tras 1 año + 1 día por cada año adicional hasta máx 30 días
 */

ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

verificarSesion();

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
    
    $fecha_ingreso = new DateTime($funcionario['fecha_ingreso']);
    $fecha_actual = new DateTime();
    $intervalo = $fecha_ingreso->diff($fecha_actual);
    $años_servicio = $intervalo->y;
    
    if ($años_servicio < 1) {
        echo json_encode([
            'success' => true,
            'cumple_requisito' => false,
            'mensaje' => 'El funcionario aún no cumple 1 año de servicio',
            'data' => [
                'años_servicio' => 0,
                'fecha_cumple_año' => $fecha_ingreso->modify('+1 year')->format('Y-m-d'),
                'dias_totales' => 0,
                'dias_disponibles' => 0
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // LOTTT
    $dias_base = 15;
    $dias_adicionales = $años_servicio - 1;
    $dias_totales = min($dias_base + $dias_adicionales, 30);
    
    $año_actual = date('Y');
    
    // Obtener todas las vacaciones de este año para este funcionario
    $stmt = $db->prepare("
        SELECT fecha_evento as fecha_inicio, fecha_fin,
               JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_habiles')) as dias_solicitados
        FROM historial_administrativo
        WHERE funcionario_id = ? AND tipo_evento = 'VACACION' AND YEAR(fecha_evento) = ?
    ");
    $stmt->execute([$funcionario_id, $año_actual]);
    $vacaciones_historial = $stmt->fetchAll();
    
    $dias_comprometidos = 0; // Días apartados/solicitados (para restar del total)
    $dias_consumidos_reales = 0; // Días que ya pasaron hasta el día de HOY
    
    $hoy_str = date('Y-m-d');
    
    foreach ($vacaciones_historial as $vac) {
        $dias_solicitados = (int)$vac['dias_solicitados'];
        $dias_comprometidos += $dias_solicitados;
        
        $inicio_vac = clone new DateTime($vac['fecha_inicio']);
        $fin_vac = clone new DateTime($vac['fecha_fin']);
        $hoy_obj = new DateTime($hoy_str);
        
        // Iterar desde el inicio hasta el fin para contar los días que ya pasaron
        $actual = clone $inicio_vac;
        $dias_contados_esta_vacacion = 0;
        
        while ($actual <= $fin_vac && $dias_contados_esta_vacacion < $dias_solicitados) {
            $dia_semana = (int)$actual->format('N');
            if ($dia_semana <= 5) { // Lunes a Viernes
                // Si el día hábil es hoy o en el pasado, está consumido
                if ($actual <= $hoy_obj) {
                    $dias_consumidos_reales++;
                }
                $dias_contados_esta_vacacion++;
            }
            $actual->modify('+1 day');
        }
    }
    
    $dias_disponibles = max(0, $dias_totales - $dias_comprometidos);
    
    echo json_encode([
        'success' => true,
        'cumple_requisito' => true,
        'data' => [
            'años_servicio' => $años_servicio,
            'dias_totales' => $dias_totales,
            'dias_comprometidos' => $dias_comprometidos,
            'dias_consumidos_reales' => $dias_consumidos_reales,
            'dias_disponibles' => $dias_disponibles
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();