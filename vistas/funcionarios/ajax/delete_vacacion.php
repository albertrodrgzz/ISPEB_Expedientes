<?php
/**
 * AJAX: Eliminar Vacaciones
 * Elimina un registro de vacaciones
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID no proporcionado');
    }
    
    // Verificar permisos
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para eliminar registros');
    }
    
    $db = getDB();
    
    // Obtener información del registro
    $stmt = $db->prepare("
        SELECT funcionario_id, ruta_archivo_pdf as ruta_archivo
        FROM historial_administrativo
        WHERE id = ? AND tipo_evento = 'VACACION'
    ");
    $stmt->execute([$id]);
    $documento = $stmt->fetch();
    
    if (!$documento) {
        throw new Exception('Registro no encontrado');
    }
    
    // Verificar permisos
    if (!verificarDepartamento($documento['funcionario_id'])) {
        throw new Exception('No tiene permisos para eliminar este registro');
    }
    
    // Eliminar archivo si existe
    if ($documento['ruta_archivo']) {
        $ruta_completa = UPLOAD_PATH . $documento['ruta_archivo'];
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
        }
    }
    
    // Eliminar de base de datos
    $stmt = $db->prepare("DELETE FROM historial_administrativo WHERE id = ?");
    $stmt->execute([$id]);
    
    // Registrar en auditoría
    registrarAuditoria('ELIMINAR_VACACIONES', 'historial_administrativo', $id, $documento, null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registro eliminado exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
