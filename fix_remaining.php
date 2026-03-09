<?php
// 1. Vacaciones
$file = 'c:/xampp/htdocs/APP3/vistas/vacaciones/index.php';
$c = file_get_contents($file);
$c = preg_replace('/<div class="page-header">.*?<\/div>\s*<div class="kpi-grid">/s', '<div style="display:flex; justify-content:flex-end; margin-bottom: 24px;"><button class="btn-primary" onclick="abrirModalVacaciones()"><?= Icon::get(\'plus\') ?> Nueva Solicitud</button></div>'."\n\n".'            <div class="kpi-grid">', $c);
file_put_contents($file, $c);
echo "Vacaciones procesado.\n";

// 2. Traslados
$file = 'c:/xampp/htdocs/APP3/vistas/traslados/index.php';
$c = file_get_contents($file);
$c = preg_replace('/<!-- Header -->\s*<div class="page-header">.*?<\/div>\s*<!-- KPI Cards -->/s', '<div style="display:flex; justify-content:flex-end; margin-bottom: 24px;"><button class="btn-primary" onclick="abrirModalTraslado()"><?= Icon::get(\'plus\') ?> Registrar Traslado</button></div>'."\n\n".'            <!-- KPI Cards -->', $c);
file_put_contents($file, $c);
echo "Traslados procesado.\n";

// 3. Mis Solicitudes
$file = 'c:/xampp/htdocs/APP3/vistas/solicitudes/mis_solicitudes.php';
$c = file_get_contents($file);
$c = preg_replace('/\$headerAction = \'<button.*?\';/s', '', $c);
$c = preg_replace('/<div class="content-wrapper">\s*<!-- KPI Cards -->/s', '<div class="content-wrapper">'."\n".'            <div style="display:flex; justify-content:flex-end; margin-bottom: 24px;"><button class="btn-primary" onclick="abrirModalNuevaSolicitud()"><?= Icon::get(\'plus\') ?> Nueva Solicitud</button></div>'."\n\n".'            <!-- KPI Cards -->', $c);
file_put_contents($file, $c);
echo "Mis Solicitudes procesado.\n";
?>
