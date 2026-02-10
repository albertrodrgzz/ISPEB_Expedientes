<!-- ===================================================
     SIDEBAR NAVIGATION - Sistema ISPEB
     IMPORTANTE: Usa APP_URL para todas las rutas
     ===================================================  -->

<!-- Botón hamburguesa para móvil -->
<button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Overlay para cerrar sidebar en móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Principal -->
<aside class="sidebar" id="sidebar">
    <!-- ===== HEADER DEL SIDEBAR ===== -->
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <img src="<?php echo APP_URL; ?>/publico/imagenes/logo-telematica-letras-negras.png" 
                     alt="<?php echo APP_NAME; ?>" 
                     style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
    
    <!-- ===== MENÚ DE NAVEGACIÓN ===== -->
    <nav class="sidebar-nav">
        <?php
        // Detectar página y directorio actual para marcar item activo
        $current_page = basename($_SERVER['PHP_SELF']);
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        
        /**
         * Obtener icono SVG por nombre
         * Iconos inline SVG para evitar dependencias externas
         */
        function getIconSVG($iconName) {
            $icons = [
                // Principal
                'dashboard' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
                'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
                'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
                
                // Gestión RRHH
                'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
                'sun' => '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>',
                'repeat' => '<polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path>',
                'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
                'x-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
                
                // Sistema
                'bar-chart' => '<line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line>',
                'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
                'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>'
            ];
            
            return $icons[$iconName] ?? '';
        }
        
        /**
         * Configuración del menú por secciones
         * Cada sección tiene un nivel de acceso requerido
         */
        $menuConfig = [
            // ===== SECCIÓN: PRINCIPAL =====
            [
                'section' => 'PRINCIPAL',
                'nivel_requerido' => 3, // Todos los niveles
                'items' => [
                    [
                        'label' => 'Inicio',
                        'url' => '/vistas/dashboard/index.php',
                        'icon' => 'dashboard',
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
                'nivel_requerido' => 2, // Nivel 1 y 2
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
                'nivel_requerido' => 2, // Nivel 1 y 2
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
                        'nivel_item' => 1 // Solo para nivel 1
                    ],
                    [
                        'label' => 'Respaldos',
                        'url' => '/vistas/respaldo/index.php',
                        'icon' => 'database',
                        'dir' => 'respaldo',
                        'nivel_item' => 1 // Solo para nivel 1
                    ]
                ]
            ]
        ];
        
        /**
         * Renderizar menú dinámicamente
         */
        foreach ($menuConfig as $section):
            // Verificar si el usuario tiene permisos para ver esta sección
            if (!verificarNivel($section['nivel_requerido'])) {
                continue;
            }
        ?>
            <div class="nav-section">
                <div class="nav-section-title"><?php echo $section['section']; ?></div>
                
                <?php foreach ($section['items'] as $item): 
                    // Verificar permisos específicos del item (si existen)
                    if (isset($item['nivel_item']) && !verificarNivel($item['nivel_item'])) {
                        continue;
                    }
                ?>
                    
                    <?php if (isset($item['submenu'])): ?>
                        <!-- ===== ITEM CON SUBMENÚ ===== -->
                        <div class="nav-item-accordion">
                            <a href="javascript:void(0)" class="nav-item" onclick="toggleSubmenu(this)">
                                <span class="nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <?php echo getIconSVG($item['icon']); ?>
                                    </svg>
                                </span>
                                <span class="nav-text"><?php echo $item['label']; ?></span>
                                <span class="nav-arrow">▼</span>
                            </a>
                            <div class="nav-submenu" style="display: none;">
                                <?php foreach ($item['submenu'] as $subitem): 
                                    $isSubActive = isset($subitem['dir']) && $current_dir == $subitem['dir'];
                                ?>
                                    <a href="<?php echo APP_URL . $subitem['url']; ?>" 
                                       class="nav-item nav-subitem <?php echo $isSubActive ? 'active' : ''; ?>">
                                        <span class="nav-text"><?php echo $subitem['label']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- ===== ITEM NORMAL ===== -->
                        <?php $isActive = isset($item['dir']) && $current_dir == $item['dir']; ?>
                        <a href="<?php echo APP_URL . $item['url']; ?>" 
                           class="nav-item <?php echo $isActive ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <?php echo getIconSVG($item['icon']); ?>
                                </svg>
                            </span>
                            <span class="nav-text"><?php echo $item['label']; ?></span>
                        </a>
                    <?php endif; ?>
                    
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- ===================================================
     JAVASCRIPT: Funcionalidad del Sidebar
     ===================================================  -->
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
 * Compatible con todas las páginas del sistema
 */
(function initSidebarMobile() {
    'use strict';
    
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Validar que existan los elementos
    if (!menuToggle || !sidebar || !overlay) {
        console.warn('Sidebar: Elementos de menú móvil no encontrados');
        return;
    }
    
    /**
     * Abrir/cerrar sidebar
     */
    function toggleSidebar(show) {
        if (show) {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            menuToggle.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevenir scroll
        } else {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Click en botón hamburguesa
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = sidebar.classList.contains('active');
        toggleSidebar(!isOpen);
    });
    
    // Click en overlay para cerrar
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
    
    console.log('Sidebar inicializado correctamente');
})();
</script>
