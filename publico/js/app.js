/**
 * JavaScript Principal
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 */

document.addEventListener('DOMContentLoaded', function () {
    // Búsqueda en tiempo real
    const buscarInput = document.getElementById('buscar');

    if (buscarInput) {
        buscarInput.addEventListener('input', function (e) {
            const termino = e.target.value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-funcionarios tr');

            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(termino) ? '' : 'none';
            });
        });
    }

    // Toggle sidebar en móvil
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar && sidebarOverlay) {
        // Abrir/cerrar sidebar
        menuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            menuToggle.classList.toggle('active');

            console.log('Menu toggle clicked. Sidebar active:', sidebar.classList.contains('active'));
        });

        // Cerrar al hacer click en overlay
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            menuToggle.classList.remove('active');
        });

        // Cerrar al hacer click en un link del menú (móvil)
        const navLinks = sidebar.querySelectorAll('.nav-item');
        navLinks.forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    menuToggle.classList.remove('active');
                }
            });
        });

        // Cerrar sidebar al redimensionar a desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
    } else {
        console.log('Menu elements not found:', {
            menuToggle: !!menuToggle,
            sidebar: !!sidebar,
            sidebarOverlay: !!sidebarOverlay
        });
    }
});
