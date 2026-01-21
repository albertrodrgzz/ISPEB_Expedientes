<?php
/**
 * AJAX: Crear nuevo usuario
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

// Verificar sesión y permisos
try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden crear usuarios');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $funcionario_id = $_POST['funcionario_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones
    if (empty($funcionario_id) || empty($username) || empty($password)) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    if (strlen($username) < 4) {
        throw new Exception('El nombre de usuario debe tener al menos 4 caracteres');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres');
    }
    
    $db = getDB();
    
    // Verificar que el funcionario existe y no tiene usuario
    $stmt = $db->prepare("
        SELECT f.id, CONCAT(f.nombres, ' ', f.apellidos) AS nombre
        FROM funcionarios f
        LEFT JOIN usuarios u ON f.id = u.funcionario_id
        WHERE f.id = ? AND u.id IS NULL
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch();
    
    if (!$funcionario) {
        throw new Exception('El funcionario no existe o ya tiene un usuario asignado');
    }
    
    // Verificar que el username no exista
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('El nombre de usuario ya está en uso');
    }
    
    // Crear usuario
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO usuarios (funcionario_id, username, password_hash, estado)
        VALUES (?, ?, ?, 'activo')
    ");
    $stmt->execute([$funcionario_id, $username, $password_hash]);
    
    $nuevo_usuario_id = $db->lastInsertId();
    
    // Registrar en auditoría
    registrarAuditoria('CREAR_USUARIO', 'usuarios', $nuevo_usuario_id, null, [
        'username' => $username,
        'funcionario' => $funcionario['nombre']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'usuario_id' => $nuevo_usuario_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
