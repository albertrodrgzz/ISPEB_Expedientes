<?php
/**
 * Endpoint: Obtener Departamentos
 * Retorna lista de departamentos activos
 */

require_once '../../../config/sesiones.php';
require_once '../../../config/database.php';

verificarSesion();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT 
            id,
            nombre,
            descripcion,
            estado
        FROM departamentos
        ORDER BY nombre
    ");
    
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => count($departamentos),
        'data' => $departamentos
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener departamentos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
