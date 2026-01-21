<?php
/**
 * AJAX: Cambiar estado de usuario (activar/desactivar)
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

// Verificar sesión y permisos
try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden cambiar el estado de usuarios');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Leer datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $input['usuario_id'] ?? null;
    $nuevo_estado = $input['nuevo_estado'] ?? null;
    
    // Validaciones
    if (empty($usuario_id) || empty($nuevo_estado)) {
        throw new Exception('Datos incompletos');
    }
    
    if (!in_array($nuevo_estado, ['activo', 'inactivo', 'bloqueado'])) {
        throw new Exception('Estado no válido');
    }
    
    $db = getDB();
    
    // Verificar que el usuario existe
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.estado, CONCAT(f.nombres, ' ', f.apellidos) AS nombre
        FROM usuarios u
        INNER JOIN funcionarios f ON u.funcionario_id = f.id
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }
    
    // No permitir desactivar el propio usuario
    if ($usuario_id == $_SESSION['usuario_id']) {
        throw new Exception('No puede cambiar el estado de su propia cuenta');
    }
    
    // Actualizar estado
    $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $usuario_id]);
    
    // Registrar en auditoría
    registrarAuditoria('CAMBIAR_ESTADO_USUARIO', 'usuarios', $usuario_id, [
        'estado_anterior' => $usuario['estado']
    ], [
        'estado_nuevo' => $nuevo_estado
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
