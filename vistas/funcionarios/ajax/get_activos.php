<?php
/**
 * AJAX: Obtener activos tecnológicos asignados a un funcionario
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
    
    $stmt = $db->prepare("
        SELECT 
            id,
            tipo,
            marca,
            modelo,
            serial,
            estado,
            fecha_asignacion,
            observaciones
        FROM activos_tecnologicos
        WHERE funcionario_id = ?
        ORDER BY fecha_asignacion DESC
    ");
    
    $stmt->execute([$funcionario_id]);
    $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar activos asignados (bloqueo para retiro)
    $stmt_count = $db->prepare("
        SELECT COUNT(*) as total_asignados
        FROM activos_tecnologicos
        WHERE funcionario_id = ? AND estado = 'Asignado'
    ");
    $stmt_count->execute([$funcionario_id]);
    $count = $stmt_count->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $activos,
        'total_asignados' => $count['total_asignados'],
        'puede_retirarse' => $count['total_asignados'] == 0
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_activos: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al obtener activos: ' . $e->getMessage()
    ]);
}
