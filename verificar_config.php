<?php
/**
 * verificar_config.php — Diagnóstico de configuración SIGED
 * ============================================================
 * Acceder en XAMPP:  http://localhost/ISPEB_Expedientes/verificar_config.php
 * Acceder en Render: https://siged.onrender.com/verificar_config.php
 *
 * ⚠️ ELIMINAR ESTE ARCHIVO antes de la presentación final.
 * ============================================================
 */

// Suprimir errores fatales para mostrarlos limpio
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Diagnóstico SIGED</title>
<style>
  body { font-family: monospace; background: #0d1117; color: #c9d1d9; padding: 30px; }
  h2   { color: #58a6ff; border-bottom: 1px solid #30363d; padding-bottom: 8px; }
  .ok  { color: #3fb950; } .fail { color: #f85149; } .warn { color: #d29922; }
  pre  { background: #161b22; border: 1px solid #30363d; border-radius: 6px;
         padding: 16px; white-space: pre-wrap; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 12px;
           font-size: 12px; font-weight: bold; margin-left: 8px; }
  .b-ok   { background: #1f4b2a; color: #3fb950; }
  .b-fail { background: #4b1f1f; color: #f85149; }
</style></head><body>';

echo '<h2>🔍 Diagnóstico de Configuración — SIGED v' . APP_VERSION . '</h2>';
echo '<pre>';

$lines = [];

// ── Entorno detectado ──────────────────────────────────────────────────────
$env_ok = defined('APP_ENV');
$lines[] = sprintf('<span class="%s">APP_ENV</span>       = %s%s',
    $env_ok ? 'ok' : 'fail',
    defined('APP_ENV') ? APP_ENV : 'NO DEFINIDO',
    $env_ok ? ' <span class="badge b-ok">OK</span>' : ' <span class="badge b-fail">FALLO</span>'
);

// ── APP_URL ────────────────────────────────────────────────────────────────
$url_ok = defined('APP_URL') && filter_var(APP_URL, FILTER_VALIDATE_URL);
$lines[] = sprintf('<span class="%s">APP_URL</span>       = %s%s',
    $url_ok ? 'ok' : 'fail',
    defined('APP_URL') ? APP_URL : 'NO DEFINIDO',
    $url_ok ? ' <span class="badge b-ok">OK</span>' : ' <span class="badge b-fail">FALLO</span>'
);

// ── BASE_DIR ───────────────────────────────────────────────────────────────
$dir_ok = defined('BASE_DIR') && is_dir(BASE_DIR);
$lines[] = sprintf('<span class="%s">BASE_DIR</span>      = %s%s',
    $dir_ok ? 'ok' : 'fail',
    defined('BASE_DIR') ? BASE_DIR : 'NO DEFINIDO',
    $dir_ok ? ' <span class="badge b-ok">OK</span>' : ' <span class="badge b-fail">FALLO</span>'
);

// ── Servidor ───────────────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '<span class="warn">──── Servidor ────────────────────────────────────</span>';
$lines[] = 'HTTP_HOST      = ' . ($_SERVER['HTTP_HOST'] ?? 'N/A');
$lines[] = 'DOCUMENT_ROOT  = ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A');
$lines[] = 'SCRIPT_NAME    = ' . ($_SERVER['SCRIPT_NAME'] ?? 'N/A');
$lines[] = 'PHP_VERSION    = ' . PHP_VERSION;

// ── APP_URL env override ───────────────────────────────────────────────────
$envUrl = getenv('APP_URL');
$lines[] = '';
$lines[] = '<span class="warn">──── Variable de entorno ─────────────────────────</span>';
$lines[] = 'getenv(APP_URL) = ' . ($envUrl ?: '(no definida — se usa detección automática)');

// ── Base de datos ──────────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '<span class="warn">──── Base de Datos ───────────────────────────────</span>';

require_once __DIR__ . '/config/database.php';

$lines[] = 'DB_HOST        = ' . DB_HOST;
$lines[] = 'DB_PORT        = ' . DB_PORT;
$lines[] = 'DB_NAME        = ' . DB_NAME;
$lines[] = 'DB_USER        = ' . DB_USER;
$lines[] = 'SSL activo     = ' . (necesitaSSL() ? '<span class="ok">SÍ (Aiven/externo)</span>' : '<span class="warn">NO (localhost)</span>');

// ── Prueba de conexión ─────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '<span class="warn">──── Prueba de Conexión ──────────────────────────</span>';
try {
    $db = getDB();
    $v  = $db->query('SELECT VERSION() as v')->fetch();
    $lines[] = '<span class="ok">Conexión: ✅ EXITOSA — MySQL ' . $v['v'] . '</span>';
} catch (Exception $e) {
    $lines[] = '<span class="fail">Conexión: ❌ FALLIDA — ' . htmlspecialchars($e->getMessage()) . '</span>';
}

// ── ca.pem ─────────────────────────────────────────────────────────────────
$caPem = __DIR__ . '/config/ca.pem';
$lines[] = 'ca.pem existe  = ' . (file_exists($caPem)
    ? '<span class="ok">SÍ (' . $caPem . ')</span>'
    : '<span class="warn">NO (solo necesario en Render/Aiven)</span>');

// ── Resultado esperado ─────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '<span class="warn">──── Resultado esperado por entorno ──────────────</span>';
$lines[] = '<span class="ok">XAMPP:  APP_URL = http://localhost/ISPEB_Expedientes  | ENV = local  | SSL = NO</span>';
$lines[] = '<span class="ok">Render: APP_URL = https://siged.onrender.com          | ENV = production | SSL = SÍ</span>';

echo implode("\n", $lines);
echo '</pre>';
echo '<p style="color:#6e7681;font-size:12px;">⚠️ Eliminar este archivo antes de la presentación final.</p>';
echo '</body></html>';
