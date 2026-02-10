<?php
/**
 * Endpoint: Obtener Cargos
 * Retorna lista de cargos activos para selects
 */

require_once '../../../config/seguridad.php';
require_once '../../../config/database.php';

verificarSesion();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT 
            id,
            nombre_cargo,
            nivel_acceso,
            descripcion
        FROM cargos
        ORDER BY nombre_cargo
    ");
    
    $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => count($cargos),
        'data' => $cargos
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener cargos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
