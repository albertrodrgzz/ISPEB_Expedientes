<?php
/**
 * AJAX: Validar Retiro de Funcionario
 * Verifica si el funcionario tiene activos asignados antes de procesar su salida
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

try {
    $funcionario_id = $_GET['funcionario_id'] ?? 0;
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario no proporcionado');
    }
    
    // Verificar permisos
    if (!verificarDepartamento($funcionario_id) && $_SESSION['nivel_acceso'] > 2) {
        throw new Exception('No tiene permisos para validar este funcionario');
    }
    
    $db = getDB();
    
    // Llamar al stored procedure
    $stmt = $db->prepare("CALL sp_validar_retiro(?)");
    $stmt->execute([$funcionario_id]);
    $resultado = $stmt->fetch();
    $stmt->closeCursor();
    
    echo json_encode([
        'success' => true,
        'total_activos' => $resultado['total_activos'] ?? 0,
        'lista_activos' => $resultado['lista_activos'] ?? ''
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
