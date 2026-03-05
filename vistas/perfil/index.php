<?php
/**
 * Perfil del Usuario – Diseño Moderno Glassmorphism
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

$db = getDB();

$stmt = $db->prepare("
    SELECT 
        u.id as usuario_id,
        u.username,
        u.estado as estado_usuario,
        u.ultimo_acceso,
        u.created_at as fecha_creacion_usuario,
        f.id as funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        f.fecha_nacimiento,
        f.genero,
        f.telefono,
        f.email,
        f.direccion,
        f.nivel_educativo,
        f.titulo_obtenido,
        f.fecha_ingreso,
        f.foto,
        f.estado as estado_funcionario,
        c.nombre_cargo,
        c.nivel_acceso,
        d.nombre as departamento
    FROM usuarios u
    INNER JOIN funcionarios f ON u.funcionario_id = f.id
    LEFT JOIN cargos c ON f.cargo_id = c.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ' . APP_URL . '/config/logout.php');
    exit;
}

$fecha_ingreso  = new DateTime($usuario['fecha_ingreso']);
$hoy            = new DateTime();
$diff           = $hoy->diff($fecha_ingreso);
$anos_servicio  = $diff->y;
$meses_servicio = $diff->m;
$dias_servicio  = $diff->d;

$iniciales = strtoupper(substr($usuario['nombres'],0,1) . substr($usuario['apellidos'],0,1));

$nivel_colors = [
    1 => ['bg'=>'#dc2626','label'=>'Administrador'],
    2 => ['bg'=>'#7c3aed','label'=>'RRHH'],
    3 => ['bg'=>'#0F4C81','label'=>'Empleado'],
];
$nivel = $usuario['nivel_acceso'] ?? 3;
$nivel_color = $nivel_colors[$nivel] ?? $nivel_colors[3];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil &ndash; <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
    :root {
        --sidebar-blue: #0F4C81;
        --sidebar-mid:  #1565A0;
        --sidebar-light:#0288D1;
        --grad-sidebar: linear-gradient(160deg, #0F4C81 0%, #1565A0 50%, #0288D1 100%);
        --glass: rgba(255,255,255,0.92);
        --glass-border: rgba(255,255,255,0.70);
        --shadow-card: 0 4px 24px rgba(15,76,129,.10);
        --radius: 16px;
    }

    /* ── Hero ─────────────────────────────────────────────────────── */
    .profile-hero {
        background: var(--grad-sidebar);
        border-radius: var(--radius);
        padding: 36px 40px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 16px 48px rgba(15,76,129,.28);
        animation: heroIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
    }

    @keyframes heroIn {
        from { opacity:0; transform:translateY(-16px); }
        to   { opacity:1; transform:translateY(0);     }
    }

    .profile-hero::before {
        content:'';
        position:absolute;inset:0;
        background:
            radial-gradient(circle at 15% 40%, rgba(255,255,255,.12) 0%, transparent 50%),
            radial-gradient(circle at 85% 60%, rgba(255,255,255,.08) 0%, transparent 45%);
        z-index:0;
    }

    .profile-hero::after {
        content:'';
        position:absolute;
        width:280px;height:280px;
        border:1.5px solid rgba(255,255,255,.12);
        border-radius:50%;
        top:-120px;right:-80px;
        box-shadow: 0 0 0 55px rgba(255,255,255,.04);
        animation: spinRing 24s linear infinite;
    }

    @keyframes spinRing {
        from { transform:rotate(0deg); }
        to   { transform:rotate(360deg); }
    }

    .hero-inner {
        position:relative;z-index:1;
        display:flex;align-items:center;gap:28px;flex-wrap:wrap;
    }

    /* Avatar */
    .profile-avatar {
        width:90px;height:90px;border-radius:50%;
        background:rgba(255,255,255,0.22);
        color:#fff;
        display:flex;align-items:center;justify-content:center;
        font-weight:800;font-size:34px;
        box-shadow:0 6px 24px rgba(0,0,0,.20),
                   0 0 0 3px rgba(255,255,255,.30);
        flex-shrink:0;
        backdrop-filter:blur(8px);
    }

    .hero-info { flex:1;color:#fff;min-width:0; }

    .hero-info h1 {
        margin:0 0 5px;font-size:clamp(18px,3vw,26px);font-weight:800;
        text-shadow:0 2px 6px rgba(0,0,0,.2);
    }

    .hero-info .hero-role {
        font-size:14px;margin:0 0 12px;opacity:0.90;font-weight:500;
    }

    .hero-meta {
        display:flex;flex-wrap:wrap;gap:10px;font-size:13px;
    }

    .hero-meta-item {
        display:flex;align-items:center;gap:6px;
        background:rgba(255,255,255,.14);
        backdrop-filter:blur(8px);
        padding:5px 12px;border-radius:20px;
        border:1px solid rgba(255,255,255,.20);
        color:#fff;
        transition:background .2s;
    }

    .hero-meta-item svg {
        width:14px;height:14px;stroke:#fff;fill:none;flex-shrink:0;
    }

    .hero-meta-item:hover { background:rgba(255,255,255,.22); }

    .hero-stats {
        display:flex;gap:10px;flex-wrap:wrap;
    }

    .hero-stat {
        background:rgba(255,255,255,.16);
        backdrop-filter:blur(10px);
        border:1px solid rgba(255,255,255,.22);
        border-radius:12px;
        padding:12px 18px;
        text-align:center;
        color:#fff;
        min-width:80px;
        transition:transform .2s, background .2s;
    }

    .hero-stat:hover {
        transform:translateY(-2px);
        background:rgba(255,255,255,.24);
    }

    .hero-stat-num {
        font-size:26px;font-weight:800;line-height:1;
    }

    .hero-stat-label {
        font-size:11px;margin-top:3px;opacity:0.82;font-weight:500;
    }

    /* ── Grid ──────────────────────────────────────────────────────── */
    .profile-grid {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:20px;
        margin-bottom:20px;
    }

    .glass-card {
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:var(--radius);
        box-shadow:var(--shadow-card);
        overflow:hidden;
        transition:transform .22s, box-shadow .22s;
        animation:cardIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
    }

    .glass-card:nth-child(1){ animation-delay:.04s; }
    .glass-card:nth-child(2){ animation-delay:.08s; }
    .glass-card:nth-child(3){ animation-delay:.12s; }

    @keyframes cardIn {
        from { opacity:0; transform:translateY(14px); }
        to   { opacity:1; transform:translateY(0);    }
    }

    .glass-card:hover {
        transform:translateY(-3px);
        box-shadow:0 12px 36px rgba(15,76,129,.13);
    }

    .glass-card-header {
        padding:16px 20px 13px;
        border-bottom:1px solid #f1f5f9;
        display:flex;align-items:center;gap:12px;
    }

    .card-icon {
        width:40px;height:40px;border-radius:10px;
        display:flex;align-items:center;justify-content:center;
        flex-shrink:0;box-shadow:0 3px 10px rgba(0,0,0,.10);
    }

    .card-icon svg {
        width:20px;height:20px;stroke:#fff;fill:none;stroke-width:2;
    }

    .card-title {
        font-size:14.5px;font-weight:700;color:#1e293b;
    }

    .glass-card-body { padding:18px 20px; }

    .info-row {
        display:flex;flex-direction:column;gap:3px;
        padding:10px 0;
        border-bottom:1px solid #f1f5f9;
    }

    .info-row:last-child { border-bottom:none;padding-bottom:0; }
    .info-row:first-child { padding-top:0; }

    .info-label {
        font-size:11px;font-weight:700;color:#94a3b8;
        text-transform:uppercase;letter-spacing:.5px;
    }

    .info-value {
        font-size:14px;color:#1e293b;font-weight:500;line-height:1.4;
    }

    /* Badges */
    .badge-nivel {
        display:inline-flex;align-items:center;gap:6px;
        padding:4px 12px;border-radius:16px;
        font-size:12px;font-weight:700;color:#fff;
        box-shadow:0 2px 8px rgba(0,0,0,.15);
    }

    .badge-nivel svg { width:12px;height:12px;stroke:#fff;fill:none; }

    .badge-estado {
        display:inline-flex;align-items:center;gap:5px;
        padding:4px 11px;border-radius:16px;
        font-size:12px;font-weight:600;
    }

    .badge-activo   { background:#dcfce7;color:#15803d; }
    .badge-inactivo { background:#fee2e2;color:#b91c1c; }

    .username-badge {
        font-family:'Courier New',monospace;
        background:#eff6ff;
        border:1.5px solid #bfdbfe;
        color:#0F4C81;
        padding:6px 12px;border-radius:8px;
        font-size:14px;font-weight:700;
        letter-spacing:.4px;
        display:inline-block;
    }

    /* ── Change Password btn ────────────────────────────────────────── */
    .change-pass-btn {
        width:100%;padding:12px;
        background:var(--grad-sidebar);
        color:#fff;border:none;border-radius:10px;
        font-size:14px;font-weight:700;cursor:pointer;
        display:flex;align-items:center;justify-content:center;gap:8px;
        transition:all .22s;
        box-shadow:0 4px 12px rgba(15,76,129,.28);
        margin-top:18px;
    }

    .change-pass-btn svg { width:16px;height:16px;stroke:#fff;fill:none;stroke-width:2; }

    .change-pass-btn:hover {
        transform:translateY(-2px);
        box-shadow:0 8px 20px rgba(15,76,129,.40);
    }

    /* ── Actions Card ───────────────────────────────────────────────── */
    .actions-card {
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:var(--radius);
        box-shadow:var(--shadow-card);
        padding:22px 24px;
        margin-bottom:20px;
        animation:cardIn .4s cubic-bezier(0.16,1,0.3,1) .16s both;
    }

    .actions-header {
        font-size:15px;font-weight:700;color:#1e293b;
        margin-bottom:16px;display:flex;align-items:center;gap:10px;
    }

    .actions-header svg { width:18px;height:18px;stroke:#0F4C81;fill:none;stroke-width:2; }

    .quick-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
        gap:12px;
    }

    .quick-btn {
        display:flex;align-items:center;gap:12px;
        padding:16px 18px;
        background:#f8faff;
        border:1px solid #e2e8f0;
        border-radius:12px;
        text-decoration:none;
        cursor:pointer;
        transition:all .20s;
    }

    .quick-btn:hover {
        background:#eff6ff;
        border-color:rgba(15,76,129,.25);
        transform:translateY(-2px);
        box-shadow:0 6px 20px rgba(15,76,129,.10);
    }

    .quick-icon {
        width:44px;height:44px;border-radius:10px;
        display:flex;align-items:center;justify-content:center;
        flex-shrink:0;
    }

    .quick-icon svg { width:22px;height:22px;stroke:#fff;fill:none;stroke-width:2; }

    .quick-title {
        font-size:13.5px;font-weight:700;color:#1e293b;margin-bottom:1px;
    }

    .quick-desc { font-size:12px;color:#64748b; }

    /* ── Responsive ─────────────────────────────────────────────────── */
    @media (max-width: 1100px) {
        .profile-grid { grid-template-columns: repeat(2,1fr); }
    }

    @media (max-width: 768px) {
        .profile-hero { padding:24px 18px; }
        .hero-inner   { gap:16px; }
        .profile-avatar { width:70px;height:70px;font-size:26px; }
        .hero-info h1 { font-size:18px; }
        .hero-stats   { gap:8px; }
        .hero-stat    { padding:10px 12px;min-width:68px; }
        .hero-stat-num{ font-size:20px; }
        .profile-grid { grid-template-columns:1fr; }
        .quick-grid   { grid-template-columns:1fr; }
    }

    @media (max-width: 480px) {
        .profile-hero { padding:18px 14px; }
        .hero-meta    { gap:6px; }
        .hero-stat    { min-width:58px;padding:8px 10px; }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>

        <div class="content-wrapper">

            <!-- Hero Banner -->
            <div class="profile-hero">
                <div class="hero-inner">
                    <div class="profile-avatar"><?php echo $iniciales; ?></div>

                    <div class="hero-info">
                        <h1><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></h1>
                        <p class="hero-role"><?php echo htmlspecialchars($usuario['nombre_cargo'] ?? 'Sin cargo asignado'); ?></p>
                        <div class="hero-meta">
                            <?php if ($usuario['email']): ?>
                            <div class="hero-meta-item">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <?php echo htmlspecialchars($usuario['email']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($usuario['telefono']): ?>
                            <div class="hero-meta-item">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <?php echo htmlspecialchars($usuario['telefono']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="hero-meta-item">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <?php echo htmlspecialchars($usuario['departamento'] ?? 'Sin departamento'); ?>
                            </div>
                            <div class="hero-meta-item">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Nivel <?php echo $nivel; ?> &ndash; <?php echo $nivel_color['label']; ?>
                            </div>
                        </div>
                    </div>

                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="hero-stat-num"><?php echo $anos_servicio; ?></div>
                            <div class="hero-stat-label">A&ntilde;os</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-num"><?php echo $meses_servicio; ?></div>
                            <div class="hero-stat-label">Meses</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-num"><?php echo $dias_servicio; ?></div>
                            <div class="hero-stat-label">D&iacute;as</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de tarjetas -->
            <div class="profile-grid">

                <!-- Info Personal -->
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-icon" style="background:var(--grad-sidebar);">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="card-title">Informaci&oacute;n Personal</div>
                    </div>
                    <div class="glass-card-body">
                        <div class="info-row">
                            <div class="info-label">C&eacute;dula de Identidad</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['cedula']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nombres completos</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Fecha de Nacimiento</div>
                            <div class="info-value">
                                <?php if ($usuario['fecha_nacimiento']): ?>
                                    <?php
                                    $fnac = new DateTime($usuario['fecha_nacimiento']);
                                    $edad = $hoy->diff($fnac)->y;
                                    echo date('d/m/Y', strtotime($usuario['fecha_nacimiento']));
                                    echo ' <span style="color:#94a3b8;font-size:12px;">(' . $edad . ' a&ntilde;os)</span>';
                                    ?>
                                <?php else: echo '<span style="color:#cbd5e0;">No registrado</span>'; endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">G&eacute;nero</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['genero'] ?? 'No especificado'); ?></div>
                        </div>
                        <?php if ($usuario['email']): ?>
                        <div class="info-row">
                            <div class="info-label">Correo Electr&oacute;nico</div>
                            <div class="info-value" style="font-size:13px;"><?php echo htmlspecialchars($usuario['email']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($usuario['telefono']): ?>
                        <div class="info-row">
                            <div class="info-label">Tel&eacute;fono</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['telefono']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Laboral -->
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="card-title">Informaci&oacute;n Laboral</div>
                    </div>
                    <div class="glass-card-body">
                        <div class="info-row">
                            <div class="info-label">Cargo</div>
                            <div class="info-value" style="color:#7c3aed;font-weight:700;">
                                <?php echo htmlspecialchars($usuario['nombre_cargo'] ?? 'Sin cargo'); ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Departamento</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['departamento'] ?? 'No asignado'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Fecha de Ingreso</div>
                            <div class="info-value">
                                <?php echo date('d/m/Y', strtotime($usuario['fecha_ingreso'])); ?>
                                <span style="color:#64748b;font-size:12px;margin-left:4px;">
                                    (<?php echo $anos_servicio; ?> a&ntilde;os, <?php echo $meses_servicio; ?> meses)
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nivel Educativo</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['nivel_educativo'] ?? 'No especificado'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">T&iacute;tulo Obtenido</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['titulo_obtenido'] ?? 'No especificado'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Estado Laboral</div>
                            <div class="info-value">
                                <span class="badge-estado <?php echo $usuario['estado_funcionario'] === 'activo' ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <svg width="8" height="8"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg>
                                    <?php echo ucfirst($usuario['estado_funcionario']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cuenta de Usuario -->
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div class="card-title">Cuenta de Usuario</div>
                    </div>
                    <div class="glass-card-body">
                        <div class="info-row">
                            <div class="info-label">Nombre de Usuario</div>
                            <div class="info-value">
                                <span class="username-badge"><?php echo htmlspecialchars($usuario['username']); ?></span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nivel de Acceso</div>
                            <div class="info-value">
                                <span class="badge-nivel" style="background:<?php echo $nivel_color['bg']; ?>;">
                                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Nivel <?php echo $nivel; ?> &ndash; <?php echo $nivel_color['label']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Estado de Cuenta</div>
                            <div class="info-value">
                                <span class="badge-estado <?php echo $usuario['estado_usuario'] === 'activo' ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <svg width="8" height="8"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg>
                                    <?php echo $usuario['estado_usuario'] === 'activo' ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">&Uacute;ltimo Acceso</div>
                            <div class="info-value" style="font-size:13px;">
                                <?php echo $usuario['ultimo_acceso']
                                    ? date('d/m/Y H:i:s', strtotime($usuario['ultimo_acceso']))
                                    : '<span style="color:#94a3b8;">Primer acceso</span>'; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Cuenta Creada</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion_usuario'])); ?></div>
                        </div>

                        <button class="change-pass-btn" onclick="cambiarContrasena()">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Cambiar Contrase&ntilde;a
                        </button>
                    </div>
                </div>

            </div><!-- /.profile-grid -->

            <!-- Acciones Rapidas -->
            <div class="actions-card">
                <div class="actions-header">
                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Acciones R&aacute;pidas
                </div>
                <div class="quick-grid">
                    <a href="<?php echo APP_URL; ?>/vistas/funcionarios/ver.php?id=<?php echo $usuario['funcionario_id']; ?>" class="quick-btn">
                        <div class="quick-icon" style="background:var(--grad-sidebar);">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <div class="quick-title">Ver Mi Expediente</div>
                            <div class="quick-desc">Consulta tu expediente completo</div>
                        </div>
                    </a>
                    <a href="<?php echo APP_URL; ?>/vistas/solicitudes/mis_solicitudes.php" class="quick-btn">
                        <div class="quick-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/></svg>
                        </div>
                        <div>
                            <div class="quick-title">Mis Solicitudes</div>
                            <div class="quick-desc">Vacaciones, permisos y m&aacute;s</div>
                        </div>
                    </a>
                    <a href="<?php echo APP_URL; ?>/vistas/dashboard/" class="quick-btn">
                        <div class="quick-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div>
                            <div class="quick-title">Ir al Dashboard</div>
                            <div class="quick-desc">P&aacute;gina principal del sistema</div>
                        </div>
                    </a>
                </div>
            </div>

        </div><!-- /.content-wrapper -->
    </div><!-- /.main-content -->

    <script src="<?php echo APP_URL; ?>/publico/js/app.js"></script>
    <script>
    function cambiarContrasena() {
        Swal.fire({
            title: '<div style="font-size:17px;font-weight:700;color:#1e293b;">Cambiar Contrase&ntilde;a</div>',
            html: `
                <div style="text-align:left;">
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#475569;">Contrase&ntilde;a Actual</label>
                        <input type="password" id="cp-actual" class="swal2-input"
                               placeholder="Ingrese su contrase&ntilde;a actual" style="width:88%;margin:0;font-size:14px;">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#475569;">Nueva Contrase&ntilde;a</label>
                        <input type="password" id="cp-nueva" class="swal2-input"
                               placeholder="M&iacute;nimo 6 caracteres" style="width:88%;margin:0;font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:#475569;">Confirmar Contrase&ntilde;a</label>
                        <input type="password" id="cp-confirm" class="swal2-input"
                               placeholder="Repita la nueva contrase&ntilde;a" style="width:88%;margin:0;font-size:14px;">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Cambiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0F4C81',
            cancelButtonColor: '#64748b',
            preConfirm: () => {
                const actual   = document.getElementById('cp-actual').value;
                const nueva    = document.getElementById('cp-nueva').value;
                const confirm  = document.getElementById('cp-confirm').value;
                if (!actual || !nueva || !confirm) {
                    Swal.showValidationMessage('Todos los campos son obligatorios');
                    return false;
                }
                if (nueva.length < 6) {
                    Swal.showValidationMessage('La nueva contrase&ntilde;a debe tener al menos 6 caracteres');
                    return false;
                }
                if (nueva !== confirm) {
                    Swal.showValidationMessage('Las contrase&ntilde;as no coinciden');
                    return false;
                }
                return { actual, nueva };
            }
        }).then(res => {
            if (res.isConfirmed) {
                Swal.fire({
                    icon: 'info',
                    title: 'Funcionalidad Pendiente',
                    text: 'El cambio de contrase&ntilde;a se implementar&aacute; pr&oacute;ximamente.',
                    confirmButtonColor: '#0F4C81'
                });
            }
        });
    }
    </script>
</body>
</html>
