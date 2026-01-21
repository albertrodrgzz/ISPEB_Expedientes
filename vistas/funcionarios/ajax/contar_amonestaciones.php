<?php
/**
 * AJAX: Contar amonestaciones y calcular nivel de riesgo
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

verificarSesion();

header('Content-Type: application/json');

$funcionario_id = $_GET['funcionario_id'] ?? 0;

if (!$funcionario_id) {
    echo json_encode(['error' => 'ID de funcionario no proporcionado']);
    exit;
}

try {
    $db = getDB();
    
    // Contar amonestaciones activas
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM historial_administrativo
        WHERE funcionario_id = ? 
        AND tipo_evento = 'AMONESTACION'
    ");
    $stmt->execute([$funcionario_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_amonestaciones = $result['total'];
    
    // Determinar nivel de riesgo
    $nivel_riesgo = 'sin_riesgo';
    $mensaje = 'Sin amonestaciones registradas';
    $color = '#10b981'; // Verde
    
    if ($total_amonestaciones == 1) {
        $nivel_riesgo = 'riesgo_bajo';
        $mensaje = '1 amonestación registrada';
        $color = '#f59e0b'; // Amarillo
    } elseif ($total_amonestaciones >= 2) {
        $nivel_riesgo = 'riesgo_alto';
        $mensaje = $total_amonestaciones . ' amonestaciones registradas - ALERTA: Próxima falta requiere expediente de expulsión';
        $color = '#ef4444'; // Rojo
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_amonestaciones' => $total_amonestaciones,
            'nivel_riesgo' => $nivel_riesgo,
            'mensaje' => $mensaje,
            'color' => $color
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en contar_amonestaciones: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al contar amonestaciones: ' . $e->getMessage()
    ]);
}
