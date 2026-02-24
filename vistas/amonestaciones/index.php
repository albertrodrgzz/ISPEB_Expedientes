<?php
/**
 * Módulo de Amonestaciones
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

// --- KPIs ---
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$total_mes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND (JSON_EXTRACT(detalles, '$.tipo_falta') = 'grave' OR JSON_EXTRACT(detalles, '$.tipo_falta') = 'muy_grave')");
$total_graves = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION'");
$total_historico = $stmt->fetch()['total'];

// --- LISTADO ---
$stmt = $db->query("
    SELECT
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        ha.fecha_evento,
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta')), 'null')  as tipo_falta,
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')), 'null')      as motivo,
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.sancion')), 'null')     as sancion,
        ha.ruta_archivo_pdf,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'AMONESTACION'
    ORDER BY ha.fecha_evento DESC
");
$amonestaciones = $stmt->fetchAll();

// Años disponibles para filtro
$stmt_anios = $db->query("SELECT DISTINCT YEAR(fecha_evento) as anio FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' ORDER BY anio DESC");
$anios_disponibles = $stmt_anios->fetchAll(PDO::FETCH_COLUMN);

// Departamentos para filtro
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amonestaciones - <?= APP_NAME ?></title>

    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">

    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>

    <style>
        /* Badges de gravedad */
        .badge-leve     { background: #FEF3C7; color: #92400E; }
        .badge-grave    { background: #FFEDD5; color: #C2410C; }
        .badge-muy_grave{ background: #FEE2E2; color: #B91C1C; font-weight: 700; }
        /* File input modal */
        .swal2-file { background:#fff !important; border:2px solid #e2e8f0 !important; border-radius:8px !important; padding:10px !important; font-size:14px !important; width:100% !important; }
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
                    <h1>Amonestaciones</h1>
                </div>
                <button class="btn-primary" onclick="abrirModalAmonestacion()">
                    <?= Icon::get('plus') ?>
                    Registrar Falta
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card color-orange">
                    <div class="kpi-icon">
                        <?= Icon::get('calendar') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= number_format($total_mes) ?></span>
                        <span class="kpi-label">Faltas este Mes</span>
                    </div>
                </div>
                <div class="kpi-card color-red">
                    <div class="kpi-icon">
                        <?= Icon::get('alert-circle') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= number_format($total_graves) ?></span>
                        <span class="kpi-label">Graves / Muy Graves</span>
                    </div>
                </div>
                <div class="kpi-card color-cyan">
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
                        <input type="text" id="searchAmonestacion" class="form-control" placeholder="Buscar funcionario, cédula...">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Gravedad</label>
                        <select id="filter-gravedad" class="form-control">
                            <option value="">Todas</option>
                            <option value="leve">Leve</option>
                            <option value="grave">Grave</option>
                            <option value="muy_grave">Muy Grave</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Departamento</label>
                        <select id="filter-departamento" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dep): ?>
                                <option value="<?= strtolower(htmlspecialchars($dep['nombre'])) ?>">
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
                    <table class="table-modern" id="tablaAmonestaciones">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Fecha</th>
                                <th>Gravedad</th>
                                <th>Motivo</th>
                                <th>Sanci&oacute;n</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($amonestaciones)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('check-circle', 'width:48px; height:48px; opacity:0.3;') ?>
                                            </div>
                                            <div class="empty-state-text">No hay amonestaciones registradas</div>
                                            <p style="font-size: 13px; color: #94A3B8; margin-top: 4px;">Todo el personal tiene historial limpio.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($amonestaciones as $a):
                                    $tipo = $a['tipo_falta'] ?? 'leve';
                                    $label = ucfirst(str_replace('_', ' ', $tipo));
                                    $anio_evento = date('Y', strtotime($a['fecha_evento']));
                                    $dept_data = strtolower($a['departamento'] ?? '');
                                ?>
                                    <tr class="amonestacion-row"
                                        data-search="<?= strtolower(htmlspecialchars($a['nombres'] . ' ' . $a['apellidos'] . ' ' . $a['cedula'])) ?>"
                                        data-gravedad="<?= htmlspecialchars($tipo) ?>"
                                        data-departamento="<?= htmlspecialchars($dept_data) ?>"
                                        data-anio="<?= $anio_evento ?>">
                                        <td>
                                            <div style="font-weight: 600; color: #1E293B;">
                                                <?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']) ?>
                                            </div>
                                            <small style="color: #64748B;"><?= htmlspecialchars($a['cedula']) ?></small>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($a['fecha_evento'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= htmlspecialchars($tipo) ?>"
                                                  style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                                <?= htmlspecialchars($label) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 13px; color: #334155;">
                                                <?= htmlspecialchars($a['motivo'] ?? '—') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 13px; color: #64748B;">
                                                <?= htmlspecialchars($a['sancion'] ?? '—') ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 8px;">
                                                <a href="../funcionarios/ver.php?id=<?= $a['funcionario_id'] ?>"
                                                   class="btn-icon" title="Ver Expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if ($a['ruta_archivo_pdf']): ?>
                                                    <a href="<?= APP_URL . '/' . $a['ruta_archivo_pdf'] ?>"
                                                       target="_blank"
                                                       class="btn-icon" title="Ver Acta PDF"
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
                    Mostrando <strong id="contadorVisible">0</strong> de <strong id="contadorTotal">0</strong> amonestaciones
                </div>
            </div>
        </div>
    </div>

    <script>
    const APP_URL = "<?= APP_URL ?>";

    // ===== FILTROS =====
    function aplicarFiltros() {
        const search   = document.getElementById('searchAmonestacion').value.toLowerCase().trim();
        const gravedad = document.getElementById('filter-gravedad').value;
        const depto    = document.getElementById('filter-departamento').value.toLowerCase();
        const anio     = document.getElementById('filter-anio').value;
        const rows     = document.querySelectorAll('.amonestacion-row');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch   = !search   || row.dataset.search.includes(search);
            const matchGravedad = !gravedad || row.dataset.gravedad === gravedad;
            const matchDepto    = !depto    || row.dataset.departamento.includes(depto);
            const matchAnio     = !anio     || row.dataset.anio === anio;
            const show = matchSearch && matchGravedad && matchDepto && matchAnio;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('contadorVisible').textContent = visible;
        document.getElementById('contadorTotal').textContent   = rows.length;
    }

    function limpiarFiltros() {
        document.getElementById('searchAmonestacion').value = '';
        document.getElementById('filter-gravedad').value    = '';
        document.getElementById('filter-departamento').value = '';
        document.getElementById('filter-anio').value        = '';
        aplicarFiltros();
    }

    document.getElementById('searchAmonestacion')?.addEventListener('input', aplicarFiltros);
    document.getElementById('filter-gravedad')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter-anio')?.addEventListener('change', aplicarFiltros);

    document.addEventListener('DOMContentLoaded', aplicarFiltros);

    // ===== MODAL REGISTRAR AMONESTACIÓN =====
    async function abrirModalAmonestacion() {
        try {
            Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const response = await fetch(`${APP_URL}/vistas/funcionarios/ajax/listar.php`);
            if (!response.ok) throw new Error('Error de conexión');
            const data = await response.json();
            if (!data.success) throw new Error('No se pudieron cargar los datos');

            const funcionarios = data.data.filter(f => f.estado === 'activo');
            Swal.close();

            const { value: formValues } = await Swal.fire({
                title: '<div style="display:flex;align-items:center;gap:10px;font-size:20px;font-weight:700;color:#1e293b"><svg width="24" height="24" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg><span>Registrar Amonestación</span></div>',
                width: '700px',
                html: `
                    <style>
                        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
                        .form-group{text-align:left;margin-bottom:16px}
                        .form-label{display:block;font-weight:600;margin-bottom:7px;color:#334155;font-size:13px}
                        .form-input,.form-select,.form-textarea{width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;transition:all .2s;font-family:inherit;box-sizing:border-box}
                        .form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#EF4444;outline:none;box-shadow:0 0 0 3px rgba(239,68,68,.1)}
                        .form-textarea{resize:vertical;min-height:70px}
                    </style>
                    <div style="max-width:650px;margin:0 auto;text-align:left">
                        <div class="form-group">
                            <label class="form-label">Funcionario <span style="color:#ef4444">*</span></label>
                            <select id="swal-funcionario" class="form-select">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `<option value="${f.id}">${f.nombres} ${f.apellidos} (${f.cedula})</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Fecha del Evento <span style="color:#ef4444">*</span></label>
                                <input type="date" id="swal-fecha" class="form-input" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gravedad de la Falta <span style="color:#ef4444">*</span></label>
                                <select id="swal-tipo" class="form-select">
                                    <option value="leve">Leve</option>
                                    <option value="grave">Grave</option>
                                    <option value="muy_grave">Muy Grave</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Motivo de la falta <span style="color:#ef4444">*</span></label>
                            <textarea id="swal-motivo" class="form-textarea" placeholder="Describa brevemente lo sucedido..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sanción Aplicada <span style="color:#ef4444">*</span></label>
                            <input type="text" id="swal-sancion" class="form-input" placeholder="Ej: Amonestación escrita, Suspensión de 3 días...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Acta de Amonestación (PDF) <span style="color:#ef4444">*</span></label>
                            <input type="file" id="swal-archivo" class="swal2-file" accept="application/pdf">
                            <div style="font-size:12px;color:#94A3B8;margin-top:5px;">El acta firmada es obligatoria.</div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Registrar Falta',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const func    = document.getElementById('swal-funcionario').value;
                    const fecha   = document.getElementById('swal-fecha').value;
                    const tipo    = document.getElementById('swal-tipo').value;
                    const motivo  = document.getElementById('swal-motivo').value.trim();
                    const sancion = document.getElementById('swal-sancion').value.trim();
                    const file    = document.getElementById('swal-archivo').files[0];

                    if (!func || !fecha || !tipo || !motivo || !sancion || !file) {
                        Swal.showValidationMessage('Todos los campos son obligatorios, incluyendo el archivo PDF.');
                        return false;
                    }
                    return { func, fecha, tipo, motivo, sancion, file };
                }
            });

            if (formValues) guardarAmonestacion(formValues);

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function guardarAmonestacion(datos) {
        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const formData = new FormData();
        formData.append('csrf_token', '<?= generarTokenCSRF() ?>');
        formData.append('accion', 'registrar_amonestacion');
        formData.append('funcionario_id', datos.func);
        formData.append('fecha_evento', datos.fecha);
        formData.append('tipo_falta', datos.tipo);
        formData.append('motivo', datos.motivo);
        formData.append('sancion', datos.sancion);
        formData.append('archivo_pdf', datos.file);

        try {
            const res  = await fetch(`${APP_URL}/vistas/funcionarios/ajax/gestionar_historial.php`, { method: 'POST', body: formData });
            if (!res.ok) {
                if (res.status === 403) throw new Error('Token de seguridad vencido. Recargue la página.');
                throw new Error(`Error del servidor: ${res.status}`);
            }
            const text = await res.text();
            let result;
            try { result = JSON.parse(text); }
            catch (e) { throw new Error('Respuesta inesperada del servidor.'); }

            if (result.success) {
                await Swal.fire({ icon: 'success', title: '¡Registrado!', text: 'La amonestación ha sido guardada correctamente.', confirmButtonColor: '#EF4444' });
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