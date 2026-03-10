<?php
/**
 * config/config.php — SIGED v5.0 UNIVERSAL
 * ============================================================
 * Detecta automáticamente el entorno y calcula APP_URL correcta:
 *
 *   XAMPP local:   http://localhost/ISPEB_Expedientes   (con subcarpeta)
 *   Render/Docker: https://siged.onrender.com           (desde raíz)
 *
 * Prioridad:
 *   1. Variable de entorno APP_URL → siempre gana (ideal para Render)
 *   2. Detección automática        → XAMPP detecta subcarpeta vía DOCUMENT_ROOT
 * ============================================================
 */

// ══════════════════════════════════════════════════════════════════
// FUNCIÓN CENTRAL: detectarAppUrl()
// ══════════════════════════════════════════════════════════════════
function detectarAppUrl(): string
{
    // ─── CAPA 1: Variable de entorno APP_URL (SIEMPRE GANA) ──────────
    // Render → Dashboard → Environment → APP_URL = https://siged.onrender.com
    // XAMPP  → NO definir esta variable (dejar vacía para detección auto)
    $envUrl = getenv('APP_URL');
    if (!empty($envUrl) && filter_var($envUrl, FILTER_VALIDATE_URL)) {
        return rtrim($envUrl, '/');
    }

    // ─── CAPA 2: Detección de protocolo ──────────────────────────────
    $protocol = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $fwd = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($fwd === 'https') $protocol = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        $protocol = 'https';
    } elseif (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $protocol = 'https';
    } elseif (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        $protocol = 'https';
    }

    // ─── Host real (respeta X-Forwarded-Host de Render) ──────────────
    $host = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]));
    }
    if (empty($host)) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    // ─── CAPA 3: Detección de subcarpeta — CRÍTICA PARA XAMPP ────────
    //
    // XAMPP:  DOCUMENT_ROOT = C:/xampp/htdocs
    //         dirname(__DIR__) = C:/xampp/htdocs/ISPEB_Expedientes
    //         Subcarpeta = /ISPEB_Expedientes
    //         APP_URL = http://localhost/ISPEB_Expedientes  ✅
    //
    // Docker: DOCUMENT_ROOT = /var/www/html
    //         dirname(__DIR__) = /var/www/html
    //         Subcarpeta = "" (vacía)
    //         APP_URL = https://siged.onrender.com          ✅
    //
    $subfolder = '';
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $projectDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

        if ($docRoot !== '' && str_starts_with($projectDir, $docRoot)) {
            $sub = substr($projectDir, strlen($docRoot));
            $sub = '/' . ltrim($sub, '/');
            if ($sub !== '/') {
                $subfolder = $sub;
            }
        }
    }

    return $protocol . '://' . $host . $subfolder;
}

if (!defined('APP_URL')) {
    define('APP_URL', detectarAppUrl());
}

// ── BASE_DIR: ruta física absoluta del proyecto (para require/include) ──────
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

// ══════════════════════════════════════════════════════════════════
// DETECCIÓN DE ENTORNO
// ══════════════════════════════════════════════════════════════════
function esEntornoLocal(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    return (
        strpos($host, 'localhost') !== false ||
        strpos($host, '127.0.0.1') !== false ||
        strpos($host, '::1')       !== false ||
        strpos($host, '.local')    !== false
    );
}

if (!defined('APP_ENV')) {
    define('APP_ENV', esEntornoLocal() ? 'local' : 'production');
}

// ══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN GENERAL
// ══════════════════════════════════════════════════════════════════
if (!defined('APP_NAME'))    define('APP_NAME',    'SIGED');
if (!defined('APP_VERSION')) define('APP_VERSION', '5.0.0');
if (!defined('APP_BUILD'))   define('APP_BUILD',   '20260309');

date_default_timezone_set('America/Caracas');

if (!defined('ROOT_PATH'))   define('ROOT_PATH',   BASE_DIR);
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', BASE_DIR . '/subidas/');
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', BASE_DIR . '/config/');

if (!defined('MAX_FILE_SIZE'))      define('MAX_FILE_SIZE',      5242880);
if (!defined('ALLOWED_EXTENSIONS')) define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// ══════════════════════════════════════════════════════════════════
// MODO DEBUG — activo solo en entorno local
// ══════════════════════════════════════════════════════════════════
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', APP_ENV === 'local');
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

// ══════════════════════════════════════════════════════════════════
// SESIÓN
// ══════════════════════════════════════════════════════════════════
if (!defined('SESSION_NAME'))     define('SESSION_NAME',     'ISPEB_SESSION');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 1800);

// ══════════════════════════════════════════════════════════════════
// HELPERS DE SEGURIDAD — Anti-XSS
// ══════════════════════════════════════════════════════════════════
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('eAttr')) {
    function eAttr($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('eJs')) {
    function eJs($value): string {
        return addslashes(strip_tags((string)($value ?? '')));
    }
}

if (!function_exists('sanitizeInt')) {
    function sanitizeInt($value, int $default = 0): int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? (int)$filtered : $default;
    }
}

if (!function_exists('sanitizeString')) {
    function sanitizeString($value, int $maxLen = 500): string {
        return mb_substr(trim(strip_tags((string)($value ?? ''))), 0, $maxLen, 'UTF-8');
    }
}
