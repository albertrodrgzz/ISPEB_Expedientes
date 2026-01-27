<?php
/**
 * AJAX: Obtener detalles de un evento de auditoría
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
    $stmt = $db->prepare("
        SELECT 
            a.*,
            CONCAT(f.nombres, ' ', f.apellidos) as usuario_nombre,
            f.cedula as usuario_cedula
        FROM auditoria a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN funcionarios f ON u.funcionario_id = f.id
        WHERE a.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        throw new Exception('Evento no encontrado');
    }
    
    // Decodificar JSON si existe
    if ($evento['datos_anteriores']) {
        $evento['datos_anteriores'] = json_decode($evento['datos_anteriores'], true);
    }
    if ($evento['datos_nuevos']) {
        $evento['datos_nuevos'] = json_decode($evento['datos_nuevos'], true);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $evento
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
