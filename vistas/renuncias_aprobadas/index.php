<?php
/**
 * Vista: Módulo de Renuncias Aprobadas
 * Placeholder para futuro desarrollo
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ../dashboard/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renuncias Aprobadas - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">✅ Renuncias Aprobadas</h2>
                </div>
                <div style="text-align: center; padding: 80px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.4;">📋</div>
                    <h3 style="color: #718096; font-weight: 500;">Módulo en Desarrollo</h3>
                    <p style="color: #a0aec0; margin-top: 12px;">Este módulo estará disponible próximamente</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
