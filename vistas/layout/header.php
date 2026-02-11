<?php
/**
 * HEADER - SIGED Enterprise
 * Sistema de Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/icons.php';
?>

<!-- Cargar fuente Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- CSS Fix para header flotante moderno -->
<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/header-fix.css">

<!-- SweetAlert2 Local (offline-ready) -->
<script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <h1 class="page-title">Vista General</h1>
    </div>
    
    <div class="header-right">
        <!-- User Profile (Clickable) -->
        <a href="<?= APP_URL ?>/vistas/perfil/" class="user-profile" title="Mi perfil">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['nombres'], 0, 2)) ?>
            </div>
            <div class="user-info">
                <div class="user-name">
                    <?= htmlspecialchars($_SESSION['nombres']) ?>
                </div>
                <div class="user-role">
                    <?= htmlspecialchars($_SESSION['cargo']) ?>
                </div>
            </div>
        </a>
        
        <!-- Logout Button -->
        <button onclick="confirmarCerrarSesion()" class="btn-logout" title="Cerrar sesión">
            <?= Icon::get('logout') ?>
        </button>
    </div>
</header>

<script>
/**
 * Confirmar cierre de sesión con SweetAlert2
 */
function confirmarCerrarSesion() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: '¿Está seguro de que desea cerrar su sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar indicador de carga
            Swal.fire({
                title: 'Cerrando sesión...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirigir al logout
            window.location.href = '<?= APP_URL ?>/config/logout.php';
        }
    });
}
</script>
