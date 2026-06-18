<?php
/**
 * Vista: Módulo de Vacaciones
 * Gestión y consulta de vacaciones del personal
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder al módulo de vacaciones';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION'");
$total_empleados_con_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
$empleados_en_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_totales'))), 0) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$dias_usados_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND fecha_evento >= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())");
$vacaciones_programadas = $stmt->fetch()['total'];

$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener registros de vacaciones actualizando el estado dinámico basado en hoy
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        ha.fecha_evento as fecha_inicio,
        ha.fecha_fin,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.dias_totales')) as dias_totales,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.titulo')) as titulo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.descripcion')) as descripcion,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.nombre_archivo_original,
        ha.created_at,
        CASE 
            WHEN CURDATE() < ha.fecha_evento THEN 'Programada'
            WHEN CURDATE() >= ha.fecha_evento AND CURDATE() <= ha.fecha_fin THEN 'En curso'
            WHEN CURDATE() = ha.fecha_evento THEN 'Iniciando hoy'
            ELSE 'Finalizada'
        END as estado_vacacion
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'VACACION'
    ORDER BY ha.fecha_evento DESC
");
$vacaciones = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../../config/icons.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacaciones - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <link rel="stylesheet" href="../../publico/css/responsive.css">
    <link rel="stylesheet" href="../../publico/css/modern-components.css">
    <link rel="stylesheet" href="../../publico/css/swal-modern.css">
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $pageTitle = 'Gestión de Vacaciones';
        include __DIR__ . '/../layout/header.php'; 
        ?>
        
        <div class="module-container">
            <div class="module-header-title">
                <div class="module-title-group">
                    <?= Icon::get('sun') ?>
                    <h2 class="module-title-text">Vacaciones</h2>
                </div>
                <button class="btn-primary" onclick="abrirModalVacaciones()" style="padding: 10px 20px; border-radius: 8px;">
                    <?= Icon::get('plus') ?> Nueva Solicitud
                </button>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card-solid bg-solid-blue">
                    <div class="kpi-icon"><?= Icon::get('users') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Empleados Histórico</div>
                        <div class="kpi-value"><?= $total_empleados_con_vacaciones ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-green">
                    <div class="kpi-icon"><?= Icon::get('sun') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">De Vacaciones Hoy</div>
                        <div class="kpi-value"><?= $empleados_en_vacaciones ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-orange">
                    <div class="kpi-icon"><?= Icon::get('clock') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Días Usados (<?= date('Y') ?>)</div>
                        <div class="kpi-value"><?= $dias_usados_anio ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-purple">
                    <div class="kpi-icon"><?= Icon::get('calendar') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Programadas</div>
                        <div class="kpi-value"><?= $vacaciones_programadas ?></div>
                    </div>
                </div>
            </div>

            <div class="flat-filter-bar" style="margin-top: 32px;">
                <div class="filter-item" style="flex: 2;">
                    <label class="filter-label">BUSCAR</label>
                    <input type="text" id="searchVacaciones" class="form-control" placeholder="Buscar funcionario...">
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">DEPARTAMENTO</label>
                    <select id="filter-departamento" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($departamentos as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['nombre']); ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">ESTADO</label>
                    <select id="filter-estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="Programada">Programada</option>
                        <option value="En curso">En curso</option>
                        <option value="Finalizada">Finalizada</option>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1;">
                    <label class="filter-label">AÑO</label>
                    <select id="filter-anio" class="form-control">
                        <option value="">Todos</option>
                        <?php 
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--): 
                        ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 0 0 auto;">
                    <button type="button" onclick="limpiarFiltros()" class="btn-flat-outline">Limpiar</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table-modern" id="vacacionesTable">
                    <thead>
                        <tr>
                            <th>Funcionario</th>
                            <th>Cédula</th>
                            <th>Departamento</th>
                            <th>Período</th>
                            <th>Días</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vacaciones)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-st">
                                        <div class="empty-st-ico"><?= Icon::get('sun') ?></div>
                                        <p class="empty-state-title">Sin registros de vacaciones</p>
                                        <p class="empty-state-desc">Las vacaciones registradas aparecerán aquí.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vacaciones as $vac):
                                $nombre_completo = $vac['nombres'] . ' ' . $vac['apellidos'];
                                $iniciales = strtoupper(mb_substr($vac['nombres'],0,1) . mb_substr($vac['apellidos'],0,1));
                                $colors = ['#0F4C81','#0288D1','#8B5CF6','#10B981','#F59E0B','#14B8A6'];
                                $color = $colors[abs(crc32($vac['cedula'])) % count($colors)];
                                $badgeStyle = match($vac['estado_vacacion']) {
                                    'En curso'       => 'background:#D1FAE5;color:#065F46;',
                                    'Iniciando hoy'  => 'background:#DBEAFE;color:#1E40AF;',
                                    'Programada'     => 'background:#FEF3C7;color:#92400E;',
                                    default      => 'background:#F1F5F9;color:#475569;'
                                };
                            ?>
                                <tr class="vacacion-row"
                                    data-empleado="<?php echo strtolower($nombre_completo . ' ' . $vac['cedula']); ?>"
                                    data-departamento="<?php echo $vac['departamento']; ?>"
                                    data-estado="<?php echo $vac['estado_vacacion']; ?>"
                                    data-anio="<?php echo date('Y', strtotime($vac['fecha_inicio'])); ?>">
                                    <td>
                                        <div class="fn-cell">
                                            <div class="fn-avatar" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc)">
                                                <?= $iniciales ?>
                                            </div>
                                            <div class="fn-info">
                                                <strong><?php echo htmlspecialchars($nombre_completo); ?></strong>
                                                <small><span class="fn-cedula"><?php echo htmlspecialchars($vac['cedula']); ?></span></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size:13px;color:#64748B;"><?php echo htmlspecialchars($vac['departamento']); ?></td>
                                    <td>
                                        <span style="font-size:13px;white-space:nowrap;">
                                            <?php echo date('d/m/Y', strtotime($vac['fecha_inicio'])); ?>
                                            <span style="color:#CBD5E1;margin:0 3px;">→</span>
                                            <?php echo date('d/m/Y', strtotime($vac['fecha_fin'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background:#F1F5F9;padding:3px 9px;border-radius:6px;font-weight:700;font-size:12px;color:#475569;white-space:nowrap;">
                                            <?php echo $vac['dias_totales']; ?>d
                                        </span>
                                    </td>
                                    <td>
                                        <span style="<?= $badgeStyle ?>padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;white-space:nowrap;">
                                            <?php echo $vac['estado_vacacion']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="tbl-actions">
                                            <a href="../funcionarios/ver.php?id=<?php echo $vac['funcionario_id']; ?>"
                                               class="btn-ic ic-view" title="Ver Expediente">
                                                <?= Icon::get('eye') ?>
                                            </a>
                                            <?php if ($vac['ruta_archivo']): ?>
                                                <a href="../../<?php echo $vac['ruta_archivo']; ?>" target="_blank"
                                                   class="btn-ic ic-pdf" title="Descargar PDF">
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
        </div>
    </div>
    
    <script src="../../publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        document.getElementById('searchVacaciones')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#vacacionesTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
        
        function limpiarFiltros() {
            document.getElementById('searchVacaciones').value = '';
            document.getElementById('filter-departamento').value = '';
            document.getElementById('filter-estado').value = '';
            document.getElementById('filter-anio').value = '';
            aplicarFiltros();
        }
        
        function aplicarFiltros() {
            const search = document.getElementById('searchVacaciones').value.toLowerCase();
            const dept = document.getElementById('filter-departamento').value;
            const est = document.getElementById('filter-estado').value;
            const anio = document.getElementById('filter-anio').value;
            
            document.querySelectorAll('.vacacion-row').forEach(row => {
                const matchSearch = row.dataset.empleado.includes(search);
                const matchDept = !dept || row.dataset.departamento === dept;
                const matchEstado = !est || row.dataset.estado === est;
                const matchAnio = !anio || row.dataset.anio === anio;
                row.style.display = (matchSearch && matchDept && matchEstado && matchAnio) ? '' : 'none';
            });
        }
        
        document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-estado')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-anio')?.addEventListener('change', aplicarFiltros);

        // ══════════════════════════════════════════════════════════════════════
        // MODAL NUEVA VACACIÓN — SISTEMA DE PERÍODOS LOTTT
        // ══════════════════════════════════════════════════════════════════════
        async function abrirModalVacaciones() {
            try {
                const res = await fetch('../funcionarios/ajax/listar.php');
                const result = await res.json();
                const funcionarios = result.data.filter(f => f.estado === 'activo');

                const { value: formValues } = await Swal.fire({
                    title: '<div style="display:flex;align-items:center;gap:12px;justify-content:center;font-size:22px;font-weight:700;color:#1e293b;"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span>Nueva Vacación</span></div>',
                    html: `
                        <style>
                            .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
                            .file-input-button { width: 100%; padding: 11px 14px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 9px; font-weight: 500; color: #475569; font-size: 13px; box-sizing: border-box; }
                            .file-input-button:hover { border-color: #0F4C81; background: #eff6ff; color: #0F4C81; }
                            .file-input-button.has-file { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-color: #06d6a0; color: #059669; }
                            .file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
                            .error-message { background: #fee2e2; border-left: 3px solid #ef4444; padding: 11px 14px; border-radius: 8px; margin-bottom: 14px; display: none; color: #991b1b; font-size: 12px; font-weight: 500; }
                            .lottt-card { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 2px solid #06d6a0; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: none; }
                            .lottt-title { font-size: 12px; color: #047857; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px; }
                            .lottt-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
                            .lottt-stat { text-align: center; }
                            .lottt-stat-label { font-size: 10px; color: #059669; font-weight: 600; margin-bottom: 3px; }
                            .lottt-stat-value { font-size: 26px; font-weight: 800; color: #047857; }
                            /* Períodos */
                            .periodos-container { display: none; margin-bottom: 16px; }
                            .periodos-title { font-size: 12px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; text-align: left; }
                            .periodo-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; margin-bottom: 8px; border: 2px solid #e2e8f0; background: #f8fafc; cursor: pointer; transition: all 0.2s; text-align: left; }
                            .periodo-item:hover { border-color: #06d6a0; background: #f0fdf4; }
                            .periodo-item.selected { border-color: #06d6a0; background: linear-gradient(135deg,#ecfdf5,#d1fae5); }
                            .periodo-item.tomado { opacity: .5; cursor: not-allowed; background: #f1f5f9; border-color: #e2e8f0; }
                            .periodo-checkbox { width: 18px; height: 18px; border-radius: 5px; border: 2px solid #cbd5e1; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
                            .periodo-item.selected .periodo-checkbox { background: #06d6a0; border-color: #06d6a0; }
                            .periodo-item.selected .periodo-checkbox::after { content: '✓'; color: white; font-size: 11px; font-weight: 700; }
                            .periodo-info { flex: 1; }
                            .periodo-label { font-size: 13px; font-weight: 700; color: #1e293b; }
                            .periodo-dias { font-size: 11px; color: #64748b; margin-top: 2px; }
                            .periodo-badge { padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 700; white-space: nowrap; }
                            .badge-disponible { background: #dcfce7; color: #166534; }
                            .badge-tomado { background: #f1f5f9; color: #94a3b8; }
                            /* Resumen selección */
                            .resumen-seleccion { background: linear-gradient(135deg,#eff6ff,#dbeafe); border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px 16px; margin-bottom: 14px; display: none; }
                            .resumen-text { font-size: 13px; color: #1e40af; font-weight: 600; }
                            /* Fecha retorno */
                            .return-date-card { background: linear-gradient(135deg, #eff6ff, #dbeafe); border-left: 3px solid #3b82f6; padding: 12px 14px; border-radius: 8px; margin-bottom: 14px; display: none; }
                            .return-date-content { color: #1e40af; font-size: 13px; font-weight: 600; }
                        </style>
                        <div style="max-width:750px;margin:0 auto;text-align:left;">
                            <!-- Funcionario -->
                            <div class="swal-form-group" style="margin-bottom:14px;">
                                <label class="swal-label">Funcionario <span style="color:#ef4444">*</span></label>
                                <select id="swal-funcionario" class="swal2-select">
                                    <option value="">Seleccionar...</option>
                                    ${funcionarios.map(f => `<option value="${f.id}">${f.nombres} ${f.apellidos} - ${f.cedula}</option>`).join('')}
                                </select>
                            </div>

                            <div id="error-message" class="error-message"></div>

                            <!-- Card resumen LOTTT -->
                            <div id="lottt-card" class="lottt-card">
                                <div class="lottt-title">Derecho Vacacional (LOTTT)</div>
                                <div class="lottt-stats">
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Años Servicio</div>
                                        <div class="lottt-stat-value" id="lbl-anios">-</div>
                                    </div>
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Períodos Totales</div>
                                        <div class="lottt-stat-value" id="lbl-periodos-total">-</div>
                                    </div>
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Disponibles</div>
                                        <div class="lottt-stat-value" id="lbl-periodos-disp">-</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de períodos -->
                            <div id="periodos-container" class="periodos-container">
                                <div class="periodos-title">Seleccione el o los períodos a tomar</div>
                                <div id="periodos-list"></div>
                            </div>

                            <!-- Resumen de selección -->
                            <div id="resumen-seleccion" class="resumen-seleccion">
                                <div class="resumen-text" id="resumen-text">-</div>
                            </div>

                            <!-- Fecha inicio -->
                            <div class="swal-form-group" style="margin-bottom:14px; display:none;" id="fecha-group">
                                <label class="swal-label">Fecha de Inicio <span style="color:#ef4444">*</span></label>
                                <input type="date" id="swal-fecha-inicio" class="swal2-input" value="${new Date().toISOString().split('T')[0]}">
                            </div>

                            <!-- Fecha retorno calculada -->
                            <div id="return-date-card" class="return-date-card">
                                <div class="return-date-content">
                                    Fecha estimada de retorno: <strong id="fecha-retorno-display">-</strong>
                                </div>
                            </div>

                            <!-- Observaciones + Documento -->
                            <div class="swal-form-grid-2col" style="display:none;" id="docs-group">
                                <div class="swal-form-group">
                                    <label class="swal-label">Observaciones</label>
                                    <textarea id="swal-observaciones" class="swal2-textarea" placeholder="Motivo o comentarios..." style="min-height:80px;"></textarea>
                                </div>
                                <div class="swal-form-group">
                                    <label class="swal-label">Documento Aval <span style="color:#ef4444">*</span></label>
                                    <div class="file-input-wrapper">
                                        <label class="file-input-button" id="file-label" for="swal-pdf">
                                            <span id="file-label-text">Seleccionar archivo</span>
                                        </label>
                                        <input type="file" id="swal-pdf" accept="application/pdf,image/png,image/jpeg,image/jpg">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '860px',
                    showCancelButton: true,
                    confirmButtonText: 'Registrar Vacación',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#06d6a0',
                    cancelButtonColor: '#64748b',
                    didOpen: () => {
                        const funcionarioSelect = document.getElementById('swal-funcionario');
                        const errorMsg      = document.getElementById('error-message');
                        const lotttCard     = document.getElementById('lottt-card');
                        const periodosContainer = document.getElementById('periodos-container');
                        const periodosList  = document.getElementById('periodos-list');
                        const resumenEl     = document.getElementById('resumen-seleccion');
                        const resumenText   = document.getElementById('resumen-text');
                        const fechaGroup    = document.getElementById('fecha-group');
                        const docsGroup     = document.getElementById('docs-group');
                        const fechaInput    = document.getElementById('swal-fecha-inicio');
                        const returnCard    = document.getElementById('return-date-card');
                        const fileInput     = document.getElementById('swal-pdf');
                        const fileLabelText = document.getElementById('file-label-text');

                        let periodosData    = [];    // todos los períodos del funcionario
                        let seleccionados   = new Set(); // año de períodos seleccionados

                        // ─── Cargar períodos al cambiar funcionario ───────────
                        funcionarioSelect.addEventListener('change', async (e) => {
                            if (!e.target.value) return;
                            lotttCard.style.display = 'none';
                            periodosContainer.style.display = 'none';
                            resumenEl.style.display = 'none';
                            fechaGroup.style.display = 'none';
                            docsGroup.style.display = 'none';
                            returnCard.style.display = 'none';
                            errorMsg.style.display = 'none';
                            seleccionados.clear();

                            const res  = await fetch(`ajax/calcular_dias_vacaciones.php?funcionario_id=${e.target.value}`);
                            const data = await res.json();

                            if (!data.success) {
                                errorMsg.textContent = '⚠️ Error al consultar datos del funcionario.';
                                errorMsg.style.display = 'block';
                                return;
                            }

                            if (!data.cumple_requisito) {
                                errorMsg.textContent = '⚠️ El funcionario no cumple 1 año de servicio (requisito LOTTT).';
                                errorMsg.style.display = 'block';
                                return;
                            }

                            periodosData = data.data.periodos_totales;
                            const disponibles = data.data.periodos_disponibles;

                            // Actualizar card LOTTT
                            document.getElementById('lbl-anios').textContent        = data.data.años_servicio;
                            document.getElementById('lbl-periodos-total').textContent = periodosData.length;
                            document.getElementById('lbl-periodos-disp').textContent  = disponibles;
                            lotttCard.style.display = 'block';

                            if (disponibles === 0) {
                                errorMsg.textContent = '⚠️ Este funcionario no tiene períodos vacacionales disponibles.';
                                errorMsg.style.display = 'block';
                                return;
                            }

                            // Renderizar lista de períodos
                            periodosList.innerHTML = periodosData.map(p => {
                                if (p.tomado) {
                                    const fi = p.fecha_inicio ? new Date(p.fecha_inicio + 'T00:00:00').toLocaleDateString('es-VE') : '';
                                    const ff = p.fecha_fin   ? new Date(p.fecha_fin   + 'T00:00:00').toLocaleDateString('es-VE') : '';
                                    return `
                                      <div class="periodo-item tomado">
                                        <div class="periodo-checkbox"></div>
                                        <div class="periodo-info">
                                          <div class="periodo-label">Período Año ${p.año}</div>
                                          <div class="periodo-dias">${p.dias} días hábiles • Tomado: ${fi} → ${ff}</div>
                                        </div>
                                        <span class="periodo-badge badge-tomado">Tomado</span>
                                      </div>`;
                                }
                                return `
                                  <div class="periodo-item" data-anio="${p.año}" data-dias="${p.dias}" onclick="togglePeriodo(this)">
                                    <div class="periodo-checkbox"></div>
                                    <div class="periodo-info">
                                      <div class="periodo-label">Período Año ${p.año}</div>
                                      <div class="periodo-dias">${p.dias} días hábiles</div>
                                    </div>
                                    <span class="periodo-badge badge-disponible">Disponible</span>
                                  </div>`;
                            }).join('');

                            periodosContainer.style.display = 'block';
                        });

                        // ─── Toggle selección de período ─────────────────────
                        window.togglePeriodo = function(el) {
                            const anio = parseInt(el.dataset.anio);
                            if (seleccionados.has(anio)) {
                                seleccionados.delete(anio);
                                el.classList.remove('selected');
                            } else {
                                seleccionados.add(anio);
                                el.classList.add('selected');
                            }
                            actualizarResumen();
                        };

                        function actualizarResumen() {
                            if (seleccionados.size === 0) {
                                resumenEl.style.display = 'none';
                                fechaGroup.style.display = 'none';
                                docsGroup.style.display = 'none';
                                returnCard.style.display = 'none';
                                return;
                            }
                            const sorted = [...seleccionados].sort((a,b) => a-b);
                            const totalDias = sorted.reduce((acc, anio) => {
                                const p = periodosData.find(x => x.año === anio);
                                return acc + (p ? p.dias : 0);
                            }, 0);
                            resumenText.innerHTML = `✅ Seleccionado${sorted.length>1?'s':''}: ${sorted.length} período${sorted.length>1?'s':''} (Año${sorted.length>1?'s':''} ${sorted.join(', ')}) — <strong>${totalDias} días hábiles totales</strong>`;
                            resumenEl.style.display = 'block';
                            fechaGroup.style.display = 'block';
                            docsGroup.style.display = 'grid';
                            calcularRetorno();
                        }

                        // ─── Calcular fecha de retorno ────────────────────────
                        async function calcularRetorno() {
                            if (seleccionados.size === 0 || !fechaInput.value) {
                                returnCard.style.display = 'none';
                                return;
                            }
                            const sorted = [...seleccionados].sort((a,b) => a-b);
                            const totalDias = sorted.reduce((acc, anio) => {
                                const p = periodosData.find(x => x.año === anio);
                                return acc + (p ? p.dias : 0);
                            }, 0);
                            const res = await fetch(`ajax/calcular_fecha_retorno.php?fecha_inicio=${fechaInput.value}&dias_habiles=${totalDias}`);
                            const data = await res.json();
                            if (data.success) {
                                document.getElementById('fecha-retorno-display').textContent = data.data.fecha_retorno_formateada;
                                returnCard.style.display = 'block';
                            }
                        }

                        fechaInput.addEventListener('change', calcularRetorno);

                        // ─── Archivo ──────────────────────────────────────────
                        fileInput.addEventListener('change', (e) => {
                            if (e.target.files.length > 0) {
                                fileLabelText.textContent = e.target.files[0].name;
                                document.getElementById('file-label').classList.add('has-file');
                            }
                        });
                    },
                    preConfirm: () => {
                        const fid       = document.getElementById('swal-funcionario').value;
                        const fecha     = document.getElementById('swal-fecha-inicio').value;
                        const obs       = document.getElementById('swal-observaciones').value;
                        const archivo   = document.getElementById('swal-pdf').files[0];
                        const periodos  = [...(window._seleccionados || [])];

                        // Leer selección desde los elementos DOM (más fiable)
                        const selItems = document.querySelectorAll('.periodo-item.selected');
                        const selAnios = [...selItems].map(el => parseInt(el.dataset.anio));

                        if (!fid) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                        if (selAnios.length === 0) { Swal.showValidationMessage('Seleccione al menos un período vacacional'); return false; }
                        if (!fecha) { Swal.showValidationMessage('Ingrese la fecha de inicio'); return false; }
                        if (!archivo) { Swal.showValidationMessage('Adjunte el documento de aval'); return false; }

                        return { funcionario_id: fid, fecha_inicio: fecha, periodos_años: selAnios, observaciones: obs, archivo_pdf: archivo };
                    }
                });

                if (formValues) {
                    const formData = new FormData();
                    formData.append('accion', 'registrar_vacacion');
                    formData.append('funcionario_id', formValues.funcionario_id);
                    formData.append('fecha_evento', formValues.fecha_inicio);
                    formData.append('periodos_años', JSON.stringify(formValues.periodos_años));
                    formData.append('observaciones', formValues.observaciones);
                    formData.append('archivo_pdf', formValues.archivo_pdf);
                    formData.append('csrf_token', '<?= generarTokenCSRF() ?>');

                    const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                    const result   = await response.json();

                    if (result.success) {
                        Swal.fire({ icon: 'success', title: '¡Listo!', text: result.message }).then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: result.error });
                    }
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
            }
        }
    </script>
</body>
</html>