<?php
/**
 * AJAX: Obtener Amonestaciones
 * Retorna el historial de amonestaciones de un funcionario
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
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.titulo')) as titulo,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.descripcion')) as descripcion,
            fecha_evento as fecha_falta,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.tipo_falta')) as tipo_falta,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.sancion_aplicada')) as sancion_aplicada,
            ruta_archivo_pdf as ruta_archivo,
            nombre_archivo_original,
            created_at
        FROM historial_administrativo
        WHERE funcionario_id = ? AND tipo_evento = 'AMONESTACION'
        ORDER BY fecha_evento DESC
    ");
    
    $stmt->execute([$funcionario_id]);
    $amonestaciones = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $amonestaciones
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
