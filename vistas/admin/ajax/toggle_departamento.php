<?php
/**
 * AJAX: Cambiar estado de departamento
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden cambiar el estado de departamentos');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $departamento_id = $input['departamento_id'] ?? null;
    $nuevo_estado = $input['nuevo_estado'] ?? null;
    
    if (empty($departamento_id) || empty($nuevo_estado)) {
        throw new Exception('Datos incompletos');
    }
    
    if (!in_array($nuevo_estado, ['activo', 'inactivo'])) {
        throw new Exception('Estado no válido');
    }
    
    $db = getDB();
    
    // Verificar que existe
    $stmt = $db->prepare("SELECT nombre, estado FROM departamentos WHERE id = ?");
    $stmt->execute([$departamento_id]);
    $departamento = $stmt->fetch();
    
    if (!$departamento) {
        throw new Exception('Departamento no encontrado');
    }
    
    // Actualizar estado
    $stmt = $db->prepare("UPDATE departamentos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $departamento_id]);
    
    registrarAuditoria('CAMBIAR_ESTADO_DEPARTAMENTO', 'departamentos', $departamento_id, [
        'estado_anterior' => $departamento['estado']
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
