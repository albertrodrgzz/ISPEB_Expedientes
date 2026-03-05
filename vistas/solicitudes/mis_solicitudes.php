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
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
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
        <?php include __DIR__ . '/../layout/header.php'; ?>

        <div class="content-wrapper">
            <div class="page-header">
                <div class="header-title">
                    <h1>Mis Solicitudes</h1>
                    <p style="color:#64748b;font-size:13.5px;margin-top:4px;">Gestiona tus solicitudes de vacaciones y permisos</p>
                </div>
                <button class="btn-primary" onclick="abrirModalNuevaSolicitud()">
                    <?= Icon::get('plus') ?>
                    Nueva Solicitud
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card color-blue">
                    <div class="kpi-icon"><?= Icon::get('send') ?></div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $stats['total'] ?></span>
                        <span class="kpi-label">Total enviadas</span>
                    </div>
                </div>
                <div class="kpi-card color-orange">
                    <div class="kpi-icon"><?= Icon::get('clock') ?></div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $stats['pendientes'] ?></span>
                        <span class="kpi-label">Pendientes</span>
                    </div>
                </div>
                <div class="kpi-card color-green">
                    <div class="kpi-icon"><?= Icon::get('check-circle') ?></div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $stats['aprobadas'] ?></span>
                        <span class="kpi-label">Aprobadas</span>
                    </div>
                </div>
                <div class="kpi-card" style="background:linear-gradient(135deg,rgba(239,68,68,.10),rgba(239,68,68,.05));border-color:rgba(239,68,68,.2);">
                    <div class="kpi-icon" style="background:rgba(239,68,68,.15);color:#ef4444;"><?= Icon::get('x-circle') ?></div>
                    <div class="kpi-content">
                        <span class="kpi-value" style="color:#ef4444;"><?= $stats['rechazadas'] ?></span>
                        <span class="kpi-label">Rechazadas</span>
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
        </div><!-- /.content-wrapper -->
    </div><!-- /.main-content -->


    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
    const CSRF_TOKEN = '<?= $csrf_token ?>';
    const APP_URL    = '<?= APP_URL ?>';

    /* =====================================================================
       UTILIDADES
    ===================================================================== */
    function diffDias(inicio, fin) {
        if (!inicio || !fin) return 0;
        const d1 = new Date(inicio + 'T00:00:00');
        const d2 = new Date(fin    + 'T00:00:00');
        return Math.max(0, Math.round((d2 - d1) / 86400000) + 1);
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
       BANNER DE SALDO VACACIONAL
    ===================================================================== */
    function renderBannerVac(info, diasSolicitados) {
        const disponibles = info.dias_disponibles;
        const excede      = diasSolicitados > disponibles;
        const color       = excede ? '#ef4444' : (disponibles <= 5 ? '#f59e0b' : '#10b981');
        const iconTop = excede
            ? '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
            : (disponibles > 0
                ? '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-13l-.87.5M4.21 17.5l-.87.5M20.66 17.5l-.87-.5M4.21 6.5l-.87-.5M21 12h1M2 12h1"/><circle cx="12" cy="12" r="3"/></svg>'
                : '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>');

        let html = '<div style="background:' + (excede ? 'rgba(239,68,68,.08)' : 'rgba(16,185,129,.08)') + ';'
                 + 'border:1.5px solid ' + color + '30;border-left:4px solid ' + color + ';'
                 + 'border-radius:12px;padding:14px 16px;margin-bottom:16px;text-align:left;">';

        html += '<div style="font-weight:700;font-size:13px;color:' + color + ';margin-bottom:10px;">'
              + iconTop + ' Saldo Vacacional &ndash; Periodo ' + info.periodo_actual.inicio + ' al ' + info.periodo_actual.fin
              + '</div>';

        html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px;">';
        html += '<div style="text-align:center;background:rgba(255,255,255,.7);border-radius:8px;padding:8px 4px;">'
              + '<div style="font-size:20px;font-weight:800;color:#1e293b;">' + info.dias_derecho + '</div>'
              + '<div style="font-size:10.5px;color:#64748b;line-height:1.3">D&iacute;as<br>correspondientes</div></div>';
        html += '<div style="text-align:center;background:rgba(255,255,255,.7);border-radius:8px;padding:8px 4px;">'
              + '<div style="font-size:20px;font-weight:800;color:#ef4444;">' + info.dias_tomados + '</div>'
              + '<div style="font-size:10.5px;color:#64748b;line-height:1.3">D&iacute;as<br>tomados</div></div>';
        html += '<div style="text-align:center;background:rgba(255,255,255,.7);border-radius:8px;padding:8px 4px;">'
              + '<div style="font-size:20px;font-weight:800;color:' + color + ';">' + disponibles + '</div>'
              + '<div style="font-size:10.5px;color:#64748b;line-height:1.3">D&iacute;as<br>disponibles</div></div>';
        html += '</div>';

        html += '<div style="font-size:11.5px;color:#64748b;">'
             + '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> '
               + info.anios_servicio + ' a&ntilde;o(s) de servicio desde el ' + info.fecha_ingreso
              + ' &nbsp;|&nbsp; Base: ' + info.dias_base + ' dias + ' + info.dias_adicionales + ' adic. (LOTTT)';
        if (info.dias_en_tramite > 0) {
            html += ' &nbsp;|&nbsp; <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ' + info.dias_en_tramite + ' dia(s) en tramite';
        }
        html += '</div>';

        if (diasSolicitados > 0) {
            html += '<div style="margin-top:10px;padding:8px 12px;border-radius:8px;'
                  + 'background:' + (excede ? 'rgba(239,68,68,.12)' : 'rgba(16,185,129,.12)') + ';'
                  + 'font-size:12.5px;font-weight:600;color:' + color + ';">';
            if (excede) {
                html += '<svg width="12" height="12" fill="none" stroke="#ef4444" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Estas solicitando ' + diasSolicitados + ' dia(s) pero solo tienes ' + disponibles + ' disponibles.';
            } else {
                html += '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><polyline points="20 6 9 17 4 12"/></svg> Solicitas ' + diasSolicitados + ' dia(s) &middot; Quedaran ' + (disponibles - diasSolicitados) + ' disponibles';
            }
            html += '</div>';
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
                <style>
                .sf-group{text-align:left;margin-bottom:14px;}
                .sf-label{display:block;font-weight:600;font-size:13px;color:#334155;margin-bottom:6px;}
                .sf-label .req{color:#ef4444;margin-left:2px;}
                .sf-control{width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:9px;
                           font-size:13.5px;font-family:inherit;background:#fff;transition:border .2s;box-sizing:border-box;}
                .sf-control:focus{border-color:#3b82f6;outline:none;box-shadow:0 0 0 3px rgba(59,130,246,.12);}
                .sf-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
                .sf-textarea{resize:vertical;min-height:75px;}
                .sf-info{background:#eff6ff;border-left:3px solid #3b82f6;padding:10px 13px;
                         border-radius:8px;font-size:12px;color:#1e40af;margin-top:4px;}
                #sf-duracion-wrap{display:none;margin-bottom:14px;}
                </style>
                <div style="max-width:520px;margin:0 auto;">
                    <div class="sf-group">
                        <label class="sf-label">Tipo de solicitud <span class="req">*</span></label>
                        <select id="sf-tipo" class="sf-control">
                            <option value="">Seleccionar tipo...</option>
                            <option value="vacaciones">&#x1F334; Vacaciones</option>
                            <option value="permiso">&#x1F550; Permiso</option>
                        </select>
                    </div>
                    <div id="sf-vac-banner"></div>
                    <div class="sf-row">
                        <div class="sf-group">
                            <label class="sf-label">Fecha inicio <span class="req">*</span></label>
                            <input type="date" id="sf-inicio" class="sf-control" min="${hoy}" value="${hoy}">
                        </div>
                        <div class="sf-group">
                            <label class="sf-label">Fecha fin <span class="req">*</span></label>
                            <input type="date" id="sf-fin" class="sf-control" min="${hoy}" value="${hoy}">
                        </div>
                    </div>
                    <div id="sf-duracion-wrap">
                        <div id="sf-duracion-badge" style="background:#f1f5f9;border-radius:8px;
                             padding:9px 14px;font-size:13px;color:#475569;font-weight:600;"></div>
                    </div>
                    <div class="sf-group">
                        <label class="sf-label">Motivo / descripcion <span class="req">*</span></label>
                        <textarea id="sf-motivo" class="sf-control sf-textarea"
                                  placeholder="Describe brevemente el motivo de tu solicitud..."></textarea>
                    </div>
                    <div class="sf-info">
                        &#x2139;&#xFE0F; Tu solicitud sera enviada al departamento de RRHH para revision y aprobacion.
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
                const tipoSel  = document.getElementById('sf-tipo');
                const inicioIn = document.getElementById('sf-inicio');
                const finIn    = document.getElementById('sf-fin');
                const banner   = document.getElementById('sf-vac-banner');
                const durWrap  = document.getElementById('sf-duracion-wrap');
                const durBadge = document.getElementById('sf-duracion-badge');
                let vacInfo = null;

                async function actualizarUI() {
                    const tipo = tipoSel.value;
                    const ini  = inicioIn.value;
                    const fin  = finIn.value;
                    const dias = diffDias(ini, fin);

                    if (ini) finIn.min = ini;
                    if (fin && fin < ini) finIn.value = ini;

                    if (ini && fin && dias >= 0) {
                        durWrap.style.display = 'block';
                        durBadge.innerHTML = '&#x1F4C5; Duracion: <strong>' + dias + ' dia(s)</strong> (' + formatFecha(ini) + ' &rarr; ' + formatFecha(fin) + ')';
                    } else {
                        durWrap.style.display = 'none';
                    }

                    if (tipo === 'vacaciones') {
                        banner.innerHTML = '<div style="text-align:center;padding:12px;color:#64748b;font-size:13px;">&#x23F3; Calculando saldo vacacional...</div>';
                        if (!vacInfo) vacInfo = await vacPromise;
                        if (vacInfo) {
                            banner.innerHTML = renderBannerVac(vacInfo, dias);
                        } else {
                            banner.innerHTML = '<div style="background:#fef3c7;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:8px;font-size:12.5px;color:#92400e;margin-bottom:14px;">&#x26A0;&#xFE0F; No se pudo cargar el saldo vacacional. Puedes continuar igual.</div>';
                        }
                    } else {
                        banner.innerHTML = '';
                    }
                }

                tipoSel.addEventListener('change', actualizarUI);
                inicioIn.addEventListener('change', actualizarUI);
                finIn.addEventListener('change', actualizarUI);
            },
            preConfirm: async () => {
                const tipo   = document.getElementById('sf-tipo').value;
                const inicio = document.getElementById('sf-inicio').value;
                const fin    = document.getElementById('sf-fin').value;
                const motivo = document.getElementById('sf-motivo').value.trim();
                const dias   = diffDias(inicio, fin);

                if (!tipo)   { Swal.showValidationMessage('Selecciona el tipo de solicitud'); return false; }
                if (!inicio) { Swal.showValidationMessage('Ingresa la fecha de inicio');      return false; }
                if (!fin)    { Swal.showValidationMessage('Ingresa la fecha de fin');         return false; }
                if (fin < inicio) { Swal.showValidationMessage('La fecha fin no puede ser anterior al inicio'); return false; }
                if (!motivo) { Swal.showValidationMessage('Describe el motivo de la solicitud'); return false; }
                if (motivo.length < 10) { Swal.showValidationMessage('El motivo debe tener al menos 10 caracteres'); return false; }

                if (tipo === 'vacaciones' && _vacInfo) {
                    if (dias > _vacInfo.dias_disponibles) {
                        Swal.showValidationMessage(
                            'Solo tienes ' + _vacInfo.dias_disponibles + ' dia(s) disponibles pero estas solicitando ' + dias + '. Ajusta las fechas.'
                        );
                        return false;
                    }
                }

                return { tipo, inicio, fin, motivo, dias };
            }
        });

        if (!form) return;

        Swal.fire({
            title: 'Enviando...',
            html: 'Procesando tu solicitud de <strong>' + form.dias + ' dia(s)</strong>...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const fd = new FormData();
            fd.append('csrf_token',     CSRF_TOKEN);
            fd.append('tipo_solicitud', form.tipo);
            fd.append('fecha_inicio',   form.inicio);
            fd.append('fecha_fin',      form.fin);
            fd.append('motivo',         form.motivo);

            const res  = await fetch(APP_URL + '/vistas/solicitudes/ajax/enviar_solicitud.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                _vacInfo = null;
                await Swal.fire({
                    icon: 'success',
                    title: '&#x1F389; Solicitud enviada!',
                    html: 'Tu solicitud de <strong>' + form.tipo + '</strong> por <strong>' + form.dias + ' dia(s)</strong> fue enviada a RRHH.',
                    confirmButtonColor: '#10b981'
                });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo enviar la solicitud', confirmButtonColor: '#ef4444' });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error de conexion', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
        }
    }
    </script>
</body>
</html>
