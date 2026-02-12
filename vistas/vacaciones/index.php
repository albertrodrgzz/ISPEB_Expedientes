<?php
/**
 * Vista: Módulo de Vacaciones
 * Gestión y consulta de vacaciones del personal
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1 y 2)
verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder al módulo de vacaciones';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas para el dashboard de vacaciones
$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION'");
$total_empleados_con_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
$empleados_en_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_totales'))), 0) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$dias_usados_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND fecha_evento > CURDATE()");
$vacaciones_programadas = $stmt->fetch()['total'];

// Obtener departamentos para filtros
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener registros de vacaciones con información del funcionario
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
            WHEN ha.fecha_evento > CURDATE() THEN 'Programada'
            WHEN ha.fecha_fin < CURDATE() THEN 'Finalizada'
            ELSE 'En curso'
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
    
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <link rel="stylesheet" href="../../publico/css/modern-components.css">
    <link rel="stylesheet" href="../../publico/css/swal-modern.css">
    
    <style>
        /* Estilos KPI Estandarizados (Estilo Nombramientos) */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s ease;
        }
        
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        
        .kpi-icon {
            width: 56px; height: 56px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 24px; flex-shrink: 0;
        }
        
        /* Gradientes Estándar */
        .gradient-blue { background: linear-gradient(135deg, #0F4C81, #00A8E8); }
        .gradient-green { background: linear-gradient(135deg, #10B981, #34D399); }
        .gradient-orange { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
        .gradient-purple { background: linear-gradient(135deg, #8B5CF6, #A78BFA); }
        
        .kpi-content { display: flex; flex-direction: column; }
        .kpi-value { font-size: 28px; font-weight: 700; color: #1E293B; line-height: 1.2; }
        .kpi-label { font-size: 13px; color: #64748B; font-weight: 600; text-transform: uppercase; margin-top: 4px; }

        /* Filtros dentro de la Card */
        .filter-toolbar {
            display: flex;
            gap: 15px;
            padding: 20px;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            flex-wrap: wrap;
        }
        .filter-item { flex: 1; min-width: 200px; }
        .filter-label { display: block; font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 5px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <div class="header-title">
                    <h1>Gestión de Vacaciones</h1>
                </div>
                <button class="btn-primary" onclick="abrirModalVacaciones()">
                    <?= Icon::get('plus') ?>
                    Nueva Solicitud
                </button>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon gradient-blue">
                        <?= Icon::get('users') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $total_empleados_con_vacaciones ?></span>
                        <span class="kpi-label">Empleados Histórico</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-green">
                        <?= Icon::get('sun') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $empleados_en_vacaciones ?></span>
                        <span class="kpi-label">De Vacaciones Hoy</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-orange">
                        <?= Icon::get('clock') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $dias_usados_anio ?></span>
                        <span class="kpi-label">Días Usados (<?= date('Y') ?>)</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-purple">
                        <?= Icon::get('calendar') ?>
                    </div>
                    <div class="kpi-content">
                        <span class="kpi-value"><?= $vacaciones_programadas ?></span>
                        <span class="kpi-label">Programadas</span>
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="filter-toolbar">
                    <div class="filter-item">
                        <label class="filter-label">Buscar</label>
                        <input type="text" id="searchVacaciones" class="form-control" placeholder="Buscar funcionario...">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Departamento</label>
                        <select id="filter-departamento" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Estado</label>
                        <select id="filter-estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="Programada">Programada</option>
                            <option value="En curso">En curso</option>
                            <option value="Finalizada">Finalizada</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Año</label>
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
                    <div class="filter-item" style="display: flex; align-items: flex-end;">
                        <button type="button" onclick="limpiarFiltros()" class="btn-secondary" style="width: 100%;">
                            <?= Icon::get('rotate-ccw') ?> Limpiar
                        </button>
                    </div>
                </div>

                <div class="table-container">
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
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('sun', 'width:48px; height:48px; opacity:0.3;') ?>
                                            </div>
                                            <div class="empty-state-text">No hay registros de vacaciones</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vacaciones as $vac): ?>
                                    <tr class="vacacion-row" 
                                        data-empleado="<?php echo strtolower($vac['nombres'] . ' ' . $vac['apellidos'] . ' ' . $vac['cedula']); ?>"
                                        data-departamento="<?php echo $vac['departamento']; ?>"
                                        data-estado="<?php echo $vac['estado_vacacion']; ?>"
                                        data-anio="<?php echo date('Y', strtotime($vac['fecha_inicio'])); ?>">
                                        <td>
                                            <div style="font-weight: 600; color: #1E293B;">
                                                <?php echo htmlspecialchars($vac['nombres'] . ' ' . $vac['apellidos']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($vac['cedula']); ?></td>
                                        <td><span style="font-size: 13px; color: #64748B;"><?php echo htmlspecialchars($vac['departamento']); ?></span></td>
                                        <td>
                                            <div style="font-size: 13px;">
                                                <?php echo date('d/m/Y', strtotime($vac['fecha_inicio'])); ?> 
                                                <span style="color: #94A3B8;">➜</span> 
                                                <?php echo date('d/m/Y', strtotime($vac['fecha_fin'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: #F1F5F9; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; color: #475569;">
                                                <?php echo $vac['dias_totales']; ?> días
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = 'badge-info'; 
                                            if ($vac['estado_vacacion'] == 'Finalizada') $badgeClass = 'badge-secondary';
                                            if ($vac['estado_vacacion'] == 'En curso') $badgeClass = 'badge-success';
                                            if ($vac['estado_vacacion'] == 'Programada') $badgeClass = 'badge-warning';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo $vac['estado_vacacion']; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                <a href="../funcionarios/ver.php?id=<?php echo $vac['funcionario_id']; ?>" 
                                                   class="btn-icon" 
                                                   title="Ver Expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if ($vac['ruta_archivo']): ?>
                                                    <a href="../../<?php echo $vac['ruta_archivo']; ?>" 
                                                       target="_blank" 
                                                       class="btn-icon" 
                                                       style="color: #EF4444;"
                                                       title="Descargar PDF">
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
    </div>
    
    <script src="../../publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    
    <script>
        // Real-time search filter for vacaciones
        document.getElementById('searchVacaciones')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#vacacionesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Limpiar filtros
        function limpiarFiltros() {
            document.getElementById('searchVacaciones').value = '';
            document.getElementById('filter-departamento').value = '';
            document.getElementById('filter-estado').value = '';
            document.getElementById('filter-anio').value = '';
            aplicarFiltros();
        }
        
        // Aplicar filtros combinados
        function aplicarFiltros() {
            const searchTerm = document.getElementById('searchVacaciones').value.toLowerCase();
            const departamento = document.getElementById('filter-departamento').value;
            const estado = document.getElementById('filter-estado').value;
            const anio = document.getElementById('filter-anio').value;
            const rows = document.querySelectorAll('.vacacion-row');
            
            rows.forEach(row => {
                const matchSearch = row.dataset.empleado.includes(searchTerm);
                const matchDept = !departamento || row.dataset.departamento === departamento;
                const matchEstado = !estado || row.dataset.estado === estado;
                const matchAnio = !anio || row.dataset.anio === anio;
                
                row.style.display = (matchSearch && matchDept && matchEstado && matchAnio) ? '' : 'none';
            });
        }
        
        document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-estado')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-anio')?.addEventListener('change', aplicarFiltros);
        
        // ===========================================
        // MODAL REGISTRO DE VACACIONES
        // ===========================================
        
        // Calcular días hábiles (lunes a viernes)
        function calcularDiasHabiles(fechaInicio, fechaFin) {
            if (!fechaInicio || !fechaFin) return 0;
            
            const inicio = new Date(fechaInicio + 'T00:00:00');
            const fin = new Date(fechaFin + 'T00:00:00');
            
            if (fin < inicio) return 0;
            
            let count = 0;
            let current = new Date(inicio);
            
            while (current <= fin) {
                const dayOfWeek = current.getDay();
                // 0 = domingo, 6 = sábado
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    count++;
                }
                current.setDate(current.getDate() + 1);
            }
            
            return count;
        }
        
        async function abrirModalVacaciones() {
            try {
                // Cargar funcionarios activos
                const funcionariosRes = await fetch('../funcionarios/ajax/listar.php');
                
                if (!funcionariosRes.ok) {
                    throw new Error(`HTTP ${funcionariosRes.status}: Error al cargar funcionarios`);
                }
                
                // Intentar parsear JSON con mejor manejo de errores
                let funcionariosData;
                try {
                    const responseText = await funcionariosRes.text();
                    console.log('Respuesta del servidor:', responseText);
                    funcionariosData = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Error al parsear JSON:', parseError);
                    console.error('Respuesta completa:', await funcionariosRes.clone().text());
                    throw new Error('El servidor devolvió una respuesta inválida (no es JSON válido)');
                }

                if (!funcionariosData.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los funcionarios',
                        confirmButtonColor: '#ef4444'
                    });
                    return;
                }

                const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');

                // Variable para almacenar datos LOTTT del funcionario seleccionado
                let datosVacaciones = null;

                // Modal con diseño HORIZONTAL y profesional (igual que nombramientos)
                const { value: formValues } = await Swal.fire({
                    title: '<div style="display: flex; align-items: center; gap: 12px; justify-content: center; font-size: 22px; font-weight: 700; color: #1e293b;"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span>Nueva Vacación</span></div>',
                    html: `
                        <style>
                            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
                            .form-group { text-align: left; }
                            .form-label { display: flex; align-items: center; gap: 7px; font-weight: 600; margin-bottom: 9px; color: #334155; font-size: 13px; }
                            .form-input, .form-select, .form-textarea { width: 100%; padding: 10px 13px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 13.5px; transition: all 0.2s; background: white; font-family: inherit; }
                            .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #06d6a0; outline: none; box-shadow: 0 0 0 3px rgba(6, 214, 160, 0.1); }
                            .form-textarea { resize: vertical; min-height: 70px; }
                            .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
                            .file-input-button { width: 100%; padding: 11px 14px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 9px; font-weight: 500; color: #475569; font-size: 13px; }
                            .file-input-button:hover { border-color: #06d6a0; background: #f0fdfa; color: #06d6a0; }
                            .file-input-button.has-file { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-color: #06d6a0; color: #059669; }
                            .file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
                            .info-box { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 3px solid #f59e0b; padding: 11px 14px; border-radius: 8px; margin-top: 10px; }
                            .info-box-content { display: flex; align-items: start; gap: 9px; font-size: 11.5px; color: #92400e; line-height: 1.5; }
                            .lottt-card { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #06d6a0; border-radius: 12px; padding: 18px; margin-bottom: 18px; display: none; }
                            .lottt-title { font-size: 13px; color: #047857; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
                            .lottt-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
                            .lottt-stat { text-align: center; }
                            .lottt-stat-label { font-size: 11px; color: #059669; font-weight: 500; margin-bottom: 4px; }
                            .lottt-stat-value { font-size: 24px; font-weight: 700; color: #047857; }
                            .return-date-card { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 3px solid #3b82f6; padding: 14px; border-radius: 8px; margin-bottom: 18px; display: none; }
                            .return-date-content { display: flex; align-items: center; gap: 10px; color: #1e40af; font-size: 13.5px; font-weight: 600; }
                            .error-message { background: #fee2e2; border-left: 3px solid #ef4444; padding: 11px 14px; border-radius: 8px; margin-bottom: 18px; display: none; color: #991b1b; font-size: 12px; font-weight: 500; }
                        </style>
                        <div style="max-width: 750px; margin: 0 auto;">
                            <div class="form-group" style="margin-bottom: 18px;">
                                <label class="form-label">
                                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Funcionario <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="swal-funcionario" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    ${funcionarios.map(f => `
                                        <option value="${f.id}">
                                            ${f.nombres} ${f.apellidos} - ${f.cedula}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>

                            <div id="error-message" class="error-message"></div>

                            <div id="lottt-card" class="lottt-card">
                                <div class="lottt-title">📋 Derecho a Vacaciones (LOTTT)</div>
                                <div class="lottt-stats">
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Años Servicio</div>
                                        <div class="lottt-stat-value" id="años-servicio">-</div>
                                    </div>
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Días Totales</div>
                                        <div class="lottt-stat-value" id="dias-totales">-</div>
                                    </div>
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Disponibles</div>
                                        <div class="lottt-stat-value" id="dias-disponibles">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Fecha Inicio <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input type="date" id="swal-fecha-inicio" class="form-input" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Días a Tomar <span style="color: #ef4444;">*</span>
                                    </label>
                                    <select id="swal-dias-selector" class="form-select" disabled>
                                        <option value="">Primero seleccione funcionario...</option>
                                    </select>
                                </div>
                            </div>

                            <div id="return-date-card" class="return-date-card">
                                <div class="return-date-content">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <div>
                                        Fecha de retorno al trabajo: 
                                        <strong id="fecha-retorno-display">-</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                        Observaciones
                                    </label>
                                    <textarea id="swal-observaciones" class="form-textarea" placeholder="Motivo o comentarios..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Documento Aval <span style="color: #ef4444;">*</span>
                                    </label>
                                    <div class="file-input-wrapper">
                                        <label class="file-input-button" id="file-label" for="swal-pdf">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <span id="file-label-text">Seleccionar archivo</span>
                                        </label>
                                        <input type="file" id="swal-pdf" accept="application/pdf,image/png,image/jpeg,image/jpg">
                                    </div>
                                </div>
                            </div>

                            <div class="info-box">
                                <div class="info-box-content">
                                    <svg width="16" height="16" fill="#f59e0b" viewBox="0 0 24 24" style="flex-shrink: 0;"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div>
                                        <strong>LOTTT:</strong> 15 días tras 1 año + 1 día/año adicional hasta máx 30 • <strong>Formatos:</strong> PDF, JPG, PNG • <strong>Máx:</strong> 5 MB
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '850px',
                    showCancelButton: true,
                    confirmButtonText: '<div style="display: flex; align-items: center; gap: 7px;"><svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Registrar Vacación</span></div>',
                    cancelButtonText: '<div style="display: flex; align-items: center; gap: 7px;"><svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg><span>Cancelar</span></div>',
                    confirmButtonColor: '#06d6a0',
                    cancelButtonColor: '#64748b',
                    customClass: {
                        popup: 'swal-modern-popup',
                        confirmButton: 'swal-btn',
                        cancelButton: 'swal-btn'
                    },
                    didOpen: () => {
                        const style = document.createElement('style');
                        style.textContent = `
                            .swal-modern-popup { border-radius: 20px; padding: 30px !important; }
                            .swal-btn { border-radius: 10px !important; padding: 11px 24px !important; font-weight: 600 !important; font-size: 14.5px !important; transition: all 0.2s !important; }
                            .swal-btn:hover { transform: translateY(-1px) !important; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15) !important; }
                        `;
                        document.head.appendChild(style);

                        // Elementos del DOM
                        const funcionarioSelect = document.getElementById('swal-funcionario');
                        const errorMessage = document.getElementById('error-message');
                        const lotttCard = document.getElementById('lottt-card');
                        const añosServicio = document.getElementById('años-servicio');
                        const diasTotales = document.getElementById('dias-totales');
                        const diasDisponibles = document.getElementById('dias-disponibles');
                        const fechaInicio = document.getElementById('swal-fecha-inicio');
                        const diasSelector = document.getElementById('swal-dias-selector');
                        const returnDateCard = document.getElementById('return-date-card');
                        const fechaRetornoDisplay = document.getElementById('fecha-retorno-display');
                        const fileInput = document.getElementById('swal-pdf');
                        const fileLabel = document.getElementById('file-label');
                        const fileLabelText = document.getElementById('file-label-text');

                        // Función: Cargar datos de vacaciones del funcionario
                        async function cargarDatosVacaciones(funcionarioId) {
                            if (!funcionarioId) {
                                lotttCard.style.display = 'none';
                                errorMessage.style.display = 'none';
                                returnDateCard.style.display = 'none';
                                diasSelector.disabled = true;
                                diasSelector.innerHTML = '<option value="">Primero seleccione funcionario...</option>';
                                return;
                            }

                            try {
                                console.log(`Cargando datos LOTTT para funcionario ID: ${funcionarioId}`);
                                const url = `ajax/calcular_dias_vacaciones.php?funcionario_id=${funcionarioId}`;
                                console.log(`URL: ${url}`);
                                
                                const res = await fetch(url);
                                console.log('Response status:', res.status);
                                console.log('Response ok:', res.ok);
                                
                                const responseText = await res.text();
                                console.log('Response text:', responseText);
                                
                                const data = JSON.parse(responseText);

                                if (!data.success) {
                                    throw new Error(data.error || 'Error al calcular vacaciones');
                                }

                                datosVacaciones = data;

                                if (!data.cumple_requisito) {
                                    // No cumple 1 año
                                    lotttCard.style.display = 'none';
                                    errorMessage.textContent = `⚠️ ${data.mensaje}. Fecha cumple 1 año: ${data.data.fecha_cumple_año}`;
                                    errorMessage.style.display = 'block';
                                    diasSelector.disabled = true;
                                    diasSelector.innerHTML = '<option value="">No disponible</option>';
                                    fechaInicio.disabled = true;
                                } else {
                                    // Mostrar datos
                                    errorMessage.style.display = 'none';
                                    lotttCard.style.display = 'block';
                                    añosServicio.textContent = data.data.años_servicio;
                                    diasTotales.textContent = data.data.dias_totales;
                                    diasDisponibles.textContent = data.data.dias_disponibles;
                                    
                                    fechaInicio.disabled = false;

                                    if (data.data.dias_disponibles === 0) {
                                        errorMessage.textContent = '⚠️ Este funcionario ya usó todos sus días de vacaciones disponibles este año.';
                                        errorMessage.style.display = 'block';
                                        diasSelector.disabled = true;
                                        diasSelector.innerHTML = '<option value="">Sin días disponibles</option>';
                                    } else {
                                        // ✅ Generar opciones del selector
                                        diasSelector.disabled = false;
                                        let options = '<option value="">Seleccione días...</option>';
                                        for (let i = 1; i <= data.data.dias_disponibles; i++) {
                                            options += `<option value="${i}">${i} día${i > 1 ? 's' : ''}</option>`;
                                        }
                                        diasSelector.innerHTML = options;
                                    }
                                }
                            } catch (error) {
                                console.error(error);
                                errorMessage.textContent = '❌ Error al cargar datos: ' + error.message;
                                errorMessage.style.display = 'block';
                                lotttCard.style.display = 'none';
                            }
                        }

                        // Función: Calcular fecha de retorno
                        async function calcularFechaRetorno() {
                            const diasValue = parseInt(diasSelector.value);
                            const fechaValue = fechaInicio.value;

                            if (!diasValue || !fechaValue || diasValue <= 0) {
                                returnDateCard.style.display = 'none';
                                return;
                            }

                            try {
                                const url = `ajax/calcular_fecha_retorno.php?fecha_inicio=${fechaValue}&dias_habiles=${diasValue}`;
                                console.log('Calculando fecha retorno - URL:', url);
                                
                                const res = await fetch(url);
                                console.log('Fecha retorno - Response status:', res.status);
                                console.log('Fecha retorno - Response ok:', res.ok);
                                
                                const responseText = await res.text();
                                console.log('Fecha retorno - Response text:', responseText);
                                
                                const data = JSON.parse(responseText);

                                if (data.success) {
                                    fechaRetornoDisplay.textContent = data.data.fecha_retorno_formateada;
                                    returnDateCard.style.display = 'block';
                                }
                            } catch (error) {
                                console.error('Error calculando fecha de retorno:', error);
                                returnDateCard.style.display = 'none';
                            }
                        }

                        // File input handler
                        fileInput.addEventListener('change', (e) => {
                            if (e.target.files.length > 0) {
                                const fileName = e.target.files[0].name;
                                fileLabelText.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
                                fileLabel.classList.add('has-file');
                            } else {
                                fileLabelText.textContent = 'Seleccionar archivo';
                                fileLabel.classList.remove('has-file');
                            }
                        });

                        // Event Listeners
                        funcionarioSelect.addEventListener('change', (e) => cargarDatosVacaciones(e.target.value));
                        diasSelector.addEventListener('change', calcularFechaRetorno);
                        fechaInicio.addEventListener('change', calcularFechaRetorno);
                    },
                    preConfirm: () => {
                        const funcionario_id = document.getElementById('swal-funcionario').value;
                        const fecha_inicio = document.getElementById('swal-fecha-inicio').value;
                        const dias_solicitar = parseInt(document.getElementById('swal-dias-selector').value);
                        const observaciones = document.getElementById('swal-observaciones').value;
                        const archivo_pdf = document.getElementById('swal-pdf').files[0];
                        
                        if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                        if (!fecha_inicio) { Swal.showValidationMessage('Ingrese la fecha de inicio'); return false; }
                        if (!dias_solicitar || dias_solicitar <= 0) { Swal.showValidationMessage('Seleccione los días a tomar'); return false; }
                        if (!archivo_pdf) { Swal.showValidationMessage('Debe adjuntar el documento de aval'); return false; }
                        
                        if (datosVacaciones && !datosVacaciones.cumple_requisito) {
                            Swal.showValidationMessage('El funcionario no cumple con el requisito de 1 año de servicio');
                            return false;
                        }
                        
                        if (datosVacaciones && dias_solicitar > datosVacaciones.data.dias_disponibles) {
                            Swal.showValidationMessage(`Solo hay ${datosVacaciones.data.dias_disponibles} días disponibles`);
                            return false;
                        }
                        
                        if (archivo_pdf && archivo_pdf.size > 5 * 1024 * 1024) {
                            Swal.showValidationMessage('Archivo muy grande (máx 5MB)');
                            return false;
                        }
                        
                        return { funcionario_id, fecha_inicio, dias_solicitar, observaciones, archivo_pdf };
                    }
                });
                
                if (!formValues) return;
                
                // Procesar registro
                Swal.fire({ title: 'Procesando...', html: 'Registrando vacación...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                try {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                    formData.append('accion', 'registrar_vacacion');
                    formData.append('funcionario_id', formValues.funcionario_id);
                    formData.append('fecha_evento', formValues.fecha_inicio);
                    formData.append('dias_habiles', formValues.dias_solicitar);
                    formData.append('observaciones', formValues.observaciones);
                    formData.append('archivo_pdf', formValues.archivo_pdf);
                    
                    console.log('Enviando registro de vacación...');
                    console.log('Funcionario ID:', formValues.funcionario_id);
                    console.log('Fecha inicio:', formValues.fecha_inicio);
                    console.log('Días:', formValues.dias_solicitar);
                    
                    const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                    console.log('Registro - Response status:', response.status);
                    console.log('Registro - Response ok:', response.ok);
                    
                    const responseText = await response.text();
                    console.log('Registro - Response text:', responseText);
                    
                    const result = JSON.parse(responseText);
                    
                    if (result.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Vacación Registrada',
                            html: `
                                <p>Las vacaciones se registraron exitosamente.</p>
                                <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                                    <p style="margin: 0; font-size: 13px;"><strong>✓ Días hábiles:</strong> ${formValues.dias_solicitar}</p>
                                    <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Fecha inicio:</strong> ${formValues.fecha_inicio}</p>
                                    <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Fecha retorno:</strong> ${result.data?.fecha_retorno || 'Calculada'}</p>
                                </div>
                            `,
                            confirmButtonColor: '#10b981'
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al registrar vacación', confirmButtonColor: '#ef4444' });
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Error al abrir el modal', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>