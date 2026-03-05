<?php
/**
 * SIDEBAR NAVIGATION - SIGED Enterprise
 * Sistema de Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/icons.php';
?>

<?php $css_v = filemtime(__DIR__ . '/../../publico/css/estilos.css'); ?>
<link rel="stylesheet" href="<?= APP_URL ?>/publico/css/sidebar-fix.css?v=<?= $css_v ?>">

<style>
/* ===== NAV BADGE — Solicitudes Pendientes ===== */
.nav-badge {
    display: none;                           /* oculto por defecto */
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    border-radius: 10px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    line-height: 20px;
    text-align: center;
    margin-left: auto;
    box-shadow: 0 2px 8px rgba(220,38,38,.45);
    animation: badge-pulse 2s ease-in-out infinite;
    letter-spacing: 0;
}
.nav-badge.visible {
    display: inline-block;
}
@keyframes badge-pulse {
    0%, 100% { box-shadow: 0 2px 8px rgba(220,38,38,.45); transform: scale(1); }
    50%       { box-shadow: 0 2px 14px rgba(220,38,38,.75); transform: scale(1.12); }
}
/* Asegurar que el nav-item tenga flex para alinear el badge al final */
.nav-item { display: flex; align-items: center; }
</style>


<button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
    <?= Icon::get('menu') ?>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="<?= APP_URL ?>/publico/imagenes/logotipo(B).png"
                 alt="<?= APP_NAME ?>"
                 class="logo-image"
                 onerror="this.src='<?= APP_URL ?>/publico/imagenes/logo-telematica-letras-negras.png'">
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php
        // Detectar página y directorio actual para marcar item activo
        $current_page = basename($_SERVER['PHP_SELF']);
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        
        /**
         * Configuración del menú por secciones
         */
        /**
         * Configuración de menú dinámica según nivel de acceso
         * Nivel 3 (Empleado Base): Portal de Autogestión "MI ESPACIO"
         * Nivel 1-2 (Admin/RRHH):  Menú de gestión global completo
         */
        $nivel_usuario = $_SESSION['nivel_acceso'] ?? 3;

        if ($nivel_usuario == 3) {
            // ===== MENÚ EXCLUSIVO NIVEL 3: MI ESPACIO =====
            $funcionario_id_sesion = $_SESSION['funcionario_id'] ?? 0;
            $menuConfig = [
                [
                    'section' => 'MI ESPACIO',
                    'nivel_requerido' => 3,
                    'items' => [
                        [
                            'label' => 'Inicio',
                            'url'   => '/vistas/dashboard/index.php',
                            'icon'  => 'home',
                            'dir'   => 'dashboard'
                        ],
                        [
                            'label' => 'Mi Expediente',
                            'url'   => '/vistas/funcionarios/ver.php?id=' . $funcionario_id_sesion,
                            'icon'  => 'user',
                            'dir'   => 'funcionarios'
                        ],
                        [
                            'label'   => 'Mis Documentos',
                            'icon'    => 'folder',
                            'dir'     => 'reportes',
                            'submenu' => [
                                [
                                    'label' => 'Constancia de Trabajo',
                                    'url'   => '/vistas/reportes/constancia_trabajo.php',
                                    'dir'   => 'reportes'
                                ]
                                // 'Recibos de Pago' eliminado — no disponible para Nivel 3
                            ]
                        ],
                        [
                            'label' => 'Mis Solicitudes',
                            'url'   => '/vistas/solicitudes/mis_solicitudes.php',
                            'icon'  => 'send',
                            'dir'   => 'solicitudes'
                        ]
                    ]
                ]
            ];
        } else {
            // ===== MENÚ NIVEL 1 Y 2: GESTIÓN GLOBAL =====
            $menuConfig = [
                // ===== SECCIÓN: PRINCIPAL =====
                [
                    'section' => 'PRINCIPAL',
                    'nivel_requerido' => 3,
                    'items' => [
                        [
                            'label' => 'Inicio',
                            'url'   => '/vistas/dashboard/index.php',
                            'icon'  => 'home',
                            'dir'   => 'dashboard'
                        ],
                        [
                            'label' => 'Funcionarios',
                            'url'   => '/vistas/funcionarios/index.php',
                            'icon'  => 'users',
                            'dir'   => 'funcionarios'
                        ],
                        [
                            'label' => 'Expedientes',
                            'url'   => '/vistas/expedientes/index.php',
                            'icon'  => 'folder',
                            'dir'   => 'expedientes'
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
                            'url'   => '/vistas/nombramientos/index.php',
                            'icon'  => 'file-text',
                            'dir'   => 'nombramientos'
                        ],
                        [
                            'label' => 'Vacaciones',
                            'url'   => '/vistas/vacaciones/index.php',
                            'icon'  => 'sun',
                            'dir'   => 'vacaciones'
                        ],
                        [
                            'label' => 'Traslados',
                            'url'   => '/vistas/traslados/index.php',
                            'icon'  => 'repeat',
                            'dir'   => 'traslados'
                        ],
                        [
                            'label' => 'Amonestaciones',
                            'url'   => '/vistas/amonestaciones/index.php',
                            'icon'  => 'alert-triangle',
                            'dir'   => 'amonestaciones'
                        ],
                        [
                            'label' => 'Bandeja de Solicitudes',
                            'url'   => '/vistas/solicitudes/gestionar_solicitudes.php',
                            'icon'  => 'inbox',
                            'dir'   => 'solicitudes',
                            'badge' => 'solicitudes'   // <- activa el badge dinámico
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
                            'url'   => '/vistas/reportes/index.php',
                            'icon'  => 'bar-chart',
                            'dir'   => 'reportes'
                        ],
                        [
                            'label'      => 'Administración',
                            'url'        => '/vistas/admin/index.php',
                            'icon'       => 'settings',
                            'dir'        => 'admin',
                            'nivel_item' => 1
                        ],
                        [
                            'label'      => 'Respaldos',
                            'url'        => '/vistas/respaldo/index.php',
                            'icon'       => 'database',
                            'dir'        => 'respaldo',
                            'nivel_item' => 1
                        ]
                    ]
                ]
            ];
        }
        
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
                        <?php $isActive = isset($item['dir']) && $current_dir == $item['dir']; ?>
                        <a href="<?= APP_URL . $item['url'] ?>" 
                           class="nav-item <?= $isActive ? 'active' : '' ?>">
                            <span class="nav-icon">
                                <?= Icon::get($item['icon']) ?>
                            </span>
                            <span class="nav-text"><?= $item['label'] ?></span>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="nav-badge" id="badge-<?= $item['badge'] ?>"></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

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
    
    console.log('[SIGED] Sidebar inicializado correctamente.');
})();

/**
 * ===== BADGE DINÁMICO: Solicitudes Pendientes =====
 * Consulta la API cada 60 segundos y actualiza el badge de forma silenciosa.
 */
(function initBadgeSolicitudes() {
    const badge = document.getElementById('badge-solicitudes');
    if (!badge) return;   // no visible para nivel 3

    const API_URL = (typeof APP_URL !== 'undefined' ? APP_URL : '') + '/api/contar_solicitudes.php';

    function actualizarBadge() {
        fetch(API_URL, { credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || typeof data.count === 'undefined') return;
                const n = data.count;
                if (n > 0) {
                    badge.textContent = n > 99 ? '99+' : n;
                    badge.classList.add('visible');
                } else {
                    badge.textContent = '';
                    badge.classList.remove('visible');
                }
            })
            .catch(() => {/* fallo silencioso, no romper la UI */});
    }

    // Ejecutar al cargar y luego cada 60 s
    actualizarBadge();
    setInterval(actualizarBadge, 60_000);
})();

</script>