<?php
/**
 * Endpoint: Calcular fecha de retorno considerando solo días hábiles
 * Excluye sábados y domingos
 */

// IMPORTANTE: Iniciar output buffering ANTES de cualquier otra cosa
ob_start();

// Suprimir display de errores para AJAX (los errores se logean en archivo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de respuesta JSON (debe estar antes de cualquier output)
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

verificarSesion();

try {
    $fecha_inicio = $_GET['fecha_inicio'] ?? null;
    $dias_habiles = (int)($_GET['dias_habiles'] ?? 0);
    
    if (!$fecha_inicio || $dias_habiles <= 0) {
        throw new Exception('Parámetros inválidos');
    }
    
    $fecha = new DateTime($fecha_inicio);
    $dias_contados = 0;
    
    // Avanzar solo días hábiles (lunes a viernes)
    while ($dias_contados < $dias_habiles) {
        $fecha->modify('+1 day');
        $dia_semana = (int)$fecha->format('N'); // 1=lunes, 7=domingo
        
        // Si es día hábil (lun-vie)
        if ($dia_semana >= 1 && $dia_semana <= 5) {
            $dias_contados++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'fecha_inicio' => $fecha_inicio,
            'dias_habiles_solicitados' => $dias_habiles,
            'fecha_ultimo_dia_vacacion' => $fecha->format('Y-m-d'),
            'fecha_retorno' => $fecha->modify('+1 day')->format('Y-m-d'),
            'fecha_retorno_formateada' => $fecha->format('d/m/Y')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Limpiar y enviar output buffer
ob_end_flush();
