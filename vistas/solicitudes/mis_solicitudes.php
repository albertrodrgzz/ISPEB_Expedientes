<?php
/**
 * Vista: Mis Solicitudes (Portal del Empleado – Nivel 3)
 * Sistema ISPEB - Autogestión de Vacaciones y Permisos
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

// Solo empleados base (Nivel 3)
if ($_SESSION['nivel_acceso'] != 3) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$pdo              = getDB();
$funcionario_id   = $_SESSION['funcionario_id'] ?? 0;
$csrf_token       = generarTokenCSRF();

// ── Estadísticas rápidas ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT
    COUNT(*)                                               AS total,
    SUM(estado = 'pendiente')                              AS pendientes,
    SUM(estado = 'aprobada')                               AS aprobadas,
    SUM(estado = 'rechazada')                              AS rechazadas
  FROM solicitudes_empleados WHERE funcionario_id = ?");
$stmt->execute([$funcionario_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Listado de solicitudes ────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT se.*,
           u.username AS revisado_por_nombre
    FROM   solicitudes_empleados se
    LEFT JOIN usuarios u ON se.revisado_por = u.id
    WHERE  se.funcionario_id = ?
    ORDER  BY se.created_at DESC
");
$stmt->execute([$funcionario_id]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <!-- SweetAlert2 en <head> para que esté disponible antes de cualquier click -->
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        /* ── Solicitudes Grid ──────────────────────────────── */
        .solicitudes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        .solicitud-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 22px 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .solicitud-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.13);
        }
        .solicitud-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            border-radius: 18px 18px 0 0;
        }
        .card-pendiente::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .card-aprobada::before   { background: linear-gradient(90deg, #10b981, #34d399); }
        .card-rechazada::before  { background: linear-gradient(90deg, #ef4444, #f87171); }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }
        .card-tipo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 15px;
            color: #1e293b;
        }
        .card-tipo-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .tipo-vacaciones .card-tipo-icon { background: #dbeafe; color: #2563eb; }
        .tipo-permiso    .card-tipo-icon { background: #fce7f3; color: #db2777; }

        .badge-estado {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-pendiente  { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-aprobada   { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-rechazada  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .card-fechas {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            margin-bottom: 10px;
        }
        .card-fechas svg { flex-shrink: 0; color: #94a3b8; }
        .card-motivo {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-footer {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-fecha-envio {
            font-size: 11px;
            color: #94a3b8;
        }
        .card-observacion {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12.5px;
            line-height: 1.5;
        }
        .card-observacion.aprobada  { background: #ecfdf5; border-left: 3px solid #10b981; color: #065f46; }
        .card-observacion.rechazada { background: #fef2f2; border-left: 3px solid #ef4444; color: #991b1b; }

        .btn-ver-aval {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-ver-aval:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.35);
        }
        .empty-solicitudes {
            text-align: center;
            padding: 80px 20px;
            color: #94a3b8;
        }
        .empty-solicitudes-icon {
            width: 80px; height: 80px;
            margin: 0 auto 16px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0.5;
        }
        /* ── KPI ──────────────────────────────────────────── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
        @media(max-width:768px){ .kpi-grid{ grid-template-columns:repeat(2,1fr); } .solicitudes-grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php 
        $pageTitle = 'Mis Solicitudes';
        
        include __DIR__ . '/../layout/header.php'; 
        ?>

        <div class="module-container">
            <!-- Header Título y Botón -->
            <div class="module-header-title">
                <div class="module-title-group">
                    <?= Icon::get('inbox') ?>
                    <h2 class="module-title-text">Mis Solicitudes</h2>
                </div>
                <button class="btn-primary" onclick="abrirModalNuevaSolicitud()" style="padding: 10px 20px; border-radius: 8px;">
                    <?= Icon::get('plus') ?> Nueva Solicitud
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card-solid bg-solid-blue">
                    <div class="kpi-icon"><?= Icon::get('send') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Total enviadas</div>
                        <div class="kpi-value"><?= $stats['total'] ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-orange">
                    <div class="kpi-icon"><?= Icon::get('clock') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Pendientes</div>
                        <div class="kpi-value"><?= $stats['pendientes'] ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-green">
                    <div class="kpi-icon"><?= Icon::get('check-circle') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Aprobadas</div>
                        <div class="kpi-value"><?= $stats['aprobadas'] ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-red">
                    <div class="kpi-icon"><?= Icon::get('x-circle') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Rechazadas</div>
                        <div class="kpi-value"><?= $stats['rechazadas'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Solicitudes Grid -->
            <?php if (empty($solicitudes)): ?>
                <div class="card-modern">
                    <div class="empty-solicitudes">
                        <div class="empty-solicitudes-icon">
                            <?= Icon::get('send', 'width:36px;height:36px;') ?>
                        </div>
                        <h3 style="font-size:18px;color:#1e293b;margin-bottom:8px;">Sin solicitudes aún</h3>
                        <p style="font-size:14px;margin-bottom:20px;">Haz click en "Nueva Solicitud" para enviar tu primera solicitud al departamento de RRHH.</p>
                        <button class="btn-primary" onclick="abrirModalNuevaSolicitud()">
                            <?= Icon::get('plus') ?> Nueva Solicitud
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="solicitudes-grid">
                    <?php foreach ($solicitudes as $sol):
                        $es_vac    = $sol['tipo_solicitud'] === 'vacaciones';
                        $tipo_css  = $es_vac ? 'tipo-vacaciones' : 'tipo-permiso';
                        $estado    = $sol['estado'];
                    ?>
                    <div class="solicitud-card card-<?= $estado ?> <?= $tipo_css ?>">
                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="card-tipo">
                                <div class="card-tipo-icon">
                                    <?= $es_vac ? Icon::get('sun') : Icon::get('clock') ?>
                                </div>
                                <?= $es_vac ? 'Vacaciones' : 'Permiso' ?>
                            </div>
                            <span class="badge-estado badge-<?= $estado ?>">
                                <?php
                                $dot = match($estado) {
                                    'pendiente'  => '<svg width="8" height="8"><circle cx="4" cy="4" r="4" fill="#f59e0b"/></svg>',
                                    'aprobada'   => '<svg width="8" height="8"><circle cx="4" cy="4" r="4" fill="#10b981"/></svg>',
                                    'rechazada'  => '<svg width="8" height="8"><circle cx="4" cy="4" r="4" fill="#ef4444"/></svg>',
                                    default      => '<svg width="8" height="8"><circle cx="4" cy="4" r="4" fill="#94a3b8"/></svg>'
                                };
                                echo $dot . ' ' . ucfirst($estado);
                                ?>
                            </span>
                        </div>

                        <!-- Fechas -->
                        <div class="card-fechas">
                            <?= Icon::get('calendar', 'width:14px;height:14px;') ?>
                            <strong><?= date('d/m/Y', strtotime($sol['fecha_inicio'])) ?></strong>
                            <span style="color:#94a3b8;">→</span>
                            <strong><?= date('d/m/Y', strtotime($sol['fecha_fin'])) ?></strong>
                        </div>

                        <!-- Motivo -->
                        <div class="card-motivo">
                            <?= htmlspecialchars($sol['motivo']) ?>
                        </div>

                        <!-- Observación del revisor -->
                        <?php if ($sol['observaciones_respuesta'] && $estado !== 'pendiente'): ?>
                            <div class="card-observacion <?= $estado ?>">
                                <strong style="display:block;margin-bottom:3px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">
                                    Respuesta de RRHH:
                                </strong>
                                <?= htmlspecialchars($sol['observaciones_respuesta']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Footer -->
                        <div class="card-footer">
                            <span class="card-fecha-envio">
                                Enviada el <?= date('d/m/Y H:i', strtotime($sol['created_at'])) ?>
                            </span>
                            <?php if ($estado === 'aprobada' && $sol['ruta_archivo_aprobacion']): ?>
                                <a href="<?= APP_URL . '/' . htmlspecialchars($sol['ruta_archivo_aprobacion']) ?>"
                                   target="_blank"
                                   class="btn-ver-aval">
                                    <?= Icon::get('file-text', 'width:14px;height:14px;') ?>
                                    Ver Aval
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div><!-- /.module-container -->
    </div><!-- /.main-content -->

    <script>
    // APP_URL ya está declarado por header.php (var APP_URL con guard).
    // Usar 'var' con comprobación para evitar SyntaxError por redeclaración de 'const'.
    if (typeof CSRF_TOKEN === 'undefined') {
        var CSRF_TOKEN = '<?= $csrf_token ?>';
    }

    /* =====================================================================
       UTILIDADES
    ===================================================================== */
    function diffDias(inicio, fin) {
        if (!inicio || !fin) return 0;
        // Prevenir corrimiento de día por zona horaria agregando hora local
        const startDate = new Date(inicio + 'T12:00:00');
        const endDate = new Date(fin + 'T12:00:00');
        if (endDate < startDate) return 0;
        let count = 0;
        let current = new Date(startDate);
        while (current <= endDate) {
            const dayOfWeek = current.getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                count++;
            }
            current.setDate(current.getDate() + 1);
        }
        return count;
    }

    function formatFecha(dateStr) {
        if (!dateStr) return '-';
        const [y, m, d] = dateStr.split('-');
        return d + '/' + m + '/' + y;
    }

    /* =====================================================================
       DATOS DE VACACIONES (cache en memoria)
    ===================================================================== */
    let _vacInfo = null;

    async function cargarInfoVacaciones() {
        if (_vacInfo) return _vacInfo;
        try {
            const res  = await fetch(APP_URL + '/vistas/solicitudes/ajax/info_vacaciones.php');
            const data = await res.json();
            if (data.success) { _vacInfo = data; return data; }
        } catch(e) {}
        return null;
    }

    /* =====================================================================
       BANNER DE SALDO VACACIONAL — Sistema de períodos LOTTT
    ===================================================================== */
    function renderBannerVac(info) {
        // Si no tiene derecho a vacaciones
        if (!info.tiene_derecho) {
            return '<div style="background:rgba(245,158,11,.08);border:1.5px solid #f59e0b30;border-left:4px solid #f59e0b;border-radius:12px;padding:14px 16px;margin-bottom:16px;">'
                 + '<div style="font-weight:700;font-size:13px;color:#d97706;margin-bottom:6px;">&#9888; Sin derecho a vacaciones aún</div>'
                 + '<div style="font-size:12px;color:#64748b;">Según la LOTTT debes cumplir <strong>1 año de servicio</strong> para tener derecho a vacaciones.<br>'
                 + 'Antigüedad actual: <strong>' + info.anios_servicio + ' año(s) y ' + info.meses_parciales + ' mes(es)</strong>.</div>'
                 + '</div>';
        }

        const totalPeriodos = (info.periodos || []).length;
        const disponibles   = info.periodos_disponibles ?? 0;
        const color         = disponibles === 0 ? '#ef4444' : (disponibles === 1 ? '#f59e0b' : '#10b981');

        let html = '<div style="background:' + (disponibles === 0 ? 'rgba(239,68,68,.08)' : 'rgba(16,185,129,.08)') + ';'
                 + 'border:1.5px solid ' + color + '30;border-left:4px solid ' + color + ';'
                 + 'border-radius:12px;padding:14px 16px;margin-bottom:16px;text-align:left;">';

        html += '<div style="font-weight:700;font-size:13px;color:' + color + ';margin-bottom:10px;">'
              + '&#127774; Saldo Vacacional — Períodos LOTTT</div>';

        html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px;">';
        html += '<div style="text-align:center;background:rgba(255,255,255,.7);border-radius:8px;padding:8px 4px;">'
              + '<div style="font-size:20px;font-weight:800;color:#1e293b;">' + totalPeriodos + '</div>'
              + '<div style="font-size:10.5px;color:#64748b;line-height:1.3">Per&iacute;odos<br>totales</div></div>';
        html += '<div style="text-align:center;background:rgba(255,255,255,.7);border-radius:8px;padding:8px 4px;">'
              + '<div style="font-size:20px;font-weight:800;color:#ef4444;">' + (totalPeriodos - disponibles) + '</div>'
              + '<div style="font-size:10.5px;color:#64748b;line-height:1.3">Per&iacute;odos<br>tomados</div></div>';
        html += '<div style="text-align:center;background:rgba(255,255,255,.7);border-radius:8px;padding:8px 4px;">'
              + '<div style="font-size:20px;font-weight:800;color:' + color + ';">' + disponibles + '</div>'
              + '<div style="font-size:10.5px;color:#64748b;line-height:1.3">Per&iacute;odos<br>disponibles</div></div>';
        html += '</div>';

        html += '<div style="font-size:11.5px;color:#64748b;">&#128197; '
              + info.anios_servicio + ' a&ntilde;o(s) de servicio &nbsp;|&nbsp; Fecha ingreso: <strong>' + info.fecha_ingreso + '</strong></div>';

        // Píldoras de estado por período
        if (info.periodos && info.periodos.length > 0) {
            html += '<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">';
            info.periodos.forEach(function(p) {
                const bg  = p.tomado ? '#fee2e2' : '#d1fae5';
                const clr = p.tomado ? '#991b1b' : '#065f46';
                const lbl = (p.tomado ? '✗' : '✓') + ' Año ' + p.año + ' (' + p.dias + 'd)';
                html += '<span style="background:' + bg + ';color:' + clr + ';padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;">' + lbl + '</span>';
            });
            html += '</div>';
        }

        if (disponibles === 0) {
            html += '<div style="margin-top:10px;padding:8px 12px;border-radius:8px;background:rgba(239,68,68,.12);font-size:12.5px;font-weight:600;color:#ef4444;">'
                  + '&#10060; No tienes períodos vacacionales disponibles. Puedes solicitar un permiso especial.</div>';
        }

        html += '</div>';
        return html;
    }

    /* =====================================================================
       MODAL NUEVA SOLICITUD
    ===================================================================== */
    async function abrirModalNuevaSolicitud() {
        const hoy = new Date().toISOString().split('T')[0];

        const vacPromise = cargarInfoVacaciones();

        const { value: form } = await Swal.fire({
            title: '<div style="font-size:20px;font-weight:700;color:#1e293b;">&#x1F4CB; Nueva Solicitud</div>',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-form-group" style="text-align:left;">
                        <label class="swal-label swal-label-required">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Tipo de solicitud
                        </label>
                        <select id="sf-tipo" class="swal2-select" style="width:100%;">
                            <option value="">Seleccionar tipo...</option>
                            <option value="vacaciones"> Vacaciones (Periodo Reg.)</option>
                            <option value="permiso"> Permiso Especial</option>
                        </select>
                    </div>

                    <div id="sf-vac-banner"></div>

                    <!-- SECCIÓN VACACIONES: selección de períodos -->
                    <style>
                        .periodo-item-sol { display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:10px; margin-bottom:8px; border:2px solid #e2e8f0; background:#f8fafc; cursor:pointer; transition:all 0.2s; text-align:left; }
                        .periodo-item-sol:hover { border-color:#06d6a0; background:#f0fdf4; }
                        .periodo-item-sol.selected { border-color:#06d6a0; background:linear-gradient(135deg,#ecfdf5,#d1fae5); }
                        .periodo-item-sol.tomado { opacity:.5; cursor:not-allowed; background:#f1f5f9; border-color:#e2e8f0; pointer-events:none; }
                        .pi-checkbox { width:18px; height:18px; border-radius:5px; border:2px solid #cbd5e1; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
                        .periodo-item-sol.selected .pi-checkbox { background:#06d6a0; border-color:#06d6a0; }
                        .periodo-item-sol.selected .pi-checkbox::after { content:'✓'; color:white; font-size:11px; font-weight:700; }
                        .pi-info { flex:1; }
                        .pi-label { font-size:13px; font-weight:700; color:#1e293b; }
                        .pi-dias { font-size:11px; color:#64748b; margin-top:2px; }
                        .pi-badge { padding:3px 9px; border-radius:20px; font-size:10px; font-weight:700; white-space:nowrap; }
                        .badge-disp { background:#dcfce7; color:#166534; }
                        .badge-tom  { background:#f1f5f9; color:#94a3b8; }
                        .resumen-sel-sol { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #bfdbfe; border-radius:10px; padding:11px 14px; margin-bottom:12px; display:none; }
                        .resumen-sel-sol .rt { font-size:13px; color:#1e40af; font-weight:600; }
                    </style>
                    <div id="sf-periodos-section" style="display:none; text-align:left;">
                        <div style="font-size:12px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;">Selecciona el o los períodos a disfrutar</div>
                        <div id="sf-periodos-list"></div>
                        <div id="sf-resumen-sel" class="resumen-sel-sol">
                            <div class="rt" id="sf-resumen-text"></div>
                        </div>
                        <div class="swal-form-group" id="sf-fecha-inicio-vac-group" style="display:none;">
                            <label class="swal-label swal-label-required">Fecha de inicio</label>
                            <input type="date" id="sf-fecha-inicio-vac" class="swal2-input" value="${hoy}">
                        </div>
                        <div id="sf-retorno-vac" style="display:none; background:linear-gradient(135deg,#eff6ff,#dbeafe); border-left:3px solid #3b82f6; padding:10px 14px; border-radius:8px; font-size:13px; font-weight:600; color:#1e40af; margin-bottom:10px;">
                            Fecha de retorno estimada: <strong id="sf-retorno-vac-fecha">-</strong>
                        </div>
                    </div>

                    <!-- SECCIÓN PERMISO: fechas libres -->
                    <div id="sf-permiso-section" style="display:none;">
                        <div class="swal-form-grid-2col" style="text-align:left;">
                            <div class="swal-form-group">
                                <label class="swal-label swal-label-required">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    Fecha inicio
                                </label>
                                <input type="date" id="sf-inicio" class="swal2-input" min="${hoy}" value="${hoy}">
                            </div>
                            <div class="swal-form-group">
                                <label class="swal-label swal-label-required">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    Fecha fin
                                </label>
                                <input type="date" id="sf-fin" class="swal2-input" min="${hoy}" value="${hoy}">
                            </div>
                        </div>
                        <div id="sf-duracion-wrap" style="display:none; text-align:left;">
                            <div id="sf-duracion-badge" style="background:#f8fafc; border-left:3px solid #0F4C81; border-radius:8px; padding:12px 14px; font-size:13px; color:#475569; font-weight:600;"></div>
                        </div>
                    </div>

                    <div class="swal-form-group" style="text-align:left;">
                        <label class="swal-label swal-label-required">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Motivo / descripción
                        </label>
                        <textarea id="sf-motivo" class="swal2-textarea" style="resize:vertical; min-height:80px; width:100%;" placeholder="Describe brevemente el motivo de tu solicitud..."></textarea>
                    </div>

                    <div style="background:#eff6ff; border-left:3px solid #3b82f6; padding:12px; border-radius:8px; font-size:12px; color:#1e40af; text-align:left; margin-top:8px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tu solicitud será enviada para su revisión y aprobación.
                    </div>
                </div>
            `,
            width: '580px',
            showCancelButton: true,
            confirmButtonText: '&#x2705; Enviar Solicitud',
            cancelButtonText:  '&#x2716; Cancelar',
            confirmButtonColor: '#10b981',
            cancelButtonColor:  '#64748b',
            customClass: { popup: 'swal-modern-popup' },
            didOpen: () => {
                const tipoSel       = document.getElementById('sf-tipo');
                const banner        = document.getElementById('sf-vac-banner');
                const periodosSeccion = document.getElementById('sf-periodos-section');
                const periodosList  = document.getElementById('sf-periodos-list');
                const resumenEl     = document.getElementById('sf-resumen-sel');
                const resumenText   = document.getElementById('sf-resumen-text');
                const fechaVacGrp   = document.getElementById('sf-fecha-inicio-vac-group');
                const fechaVacIn    = document.getElementById('sf-fecha-inicio-vac');
                const retornoBox    = document.getElementById('sf-retorno-vac');
                const retornoFecha  = document.getElementById('sf-retorno-vac-fecha');
                const permisoSeccion = document.getElementById('sf-permiso-section');
                const inicioIn      = document.getElementById('sf-inicio');
                const finIn         = document.getElementById('sf-fin');
                const durWrap       = document.getElementById('sf-duracion-wrap');
                const durBadge      = document.getElementById('sf-duracion-badge');
                let vacInfo         = null;
                let seleccionados   = new Set();
                let periodosData    = [];

                // ── Toggle periodo ────────────────────────────────────────────
                periodosList.addEventListener('click', (e) => {
                    const item = e.target.closest('.periodo-item-sol');
                    if (!item || item.classList.contains('tomado')) return;

                    const anio = parseInt(item.dataset.anio);
                    if (seleccionados.has(anio)) {
                        seleccionados.delete(anio);
                        item.classList.remove('selected');
                    } else {
                        seleccionados.add(anio);
                        item.classList.add('selected');
                    }
                    actualizarResumenSol();
                });

                function actualizarResumenSol() {
                    if (seleccionados.size === 0) {
                        resumenEl.style.display = 'none';
                        fechaVacGrp.style.display = 'none';
                        retornoBox.style.display = 'none';
                        return;
                    }
                    const sorted = [...seleccionados].sort((a,b) => a-b);
                    const totalDias = sorted.reduce((acc, a) => {
                        const p = periodosData.find(x => x.año === a);
                        return acc + (p ? p.dias : 0);
                    }, 0);
                    resumenText.innerHTML = `✅ ${sorted.length} período${sorted.length>1?'s':''} seleccionado${sorted.length>1?'s':''} (Año${sorted.length>1?'s':''} ${sorted.join(', ')}) — <strong>${totalDias} días hábiles</strong>`;
                    resumenEl.style.display = 'block';
                    fechaVacGrp.style.display = 'block';
                    calcularRetornoSol(totalDias);
                }

                async function calcularRetornoSol(totalDias) {
                    if (!fechaVacIn.value || totalDias === 0) { retornoBox.style.display='none'; return; }
                    const res  = await fetch(APP_URL + '/vistas/vacaciones/ajax/calcular_fecha_retorno.php?fecha_inicio=' + fechaVacIn.value + '&dias_habiles=' + totalDias);
                    const data = await res.json();
                    if (data.success) {
                        retornoFecha.textContent = data.data.fecha_retorno_formateada;
                        retornoBox.style.display = 'block';
                    }
                }

                fechaVacIn.addEventListener('change', () => {
                    if (seleccionados.size > 0) {
                        const sorted = [...seleccionados].sort((a,b) => a-b);
                        const totalDias = sorted.reduce((acc, a) => {
                            const p = periodosData.find(x => x.año === a);
                            return acc + (p ? p.dias : 0);
                        }, 0);
                        calcularRetornoSol(totalDias);
                    }
                });

                // ── Cambio de tipo ────────────────────────────────────────────
                tipoSel.addEventListener('change', async (e) => {
                    const tipo = e.target.value;
                    banner.innerHTML = '';
                    periodosSeccion.style.display = 'none';
                    permisoSeccion.style.display  = 'none';
                    seleccionados.clear();

                    if (tipo === 'vacaciones') {
                        banner.innerHTML = '<div style="text-align:center;padding:10px;color:#64748b;font-size:13px;">&#x23F3; Cargando períodos...</div>';
                        if (!vacInfo) vacInfo = await vacPromise;

                        if (vacInfo) {
                            banner.innerHTML = renderBannerVac(vacInfo);

                            if (vacInfo.tiene_derecho && (vacInfo.periodos_disponibles ?? 0) > 0) {
                                periodosData = vacInfo.periodos || [];
                                periodosList.innerHTML = periodosData.map(p => {
                                    if (p.tomado) {
                                        const fi = p.fecha_inicio ? new Date(p.fecha_inicio+'T00:00:00').toLocaleDateString('es-VE') : '';
                                        const ff = p.fecha_fin   ? new Date(p.fecha_fin  +'T00:00:00').toLocaleDateString('es-VE') : '';
                                        return `<div class="periodo-item-sol tomado">
                                            <div class="pi-checkbox"></div>
                                            <div class="pi-info">
                                                <div class="pi-label">Período Año ${p.año}</div>
                                                <div class="pi-dias">${p.dias} días hábiles &bull; Tomado: ${fi} → ${ff}</div>
                                            </div>
                                            <span class="pi-badge badge-tom">Tomado</span>
                                        </div>`;
                                    }
                                    return `<div class="periodo-item-sol" data-anio="${p.año}" data-dias="${p.dias}">
                                        <div class="pi-checkbox"></div>
                                        <div class="pi-info">
                                            <div class="pi-label">Período Año ${p.año}</div>
                                            <div class="pi-dias">${p.dias} días hábiles</div>
                                        </div>
                                        <span class="pi-badge badge-disp">Disponible</span>
                                    </div>`;
                                }).join('');
                                periodosSeccion.style.display = 'block';
                            }
                        } else {
                            banner.innerHTML = '<div style="background:#fef3c7;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:8px;font-size:12.5px;color:#92400e;">&#x26A0;&#xFE0F; No se pudo cargar el saldo vacacional.</div>';
                        }

                    } else if (tipo === 'permiso') {
                        permisoSeccion.style.display = 'block';
                    }
                });

                // ── Permiso: duración ─────────────────────────────────────────
                function actualizarDuracion() {
                    const ini  = inicioIn.value;
                    const fin  = finIn.value;
                    const dias = diffDias(ini, fin);
                    if (ini) finIn.min = ini;
                    if (fin && fin < ini) finIn.value = ini;
                    if (ini && fin && dias >= 0) {
                        durWrap.style.display = 'block';
                        durBadge.innerHTML = '&#x1F4C5; Duración: <strong>' + dias + ' día(s)</strong> (' + formatFecha(ini) + ' &rarr; ' + formatFecha(fin) + ')';
                    } else {
                        durWrap.style.display = 'none';
                    }
                }
                inicioIn.addEventListener('change', actualizarDuracion);
                finIn.addEventListener('change', actualizarDuracion);
            },
            preConfirm: async () => {
                const tipo   = document.getElementById('sf-tipo').value;
                const motivo = document.getElementById('sf-motivo').value.trim();

                if (!tipo)   { Swal.showValidationMessage('Selecciona el tipo de solicitud'); return false; }
                if (!motivo) { Swal.showValidationMessage('Describe el motivo de la solicitud'); return false; }
                if (motivo.length < 10) { Swal.showValidationMessage('El motivo debe tener al menos 10 caracteres'); return false; }

                if (tipo === 'vacaciones') {
                    // Leer períodos seleccionados desde el DOM
                    const selItems = document.querySelectorAll('.periodo-item-sol.selected');
                    const selAnios = [...selItems].map(el => parseInt(el.dataset.anio));
                    if (selAnios.length === 0) { Swal.showValidationMessage('Selecciona al menos un período vacacional'); return false; }
                    const fechaVac = document.getElementById('sf-fecha-inicio-vac').value;
                    if (!fechaVac) { Swal.showValidationMessage('Ingresa la fecha de inicio de las vacaciones'); return false; }
                    return { tipo, motivo, periodos_años: selAnios, fecha_inicio: fechaVac, fecha_fin: fechaVac };
                }

                // Permiso: validar fechas
                const inicio = document.getElementById('sf-inicio').value;
                const fin    = document.getElementById('sf-fin').value;
                const dias   = diffDias(inicio, fin);
                if (!inicio) { Swal.showValidationMessage('Ingresa la fecha de inicio'); return false; }
                if (!fin)    { Swal.showValidationMessage('Ingresa la fecha de fin');    return false; }
                if (fin < inicio) { Swal.showValidationMessage('La fecha fin no puede ser anterior al inicio'); return false; }
                return { tipo, motivo, fecha_inicio: inicio, fecha_fin: fin, dias };
            }
        });

        if (!form) return;

        // ── Envío de datos al backend (AJAX) ──
        Swal.fire({
            title: 'Enviando...',
            html: 'Procesando tu solicitud...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const fd = new FormData();
            fd.append('csrf_token',     CSRF_TOKEN);
            fd.append('tipo_solicitud', form.tipo);
            fd.append('motivo',         form.motivo);
            
            // Determinar fechas
            const fi = form.tipo === 'vacaciones' ? form.fecha_inicio : form.inicio;
            const ff = form.tipo === 'vacaciones' ? form.fecha_fin    : form.fin;
            fd.append('fecha_inicio', fi);
            fd.append('fecha_fin',    ff);

            if (form.tipo === 'vacaciones') {
                fd.append('periodos_años', JSON.stringify(form.periodos_años));
            }

            const res  = await fetch(APP_URL + '/vistas/solicitudes/ajax/enviar_solicitud.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                _vacInfo = null;
                const msjDias = form.tipo === 'vacaciones' ? '(por períodos)' : ('por <strong>' + form.dias + ' día(s)</strong>');
                await Swal.fire({
                    icon: 'success',
                    title: '&#x1F389; ¡Solicitud enviada!',
                    html: 'Tu solicitud de <strong>' + form.tipo + '</strong> ' + msjDias + ' fue enviada para revisión.',
                    confirmButtonColor: '#10b981'
                });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo enviar la solicitud', confirmButtonColor: '#ef4444' });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
        }
    }
    </script>
</body>
</html>
