<?php
/**
 * AJAX: Editar cargo
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
    $nombre_cargo = trim($_POST['nombre_cargo'] ?? '');
    $nivel_acceso = $_POST['nivel_acceso'] ?? null;
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (!$id || empty($nombre_cargo) || !$nivel_acceso) {
        throw new Exception('Datos incompletos');
    }
    
    if (!in_array($nivel_acceso, [1, 2, 3])) {
        throw new Exception('Nivel de acceso inválido');
    }
    
    $db = getDB();
    
    // Verificar que no exista otro cargo con el mismo nombre
    $stmt = $db->prepare("SELECT id FROM cargos WHERE nombre_cargo = ? AND id != ?");
    $stmt->execute([$nombre_cargo, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe otro cargo con ese nombre');
    }
    
    // Actualizar
    $stmt = $db->prepare("
        UPDATE cargos 
        SET nombre_cargo = ?, nivel_acceso = ?, descripcion = ?
        WHERE id = ?
    ");
    
    $resultado = $stmt->execute([$nombre_cargo, $nivel_acceso, $descripcion, $id]);
    
    if ($resultado) {
        registrarAuditoria('ACTUALIZAR_CARGO', 'cargos', $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cargo actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el cargo');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
