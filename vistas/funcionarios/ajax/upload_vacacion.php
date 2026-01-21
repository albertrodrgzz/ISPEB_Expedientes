<?php
/**
 * AJAX: Registrar Vacaciones
 * Procesa el registro de un período de vacaciones
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
    
    // Verificar permisos
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para registrar vacaciones');
    }
    
    if (!verificarDepartamento($funcionario_id)) {
        throw new Exception('No tiene permisos para editar este funcionario');
    }
    
    // Validar datos
    $titulo = limpiar($_POST['titulo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    
    if (empty($titulo) || empty($fecha_inicio) || empty($fecha_fin)) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    // Calcular días totales
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    $dias_totales = $inicio->diff($fin)->days + 1;
    
    if ($dias_totales <= 0) {
        throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio');
    }
    
    // Procesar archivo si existe
    $ruta_relativa = null;
    $nombre_original = null;
    $mime_type = null;
    $tamano = null;
    
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
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
        $directorio = UPLOAD_PATH . "funcionarios/{$funcionario_id}/vacaciones/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Guardar archivo
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = uniqid('vacacion_') . '.' . $extension;
        $ruta_completa = $directorio . $nombre_archivo;
        $ruta_relativa = "funcionarios/{$funcionario_id}/vacaciones/" . $nombre_archivo;
        
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            throw new Exception('Error al guardar el archivo');
        }
        
        $nombre_original = $archivo['name'];
        $tamano = $archivo['size'];
    }
    
    // Preparar detalles en formato JSON
    $detalles = json_encode([
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'dias_totales' => $dias_totales,
        'periodo' => date('Y', strtotime($fecha_inicio))
    ], JSON_UNESCAPED_UNICODE);
    
    // Guardar en base de datos
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO historial_administrativo 
        (funcionario_id, tipo_evento, fecha_evento, fecha_fin, detalles,
         ruta_archivo_pdf, nombre_archivo_original, registrado_por)
        VALUES (?, 'VACACION', ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $funcionario_id,
        $fecha_inicio,
        $fecha_fin,
        $detalles,
        $ruta_relativa,
        $nombre_original,
        $_SESSION['usuario_id']
    ]);
    
    $id = $db->lastInsertId();
    
    // Registrar en auditoría
    registrarAuditoria('REGISTRAR_VACACIONES', 'historial_administrativo', $id, null, [
        'funcionario_id' => $funcionario_id,
        'titulo' => $titulo,
        'tipo_evento' => 'VACACION',
        'dias_totales' => $dias_totales
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Vacaciones registradas exitosamente',
        'id' => $id,
        'dias_totales' => $dias_totales
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
