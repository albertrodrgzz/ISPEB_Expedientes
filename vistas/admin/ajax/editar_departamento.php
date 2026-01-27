<?php
/**
 * AJAX: Editar departamento
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
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (!$id || empty($nombre)) {
        throw new Exception('Datos incompletos');
    }
    
    $db = getDB();
    
    // Verificar que no exista otro departamento con el mismo nombre
    $stmt = $db->prepare("SELECT id FROM departamentos WHERE nombre = ? AND id != ?");
    $stmt->execute([$nombre, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe otro departamento con ese nombre');
    }
    
    // Actualizar
    $stmt = $db->prepare("
        UPDATE departamentos 
        SET nombre = ?, descripcion = ?
        WHERE id = ?
    ");
    
    $resultado = $stmt->execute([$nombre, $descripcion, $id]);
    
    if ($resultado) {
        registrarAuditoria('ACTUALIZAR_DEPARTAMENTO', 'departamentos', $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Departamento actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el departamento');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
