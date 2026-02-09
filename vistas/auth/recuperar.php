<?php
/**
 * Vista: Recuperar Contraseña mediante Preguntas de Seguridad
 * Sistema de recuperación en 4 pasos usando preguntas de seguridad
 */

// Cargar configuración (incluye sesiones y seguridad)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Usuario.php';

// Inicializar variables
$error = '';
$mensaje = '';
$step = $_SESSION['recovery_step'] ?? 1;

// Procesar formularios según el paso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // PASO 1: Validar username
    if (isset($_POST['step1_submit'])) {
        $username = limpiar($_POST['username'] ?? '');
        
        if (empty($username)) {
            $error = 'Por favor, ingrese su nombre de usuario';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    SELECT u.*, f.nombres, f.apellidos, f.cedula
                    FROM usuarios u
                    INNER JOIN funcionarios f ON u.funcionario_id = f.id
                    WHERE u.username = ? AND u.estado = 'activo' AND u.registro_completado = TRUE
                ");
                $stmt->execute([$username]);
                $usuario = $stmt->fetch();
                
                if ($usuario) {
                    // Verificar si el usuario está bloqueado
                    if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
                        $minutos_restantes = ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
                        $error = "Esta cuenta está temporalmente bloqueada. Intente nuevamente en $minutos_restantes minutos.";
                    }
                    // Verificar que tenga preguntas de seguridad configuradas
                    elseif (empty($usuario['pregunta_seguridad_1']) || empty($usuario['pregunta_seguridad_2']) || empty($usuario['pregunta_seguridad_3'])) {
                        $error = 'Este usuario no tiene preguntas de seguridad configuradas. Contacte al administrador.';
                    } else {
                        // Limpiar bloqueo expirado si existe
                        if ($usuario['bloqueado_hasta']) {
                            $stmt = $db->prepare("
                                UPDATE usuarios 
                                SET bloqueado_hasta = NULL, intentos_fallidos = 0 
                                WHERE id = ?
                            ");
                            $stmt->execute([$usuario['id']]);
                        }
                        
                        // Guardar datos en sesión y avanzar al paso 2
                        $_SESSION['recovery_username'] = $username;
                        $_SESSION['recovery_user_id'] = $usuario['id'];
                        $_SESSION['recovery_step'] = 2;
                        $step = 2;
                        
                        // Registrar intento de recuperación
                        registrarAuditoria('RECUPERAR_PASSWORD_INICIO', 'usuarios', $usuario['id']);
                    }
                } else {
                    $error = 'Usuario no encontrado o inactivo';
                }
            } catch (Exception $e) {
                $error = 'Error en el sistema. Por favor, intente más tarde.';
                error_log("Error en recuperación paso 1: " . $e->getMessage());
            }
        }
    }
    
    // PASO 2: Validar respuestas de seguridad
    elseif (isset($_POST['step2_submit'])) {
        if (!isset($_SESSION['recovery_user_id'])) {
            header('Location: recuperar.php');
            exit;
        }
        
        $respuesta_1 = trim($_POST['respuesta_1'] ?? '');
        $respuesta_2 = trim($_POST['respuesta_2'] ?? '');
        $respuesta_3 = trim($_POST['respuesta_3'] ?? '');
        
        if (empty($respuesta_1) || empty($respuesta_2) || empty($respuesta_3)) {
            $error = 'Debe responder todas las preguntas de seguridad';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    SELECT respuesta_seguridad_1, respuesta_seguridad_2, respuesta_seguridad_3,
                           intentos_fallidos, bloqueado_hasta
                    FROM usuarios
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['recovery_user_id']]);
                $usuario = $stmt->fetch();
                
                // Verificar las 3 respuestas (convertir a minúsculas para comparar)
                $respuesta_1_correcta = password_verify(strtolower($respuesta_1), $usuario['respuesta_seguridad_1']);
                $respuesta_2_correcta = password_verify(strtolower($respuesta_2), $usuario['respuesta_seguridad_2']);
                $respuesta_3_correcta = password_verify(strtolower($respuesta_3), $usuario['respuesta_seguridad_3']);
                
                if ($respuesta_1_correcta && $respuesta_2_correcta && $respuesta_3_correcta) {
                    // Todas las respuestas correctas, avanzar al paso 3
                    $_SESSION['recovery_step'] = 3;
                    $step = 3;
                    
                    // Resetear intentos fallidos del usuario
                    $stmt = $db->prepare("
                        UPDATE usuarios 
                        SET intentos_fallidos = 0, bloqueado_hasta = NULL 
                        WHERE id = ?
                    ");
                    $stmt->execute([$_SESSION['recovery_user_id']]);
                    
                    // Registrar éxito en validación
                    registrarAuditoria('RECUPERAR_PASSWORD_VALIDACION_EXITOSA', 'usuarios', $_SESSION['recovery_user_id']);
                } else {
                    // Respuesta incorrecta - incrementar intentos en la base de datos
                    $intentos = $usuario['intentos_fallidos'] + 1;
                    
                    if ($intentos >= 3) {
                        // Bloquear temporalmente en la base de datos
                        $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $stmt = $db->prepare("
                            UPDATE usuarios 
                            SET intentos_fallidos = ?, bloqueado_hasta = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$intentos, $bloqueado_hasta, $_SESSION['recovery_user_id']]);
                        
                        $error = 'Demasiados intentos fallidos. Esta cuenta ha sido bloqueada temporalmente por 15 minutos.';
                        
                        // Registrar bloqueo
                        registrarAuditoria('RECUPERAR_PASSWORD_BLOQUEADO', 'usuarios', $_SESSION['recovery_user_id']);
                        
                        // Limpiar sesión de recuperación
                        unset($_SESSION['recovery_username']);
                        unset($_SESSION['recovery_user_id']);
                        unset($_SESSION['recovery_step']);
                        unset($_SESSION['recovery_attempts']);
                        $step = 1;
                    } else {
                        // Actualizar intentos fallidos en la base de datos
                        $stmt = $db->prepare("
                            UPDATE usuarios 
                            SET intentos_fallidos = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$intentos, $_SESSION['recovery_user_id']]);
                        
                        $intentos_restantes = 3 - $intentos;
                        $error = "Una o más respuestas son incorrectas. Intentos restantes: $intentos_restantes";
                        
                        // Registrar intento fallido
                        registrarAuditoria('RECUPERAR_PASSWORD_INTENTO_FALLIDO', 'usuarios', $_SESSION['recovery_user_id']);
                    }
                }
            } catch (Exception $e) {
                $error = 'Error en el sistema. Por favor, intente más tarde.';
                error_log("Error en recuperación paso 2: " . $e->getMessage());
            }
        }
    }
    
    // PASO 3: Restablecer contraseña
    elseif (isset($_POST['step3_submit'])) {
        if (!isset($_SESSION['recovery_user_id']) || $_SESSION['recovery_step'] != 3) {
            header('Location: recuperar.php');
            exit;
        }
        
        $nueva_password = $_POST['password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        
        if (empty($nueva_password) || empty($confirmar_password)) {
            $error = 'Debe completar ambos campos';
        } elseif (strlen($nueva_password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
        } elseif ($nueva_password !== $confirmar_password) {
            $error = 'Las contraseñas no coinciden';
        } else {
            try {
                $modeloUsuario = new Usuario();
                
                if ($modeloUsuario->cambiarPassword($_SESSION['recovery_user_id'], $nueva_password)) {
                    // Registrar éxito
                    registrarAuditoria('RECUPERAR_PASSWORD_EXITO', 'usuarios', $_SESSION['recovery_user_id']);
                    
                    // Limpiar sesión de recuperación
                    $username = $_SESSION['recovery_username'];
                    unset($_SESSION['recovery_username']);
                    unset($_SESSION['recovery_user_id']);
                    unset($_SESSION['recovery_step']);
                    unset($_SESSION['recovery_attempts']);
                    
                    // Mostrar mensaje de éxito
                    $_SESSION['recovery_step'] = 4;
                    $step = 4;
                    $mensaje = 'Contraseña restablecida exitosamente. Ahora puede iniciar sesión con su nueva contraseña.';
                } else {
                    $error = 'Error al cambiar la contraseña. Intente nuevamente.';
                }
            } catch (Exception $e) {
                $error = 'Error en el sistema. Por favor, intente más tarde.';
                error_log("Error en recuperación paso 3: " . $e->getMessage());
            }
        }
    }
}

// No se necesita verificación de bloqueo en sesión porque ahora es por usuario en la base de datos

// Obtener datos del usuario para mostrar preguntas (solo en paso 2)
$preguntas = [];
$intentos_usuario = 0;
if ($step == 2 && isset($_SESSION['recovery_user_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.pregunta_seguridad_1, u.pregunta_seguridad_2, u.pregunta_seguridad_3,
                   u.intentos_fallidos,
                   f.nombres, f.apellidos
            FROM usuarios u
            INNER JOIN funcionarios f ON u.funcionario_id = f.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['recovery_user_id']]);
        $usuario_data = $stmt->fetch();
        
        if ($usuario_data) {
            $preguntas = [
                $usuario_data['pregunta_seguridad_1'],
                $usuario_data['pregunta_seguridad_2'],
                $usuario_data['pregunta_seguridad_3']
            ];
            $nombre_usuario = $usuario_data['nombres'] . ' ' . $usuario_data['apellidos'];
            $intentos_usuario = $usuario_data['intentos_fallidos'];
        }
    } catch (Exception $e) {
        error_log("Error al obtener preguntas: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --color-primary: #00a8cc;
            --color-primary-dark: #0088aa;
            --color-secondary: #005f73;
            --color-success: #06d6a0;
            --color-warning: #ffd166;
            --color-danger: #ef476f;
            --color-text: #2d3748;
            --color-text-light: #718096;
            --color-bg: #f7fafc;
            --color-white: #ffffff;
            --color-border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        /* Imagen de fondo institucional */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../../publico/imagenes/edificio-ispeb.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.08;
            z-index: 0;
        }
        
        /* Cintillo institucional */
        .banner-container {
            width: 100%;
            height: 70px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }
        
        .banner-container img {
            height: 100%;
            width: auto;
            object-fit: contain;
            padding: 10px 30px;
            max-width: 100%;
        }
        
        /* Wrapper horizontal */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px);
            padding: 30px;
            position: relative;
            z-index: 1;
        }
        
        /* Card horizontal minimalista */
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08),
                        0 0 0 1px rgba(255, 255, 255, 0.5);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 380px 1fr;
            animation: fadeInScale 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Panel izquierdo - Branding con diseño creativo */
        .login-header {
            background: linear-gradient(135deg, #00a8cc 0%, #005f73 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Patrón geométrico de fondo */
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.06) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,0.05) 0%, transparent 40%);
            z-index: 0;
        }
        
        /* Formas geométricas decorativas */
        .login-header::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Partículas flotantes */
        .login-header .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float-particle 8s ease-in-out infinite;
            z-index: 0;
        }
        
        .login-header .particle:nth-child(1) { left: 20%; top: 20%; animation-delay: 0s; animation-duration: 7s; }
        .login-header .particle:nth-child(2) { left: 60%; top: 30%; animation-delay: 1s; animation-duration: 9s; }
        .login-header .particle:nth-child(3) { left: 80%; top: 60%; animation-delay: 2s; animation-duration: 6s; }
        .login-header .particle:nth-child(4) { left: 30%; top: 70%; animation-delay: 1.5s; animation-duration: 8s; }
        .login-header .particle:nth-child(5) { left: 70%; top: 40%; animation-delay: 0.5s; animation-duration: 7.5s; }
        
        @keyframes float-particle {
            0%, 100% { transform: translateY(0px) translateX(0px); opacity: 0.6; }
            25% { transform: translateY(-20px) translateX(10px); opacity: 1; }
            50% { transform: translateY(-40px) translateX(-10px); opacity: 0.8; }
            75% { transform: translateY(-20px) translateX(5px); opacity: 1; }
        }
        
        /* Logo mejorado - Más grande y prominente */
        .login-header .logo {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
            animation: float-logo 3s ease-in-out infinite;
            overflow: hidden;
        }
        
        .login-header .logo img {
            width: 85%;
            height: 85%;
            object-fit: contain;
        }
        
        /* Brillo sutil en el logo */
        .login-header .logo::before {
            content: '';
            position: absolute;
            top: 10%;
            left: 10%;
            right: 10%;
            height: 30%;
            background: linear-gradient(to bottom, rgba(255,255,255,0.3), transparent);
            border-radius: 12px;
            z-index: -1;
        }
        
        @keyframes float-logo {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.02); }
        }
        
        /* Ocultar textos del header */
        .login-header h1,
        .login-header p {
            display: none;
        }
        
        /* Indicador de pasos */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }
        
        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .step-dot.active {
            background: white;
            width: 32px;
            border-radius: 6px;
        }
        
        /* Panel derecho - Formulario */
        .login-body {
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }
        
        /* Scrollbar styling */
        .login-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .login-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        .login-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        .login-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .login-body h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 8px;
        }
        
        .login-body .subtitle {
            font-size: 14px;
            color: var(--color-text-light);
            margin-bottom: 35px;
        }
        
        .user-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #075985;
        }
        
        .user-info strong {
            color: #0c4a6e;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fee;
            color: var(--color-danger);
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #2d7a4f;
            border: 1px solid #cfc;
        }
        
        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            background: #fafbfc;
            color: var(--color-text);
        }
        
        .form-group input:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 168, 204, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group input::placeholder {
            color: #9ca3af;
        }
        
        .btn {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            letter-spacing: 0.3px;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(0, 168, 204, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 168, 204, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: var(--color-text);
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .login-footer {
            padding: 20px 45px;
            text-align: center;
            font-size: 13px;
            color: var(--color-text-light);
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            grid-column: 1 / -1;
        }
        
        .login-footer a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .login-footer a:hover {
            color: var(--color-primary-dark);
        }
        
        @media (max-width: 1000px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 520px;
            }
            
            .login-header {
                padding: 40px 30px;
            }
            
            .login-body {
                padding: 40px 30px;
            }
            
            .login-footer {
                padding: 20px 30px;
            }
        }
        
        @media (max-width: 768px) {
            .banner-container {
                height: 60px;
            }
            
            .banner-container img {
                padding: 8px 20px;
            }
            
            .login-wrapper {
                min-height: calc(100vh - 60px);
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .login-body {
                padding: 30px 25px;
            }
            
            .login-footer {
                padding: 20px 25px;
            }
            
            .login-header .logo {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }
            
            .login-header h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <!-- Banner de ancho completo -->
    <div class="banner-container">
        <img src="../../publico/imagenes/cintillo.png" alt="Gobierno Bolivariano - ISPEB - Dirección de Telemática">
    </div>
    
    <!-- Contenedor centrado para el formulario -->
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="logo"><img src="../../publico/imagenes/logo-telematica-letras-blancas.png" alt="ISPEB Telemática"></div>
                
                <!-- Indicador de pasos -->
                <div class="step-indicator">
                    <div class="step-dot <?php echo $step == 1 ? 'active' : ''; ?>"></div>
                    <div class="step-dot <?php echo $step == 2 ? 'active' : ''; ?>"></div>
                    <div class="step-dot <?php echo $step == 3 ? 'active' : ''; ?>"></div>
                    <div class="step-dot <?php echo $step == 4 ? 'active' : ''; ?>"></div>
                </div>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($mensaje): ?>
                    <div class="alert alert-success">
                        <span>✓</span>
                        <span><?php echo htmlspecialchars($mensaje); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($step == 1): ?>
                    <!-- PASO 1: Ingresar username -->
                    <h2>Recuperar Contraseña</h2>
                    <p class="subtitle">Paso 1 de 3: Ingrese su nombre de usuario</p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Nombre de Usuario</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                placeholder="Ingrese su usuario"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        
                        <button type="submit" name="step1_submit" class="btn btn-primary">
                            Continuar
                        </button>
                    </form>
                    
                <?php elseif ($step == 2): ?>
                    <!-- PASO 2: Responder preguntas de seguridad -->
                    <h2>Preguntas de Seguridad</h2>
                    <p class="subtitle">Paso 2 de 3: Responda sus preguntas de seguridad</p>
                    
                    <?php if (isset($nombre_usuario)): ?>
                        <div class="user-info">
                            Recuperando contraseña para: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($intentos_usuario) && $intentos_usuario > 0): ?>
                        <div class="alert alert-info">
                            <span>ℹ️</span>
                            <span>Intentos fallidos: <?php echo $intentos_usuario; ?> de 3</span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php foreach ($preguntas as $index => $pregunta): ?>
                            <div class="form-group">
                                <label for="respuesta_<?php echo $index + 1; ?>">
                                    <?php echo htmlspecialchars($pregunta); ?>
                                </label>
                                <input 
                                    type="text" 
                                    id="respuesta_<?php echo $index + 1; ?>" 
                                    name="respuesta_<?php echo $index + 1; ?>" 
                                    placeholder="Su respuesta"
                                    required
                                    <?php echo $index == 0 ? 'autofocus' : ''; ?>
                                    autocomplete="off"
                                >
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" name="step2_submit" class="btn btn-primary">
                            Validar Respuestas
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='recuperar.php?reset=1'">
                            ← Volver
                        </button>
                    </form>
                    
                <?php elseif ($step == 3): ?>
                    <!-- PASO 3: Establecer nueva contraseña -->
                    <h2>Nueva Contraseña</h2>
                    <p class="subtitle">Paso 3 de 3: Establezca su nueva contraseña</p>
                    
                    <div class="alert alert-success">
                        <span>✓</span>
                        <span>Identidad verificada correctamente</span>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="password">Nueva Contraseña</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Mínimo 6 caracteres"
                                required
                                minlength="6"
                                autofocus
                                autocomplete="new-password"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_password">Confirmar Contraseña</label>
                            <input 
                                type="password" 
                                id="confirmar_password" 
                                name="confirmar_password" 
                                placeholder="Repita la contraseña"
                                required
                                minlength="6"
                                autocomplete="new-password"
                            >
                        </div>
                        
                        <button type="submit" name="step3_submit" class="btn btn-primary">
                            Restablecer Contraseña
                        </button>
                    </form>
                    
                <?php elseif ($step == 4): ?>
                    <!-- PASO 4: Éxito -->
                    <h2>¡Contraseña Restablecida!</h2>
                    <p class="subtitle">Su contraseña ha sido actualizada exitosamente</p>
                    
                    <div class="alert alert-success">
                        <span>✓</span>
                        <span>Ahora puede iniciar sesión con su nueva contraseña</span>
                    </div>
                    
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">
                        Ir al Login
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="login-footer">
                <p>
                    ¿Recordó su contraseña? 
                    <a href="<?php echo APP_URL; ?>/index.php">Volver al login</a>
                </p>
                <p style="margin-top: 12px; font-size: 12px;">
                    © <?php echo date('Y'); ?> ISPEB - Todos los derechos reservados
                </p>
            </div>
        </div>
    </div>
    
    <?php
    // Limpiar sesión si se solicita reset
    if (isset($_GET['reset'])) {
        unset($_SESSION['recovery_username']);
        unset($_SESSION['recovery_user_id']);
        unset($_SESSION['recovery_step']);
        unset($_SESSION['recovery_attempts']);
        header('Location: recuperar.php');
        exit;
    }
    ?>
</body>
</html>
