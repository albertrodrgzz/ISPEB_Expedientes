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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="logo-text">
                <div class="logo-title">ISPEB</div>
                <div class="logo-subtitle">DIR. TELEMÁTICA</div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">MENÚ PRINCIPAL</div>
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $current_dir = basename(dirname($_SERVER['PHP_SELF']));
            ?>
            <a href="<?php echo APP_URL; ?>/vistas/dashboard/index.php" class="nav-item <?php echo $current_dir == 'dashboard' ? 'active' : ''; ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/funcionarios/index.php" class="nav-item <?php echo $current_dir == 'funcionarios' ? 'active' : ''; ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </span>
                <span class="nav-text">Funcionarios</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/expedientes/index.php" class="nav-item <?php echo $current_dir == 'expedientes' ? 'active' : ''; ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="nav-text">Expedientes</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">GESTIÓN ADMINISTRATIVA</div>
            <a href="<?php echo APP_URL; ?>/vistas/nombramientos/index.php" class="nav-item">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </span>
                <span class="nav-text">Nombramientos</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/vacaciones/index.php" class="nav-item <?php echo $current_dir == 'vacaciones' ? 'active' : ''; ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </span>
                <span class="nav-text">Control Vacacional</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/traslados/index.php" class="nav-item">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="17 1 21 5 17 9"></polyline>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <polyline points="7 23 3 19 7 15"></polyline>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                </span>
                <span class="nav-text">Traslados</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/amonestaciones/index.php" class="nav-item <?php echo $current_dir == 'amonestaciones' ? 'active' : ''; ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <span class="nav-text">Amonestaciones</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/remociones/index.php" class="nav-item">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </span>
                <span class="nav-text">Remociones</span>
            </a>
            
            <!-- Menú Desplegable: Salidas -->
            <div class="nav-item-accordion">
                <a href="javascript:void(0)" class="nav-item" onclick="toggleSubmenu(this)">
                    <span class="nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </span>
                    <span class="nav-text">Salidas</span>
                    <span class="nav-arrow" style="margin-left: auto; transition: transform 0.3s ease;">▼</span>
                </a>
                <div class="nav-submenu" style="display: none; padding-left: 20px;">
                    <a href="<?php echo APP_URL; ?>/vistas/despidos/index.php" class="nav-item nav-subitem">
                        <span class="nav-text">Despidos</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/vistas/renuncias/index.php" class="nav-item nav-subitem">
                        <span class="nav-text">Renuncias</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/vistas/renuncias_aprobadas/index.php" class="nav-item nav-subitem">
                        <span class="nav-text">Renuncias Aprobadas</span>
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (verificarNivel(1)): ?>
        <div class="nav-section">
            <div class="nav-section-title">ADMINISTRACIÓN</div>
            <a href="<?php echo APP_URL; ?>/vistas/admin/index.php" class="nav-item">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                    </svg>
                </span>
                <span class="nav-text">Panel Admin</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/admin/respaldo.php" class="nav-item">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </span>
                <span class="nav-text">Respaldo BD</span>
            </a>
            <a href="<?php echo APP_URL; ?>/vistas/admin/auditoria.php" class="nav-item">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </span>
                <span class="nav-text">Auditoría</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>
    
    <script>
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
    </script>
</aside>

