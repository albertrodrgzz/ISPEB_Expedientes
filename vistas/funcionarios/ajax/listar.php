<?php
/**
 * Endpoint: Listar Funcionarios Activos
 * Retorna lista de funcionarios para selects y formularios
 */

require_once '../../../config/sesiones.php';
require_once '../../../config/database.php';

verificarSesion();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT 
            f.id,
            f.cedula,
            f.nombres,
            f.apellidos,
            f.estado,
            f.departamento_id,
            d.nombre as departamento_nombre,
            c.nombre_cargo
        FROM funcionarios f
        LEFT JOIN departamentos d ON f.departamento_id = d.id
        LEFT JOIN cargos c ON f.cargo_id = c.id
        ORDER BY f.nombres, f.apellidos
    ");
    
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => count($funcionarios),
        'data' => $funcionarios
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener funcionarios: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
