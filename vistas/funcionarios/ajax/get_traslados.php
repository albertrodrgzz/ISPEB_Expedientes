<?php
/**
 * AJAX: Obtener Traslados
 * Retorna el historial de traslados de un funcionario
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
        throw new Exception('No tiene permisos para ver este expediente');
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            id,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.departamento_origen')) as departamento_origen,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.departamento_destino')) as departamento_destino,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.motivo')) as motivo,
            fecha_evento as fecha_traslado,
            ruta_archivo_pdf as ruta_archivo,
            nombre_archivo_original,
            created_at
        FROM historial_administrativo
        WHERE funcionario_id = ? AND tipo_evento = 'TRASLADO'
        ORDER BY fecha_evento DESC
    ");
    
    $stmt->execute([$funcionario_id]);
    $traslados = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $traslados
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
