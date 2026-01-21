<?php
/**
 * AJAX: Subir Nombramiento
 * Procesa la subida de un documento de nombramiento
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
    
    // Verificar permisos (solo nivel 1 y 2 pueden subir documentos)
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para subir documentos');
    }
    
    if (!verificarDepartamento($funcionario_id)) {
        throw new Exception('No tiene permisos para editar este funcionario');
    }
    
    // Validar datos del formulario
    $categoria = limpiar($_POST['categoria'] ?? '');
    $titulo = limpiar($_POST['titulo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    
    if (empty($titulo) || empty($fecha_inicio)) {
        throw new Exception('El título y la fecha de inicio son obligatorios');
    }
    
    // Validar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debe seleccionar un archivo');
    }
    
    $archivo = $_FILES['archivo'];
    
    // Validar tipo de archivo (solo PDF e imágenes)
    $tipos_permitidos = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $tipos_permitidos)) {
        throw new Exception('Solo se permiten archivos PDF, JPG o PNG');
    }
    
    // Validar tamaño (máximo 5MB)
    if ($archivo['size'] > MAX_FILE_SIZE) {
        throw new Exception('El archivo no debe superar los 5MB');
    }
    
    // Crear directorio si no existe
    $directorio = UPLOAD_PATH . "funcionarios/{$funcionario_id}/nombramientos/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid('nombramiento_') . '.' . $extension;
    $ruta_completa = $directorio . $nombre_archivo;
    $ruta_relativa = "funcionarios/{$funcionario_id}/nombramientos/" . $nombre_archivo;
    
    // Mover archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Preparar detalles en formato JSON
    $detalles = json_encode([
        'categoria' => $categoria ?: 'Nombramiento',
        'titulo' => $titulo,
        'descripcion' => $descripcion
    ], JSON_UNESCAPED_UNICODE);
    
    // Guardar en base de datos
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO historial_administrativo 
        (funcionario_id, tipo_evento, fecha_evento, fecha_fin, detalles,
         ruta_archivo_pdf, nombre_archivo_original, registrado_por)
        VALUES (?, 'NOMBRAMIENTO', ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $funcionario_id,
        $fecha_inicio,
        $fecha_fin,
        $detalles,
        $ruta_relativa,
        $archivo['name'],
        $_SESSION['usuario_id']
    ]);
    
    $id = $db->lastInsertId();
    
    // Registrar en auditoría
    registrarAuditoria('SUBIR_NOMBRAMIENTO', 'historial_administrativo', $id, null, [
        'funcionario_id' => $funcionario_id,
        'titulo' => $titulo,
        'tipo_evento' => 'NOMBRAMIENTO'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Nombramiento registrado exitosamente',
        'id' => $id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
