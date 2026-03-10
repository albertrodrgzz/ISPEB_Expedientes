<?php
/**
 * BARRA DE NAVEGACIÓN INFERIOR — MÓVIL
 * SIGED Enterprise — Solo visible en pantallas < 1024px
 */
$_bn_nivel   = (int)($_SESSION['nivel_acceso'] ?? 3);
$_bn_cur_dir = basename(dirname($_SERVER['PHP_SELF']));
$_bn_cur_pg  = basename($_SERVER['PHP_SELF']);
$_bn_func_id = $_SESSION['funcionario_id'] ?? 0;

function bn_icon(string $name): string {
    $icons = [
        'home'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'folder'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
        'chart'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
        'send'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'user'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'inbox'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'more'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>',
        'settings'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M4.93 19.07l1.41-1.41M19.07 19.07l-1.41-1.41M12 2v2M12 20v2M2 12H4M20 12h2"/></svg>',
    ];
    return $icons[$name] ?? $icons['home'];
}

if ($_bn_nivel >= 3) {
    $items = [
        ['label' => 'Inicio',      'icon' => 'home',   'url' => '/vistas/dashboard/index.php',                    'dir' => 'dashboard'],
        ['label' => 'Mi Perfil',   'icon' => 'user',   'url' => "/vistas/funcionarios/ver.php?id={$_bn_func_id}", 'dir' => 'funcionarios'],
        ['label' => 'Docs',        'icon' => 'folder', 'url' => '/vistas/reportes/constancia_trabajo.php',        'dir' => 'reportes'],
        ['label' => 'Solicitudes', 'icon' => 'send',   'url' => '/vistas/solicitudes/mis_solicitudes.php',        'dir' => 'solicitudes'],
    ];
} else {
    $items = [
        ['label' => 'Inicio',      'icon' => 'home',  'url' => '/vistas/dashboard/index.php',                   'dir' => 'dashboard'],
        ['label' => 'Personal',    'icon' => 'users', 'url' => '/vistas/funcionarios/index.php',                'dir' => 'funcionarios'],
        ['label' => 'Reportes',    'icon' => 'chart', 'url' => '/vistas/reportes/index.php',                    'dir' => 'reportes'],
        ['label' => 'Bandeja',     'icon' => 'inbox', 'url' => '/vistas/solicitudes/gestionar_solicitudes.php', 'dir' => 'solicitudes', 'badge' => true],
        ['label' => 'Más',         'icon' => 'more',  'url' => '#',                                            'dir' => '__more__', 'more' => true],
    ];
}
?>

<nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Navegación principal">
    <?php foreach ($items as $item):
        $isActive = isset($item['dir']) && $item['dir'] === $_bn_cur_dir && !isset($item['more']);
        $isMore   = !empty($item['more']);
        $url      = $isMore ? 'javascript:void(0)' : APP_URL . $item['url'];
    ?>
        <a href="<?= $url ?>"
           class="bn-item <?= $isActive ? 'active' : '' ?>"
           <?= $isMore ? 'onclick="toggleMobileMore(this)" aria-expanded="false"' : '' ?>
           aria-label="<?= htmlspecialchars($item['label']) ?>">
            <span class="bn-icon-wrap">
                <?= bn_icon($item['icon']) ?>
                <?php if (!empty($item['badge'])): ?>
                    <span class="bn-badge" id="mobile-badge-solicitudes"></span>
                <?php endif; ?>
            </span>
            <span class="bn-label"><?= htmlspecialchars($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($_bn_nivel < 3): ?>
<!-- Panel "Más" — Bottom Sheet -->
<div class="mobile-more-panel" id="mobileMorePanel">
    <div class="mobile-more-overlay" id="mobileMoreOverlay" onclick="closeMobileMore()"></div>
    <div class="mobile-more-sheet">
        <div class="mobile-more-handle"></div>
        <div class="mobile-more-grid">
            <a href="<?= APP_URL ?>/vistas/expedientes/index.php"    class="mobile-more-item <?= $_bn_cur_dir==='expedientes'?'active':'' ?>">
                <?= bn_icon('folder') ?><span>Expedientes</span>
            </a>
            <a href="<?= APP_URL ?>/vistas/vacaciones/index.php"     class="mobile-more-item <?= $_bn_cur_dir==='vacaciones'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <span>Vacaciones</span>
            </a>
            <a href="<?= APP_URL ?>/vistas/nombramientos/index.php"  class="mobile-more-item <?= $_bn_cur_dir==='nombramientos'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>Nombramientos</span>
            </a>
            <a href="<?= APP_URL ?>/vistas/traslados/index.php"      class="mobile-more-item <?= $_bn_cur_dir==='traslados'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                <span>Traslados</span>
            </a>
            <a href="<?= APP_URL ?>/vistas/amonestaciones/index.php" class="mobile-more-item <?= $_bn_cur_dir==='amonestaciones'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Amonestaciones</span>
            </a>
            <?php if ($_bn_nivel <= 1): ?>
            <a href="<?= APP_URL ?>/vistas/admin/index.php"          class="mobile-more-item <?= $_bn_cur_dir==='admin'?'active':'' ?>">
                <?= bn_icon('settings') ?><span>Admin</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ══════════════════════════════════════════════════
   BOTTOM NAV — Estilos base (complementan mobile-first.css)
   ══════════════════════════════════════════════════ */

/* Sobreescribir los estilos genéricos de mobile-first.css
   para el nuevo marcado .bn-item / .bn-icon-wrap / .bn-label */
.mobile-bottom-nav {
    padding: 0 !important;
    gap: 0 !important;
}

.bn-item {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 3px !important;
    text-decoration: none !important;
    color: var(--mf-text-2, #64748B) !important;
    font-size: 9px !important;
    font-weight: 600 !important;
    padding: 7px 4px 5px !important;
    transition: color .18s ease, background .18s ease !important;
    text-transform: uppercase !important;
    letter-spacing: .5px !important;
    position: relative !important;
    border: none !important;
    background: transparent !important;
    -webkit-tap-highlight-color: transparent !important;
    cursor: pointer !important;
}

.bn-item:active { background: rgba(15,76,129,.05) !important; }

/* Barra indicadora activa (top border) */
.bn-item.active {
    color: var(--mf-blue, #0F4C81) !important;
}
.bn-item.active::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 12% !important;
    right: 12% !important;
    height: 2.5px !important;
    background: var(--mf-blue, #0F4C81) !important;
    border-radius: 0 0 3px 3px !important;
}

/* Contenedor del ícono con posición relativa para el badge */
.bn-icon-wrap {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 26px !important;
    height: 26px !important;
}
.bn-icon-wrap svg {
    width: 22px !important;
    height: 22px !important;
    stroke: currentColor !important;
    stroke-width: 2 !important;
    fill: none !important;
    display: block !important;
}
.bn-item.active .bn-icon-wrap svg {
    stroke: var(--mf-blue, #0F4C81) !important;
}

.bn-label {
    font-size: 9px !important;
    line-height: 1 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    max-width: 100% !important;
}

/* Badge sobre el ícono — oculto por defecto */
.bn-badge {
    display: none; /* JS lo muestra/oculta */
    position: absolute !important;
    top: -3px !important;
    right: -5px !important;
    min-width: 14px !important;
    height: 14px !important;
    padding: 0 3px !important;
    background: #EF4444 !important;
    color: #fff !important;
    font-size: 8.5px !important;
    font-weight: 800 !important;
    border-radius: 7px !important;
    line-height: 14px !important;
    text-align: center !important;
    border: 1.5px solid #fff !important;
    pointer-events: none !important;
    animation: badge-pulse 2.2s ease-in-out infinite !important;
}
.bn-badge.visible {
    display: inline-block !important;
}
@keyframes badge-pulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.15); }
}

/* ── PANEL "MÁS" — BOTTOM SHEET ───────────────────── */
.mobile-more-panel {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 900;
}
.mobile-more-panel.open { display: block; }

.mobile-more-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
}

