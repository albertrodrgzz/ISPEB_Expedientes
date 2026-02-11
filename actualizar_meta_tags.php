<?php
/**
 * Script para actualizar todas las páginas con el meta tag app-url
 * SIGEX v3.5 - Consolidación de configuración global
 */

// Lista de archivos a actualizar
$files = [
    'c:\xampp\htdocs\APP3\vistas\vacaciones\index.php',
    'c:\xampp\htdocs\APP3\vistas\respaldo\exportar.php',
    'c:\xampp\htdocs\APP3\vistas\traslados\index.php',
    'c:\xampp\htdocs\APP3\vistas\respaldo\importar.php',
    'c:\xampp\htdocs\APP3\vistas\admin\auditoria.php',
    'c:\xampp\htdocs\APP3\vistas\renuncias_aprobadas\index.php',
    'c:\xampp\htdocs\APP3\vistas\respaldo\index.php',
    'c:\xampp\htdocs\APP3\vistas\reportes\generar_pdf.php',
    'c:\xampp\htdocs\APP3\vistas\reportes\index.php',
    'c:\xampp\htdocs\APP3\vistas\renuncias\index.php',
    'c:\xampp\htdocs\APP3\vistas\remociones\index.php',
    'c:\xampp\htdocs\APP3\vistas\perfil\index.php',
    'c:\xampp\htdocs\APP3\vistas\admin\organizacion.php',
    'c:\xampp\htdocs\APP3\vistas\nombramientos\index.php',
    'c:\xampp\htdocs\APP3\vistas\admin\index.php',
    'c:\xampp\htdocs\APP3\vistas\nombramientos\index_horizontal.php',
    'c:\xampp\htdocs\APP3\vistas\admin\restaurar.php',
    'c:\xampp\htdocs\APP3\vistas\expedientes\index.php',
    'c:\xampp\htdocs\APP3\vistas\funcionarios\crear.php',
    // 'c:\xampp\htdocs\APP3\vistas\dashboard\index.php', // Ya actualizado
    'c:\xampp\htdocs\APP3\vistas\despidos\index.php',
    'c:\xampp\htdocs\APP3\vistas\auth\recuperar.php',
    'c:\xampp\htdocs\APP3\vistas\auth\restablecer.php',
    'c:\xampp\htdocs\APP3\vistas\amonestaciones\index.php',
    'c:\xampp\htdocs\APP3\vistas\funcionarios\editar.php',
    'c:\xampp\htdocs\APP3\vistas\funcionarios\index.php',
    'c:\xampp\htdocs\APP3\vistas\funcionarios\test_javascript.php',
    'c:\xampp\htdocs\APP3\vistas\funcionarios\ver.php',
    'c:\xampp\htdocs\APP3\vistas\funcionarios\ver_backup.php',
];

$metaTag = '    <!-- APP_URL para JavaScript -->' . PHP_EOL . '    <meta name="app-url" content="<?= APP_URL ?>">';
$appJsScript = '    <script src="<?= APP_URL ?>/publico/js/app.js"></script>';

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "❌ No existe: $file\n";
        $errors++;
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Verificar si ya tiene el meta tag
    if (strpos($content, 'name="app-url"') !== false) {
        echo "⏭️  Ya tiene meta tag: $file\n";
        $skipped++;
        continue;
    }
    
    // Buscar la línea después de <head>
    // Patrón para encontrar <head> y agregar el meta tag después
    $pattern = '/(<head>)/i';
    
    if (preg_match($pattern, $content)) {
        // Insertar el meta tag después de <head>
        $content = preg_replace(
            '/(<head>\s*\n)/i',
            "$1" . $metaTag . PHP_EOL,
            $content,
            1
        );
        
        // Buscar </head> y agregar app.js antes si no existe
        if (strpos($content, 'app.js') === false) {
            $content = preg_replace(
                '/(\s*<\/head>)/i',
                PHP_EOL . $appJsScript . PHP_EOL . "$1",
                $content,
                1
            );
        }
        
        // Guardar el archivo
        if (file_put_contents($file, $content)) {
            echo "✅ Actualizado: $file\n";
            $updated++;
        } else {
            echo "❌ Error al guardar: $file\n";
            $errors++;
        }
    } else {
        echo "⚠️  No se encontró <head>: $file\n";
        $errors++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "📊 RESUMEN:\n";
echo "✅ Actualizados: $updated\n";
echo "⏭️  Ya tenían meta tag: $skipped\n";
echo "❌ Errores: $errors\n";
echo str_repeat('=', 50) . "\n";
