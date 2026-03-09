<?php
/**
 * Vista: Bandeja de Solicitudes (Niveles 1 y 2 – Admin/RRHH)
 * Sistema ISPEB - Aprobación Documental con Memo Físico
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

// Solo Nivel 1 y 2
if ($_SESSION['nivel_acceso'] > 2) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$pdo        = getDB();
$csrf_token = generarTokenCSRF();

// ── Estadísticas ──────────────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT
    SUM(estado = 'pendiente')  AS pendientes,
    SUM(estado = 'aprobada')   AS aprobadas,
    SUM(estado = 'rechazada')  AS rechazadas,
    COUNT(*)                   AS total
  FROM solicitudes_empleados");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Solicitudes Pendientes ────────────────────────────────────────────────────
$stmt = $pdo->query("
    SELECT se.*,
           CONCAT(f.nombres,' ',f.apellidos) AS funcionario_nombre,
           f.cedula,
           d.nombre                          AS departamento
    FROM   solicitudes_empleados se
    INNER JOIN funcionarios f ON se.funcionario_id = f.id
    LEFT  JOIN departamentos d ON f.departamento_id = d.id
    WHERE  se.estado = 'pendiente'
    ORDER  BY se.created_at ASC
");
$pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Historial reciente (últimos 20) ──────────────────────────────────────────
$stmt = $pdo->query("
    SELECT se.*,
           CONCAT(f.nombres,' ',f.apellidos) AS funcionario_nombre,
           f.cedula,
           u.username                        AS revisado_por_nombre
    FROM   solicitudes_empleados se
    INNER JOIN funcionarios f ON se.funcionario_id = f.id
    LEFT  JOIN usuarios     u ON se.revisado_por    = u.id
    WHERE  se.estado != 'pendiente'
    ORDER  BY se.updated_at DESC
    LIMIT  20
");
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandeja de Solicitudes - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <style>
        /* ── Modal de Aprobación ───────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 20px;
            padding: 32px;
            width: min(540px, 95vw);
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
            animation: modalSlideIn .25s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }
        .modal-title {
            font-size: 19px; font-weight: 700; color: #1e293b;
            margin-bottom: 6px; display: flex; align-items: center; gap: 10px;
        }
        .modal-subtitle { font-size: 13px; color: #64748b; margin-bottom: 22px; }
        .mf-group { margin-bottom: 16px; }
        .mf-label {
            display: block; font-size: 13px; font-weight: 600; color: #334155;
            margin-bottom: 6px;
        }
        .mf-req { color: #ef4444; }
        .mf-control {
            width: 100%; padding: 10px 13px;
            border: 2px solid #e2e8f0; border-radius: 9px;
            font-size: 13.5px; font-family: inherit;
            transition: border .2s; box-sizing: border-box;
        }
        .mf-control:focus { border-color: #10b981; outline: none; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
        .mf-textarea { resize: vertical; min-height: 80px; }
        .file-drop-zone {
            border: 2.5px dashed #cbd5e1; border-radius: 12px;
            padding: 22px; text-align: center;
            cursor: pointer; transition: all .2s;
            background: #f8fafc;
        }
        .file-drop-zone:hover, .file-drop-zone.has-file {
            border-color: #10b981; background: #ecfdf5; color: #059669;
        }
        .file-drop-zone input[type=file] { display: none; }
        .file-drop-zone-icon { font-size: 28px; margin-bottom: 6px; }
        .file-drop-text { font-size: 13px; color: #64748b; font-weight: 500; }
        .file-drop-hint { font-size: 11px; color: #94a3b8; margin-top: 3px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; }
        .btn-cancel { padding: 10px 20px; border-radius: 9px; border: 2px solid #e2e8f0;
                      background: #fff; color: #475569; font-weight: 600; font-size: 13.5px;
                      cursor: pointer; transition: all .2s; }
        .btn-cancel:hover { background: #f1f5f9; }
        .btn-confirm-approve { padding: 10px 24px; border-radius: 9px; border: none;
                               background: linear-gradient(135deg,#10b981,#059669);
                               color: #fff; font-weight: 700; font-size: 13.5px;
                               cursor: pointer; transition: all .2s; }
        .btn-confirm-approve:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16,185,129,.35); }

        /* ── Badges ────────────────────────────────────────────── */
        .badge-pendiente  { background:#fef3c7;color:#92400e;border:1px solid #fcd34d; }
        .badge-aprobada   { background:#d1fae5;color:#065f46;border:1px solid #6ee7b7; }
        .badge-rechazada  { background:#fee2e2;color:#991b1b;border:1px solid #fca5a5; }
        .badge-sol {
            padding: 3px 10px; border-radius: 99px;
            font-size: 11.5px; font-weight: 600;
        }

        /* ── Tipo icono ────────────────────────────────────────── */
        .tipo-pill {
            display:inline-flex;align-items:center;gap:5px;
            padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;
        }
        .tipo-pill.vacaciones { background:#dbeafe;color:#1d4ed8; }
        .tipo-pill.permiso    { background:#fce7f3;color:#be185d; }

        /* ── Tabs ──────────────────────────────────────────────── */
        .tabs { display:flex;gap:6px;margin-bottom:20px; }
        .tab-btn {
            padding:8px 18px;border-radius:9px;border:2px solid transparent;
            font-weight:600;font-size:13px;cursor:pointer;transition:all .2s;
            background:#f1f5f9;color:#64748b;
        }
        .tab-btn.active { background:#1e293b;color:#fff;border-color:#1e293b; }
        .tab-content { display:none; }
        .tab-content.visible { display:block; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php 
        $pageTitle = 'Bandeja de Solicitudes';
        include __DIR__ . '/../layout/header.php'; 
        ?>

        <div class="content-wrapper">
            

            <!-- KPI Cards -->
            <div class="kpi-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
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
                <div class="kpi-card color-blue">
                    <div class="kpi-icon"><?= Icon::get('inbox') ?></div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $stats['total'] ?></span>
                        <span class="kpi-label">Total</span>
                    </div>
                </div>
            </div>

            <!-- Tabs: Pendientes / Historial -->
            <div class="card-modern">
                <div class="tabs">
                    <button class="tab-btn active" onclick="cambiarTab('pendientes', this)">
                        <?= Icon::get('clock', 'width:14px;height:14px;') ?>
                        Pendientes
                        <?php if ($stats['pendientes'] > 0): ?>
                            <span style="background:#ef4444;color:#fff;border-radius:99px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= $stats['pendientes'] ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-btn" onclick="cambiarTab('historial', this)">
                        <?= Icon::get('list', 'width:14px;height:14px;') ?>
                        Historial Reciente
                    </button>
                </div>

                <!-- ── TAB PENDIENTES ─────────────────────────────────── -->
                <div id="tab-pendientes" class="tab-content visible">
                    <?php if (empty($pendientes)): ?>
                        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
                            <svg width="56" height="56" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" style="margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <h3 style="color:#1e293b;margin-bottom:6px;">Todo al d&iacute;a</h3>
                            <p>No hay solicitudes pendientes de revisi&oacute;n.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table-modern" id="tablaPendientes">
                                <thead>
                                    <tr>
                                        <th>Funcionario</th>
                                        <th>Tipo</th>
                                        <th>Período</th>
                                        <th>Motivo</th>
                                        <th>Enviada</th>
                                        <th style="text-align:right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendientes as $sol): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($sol['funcionario_nombre']) ?></div>
                                            <div style="font-size:12px;color:#64748b;"><?= htmlspecialchars($sol['cedula']) ?> · <?= htmlspecialchars($sol['departamento'] ?? 'Sin depto.') ?></div>
                                        </td>
                                        <td>
                                            <span class="tipo-pill <?= $sol['tipo_solicitud'] ?>">
                                                <?= $sol['tipo_solicitud'] === 'vacaciones'
                                                    ? '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-13l-.87.5M4.21 17.5l-.87.5M20.66 17.5l-.87-.5M4.21 6.5l-.87-.5M21 12h1M2 12h1"/><circle cx="12" cy="12" r="3"/></svg> Vacaciones'
                                                    : '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Permiso' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size:13px;">
                                                <?= date('d/m/Y', strtotime($sol['fecha_inicio'])) ?>
                                                <span style="color:#94a3b8;">→</span>
                                                <?= date('d/m/Y', strtotime($sol['fecha_fin'])) ?>
                                            </div>
                                        </td>
                                        <td style="max-width:200px;">
                                            <div style="font-size:13px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;"
                                                 title="<?= htmlspecialchars($sol['motivo']) ?>">
                                                <?= htmlspecialchars($sol['motivo']) ?>
                                            </div>
                                        </td>
                                        <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                                            <?= date('d/m/Y H:i', strtotime($sol['created_at'])) ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                                <button class="btn-icon"
                                                        style="background:rgba(16,185,129,.12);color:#059669;"
                                                        title="Aprobar"
                                                        onclick="abrirModalAprobar(<?= $sol['id'] ?>, '<?= htmlspecialchars($sol['funcionario_nombre']) ?>', '<?= $sol['tipo_solicitud'] ?>')">
                                                    <?= Icon::get('check') ?>
                                                </button>
                                                <button class="btn-icon"
                                                        style="background:rgba(239,68,68,.12);color:#ef4444;"
                                                        title="Rechazar"
                                                        onclick="rechazarSolicitud(<?= $sol['id'] ?>, '<?= htmlspecialchars($sol['funcionario_nombre']) ?>', '<?= $sol['tipo_solicitud'] ?>', '<?= $sol['fecha_inicio'] ?>', '<?= $sol['fecha_fin'] ?>')">
                                                    <?= Icon::get('x') ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── TAB HISTORIAL ─────────────────────────────────── -->
                <div id="tab-historial" class="tab-content">
                    <?php if (empty($historial)): ?>
                        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
                            <p>No hay historial de solicitudes.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>Funcionario</th>
                                        <th>Tipo</th>
                                        <th>Período</th>
                                        <th>Estado</th>
                                        <th>Revisado por</th>
                                        <th>Aval</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $h): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($h['funcionario_nombre']) ?></div>
                                            <div style="font-size:12px;color:#64748b;"><?= htmlspecialchars($h['cedula']) ?></div>
                                        </td>
                                        <td>
                                            <span class="tipo-pill <?= $h['tipo_solicitud'] ?>">
                                                <?= $h['tipo_solicitud'] === 'vacaciones'
                                                    ? '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-13l-.87.5M4.21 17.5l-.87.5M20.66 17.5l-.87-.5M4.21 6.5l-.87-.5M21 12h1M2 12h1"/><circle cx="12" cy="12" r="3"/></svg> Vacaciones'
                                                    : '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Permiso' ?>
                                            </span>
                                        </td>
                                        <td style="font-size:13px;">
                                            <?= date('d/m/Y', strtotime($h['fecha_inicio'])) ?>
                                            <span style="color:#94a3b8;">→</span>
                                            <?= date('d/m/Y', strtotime($h['fecha_fin'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge-sol badge-<?= $h['estado'] ?>">
                                                <?= ucfirst($h['estado']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size:12.5px;color:#475569;">
                                            <?= htmlspecialchars($h['revisado_por_nombre'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <?php if ($h['ruta_archivo_aprobacion']): ?>
                                                <a href="<?= APP_URL . '/' . htmlspecialchars($h['ruta_archivo_aprobacion']) ?>"
                                                   target="_blank"
                                                   class="btn-icon"
                                                   title="Ver memo">
                                                    <?= Icon::get('file-text') ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color:#cbd5e1;font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /.card-modern -->
        </div><!-- /.content-wrapper -->
    </div><!-- /.main-content -->

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL DE APROBACIÓN (HTML nativo para soportar file upload)
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalAprobar">
        <div class="modal-box">
            <div class="modal-title">
                <span style="background:#d1fae5;color:#065f46;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <?= Icon::get('check-circle', 'width:20px;height:20px;') ?>
                </span>
                Aprobar Solicitud
            </div>
            <p class="modal-subtitle" id="modalSubtitle"></p>

            <form id="formAprobar" enctype="multipart/form-data">
                <input type="hidden" id="aprobarSolicitudId"  name="solicitud_id">
                <input type="hidden" name="accion"            value="aprobar">
                <input type="hidden" name="csrf_token"        value="<?= $csrf_token ?>">

                <!-- Documento obligatorio -->
                <div class="mf-group">
                    <label class="mf-label">
                        Memo / Aval Firmado y Sellado <span class="mf-req">*</span>
                    </label>
                    <div class="file-drop-zone" id="dropZone" onclick="document.getElementById('memoFile').click()">
                        <input type="file" id="memoFile" name="archivo_aprobacion"
                               accept="application/pdf,image/jpeg,image/jpg,image/png">
                        <div class="file-drop-zone-icon">
                            <svg width="32" height="32" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </div>
                        <div class="file-drop-text" id="dropZoneText">Haz click para adjuntar el documento</div>
                        <div class="file-drop-hint">PDF, JPG o PNG · Máx 5 MB</div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mf-group">
                    <label class="mf-label">Observaciones (opcional)</label>
                    <textarea id="aprobarObservaciones" name="observaciones"
                              class="mf-control mf-textarea"
                              placeholder="Ej: Aprobado según solicitud. Retorno el 15/04/2026..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalAprobar()">Cancelar</button>
                    <button type="submit" class="btn-confirm-approve">
                        <?= Icon::get('check', 'width:15px;height:15px;') ?>
                        Confirmar Aprobación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
    // APP_URL ya está declarado por header.php con guard. Evitar redeclaración con 'const'.
    if (typeof CSRF_TOKEN === 'undefined') {
        var CSRF_TOKEN = '<?= $csrf_token ?>';
    }
    const AJAX_URL = APP_URL + '/vistas/solicitudes/ajax/procesar_solicitud.php';


    function formatFechaGestion(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }

    // ── Tabs ───────────────────────────────────────────────────────────────────
    function cambiarTab(tab, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('visible'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('visible');
        btn.classList.add('active');
    }

    // ── Modal Aprobación ───────────────────────────────────────────────────────
    function abrirModalAprobar(id, nombre, tipo) {
        document.getElementById('aprobarSolicitudId').value = id;
        document.getElementById('modalSubtitle').textContent =
            `Funcionario: ${nombre} · Tipo: ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
        document.getElementById('modalAprobar').classList.add('open');
    }
    function cerrarModalAprobar() {
        document.getElementById('modalAprobar').classList.remove('open');
        document.getElementById('formAprobar').reset();
        document.getElementById('dropZoneText').textContent = 'Haz click para adjuntar el documento';
        document.getElementById('dropZone').classList.remove('has-file');
    }

    // Cerrar modal con overlay click
    document.getElementById('modalAprobar').addEventListener('click', e => {
        if (e.target === e.currentTarget) cerrarModalAprobar();
    });

    // File drop zone feedback
    document.getElementById('memoFile').addEventListener('change', e => {
        const f = e.target.files[0];
        if (!f) return;
        const name = f.name.length > 35 ? f.name.substring(0, 32) + '...' : f.name;
        document.getElementById('dropZoneText').textContent = '&#x2705; ' + name;
        document.getElementById('dropZone').classList.add('has-file');
    });

    // Submit del formulario de aprobación
    document.getElementById('formAprobar').addEventListener('submit', async e => {
        e.preventDefault();

        const file = document.getElementById('memoFile').files[0];
        if (!file) {
            Swal.fire({ icon: 'warning', title: 'Documento requerido',
                        text: 'Debes adjuntar el memo de aprobación firmado y sellado.',
                        confirmButtonColor: '#f59e0b' });
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'Archivo demasiado grande',
                        text: 'El archivo no puede superar los 5 MB.',
                        confirmButtonColor: '#ef4444' });
            return;
        }

        cerrarModalAprobar();
        Swal.fire({ title: 'Procesando...', html: 'Subiendo documento y aprobando solicitud...',
                    allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fd = new FormData(document.getElementById('formAprobar'));
            const res  = await fetch(AJAX_URL, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                await Swal.fire({ icon: 'success', title: '¡Solicitud Aprobada!',
                                  html: `<p>La solicitud fue aprobada exitosamente.</p>
                                         <p style="font-size:13px;color:#64748b;margin-top:8px;">
                                           El evento fue registrado en el historial administrativo.</p>`,
                                  confirmButtonColor: '#10b981' });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error al aprobar',
                            text: data.error || 'Ocurrió un error inesperado.',
                            confirmButtonColor: '#ef4444' });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error de conexión',
                        text: 'No se pudo conectar al servidor.',
                        confirmButtonColor: '#ef4444' });
        }
    });

    // ── Rechazo (SweetAlert2) ──────────────────────────────────────────────────
    async function rechazarSolicitud(id, nombre, tipo, fechaInicio, fechaFin) {
        const periodo = fechaInicio && fechaFin
            ? `${formatFechaGestion(fechaInicio)} → ${formatFechaGestion(fechaFin)}`
            : '';

        const { value: datos } = await Swal.fire({
            title: '<div style="font-size:18px;font-weight:700;color:#1e293b;">&#x274C; Rechazar Solicitud</div>',
            html: `
                <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;
                     padding:12px 16px;margin-bottom:16px;text-align:left;">
                    <div style="font-size:12px;color:#991b1b;font-weight:700;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Solicitud a rechazar</div>
                    <div style="font-weight:600;color:#1e293b;">${nombre}</div>
                    <div style="font-size:12.5px;color:#64748b;margin-top:3px;">
                        ${tipo === 'vacaciones' ? '&#x1F334; Vacaciones' : '&#x1F552; Permiso'}
                        ${periodo ? ' &middot; &#x1F4C5; ' + periodo : ''}
                    </div>
                </div>
                <div style="text-align:left;margin-bottom:14px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">
                        Causa del rechazo <span style="color:#ef4444;">*</span>
                    </label>
                    <select id="swal-causa-rechazo"
                        style="width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:9px;
                               font-size:13.5px;font-family:inherit;background:#fff;box-sizing:border-box;">
                        <option value="">Seleccionar causa...</option>
                        <option value="Saldo de vacaciones insuficiente para el per&#xED;odo solicitado">&#x26D4; Saldo insuficiente de vacaciones</option>
                        <option value="Fechas solicitadas en conflicto con necesidades del servicio">&#x1F4C5; Fechas en conflicto con necesidades del servicio</option>
                        <option value="Documentaci&#xF3;n o motivo de permiso incompleto o no fundamentado">&#x1F4C4; Documentaci&#xF3;n o motivo insuficiente</option>
                        <option value="Ya existe una solicitud activa o aprobada para ese per&#xED;odo">&#x1F501; Solicitud duplicada o per&#xED;odo ya cubierto</option>
                        <option value="La solicitud no cumple con el tiempo m&#xED;nimo de anticipaci&#xF3;n requerido">&#x231B; Anticipaci&#xF3;n insuficiente</option>
                        <option value="Otro motivo (especifique abajo)">&#x270F;&#xFE0F; Otro motivo</option>
                    </select>
                </div>
                <div style="text-align:left;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">
                        Explicación detallada <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea id="swal-motivo-rechazo"
                        style="width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:9px;
                               font-family:inherit;font-size:13.5px;resize:vertical;min-height:90px;box-sizing:border-box;"
                        placeholder="Explica con detalle el motivo del rechazo para que el empleado lo entienda..."></textarea>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Mínimo 20 caracteres</div>
                </div>`,
            width: '520px',
            showCancelButton: true,
            confirmButtonText: '&#x274C; Confirmar Rechazo',
            cancelButtonText:  'Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor:  '#64748b',
            customClass: { popup: 'swal-modern-popup' },
            didOpen: () => {
                // Al seleccionar una causa predefinida, rellenar el área de explicación
                const causa = document.getElementById('swal-causa-rechazo');
                const motivo = document.getElementById('swal-motivo-rechazo');
                causa.addEventListener('change', () => {
                    if (causa.value && causa.value !== 'Otro motivo (especifique abajo)') {
                        if (!motivo.value || motivo.value.length < 15) {
                            motivo.value = causa.value + '. ';
                            motivo.focus();
                            motivo.setSelectionRange(motivo.value.length, motivo.value.length);
                        }
                    }
                });
            },
            preConfirm: () => {
                const causa  = document.getElementById('swal-causa-rechazo').value;
                const motivo = document.getElementById('swal-motivo-rechazo').value.trim();
                if (!causa)  { Swal.showValidationMessage('Selecciona la causa del rechazo'); return false; }
                if (!motivo) { Swal.showValidationMessage('Escribe una explicación detallada'); return false; }
                if (motivo.length < 20) { Swal.showValidationMessage('La explicación debe tener al menos 20 caracteres'); return false; }
                return { causa, motivo, textoCompleto: `[${causa}] ${motivo}` };
            }
        });

        if (!datos) return;

        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fd = new FormData();
            fd.append('csrf_token',    CSRF_TOKEN);
            fd.append('accion',        'rechazar');
            fd.append('solicitud_id',  id);
            fd.append('observaciones', datos.textoCompleto);

            const res  = await fetch(AJAX_URL, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Solicitud Rechazada',
                                  text: 'El empleado será notificado del rechazo.',
                                  confirmButtonColor: '#64748b' });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.error, confirmButtonColor: '#ef4444' });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#ef4444' });
        }
    }

    // ── Búsqueda en tabla ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const tabla = document.getElementById('tablaPendientes');
        if (!tabla) return;
        const buscador = document.createElement('input');
        buscador.type = 'text'; buscador.placeholder = 'Buscar funcionario...';
        buscador.className = 'form-control';
        buscador.style.cssText = 'max-width:280px;margin-bottom:12px;';
        tabla.parentElement.insertBefore(buscador, tabla);
        buscador.addEventListener('input', () => {
            const q = buscador.value.toLowerCase();
            tabla.querySelectorAll('tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });
    </script>
</body>
</html>
