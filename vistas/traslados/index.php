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
$stmtDeps = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre ASC");
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
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">

    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>

    <style>
        /* Flecha de cambio de departamento */
        .dept-change { display: flex; align-items: center; gap: 8px; font-size: 13px; flex-wrap: wrap; }
        .dept-arrow { color: #94A3B8; font-size: 14px; font-weight: 700; }
        .dept-old { color: #64748B; text-decoration: line-through; opacity: 0.75; font-size: 12px; }
        .dept-new { color: #0F4C81; font-weight: 600; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php 
        $pageTitle = 'Traslados';
        include __DIR__ . '/../layout/header.php'; 
        ?>

        <div class="module-container">
            <!-- Header Título y Botón -->
            <div class="module-header-title">
                <div class="module-title-group">
                    <?= Icon::get('refresh-cw') ?>
                    <h2 class="module-title-text">Traslados</h2>
                </div>
                <button class="btn-primary" onclick="abrirModalTraslado()" style="padding: 10px 20px; border-radius: 8px;">
                    <?= Icon::get('plus') ?> Registrar Traslado
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card-solid bg-solid-purple">
                    <div class="kpi-icon">
                        <?= Icon::get('refresh-cw') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">Traslados este Mes</div>
                        <div class="kpi-value"><?= number_format($traslados_mes) ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-orange">
                    <div class="kpi-icon">
                        <?= Icon::get('calendar') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">Traslados este Año</div>
                        <div class="kpi-value"><?= number_format($traslados_anio) ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-cyan">
                    <div class="kpi-icon">
                        <?= Icon::get('file-text') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">Total Histórico</div>
                        <div class="kpi-value"><?= number_format($total_historico) ?></div>
                    </div>
                </div>
            </div>

            <!-- Filtros Flat -->
            <div class="flat-filter-bar" style="margin-top: 32px;">
                <div class="filter-item" style="flex: 2;">
                    <label class="filter-label">BUSCAR</label>
                    <input type="text" id="searchTraslado" class="form-control" placeholder="Buscar funcionario, cédula...">
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">DEPARTAMENTO DESTINO</label>
                    <select id="filter-departamento" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($departamentos as $dep): ?>
                            <option value="<?= htmlspecialchars(strtolower($dep['nombre'])) ?>">
                                <?= htmlspecialchars($dep['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1;">
                    <label class="filter-label">AÑO</label>
                    <select id="filter-anio" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($anios_disponibles as $anio): ?>
                            <option value="<?= $anio ?>"><?= $anio ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 0 0 auto;">
                    <button type="button" onclick="limpiarFiltros()" class="btn-flat-outline">
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="table-wrapper">
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
                                        <div class="empty-st">
                                            <div class="empty-st-ico">
                                                <?= Icon::get('briefcase') ?>
                                            </div>
                                            <p class="empty-state-title">Sin traslados registrados</p>
                                            <p class="empty-state-desc">Aún no se han registrado traslados en el sistema.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($traslados as $t):
                                    $detalles   = json_decode($t['detalles'], true) ?? [];
                                    $dep_origen = $detalles['departamento_origen'] ?? $detalles['departamento_anterior'] ?? 'N/A';
                                    $dep_destino = $detalles['departamento_destino'] ?? $detalles['departamento_nuevo'] ?? 'N/A';
                                    $motivo     = $detalles['motivo'] ?? $detalles['observaciones'] ?? '—';
                                    $anio_evento = date('Y', strtotime($t['fecha_evento']));
                                    $nombre_completo = $t['nombres'] . ' ' . $t['apellidos'];
                                    $iniciales = strtoupper(mb_substr($t['nombres'],0,1) . mb_substr($t['apellidos'],0,1));
                                    $colors = ['#0F4C81','#0288D1','#8B5CF6','#10B981','#F59E0B','#14B8A6'];
                                    $color = $colors[abs(crc32($t['cedula'])) % count($colors)];
                                ?>
                                    <tr class="traslado-row"
                                        data-search="<?= strtolower(htmlspecialchars($nombre_completo . ' ' . $t['cedula'])) ?>"
                                        data-departamento="<?= strtolower(htmlspecialchars($dep_destino)) ?>"
                                        data-anio="<?= $anio_evento ?>">
                                        <td>
                                            <div class="fn-cell">
                                                <div class="fn-avatar" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc)">
                                                    <?= $iniciales ?>
                                                </div>
                                                <div class="fn-info">
                                                    <strong><?= htmlspecialchars($nombre_completo) ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="fn-cedula"><?= htmlspecialchars($t['cedula']) ?></span></td>
                                        <td>
                                            <div class="dept-flow">
                                                <span class="dept-from"><?= htmlspecialchars($dep_origen) ?></span>
                                                <span class="dept-arr">→</span>
                                                <span class="dept-to"><?= htmlspecialchars($dep_destino) ?></span>
                                            </div>
                                        </td>
                                        <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($t['fecha_evento'])) ?></td>
                                        <td>
                                            <span style="font-size:12.5px;color:#64748B;">
                                                <?= htmlspecialchars(mb_substr($motivo,0,60)) ?><?= strlen($motivo)>60?'…':'' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="tbl-actions">
                                                <a href="../funcionarios/ver.php?id=<?= $t['funcionario_id'] ?>"
                                                   class="btn-ic ic-view" title="Ver Expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if ($t['ruta_archivo_pdf']): ?>
                                                    <a href="<?= APP_URL . '/' . $t['ruta_archivo_pdf'] ?>"
                                                       target="_blank"
                                                       class="btn-ic ic-pdf" title="Ver Resolución PDF">
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
            </div> <!-- End Table Wrapper -->
            
            <!-- Contador -->
            <div class="tbl-foot" style="padding: 12px 16px; border-top: 1px solid var(--color-border-light); font-size: 13px; color: var(--color-text-light);">
                Mostrando <strong id="contadorVisible">0</strong> de <strong id="contadorTotal">0</strong> traslados
            </div>
        </div> <!-- End Module Container -->
    </div> <!-- End Main Content -->

    <script>
    if (typeof APP_URL === 'undefined') { var APP_URL = "<?= APP_URL ?>"; }
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

    /** Actualiza el label del file-input moderno en el modal Traslado */
    function updateFileNameTraslado(input) {
        const label = input.parentElement.querySelector('.file-input-label');
        const span  = label ? label.querySelector('.file-name') : null;
        if (span && input.files && input.files[0]) {
            const f = input.files[0];
            span.textContent = f.name.length > 35 ? f.name.substring(0, 32) + '...' : f.name;
            label.classList.add('has-file');
        }
    }

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
                title: '<div style="display:flex;align-items:center;gap:10px;font-size:20px;font-weight:700;color:#1e293b"><?= Icon::get("repeat") ?><span>Registrar Traslado</span></div>',
                width: '680px',
                customClass: { popup: 'swal-modern-popup' },
                html: `
                    <div class="swal-form-grid" style="margin-bottom:20px">
                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('user') ?>
                                Funcionario
                            </label>
                            <select id="swal-funcionario" class="swal2-select">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `<option value="${f.id}" data-dept="${f.departamento_nombre || 'No definido'}">${f.nombres} ${f.apellidos} (${f.cedula})</option>`).join('')}
                            </select>
                        </div>
                        <div class="swal-hint show" id="dept-actual-preview" style="display:flex;align-items:center;gap:8px;">
                            <?= Icon::get('building') ?>
                            <span>Dept. Actual: <strong id="dept-actual-text">— Seleccione un funcionario —</strong></span>
                        </div>
                    </div>

                    <div class="swal-form-grid-2col" style="margin-bottom:20px">
                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('arrow-right') ?>
                                Nuevo Departamento
                            </label>
                            <select id="swal-dept-nuevo" class="swal2-select">
                                <option value="">Seleccione destino...</option>
                                ${deptOptions}
                            </select>
                        </div>
                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('calendar') ?>
                                Fecha Efectiva
                            </label>
                            <input type="date" id="swal-fecha" class="swal2-input" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="swal-form-grid" style="margin-bottom:20px">
                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('file-text') ?>
                                Motivo / Resolución
                            </label>
                            <input type="text" id="swal-motivo" class="swal2-input" placeholder="Ej: Resolución RRHH-2024-001">
                        </div>
                        <div class="swal-form-group">
                            <label class="swal-label">
                                <?= Icon::get('upload') ?>
                                Orden de Traslado (PDF — opcional)
                            </label>
                            <div class="file-input-modern">
                                <input type="file" id="swal-archivo" accept="application/pdf" class="file-input-hidden" onchange="updateFileNameTraslado(this)">
                                <label for="swal-archivo" class="file-input-label">
                                    <?= Icon::get('upload') ?>
                                    <span class="file-name">Seleccionar PDF...</span>
                                </label>
                            </div>
                            <small class="swal-helper">Máximo 5 MB</small>
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