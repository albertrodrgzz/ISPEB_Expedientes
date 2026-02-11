<?php
/**
 * AJAX: Obtener Lista de Funcionarios Activos
 * Para select en formulario de Nombramientos
 * 
 * Retorna: JSON con funcionarios y su cargo_id actual
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
    
    // Obtener funcionarios activos con su cargo actual
    $stmt = $db->query("
        SELECT 
            f.id,
            f.cedula,
            f.nombres,
            f.apellidos,
            f.cargo_id,
            c.nombre_cargo as cargo_actual
        FROM funcionarios f
        INNER JOIN cargos c ON f.cargo_id = c.id
        WHERE f.estado = 'activo'
        ORDER BY f.apellidos ASC, f.nombres ASC
    ");
    
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $funcionarios
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
