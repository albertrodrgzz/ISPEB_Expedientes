<?php
/**
 * AJAX: Obtener lista de preguntas de seguridad disponibles
 */

require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, pregunta FROM preguntas_seguridad_catalogo WHERE activa = 1 ORDER BY orden ASC");
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $preguntas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener preguntas de seguridad'
    ]);
}
