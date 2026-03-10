<?php
/**
 * Endpoint: Calcular fecha de retorno considerando solo días hábiles
 * Excluye sábados y domingos
 */

ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

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
    
    // Si la fecha de inicio cae fin de semana, rodarla al lunes
    while ((int)$fecha->format('N') > 5) {
        $fecha->modify('+1 day');
    }
    
    // Contar los días hábiles de la vacación
    while ($dias_contados < $dias_habiles) {
        $dia_semana = (int)$fecha->format('N');
        if ($dia_semana >= 1 && $dia_semana <= 5) {
            $dias_contados++;
        }
        // Solo avanzar si no hemos llegado al total de días solicitados
        if ($dias_contados < $dias_habiles) {
            $fecha->modify('+1 day');
        }
    }
    
    $fecha_ultimo_dia = clone $fecha;
    
    // Calcular fecha de retorno (el siguiente día hábil después de terminar)
    $fecha_retorno = clone $fecha;
    $fecha_retorno->modify('+1 day');
    while ((int)$fecha_retorno->format('N') > 5) {
        $fecha_retorno->modify('+1 day');
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'fecha_inicio' => $fecha_inicio,
            'dias_habiles_solicitados' => $dias_habiles,
            'fecha_ultimo_dia_vacacion' => $fecha_ultimo_dia->format('Y-m-d'),
            'fecha_retorno' => $fecha_retorno->format('Y-m-d'),
            'fecha_retorno_formateada' => $fecha_retorno->format('d/m/Y')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();