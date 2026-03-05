<?php
/**
 * Módulo de Traslados
 * Diseño: Enterprise Standard - igual a Vacaciones/Nombramientos
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'Acceso no autorizado';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// --- DEPARTAMENTOS ---
$stmtDeps = $db->query("SELECT * FROM departamentos ORDER BY nombre ASC");
$departamentos = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
$departamentosJson = json_encode($departamentos);

// --- KPIs ---
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$traslados_mes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$traslados_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO'");
$total_historico = $stmt->fetch()['total'];

// --- LISTADO ---
$stmt = $db->query("
    SELECT
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        ha.fecha_evento,
        ha.detalles,
        ha.ruta_archivo_pdf,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'TRASLADO'
    ORDER BY ha.fecha_evento DESC
");
$traslados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener años únicos para el filtro
$stmt_anios = $db->query("SELECT DISTINCT YEAR(fecha_evento) as anio FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' ORDER BY anio DESC");
$anios_disponibles = $stmt_anios->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslados - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">

    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">

    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>

    <style>
        /* Flecha de cambio de departamento */
        .dept-change { display: flex; align-items: center; gap: 8px; font-size: 13px; flex-wrap: wrap; }
        .dept-arrow { color: #94A3B8; font-size: 14px; font-weight: 700; }
        .dept-old { color: #64748B; text-decoration: line-through; opacity: 0.75; font-size: 12px; }
        .dept-new { color: #0F4C81; font-weight: 600; }
        /* File input modal */
        .swal2-file { background: #fff !important; border: 2px solid #e2e8f0 !important; border-radius: 8px !important; padding: 10px !important; font-size: 14px !important; width: 100% !important; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>

        <div class="content-wrapper">
            <!-- Header -->
            <div class="page-header">
                <div class="header-title">
                    <h1>Gestión de Traslados</h1>
                </div>
                <button class="btn-primary" onclick="abrirModalTraslado()">
                    <?= Icon::get('plus') ?>
                    Registrar Traslado
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card color-purple">
                    <div class="kpi-icon">
                        <?= Icon::get('refresh-cw') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= number_format($traslados_mes) ?></span>
                        <span class="kpi-label">Traslados este Mes</span>
                    </div>
                </div>
                <div class="kpi-card color-orange">
                    <div class="kpi-icon">
                        <?= Icon::get('calendar') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= number_format($traslados_anio) ?></span>
                        <span class="kpi-label">Traslados este A&ntilde;o</span>
                    </div>
                </div>
                <div class="kpi-card color-teal">
                    <div class="kpi-icon">
                        <?= Icon::get('file-text') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= number_format($total_historico) ?></span>
                        <span class="kpi-label">Total Hist&oacute;rico</span>
                    </div>
                </div>
            </div>

            <!-- Tabla con filtros -->
            <div class="card-modern">
                <!-- Filter Toolbar -->
                <div class="filter-toolbar">
                    <div class="filter-item">
                        <label class="filter-label">Buscar</label>
                        <input type="text" id="searchTraslado" class="form-control" placeholder="Buscar funcionario, cédula...">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Departamento Destino</label>
                        <select id="filter-departamento" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dep): ?>
                                <option value="<?= htmlspecialchars(strtolower($dep['nombre'])) ?>">
                                    <?= htmlspecialchars($dep['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">A&ntilde;o</label>
                        <select id="filter-anio" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($anios_disponibles as $anio): ?>
                                <option value="<?= $anio ?>"><?= $anio ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item" style="display: flex; align-items: flex-end;">
                        <button type="button" onclick="limpiarFiltros()" class="btn-secondary" style="width: 100%;">
                            <?= Icon::get('rotate-ccw') ?> Limpiar
                        </button>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-container">
                    <table class="table-modern" id="tablaTraslados">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>C&eacute;dula</th>
                                <th>Movimiento (Departamento)</th>
                                <th>Fecha Efectiva</th>
                                <th>Motivo</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($traslados)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('briefcase', 'width:48px; height:48px; opacity:0.3;') ?>
                                            </div>
                                            <div class="empty-state-text">No hay traslados registrados</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($traslados as $t):
                                    $detalles   = json_decode($t['detalles'], true) ?? [];
                                    $dep_origen = $detalles['departamento_origen'] ?? $detalles['departamento_anterior'] ?? 'N/A';
                                    $dep_destino = $detalles['departamento_destino'] ?? $detalles['departamento_nuevo'] ?? 'N/A';
                                    $motivo     = $detalles['motivo'] ?? $detalles['observaciones'] ?? 'Sin motivo';
                                    $anio_evento = date('Y', strtotime($t['fecha_evento']));
                                ?>
                                    <tr class="traslado-row"
                                        data-search="<?= strtolower(htmlspecialchars($t['nombres'] . ' ' . $t['apellidos'] . ' ' . $t['cedula'])) ?>"
                                        data-departamento="<?= strtolower(htmlspecialchars($dep_destino)) ?>"
                                        data-anio="<?= $anio_evento ?>">
                                        <td>
                                            <div style="font-weight: 600; color: #1E293B;">
                                                <?= htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($t['cedula']) ?></td>
                                        <td>
                                            <div class="dept-change">
                                                <span class="dept-old"><?= htmlspecialchars($dep_origen) ?></span>
                                                <span class="dept-arrow">&rarr;</span>
                                                <span class="dept-new"><?= htmlspecialchars($dep_destino) ?></span>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($t['fecha_evento'])) ?></td>
                                        <td>
                                            <span style="font-size: 12px; color: #64748B;">
                                                <?= htmlspecialchars($motivo) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 8px;">
                                                <a href="../funcionarios/ver.php?id=<?= $t['funcionario_id'] ?>"
                                                   class="btn-icon" title="Ver Expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if ($t['ruta_archivo_pdf']): ?>
                                                    <a href="<?= APP_URL . '/' . $t['ruta_archivo_pdf'] ?>"
                                                       target="_blank"
                                                       class="btn-icon" title="Ver Resolución PDF"
                                                       style="color: #EF4444;">
                                                        <?= Icon::get('file-text') ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Contador -->
                <div style="padding: 12px 20px; border-top: 1px solid #E2E8F0; font-size: 13px; color: #64748B;">
                    Mostrando <strong id="contadorVisible">0</strong> de <strong id="contadorTotal">0</strong> traslados
                </div>
            </div>
        </div>
    </div>

    <script>
    const APP_URL = "<?= APP_URL ?>";
    const DEPARTAMENTOS = <?= $departamentosJson ?>;

    // ===== FILTROS =====
    function aplicarFiltros() {
        const search   = document.getElementById('searchTraslado').value.toLowerCase().trim();
        const depto    = document.getElementById('filter-departamento').value.toLowerCase();
        const anio     = document.getElementById('filter-anio').value;
        const rows     = document.querySelectorAll('.traslado-row');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = !search || row.dataset.search.includes(search);
            const matchDepto  = !depto  || row.dataset.departamento.includes(depto);
            const matchAnio   = !anio   || row.dataset.anio === anio;
            const show = matchSearch && matchDepto && matchAnio;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('contadorVisible').textContent = visible;
        document.getElementById('contadorTotal').textContent   = rows.length;
    }

    function limpiarFiltros() {
        document.getElementById('searchTraslado').value = '';
        document.getElementById('filter-departamento').value = '';
        document.getElementById('filter-anio').value = '';
        aplicarFiltros();
    }

    document.getElementById('searchTraslado')?.addEventListener('input', aplicarFiltros);
    document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter-anio')?.addEventListener('change', aplicarFiltros);

    document.addEventListener('DOMContentLoaded', aplicarFiltros);

    // ===== MODAL REGISTRAR TRASLADO =====
    async function abrirModalTraslado() {
        try {
            Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const response = await fetch(`${APP_URL}/vistas/funcionarios/ajax/listar.php`);
            if (!response.ok) throw new Error('Error de conexión');
            const data = await response.json();
            if (!data.success) throw new Error('No se pudieron cargar los datos');

            const funcionarios = data.data.filter(f => f.estado === 'activo');
            Swal.close();

            const deptOptions = DEPARTAMENTOS.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('');

            const { value: formValues } = await Swal.fire({
                title: '<div style="display:flex;align-items:center;gap:10px;font-size:20px;font-weight:700;color:#1e293b"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg><span>Registrar Traslado</span></div>',
                width: '700px',
                html: `
                    <style>
                        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
                        .form-group{text-align:left}
                        .form-label{display:block;font-weight:600;margin-bottom:7px;color:#334155;font-size:13px}
                        .form-input,.form-select{width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;transition:all .2s;font-family:inherit}
                        .form-input:focus,.form-select:focus{border-color:#8B5CF6;outline:none;box-shadow:0 0 0 3px rgba(139,92,246,.1)}
                        .dept-preview{background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;font-size:13px;color:#64748B;display:flex;align-items:center;gap:8px;margin-top:4px}
                    </style>
                    <div style="max-width:650px;margin:0 auto;text-align:left">
                        <div class="form-group" style="margin-bottom:16px">
                            <label class="form-label">Funcionario <span style="color:#ef4444">*</span></label>
                            <select id="swal-funcionario" class="form-select">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `<option value="${f.id}" data-dept="${f.departamento_nombre || 'No definido'}">${f.nombres} ${f.apellidos} (${f.cedula})</option>`).join('')}
                            </select>
                        </div>
                        <div class="dept-preview" id="dept-actual-preview">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Dept. Actual: <strong id="dept-actual-text">— Seleccione un funcionario —</strong></span>
                        </div>
                        <div class="form-row" style="margin-top:16px">
                            <div class="form-group">
                                <label class="form-label">Nuevo Departamento <span style="color:#ef4444">*</span></label>
                                <select id="swal-dept-nuevo" class="form-select">
                                    <option value="">Seleccione destino...</option>
                                    ${deptOptions}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha Efectiva <span style="color:#ef4444">*</span></label>
                                <input type="date" id="swal-fecha" class="form-input" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:16px">
                            <label class="form-label">Motivo / Resolución <span style="color:#ef4444">*</span></label>
                            <input type="text" id="swal-motivo" class="form-input" placeholder="Ej: Resolución RRHH-2024-001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Orden de Traslado (PDF — opcional)</label>
                            <input type="file" id="swal-archivo" class="swal2-file" accept="application/pdf">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Procesar Traslado',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#8B5CF6',
                cancelButtonColor: '#64748b',
                didOpen: () => {
                    document.getElementById('swal-funcionario').addEventListener('change', (e) => {
                        const opt = e.target.options[e.target.selectedIndex];
                        document.getElementById('dept-actual-text').textContent = opt.getAttribute('data-dept') || '—';
                    });
                },
                preConfirm: () => {
                    const func     = document.getElementById('swal-funcionario').value;
                    const dept_new = document.getElementById('swal-dept-nuevo').value;
                    const fecha    = document.getElementById('swal-fecha').value;
                    const motivo   = document.getElementById('swal-motivo').value.trim();
                    const file     = document.getElementById('swal-archivo').files[0];
                    if (!func || !dept_new || !fecha || !motivo) {
                        Swal.showValidationMessage('Complete todos los campos obligatorios');
                        return false;
                    }
                    return { func, dept_new, fecha, motivo, file };
                }
            });

            if (formValues) guardarTraslado(formValues);

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function guardarTraslado(datos) {
        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const formData = new FormData();
        formData.append('csrf_token', '<?= generarTokenCSRF() ?>');
        formData.append('accion', 'registrar_traslado');
        formData.append('funcionario_id', datos.func);
        formData.append('departamento_destino_id', datos.dept_new);
        formData.append('fecha_evento', datos.fecha);
        formData.append('motivo', datos.motivo);
        if (datos.file) formData.append('archivo_pdf', datos.file);

        try {
            const res  = await fetch(`${APP_URL}/vistas/funcionarios/ajax/gestionar_historial.php`, { method: 'POST', body: formData });
            const text = await res.text();
            let result;
            try { result = JSON.parse(text); }
            catch (e) { throw new Error('Error del servidor. Revise la consola.'); }

            if (result.success) {
                await Swal.fire({ icon: 'success', title: '¡Traslado Exitoso!', text: 'El funcionario ha sido movido al nuevo departamento.', confirmButtonColor: '#8B5CF6' });
                window.location.reload();
            } else {
                throw new Error(result.error);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        }
    }
    </script>
</body>
</html>