.mobile-more-sheet {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px 20px 0 0;
    padding: 12px 16px 20px;
    padding-bottom: calc(20px + env(safe-area-inset-bottom, 0));
    box-shadow: 0 -8px 32px rgba(0,0,0,.15);
    animation: slide-up-sheet 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes slide-up-sheet {
    from { transform: translateY(100%); }
    to   { transform: translateY(0); }
}

.mobile-more-handle {
    width: 36px; height: 4px;
    background: #E2E8F0; border-radius: 2px;
    margin: 0 auto 18px;
}

.mobile-more-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.mobile-more-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 14px 6px;
    border-radius: 12px;
    text-decoration: none;
    color: #64748b;
    font-size: 10.5px;
    font-weight: 600;
    text-align: center;
    background: #f8fafc;
    transition: all 0.2s ease;
    letter-spacing: .2px;
}
.mobile-more-item svg {
    width: 22px; height: 22px;
    stroke: #64748b;
    stroke-width: 2; fill: none;
    transition: stroke 0.2s ease;
}
.mobile-more-item:active, .mobile-more-item.active {
    background: #E0F2FE; color: #0F4C81;
}
.mobile-more-item.active svg, .mobile-more-item:active svg {
    stroke: #0F4C81;
}

/* Ocultar en desktop */
@media (min-width: 1024px) {
    .mobile-bottom-nav, .mobile-more-panel { display: none !important; }
}
</style>

<script>
/* ── Panel "Más" ──────────────────────────────────────── */
function toggleMobileMore(btn) {
    const panel = document.getElementById('mobileMorePanel');
    if (!panel) return;
    if (panel.classList.contains('open')) {
        closeMobileMore();
    } else {
        panel.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
}
function closeMobileMore() {
    const panel = document.getElementById('mobileMorePanel');
    const btn   = document.querySelector('.bn-item[onclick]');
    if (!panel) return;
    panel.classList.remove('open');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
}

/* ── Badge de solicitudes — llamada directa a la API ──── */
(function initMobileBadge() {
    const badge = document.getElementById('mobile-badge-solicitudes');
    if (!badge) return; // no visible para nivel 3

    const API_URL = (typeof APP_URL !== 'undefined' ? APP_URL : '') + '/api/contar_solicitudes.php';

    function actualizarBadge() {
        fetch(API_URL, { credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data || typeof data.count === 'undefined') return;
                var n = parseInt(data.count, 10);
                if (n > 0) {
                    badge.textContent = n > 99 ? '99+' : String(n);
                    badge.classList.add('visible');
                    // Sincronizar también con el badge del sidebar desktop
                    var desktopBadge = document.getElementById('badge-solicitudes');
                    if (desktopBadge) {
                        desktopBadge.textContent = n > 99 ? '99+' : String(n);
                        desktopBadge.classList.add('visible');
                    }
                } else {
                    badge.textContent = '';
                    badge.classList.remove('visible');
                    var desktopBadge = document.getElementById('badge-solicitudes');
                    if (desktopBadge) {
                        desktopBadge.textContent = '';
                        desktopBadge.classList.remove('visible');
                    }
                }
            })
            .catch(function(err) {
                console.warn('[SIGED] Badge mobile: error al cargar solicitudes', err);
            });
    }

    // Ejecutar al cargar y cada 60 s
    actualizarBadge();
    setInterval(actualizarBadge, 60000);
})();
</script>
