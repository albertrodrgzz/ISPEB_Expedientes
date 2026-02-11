<?php
/**
 * SIDEBAR NAVIGATION - SIGED Enterprise
 * Sistema de Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/icons.php';
?>

<!-- CSS Fix para forzar texto blanco -->
<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/sidebar-fix.css">

<!-- Botón hamburguesa para móvil -->
<button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
    <?= Icon::get('menu') ?>
</button>

<!-- Overlay para cerrar sidebar en móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Principal -->
<aside class="sidebar" id="sidebar">
    <!-- ===== HEADER DEL SIDEBAR ===== -->
    <div class="sidebar-header">
        <div class="logo">
            <img src="<?= APP_URL ?>/publico/imagenes/logo-telematica-letras-blancas.png" 
                 alt="<?= APP_NAME ?>" 
                 class="logo-image"
                 onerror="this.src='<?= APP_URL ?>/publico/imagenes/logo-telematica-letras-negras.png'">
        </div>
    </div>
    
    <!-- ===== MENÚ DE NAVEGACIÓN ===== -->
    <nav class="sidebar-nav">
        <?php
        // Detectar página y directorio actual para marcar item activo
        $current_page = basename($_SERVER['PHP_SELF']);
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        
        /**
         * Configuración del menú por secciones
         */
        $menuConfig = [
            // ===== SECCIÓN: PRINCIPAL =====
            [
                'section' => 'PRINCIPAL',
                'nivel_requerido' => 3,
                'items' => [
                    [
                        'label' => 'Inicio',
                        'url' => '/vistas/dashboard/index.php',
                        'icon' => 'home',
                        'dir' => 'dashboard'
                    ],
                    [
                        'label' => 'Funcionarios',
                        'url' => '/vistas/funcionarios/index.php',
                        'icon' => 'users',
                        'dir' => 'funcionarios'
                    ],
                    [
                        'label' => 'Expedientes',
                        'url' => '/vistas/expedientes/index.php',
                        'icon' => 'folder',
                        'dir' => 'expedientes'
                    ]
                ]
            ],
            
            // ===== SECCIÓN: GESTIÓN RRHH =====
            [
                'section' => 'GESTIÓN RRHH',
                'nivel_requerido' => 2,
                'items' => [
                    [
                        'label' => 'Nombramientos',
                        'url' => '/vistas/nombramientos/index.php',
                        'icon' => 'file-text',
                        'dir' => 'nombramientos'
                    ],
                    [
                        'label' => 'Vacaciones',
                        'url' => '/vistas/vacaciones/index.php',
                        'icon' => 'sun',
                        'dir' => 'vacaciones'
                    ],
                    [
                        'label' => 'Traslados',
                        'url' => '/vistas/traslados/index.php',
                        'icon' => 'repeat',
                        'dir' => 'traslados'
                    ],
                    [
                        'label' => 'Amonestaciones',
                        'url' => '/vistas/amonestaciones/index.php',
                        'icon' => 'alert-triangle',
                        'dir' => 'amonestaciones'
                    ],
                    [
                        'label' => 'Remociones',
                        'url' => '/vistas/remociones/index.php',
                        'icon' => 'x-circle',
                        'dir' => 'remociones'
                    ],
                    [
                        'label' => 'Salidas',
                        'icon' => 'log-out',
                        'submenu' => [
                            [
                                'label' => 'Despidos',
                                'url' => '/vistas/despidos/index.php',
                                'dir' => 'despidos'
                            ],
                            [
                                'label' => 'Renuncias',
                                'url' => '/vistas/renuncias/index.php',
                                'dir' => 'renuncias'
                            ],
                            [
                                'label' => 'Renuncias Aprobadas',
                                'url' => '/vistas/renuncias_aprobadas/index.php',
                                'dir' => 'renuncias_aprobadas'
                            ]
                        ]
                    ]
                ]
            ],
            
            // ===== SECCIÓN: SISTEMA =====
            [
                'section' => 'SISTEMA',
                'nivel_requerido' => 2,
                'items' => [
                    [
                        'label' => 'Reportes',
                        'url' => '/vistas/reportes/index.php',
                        'icon' => 'bar-chart',
                        'dir' => 'reportes'
                    ],
                    [
                        'label' => 'Administración',
                        'url' => '/vistas/admin/index.php',
                        'icon' => 'settings',
                        'dir' => 'admin',
                        'nivel_item' => 1
                    ],
                    [
                        'label' => 'Respaldos',
                        'url' => '/vistas/respaldo/index.php',
                        'icon' => 'database',
                        'dir' => 'respaldo',
                        'nivel_item' => 1
                    ]
                ]
            ]
        ];
        
        /**
         * Renderizar menú dinámicamente
         */
        foreach ($menuConfig as $section):
            // Verificar permisos de sección
            if (!verificarNivel($section['nivel_requerido'])) {
                continue;
            }
        ?>
            <div class="nav-section">
                <div class="nav-section-title"><?= $section['section'] ?></div>
                
                <?php foreach ($section['items'] as $item): 
                    // Verificar permisos específicos del item
                    if (isset($item['nivel_item']) && !verificarNivel($item['nivel_item'])) {
                        continue;
                    }
                ?>
                    
                    <?php if (isset($item['submenu'])): ?>
                        <!-- ITEM CON SUBMENÚ -->
                        <div class="nav-item-accordion">
                            <a href="javascript:void(0)" class="nav-item" onclick="toggleSubmenu(this)">
                                <span class="nav-icon">
                                    <?= Icon::get($item['icon']) ?>
                                </span>
                                <span class="nav-text"><?= $item['label'] ?></span>
                                <span class="nav-arrow">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            </a>
                            <div class="nav-submenu" style="display: none;">
                                <?php foreach ($item['submenu'] as $subitem): 
                                    $isSubActive = isset($subitem['dir']) && $current_dir == $subitem['dir'];
                                ?>
                                    <a href="<?= APP_URL . $subitem['url'] ?>" 
                                       class="nav-item nav-subitem <?= $isSubActive ? 'active' : '' ?>">
                                        <span class="nav-text"><?= $subitem['label'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- ITEM NORMAL -->
                        <?php $isActive = isset($item['dir']) && $current_dir == $item['dir']; ?>
                        <a href="<?= APP_URL . $item['url'] ?>" 
                           class="nav-item <?= $isActive ? 'active' : '' ?>">
                            <span class="nav-icon">
                                <?= Icon::get($item['icon']) ?>
                            </span>
                            <span class="nav-text"><?= $item['label'] ?></span>
                        </a>
                    <?php endif; ?>
                    
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- JavaScript: Funcionalidad del Sidebar -->
<script>
/**
 * Toggle submenu (acordeón)
 */
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.nav-arrow');
    
    if (!submenu || !arrow) return;
    
    const isOpen = submenu.style.display === 'block';
    
    if (isOpen) {
        submenu.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
        element.classList.remove('open');
    } else {
        submenu.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
        element.classList.add('open');
    }
}

