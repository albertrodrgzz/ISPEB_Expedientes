<?php
/**
 * Endpoint: Listar Funcionarios Activos
 * Retorna lista de funcionarios para selects y formularios
 */

// IMPORTANTE: Iniciar output buffering ANTES de cualquier otra cosa
ob_start();

// Suprimir display de errores para AJAX (los errores se logean en archivo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de respuesta JSON (debe estar antes de cualquier output)
header('Content-Type: application/json; charset=utf-8');

require_once '../../../config/seguridad.php';
require_once '../../../config/database.php';

verificarSesion();

try {
    $pdo = getDB();
    
    $stmt = $pdo->query("
        SELECT 
            f.id,
            f.cedula,
            f.nombres,
            f.apellidos,
            f.estado,
            f.departamento_id,
            d.nombre as departamento_nombre,
            c.nombre_cargo
        FROM funcionarios f
        LEFT JOIN departamentos d ON f.departamento_id = d.id
        LEFT JOIN cargos c ON f.cargo_id = c.id
        ORDER BY f.nombres, f.apellidos
    ");
    
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => count($funcionarios),
        'data' => $funcionarios
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener funcionarios: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Limpiar y enviar output buffer
ob_end_flush();
