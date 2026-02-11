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
    <link rel="stylesheet" href="../../publico/css/swal-modern.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #06d6a0 0%, #00a8cc 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #ffd166 0%, #ff9f1c 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.95;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
        }
        
        .filter-panel {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border-radius: 2px;
        }
        
        .vacaciones-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .vacaciones-table thead {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
        }
        
        .vacaciones-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .vacaciones-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--color-border-light);
            font-size: 14px;
        }
        
        .vacaciones-table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .vacaciones-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-programada {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-curso {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-finalizada {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .btn-icon {
            padding: 8px 12px;
            font-size: 12px;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view {
            background: #3b82f6;
            color: white;
        }
        
        .btn-view:hover {
            background: #2563eb;
        }
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn-download:hover {
            background: #059669;
        }
        
        .no-results {
            text-align: center;
            padding: 48px;
            color: var(--color-text-light);
        }
        
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" style="vertical-align: middle; margin-right: 8px;">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    Gestión de Vacaciones
                </h1>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estadísticas Rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Empleados con Vacaciones</div>
                    <div class="stat-value"><?php echo $total_empleados_con_vacaciones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Actualmente de Vacaciones</div>
                    <div class="stat-value"><?php echo $empleados_en_vacaciones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Días Usados (<?php echo date('Y'); ?>)</div>
                    <div class="stat-value"><?php echo $dias_usados_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Vacaciones Programadas</div>
                    <div class="stat-value"><?php echo $vacaciones_programadas; ?></div>
                </div>
            </div>
            
            <!-- Panel de Filtros -->
            <div class="filter-panel">
                <h3 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="vertical-align: middle; margin-right: 8px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Filtros de Búsqueda
                </h3>
                <div class="filter-grid">
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar</label>
                        <input type="text" id="searchVacaciones" class="search-input" placeholder="Buscar vacación..." style="width: 100%;">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento</label>
                        <select id="filter-departamento" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Estado</label>
                        <select id="filter-estado" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <option value="Programada">Programada</option>
                            <option value="En curso">En curso</option>
                            <option value="Finalizada">Finalizada</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Año</label>
                        <select id="filter-anio" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($i = $currentYear; $i >= $currentYear - 5; $i--): 
                            ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: flex-end; gap: 8px;">
                        <button type="button" onclick="limpiarFiltros()" class="btn" style="flex: 1; background: #e2e8f0; color: #2d3748;">
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Vacaciones -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="vertical-align: middle; margin-right: 8px;">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Registro de Vacaciones
                        </h2>
                        <p class="card-subtitle"><?php echo count($vacaciones); ?> periodos registrados</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="abrirModalVacaciones()" class="btn btn-primary" style=" padding: 10px 20px; display: flex; align-items: center; gap: 8px;">
                            <?php echo Icon::get('sun'); ?>
                            Registrar Vacaciones
                        </button>
                        <a href="../reportes/index.php#vacaciones" class="btn" style="text-decoration: none; background: #e2e8f0; color: #2d3748;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-right: 6px;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <line x1="10" y1="9" x2="8" y2="9"></line>
                            </svg>
                            Generar Reporte
                        </a>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="vacaciones-table" id="vacacionesTable">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="vacaciones-tbody">
                            <?php if (empty($vacaciones)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="no-results">
                                            <div class="no-results-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                                                    <circle cx="12" cy="12" r="5"></circle>
                                                    <line x1="12" y1="1" x2="12" y2="3"></line>
                                                    <line x1="12" y1="21" x2="12" y2="23"></line>
                                                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                                    <line x1="1" y1="12" x2="3" y2="12"></line>
                                                    <line x1="21" y1="12" x2="23" y2="12"></line>
                                                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                                </svg>
                                            </div>
                                            <p>No hay registros de vacaciones</p>
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
                                            <strong><?php echo htmlspecialchars($vac['nombres'] . ' ' . $vac['apellidos']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($vac['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($vac['departamento']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($vac['fecha_inicio'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($vac['fecha_fin'])); ?></td>
                                        <td><strong><?php echo $vac['dias_totales']; ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $vac['estado_vacacion'])); ?>">
                                                <?php echo $vac['estado_vacacion']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $vac['funcionario_id']; ?>" 
                                               class="btn-icon btn-view" 
                                               title="Ver Empleado">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            <?php if ($vac['ruta_archivo']): ?>
                                                <a href="../../<?php echo $vac['ruta_archivo']; ?>" 
                                                   class="btn-icon btn-download" 
                                                   target="_blank"
                                                   title="Descargar Documento">
                                                    📥
                                                </a>
                                            <?php endif; ?>
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
    
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    
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
                            <!-- Funcionario -->
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

                            <!-- Mensaje de error -->
                            <div id="error-message" class="error-message"></div>

                            <!-- Información LOTTT -->
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

                            <!-- Fechas y Días -->
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

                            <!-- Fecha de retorno calculada -->
                            <div id="return-date-card" class="return-date-card">
                                <div class="return-date-content">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <div>
                                        Fecha de retorno al trabajo: 
                                        <strong id="fecha-retorno-display">-</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones y Documento -->
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

                            <!-- Info Box -->
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
