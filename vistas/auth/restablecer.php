<?php
/**
 * Vista: Restablecer Contraseña
 * Formulario para establecer nueva contraseña con token
 */

// Cargar configuración (incluye sesiones y seguridad)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Usuario.php';

$token = $_GET['token'] ?? '';
$mensaje = '';
$error = '';
$token_valido = false;

// Verificar token
if ($token) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, f.nombres, f.apellidos
            FROM usuarios u
            INNER JOIN funcionarios f ON u.funcionario_id = f.id
            WHERE u.token_recuperacion = ? 
            AND u.token_expiracion > NOW()
            AND u.estado = 'activo'
        ");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $token_valido = true;
        } else {
            $error = 'El enlace de recuperación es inválido o ha expirado';
        }
    } catch (Exception $e) {
        $error = 'Error al verificar el token';
        error_log("Error en verificación de token: " . $e->getMessage());
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
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
            
            // Cambiar contraseña
            if ($modeloUsuario->cambiarPassword($usuario['id'], $nueva_password)) {
                // Limpiar token
                $stmt = $db->prepare("
                    UPDATE usuarios 
                    SET token_recuperacion = NULL, token_expiracion = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$usuario['id']]);
                
                // Registrar en auditoría
                registrarAuditoria('RESTABLECER_PASSWORD', 'usuarios', $usuario['id']);
                
                $mensaje = 'Contraseña restablecida exitosamente. Puede iniciar sesión con su nueva contraseña.';
                $token_valido = false;
            } else {
                $error = 'Error al cambiar la contraseña';
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema. Por favor, intente más tarde.';
            error_log("Error al restablecer contraseña: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - <?php echo APP_NAME; ?></title>
    
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
        
        /* Panel izquierdo - Branding */
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
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
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
            animation: float 3s ease-in-out infinite;
            overflow: hidden;
        }
        
        .login-header .logo img {
            width: 85%;
            height: 85%;
            object-fit: contain;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        /* Ocultar textos del header */
        .login-header h1,
        .login-header p {
            display: none;
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
        <img src="../../publico/imagenes/cintillo.png" alt="Gobierno Bolivariano - ISPEB - Dirección de Telemática">
    </div>
    
    <!-- Contenedor centrado para el formulario -->
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo"><img src="../../publico/imagenes/logo-telematica-letras-blancas.png" alt="ISPEB Telemática"></div>
            </div>
            
            <div class="login-body">
                <h2>Restablecer Contraseña</h2>
                <p class="subtitle">Configure su nueva contraseña de acceso</p>
                
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
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">
                            Ir al Login
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($token_valido): ?>
                    <div class="user-info">
                        Restableciendo contraseña para: <strong><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></strong>
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
                        
                        <button type="submit" class="btn btn-primary">
                            Restablecer Contraseña
                        </button>
                    </form>
                <?php elseif (!$mensaje): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span>El enlace de recuperación no es válido o ha expirado.</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="login-footer">
                <p>
                    <a href="<?php echo APP_URL; ?>/index.php">Volver al login</a>
                </p>
                <p style="margin-top: 12px; font-size: 12px;">
                    © <?php echo date('Y'); ?> ISPEB - Todos los derechos reservados
                </p>
            </div>
        </div>
    </div>
</body>
</html>
