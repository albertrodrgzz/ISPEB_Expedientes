<?php
/**
 * Script de Depuración - APP_URL
 * Ejecutar este archivo directamente para ver los valores de configuración
 */

echo "<h1>Depuración de APP_URL</h1>";
echo "<pre>";

echo "=== VARIABLES DEL SERVIDOR ===\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NO DEFINIDO') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NO DEFINIDO') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "\n";
echo "SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? 'NO DEFINIDO') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NO DEFINIDO') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NO DEFINIDO') . "\n";

echo "\n=== DETECCIÓN DE RUTA BASE ===\n";
$scriptName = $_SERVER['SCRIPT_NAME'];
echo "Script Name original: $scriptName\n";

$dirname1 = dirname($_SERVER['SCRIPT_NAME']);
echo "dirname(SCRIPT_NAME): $dirname1\n";

$dirname2 = dirname(dirname($_SERVER['SCRIPT_NAME']));
echo "dirname(dirname(SCRIPT_NAME)): $dirname2\n";

$basePath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
echo "Base Path (limpiado): $basePath\n";

echo "\n=== PROTOCOLO ===\n";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
echo "Protocolo detectado: $protocol\n";

echo "\n=== APP_URL CALCULADO ===\n";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($basePath === '/') {
    $basePath = '';
}

$app_url_calculated = $protocol . '://' . $host . $basePath;
echo "APP_URL: $app_url_calculated\n";

echo "\n=== APP_URL DESDE CONFIG ===\n";
require_once __DIR__ . '/config/config.php';
echo "APP_URL (constante): " . APP_URL . "\n";

echo "\n=== RUTA CORRECTA ESPERADA ===\n";
// Detectar automáticamente la ruta correcta
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relativePath = str_replace($documentRoot, '', $scriptPath);
$correctPath = $protocol . '://' . $host . $relativePath;
echo "Ruta correcta: $correctPath\n";

echo "</pre>";
?>
