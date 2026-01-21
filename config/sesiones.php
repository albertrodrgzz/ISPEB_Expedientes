<?php
/**
 * Configuración de Sesiones
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 */

// Configuración de sesiones seguras
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de seguridad de sesiones
    ini_set('session.cookie_httponly', 1);  // Prevenir acceso JavaScript a cookies
    ini_set('session.use_only_cookies', 1); // Solo usar cookies, no URL
    ini_set('session.cookie_secure', 0);    // Cambiar a 1 si usa HTTPS
    ini_set('session.cookie_samesite', 'Lax'); // Protección CSRF
    
    // Tiempo de vida de la sesión (30 minutos de inactividad)
    ini_set('session.gc_maxlifetime', 1800);
    ini_set('session.cookie_lifetime', 0); // Cookie expira al cerrar navegador
    
    // Nombre de sesión personalizado
    session_name('ISPEB_SESSION');
    
    // Iniciar sesión
    session_start();
    
    // Regenerar ID de sesión periódicamente (cada 30 minutos)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Verificar timeout de sesión
 * Cierra la sesión si ha estado inactiva por más de 30 minutos
 */
function verificarTimeoutSesion() {
    $timeout = 1800; // 30 minutos en segundos
    
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactivo = time() - $_SESSION['ultimo_acceso'];
        
        if ($inactivo > $timeout) {
            // Sesión expirada por inactividad
            session_unset();
            session_destroy();
            header('Location: ' . APP_URL . '/index.php?error=sesion_expirada');
            exit;
        }
    }
    
    // Actualizar último acceso
    $_SESSION['ultimo_acceso'] = time();
}

/**
 * Inicializar sesión de usuario
 * @param array $usuario_data Datos del usuario
 */
function inicializarSesion($usuario_data) {
    // Regenerar ID de sesión al iniciar sesión (prevenir session fixation)
    session_regenerate_id(true);
    
    // Establecer variables de sesión
    $_SESSION['usuario_id'] = $usuario_data['usuario_id'];
    $_SESSION['funcionario_id'] = $usuario_data['funcionario_id'];
    $_SESSION['username'] = $usuario_data['username'];
    $_SESSION['nombre_completo'] = $usuario_data['nombres'] . ' ' . $usuario_data['apellidos'];
    $_SESSION['nombres'] = $usuario_data['nombres'];
    $_SESSION['apellidos'] = $usuario_data['apellidos'];
    $_SESSION['cedula'] = $usuario_data['cedula'];
    $_SESSION['foto'] = $usuario_data['foto'] ?? null; // Opcional
    $_SESSION['cargo_id'] = $usuario_data['cargo_id'];
    $_SESSION['cargo'] = $usuario_data['nombre_cargo'];
    $_SESSION['nivel_acceso'] = $usuario_data['nivel_acceso'];
    $_SESSION['departamento_id'] = $usuario_data['departamento_id'] ?? null; // Opcional
    $_SESSION['departamento'] = $usuario_data['departamento'];
    $_SESSION['ultimo_acceso'] = time();
    $_SESSION['last_regeneration'] = time();
    $_SESSION['ip_address'] = obtenerIP();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    
    // Log de sesión inicializada
    error_log("Sesión inicializada: Usuario {$usuario_data['username']} (ID: {$usuario_data['usuario_id']}) - Nivel: {$usuario_data['nivel_acceso']}");
}

/**
 * Verificar integridad de sesión
 * Prevenir session hijacking verificando IP y User Agent
 */
function verificarIntegridadSesion() {
    if (isset($_SESSION['usuario_id'])) {
        // Verificar IP (opcional, puede causar problemas con IPs dinámicas)
        // if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== obtenerIP()) {
        //     cerrarSesion();
        // }
        
        // Verificar User Agent
        $user_agent_actual = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $user_agent_actual) {
            // Posible session hijacking
            session_unset();
            session_destroy();
            header('Location: ' . APP_URL . '/index.php?error=sesion_invalida');
            exit;
        }
    }
}
