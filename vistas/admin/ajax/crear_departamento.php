<?php
/**
 * AJAX: Crear nuevo departamento
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden crear departamentos');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (empty($nombre)) {
        throw new Exception('El nombre del departamento es obligatorio');
    }
    
    $db = getDB();
    
    // Verificar que no exista
    $stmt = $db->prepare("SELECT id FROM departamentos WHERE nombre = ?");
    $stmt->execute([$nombre]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un departamento con ese nombre');
    }
    
    // Crear departamento
    $stmt = $db->prepare("
        INSERT INTO departamentos (nombre, descripcion, estado)
        VALUES (?, ?, 'activo')
    ");
    $stmt->execute([$nombre, $descripcion]);
    
    $nuevo_id = $db->lastInsertId();
    
    registrarAuditoria('CREAR_DEPARTAMENTO', 'departamentos', $nuevo_id, null, [
        'nombre' => $nombre
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Departamento creado exitosamente',
        'departamento_id' => $nuevo_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
