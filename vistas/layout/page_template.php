<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- APP_URL para JavaScript - REQUERIDO -->
    <meta name="app-url" content="<?= APP_URL ?>">
    <title><?= $pageTitle ?? 'SIGEX' ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <!-- Agregar scripts adicionales según necesidad -->
    <script src="<?= APP_URL ?>/publico/js/app.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- CONTENIDO AQUÍ -->
            
        </div>
    </div>
</body>
</html>
