<?php
/**
 * HEADER - SIGED Enterprise
 * Sistema de Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/icons.php';

// Variables de sesión defensivas — nunca generan PHP notices
$_hdr_nombre   = $_SESSION['nombres'] ?? ($_SESSION['nombre_completo'] ?? 'Usuario');
$_hdr_apellido = $_SESSION['apellidos'] ?? ''; 
$_hdr_cargo    = $_SESSION['cargo'] ?? ($_SESSION['departamento'] ?? 'Sistema');

// Extraer las iniciales correctamente (1ra letra del nombre + 1ra del apellido)
$ini_n = mb_substr(trim($_hdr_nombre), 0, 1, 'UTF-8');
$ini_a = mb_substr(trim($_hdr_apellido), 0, 1, 'UTF-8');
$_hdr_iniciales = strtoupper($ini_n . $ini_a) ?: 'U';

// Cache buster: timestamp del archivo (cambia solo cuando se modifica el CSS/JS)
$_pub = __DIR__ . '/../../publico';
$_v_inter      = @filemtime($_pub . '/fonts/inter.css')           ?: APP_BUILD ?? date('Ymd');
$_v_modern     = @filemtime($_pub . '/css/modern-components.css') ?: APP_BUILD ?? date('Ymd');
$_v_hdrfix     = @filemtime($_pub . '/css/header-fix.css')        ?: APP_BUILD ?? date('Ymd');
$_v_swal       = @filemtime($_pub . '/vendor/sweetalert2/sweetalert2.all.min.js') ?: APP_BUILD ?? date('Ymd');
?>

<link rel="stylesheet" href="<?= APP_URL ?>/publico/fonts/inter.css?v=<?= $_v_inter ?>">

<link rel="icon"             type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
<link rel="icon"             type="image/png" sizes="16x16" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
<link rel="apple-touch-icon"                  sizes="180x180" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
<link rel="shortcut icon"    type="image/x-icon"              href="<?= APP_URL ?>/publico/imagenes/isotipo.png">

<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css?v=<?= $_v_modern ?>">

<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/header-fix.css?v=<?= $_v_hdrfix ?>">

<?php $_v_mf = @filemtime(__DIR__ . '/../../publico/css/mobile-first.css') ?: date('Ymd'); ?>
<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/mobile-first.css?v=<?= $_v_mf ?>">

<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js?v=<?= $_v_swal ?>"></script>

<header class="header">
    <div class="header-left">
        <button class="menu-toggle menu-toggle-header" id="menuToggleHeader" aria-label="Abrir menú">
            <?= Icon::get('menu') ?>
        </button>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Vista General') ?></h1>
    </div>

    <div class="header-right">
        <?php if (!empty($headerAction)): ?>
            <?= $headerAction ?>
        <?php endif; ?>

        <a href="<?= APP_URL ?>/vistas/perfil/" class="user-profile" title="Mi perfil">
            <div class="user-avatar" style="
                width: 40px; 
                height: 40px; 
                border-radius: 50%; 
                background: var(--color-primary, #0F4C81); 
                color: #ffffff; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                font-weight: 700;
                font-size: 14px;
                flex-shrink: 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                letter-spacing: 1px;
            ">
                <?= htmlspecialchars($_hdr_iniciales) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_hdr_nombre) ?></div>
                <div class="user-role"><?= htmlspecialchars($_hdr_cargo) ?></div>
            </div>
        </a>

        <button onclick="confirmarCerrarSesion()" class="btn-logout" title="Cerrar sesión">
            <?= Icon::get('logout') ?>
        </button>
    </div>
</header>

<script>
/* Variable global APP_URL para JS (badge, fetch, etc.) */
if (typeof APP_URL === 'undefined') {
    var APP_URL = '<?= addslashes(APP_URL) ?>';
}

/* Sincronizar el botón hamburguesa del header con el sidebar */
document.addEventListener('DOMContentLoaded', function() {
    var toggleHeader = document.getElementById('menuToggleHeader');
    var toggleSidebar = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    if (toggleHeader && (sidebar || toggleSidebar)) {
        toggleHeader.addEventListener('click', function() {
            // Disparar el mismo comportamiento que el botón original
            if (toggleSidebar) {
                toggleSidebar.click();
            } else if (sidebar) {
                sidebar.classList.toggle('active');
                if (overlay) overlay.classList.toggle('active');
            }
            toggleHeader.classList.toggle('active');
        });

        // Sincronizar estado activo cuando el sidebar cierra
        if (overlay) {
            overlay.addEventListener('click', function() {
                toggleHeader.classList.remove('active');
            });
        }
    }
});

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
            Swal.fire({
                title: 'Cerrando sesión...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            window.location.href = '<?= APP_URL ?>/config/logout.php';
        }
    });
}
</script>

<?php include __DIR__ . '/bottom-nav.php'; ?>