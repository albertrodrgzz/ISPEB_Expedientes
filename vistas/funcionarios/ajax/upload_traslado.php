<?php
/**
 * AJAX: Subir Traslado
 * Registra un traslado de funcionario entre departamentos
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

try {
    $funcionario_id = $_POST['funcionario_id'] ?? 0;
    $departamento_origen_id = $_POST['departamento_origen_id'] ?? 0;
    $departamento_destino_id = $_POST['departamento_destino_id'] ?? 0;
    $motivo = $_POST['motivo'] ?? '';
    $fecha_traslado = $_POST['fecha_traslado'] ?? '';
    
    if (!$funcionario_id || !$departamento_origen_id || !$departamento_destino_id || !$motivo || !$fecha_traslado) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    if ($departamento_origen_id == $departamento_destino_id) {
        throw new Exception('El departamento de origen y destino no pueden ser el mismo');
    }
    
    // Verificar permisos (solo nivel 1 y 2 pueden registrar traslados)
    if ($_SESSION['nivel_acceso'] > 2) {
        throw new Exception('No tiene permisos para registrar traslados');
    }
    
    $db = getDB();
    
    // Obtener nombres de departamentos
    $stmt = $db->prepare("SELECT nombre FROM departamentos WHERE id = ?");
    $stmt->execute([$departamento_origen_id]);
    $dept_origen = $stmt->fetch();
    
    $stmt->execute([$departamento_destino_id]);
    $dept_destino = $stmt->fetch();
    
    if (!$dept_origen || !$dept_destino) {
        throw new Exception('Departamento no encontrado');
    }
    
    // Procesar archivo PDF si existe
    $ruta_archivo = null;
    $nombre_archivo_original = null;
    
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../../subidas/traslados/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $nombre_archivo_original = $_FILES['archivo_pdf']['name'];
        $extension = pathinfo($nombre_archivo_original, PATHINFO_EXTENSION);
        
        if (strtolower($extension) !== 'pdf') {
            throw new Exception('Solo se permiten archivos PDF');
        }
        
        $nombre_unico = 'traslado_' . $funcionario_id . '_' . time() . '.pdf';
        $ruta_completa = $upload_dir . $nombre_unico;
        
        if (!move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $ruta_completa)) {
            throw new Exception('Error al subir el archivo');
        }
        
        $ruta_archivo = 'subidas/traslados/' . $nombre_unico;
    }
    
    // Preparar detalles en JSON
    $detalles = json_encode([
        'departamento_origen' => $dept_origen['nombre'],
        'departamento_destino' => $dept_destino['nombre'],
        'departamento_origen_id' => $departamento_origen_id,
        'departamento_destino_id' => $departamento_destino_id,
        'motivo' => $motivo
    ], JSON_UNESCAPED_UNICODE);
    
    // Insertar en historial_administrativo
    $stmt = $db->prepare("
        INSERT INTO historial_administrativo 
        (funcionario_id, tipo_evento, fecha_evento, detalles, ruta_archivo_pdf, nombre_archivo_original, registrado_por)
        VALUES (?, 'TRASLADO', ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $funcionario_id,
        $fecha_traslado,
        $detalles,
        $ruta_archivo,
        $nombre_archivo_original,
        $_SESSION['usuario_id']
    ]);
    
    // Actualizar el departamento del funcionario
    $stmt = $db->prepare("UPDATE funcionarios SET departamento_id = ? WHERE id = ?");
    $stmt->execute([$departamento_destino_id, $funcionario_id]);
    
    // Registrar en auditoría
    registrarAuditoria(
        'REGISTRAR_TRASLADO',
        'historial_administrativo',
        $db->lastInsertId(),
        null,
        [
            'funcionario_id' => $funcionario_id,
            'departamento_origen' => $dept_origen['nombre'],
            'departamento_destino' => $dept_destino['nombre']
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Traslado registrado exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
