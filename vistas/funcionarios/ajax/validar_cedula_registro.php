<?php
/**
 * AJAX: Validar Cédula para Registro
 * Verifica si existe un empleado con la cédula y si tiene registro pendiente
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../modelos/Usuario.php';

header('Content-Type: application/json');

try {
    $cedula = trim($_POST['cedula'] ?? '');
    
    if (empty($cedula)) {
        echo json_encode([
            'success' => false,
            'error' => 'La cédula es requerida'
        ]);
        exit;
    }
    
    $modeloUsuario = new Usuario();
    $usuario = $modeloUsuario->obtenerPorCedula($cedula);
    
    if (!$usuario) {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontró ningún empleado con esta cédula. Contacte al administrador.'
        ]);
        exit;
    }
    
    // Verificar si ya completó el registro
    if ($usuario['registro_completado']) {
        echo json_encode([
            'success' => false,
            'error' => 'Este usuario ya completó su registro. Puede iniciar sesión directamente.'
        ]);
        exit;
    }
    
    // Verificar si tiene contraseña (por si acaso)
    if (!is_null($usuario['password_hash']) && $usuario['password_hash'] !== '') {
        echo json_encode([
            'success' => false,
            'error' => 'Este usuario ya tiene una contraseña establecida. Intente iniciar sesión.'
        ]);
        exit;
    }
    
    // Todo OK, devolver datos del empleado
    echo json_encode([
        'success' => true,
        'data' => [
            'usuario_id' => $usuario['id'],
            'nombres' => $usuario['nombres'],
            'apellidos' => $usuario['apellidos'],
            'cedula' => $usuario['cedula'],
            'email' => $usuario['email_recuperacion'],
            'username' => $usuario['username']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en validar_cedula_registro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del sistema. Por favor intente nuevamente.'
    ]);
}
