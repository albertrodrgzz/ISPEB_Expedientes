<?php
/**
 * AJAX: Procesar Salida de Funcionario
 * Registra la salida (despido o renuncia) de un funcionario
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

try {
    $funcionario_id = $_POST['funcionario_id'] ?? 0;
    $tipo_salida = $_POST['tipo_salida'] ?? '';
    $fecha_salida = $_POST['fecha_salida'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    
    if (!$funcionario_id || !$tipo_salida || !$fecha_salida || !$motivo) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    // Verificar permisos
    if (!verificarDepartamento($funcionario_id) && $_SESSION['nivel_acceso'] > 2) {
        throw new Exception('No tiene permisos para procesar la salida de este funcionario');
    }
    
    // Validar que no tenga activos asignados
    $db = getDB();
    $stmt = $db->prepare("CALL sp_validar_retiro(?)");
    $stmt->execute([$funcionario_id]);
    $validacion = $stmt->fetch();
    $stmt->closeCursor();
    
    if ($validacion['total_activos'] > 0) {
        throw new Exception('El funcionario tiene activos asignados. Debe entregarlos antes de procesar la salida.');
    }
    
    // Procesar archivo PDF si existe
    $ruta_archivo = null;
    $nombre_archivo_original = null;
    
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../../subidas/salidas/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $nombre_archivo_original = $_FILES['archivo_pdf']['name'];
        $extension = pathinfo($nombre_archivo_original, PATHINFO_EXTENSION);
        $nombre_unico = 'salida_' . $funcionario_id . '_' . time() . '.' . $extension;
        $ruta_completa = $upload_dir . $nombre_unico;
        
        if (!move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $ruta_completa)) {
            throw new Exception('Error al subir el archivo');
        }
        
        $ruta_archivo = 'subidas/salidas/' . $nombre_unico;
    }
    
    // Preparar detalles en JSON
    $detalles = json_encode([
        'motivo' => $motivo,
        'tipo_salida' => $tipo_salida
    ]);
    
    // Insertar en historial_administrativo
    $stmt = $db->prepare("
        INSERT INTO historial_administrativo 
        (funcionario_id, tipo_evento, fecha_evento, detalles, ruta_archivo_pdf, nombre_archivo_original, registrado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $funcionario_id,
        $tipo_salida, // DESPIDO o RENUNCIA
        $fecha_salida,
        $detalles,
        $ruta_archivo,
        $nombre_archivo_original,
        $_SESSION['usuario_id']
    ]);
    
    // Actualizar estado del funcionario a 'inactivo'
    $stmt = $db->prepare("UPDATE funcionarios SET estado = 'inactivo' WHERE id = ?");
    $stmt->execute([$funcionario_id]);
    
    // Registrar en auditoría
    registrarAuditoria(
        'PROCESAR_SALIDA',
        'funcionarios',
        $funcionario_id,
        null,
        ['tipo_salida' => $tipo_salida, 'fecha_salida' => $fecha_salida]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Salida procesada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
