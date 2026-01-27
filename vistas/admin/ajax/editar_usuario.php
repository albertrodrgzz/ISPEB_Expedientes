<?php
/**
 * AJAX: Editar usuario
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('No tiene permisos para realizar esta acción');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $id = $_POST['id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo_id = $_POST['cargo_id'] ?? null;
    
    if (!$id || empty($username) || !$cargo_id) {
        throw new Exception('Datos incompletos');
    }
    
    // Validar email si se proporciona
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    $db = getDB();
    
    // Verificar que no exista otro usuario con el mismo username
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe otro usuario con ese nombre de usuario');
    }
    
    // Obtener funcionario_id
    $stmt = $db->prepare("SELECT funcionario_id FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Actualizar usuario
        $stmt = $db->prepare("
            UPDATE usuarios 
            SET username = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $id]);
        
        // Actualizar cargo del funcionario
        $stmt = $db->prepare("
            UPDATE funcionarios 
            SET cargo_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$cargo_id, $usuario['funcionario_id']]);
        
        $db->commit();
        
        registrarAuditoria('ACTUALIZAR_USUARIO', 'usuarios', $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
