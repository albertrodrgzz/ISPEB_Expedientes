<?php
/**
 * Configuración Base de la Aplicación
 * SIGED — Sistema de Gestión de Expedientes Digitales
 * Instituto de Salud Pública del Estado Bolívar (ISPEB)
 *
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  CORRECCIÓN CRÍTICA v4.8 — Compatibilidad con Render + HTTPS ║
 * ║  Problema resuelto: Mixed Content / SSL Termination Proxy    ║
 * ╚══════════════════════════════════════════════════════════════╝
 */

// ===================================================
// CONFIGURACIÓN DE URL DINÁMICA — CORREGIDA
// ===================================================

/**
 * detectarAppUrl()
 *
 * PROBLEMA ORIGINAL:
 *   La función anterior usaba $_SERVER['HTTPS'] y $_SERVER['SERVER_PORT']
 *   para detectar el protocolo. En Render (y cualquier plataforma con
 *   reverse proxy / load balancer con SSL termination), la conexión entre
 *   el load balancer y el contenedor PHP es HTTP puro. Por lo tanto:
 *     - $_SERVER['HTTPS']       → vacío o "off"
 *     - $_SERVER['SERVER_PORT'] → 80 (no 443)
 *   Resultado: APP_URL se generaba con "http://" cuando el cliente
 *   realmente usaba "https://", causando errores de Mixed Content en el
 *   navegador que bloqueaban TODOS los assets CSS/JS.
 *
 * SOLUCIÓN:
 *   Render (y la mayoría de reverse proxies: Nginx, Cloudflare, AWS ALB)
 *   inyectan la cabecera HTTP_X_FORWARDED_PROTO con el protocolo ORIGINAL
 *   del cliente. Esta función la lee PRIMERO antes de revisar $_SERVER.
 *
 * ORDEN DE PRIORIDAD (de mayor a menor confianza):
 *   1. HTTP_X_FORWARDED_PROTO  → Cabecera del reverse proxy (Render, Nginx)
 *   2. HTTP_X_FORWARDED_SSL    → Alternativa usada por algunos proxies
 *   3. $_SERVER['HTTPS']       → Apache/PHP nativo (local, servidor directo)
 *   4. $_SERVER['SERVER_PORT'] → Último recurso (puerto 443 = HTTPS)
 *
 * GARANTÍA EXTRA:
 *   La URL retornada NUNCA termina en "/" para que al concatenar rutas
 *   como APP_URL . '/publico/css/...' no resulten dobles barras.
 */
function detectarAppUrl(): string
{
    // ── PASO 1: Detectar protocolo con soporte para reverse proxy ──────────
    $protocol = 'http'; // valor por defecto seguro

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        // Render, Nginx, AWS ALB, Cloudflare → cabecera estándar del proxy
        // Puede venir como "https" o "https, http" (cuando hay cadenas de proxies)
        $forwarded = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($forwarded === 'https') {
            $protocol = 'https';
        }
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        // Algunos balanceadores usan esta cabecera alternativa
        $protocol = 'https';
    } elseif (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        // Apache/PHP con SSL directo (desarrollo local con XAMPP + SSL, por ejemplo)
        $protocol = 'https';
    } elseif (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        // Último recurso: inferir por puerto
        $protocol = 'https';
    }

    // ── PASO 2: Obtener el host del request ────────────────────────────────
    // HTTP_X_FORWARDED_HOST → cuando el proxy reescribe el host
    // HTTP_HOST            → estándar (incluye puerto si no es 80/443)
    $host = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]));
    }
    if (empty($host)) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    // ── PASO 3: Construir la URL base ──────────────────────────────────────
    // En Render el contenedor se sirve desde la raíz ("/"), no desde un
    // subdirectorio. Dockerfile copia los archivos a /var/www/html/ y
    // Apache sirve desde ahí → sin subfolder en la URL.
    //
    // Si en el futuro deploys en cPanel con subfolder (ej: /APP3/),
    // cambia $basePath a la ruta correspondiente o detéctala dinámicamente.
    $basePath = '';

    // ── PASO 4: Ensamblar y limpiar la URL ─────────────────────────────────
    $url = $protocol . '://' . $host . $basePath;

    // GARANTÍA: eliminar barra final para evitar dobles "//"
    // Correcto:   APP_URL . '/publico/css/estilos.css'
    // Incorrecto: APP_URL . '/publico/css/estilos.css' con APP_URL = "https://host/"
    return rtrim($url, '/');
}

// Definir APP_URL dinámicamente (solo una vez)
if (!defined('APP_URL')) {
    define('APP_URL', detectarAppUrl());
}

// ===================================================
// CONFIGURACIÓN GENERAL
// ===================================================

if (!defined('APP_NAME')) {
    define('APP_NAME', 'SIGED');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '4.8.0');
}

// Build para cache busting — actualizar en cada deploy
if (!defined('APP_BUILD')) {
    define('APP_BUILD', '20260309');
}

// ===================================================
// ZONA HORARIA
// ===================================================

date_default_timezone_set('America/Caracas');

// ===================================================
// RUTAS DEL SISTEMA (filesystem, no URLs)
// ===================================================

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . '/subidas/');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . '/config/');
}

// ===================================================
// CONFIGURACIÓN DE ARCHIVOS
// ===================================================

if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5242880); // 5 MB
}

if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
}

// ===================================================
// MODO DE DEPURACIÓN
// ===================================================

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
}

// ===================================================
// CONFIGURACIÓN DE SESIÓN
// ===================================================

if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'ISPEB_SESSION');
}

if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 1800); // 30 minutos
}

// ===================================================
// HELPERS DE SEGURIDAD — Anti-XSS
// ===================================================

/**
 * e() — Escape seguro para HTML (OWASP XSS Prevention Rule #1)
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * eAttr() — Escape para atributos HTML
 */
if (!function_exists('eAttr')) {
    function eAttr($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * eJs() — Escape seguro para contexto JavaScript inline
 */
if (!function_exists('eJs')) {
    function eJs($value): string {
        return addslashes(strip_tags((string)($value ?? '')));
    }
}

/**
 * sanitizeInt() — Forzar entero válido
 */
if (!function_exists('sanitizeInt')) {
    function sanitizeInt($value, int $default = 0): int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? (int)$filtered : $default;
    }
}

/**
 * sanitizeString() — Limpia espacios y caracteres de control
 */
if (!function_exists('sanitizeString')) {
    function sanitizeString($value, int $maxLen = 500): string {
        return mb_substr(trim(strip_tags((string)($value ?? ''))), 0, $maxLen, 'UTF-8');
    }
}
