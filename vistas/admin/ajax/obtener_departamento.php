<?php
/**
 * AJAX: Obtener datos de un departamento
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    if (!verificarNivel(1)) {
        throw new Exception('No tiene permisos para realizar esta acción');
    }
    
    if (!isset($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM departamentos WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $departamento = $stmt->fetch();
    
    if (!$departamento) {
        throw new Exception('Departamento no encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $departamento
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
