<?php
/**
 * Script de un solo uso: Añade favicon con APP_URL a todas las vistas
 * que tienen su propio <head> HTML sin el favicon correcto.
 * Acceder: http://localhost/APP3/agregar_favicons.php
 * ELIMINAR después de ejecutar.
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

// Vistas con <head> propio que NO heredan de header.php
$archivos = [
    'vistas/vacaciones/index.php',
    'vistas/traslados/index.php',
    'vistas/solicitudes/mis_solicitudes.php',
    'vistas/solicitudes/gestionar_solicitudes.php',
    'vistas/reportes/index.php',
    'vistas/perfil/index.php',
    'vistas/nombramientos/index.php',
    'vistas/funcionarios/ver.php',
    'vistas/funcionarios/index.php',
    'vistas/funcionarios/editar.php',
    'vistas/funcionarios/crear.php',
    'vistas/expedientes/index.php',
    'vistas/amonestaciones/index.php',
    'vistas/admin/auditoria.php',
    'vistas/admin/organizacion.php',
    'vistas/admin/index.php',
    'vistas/respaldo/index.php',
];

$favicon_tag = "\n    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"<?= APP_URL ?>/publico/imagenes/isotipo.png\">\n    <link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"<?= APP_URL ?>/publico/imagenes/isotipo.png\">";

$resultados = [];

foreach ($archivos as $rel) {
    $path = __DIR__ . '/' . $rel;
    if (!file_exists($path)) {
        $resultados[] = "⚠️ No encontrado: $rel";
        continue;
    }

    $contenido = file_get_contents($path);

    // ¿Ya tiene favicon con APP_URL? Si sí, skip
    if (strpos($contenido, 'APP_URL ?>/publico/imagenes/isotipo') !== false) {
        $resultados[] = "✅ Ya tiene favicon: $rel";
        continue;
    }

    // Insertar favicon después de <title>...</title>
    $nuevo = preg_replace(
        '/(<\/title>)/i',
        '$1' . $favicon_tag,
        $contenido, 1, $count
    );

    if ($count > 0) {
        file_put_contents($path, $nuevo);
        $resultados[] = "✅ Favicon añadido: $rel";
    } else {
        // Fallback: insertar después de <head>
        $nuevo = preg_replace(
            '/(<head[^>]*>)/i',
            '$1' . $favicon_tag,
            $contenido, 1, $count
        );
        if ($count > 0) {
            file_put_contents($path, $nuevo);
            $resultados[] = "✅ Favicon añadido (fallback head): $rel";
        } else {
            $resultados[] = "❌ No se pudo añadir en: $rel";
        }
    }
}

echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Favicon Update</title>
<style>body{font-family:Inter,sans-serif;max-width:700px;margin:40px auto;background:#f1f5f9;padding:20px}
.box{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
h1{color:#0F4C81}p{margin:6px 0;font-size:14px}.btn{display:inline-block;background:#0F4C81;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;margin-top:14px}</style>
</head><body><div class="box">';
echo '<h1>🔖 Actualización de Favicons</h1>';
foreach ($resultados as $r) echo "<p>$r</p>";
echo '<br><a class="btn" href="' . APP_URL . '">Ir al Login →</a></div></body></html>';
