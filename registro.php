<?php
/**
 * Página de Registro de Usuarios - Sistema de Dos Etapas
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 * 
 * Los empleados usan su cédula para completar su registro
 * estableciendo contraseña y preguntas de seguridad
 */

// Cargar configuración (incluye sesiones)
require_once 'config/database.php';
require_once 'config/seguridad.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id']) && isset($_SESSION['funcionario_id'])) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$error = '';
$mensaje = '';
$cedula_prefill = '';

// Si viene redirigido desde login por registro pendiente
if (isset($_SESSION['registro_pendiente_cedula'])) {
    $cedula_prefill = $_SESSION['registro_pendiente_cedula'];
    $mensaje = 'Debe completar su registro antes de iniciar sesión.';
    unset($_SESSION['registro_pendiente_cedula']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Registro - Sistema de Expedientes ISPEB</title>
    <link rel="icon" type="image/png" href="publico/imagenes/isotipo.png">
    <link rel="shortcut icon" href="publico/imagenes/isotipo.png">
    <style>
        :root {
            --color-primary: #00a8cc;
            --color-primary-dark: #006d85;
            --color-secondary: #005f73;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-error: #ef4444;
            --color-info: #3b82f6;
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-border: #e5e7eb;
            --color-bg: #f9fafb;
            --color-white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
            background-image: url('publico/imagenes/edificio-ispeb.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.35;
            z-index: 0;
        }
        
        /* Overlay igual al login principal */
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
            height: 80px;
            background: #ffffff;
            box-shadow: 0 2px 16px rgba(0,0,0,0.12);
            position: sticky;
            top: 0;
            z-index: 10;
            overflow: hidden;
            animation: bannerSlideDown 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes bannerSlideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }
        .banner-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        
        /* Wrapper horizontal */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px);
            padding: 30px 15px;
            position: relative;
            z-index: 1;
        }
        
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
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 350px 1fr;
            border: 1px solid rgba(255,255,255,0.15);
            animation: loginCardIn 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
            transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1),
                        box-shadow 0.4s cubic-bezier(0.22, 1, 0.36, 1),
                        max-width 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                        grid-template-columns 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 20px auto;
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

        /* Card expandida para el paso 2 */
        .login-container.wide {
            max-width: 1400px;
            grid-template-columns: 400px 1fr;
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
            min-height: 400px;
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


        
        /* Panel derecho - Formulario */
        .login-body {
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
            /* Removed max-height to allow content to expand naturally */
        }
        
        /* Estilo del scrollbar */
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
            margin-bottom: 6px;
        }
        
        .login-body .subtitle {
            font-size: 14px;
            color: var(--color-text-light);
            margin-bottom: 28px;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
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
        
        .form-group input:hover,
        .form-group select:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 168, 204, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group input::placeholder {
            color: #9ca3af;
        }
        
        .form-group input:disabled {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
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
            box-shadow: 0 4px 14px rgba(15,76,129,0.35);
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15,76,129,0.45);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-primary:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .login-footer {
            padding: 20px 40px;
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
        
        .hidden {
            display: none !important;
        }
        
        .employee-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .employee-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 8px;
        }
        
        .employee-info p {
            font-size: 14px;
            color: #075985;
            margin: 4px 0;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        
        /* Media Queries para Responsive */
        @media (max-width: 1450px) {
            .login-container.wide {
                grid-template-columns: 1fr;
                max-width: 700px;
            }
            
            .login-header {
                min-height: 300px;
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 1100px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 600px;
            }
            
            .login-header {
                min-height: 250px;
                padding: 35px 30px;
            }
            
            .login-body {
                padding: 40px 35px;
                /* max-height removed globally - no need to override */
            }
            
            .login-footer {
                padding: 20px 35px;
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
                padding: 20px 10px;
            }
            
            .login-container {
                max-width: 100%;
                border-radius: 16px;
                margin: 10px;
            }
            
            .login-header {
                min-height: 200px;
                padding: 30px 25px;
            }
            
            .login-header .logo {
                width: 70px;
                height: 70px;
                font-size: 32px;
                margin-bottom: 20px;
            }
            
            .login-header h1 {
                font-size: 26px;
            }
            
            .login-header p {
                font-size: 14px;
            }
            
            .login-body {
                padding: 30px 25px 80px 25px; /* Extra padding-bottom for mobile keyboard */
            }
            
            .login-body h2 {
                font-size: 22px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            .login-footer {
                padding: 20px 25px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 15px 5px;
            }
            
            .login-container {
                border-radius: 12px;
                margin: 5px;
            }
            
            .login-header {
                min-height: 180px;
                padding: 25px 20px;
            }
            
            .login-header .logo {
                width: 60px;
                height: 60px;
                font-size: 28px;
                margin-bottom: 15px;
            }
            
            .login-header h1 {
                font-size: 22px;
            }
            
            .login-body {
                padding: 25px 20px 100px 20px; /* Increased padding-bottom for small mobile keyboards */
            }
            
            .login-body h2 {
                font-size: 20px;
            }
            
            .login-body .subtitle {
                font-size: 13px;
            }
            
            .form-group input,
            .form-group select {
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 15px;
            }
            
            .employee-info {
                padding: 14px;
            }
            
            .employee-info h3 {
                font-size: 15px;
            }
            
            .employee-info p {
                font-size: 13px;
            }
            
            .login-footer {
                padding: 18px 20px;
            }
        }
        
        /* Mejoras para pantallas muy pequeñas */
        @media (max-width: 360px) {
            .login-body {
                padding: 20px 15px 120px 15px; /* Maximum padding-bottom for very small screens */
            }
            
            .form-group input,
            .form-group select {
                padding: 10px 14px;
                font-size: 13px;
            }
            
            .btn {
                padding: 12px 18px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Banner de ancho completo -->
    <div class="banner-container">
        <img src="publico/imagenes/cintillo.png" alt="Gobierno Bolivariano - ISPEB - Dirección de Telemática">
    </div>
    
    <!-- Contenedor centrado para el registro -->
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
                    <img src="publico/imagenes/logotipo(B).png" alt="ISPEB Telemática">
                </div>
                <div class="logo-divider"></div>
                <p class="panel-tagline">Sistema de Gestión de<br>Expedientes Digitales</p>
                <div class="panel-version">SIGED v3.1 &middot; ISPEB © 2026</div>
            </div>
            
            <div class="login-body">
                <!-- PASO 1: Validar Cédula -->
                <div id="step1">
                    <h2>Completar Registro</h2>
                    <p class="subtitle">Ingrese su cédula para continuar con el registro</p>
                    
                    <div id="alert-step1"></div>
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-info">
                            <span>ℹ️</span>
                            <span><?php echo htmlspecialchars($mensaje); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form id="form-cedula">
                        <div class="form-group">
                            <label for="cedula">C&eacute;dula de Identidad</label>
                            <input 
                                type="text" 
                                id="cedula" 
                                name="cedula" 
                                placeholder="Ej: 12345678 (sin prefijo V-)"
                                value="<?php echo htmlspecialchars($cedula_prefill); ?>"
                                required
                                autofocus
                            >
                            <small style="display:block;margin-top:6px;color:#64748b;font-size:12px;">Ingrese solo los n&uacute;meros, sin la letra V ni guiones</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="btn-validar">
                            Continuar
                        </button>
                    </form>
                </div>
                
                <!-- PASO 2: Completar Registro -->
                <div id="step2" class="hidden">
                    <h2>Completar Registro</h2>
                    <p class="subtitle">Configure su contraseña y preguntas de seguridad</p>
                    
                    <div id="alert-step2"></div>
                    
                    <div class="employee-info" id="employee-info">
                        <h3 id="employee-name"></h3>
                        <p><strong>Cédula:</strong> <span id="employee-cedula"></span></p>
                        <p><strong>Usuario:</strong> <span id="employee-username"></span></p>
                    </div>
                    
                    <form id="form-registro">
                        <input type="hidden" id="cedula-hidden" name="cedula">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Mínimo 6 caracteres"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="password_confirm">Confirmar Contraseña</label>
                                <input 
                                    type="password" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Repita la contraseña"
                                    required
                                >
                            </div>
                        </div>
                        
                        <h3 style="font-size: 16px; font-weight: 600; margin: 24px 0 16px; color: var(--color-text);">
                            Preguntas de Seguridad
                        </h3>
                        <p style="font-size: 13px; color: var(--color-text-light); margin-bottom: 20px;">
                            Seleccione 3 preguntas diferentes y proporcione sus respuestas. Estas se usarán para recuperar su cuenta.
                        </p>
                        
                        <!-- Pregunta 1 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pregunta_1">Pregunta 1</label>
                                <select id="pregunta_1" name="pregunta_1" required>
                                    <option value="">Seleccione una pregunta...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="respuesta_1">Respuesta 1</label>
                                <input 
                                    type="text" 
                                    id="respuesta_1" 
                                    name="respuesta_1" 
                                    placeholder="Su respuesta"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Pregunta 2 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pregunta_2">Pregunta 2</label>
                                <select id="pregunta_2" name="pregunta_2" required>
                                    <option value="">Seleccione una pregunta...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="respuesta_2">Respuesta 2</label>
                                <input 
                                    type="text" 
                                    id="respuesta_2" 
                                    name="respuesta_2" 
                                    placeholder="Su respuesta"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Pregunta 3 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pregunta_3">Pregunta 3</label>
                                <select id="pregunta_3" name="pregunta_3" required>
                                    <option value="">Seleccione una pregunta...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="respuesta_3">Respuesta 3</label>
                                <input 
                                    type="text" 
                                    id="respuesta_3" 
                                    name="respuesta_3" 
                                    placeholder="Su respuesta"
                                    required
                                >
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="btn-completar">
                            Completar Registro
                        </button>
                        
                        <button type="button" class="btn" style="background: #e2e8f0; color: #2d3748; margin-top: 12px;" onclick="volverPaso1()">
                            ← Volver
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="login-footer">
                <p>
                    ¿Ya completó su registro? 
                    <a href="index.php">Iniciar Sesión</a>
                </p>
                <p style="margin-top: 12px; font-size: 12px;">
                    © <?php echo date('Y'); ?> ISPEB - Todos los derechos reservados
                </p>
            </div>
        </div>
    </div>
    
    <script>
        let preguntasDisponibles = [];
        
        // Cargar preguntas de seguridad
        async function cargarPreguntas() {
            try {
                const response = await fetch('vistas/funcionarios/ajax/obtener_preguntas_seguridad.php');
                const data = await response.json();
                
                if (data.success) {
                    preguntasDisponibles = data.data;
                    llenarSelectsPreguntas();
                } else {
                    mostrarAlerta('step2', 'Error al cargar preguntas de seguridad', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('step2', 'Error de conexión', 'error');
            }
        }
        
        function llenarSelectsPreguntas() {
            const selects = ['pregunta_1', 'pregunta_2', 'pregunta_3'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">Seleccione una pregunta...</option>';
                preguntasDisponibles.forEach(pregunta => {
                    const option = document.createElement('option');
                    option.value = pregunta.pregunta;
                    option.textContent = pregunta.pregunta;
                    select.appendChild(option);
                });
            });
        }
        
        // Paso 1: Validar cédula
        document.getElementById('form-cedula').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const cedula = document.getElementById('cedula').value.trim();
            const btnValidar = document.getElementById('btn-validar');
            
            btnValidar.disabled = true;
            btnValidar.textContent = 'Validando...';
            
            try {
                const formData = new FormData();
                formData.append('cedula', cedula);
                
                const response = await fetch('vistas/funcionarios/ajax/validar_cedula_registro.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Mostrar paso 2
                    document.getElementById('employee-name').textContent = `${data.data.nombres} ${data.data.apellidos}`;
                    document.getElementById('employee-cedula').textContent = data.data.cedula;
                    document.getElementById('employee-username').textContent = data.data.username;
                    document.getElementById('cedula-hidden').value = data.data.cedula;
                    
                    document.getElementById('step1').classList.add('hidden');
                    document.getElementById('step2').classList.remove('hidden');
                    
                    // Expandir la card para el formulario completo
                    document.querySelector('.login-container').classList.add('wide');
                    
                    // Cargar preguntas
                    await cargarPreguntas();
                } else {
                    mostrarAlerta('step1', data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('step1', 'Error de conexión. Intente nuevamente.', 'error');
            } finally {
                btnValidar.disabled = false;
                btnValidar.textContent = 'Continuar';
            }
        });
        
        // Paso 2: Completar registro
        document.getElementById('form-registro').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const btnCompletar = document.getElementById('btn-completar');
            
            // Validar que las preguntas sean diferentes
            const p1 = formData.get('pregunta_1');
            const p2 = formData.get('pregunta_2');
            const p3 = formData.get('pregunta_3');
            
            if (p1 === p2 || p1 === p3 || p2 === p3) {
                mostrarAlerta('step2', 'Debe seleccionar 3 preguntas diferentes', 'error');
                return;
            }
            
            btnCompletar.disabled = true;
            btnCompletar.textContent = 'Procesando...';
            
            try {
                const response = await fetch('vistas/funcionarios/ajax/completar_registro.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirigir al login con parámetros para mostrar SweetAlert
                    window.location.href = `index.php?registro_exitoso=1&username=${encodeURIComponent(data.username)}`;
                } else {
                    mostrarAlerta('step2', data.error, 'error');
                    btnCompletar.disabled = false;
                    btnCompletar.textContent = 'Completar Registro';
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('step2', 'Error de conexión. Intente nuevamente.', 'error');
                btnCompletar.disabled = false;
                btnCompletar.textContent = 'Completar Registro';
            }
        });
        
        function volverPaso1() {
            document.getElementById('step2').classList.add('hidden');
            document.getElementById('step1').classList.remove('hidden');
            document.getElementById('form-cedula').reset();
            document.getElementById('form-registro').reset();
            limpiarAlertas();
            
            // Contraer la card al tamaño original
            document.querySelector('.login-container').classList.remove('wide');
        }
        
        function mostrarAlerta(paso, mensaje, tipo) {
            const container = document.getElementById(`alert-${paso}`);
            const tipoClase = tipo === 'error' ? 'alert-error' : tipo === 'success' ? 'alert-success' : 'alert-info';
            const icono = tipo === 'error'
                ? '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
                : tipo === 'success'
                ? '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                : '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            
            container.innerHTML = `
                <div class="alert ${tipoClase}">
                    <span>${icono}</span>
                    <span>${mensaje}</span>
                </div>
            `;
        }
        
        function limpiarAlertas() {
            document.getElementById('alert-step1').innerHTML = '';
            document.getElementById('alert-step2').innerHTML = '';
        }
    </script>
</body>
</html>
