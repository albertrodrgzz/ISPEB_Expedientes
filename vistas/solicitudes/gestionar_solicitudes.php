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
           f.nombres,
           f.apellidos,
           f.cedula,
           f.fecha_ingreso,
           d.nombre AS departamento
    FROM   solicitudes_empleados se
    INNER JOIN funcionarios f ON se.funcionario_id = f.id
    LEFT  JOIN departamentos d ON f.departamento_id = d.id
    WHERE  se.estado = 'pendiente'
    ORDER  BY se.created_at ASC
");
$pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Calcula días LOTTT para el año N de servicio
 * Año 1 → 15d, Año 2 → 18d, Año N≥2 → min(18+(N-2), 30)
 */
function diasPeriodoLOTTT(int $n): int {
    if ($n === 1) return 15;
    return min(18 + ($n - 2), 30);
}

/**
 * Extrae períodos y calcula días totales de una solicitud de vacaciones
 * El motivo viene en formato: [Períodos: Año 1, Año 2] motivo libre...
 * Retorna array ['periodos'=>'Año 1, Año 2', 'dias'=>33, 'label'=>'Año 1 (15d), Año 2 (18d)']
 */
function calcularDiasVacaciones(string $motivo, string $fecha_ingreso): array {
    $result = ['periodos' => '', 'dias' => 0, 'label' => '', 'motivo_limpio' => $motivo];
    // Parsear períodos del motivo: [Períodos: Año 1, Año 2]
    if (preg_match('/^\[Períodos:\s*([^\]]+)\]\s*(.*)/su', $motivo, $m)) {
        $periodos_str     = trim($m[1]); // e.g. "Año 1, Año 2"
        $motivo_limpio    = trim($m[2]);
        $result['periodos']      = $periodos_str;
        $result['motivo_limpio'] = $motivo_limpio;
        // Extraer números de año
        preg_match_all('/\d+/', $periodos_str, $nums);
        $parts = [];
        $total = 0;
        foreach ($nums[0] as $n) {
            $n    = (int)$n;
            $dias = diasPeriodoLOTTT($n);
            $total += $dias;
            $parts[] = "Año $n ($dias días LOTTT)";
        }
        $result['dias']  = $total;
        $result['label'] = implode(', ', $parts);
    } else {
        // Para permisos u otros: diferencia de fecha no aplica aquí
        $result['motivo_limpio'] = $motivo;
    }
    return $result;
}


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
        /* ══════════════════════════════════════════════════════════
           MODALES — Base
        ══════════════════════════════════════════════════════════ */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            align-items: center; justify-content: center;
            padding: 16px;
        }
        .modal-overlay.open { display: flex; }

        @keyframes modalSlideIn {
            from { transform: translateY(-24px) scale(0.97); opacity: 0; }
            to   { transform: translateY(0) scale(1);         opacity: 1; }
        }

        /* ══════════════════════════════════════════════════════════
           MODAL DETALLE SOLICITUD
        ══════════════════════════════════════════════════════════ */
        .detail-modal-box {
            background: #fff;
            border-radius: 22px;
            width: min(680px, 96vw);
            max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 32px 80px rgba(0,0,0,.22), 0 6px 20px rgba(0,0,0,.10);
            animation: modalSlideIn .3s cubic-bezier(0.165, 0.84, 0.44, 1);
            font-family: 'Inter', -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
        }
        /* Header coloreado */
        .dmo-header {
            padding: 28px 32px 24px;
            border-radius: 22px 22px 0 0;
            background: linear-gradient(135deg, #0F4C81 0%, #0288D1 100%);
            color: #fff;
            position: relative;
            flex-shrink: 0;
        }
        .dmo-header-vac  { background: linear-gradient(135deg, #0284C7 0%, #06B6D4 100%); }
        .dmo-header-perm { background: linear-gradient(135deg, #7C3AED 0%, #A855F7 100%); }
        .dmo-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.2); border-radius: 99px;
            padding: 4px 12px; font-size: 12px; font-weight: 700;
            letter-spacing: .5px; text-transform: uppercase;
            margin-bottom: 12px;
        }
        .dmo-title {
            font-size: 22px; font-weight: 800; letter-spacing: -.4px;
            margin: 0 0 4px;
        }
        .dmo-meta { font-size: 13px; opacity: .82; }
        .dmo-close {
            position: absolute; top: 18px; right: 20px;
            background: rgba(255,255,255,.15); border: none;
            width: 32px; height: 32px; border-radius: 50%;
            cursor: pointer; font-size: 18px; color: #fff;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .dmo-close:hover { background: rgba(255,255,255,.3); }

        /* Body */
        .dmo-body { padding: 28px 32px; flex: 1; }

        /* Info grid */
        .dmo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .dmo-field {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .dmo-field-label {
            font-size: 11px; font-weight: 700; color: #94A3B8;
            letter-spacing: .6px; text-transform: uppercase; margin-bottom: 5px;
        }
        .dmo-field-value {
            font-size: 14px; font-weight: 600; color: #1E293B;
        }
        .dmo-field-full { grid-column: 1 / -1; }

        /* Motivo box */
        .dmo-motivo {
            background: #FFFBEB;
            border: 1.5px solid #FDE68A;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .dmo-motivo-label {
            font-size: 11px; font-weight: 700; color: #92400E;
            letter-spacing: .6px; text-transform: uppercase; margin-bottom: 8px;
        }
        .dmo-motivo-text { font-size: 14px; color: #451A03; line-height: 1.6; }

        /* Divider */
        .dmo-divider {
            border: none; border-top: 1.5px solid #E2E8F0;
            margin: 0 0 24px;
        }

        /* Sección aprobar dentro del modal */
        .dmo-approve-section { margin-bottom: 20px; }
        .dmo-section-title {
            font-size: 13px; font-weight: 700; color: #1E293B;
            margin-bottom: 12px; display: flex; align-items: center; gap: 7px;
        }

        /* File drop */
        .file-drop-zone {
            border: 2px dashed #CBD5E1; border-radius: 12px;
            padding: 20px; text-align: center;
            cursor: pointer; transition: all .2s;
            background: #F8FAFC;
        }
        .file-drop-zone:hover, .file-drop-zone.has-file {
            border-color: #0F4C81; background: #EFF6FF;
        }
        .file-drop-zone input[type=file] { display: none; }
        .file-drop-zone-icon {
            display:inline-flex; align-items:center; justify-content:center;
            width: 40px; height: 40px; border-radius: 50%;
            background: #DBEAFE; color: #2563EB; margin-bottom: 8px;
        }
        .file-drop-text  { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 3px; }
        .file-drop-hint  { font-size: 11.5px; color: #94A3B8; }

        /* Textarea observaciones */
        .mf-control {
            width: 100%; padding: 11px 14px;
            border: 2px solid #E2E8F0; border-radius: 10px;
            font-size: 13.5px; font-family: inherit;
            transition: border-color .2s, box-shadow .2s;
            box-sizing: border-box; background: #fff;
            resize: vertical;
        }
        .mf-control:focus {
            border-color: #0F4C81; outline: none;
            box-shadow: 0 0 0 3px rgba(15,76,129,.10);
        }

        /* Footer acciones */
        .dmo-footer {
            padding: 20px 32px 28px;
            display: flex; gap: 12px; justify-content: flex-end;
            border-top: 1.5px solid #F1F5F9;
            flex-shrink: 0;
        }
        .btn-cancel {
            background: #F1F5F9; color: #475569; border: none;
            padding: 12px 24px; border-radius: 10px;
            font-weight: 600; font-size: 14px;
            transition: background .2s; cursor: pointer;
        }
        .btn-cancel:hover { background: #E2E8F0; }
        .btn-reject-modal {
            background: #FEF2F2; color: #DC2626; border: 1.5px solid #FECACA;
            padding: 12px 22px; border-radius: 10px;
            font-weight: 700; font-size: 14px; cursor: pointer;
            transition: all .2s; display: flex; align-items: center; gap: 7px;
        }
        .btn-reject-modal:hover {
            background: #DC2626; color: #fff; border-color: #DC2626;
        }
        .btn-approve-modal {
            background: linear-gradient(135deg, #0F4C81 0%, #0288D1 100%);
            color: #fff; border: none;
            padding: 12px 24px; border-radius: 10px;
            font-weight: 700; font-size: 14px; cursor: pointer;
            box-shadow: 0 4px 14px rgba(15,76,129,.28);
            transition: all .3s; display: flex; align-items: center; gap: 7px;
        }
        .btn-approve-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(15,76,129,.38);
        }

        /* ══════════════════════════════════════════════════════════
           MODAL APROBACIÓN (confirmar con archivo) — secundario
        ══════════════════════════════════════════════════════════ */
        .modal-box {
            background: #fff;
            border-radius: 20px;
            padding: 32px;
            width: min(520px, 95vw);
            box-shadow: 0 20px 60px rgba(0,0,0,.18), 0 4px 16px rgba(0,0,0,.10);
            animation: modalSlideIn .3s cubic-bezier(0.165, 0.84, 0.44, 1);
            font-family: inherit;
        }
        .modal-title {
            font-size: 22px; font-weight: 700; color: #1e293b;
            margin-bottom: 6px; letter-spacing: -.4px;
        }
        .modal-subtitle { font-size: 13.5px; color: #64748b; margin-bottom: 22px; }
        .mf-group { margin-bottom: 18px; display: flex; flex-direction: column; gap: 7px; }
        .mf-label { font-size: 13px; font-weight: 600; color: #1e293b; }
        .mf-textarea { min-height: 95px; }
        .modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 26px; }
        .btn-confirm-approve {
            background: linear-gradient(135deg, #0F4C81 0%, #00a8cc 100%);
            color: #fff; border: none; padding: 12px 26px; border-radius: 10px;
            font-weight: 700; font-size: 14px; cursor: pointer;
            box-shadow: 0 4px 12px rgba(15,76,129,.25);
            transition: all .3s; display:flex; align-items:center; gap:8px;
        }
        .btn-confirm-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15,76,129,.38);
        }

        /* ── Filas clickeables ──────────────────────────────────── */
        #tablaPendientes tbody tr {
            cursor: pointer;
            transition: background .15s;
        }
        #tablaPendientes tbody tr:hover { background: #F0F9FF !important; }

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

                                        // Calcular días según tipo
                                        if ($sol['tipo_solicitud'] === 'vacaciones') {
                                            $vacInfo = calcularDiasVacaciones($sol['motivo'], $sol['fecha_ingreso'] ?? '');
                                            $dias         = $vacInfo['dias'];
                                            $dias_label   = $vacInfo['label'];
                                            $periodos_sol = $vacInfo['periodos'];
                                            $motivo_limpio = $vacInfo['motivo_limpio'];
                                            // Calcular fecha fin estimada: fecha_inicio + días LOTTT
                                            $fecha_display_inicio = $sol['fecha_inicio'];
                                            $fecha_display_fin    = $dias > 0
                                                ? date('Y-m-d', strtotime($sol['fecha_inicio'] . ' + ' . $dias . ' days'))
                                                : $sol['fecha_inicio'];
                                        } else {
                                            $dias = (int)((strtotime($sol['fecha_fin']) - strtotime($sol['fecha_inicio'])) / 86400) + 1;
                                            $dias_label    = $dias . ' ' . ($dias === 1 ? 'día' : 'días');
                                            $periodos_sol  = '';
                                            $motivo_limpio = $sol['motivo'];
                                            $fecha_display_inicio = $sol['fecha_inicio'];
                                            $fecha_display_fin    = $sol['fecha_fin'];
                                        }

                                        // Encode data para el modal
                                        $dataJson = htmlspecialchars(json_encode([
                                            'id'           => $sol['id'],
                                            'nombre'       => $sol['funcionario_nombre'],
                                            'cedula'       => $sol['cedula'],
                                            'depto'        => $sol['departamento'] ?? '—',
                                            'tipo'         => $sol['tipo_solicitud'],
                                            'inicio'       => $fecha_display_inicio,
                                            'fin'          => $fecha_display_fin,
                                            'dias'         => $dias,
                                            'dias_label'   => $dias_label,
                                            'periodos'     => $periodos_sol,
                                            'motivo'       => $motivo_limpio,
                                            'enviada'      => $sol['created_at'],
                                            'color'        => $color,
                                            'iniciales'    => $iniciales,
                                        ]), ENT_QUOTES);
                                    ?>
                                    <tr onclick="abrirDetalle('<?= $sol['id'] ?>')"
                                        data-sol='<?= $dataJson ?>'
                                        id="row-sol-<?= $sol['id'] ?>">
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
                                        <td onclick="event.stopPropagation()">
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
         MODAL DETALLE SOLICITUD — Se abre al hacer clic en la fila
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalDetalle">
        <div class="detail-modal-box">
            <!-- Header dinámico -->
            <div class="dmo-header" id="dmoHeader">
                <button class="dmo-close" onclick="cerrarDetalle()" title="Cerrar">✕</button>
                <div class="dmo-badge" id="dmoBadge"></div>
                <div class="dmo-title" id="dmoTitle"></div>
                <div class="dmo-meta"  id="dmoMeta"></div>
            </div>

            <!-- Body con información completa -->
            <div class="dmo-body">

                <!-- Grid de datos -->
                <div class="dmo-grid" id="dmoGrid">
                    <div class="dmo-field">
                        <div class="dmo-field-label">Cédula</div>
                        <div class="dmo-field-value" id="dmo-cedula">—</div>
                    </div>
                    <div class="dmo-field">
                        <div class="dmo-field-label">Departamento</div>
                        <div class="dmo-field-value" id="dmo-depto">—</div>
                    </div>
                    <div class="dmo-field" id="dmo-field-inicio">
                        <div class="dmo-field-label" id="dmo-label-inicio">Fecha Inicio</div>
                        <div class="dmo-field-value" id="dmo-inicio">—</div>
                    </div>
                    <div class="dmo-field" id="dmo-field-fin">
                        <div class="dmo-field-label" id="dmo-label-fin">Fecha Fin</div>
                        <div class="dmo-field-value" id="dmo-fin">—</div>
                    </div>
                    <div class="dmo-field">
                        <div class="dmo-field-label">Días (LOTTT)</div>
                        <div class="dmo-field-value" id="dmo-dias">—</div>
                    </div>
                    <div class="dmo-field">
                        <div class="dmo-field-label">Fecha de envío</div>
                        <div class="dmo-field-value" id="dmo-enviada">—</div>
                    </div>
                </div>

                <!-- Períodos (solo vacaciones) -->
                <div id="dmo-periodos-row" style="display:none;margin-bottom:16px;">
                    <div class="dmo-field" style="background:#EFF6FF;border-color:#BFDBFE;">
                        <div class="dmo-field-label" style="color:#1E40AF;">📅 Períodos solicitados</div>
                        <div class="dmo-field-value" id="dmo-periodos" style="color:#1E40AF;">—</div>
                    </div>
                </div>

                <!-- Motivo / Justificación -->
                <div class="dmo-motivo">
                    <div class="dmo-motivo-label">📋 Motivo / Justificación</div>
                    <div class="dmo-motivo-text" id="dmo-motivo">—</div>
                </div>
            </div>

            <!-- Footer con acciones -->
            <div class="dmo-footer">
                <button type="button" class="btn-cancel" onclick="cerrarDetalle()">Cerrar</button>
                <button type="button" class="btn-reject-modal" id="btnRechazarDetalle" onclick="rechazarDesdeDetalle()">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    Rechazar
                </button>
                <button type="button" class="btn-approve-modal" id="btnAprobarDetalle" onclick="aprobarDesdeDetalle()">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Aprobar Solicitud
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL DE APROBACIÓN SECUNDARIO (cuando se abre desde botón de tabla)
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalAprobar">
        <div class="modal-box">
            <div class="modal-title">Aprobar Solicitud</div>
            <p class="modal-subtitle" id="modalSubtitle"></p>

            <form id="formAprobar" enctype="multipart/form-data">
                <input type="hidden" id="aprobarSolicitudId" name="solicitud_id">
                <input type="hidden" name="accion"           value="aprobar">
                <input type="hidden" name="csrf_token"       value="<?= $csrf_token ?>">

                <div class="mf-group">
                    <label class="mf-label">
                        Memo / Aval Firmado y Sellado
                        <span style="color:#ef4444;margin-left:3px;">*</span>
                        <span style="font-size:11px;color:#64748b;font-weight:400;margin-left:4px;">(obligatorio)</span>
                    </label>
                    <div class="file-drop-zone" id="dropZone2" onclick="document.getElementById('memoFile2').click()">
                        <input type="file" id="memoFile2" name="archivo_aprobacion"
                               accept="application/pdf,image/jpeg,image/jpg,image/png">
                        <div class="file-drop-zone-icon">
                            <svg width="28" height="28" fill="none" stroke="#2563EB" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </div>
                        <div class="file-drop-text" id="dropZoneText2">Haz click para adjuntar el documento</div>
                        <div class="file-drop-hint">PDF, JPG o PNG · Máx 5 MB</div>
                    </div>
                </div>

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
    if (typeof CSRF_TOKEN === 'undefined') {
        var CSRF_TOKEN = '<?= $csrf_token ?>';
    }
    const AJAX_URL = APP_URL + '/vistas/solicitudes/ajax/procesar_solicitud.php';

    // ═══════════════════════════════════════════════════════════
    //  Utilidades
    // ═══════════════════════════════════════════════════════════
    function formatFechaGestion(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }
    function formatFechaHora(dateStr) {
        if (!dateStr) return '—';
        const dt = new Date(dateStr.replace(' ', 'T'));
        return dt.toLocaleDateString('es-VE', {day:'2-digit',month:'2-digit',year:'numeric'})
             + ' ' + dt.toLocaleTimeString('es-VE', {hour:'2-digit',minute:'2-digit'});
    }

    // ═══════════════════════════════════════════════════════════
    //  Tabs
    // ═══════════════════════════════════════════════════════════
    function cambiarTab(tab, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('visible'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('visible');
        btn.classList.add('active');
    }

    // ═══════════════════════════════════════════════════════════
    //  MODAL DETALLE — Estado global de la solicitud activa
    // ═══════════════════════════════════════════════════════════
    let _solActiva = null;

    function abrirDetalle(solId) {
        // Buscar la fila por ID de solicitud
        const row = document.getElementById('row-sol-' + solId);
        if (!row) return;
        const sol = JSON.parse(row.dataset.sol);
        _solActiva = sol;

        // ── Header ──
        const header = document.getElementById('dmoHeader');
        const esVac  = sol.tipo === 'vacaciones';
        header.className = 'dmo-header ' + (esVac ? 'dmo-header-vac' : 'dmo-header-perm');

        document.getElementById('dmoBadge').innerHTML =
            esVac ? '☀️ &nbsp;Vacaciones' : '🕐 &nbsp;Permiso';

        document.getElementById('dmoTitle').textContent = sol.nombre;
        document.getElementById('dmoMeta').textContent  = `C.I. ${sol.cedula}  ·  ${sol.depto}`;

        // ── Grid de datos ──
        document.getElementById('dmo-cedula').textContent  = sol.cedula || '—';
        document.getElementById('dmo-depto').textContent   = sol.depto  || '—';
        document.getElementById('dmo-enviada').textContent = formatFechaHora(sol.enviada);

        if (esVac) {
            // Vacaciones: fecha fin estimada calculada con días LOTTT
            document.getElementById('dmo-label-inicio').textContent = 'Fecha Inicio';
            document.getElementById('dmo-inicio').textContent       = formatFechaGestion(sol.inicio);
            document.getElementById('dmo-label-fin').textContent    = 'Fecha Fin (estimada)';
            document.getElementById('dmo-fin').textContent          = formatFechaGestion(sol.fin);
            document.getElementById('dmo-fin').style.color          = '#1E293B';
            document.getElementById('dmo-fin').style.fontStyle      = 'normal';
            // Días LOTTT
            document.getElementById('dmo-dias').textContent         = sol.dias_label || (sol.dias + ' días');
            // Períodos
            if (sol.periodos) {
                document.getElementById('dmo-periodos').textContent = sol.periodos
                    + (sol.dias_label ? ' — ' + sol.dias_label : '');
                document.getElementById('dmo-periodos-row').style.display = '';
            } else {
                document.getElementById('dmo-periodos-row').style.display = 'none';
            }
        } else {
            // Permiso: fechas reales
            document.getElementById('dmo-label-inicio').textContent = 'Fecha Inicio';
            document.getElementById('dmo-inicio').textContent       = formatFechaGestion(sol.inicio);
            document.getElementById('dmo-label-fin').textContent    = 'Fecha Fin';
            document.getElementById('dmo-fin').textContent          = formatFechaGestion(sol.fin);
            document.getElementById('dmo-fin').style.color          = '';
            document.getElementById('dmo-fin').style.fontStyle      = '';
            document.getElementById('dmo-dias').textContent         = sol.dias_label || (sol.dias + ' días');
            document.getElementById('dmo-periodos-row').style.display = 'none';
        }

        // ── Motivo limpio (sin el prefijo [Períodos: ...]) ──
        document.getElementById('dmo-motivo').textContent = sol.motivo || '— Sin motivo especificado —';

        document.getElementById('modalDetalle').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function cerrarDetalle() {
        document.getElementById('modalDetalle').classList.remove('open');
        document.body.style.overflow = '';
        _solActiva = null;
    }

    // Cerrar con clic en overlay
    document.getElementById('modalDetalle').addEventListener('click', e => {
        if (e.target === e.currentTarget) cerrarDetalle();
    });
    // Cerrar con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (document.getElementById('modalDetalle').classList.contains('open')) cerrarDetalle();
            if (document.getElementById('modalAprobar').classList.contains('open')) cerrarModalAprobar();
        }
    });

    // ═══════════════════════════════════════════════════════════
    //  APROBAR desde el modal detalle → abre modal secundario
    // ═══════════════════════════════════════════════════════════
    function aprobarDesdeDetalle() {
        if (!_solActiva) return;
        const sol = _solActiva; // guardar antes de que cerrarDetalle() lo anule
        cerrarDetalle();
        abrirModalAprobar(sol.id, sol.nombre, sol.tipo);
    }

    // ═══════════════════════════════════════════════════════════
    //  RECHAZAR desde el modal detalle
    // ═══════════════════════════════════════════════════════════
    function rechazarDesdeDetalle() {
        if (!_solActiva) return;
        const sol = _solActiva;
        cerrarDetalle();
        rechazarSolicitud(sol.id, sol.nombre, sol.tipo, sol.inicio, sol.fin);
    }

    // ═══════════════════════════════════════════════════════════
    //  MODAL APROBACIÓN SECUNDARIO (acciones directas en tabla)
    // ═══════════════════════════════════════════════════════════
    function abrirModalAprobar(id, nombre, tipo) {
        document.getElementById('aprobarSolicitudId').value = id;
        document.getElementById('modalSubtitle').textContent =
            `Funcionario: ${nombre} · Tipo: ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
        document.getElementById('modalAprobar').classList.add('open');
    }
    function cerrarModalAprobar() {
        document.getElementById('modalAprobar').classList.remove('open');
        document.getElementById('formAprobar').reset();
        document.getElementById('dropZoneText2').textContent = 'Haz click para adjuntar el documento';
        document.getElementById('dropZone2').classList.remove('has-file');
    }
    document.getElementById('modalAprobar').addEventListener('click', e => {
        if (e.target === e.currentTarget) cerrarModalAprobar();
    });
    document.getElementById('memoFile2').addEventListener('change', e => {
        const f = e.target.files[0];
        if (!f) return;
        const name = f.name.length > 35 ? f.name.substring(0,32)+'...' : f.name;
        document.getElementById('dropZoneText2').textContent = '✅ ' + name;
        document.getElementById('dropZone2').classList.add('has-file');
    });
    document.getElementById('formAprobar').addEventListener('submit', async e => {
        e.preventDefault();
        const file = document.getElementById('memoFile2').files[0];
        // El archivo es OBLIGATORIO al aprobar
        if (!file) {
            Swal.fire({
                icon: 'warning',
                title: 'Documento requerido',
                text: 'Debes adjuntar el memo / oficio de aprobaci\u00f3n firmado y sellado.',
                confirmButtonColor: '#f59e0b'
            });
            return;
        }
        if (file.size > 5*1024*1024) {
            Swal.fire({icon:'error',title:'Archivo muy grande',text:'M\u00e1x 5 MB.',confirmButtonColor:'#ef4444'});
            return;
        }
        cerrarModalAprobar();
        Swal.fire({title:'Procesando...',html:'Aprobando solicitud...',
                   allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
        try {
            const fd = new FormData(document.getElementById('formAprobar'));
            if (file) fd.set('archivo_aprobacion', file);
            const res  = await fetch(AJAX_URL, {method:'POST', body:fd});
            const data = await res.json();
            if (data.success) {
                await Swal.fire({icon:'success',title:'¡Solicitud Aprobada!',
                    html:`<p>La solicitud fue aprobada exitosamente.</p>
                          <p style="font-size:13px;color:#64748b;margin-top:8px;">El evento quedó registrado en el historial.</p>`,
                    confirmButtonColor:'#10b981'});
                location.reload();
            } else {
                Swal.fire({icon:'error',title:'Error al aprobar',
                           text:data.error||'Error inesperado.',confirmButtonColor:'#ef4444'});
            }
        } catch {
            Swal.fire({icon:'error',title:'Error de conexión',confirmButtonColor:'#ef4444'});
        }
    });

    // ═══════════════════════════════════════════════════════════
    //  RECHAZAR — SweetAlert2 (función reutilizable)
    // ═══════════════════════════════════════════════════════════
    async function rechazarSolicitud(id, nombre, tipo, fechaInicio, fechaFin) {
        const periodo = fechaInicio && fechaFin
            ? `${formatFechaGestion(fechaInicio)} → ${formatFechaGestion(fechaFin)}`
            : '';

        const { value: datos } = await Swal.fire({
            title: '<div style="font-size:18px;font-weight:700;color:#1e293b;">❌ Rechazar Solicitud</div>',
            html: `
                <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;
                     padding:12px 16px;margin-bottom:16px;text-align:left;">
                    <div style="font-size:11px;color:#991b1b;font-weight:700;margin-bottom:6px;
                         text-transform:uppercase;letter-spacing:.5px;">Solicitud a rechazar</div>
                    <div style="font-weight:600;color:#1e293b;">${nombre}</div>
                    <div style="font-size:12.5px;color:#64748b;margin-top:3px;">
                        ${tipo==='vacaciones'?'☀️ Vacaciones':'🕐 Permiso'}
                        ${periodo?' · 📅 '+periodo:''}
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
                        <option value="Saldo de vacaciones insuficiente para el período solicitado">⛔ Saldo insuficiente de vacaciones</option>
                        <option value="Fechas solicitadas en conflicto con necesidades del servicio">📅 Fechas en conflicto con el servicio</option>
                        <option value="Documentación o motivo de permiso incompleto o no fundamentado">📄 Documentación o motivo insuficiente</option>
                        <option value="Ya existe una solicitud activa o aprobada para ese período">🔁 Solicitud duplicada o período ya cubierto</option>
                        <option value="La solicitud no cumple con el tiempo mínimo de anticipación requerido">⌛ Anticipación insuficiente</option>
                        <option value="Otro motivo (especifique abajo)">✏️ Otro motivo</option>
                    </select>
                </div>
                <div style="text-align:left;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">
                        Explicación detallada <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea id="swal-motivo-rechazo"
                        style="width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:9px;
                               font-family:inherit;font-size:13.5px;resize:vertical;min-height:90px;box-sizing:border-box;"
                        placeholder="Explica con detalle el motivo del rechazo..."></textarea>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Mínimo 20 caracteres</div>
                </div>`,
            width: '520px',
            showCancelButton: true,
            confirmButtonText: '❌ Confirmar Rechazo',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            customClass: { popup: 'swal-modern-popup' },
            didOpen: () => {
                const causa  = document.getElementById('swal-causa-rechazo');
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

        Swal.fire({title:'Procesando...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
        try {
            const fd = new FormData();
            fd.append('csrf_token',    CSRF_TOKEN);
            fd.append('accion',        'rechazar');
            fd.append('solicitud_id',  id);
            fd.append('observaciones', datos.textoCompleto);
            const res  = await fetch(AJAX_URL, {method:'POST', body:fd});
            const data = await res.json();
            if (data.success) {
                await Swal.fire({icon:'success', title:'Solicitud Rechazada',
                                 text:'El empleado será notificado del rechazo.',
                                 confirmButtonColor:'#64748b'});
                location.reload();
            } else {
                Swal.fire({icon:'error', title:'Error', text:data.error, confirmButtonColor:'#ef4444'});
            }
        } catch {
            Swal.fire({icon:'error', title:'Error de conexión', confirmButtonColor:'#ef4444'});
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  Búsqueda en tabla pendientes
    // ═══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        const tabla = document.getElementById('tablaPendientes');
        if (!tabla) return;
        const buscador = document.createElement('input');
        buscador.type = 'text';
        buscador.placeholder = 'Buscar funcionario...';
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
