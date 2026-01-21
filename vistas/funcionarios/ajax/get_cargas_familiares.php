<?php
/**
 * AJAX: Obtener cargas familiares de un funcionario
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
            nombre_completo,
            fecha_nacimiento,
            parentesco,
            cedula,
            observaciones,
            TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad
        FROM cargas_familiares
        WHERE funcionario_id = ?
        ORDER BY parentesco, fecha_nacimiento DESC
    ");
    
    $stmt->execute([$funcionario_id]);
    $cargas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $cargas
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_cargas_familiares: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al obtener cargas familiares: ' . $e->getMessage()
    ]);
}
