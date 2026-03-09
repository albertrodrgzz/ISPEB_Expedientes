<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/responsive.css?v=<?= APP_BUILD ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --color-primary: #0F4C81;
            --color-primary-dark: #0a3560;
            --color-secondary: #0288D1;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-text: #1e293b;
            --color-text-light: #64748b;
            --color-bg: #f1f5f9;
            --color-white: #ffffff;
            --color-border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.14);
            --shadow-lg: 0 10px 30px rgba(15,76,129,0.22);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #1e3a52;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Imagen de fondo - más visible */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url('<?= APP_URL ?>/publico/imagenes/edificio-ispeb.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.45;
            z-index: 0;
        }

        /* Overlay con degradado + patrón de puntos */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background:
                linear-gradient(160deg, rgba(15,76,129,0.60) 0%, rgba(2,136,209,0.38) 100%),
                radial-gradient(circle, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: auto, 32px 32px;
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
            to   { transform: translateY(0);    opacity: 1; }
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
        
        /* Card del login */
        .login-container {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            /* Sombra diseñada para fondo oscuro: glow + profundidad */
            box-shadow:
                0 0   0   1px rgba(255,255,255,0.15),
                0 4px 12px rgba(0,0,0,0.4),
                0 16px 40px rgba(0,0,0,0.5),
                0 40px 80px rgba(0,0,0,0.55),
                0 24px 80px rgba(2,136,209,0.35);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 380px 1fr;
            border: 1px solid rgba(255,255,255,0.15);
            /* Entrada suave desde abajo */
            animation: loginCardIn 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
            /* Hover transition */
            transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1),
                        box-shadow 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
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

        /* Hover: la card sube suavemente */
        .login-container:hover {
            transform: translateY(-10px);
            box-shadow:
                0 0   0   1px rgba(255,255,255,0.20),
                0 8px 20px rgba(0,0,0,0.45),
                0 24px 56px rgba(0,0,0,0.55),
                0 56px 96px rgba(0,0,0,0.55),
                0 32px 96px rgba(2,136,209,0.50);
        }
        
        /* ===== PANEL IZQUIERDO - Premium Definitivo ===== */
        .login-header {
            background: linear-gradient(160deg, #0F4C81 0%, #1565A0 50%, #0288D1 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            padding: 44px 32px;
        }

        /* Cuadrícula de puntos finos */
        .login-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.09) 1px, transparent 1px);
            background-size: 24px 24px;
            z-index: 0;
        }

        /* Orbe de brillo superior derecho - más prominente */
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
            0%,100% { transform: scale(1) rotate(0deg);     opacity: 0.7; }
            50%      { transform: scale(1.25) rotate(10deg); opacity: 1; }
        }

        /* Líneas diagonales decorativas */
        .login-header .deco-lines {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(45deg,  rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(-45deg, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* Anillo grande pulsante centrado abajo */
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
            content: '';
            position: absolute;
            inset: 28px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.05);
        }
        @keyframes ring-pulse {
            0%,100% { transform: translateX(-50%) scale(1);    opacity:0.6; }
            50%      { transform: translateX(-50%) scale(1.10); opacity:1; }
        }

        /* Anillos decorativos - abajo izquierda */
        .login-header .particle:nth-child(1) {
            position: absolute;
            width: 220px; height: 220px;
            border: 1.5px solid rgba(100,180,255,0.12);
            border-radius: 50%;
            bottom: -80px; left: -80px;
            animation: spin-slow 24s linear infinite;
        }
        .login-header .particle:nth-child(2) {
            position: absolute;
            width: 130px; height: 130px;
            border: 1.5px solid rgba(100,180,255,0.08);
            border-radius: 50%;
            bottom: -30px; left: -30px;
            animation: spin-slow 16s linear infinite reverse;
        }
        /* Puntos destellantes */
        .login-header .particle:nth-child(3),
        .login-header .particle:nth-child(4),
        .login-header .particle:nth-child(5) {
            position: absolute;
            border-radius: 50%;
            background: rgba(147,197,253,0.9);
            box-shadow: 0 0 8px rgba(147,197,253,0.7);
            animation: twinkle 5s ease-in-out infinite;
        }
        .login-header .particle:nth-child(3) { width:5px;height:5px; top:22%;left:18%; animation-delay:0s; }
        .login-header .particle:nth-child(4) { width:4px;height:4px; top:62%;left:80%; animation-delay:1.8s; }
        .login-header .particle:nth-child(5) { width:3px;height:3px; top:38%;left:72%; animation-delay:3.2s; }

        @keyframes spin-slow  { to { transform: rotate(360deg); } }
        @keyframes twinkle    { 0%,100%{opacity:.3;transform:scale(1)} 50%{opacity:1;transform:scale(1.6)} }

        /* Logo más grande con shimmer */
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
            position: absolute;
            inset: 0;
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

        /* Línea divisora con glow */
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

        /* Tagline más visible */
        .panel-tagline {
            position: relative; z-index: 2;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.72);
            line-height: 1.8;
            max-width: 240px;
        }

        /* Versión al pie */
        .panel-version {
            position: absolute;
            bottom: 16px; left: 0; right: 0;
            text-align: center;
            font-size: 10.5px;
            color: rgba(255,255,255,0.28);
            z-index: 2;
            letter-spacing: 0.8px;
        }

        /* Ocultar todo lo que sobra */
        .login-header h1,
        .login-header p:not(.panel-tagline),
        .panel-features, .panel-subtitle,
        .header-divider, .step-indicator { display: none !important; }
        
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
            box-shadow: 0 0 0 4px rgba(15,76,129,0.12);
            transform: translateY(-2px);
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
            background: linear-gradient(160deg, #0F4C81 0%, #1565A0 50%, #0288D1 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(15,76,129,0.4);
            position: relative;
            overflow: hidden;
            animation: pulseShadow 3s ease-in-out infinite;
        }
        
        @keyframes pulseShadow {
            0%, 100% { box-shadow: 0 6px 20px rgba(15,76,129,0.35); }
            50% { box-shadow: 0 8px 28px rgba(2,136,209,0.55); }
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
            transition: left 0.4s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(13,46,127,0.55);
            animation: none;
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
                height: auto;
                min-height: calc(100dvh - 60px);
                padding: 20px 16px;
                align-items: flex-start;
            }

            /* Card: 1 columna en tablet */
            .login-container {
                grid-template-columns: 1fr !important;
                max-width: 480px !important;
            }

            /* Panel izquierdo arriba, más compacto */
            .login-header {
                min-height: 200px !important;
                padding: 30px 24px !important;
            }

            /* Ocultar decoraciones pesadas */
            .deco-lines, .deco-ring, .particle, .particle-extra {
                display: none !important;
            }
        }
        
        @media (max-width: 480px) {
            .login-body {
                padding: 24px 20px;
            }
            
            .login-footer {
                padding: 16px 20px;
            }

            /* Card ocupa todo el ancho */
            .login-container {
                max-width: 100% !important;
                border-radius: 16px !important;
            }
            
            .login-header {
                min-height: 160px !important;
                padding: 24px 20px !important;
            }

            .login-header .logo {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }
            
            .login-header h1 {
                font-size: 22px;
            }

            .login-wrapper {
                padding: 12px !important;
            }

            .banner-container {
                height: 52px;
            }
        }
    </style>