/**
 * Inicializar menú hamburguesa (móvil)
 */
(function initSidebarMobile() {
    'use strict';
    
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!menuToggle || !sidebar || !overlay) {
        console.warn('Sidebar: Elementos de menú móvil no encontrados');
        return;
    }
    
    function toggleSidebar(show) {
        if (show) {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            menuToggle.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = sidebar.classList.contains('active');
        toggleSidebar(!isOpen);
    });
    
    overlay.addEventListener('click', function() {
        toggleSidebar(false);
    });
    
    // Cerrar al hacer click en un enlace (solo en móvil)
    const navLinks = sidebar.querySelectorAll('.nav-item[href]');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                toggleSidebar(false);
            }
        });
    });
    
    // Cerrar sidebar cuando se redimensiona a desktop
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 1024) {
                toggleSidebar(false);
            }
        }, 250);
    });
    
    // Abrir submenús activos al cargar
    document.querySelectorAll('.nav-subitem.active').forEach(function(activeSubitem) {
        const accordion = activeSubitem.closest('.nav-item-accordion');
        if (accordion) {
            const parentLink = accordion.querySelector('.nav-item');
            const submenu = accordion.querySelector('.nav-submenu');
            const arrow = accordion.querySelector('.nav-arrow');
            
            if (submenu && arrow && parentLink) {
                submenu.style.display = 'block';
                arrow.style.transform = 'rotate(180deg)';
                parentLink.classList.add('open');
            }
        }
    });
    
    console.log('✅ Sidebar SIGED Enterprise inicializado');
})();
</script>
