<?php
/**
 * AJAX: Obtener datos de un usuario
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('No tiene permisos para realizar esta acción');
    }
    
    if (!isset($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.funcionario_id,
            u.estado,
            CONCAT(f.nombres, ' ', f.apellidos) as nombre_completo,
            f.cedula,
            f.email,
            c.id as cargo_id,
            c.nombre_cargo,
            c.nivel_acceso
        FROM usuarios u
        LEFT JOIN funcionarios f ON u.funcionario_id = f.id
        LEFT JOIN cargos c ON f.cargo_id = c.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $usuario
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
