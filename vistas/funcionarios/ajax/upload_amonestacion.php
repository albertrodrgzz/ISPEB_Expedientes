<?php
/**
 * AJAX: Registrar Amonestación
 * Procesa el registro de una amonestación
 */


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

try {
    $funcionario_id = $_POST['funcionario_id'] ?? 0;
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario no proporcionado');
    }
    
    // Verificar permisos (solo nivel 1 y 2)
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para registrar amonestaciones');
    }
    
    if (!verificarDepartamento($funcionario_id)) {
        throw new Exception('No tiene permisos para editar este funcionario');
    }
    
    // Validar datos
    $titulo = limpiar($_POST['titulo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha_falta = $_POST['fecha_falta'] ?? null;
    $tipo_falta = limpiar($_POST['tipo_falta'] ?? '');
    $sancion_aplicada = limpiar($_POST['sancion_aplicada'] ?? '');
    
    if (empty($titulo) || empty($fecha_falta) || empty($tipo_falta)) {
        throw new Exception('El título, fecha de falta y tipo de falta son obligatorios');
    }
    
    // Validar archivo (obligatorio para amonestaciones)
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debe adjuntar el memorándum firmado');
    }
    
    $archivo = $_FILES['archivo'];
    
    // Validar tipo
    $tipos_permitidos = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $tipos_permitidos)) {
        throw new Exception('Solo se permiten archivos PDF, JPG o PNG');
    }
    
    if ($archivo['size'] > MAX_FILE_SIZE) {
        throw new Exception('El archivo no debe superar los 5MB');
    }
    
    // Crear directorio
    $directorio = UPLOAD_PATH . "funcionarios/{$funcionario_id}/amonestaciones/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Guardar archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid('amonestacion_') . '.' . $extension;
    $ruta_completa = $directorio . $nombre_archivo;
    $ruta_relativa = "funcionarios/{$funcionario_id}/amonestaciones/" . $nombre_archivo;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Preparar detalles en formato JSON
    $detalles = json_encode([
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'tipo_falta' => $tipo_falta,
        'sancion_aplicada' => $sancion_aplicada
    ], JSON_UNESCAPED_UNICODE);
    
    // Guardar en base de datos
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO historial_administrativo 
        (funcionario_id, tipo_evento, fecha_evento, detalles,
         ruta_archivo_pdf, nombre_archivo_original, registrado_por)
        VALUES (?, 'AMONESTACION', ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $funcionario_id,
        $fecha_falta,
        $detalles,
        $ruta_relativa,
        $archivo['name'],
        $_SESSION['usuario_id']
    ]);
    
    $id = $db->lastInsertId();
    
    // Registrar en auditoría
    registrarAuditoria('REGISTRAR_AMONESTACION', 'historial_administrativo', $id, null, [
        'funcionario_id' => $funcionario_id,
        'titulo' => $titulo,
        'tipo_evento' => 'AMONESTACION',
        'tipo_falta' => $tipo_falta
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Amonestación registrada exitosamente',
        'id' => $id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
