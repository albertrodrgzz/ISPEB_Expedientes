<?php
/**
 * AJAX: Completar registro de usuario
 * Establece contraseña y preguntas de seguridad
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../modelos/Usuario.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
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
    if (empty($cedula) || empty($password) || empty($password_confirm)) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    if ($password !== $password_confirm) {
        throw new Exception('Las contraseñas no coinciden');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres');
    }
    
    if (empty($pregunta_1) || empty($respuesta_1) || 
        empty($pregunta_2) || empty($respuesta_2) || 
        empty($pregunta_3) || empty($respuesta_3)) {
        throw new Exception('Debe completar las 3 preguntas de seguridad');
    }
    
    if ($pregunta_1 === $pregunta_2 || $pregunta_1 === $pregunta_3 || $pregunta_2 === $pregunta_3) {
        throw new Exception('Debe seleccionar 3 preguntas diferentes');
    }
    
    $db = getDB();
    
    // Buscar usuario pendiente
    $stmt = $db->prepare("
        SELECT u.id, u.funcionario_id, u.username
        FROM usuarios u
        INNER JOIN funcionarios f ON u.funcionario_id = f.id
        WHERE f.cedula = ? AND u.registro_completado = 0
    ");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('No se encontró un usuario pendiente de registro con esa cédula');
    }
    
    // Usar el modelo Usuario para completar el registro
    $modeloUsuario = new Usuario();
    
    $preguntas_respuestas = [
        'pregunta_1' => $pregunta_1,
        'respuesta_1' => $respuesta_1,
        'pregunta_2' => $pregunta_2,
        'respuesta_2' => $respuesta_2,
        'pregunta_3' => $pregunta_3,
        'respuesta_3' => $respuesta_3
    ];
    
    if ($modeloUsuario->completarRegistro($usuario['id'], $password, $preguntas_respuestas)) {
        // Intentar registrar en auditoría (no fallar si esto falla)
        try {
            registrarAuditoria('REGISTRO_COMPLETADO', 'usuarios', $usuario['id']);
        } catch (Exception $e) {
            error_log("Error al registrar auditoría: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => '¡Registro completado exitosamente!',
            'username' => $usuario['username']
        ]);
    } else {
        throw new Exception('Error al completar el registro. Por favor, intente nuevamente.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
