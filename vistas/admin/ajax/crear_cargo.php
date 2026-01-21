<?php
/**
 * AJAX: Crear nuevo cargo
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden crear cargos');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $nombre_cargo = trim($_POST['nombre_cargo'] ?? '');
    $nivel_acceso = $_POST['nivel_acceso'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (empty($nombre_cargo) || empty($nivel_acceso)) {
        throw new Exception('El nombre y nivel de acceso son obligatorios');
    }
    
    if (!in_array($nivel_acceso, ['1', '2', '3'])) {
        throw new Exception('Nivel de acceso no válido');
    }
    
    $db = getDB();
    
    // Verificar que no exista
    $stmt = $db->prepare("SELECT id FROM cargos WHERE nombre_cargo = ?");
    $stmt->execute([$nombre_cargo]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un cargo con ese nombre');
    }
    
    // Crear cargo
    $stmt = $db->prepare("
        INSERT INTO cargos (nombre_cargo, nivel_acceso, descripcion)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$nombre_cargo, $nivel_acceso, $descripcion]);
    
    $nuevo_id = $db->lastInsertId();
    
    registrarAuditoria('CREAR_CARGO', 'cargos', $nuevo_id, null, [
        'nombre_cargo' => $nombre_cargo,
        'nivel_acceso' => $nivel_acceso
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cargo creado exitosamente',
        'cargo_id' => $nuevo_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
