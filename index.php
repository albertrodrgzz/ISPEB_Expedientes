<?php
/**
 * INDEX.PHP - Punto de Entrada Único
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 * 
 * Este archivo muestra el login si no hay sesión activa,
 * o redirige al dashboard si el usuario ya está autenticado.
 */

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/seguridad.php';

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['usuario_id']) && isset($_SESSION['funcionario_id'])) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

// Procesar login
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = limpiar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingrese usuario y contraseña';
    } else {
        try {
            $db = getDB();
            
            // Buscar usuario con sus datos completos
            $stmt = $db->prepare("
                SELECT 
                    u.id AS usuario_id,
                    u.username,
                    u.password_hash,
                    u.estado AS estado_usuario,
                    u.intentos_fallidos,
                    u.bloqueado_hasta,
                    u.registro_completado,
                    f.id AS funcionario_id,
                    f.nombres,
                    f.apellidos,
                    f.cedula,
                    f.foto AS foto,
                    f.departamento_id,
                    c.id AS cargo_id,
                    c.nombre_cargo,
                    c.nivel_acceso,
                    d.nombre AS departamento
                FROM usuarios u
                INNER JOIN funcionarios f ON u.funcionario_id = f.id
                INNER JOIN cargos c ON f.cargo_id = c.id
                INNER JOIN departamentos d ON f.departamento_id = d.id
                WHERE u.username = ? AND u.estado = 'activo' AND f.estado = 'activo'
            ");
            
            $stmt->execute([$username]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                $error = 'Usuario o contraseña incorrectos';
                error_log("Login fallido: Usuario no encontrado - username: $username");
            } else {
                // Verificar si completó el registro
                if (!$usuario['registro_completado']) {
                    error_log("Login redirigido a registro: Usuario {$usuario['username']} no ha completado registro");
                    $_SESSION['registro_pendiente_cedula'] = $usuario['cedula'];
                    header('Location: ' . APP_URL . '/registro.php');
                    exit;
                }
                
                // Verificar que tenga contraseña (doble verificación)
                if (is_null($usuario['password_hash']) || $usuario['password_hash'] === '') {
                    error_log("Login fallido: Usuario {$usuario['username']} sin contraseña pero marcado como completado");
                    $error = 'Error en la configuración de su cuenta. Contacte al administrador.';
                } else {
                // Verificar si está bloqueado
                if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
                    $error = 'Usuario bloqueado temporalmente. Intente más tarde.';
                } else {
                    // Verificar contraseña SOLO con hash bcrypt (SEGURIDAD)
                    $password_valida = password_verify($password, $usuario['password_hash']);
                    
                    if ($password_valida) {
                        // Login exitoso
                        
                        // Resetear intentos fallidos
                        $stmt = $db->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?");
                        $stmt->execute([$usuario['usuario_id']]);
                        
                        // Inicializar sesión con datos del usuario
                        inicializarSesion($usuario);
                        
                        // Registrar en auditoría
                        registrarAuditoria('LOGIN');
                        
                        // Redirigir al dashboard
                        header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
                        exit;
                    } else {
                        // Contraseña incorrecta
                        $intentos = $usuario['intentos_fallidos'] + 1;
                        
                        if ($intentos >= 5) {
                            // Bloquear por 15 minutos
                            $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            $stmt = $db->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
                            $stmt->execute([$intentos, $bloqueado_hasta, $usuario['usuario_id']]);
                            $error = 'Demasiados intentos fallidos. Usuario bloqueado por 15 minutos.';
                        } else {
                            $stmt = $db->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
                            $stmt->execute([$intentos, $usuario['usuario_id']]);
                            $error = 'Usuario o contraseña incorrectos';
                        }
                        
                        // Registrar intento fallido
                        registrarAuditoria('LOGIN_FALLIDO', 'usuarios', $usuario['usuario_id']);
                    }
                }
            }
            }
        } catch (Exception $e) {
            // Log detallado del error
            error_log("=== ERROR EN LOGIN ===");
            error_log("Mensaje: " . $e->getMessage());
            error_log("Archivo: " . $e->getFile());
            error_log("Línea: " . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());
            error_log("=====================");
            
            // En desarrollo, mostrar el error real
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $error = 'Error: ' . $e->getMessage();
            } else {
                $error = 'Error en el sistema. Por favor, intente más tarde.';
            }
        }
    }
}

// Mostrar mensajes de sesión expirada
if (isset($_GET['error']) && $_GET['error'] === 'sesion_expirada') {
    $mensaje = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $mensaje = 'Ha cerrado sesión exitosamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            background-image: url('publico/imagenes/edificio-ispeb.jpg');
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
            height: calc(100vh - 70px);
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
            max-width: 900px;
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
        
        /* Logo mejorado con múltiples capas */
        .login-header .logo {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            font-size: 40px;
            color: white;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
            animation: float-logo 3s ease-in-out infinite;
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
        
        .login-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .login-header p {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 400;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        
        /* Panel derecho - Formulario */
        .login-body {
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            margin-bottom: 26px;
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
        
        .login-footer .divider {
            margin: 0 10px;
            color: var(--color-border);
        }
        
        @media (max-width: 900px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 460px;
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
                height: calc(100vh - 60px);
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
        <img src="publico/imagenes/logos-institucionales.jpg" alt="Gobierno Bolivariano - ISPEB - Dirección de Telemática">
    </div>
    
    <!-- Contenedor centrado para el login -->
    <div class="login-wrapper">
        <div class="login-container">
        <div class="login-header">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="logo">📁</div>
            <h1>ISPEB</h1>
            <p>Dirección de Telemática</p>
        </div>
        
        <div class="login-body">
            <h2>Iniciar Sesión</h2>
            <p class="subtitle">Ingrese sus credenciales para acceder al sistema</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-info">
                    <span>ℹ️</span>
                    <span><?php echo htmlspecialchars($mensaje); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario</label>
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
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Ingrese su contraseña"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" name="login" class="btn btn-primary">
                    Ingresar al Sistema
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <p>
                ¿Olvidó su contraseña? 
                <a href="vistas/auth/recuperar.php">Recuperar acceso</a>
                <span class="divider">•</span>
                ¿No tiene cuenta? <a href="registro.php">Registrarse</a>
            </p>
            <p style="margin-top: 12px; font-size: 12px;">
                © <?php echo date('Y'); ?> ISPEB - Todos los derechos reservados
            </p>
        </div>
        </div>
    </div>
</body>
</html>
