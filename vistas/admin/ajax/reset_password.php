<?php
/**
 * AJAX: Resetear contraseña de usuario
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

// Verificar sesión y permisos
try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden resetear contraseñas');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Leer datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $input['usuario_id'] ?? null;
    $nueva_password = $input['nueva_password'] ?? '';
    
    // Validaciones
    if (empty($usuario_id) || empty($nueva_password)) {
        throw new Exception('Datos incompletos');
    }
    
    if (strlen($nueva_password) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres');
    }
    
    $db = getDB();
    
    // Verificar que el usuario existe
    $stmt = $db->prepare("
        SELECT u.id, u.username, CONCAT(f.nombres, ' ', f.apellidos) AS nombre
        FROM usuarios u
        INNER JOIN funcionarios f ON u.funcionario_id = f.id
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Actualizar contraseña
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        UPDATE usuarios 
        SET password_hash = ?, 
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id = ?
    ");
    $stmt->execute([$password_hash, $usuario_id]);
    
    // Registrar en auditoría
    registrarAuditoria('RESETEAR_PASSWORD', 'usuarios', $usuario_id, null, [
        'usuario' => $usuario['username'],
        'funcionario' => $usuario['nombre']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contraseña actualizada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
