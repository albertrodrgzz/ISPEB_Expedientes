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
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar</label>
                        <input type="text" id="searchVacaciones" class="search-input" placeholder="🔍 Buscar vacación..." style="width: 100%;">
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
                            <span style="font-size: 18px;">✈️</span>
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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
            // Cargar funcionarios activos
            const response = await fetch('../funcionarios/ajax/listar.php');
            const data = await response.json();
            
            if (!data.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }
            
            const funcionarios = data.data.filter(f => f.estado === 'activo');
            
            const { value: formValues } = await Swal.fire({
                title: '✈️ Registrar Vacaciones',
                html: `
                    <div style="text-align: left;">
                        <div style="background: #fef3c7; border: 2px solid #fbbf24; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                            <div style="display: flex; align-items: center; gap: 10px; color: #92400e;">
                                <span style="font-size: 24px;">ℹ️</span>
                                <p style="margin: 0; font-size: 13px;">El estado del funcionario cambiará automáticamente a "vacaciones" durante el periodo registrado.</p>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `
                                    <option value="${f.id}">
                                        ${f.nombres} ${f.apellidos} - ${f.cedula} (${f.nombre_cargo})
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha Inicio *</label>
                                <input type="date" id="swal-fecha-inicio" class="swal2-input" style="width: 100%; padding: 10px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha Fin *</label>
                                <input type="date" id="swal-fecha-fin" class="swal2-input" style="width: 100%; padding: 10px;">
                            </div>
                        </div>
                        
                        <div id="dias-habiles-box" style="background: #f0f9ff; border: 2px dashed #0ea5e9; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-weight: 600; color: #0c4a6e;">Días hábiles calculados:</span>
                                <span id="dias-habiles-valor" style="font-size: 28px; font-weight: 700; color: #0284c7;">0</span>
                            </div>
                            <small style="color: #075985; font-size: 12px; display: block; margin-top: 8px;">* Solo cuenta días de lunes a viernes</small>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Observaciones</label>
                            <textarea id="swal-observaciones" class="swal2-textarea" rows="3" placeholder="Observaciones adicionales..." style="width: 95%; padding: 10px; resize: vertical;"></textarea>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento PDF (Opcional)</label>
                            <input type="file" id="swal-pdf" accept=".pdf" class="swal2-file" style="width: 100%; padding: 10px;">
                            <small style="color: #718096; font-size: 12px;">Máximo 5MB</small>
                        </div>
                    </div>
                `,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: 'Registrar Vacaciones',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#06d6a0',
                didOpen: () => {
                    const fechaInicio = document.getElementById('swal-fecha-inicio');
                    const fechaFin = document.getElementById('swal-fecha-fin');
                    const diasBox = document.getElementById('dias-habiles-box');
                    const diasValor = document.getElementById('dias-habiles-valor');
                    
                    function actualizarDias() {
                        const dias = calcularDiasHabiles(fechaInicio.value, fechaFin.value);
                        if (dias > 0) {
                            diasValor.textContent = dias;
                            diasBox.style.display = 'block';
                        } else {
                            diasBox.style.display = 'none';
                        }
                    }
                    
                    fechaInicio.addEventListener('change', actualizarDias);
                    fechaFin.addEventListener('change', actualizarDias);
                },
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const fecha_evento = document.getElementById('swal-fecha-inicio').value;
                    const fecha_fin = document.getElementById('swal-fecha-fin').value;
                    const observaciones = document.getElementById('swal-observaciones').value;
                    const archivo_pdf = document.getElementById('swal-pdf').files[0];
                    
                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha de inicio'); return false; }
                    if (!fecha_fin) { Swal.showValidationMessage('Ingrese la fecha de fin'); return false; }
                    
                    if (new Date(fecha_fin) <= new Date(fecha_evento)) {
                        Swal.showValidationMessage('La fecha de fin debe ser posterior a la de inicio');
                        return false;
                    }
                    
                    if (archivo_pdf && archivo_pdf.size > 5 * 1024 * 1024) {
                        Swal.showValidationMessage('Archivo muy grande (máx 5MB)');
                        return false;
                    }
                    
                    if (archivo_pdf && archivo_pdf.type !== 'application/pdf') {
                        Swal.showValidationMessage('Solo archivos PDF');
                        return false;
                    }
                    
                    return { funcionario_id, fecha_evento, fecha_fin, observaciones, archivo_pdf };
                }
            });
            
            if (!formValues) return;
            
            // Procesar
            Swal.fire({ title: 'Procesando...', html: 'Registrando vacaciones...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_vacacion');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('fecha_fin', formValues.fecha_fin);
                formData.append('observaciones', formValues.observaciones);
                if (formValues.archivo_pdf) {
                    formData.append('archivo_pdf', formValues.archivo_pdf);
                }
                
                const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Vacaciones Registradas',
                        html: `
                            <p>Las vacaciones se registraron exitosamente.</p>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                                <p style="margin: 0; font-size: 13px;"><strong>✓ Días hábiles:</strong> ${result.data.dias_habiles}</p>
                                <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Periodo:</strong> ${result.data.fecha_inicio} - ${result.data.fecha_fin}</p>
                                <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Estado:</strong> ${result.data.nuevo_estado}</p>
                            </div>
                        `,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al registrar vacaciones', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
