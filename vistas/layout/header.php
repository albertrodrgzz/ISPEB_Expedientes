<?php
/**
 * HEADER - SIGED Enterprise
 * Sistema de Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/icons.php';

// Variables de sesión defensivas — nunca generan PHP notices
$_hdr_nombre  = $_SESSION['nombres']       ?? ($_SESSION['nombre_completo'] ?? 'Usuario');
$_hdr_cargo   = $_SESSION['cargo']         ?? ($_SESSION['departamento']    ?? 'Sistema');
$_hdr_nivel   = (int)($_SESSION['nivel_acceso'] ?? 3);
$_hdr_inicial = strtoupper(mb_substr(trim($_hdr_nombre), 0, 1, 'UTF-8') ?: 'U');
$_hdr_av_clase = match($_hdr_nivel) {
    1       => 'avatar-inicial--nivel-1',
    2       => 'avatar-inicial--nivel-2',
    default => 'avatar-inicial--nivel-3',
};

// Cache buster: timestamp del archivo (cambia solo cuando se modifica el CSS/JS)
$_pub = __DIR__ . '/../../publico';
$_v_inter      = @filemtime($_pub . '/fonts/inter.css')           ?: APP_BUILD ?? date('Ymd');
$_v_modern     = @filemtime($_pub . '/css/modern-components.css') ?: APP_BUILD ?? date('Ymd');
$_v_hdrfix     = @filemtime($_pub . '/css/header-fix.css')        ?: APP_BUILD ?? date('Ymd');
$_v_swal       = @filemtime($_pub . '/vendor/sweetalert2/sweetalert2.all.min.js') ?: APP_BUILD ?? date('Ymd');
?>

<!-- Fuente Inter — LOCAL (100% offline, sin CDN) -->
<link rel="stylesheet" href="<?= APP_URL ?>/publico/fonts/inter.css?v=<?= $_v_inter ?>">

<!-- Favicon absoluto — funciona en CUALQUIER ruta de la app -->
<link rel="icon"             type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
<link rel="icon"             type="image/png" sizes="16x16" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
<link rel="apple-touch-icon"                  sizes="180x180" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
<link rel="shortcut icon"    type="image/x-icon"              href="<?= APP_URL ?>/publico/imagenes/isotipo.png">

<!-- modern-components.css garantiza estilos de avatar en todas las vistas -->
<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css?v=<?= $_v_modern ?>">

<!-- CSS Fix para header flotante moderno -->
<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/header-fix.css?v=<?= $_v_hdrfix ?>">

<!-- SweetAlert2 Local (offline-ready) -->
<script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js?v=<?= $_v_swal ?>"></script>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Vista General') ?></h1>
    </div>

    <div class="header-right">
        <?php if (!empty($headerAction)): ?>
            <?= $headerAction ?>
        <?php endif; ?>

        <!-- User Profile (Clickable) -->
        <a href="<?= APP_URL ?>/vistas/perfil/" class="user-profile" title="Mi perfil">
            <div class="user-avatar avatar-inicial <?= $_hdr_av_clase ?>">
                <?= htmlspecialchars($_hdr_inicial) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_hdr_nombre) ?></div>
                <div class="user-role"><?= htmlspecialchars($_hdr_cargo) ?></div>
            </div>
        </a>

        <!-- Logout Button — SIEMPRE visible -->
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
