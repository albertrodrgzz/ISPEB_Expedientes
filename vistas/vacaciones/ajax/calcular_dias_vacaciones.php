<?php
/**
 * Endpoint: Calcular días de vacaciones disponibles según LOTTT
 * Ley Orgánica del Trabajo de Venezuela
 * 
 * Regla: 15 días tras 1 año + 1 día por cada año adicional hasta máx 30 días
 */

// IMPORTANTE: Iniciar output buffering ANTES de cualquier otra cosa
ob_start();

// Suprimir display de errores para AJAX (los errores se logean en archivo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de respuesta JSON (debe estar antes de cualquier output)
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
    
    // Obtener datos del funcionario
    $stmt = $db->prepare("
        SELECT 
            id,
            nombres,
            apellidos,
            fecha_ingreso,
            estado
        FROM funcionarios 
        WHERE id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch();
    
    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado');
    }
    
    // Calcular años de servicio
    $fecha_ingreso = new DateTime($funcionario['fecha_ingreso']);
    $fecha_actual = new DateTime();
    $intervalo = $fecha_ingreso->diff($fecha_actual);
    $años_servicio = $intervalo->y;
    $meses_servicio = $intervalo->m;
    $dias_servicio = $intervalo->days;
    
    // Aplicar regla LOTTT
    // Mínimo 1 año completo para tener derecho
    if ($años_servicio < 1) {
        echo json_encode([
            'success' => true,
            'cumple_requisito' => false,
            'mensaje' => 'El funcionario aún no cumple 1 año de servicio',
            'data' => [
                'años_servicio' => 0,
                'meses_servicio' => $meses_servicio,
                'dias_servicio' => $dias_servicio,
                'fecha_ingreso' => $funcionario['fecha_ingreso'],
                'fecha_cumple_año' => $fecha_ingreso->modify('+1 year')->format('Y-m-d'),
                'dias_totales' => 0,
                'dias_usados' => 0,
                'dias_disponibles' => 0
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Calcular días totales según LOTTT
    // Año 1: 15 días
    // Año 2: 16 días
    // Año 3: 17 días
    // ...
    // Año 15+: 30 días (máximo)
    $dias_base = 15;
    $dias_adicionales = $años_servicio - 1; // Por cada año después del primero
    $dias_totales = min($dias_base + $dias_adicionales, 30); // Máximo 30
    
    // Contar días ya usados en el período actual (año calendario)
    $año_actual = date('Y');
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(
                JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_habiles'))
            ), 0) as total_dias
        FROM historial_administrativo
        WHERE funcionario_id = ?
        AND tipo_evento = 'VACACION'
        AND YEAR(fecha_evento) = ?
    ");
    $stmt->execute([$funcionario_id, $año_actual]);
    $resultado = $stmt->fetch();
    $dias_usados = (int)($resultado['total_dias'] ?? 0);
    
    // Calcular días disponibles
    $dias_disponibles = max(0, $dias_totales - $dias_usados);
    
    // Obtener historial de vacaciones del año
    $stmt = $db->prepare("
        SELECT 
            fecha_evento as fecha_inicio,
            fecha_fin,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_habiles')) as dias,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.observaciones')) as observaciones,
            created_at
        FROM historial_administrativo
        WHERE funcionario_id = ?
        AND tipo_evento = 'VACACION'
        AND YEAR(fecha_evento) = ?
        ORDER BY fecha_evento DESC
    ");
    $stmt->execute([$funcionario_id, $año_actual]);
    $historial = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'cumple_requisito' => true,
        'data' => [
            'funcionario' => [
                'id' => $funcionario['id'],
                'nombre_completo' => $funcionario['nombres'] . ' ' . $funcionario['apellidos'],
                'fecha_ingreso' => $funcionario['fecha_ingreso'],
                'estado' => $funcionario['estado']
            ],
            'años_servicio' => $años_servicio,
            'meses_servicio' => $meses_servicio,
            'dias_servicio' => $dias_servicio,
            'dias_totales' => $dias_totales,
            'dias_usados' => $dias_usados,
            'dias_disponibles' => $dias_disponibles,
            'periodo_actual' => $año_actual,
            'historial' => $historial,
            'lottt_info' => [
                'formula' => '15 días + 1 por cada año adicional (máx 30)',
                'base' => 15,
                'adicionales' => $dias_adicionales,
                'maximo' => 30
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Limpiar y enviar output buffer
ob_end_flush();
