<?php
/**
 * AJAX: Calcular estadísticas de vacaciones
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

verificarSesion();

header('Content-Type: application/json');

$funcionario_id = $_GET['funcionario_id'] ?? 0;

if (!$funcionario_id) {
    echo json_encode(['error' => 'ID de funcionario no proporcionado']);
    exit;
}

try {
    $db = getDB();
    
    // Obtener antigüedad del funcionario
    $stmt = $db->prepare("
        SELECT 
            TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE()) as antiguedad_anos,
            fecha_ingreso
        FROM funcionarios
        WHERE id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$funcionario) {
        echo json_encode(['error' => 'Funcionario no encontrado']);
        exit;
    }
    
    $antiguedad = $funcionario['antiguedad_anos'];
    
    // Calcular días disponibles: 15 días por año de servicio
    $dias_disponibles = 15 * $antiguedad;
    
    // Obtener días disfrutados del año actual
    $stmt_usados = $db->prepare("
        SELECT 
            COALESCE(SUM(DATEDIFF(fecha_fin, fecha_evento)), 0) as dias_usados
        FROM historial_administrativo
        WHERE funcionario_id = ? 
        AND tipo_evento = 'VACACION'
        AND YEAR(fecha_evento) = YEAR(CURDATE())
    ");
    $stmt_usados->execute([$funcionario_id]);
    $result = $stmt_usados->fetch(PDO::FETCH_ASSOC);
    $dias_usados = $result['dias_usados'];
    
    // Calcular días pendientes
    $dias_pendientes = $dias_disponibles - $dias_usados;
    
    // Determinar nivel de alerta
    $alerta = 'normal';
    if ($dias_pendientes > 45) {
        $alerta = 'alta'; // Muchos días acumulados
    } elseif ($dias_pendientes > 30) {
        $alerta = 'media';
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'antiguedad_anos' => $antiguedad,
            'dias_disponibles' => $dias_disponibles,
            'dias_usados' => $dias_usados,
            'dias_pendientes' => $dias_pendientes,
            'porcentaje_usado' => $dias_disponibles > 0 ? round(($dias_usados / $dias_disponibles) * 100, 1) : 0,
            'alerta' => $alerta
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en calcular_vacaciones: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al calcular vacaciones: ' . $e->getMessage()
    ]);
}
