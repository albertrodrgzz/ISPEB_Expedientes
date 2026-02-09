<!-- SweetAlert2 Local (offline-ready) -->
<script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <h1 class="page-title">Vista General</h1>
    </div>
    
    <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
        <!-- User Profile (Clickable) -->
        <a href="<?php echo APP_URL; ?>/vistas/perfil/" class="user-profile" style="display: flex; align-items: center; gap: 12px; text-decoration: none; cursor: pointer; transition: opacity 0.2s ease;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
            <div class="user-avatar-small" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                <?php echo strtoupper(substr($_SESSION['nombres'], 0, 2)); ?>
            </div>
            <div class="user-info">
                <div class="user-name-small" style="font-weight: 600; font-size: 14px; color: #1a202c;">
                    <?php echo htmlspecialchars($_SESSION['nombres']); ?>
                </div>
                <div class="user-role" style="font-size: 12px; color: #718096;">
                    <?php echo htmlspecialchars($_SESSION['cargo']); ?>
                </div>
            </div>
        </a>
        
        <!-- Logout Button (with confirmation) -->
        <button onclick="confirmarCerrarSesion()" class="logout-btn" title="Cerrar sesión" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #ef476f, #f78c6b); color: white; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; border: none; cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
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
        confirmButtonColor: '#ef476f',
        cancelButtonColor: '#6c757d',
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
            window.location.href = '<?php echo APP_URL; ?>/config/logout.php';
        }
    });
}
</script>
