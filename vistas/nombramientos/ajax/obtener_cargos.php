<?php
/**
 * AJAX: Obtener Lista de Cargos
 * Para select en formulario de Nombramientos
 * 
 * Retorna: JSON con todos los cargos disponibles
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json; charset=utf-8');

try {
    verificarSesion();
    
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para esta acción', 403);
    }
    
    $db = getDB();
    
    // Obtener todos los cargos ordenados por nombre
    $stmt = $db->query("
        SELECT 
            id,
            nombre_cargo,
            nivel_acceso
        FROM cargos
        ORDER BY nombre_cargo ASC
    ");
    
    $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $cargos
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
