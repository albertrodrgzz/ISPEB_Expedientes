<?php

$files = [
    'c:/xampp/htdocs/APP3/vistas/funcionarios/index.php' => [
        'search' => '/\s*\$headerAction\s*=\s*verificarNivel.*? Nuevo Funcionario<\/a>\'\s*:\s*\'\';/s',
        'html' => "\n        <div style=\"display:flex; justify-content:flex-end; margin-bottom: 20px; margin-top: 10px;\">\n            <?php if (verificarNivel(2)): ?>\n                <a href=\"crear.php\" class=\"btn-primary\">\n                    <?= Icon::get('plus') ?> Nuevo Funcionario\n                </a>\n            <?php endif; ?>\n        </div>\n"
    ],
    'c:/xampp/htdocs/APP3/vistas/vacaciones/index.php' => [
        'search' => '/\s*\$headerAction\s*=\s*\'.*? Nueva Solicitud<\/button>\';/s',
        'html' => "\n        <div style=\"display:flex; justify-content:flex-end; margin-bottom: 20px; margin-top: 10px;\">\n            <button class=\"btn-primary\" onclick=\"abrirModalVacaciones()\">\n                <?= Icon::get('plus') ?> Nueva Solicitud\n            </button>\n        </div>\n"
    ],
    'c:/xampp/htdocs/APP3/vistas/amonestaciones/index.php' => [
        'search' => '/\s*\$headerAction\s*=\s*\'.*? Registrar Falta<\/button>\';/s',
        'html' => "\n        <div style=\"display:flex; justify-content:flex-end; margin-bottom: 20px; margin-top: 10px;\">\n            <button class=\"btn-primary\" onclick=\"abrirModalAmonestacion()\">\n                <?= Icon::get('plus') ?> Registrar Falta\n            </button>\n        </div>\n"
    ],
    'c:/xampp/htdocs/APP3/vistas/traslados/index.php' => [
        'search' => '/\s*\$headerAction\s*=\s*\'.*? Registrar Traslado<\/button>\';/s',
        'html' => "\n        <div style=\"display:flex; justify-content:flex-end; margin-bottom: 20px; margin-top: 10px;\">\n            <button class=\"btn-primary\" onclick=\"abrirModalTraslado()\">\n                <?= Icon::get('plus') ?> Registrar Traslado\n            </button>\n        </div>\n"
    ],
    'c:/xampp/htdocs/APP3/vistas/solicitudes/mis_solicitudes.php' => [
        'search' => '/\s*\$headerAction\s*=\s*\'.*? Nueva Solicitud<\/button>\';/s',
        'html' => "\n        <div style=\"display:flex; justify-content:flex-end; margin-bottom: 20px; margin-top: 10px;\">\n            <button class=\"btn-primary\" onclick=\"abrirModalNuevaSolicitud()\">\n                <?= Icon::get('plus') ?> Nueva Solicitud\n            </button>\n        </div>\n"
    ]
];

foreach ($files as $file => $data) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Solo continuar si la cadena $headerAction todavia existe en el archivo
    if (strpos($content, '$headerAction') === false) {
        echo "Saltando $file (ya procesado)\n";
        continue;
    }
    
    // Eliminar el $headerAction definition
    $newContent = preg_replace($data['search'], '', $content);
    
    if ($newContent === $content) {
        echo "Fallo MATCH Regex en $file\n";
        continue;
    }
    
    // Insertar el HTML despues del include de header.php
    // La forma mas segura es buscar el include header exacto, y reemplazar "?>" o similar
    $headerIncludeRegex = '/(include\s+__DIR__\s*\.\s*\'\/\.\.\/layout\/header\.php\';\s*\?>|include\s*\'\.\.\/layout\/header\.php\';\s*\?>)/s';
    
    // Tambien revisemos si hay un <div class="content-wrapper"> justo abajo para meterlo dentro de el.
    if (preg_match('/(include(?:[^>]+?)header\.php[\'"];\s*\?>)(\s*<div class="content-wrapper">)?/s', $newContent, $match)) {
        
        $replacement = $match[1];
        if (isset($match[2])) {
            $replacement .= $match[2] . "\n        " . $data['html'];
        } else {
            $replacement .= "\n        " . $data['html'];
        }
        
        $newContent = str_replace($match[0], $replacement, $newContent);
        file_put_contents($file, $newContent);
        echo "Exito en $file\n";
    } else {
        echo "Fallo HEADER MAP in $file\n";
    }
}
echo "Finalizado.";
?>
