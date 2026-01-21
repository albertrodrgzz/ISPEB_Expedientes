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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 350px 1fr;
            animation: fadeInScale 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: max-width 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                        grid-template-columns 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Card expandida para el paso 2 (formulario completo) */
        .login-container.wide {
            max-width: 1400px;
            grid-template-columns: 400px 1fr;
            max-height: 90vh;
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
            padding: 50px 35px;
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
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Scroll solo cuando está en modo wide (paso 2) */
        .login-container.wide .login-body {
            justify-content: flex-start;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Estilo del scrollbar */
        .login-container.wide .login-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .login-container.wide .login-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        .login-container.wide .login-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        .login-container.wide .login-body::-webkit-scrollbar-thumb:hover {
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
        
        @media (max-width: 1100px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 520px;
            }
            
            .login-header {
                padding: 40px 30px;
            }
            
            .login-body {
                padding: 40px 35px;
            }
            
            .login-footer {
                padding: 20px 35px;
            }
        }
        
        /* Media query adicional para card expandida */
        @media (max-width: 1450px) {
            .login-container.wide {
                grid-template-columns: 1fr;
                max-width: 600px;
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
            
            .form-row {
                grid-template-columns: 1fr;
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
    
    <!-- Contenedor centrado para el registro -->
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
                            <label for="cedula">Cédula de Identidad</label>
                            <input 
                                type="text" 
                                id="cedula" 
                                name="cedula" 
                                placeholder="V-12345678"
                                value="<?php echo htmlspecialchars($cedula_prefill); ?>"
                                required
                                autofocus
                            >
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
                    mostrarAlerta('step2', data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
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
            const icono = tipo === 'error' ? '⚠️' : tipo === 'success' ? '✓' : 'ℹ️';
            
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
