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


// ── Historial ──
$busqueda = $_GET['busqueda'] ?? '';
$f_estado = $_GET['estado'] ?? '';
$where = ["se.estado != 'pendiente'"];
$params = [];

if ($busqueda !== '') {
    $where[] = "(f.cedula LIKE ? OR f.nombres LIKE ? OR f.apellidos LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($f_estado !== '') {
    $where[] = "se.estado = ?";
    $params[] = $f_estado;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT se.*,
           CONCAT(f.nombres,' ',f.apellidos) AS funcionario_nombre,
           f.cedula,
           u.username                        AS revisado_por_nombre
    FROM   solicitudes_empleados se
    INNER JOIN funcionarios f ON se.funcionario_id = f.id
    LEFT  JOIN usuarios     u ON se.revisado_por    = u.id
    WHERE  $whereSql
    ORDER  BY se.updated_at DESC
    LIMIT  100
");
$stmt->execute($params);
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
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <style>
        /* ── Modal de Aprobación (Estilo SWAL Moderno Avanzado) ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 20px;
            padding: 32px;
            width: min(540px, 95vw);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 4px 16px rgba(0, 0, 0, 0.1);
            animation: modalSlideIn .3s cubic-bezier(0.165, 0.84, 0.44, 1);
            font-family: 'Inter', sans-serif;
            border: none;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-30px) scale(0.95); opacity: 0; }
            to   { transform: translateY(0) scale(1);        opacity: 1; }
        }
        .modal-title {
            font-size: 24px; font-weight: 700; color: #1e293b;
            margin-bottom: 8px; letter-spacing: -0.5px;
        }
        .modal-subtitle { font-size: 14px; color: #64748b; margin-bottom: 24px; display: flex; align-items: center; gap: 6px; }
        .mf-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
        .mf-label {
            font-size: 13px; font-weight: 600; color: #1e293b;
            display: flex; align-items: center; gap: 6px;
        }
        .mf-req { color: #dc2626; margin-left:2px; }
        .mf-control {
            width: 100%; padding: 12px 16px;
            border: 2px solid #E5E7EB; border-radius: 10px;
            font-size: 14px; font-family: inherit;
            transition: all .2s ease; box-sizing: border-box; background: white;
        }
        .mf-control:focus { 
            border-color: #0F4C81; outline: none; 
            box-shadow: 0 0 0 3px rgba(15, 76, 129, 0.1); 
        }
        .mf-textarea { resize: vertical; min-height: 100px; }
        
        .file-drop-zone {
            border: 2px dashed #E5E7EB; border-radius: 12px;
            padding: 24px; text-align: center;
            cursor: pointer; transition: all .2s;
            background: #F9FAFB;
        }
        .file-drop-zone:hover, .file-drop-zone.has-file {
            border-color: #0F4C81; background: #F0F9FF;
        }
        .file-drop-zone input[type=file] { display: none; }
        .file-drop-zone-icon { 
            margin-bottom: 8px; display:inline-flex; align-items:center; justify-content:center;
            width: 48px; height: 48px; border-radius: 50%; background: #E0F2FE; color: #0EA5E9;
        }
        .file-drop-text { font-size: 14px; color: #1e293b; font-weight: 600; margin-bottom: 4px; }
        .file-drop-hint { font-size: 12px; color: #64748b; }
        
        .modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 28px; }
        .btn-cancel {
            background: #F3F4F6; color: #1e293b; border: none;
            padding: 12px 28px; border-radius: 10px; font-weight: 600; font-size: 14px;
            transition: all 0.2s; cursor: pointer;
        }
        .btn-cancel:hover { background: #E5E7EB; }
        .btn-confirm-approve {
            background: linear-gradient(135deg, #0F4C81 0%, #00a8cc 100%);
            color: white; border: none; padding: 12px 28px; border-radius: 10px;
            font-weight: 600; font-size: 14px; cursor: pointer;
            box-shadow: 0 4px 12px rgba(15, 76, 129, 0.25);
            transition: all 0.3s ease; display:flex; align-items:center; gap:8px;
        }
        .btn-confirm-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 76, 129, 0.35);
        }

        /* ── Tabs ──────────────────────────────────────────────── */
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

        <div class="module-container">
            <!-- Header Título y Botón -->
            <div class="module-header-title">
                <div class="module-title-group">
                    <?= Icon::get('inbox') ?>
                    <h2 class="module-title-text">Bandeja de Solicitudes</h2>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
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
                
                <div class="kpi-card-solid bg-solid-blue">
                    <div class="kpi-icon"><?= Icon::get('inbox') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Total</div>
                        <div class="kpi-value"><?= $stats['total'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Tabs: Pendientes / Historial -->
            <div style="margin-top: 32px;">
                <div class="tabs">
                    <button class="tab-btn active" onclick="cambiarTab('pendientes', this)">
                        <?= Icon::get('clock', 'width:14px;height:14px;') ?>
                        Pendientes
                        <?php if ($stats['pendientes'] > 0): ?>
                            <span style="background:#EF4444;color:#fff;border-radius:99px;padding:1px 7px;font-size:11px;margin-left:4px;font-weight:700;line-height:1.6;"><?= $stats['pendientes'] ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-btn" onclick="cambiarTab('historial', this)">
                        <?= Icon::get('list', 'width:14px;height:14px;') ?>
                        Historial
                    </button>
                </div>

                <!-- ── TAB PENDIENTES ─────────────────────────────────── -->
                <div id="tab-pendientes" class="tab-content visible">
                    <?php if (empty($pendientes)): ?>
                        <div class="empty-st">
                            <div class="empty-st-ico">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <h3>Todo al día</h3>
                            <p>No hay solicitudes pendientes de revisión.</p>
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
                                    <?php foreach ($pendientes as $sol):
                                        $iniciales = strtoupper(mb_substr($sol['funcionario_nombre'],0,1));
                                        $colors = ['#0F4C81','#0288D1','#8B5CF6','#10B981','#F59E0B','#14B8A6'];
                                        $color = $colors[abs(crc32($sol['cedula']??'')) % count($colors)];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fn-cell">
                                                <div class="fn-avatar" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc)"><?= $iniciales ?></div>
                                                <div class="fn-info">
                                                    <span class="fn-name"><?= htmlspecialchars($sol['funcionario_nombre']) ?></span>
                                                    <span class="fn-meta"><span class="fn-cedula"><?= htmlspecialchars($sol['cedula']) ?></span> · <?= htmlspecialchars($sol['departamento'] ?? '—') ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="type-pill <?= $sol['tipo_solicitud'] ?>">
                                                <?= $sol['tipo_solicitud'] === 'vacaciones' ? '☀️ Vacaciones' : '🕐 Permiso' ?>
                                            </span>
                                        </td>
                                        <td style="font-size:13px;white-space:nowrap;">
                                            <?= date('d/m/Y', strtotime($sol['fecha_inicio'])) ?>
                                            <span style="color:#CBD5E1;margin:0 3px;">→</span>
                                            <?= date('d/m/Y', strtotime($sol['fecha_fin'])) ?>
                                        </td>
                                        <td style="max-width:200px;">
                                            <div style="font-size:13px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;" title="<?= htmlspecialchars($sol['motivo']) ?>">
                                                <?= htmlspecialchars(mb_substr($sol['motivo'],0,60)) ?><?= mb_strlen($sol['motivo'])>60?'…':'' ?>
                                            </div>
                                        </td>
                                        <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                                            <?= date('d/m/Y H:i', strtotime($sol['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div class="tbl-actions">
                                                <button class="btn-ic ic-approve" title="Aprobar"
                                                        onclick="abrirModalAprobar(<?= $sol['id'] ?>, '<?= htmlspecialchars($sol['funcionario_nombre']) ?>', '<?= $sol['tipo_solicitud'] ?>')">
                                                    <?= Icon::get('check') ?>
                                                </button>
                                                <button class="btn-ic ic-del" title="Rechazar"
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
                    
                    <form method="GET" action="gestionar_solicitudes.php" class="flat-filter-bar" id="historialFilters">
                        <div class="filter-group">
                            <label class="filter-label">Funcionario / Cédula</label>
                            <input type="text" name="busqueda" class="flat-input" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Estado</label>
                            <select name="estado" class="flat-input" onchange="document.getElementById('historialFilters').submit()">
                                <option value="">Todos</option>
                                <option value="aprobada" <?= ($_GET['estado']??'')==='aprobada'?'selected':'' ?>>Aprobada</option>
                                <option value="rechazada" <?= ($_GET['estado']??'')==='rechazada'?'selected':'' ?>>Rechazada</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn-primary" style="padding:10px 20px; border-radius:8px;">Filtrar</button>
                            <?php if(!empty($_GET['busqueda']) || !empty($_GET['estado'])): ?>
                                <a href="gestionar_solicitudes.php" class="btn-clear-flat" style="display:inline-block; padding:10px 20px; margin-left:8px; border-radius:8px; border:1px solid #e2e8f0; color:#64748b; text-decoration:none;">Limpiar</a>
                                <script>
                                    // Make sure historial tab is active on load if filters are applied
                                    document.addEventListener('DOMContentLoaded', () => {
                                        document.querySelectorAll('.tab-btn')[0].classList.remove('active');
                                        document.querySelectorAll('.tab-btn')[1].classList.add('active');
                                        document.getElementById('tab-pendientes').classList.remove('visible');
                                        document.getElementById('tab-historial').classList.add('visible');
                                    });
                                </script>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (empty($historial)): ?>
                        <div class="empty-st">
                            <div class="empty-st-ico"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                            <h3>Sin historial</h3>
                            <p>No hay solicitudes procesadas aún.</p>
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
                                    <?php foreach ($historial as $h):
                                        $iniciales = strtoupper(mb_substr($h['funcionario_nombre'],0,1));
                                        $colors = ['#0F4C81','#0288D1','#8B5CF6','#10B981','#F59E0B','#14B8A6'];
                                        $color = $colors[abs(crc32($h['cedula']??'')) % count($colors)];
                                        $stMap = ['pendiente'=>'pending','aprobada'=>'approved','rechazada'=>'rejected'];
                                        $stClass = $stMap[$h['estado']] ?? 'inactive';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fn-cell">
                                                <div class="fn-avatar" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc)"><?= $iniciales ?></div>
                                                <div class="fn-info">
                                                    <span class="fn-name"><?= htmlspecialchars($h['funcionario_nombre']) ?></span>
                                                    <span class="fn-meta"><span class="fn-cedula"><?= htmlspecialchars($h['cedula']) ?></span></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="type-pill <?= $h['tipo_solicitud'] ?>">
                                                <?= $h['tipo_solicitud'] === 'vacaciones' ? '☀️ Vacaciones' : '🕐 Permiso' ?>
                                            </span>
                                        </td>
                                        <td style="font-size:13px;white-space:nowrap;">
                                            <?= date('d/m/Y', strtotime($h['fecha_inicio'])) ?>
                                            <span style="color:#CBD5E1;margin:0 3px;">→</span>
                                            <?= date('d/m/Y', strtotime($h['fecha_fin'])) ?>
                                        </td>
                                        <td>
                                            <span class="st-badge st-<?= $stClass ?>">
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
                                                   class="btn-ic ic-pdf"
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

            </div><!-- /.tabs wrapper -->
        </div><!-- /.module-container -->
    </div><!-- /.main-content -->

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL DE APROBACIÓN (HTML nativo para soportar file upload)
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalAprobar">
        <div class="modal-box">
            <div class="modal-title">
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
