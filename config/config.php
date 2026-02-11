<?php
/**
 * Configuración Base de la Aplicación
 * Sistema ISPEB - Gestión de Expedientes Digitales
 */

// ===================================================
// CONFIGURACIÓN DE URL DINÁMICA
// ===================================================

/**
 * Detecta automáticamente la URL base de la aplicación
 * Funciona en desarrollo (localhost) y producción
 */
function detectarAppUrl() {
    // Detectar protocolo (HTTP o HTTPS)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
    
    // Detectar host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Detectar ruta base usando SCRIPT_FILENAME y DOCUMENT_ROOT
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    
    // Obtener la ruta relativa desde document root
    $basePath = str_replace($documentRoot, '', $scriptPath);
    
    // Para archivos en subdirectorios, subir al directorio raíz de la app
    // Si estamos en /APP3/config o /APP3/vistas/algo, necesitamos /APP3
    if (strpos($basePath, '/config') !== false) {
        $basePath = dirname($basePath);
    } elseif (strpos($basePath, '/vistas') !== false) {
        $basePath = dirname(dirname($basePath));
    } elseif (strpos($basePath, '/lib') !== false) {
        $basePath = dirname($basePath);
    }
    
    // Limpiar la ruta base
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    
    $basePath = str_replace('\\', '/', $basePath);
    
    return $protocol . '://' . $host . $basePath;
}

// Definir APP_URL dinámicamente
if (!defined('APP_URL')) {
    define('APP_URL', detectarAppUrl());
}

// ===================================================
// CONFIGURACIÓN GENERAL
// ===================================================

// Nombre de la aplicación
if (!defined('APP_NAME')) {
    define('APP_NAME', 'SIGEX');
}

// Versión de la aplicación
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '3.5.0');
}

// ===================================================
// ZONA HORARIA
// ===================================================

// Configurar zona horaria de Venezuela
date_default_timezone_set('America/Caracas');

// ===================================================
// RUTAS DEL SISTEMA
// ===================================================

// Directorio raíz
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Directorio de subidas
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . '/subidas/');
}

// Directorio de configuración
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . '/config/');
}

// ===================================================
// CONFIGURACIÓN DE ARCHIVOS
// ===================================================

// Tamaño máximo de archivo (5MB)
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5242880);
}

// Extensiones permitidas
if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
}

// ===================================================
// MODO DE DEPURACIÓN
// ===================================================

// Activar en desarrollo, desactivar en producción
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

// Configurar reporte de errores según modo debug
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ===================================================
// CONFIGURACIÓN DE SESIÓN
// ===================================================

// Nombre de la sesión
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'ISPEB_SESSION');
}

// Tiempo de expiración de sesión (30 minutos)
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 1800);
}