</head>
<body>
    <div class="banner-container">
        <img src="<?= APP_URL ?>/publico/imagenes/cintillo.png" alt="ISPEB - Dirección de Telemática">
    </div>
    
    <!-- Contenedor centrado para el login -->
    <div class="login-wrapper">
        <div class="login-container">
        <div class="login-header">
            <!-- Líneas diagonales decorativas -->
            <div class="deco-lines"></div>
            <!-- Anillo central pulsante -->
            <div class="deco-ring"></div>
            <!-- Partículas / Anillos giratorios -->
            <div class="particle"></div>
            <div class="particle"></div>
            <!-- Twinkle stars -->
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <!-- Extra twinkle -->
            <div class="particle-extra"></div>

            <!-- Logo institucional -->
            <div class="logo">
                <img src="<?= APP_URL ?>/publico/imagenes/logotipo(B).png" alt="ISPEB Telemática">
            </div>

            <!-- Línea decorativa -->
            <div class="logo-divider"></div>

            <!-- Tagline definitivo -->
            <p class="panel-tagline">Sistema de Gestión de<br>Expedientes Digitales</p>

            <!-- Versión -->
            <div class="panel-version">SIGED v3.1 &middot; ISPEB © 2026</div>
        </div>
        
        <div class="login-body">
            <h2>Iniciar Sesión</h2>
            <p class="subtitle">Ingrese sus credenciales para acceder al sistema</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-info">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?php echo htmlspecialchars($mensaje); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= APP_URL ?>/">
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
                <a href="<?= APP_URL ?>/vistas/auth/recuperar.php">Recuperar acceso</a>
                <span class="divider">•</span>
                ¿No tiene cuenta? <a href="<?= APP_URL ?>/registro.php">Registrarse</a>
            </p>
            <p style="margin-top: 12px; font-size: 12px;">
                © <?php echo date('Y'); ?> ISPEB - Todos los derechos reservados
            </p>
        </div>
        </div>
    </div>
    
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    
    <script>
        // Detectar si viene de un registro exitoso
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const registroExitoso = urlParams.get('registro_exitoso');
            const username = urlParams.get('username');
            
            if (registroExitoso === '1' && username) {
                // Limpiar URL
                window.history.replaceState({}, document.title, window.location.pathname);
                
                // Mostrar SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro Completado!',
                    html: `
                        <div style="text-align: center; padding: 10px;">
                            <p style="font-size: 16px; color: #4b5563; margin-bottom: 20px;">
                                Tu cuenta ha sido creada exitosamente
                            </p>
                            <div style="background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%); 
                                        padding: 16px; 
                                        border-radius: 12px; 
                                        border: 2px solid #86efac;
                                        margin-bottom: 16px;">
                                <p style="font-size: 14px; color: #166534; margin-bottom: 8px; font-weight: 600;">
                                    Tu usuario es:
                                </p>
                                <p style="font-size: 24px; 
                                          font-weight: 700; 
                                          color: #15803d; 
                                          font-family: 'Courier New', monospace;
                                          letter-spacing: 1px;">
                                    ${username}
                                </p>
                            </div>
                            <p style="font-size: 14px; color: #6b7280;">
                                Ahora puedes iniciar sesión con tu usuario y contraseña
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#0F4C81',
                    buttonsStyling: true,
                    allowOutsideClick: true,
                    allowEscapeKey: true
                }).then(function(result) {
                    // Auto-llenar el campo de usuario
                    const usernameInput = document.getElementById('username');
                    if (usernameInput) {
                        usernameInput.value = username;
                    }
                    // Enfocar el campo de contraseña
                    const passwordInput = document.getElementById('password');
                    if (passwordInput) {
                        setTimeout(function() {
                            passwordInput.focus();
                        }, 200);
                    }
                });
            }
        });
    </script>
</body>
</html>
