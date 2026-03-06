<?php
/**
 * Configuración Base de la Aplicación
 * SIGED — Sistema de Gestión de Expedientes Digitales
 * Instituto de Salud Pública del Estado Bolívar (ISPEB)
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
    define('APP_NAME', 'SIGED');
}

// Versión de la aplicación
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '4.7.5');
}

// Número de build para cache busting (auto-generado al modificar config)
if (!defined('APP_BUILD')) {
    define('APP_BUILD', '20260305');
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

// PRODUCCIÓN: false → errores ocultos al usuario, guardados en log
// DESARROLLO: true  → errores visibles en pantalla
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

// Configurar reporte de errores según modo debug
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // PRODUCCIÓN — nunca revelar rutas de servidor
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    // Los errores se guardan en el log de Apache/XAMPP
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

// ===================================================
// HELPERS DE SEGURIDAD — Anti-XSS
// ===================================================

/**
 * e() — Escape seguro para HTML (OWASP XSS Prevention Rule #1)
 *
 * Úsalo en TODAS las vistas al hacer echo de datos de BD o usuarios:
 *   <?= e($variable) ?>
 *   <?php echo e($nombre) ?>
 *
 * Protege contra: <script>, onclick=, javascript:, y cualquier
 * etiqueta/atributo HTML inyectado por el usuario.
 *
 * @param  mixed  $value  Valor a escapar (string, int, null)
 * @return string         Valor seguro para insertar en HTML
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * eAttr() — Escape para atributos HTML (value=, placeholder=, title=, etc.)
 * Uso: <input value="<?= eAttr($variable) ?>">
 */
if (!function_exists('eAttr')) {
    function eAttr($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * eJs() — Escape seguro para contexto JavaScript inline
 * Uso: var nombre = "<?= eJs($variable) ?>";
 * NUNCA uses e() dentro de <script>, usa eJs() en su lugar.
 */
if (!function_exists('eJs')) {
    function eJs($value): string {
        return addslashes(strip_tags((string)($value ?? '')));
    }
}

/**
 * sanitizeInt() — Forzar entero válido (evita inyección tipo "1 OR 1=1")
 * Uso en IDs GET/POST: $id = sanitizeInt($_GET['id'] ?? 0);
 */
if (!function_exists('sanitizeInt')) {
    function sanitizeInt($value, int $default = 0): int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? (int)$filtered : $default;
    }
}

/**
 * sanitizeString() — Limpia espacios y caracteres de control
 * Uso general para sanitizar inputs de texto antes de procesarlos.
 * NO sustituye las sentencias preparadas — úsalo en conjunto.
 */
if (!function_exists('sanitizeString')) {
    function sanitizeString($value, int $maxLen = 500): string {
        return mb_substr(trim(strip_tags((string)($value ?? ''))), 0, $maxLen, 'UTF-8');
    }
}
