<?php
/**
 * Configuración Base de la Aplicación
 * SIGED — Sistema de Gestión de Expedientes Digitales
 * Instituto de Salud Pública del Estado Bolívar (ISPEB)
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  VERSIÓN DEFINITIVA — Anti-Mixed Content para Render + Docker   ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

// ===================================================
// CONFIGURACIÓN DE URL — MÉTODO MULTICAPA
// ===================================================

/**
 * detectarAppUrl()
 *
 * MÉTODO DEFINITIVO PARA RENDER:
 * ─────────────────────────────────────────────────────────────────
 * CAPA 1 (más confiable): Variable de entorno APP_URL
 *   → La defines en el Dashboard de Render:
 *     Environment > Add Variable > APP_URL = https://siged.onrender.com
 *   → Bypasea TODA detección automática. Siempre gana.
 *
 * CAPA 2: HTTP_X_FORWARDED_PROTO
 *   → Header que inyecta Render con el protocolo real del cliente.
 *
 * CAPA 3: HTTP_X_FORWARDED_SSL
 *   → Alternativa usada por HAProxy y otros balanceadores.
 *
 * CAPA 4: $_SERVER['HTTPS']
 *   → Solo funciona si Apache maneja SSL directamente (desarrollo local).
 *
 * CAPA 5: SERVER_PORT = 443
 *   → Último recurso. Falso en Render (usa puerto dinámico interno).
 */
function detectarAppUrl(): string
{
    // ══════════════════════════════════════════════════════════════
    // CAPA 1 — Variable de entorno APP_URL (SOLUCIÓN DEFINITIVA)
    // ══════════════════════════════════════════════════════════════
    // Configura esto en Render: Environment > Add Variable
    //   Nombre: APP_URL
    //   Valor:  https://siged.onrender.com
    $envUrl = getenv('APP_URL');
    if (!empty($envUrl) && filter_var($envUrl, FILTER_VALIDATE_URL)) {
        return rtrim($envUrl, '/');
    }

    // ══════════════════════════════════════════════════════════════
    // CAPA 2-5 — Detección automática de protocolo (fallback)
    // ══════════════════════════════════════════════════════════════
    $protocol = 'http';

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $fwd = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($fwd === 'https') {
            $protocol = 'https';
        }
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        $protocol = 'https';
    } elseif (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $protocol = 'https';
    } elseif (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        $protocol = 'https';
    }

    $host = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]));
    }
    if (empty($host)) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    // En Render el app sirve desde la raiz "/" — sin subfolder en la URL.
    return rtrim($protocol . '://' . $host, '/');
}

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
    define('APP_VERSION', '4.8.1');
}

if (!defined('APP_BUILD')) {
    define('APP_BUILD', '20260309');
}

// ===================================================
// ZONA HORARIA
// ===================================================

date_default_timezone_set('America/Caracas');

// ===================================================
// RUTAS DE FILESYSTEM
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
    define('MAX_FILE_SIZE', 5242880);
}

if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
}

// ===================================================
// MODO DEBUG
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
    define('SESSION_LIFETIME', 1800);
}

// ===================================================
// HELPERS DE SEGURIDAD — Anti-XSS
// ===================================================

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
