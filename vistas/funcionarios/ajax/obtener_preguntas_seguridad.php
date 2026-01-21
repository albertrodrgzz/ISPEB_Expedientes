<?php
/**
 * AJAX: Obtener Preguntas de Seguridad
 * Retorna el catálogo de preguntas de seguridad disponibles
 */


require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT id, pregunta 
        FROM preguntas_seguridad_catalogo 
        WHERE activa = TRUE 
        ORDER BY orden ASC
    ");
    
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $preguntas
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_preguntas_seguridad: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar las preguntas de seguridad'
    ]);
}
