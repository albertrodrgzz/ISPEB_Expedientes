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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacaciones - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
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
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar Empleado</label>
                        <input type="text" id="search-empleado" class="search-input" placeholder="Nombre, apellido o cédula..." style="width: 100%;">
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
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="vertical-align: middle; margin-right: 8px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Registro de Vacaciones
                    </h2>
                    <div style="display: flex; gap: 8px;">
                        <a href="../reportes/index.php#vacaciones" class="btn btn-primary" style="text-decoration: none;">
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
                    <table class="vacaciones-table" id="vacaciones-table">
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
                                            <a href="../funcionarios/view.php?id=<?php echo $vac['funcionario_id']; ?>" 
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
    
    <script>
        // Filtrado en tiempo real
        const searchInput = document.getElementById('search-empleado');
        const filterDepartamento = document.getElementById('filter-departamento');
        const filterEstado = document.getElementById('filter-estado');
        const filterAnio = document.getElementById('filter-anio');
        const rows = document.querySelectorAll('.vacacion-row');
        
        function aplicarFiltros() {
            const searchTerm = searchInput.value.toLowerCase();
            const departamento = filterDepartamento.value;
            const estado = filterEstado.value;
            const anio = filterAnio.value;
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const empleado = row.dataset.empleado;
                const rowDepartamento = row.dataset.departamento;
                const rowEstado = row.dataset.estado;
                const rowAnio = row.dataset.anio;
                
                const matchSearch = empleado.includes(searchTerm);
                const matchDepartamento = !departamento || rowDepartamento === departamento;
                const matchEstado = !estado || rowEstado === estado;
                const matchAnio = !anio || rowAnio === anio;
                
                if (matchSearch && matchDepartamento && matchEstado && matchAnio) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            const tbody = document.getElementById('vacaciones-tbody');
            const noResultsRow = tbody.querySelector('.no-results');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td colspan="8">
                            <div class="no-results">
                                <div class="no-results-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </div>
                                <p>No se encontraron resultados con los filtros aplicados</p>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                }
            } else if (noResultsRow && visibleCount > 0) {
                noResultsRow.remove();
            }
        }
        
        searchInput.addEventListener('input', aplicarFiltros);
        filterDepartamento.addEventListener('change', aplicarFiltros);
        filterEstado.addEventListener('change', aplicarFiltros);
        filterAnio.addEventListener('change', aplicarFiltros);
        
        function limpiarFiltros() {
            searchInput.value = '';
            filterDepartamento.value = '';
            filterEstado.value = '';
            filterAnio.value = '';
            aplicarFiltros();
        }
    </script>
</body>
</html>
