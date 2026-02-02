<?php
/**
 * AJAX: Crear usuario para un funcionario desde su perfil
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/username_generator.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    // Solo nivel 1 y 2 pueden crear usuarios
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para crear usuarios');
    }
    
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    $funcionario_id = $data['funcionario_id'] ?? null;
    $password = $data['password'] ?? '';
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres');
    }
    
    $db = getDB();
    
    // Obtener datos del funcionario
    $stmt = $db->prepare("
        SELECT 
            f.id,
            f.nombres,
            f.apellidos,
            CONCAT(f.nombres, ' ', f.apellidos) as nombre_completo
        FROM funcionarios f
        WHERE f.id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch();
    
    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado');
    }
    
    // Verificar que no tenga usuario ya
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE funcionario_id = ?");
    $stmt->execute([$funcionario_id]);
    if ($stmt->fetch()) {
        throw new Exception('Este funcionario ya tiene un usuario asignado');
    }
    
    // Generar username automáticamente
    $username = generarUsernameUnico($db, $funcionario['nombres'], $funcionario['apellidos']);
    
    // Crear usuario
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO usuarios (
            funcionario_id,
            username,
            password_hash,
            estado,
            registro_completado
        ) VALUES (?, ?, ?, 'activo', 1)
    ");
    
    $stmt->execute([
        $funcionario_id,
        $username,
        $password_hash
    ]);
    
    $usuario_id = $db->lastInsertId();
    
    // Registrar en auditoría
    registrarAuditoria(
        'CREAR_USUARIO',
        'usuarios',
        $usuario_id,
        null,
        [
            'username' => $username,
            'funcionario_id' => $funcionario_id,
            'funcionario' => $funcionario['nombre_completo']
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'usuario' => [
            'id' => $usuario_id,
            'username' => $username,
            'funcionario' => $funcionario['nombre_completo']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
