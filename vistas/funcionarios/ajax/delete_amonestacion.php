<?php
/**
 * AJAX: Eliminar Amonestación
 * Elimina un registro de amonestación
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
    
    // Verificar permisos (solo nivel 1 puede eliminar amonestaciones)
    if (!verificarNivel(1)) {
        throw new Exception('Solo los administradores pueden eliminar amonestaciones');
    }
    
    $db = getDB();
    
    // Obtener información del registro
    $stmt = $db->prepare("
        SELECT funcionario_id, ruta_archivo_pdf as ruta_archivo
        FROM historial_administrativo
        WHERE id = ? AND tipo_evento = 'AMONESTACION'
    ");
    $stmt->execute([$id]);
    $documento = $stmt->fetch();
    
    if (!$documento) {
        throw new Exception('Registro no encontrado');
    }
    
    // Eliminar archivo
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
    registrarAuditoria('ELIMINAR_AMONESTACION', 'historial_administrativo', $id, $documento, null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Amonestación eliminada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
