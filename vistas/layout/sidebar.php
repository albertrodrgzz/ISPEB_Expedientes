<!-- Sidebar Navigation -->
<!-- Botón hamburguesa para móvil -->
<button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Overlay para cerrar sidebar en móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <img src="<?php echo APP_URL; ?>/publico/imagenes/logo-telematica-letras-negras.png" alt="ISPEB">
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php
        // Detectar página y directorio actual
        $current_page = basename($_SERVER['PHP_SELF']);
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        
        /**
         * Obtener SVG de icono por nombre
         */
        function getIconSVG($iconName) {
            $icons = [
                'dashboard' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
                'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
                'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
                'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
                'sun' => '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>',
                'repeat' => '<polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path>',
                'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
                'x-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
                'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>',
                'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>'
            ];
            
            return $icons[$iconName] ?? '';
        }
        
        // Configuración del menú
        $menuItems = [
            [
                'section' => 'MENÚ PRINCIPAL',
                'nivel_requerido' => 3, // Todos pueden ver
                'items' => [
                    [
                        'label' => 'Dashboard',
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
            [
                'section' => 'GESTIÓN ADMINISTRATIVA',
                'nivel_requerido' => 2, // Solo nivel 1 y 2
                'items' => [
                    [
                        'label' => 'Nombramientos',
                        'url' => '/vistas/nombramientos/index.php',
                        'icon' => 'file-text',
                        'dir' => 'nombramientos'
                    ],
                    [
                        'label' => 'Control Vacacional',
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
                                'url' => '/vistas/despidos/index.php'
                            ],
                            [
                                'label' => 'Renuncias',
                                'url' => '/vistas/renuncias/index.php'
                            ],
                            [
                                'label' => 'Renuncias Aprobadas',
                                'url' => '/vistas/renuncias_aprobadas/index.php'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'section' => 'ADMINISTRACIÓN',
                'nivel_requerido' => 1, // Solo nivel 1 (Directores)
                'items' => [
                    [
                        'label' => 'Panel Admin',
                        'url' => '/vistas/admin/index.php',
                        'icon' => 'settings',
                        'dir' => 'admin'
                    ],
                    [
                        'label' => 'Respaldo BD',
                        'url' => '/vistas/respaldo/index.php',
                        'icon' => 'download',
                        'dir' => 'respaldo'
                    ],
                    [
                        'label' => 'Auditoría',
                        'url' => '/vistas/admin/auditoria.php',
                        'icon' => 'file-text'
                    ]
                ]
            ]
        ];
        
        // Renderizar menú dinámicamente
        foreach ($menuItems as $section):
            // Verificar permisos de sección
            if (!verificarNivel($section['nivel_requerido'])) {
                continue;
            }
        ?>
            <div class="nav-section">
                <div class="nav-section-title"><?php echo $section['section']; ?></div>
                
                <?php foreach ($section['items'] as $item): ?>
                    <?php if (isset($item['submenu'])): ?>
                        <!-- Item con submenú -->
                        <div class="nav-item-accordion">
                            <a href="javascript:void(0)" class="nav-item" onclick="toggleSubmenu(this)">
                                <span class="nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <?php echo getIconSVG($item['icon']); ?>
                                    </svg>
                                </span>
                                <span class="nav-text"><?php echo $item['label']; ?></span>
                                <span class="nav-arrow" style="margin-left: auto; transition: transform 0.3s ease;">▼</span>
                            </a>
                            <div class="nav-submenu" style="display: none; padding-left: 20px;">
                                <?php foreach ($item['submenu'] as $subitem): ?>
                                    <a href="<?php echo APP_URL . $subitem['url']; ?>" class="nav-item nav-subitem">
                                        <span class="nav-text"><?php echo $subitem['label']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Item normal -->
                        <a href="<?php echo APP_URL . $item['url']; ?>" 
                           class="nav-item <?php echo (isset($item['dir']) && $current_dir == $item['dir']) ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
    
    <script>
    // Toggle submenu
    function toggleSubmenu(element) {
        const submenu = element.nextElementSibling;
        const arrow = element.querySelector('.nav-arrow');
        
        if (submenu.style.display === 'none' || submenu.style.display === '') {
            submenu.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            submenu.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
    }
    
    
    // Hamburger menu functionality - Universal para todas las páginas
    (function() {
        function initHamburgerMenu() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (!menuToggle || !sidebar || !sidebarOverlay) {
                console.warn('Menu elements not found:', {
                    menuToggle: !!menuToggle,
                    sidebar: !!sidebar,
                    sidebarOverlay: !!sidebarOverlay
                });
                return;
            }
            
            // Abrir/cerrar sidebar
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                menuToggle.classList.toggle('active');
                
                console.log('Menu toggle clicked. Sidebar active:', sidebar.classList.contains('active'));
            });
            
            // Cerrar al hacer click en overlay
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                menuToggle.classList.remove('active');
            });
            
            // Cerrar al hacer click en un link del menú (móvil)
            const navLinks = sidebar.querySelectorAll('.nav-item');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        menuToggle.classList.remove('active');
                    }
                });
            });
            
            // Cerrar sidebar al redimensionar a desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    menuToggle.classList.remove('active');
                }
            });
            
            console.log('Hamburger menu initialized successfully');
        }
        
        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHamburgerMenu);
        } else {
            // DOM ya está listo
            initHamburgerMenu();
        }
    })();
    </script>
</aside>

