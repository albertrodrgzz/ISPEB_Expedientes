<?php
/**
 * AJAX: Verificar si un funcionario tiene usuario
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    // Solo nivel 1 y 2 pueden verificar usuarios
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para esta acción');
    }
    
    $funcionario_id = $_GET['funcionario_id'] ?? null;
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido');
    }
    
    $db = getDB();
    
    // Verificar si el funcionario existe
    $stmt = $db->prepare("SELECT id, cedula FROM funcionarios WHERE id = ?");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch();
    
    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado');
    }
    
    // Buscar usuario vinculado
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            c.nivel_acceso,
            u.estado,
            u.password_hash,
            u.created_at
        FROM usuarios u
        INNER JOIN funcionarios f ON u.funcionario_id = f.id
        INNER JOIN cargos c ON f.cargo_id = c.id
        WHERE f.cedula = ?
    ");
    $stmt->execute([$funcionario['cedula']]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Determinar estado del usuario
        $estado_usuario = 'activo';
        if ($usuario['password_hash'] === 'PENDING') {
            $estado_usuario = 'pendiente';
        } elseif ($usuario['estado'] === 'inactivo') {
            $estado_usuario = 'inactivo';
        }
        
        echo json_encode([
            'success' => true,
            'tiene_usuario' => true,
            'usuario' => [
                'id' => $usuario['id'],
                'username' => $usuario['username'],
                'nivel_acceso' => $usuario['nivel_acceso'],
                'estado' => $estado_usuario,
                'fecha_creacion' => $usuario['created_at']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'tiene_usuario' => false
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
