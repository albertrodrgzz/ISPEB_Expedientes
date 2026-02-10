<?php
/**
 * Calculadora de Vacaciones
 * Migrado a historial_administrativo
 * 
 * Calcula días de vacaciones pendientes basado en:
 * - Antigüedad del funcionario
 * - Días ya tomados (registrados en historial)
 */

// IMPORTANTE: Iniciar output buffering ANTES de cualquier otra cosa
ob_start();

// Suprimir display de errores para AJAX (los errores se logean en archivo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de respuesta JSON (debe estar antes de cualquier output)
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../config/sesiones.php';
    require_once '../../../config/database.php';
    require_once '../../../config/seguridad.php';
    
    verificarSesion();
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error en calcular_vacaciones.php - Requires/Session: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de configuración: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

try {
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido', 400);
    }
    
    $pdo = getDB();
    
    // Obtener datos del funcionario
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nombres,
            apellidos,
            fecha_ingreso,
            estado
        FROM funcionarios
        WHERE id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado', 404);
    }
    
    // Calcular antigüedad en años
    $fecha_ingreso = new DateTime($funcionario['fecha_ingreso']);
    $fecha_actual = new DateTime();
    $antiguedad = $fecha_ingreso->diff($fecha_actual);
    $anos_servicio = $antiguedad->y;
    
    // Verificar si le corresponden vacaciones (mínimo 1 año de servicio según LOTTT)
    $tiene_derecho = $anos_servicio >= 1;
    
    // Determinar días que le corresponden según antigüedad
    // Ley Laboral Venezuela: 15 días primer año, luego 1 día adicional por año hasta 15 días extras (máx 30 días)
    $dias_correspondientes = 0;
    $dias_tomados = 0;
    $dias_pendientes = 0;
    $alerta = null;
    
    if ($tiene_derecho) {
        $dias_correspondientes = 15; // Base
        
        if ($anos_servicio > 1) {
            $dias_extras = min($anos_servicio - 1, 15); // Máximo 15 días extras
            $dias_correspondientes += $dias_extras;
        }
        
        // Calcular días ya tomados desde historial_administrativo
        $stmt = $pdo->prepare("
            SELECT 
                SUM(JSON_EXTRACT(detalles, '$.dias_habiles')) as total_dias_tomados
            FROM historial_administrativo
            WHERE funcionario_id = ?
            AND tipo_evento = 'VACACION'
            AND YEAR(fecha_evento) = YEAR(CURDATE())
        ");
        $stmt->execute([$funcionario_id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $dias_tomados = (int) ($resultado['total_dias_tomados'] ?? 0);
        $dias_pendientes = $dias_correspondientes - $dias_tomados;
        
        // Determinar nivel de alerta
        if ($dias_pendientes <= 0) {
            $alerta = [
                'tipo' => 'critico',
                'mensaje' => 'El funcionario ha excedido o agotado sus días de vacaciones correspondientes'
            ];
        } elseif ($dias_pendientes <= 5) {
            $alerta = [
                'tipo' => 'advertencia',
                'mensaje' => 'Quedan pocos días de vacaciones disponibles'
            ];
        } elseif ($dias_pendientes === $dias_correspondientes) {
            $alerta = [
                'tipo' => 'info',
                'mensaje' => 'No ha tomado vacaciones este año'
            ];
        }
    } else {
        // No tiene derecho aún
        $alerta = [
            'tipo' => 'info',
            'mensaje' => 'El funcionario aún no cumple el año de servicio requerido para tener derecho a vacaciones según LOTTT'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'funcionario' => [
                'id' => $funcionario['id'],
                'nombre_completo' => $funcionario['nombres'] . ' ' . $funcionario['apellidos'],
                'fecha_ingreso' => $funcionario['fecha_ingreso'],
                'estado' => $funcionario['estado']
            ],
            'antiguedad' => [
                'anos' => $anos_servicio,
                'meses' => $antiguedad->m,
                'texto' => "$anos_servicio años, {$antiguedad->m} meses"
            ],
            'vacaciones' => [
                'tiene_derecho' => $tiene_derecho,
                'dias_correspondientes' => $dias_correspondientes,
                'dias_tomados' => $dias_tomados,
                'dias_pendientes' => $dias_pendientes,
                'porcentaje_usado' => $dias_correspondientes > 0 
                    ? round(($dias_tomados / $dias_correspondientes) * 100, 1) 
                    : 0
            ],
            'alerta' => $alerta
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Limpiar y enviar output buffer
ob_end_flush();
