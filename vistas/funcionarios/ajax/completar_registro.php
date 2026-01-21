<?php
/**
 * AJAX: Completar Registro
 * Procesa el registro completo con contraseña y preguntas de seguridad
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../modelos/Usuario.php';

header('Content-Type: application/json');

try {
    // Validar datos recibidos
    $cedula = trim($_POST['cedula'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $pregunta_1 = trim($_POST['pregunta_1'] ?? '');
    $respuesta_1 = trim($_POST['respuesta_1'] ?? '');
    $pregunta_2 = trim($_POST['pregunta_2'] ?? '');
    $respuesta_2 = trim($_POST['respuesta_2'] ?? '');
    $pregunta_3 = trim($_POST['pregunta_3'] ?? '');
    $respuesta_3 = trim($_POST['respuesta_3'] ?? '');
    
    // Validaciones
    if (empty($cedula) || empty($password) || empty($pregunta_1) || empty($respuesta_1) || 
        empty($pregunta_2) || empty($respuesta_2) || empty($pregunta_3) || empty($respuesta_3)) {
        echo json_encode([
            'success' => false,
            'error' => 'Todos los campos son obligatorios'
        ]);
        exit;
    }
    
    if ($password !== $password_confirm) {
        echo json_encode([
            'success' => false,
            'error' => 'Las contraseñas no coinciden'
        ]);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode([
            'success' => false,
            'error' => 'La contraseña debe tener al menos 6 caracteres'
        ]);
        exit;
    }
    
    // Verificar que las 3 preguntas sean diferentes
    if ($pregunta_1 === $pregunta_2 || $pregunta_1 === $pregunta_3 || $pregunta_2 === $pregunta_3) {
        echo json_encode([
            'success' => false,
            'error' => 'Debe seleccionar 3 preguntas diferentes'
        ]);
        exit;
    }
    
    // Verificar que las respuestas no estén vacías
    if (strlen($respuesta_1) < 2 || strlen($respuesta_2) < 2 || strlen($respuesta_3) < 2) {
        echo json_encode([
            'success' => false,
            'error' => 'Las respuestas deben tener al menos 2 caracteres'
        ]);
        exit;
    }
    
    // Obtener usuario por cédula
    $modeloUsuario = new Usuario();
    $usuario = $modeloUsuario->obtenerPorCedula($cedula);
    
    if (!$usuario) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    if ($usuario['registro_completado']) {
        echo json_encode([
            'success' => false,
            'error' => 'Este usuario ya completó su registro'
        ]);
        exit;
    }
    
    // Completar registro
    $preguntas_respuestas = [
        'pregunta_1' => $pregunta_1,
        'respuesta_1' => $respuesta_1,
        'pregunta_2' => $pregunta_2,
        'respuesta_2' => $respuesta_2,
        'pregunta_3' => $pregunta_3,
        'respuesta_3' => $respuesta_3
    ];
    
    $resultado = $modeloUsuario->completarRegistro($usuario['id'], $password, $preguntas_respuestas);
    
    if ($resultado) {
        // Registrar en auditoría
        registrarAuditoria('COMPLETAR_REGISTRO', 'usuarios', $usuario['id']);
        
        // Log de éxito
        error_log("Registro completado exitosamente: Usuario {$usuario['username']} (ID: {$usuario['id']})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro completado exitosamente. Ahora puede iniciar sesión.',
            'username' => $usuario['username']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al completar el registro. Intente nuevamente.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en completar_registro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del sistema. Por favor contacte al administrador.'
    ]);
}
