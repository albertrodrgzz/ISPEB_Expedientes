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
            $lista_preguntas = [
                1 => '¿Cuál es el nombre de tu primera mascota?',
                2 => '¿En qué ciudad naciste?',
                3 => '¿Cuál es el nombre de tu mejor amigo de la infancia?',
                4 => '¿Cuál es tu comida favorita?',
                5 => '¿Cuál es el nombre de tu escuela primaria?',
                6 => '¿Cuál es el segundo nombre de tu madre?',
                7 => '¿Cuál es el segundo nombre de tu padre?',
                8 => '¿En qué año te graduaste de bachillerato?',
                9 => '¿Cuál es tu color favorito?',
                10 => '¿Cuál es el nombre de tu película favorita?',
                11 => '¿Cuál es tu equipo deportivo favorito?',
                12 => '¿Cuál es el nombre de tu libro favorito?',
                13 => '¿Cuál es tu lugar de vacaciones favorito?',
                14 => '¿Cuál es el nombre de tu primer trabajo?',
                15 => '¿Cuál es tu número de la suerte?'
            ];
            $preguntas = [
                $lista_preguntas[$usuario_data['pregunta_seguridad_1']] ?? ('Pregunta ID: ' . $usuario_data['pregunta_seguridad_1']),
                $lista_preguntas[$usuario_data['pregunta_seguridad_2']] ?? ('Pregunta ID: ' . $usuario_data['pregunta_seguridad_2']),
                $lista_preguntas[$usuario_data['pregunta_seguridad_3']] ?? ('Pregunta ID: ' . $usuario_data['pregunta_seguridad_3'])
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
    <link rel="icon" type="image/png" href="../../publico/imagenes/isotipo.png">
    <link rel="shortcut icon" href="../../publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="../../publico/css/responsive.css">
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
            background: #1a2f48;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
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
            opacity: 0.35;
            z-index: 0;
        }
        
        /* Overlay azul institucional */
        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(160deg, rgba(15,76,129,0.72) 0%, rgba(2,136,209,0.50) 100%);
            z-index: 0;
        }
        
        /* Cintillo institucional — animación de entrada */
        .banner-container {
            width: 100%;
            height: auto;
            background: #ffffff;
            box-shadow: 0 2px 16px rgba(0,0,0,0.12);
            position: relative;
            z-index: 10;
            overflow: visible;
            animation: bannerSlideDown 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes bannerSlideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }

        .banner-container img {
            width: 100%;
            height: auto;
            max-height: none;
            object-fit: contain;
            object-position: center;
            display: block;
        }
        
        /* Wrapper horizontal */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            height: auto;
            padding: 30px;
            position: relative;
            z-index: 1;
        }
        
        /* Card horizontal */
        /* Card del login */
        .login-container {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            box-shadow:
                0 0   0   1px rgba(255,255,255,0.15),
                0 4px 12px rgba(0,0,0,0.4),
                0 16px 40px rgba(0,0,0,0.5),
                0 40px 80px rgba(0,0,0,0.55),
                0 24px 80px rgba(2,136,209,0.35);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 380px 1fr;
            border: 1px solid rgba(255,255,255,0.15);
            animation: loginCardIn 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
            transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1),
                        box-shadow 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
        }

        /* Hover: sube suavemente */
        .login-container:hover {
            transform: translateY(-10px);
            box-shadow:
                0 0   0   1px rgba(255,255,255,0.20),
                0 8px 20px rgba(0,0,0,0.45),
                0 24px 56px rgba(0,0,0,0.55),
                0 56px 96px rgba(0,0,0,0.55),
                0 32px 96px rgba(2,136,209,0.50);
        }

        @keyframes loginCardIn {
            0% {
                opacity: 0;
                transform: translateY(40px);
                filter: blur(6px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }
        
        /* Panel izquierdo - Premium Definitivo */
        .login-header {
            background: linear-gradient(160deg, #0F4C81 0%, #1565A0 50%, #0288D1 100%);
            color: white;
            padding: 44px 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .login-header::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.09) 1px, transparent 1px);
            background-size: 24px 24px;
            z-index: 0;
        }
        .login-header::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(14,123,196,0.10) 50%, transparent 70%);
            top: -100px; right: -80px;
            z-index: 0;
            animation: orb-pulse 7s ease-in-out infinite;
        }
        @keyframes orb-pulse {
            0%,100% { transform: scale(1) rotate(0deg);     opacity:0.7; }
            50%      { transform: scale(1.25) rotate(10deg); opacity:1; }
        }
        .login-header .deco-lines {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(45deg,  rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(-45deg, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .login-header .deco-ring {
            position: absolute;
            width: 360px; height: 360px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.08);
            bottom: -180px; left: 50%;
            transform: translateX(-50%);
            z-index: 0;
            animation: ring-pulse 8s ease-in-out infinite;
        }
        .login-header .deco-ring::after {
            content: ''; position: absolute;
            inset: 28px; border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.05);
        }
        @keyframes ring-pulse {
            0%,100% { transform: translateX(-50%) scale(1);    opacity:0.6; }
            50%      { transform: translateX(-50%) scale(1.10); opacity:1; }
        }
        .login-header .particle:nth-child(1) {
            position: absolute; width:200px; height:200px;
            border: 1.5px solid rgba(255,255,255,0.10);
            border-radius: 50%;
            bottom: -70px; left: -70px;
            animation: spin-slow 28s linear infinite;
        }
        .login-header .particle:nth-child(2) {
            position: absolute; width:110px; height:110px;
            border: 1.5px solid rgba(255,255,255,0.07);
            border-radius: 50%;
            bottom: -20px; left: -20px;
            animation: spin-slow 18s linear infinite reverse;
        }
        .login-header .particle:nth-child(3),
        .login-header .particle:nth-child(4),
        .login-header .particle:nth-child(5) {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.9);
            box-shadow: 0 0 10px rgba(255,255,255,0.7), 0 0 20px rgba(147,197,253,0.4);
            animation: twinkle 5s ease-in-out infinite;
        }
        .login-header .particle:nth-child(3) { width:5px;height:5px; top:20%;left:16%; animation-delay:0s; }
        .login-header .particle:nth-child(4) { width:4px;height:4px; top:60%;left:82%; animation-delay:2s; }
        .login-header .particle:nth-child(5) { width:3px;height:3px; top:35%;left:74%; animation-delay:4s; }
        .particle-extra {
            position: absolute; width:4px; height:4px; border-radius:50%;
            background: rgba(255,255,255,0.6);
            box-shadow: 0 0 8px rgba(147,197,253,0.5);
            top:75%; left:35%;
            animation: twinkle 6s ease-in-out infinite 1.5s;
            z-index: 1;
        }
        @keyframes spin-slow { to { transform: rotate(360deg); } }
        @keyframes twinkle { 0%,100%{opacity:.2;transform:scale(1)} 50%{opacity:1;transform:scale(1.8)} }
        .login-header .logo {
            position: relative; z-index: 2;
            margin-bottom: 16px;
            animation: breathe 5s ease-in-out infinite;
            overflow: hidden;
        }
        .login-header .logo img {
            width: 264px; height: auto;
            object-fit: contain;
            filter: brightness(0) invert(1) drop-shadow(0 4px 16px rgba(0,0,0,0.30));
            display: block;
        }
        .login-header .logo::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.12) 50%, transparent 60%);
            animation: shimmer 5s ease-in-out infinite 2s;
        }
        @keyframes breathe {
            0%,100% { transform: translateY(0)    scale(1); }
            50%      { transform: translateY(-9px) scale(1.022); }
        }
        @keyframes shimmer {
            0%   { transform: translateX(-150%); }
            100% { transform: translateX(250%); }
        }
        .logo-divider {
            width: 56px; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.65), transparent);
            border-radius: 2px;
            margin-bottom: 14px;
            position: relative; z-index: 2;
            animation: divider-glow 3.5s ease-in-out infinite;
        }
        @keyframes divider-glow {
            0%,100% { width:40px; opacity:0.5; }
            50%      { width:76px; opacity:1; }
        }
        .panel-tagline {
            position: relative; z-index: 2;
            font-size: 10.5px; font-weight: 700;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.72);
            line-height: 1.8; max-width: 240px;
        }
        .panel-version {
            position: absolute;
            bottom: 16px; left: 0; right: 0;
            text-align: center; font-size: 10.5px;
            color: rgba(255,255,255,0.28);
            z-index: 2; letter-spacing: 0.8px;
        }
        .login-header h1,
        .login-header p:not(.panel-tagline),
        .panel-features, .panel-subtitle,
        .header-divider, .step-indicator { display: none !important; }


        
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
    <div class="banner-container">
        <img src="../../publico/imagenes/cintillo.png" alt="ISPEB - Dirección de Telemática">
    </div>
    
    <!-- Contenedor centrado para el formulario -->
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="deco-lines"></div>
                <div class="deco-ring"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle-extra"></div>
                <div class="logo">
                    <img src="../../publico/imagenes/logotipo(B).png" alt="ISPEB Telemática">
                </div>
                <div class="logo-divider"></div>
                <p class="panel-tagline">Sistema de Gestión de<br>Expedientes Digitales</p>
                <div class="panel-version">SIGED v3.1 &middot; ISPEB © 2026</div>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
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
                            <span><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
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
                        <span><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
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
                        <span><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
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
