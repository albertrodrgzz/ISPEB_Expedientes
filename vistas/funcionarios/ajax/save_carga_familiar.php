<?php
/**
 * AJAX: Guardar/Actualizar carga familiar
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

verificarSesion();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$funcionario_id = $_POST['funcionario_id'] ?? 0;
$id = $_POST['id'] ?? 0;
$nombre_completo = limpiar($_POST['nombre_completo'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$parentesco = $_POST['parentesco'] ?? '';
$cedula = limpiar($_POST['cedula'] ?? '');
$observaciones = limpiar($_POST['observaciones'] ?? '');

if (!$funcionario_id || !$nombre_completo || !$fecha_nacimiento || !$parentesco) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    $db = getDB();
    
    if ($id > 0) {
        // Actualizar
        $stmt = $db->prepare("
            UPDATE cargas_familiares 
            SET nombre_completo = ?, 
                fecha_nacimiento = ?, 
                parentesco = ?, 
                cedula = ?, 
                observaciones = ?
            WHERE id = ? AND funcionario_id = ?
        ");
        
        $stmt->execute([
            $nombre_completo,
            $fecha_nacimiento,
            $parentesco,
            $cedula,
            $observaciones,
            $id,
            $funcionario_id
        ]);
        
        $mensaje = 'Carga familiar actualizada exitosamente';
    } else {
        // Insertar
        $stmt = $db->prepare("
            INSERT INTO cargas_familiares 
            (funcionario_id, nombre_completo, fecha_nacimiento, parentesco, cedula, observaciones)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $funcionario_id,
            $nombre_completo,
            $fecha_nacimiento,
            $parentesco,
            $cedula,
            $observaciones
        ]);
        
        $mensaje = 'Carga familiar registrada exitosamente';
        
        // Actualizar contador de hijos si es hijo/a
        if ($parentesco === 'Hijo/a') {
            $db->exec("
                UPDATE funcionarios 
                SET cantidad_hijos = (
                    SELECT COUNT(*) FROM cargas_familiares 
                    WHERE funcionario_id = $funcionario_id AND parentesco = 'Hijo/a'
                )
                WHERE id = $funcionario_id
            ");
        }
    }
    
    registrarAuditoria('GUARDAR_CARGA_FAMILIAR', 'cargas_familiares', $id ?: $db->lastInsertId());
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);
    
} catch (Exception $e) {
    error_log("Error en save_carga_familiar: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al guardar carga familiar: ' . $e->getMessage()
    ]);
}
