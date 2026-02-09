<?php
/**
 * Vista: Módulo de Reportes
 * Generación de reportes del sistema con filtros avanzados
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1 y 2)
verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a los reportes';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas para el dashboard de reportes
$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado != 'inactivo'");
$total_funcionarios = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
$total_activos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
$total_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$total_amonestaciones = $stmt->fetch()['total'];

// Obtener departamentos y cargos para filtros
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();
$cargos = $db->query("SELECT * FROM cargos ORDER BY nivel_acceso, nombre_cargo")->fetchAll();

// Estadísticas adicionales
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$docs_por_vencer = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE())");
$cumpleanos_mes = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .report-card {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .report-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
            border-color: var(--color-primary);
        }
        
        .report-card:hover::before {
            transform: scaleX(1);
        }
        
        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 168, 204, 0.3);
        }
        
        .report-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 8px;
        }
        
        .report-description {
            font-size: 14px;
            color: var(--color-text-light);
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .report-actions {
            display: flex;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            background: linear-gradient(135deg, #ef476f 0%, #d62828 100%);
        }
        
        .stat-card:nth-child(5) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(6) {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
        
        .btn-excel {
            background: #10b981;
            color: white;
        }
        
        .btn-excel:hover {
            background: #059669;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Reportes del Sistema</h1>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estadísticas Rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Funcionarios</div>
                    <div class="stat-value"><?php echo $total_funcionarios; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Activos</div>
                    <div class="stat-value"><?php echo $total_activos; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">De Vacaciones</div>
                    <div class="stat-value"><?php echo $total_vacaciones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Con Amonestaciones (<?php echo date('Y'); ?>)</div>
                    <div class="stat-value"><?php echo $total_amonestaciones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Docs. por Vencer (30 días)</div>
                    <div class="stat-value"><?php echo $docs_por_vencer; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Cumpleaños este Mes</div>
                    <div class="stat-value"><?php echo $cumpleanos_mes; ?></div>
                </div>
            </div>
            
            <!-- Panel de Filtros Avanzados -->
            <div class="filter-panel">
                <h3 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="vertical-align: middle; margin-right: 8px;">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    Filtros Avanzados
                </h3>
                <p style="font-size: 14px; color: var(--color-text-light); margin-bottom: 16px;">
                    Configure los filtros y luego seleccione el reporte que desea generar
                </p>
                <form id="filter-form">
                    <div class="filter-grid">
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Fecha Desde</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" class="search-input" style="width: 100%;">
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Fecha Hasta</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" class="search-input" style="width: 100%;">
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento</label>
                            <select id="departamento_id" name="departamento_id" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Cargo</label>
                            <select id="cargo_id" name="cargo_id" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <?php foreach ($cargos as $cargo): ?>
                                    <option value="<?php echo $cargo['id']; ?>"><?php echo htmlspecialchars($cargo['nombre_cargo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Estado</label>
                            <select id="estado" name="estado" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <option value="activo">Activo</option>
                                <option value="vacaciones">Vacaciones</option>
                                <option value="reposo">Reposo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; align-items: flex-end; gap: 8px;">
                            <button type="button" onclick="limpiarFiltros()" class="btn" style="flex: 1; background: #e2e8f0; color: #2d3748;">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tarjetas de Reportes -->
            <h2 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" style="vertical-align: middle; margin-right: 8px;">
                    <line x1="12" y1="20" x2="12" y2="10"></line>
                    <line x1="18" y1="20" x2="18" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="16"></line>
                </svg>
                Reportes Disponibles
            </h2>
            
            <div class="reports-grid">
                <!-- Reporte General de Funcionarios -->
                <div class="report-card">
                    <div class="report-icon">👥</div>
                    <h3 class="report-title">Listado General de Funcionarios</h3>
                    <p class="report-description">Reporte completo con todos los funcionarios del sistema según los filtros aplicados</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('general', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-right: 4px;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            PDF
                        </a>
                        <a href="#" onclick="generarReporte('general', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-right: 4px;">
                                <line x1="12" y1="20" x2="12" y2="10"></line>
                                <line x1="18" y1="20" x2="18" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="16"></line>
                            </svg>
                            Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte por Departamento -->
                <div class="report-card">
                    <div class="report-icon">🏢</div>
                    <h3 class="report-title">Reporte por Departamento</h3>
                    <p class="report-description">Funcionarios agrupados y organizados por departamento</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('departamento', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('departamento', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte de Vacaciones -->
                <div class="report-card">
                    <div class="report-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                        </svg>
                    </div>
                    <h3 class="report-title">Control Vacacional</h3>
                    <p class="report-description">Resumen de vacaciones, días disponibles y días usados por funcionario</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('vacaciones', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('vacaciones', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte de Amonestaciones -->
                <div class="report-card">
                    <div class="report-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                        </svg>
                    </div>
                    <h3 class="report-title">Historial de Amonestaciones</h3>
                    <p class="report-description">Registro completo de amonestaciones y sanciones aplicadas</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('amonestaciones', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('amonestaciones', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte de Antigüedad -->
                <div class="report-card">
                    <div class="report-icon">📅</div>
                    <h3 class="report-title">Antigüedad del Personal</h3>
                    <p class="report-description">Funcionarios ordenados por años de servicio en la institución</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('antiguedad', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('antiguedad', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte de Nombramientos -->
                <div class="report-card">
                    <div class="report-icon">📝</div>
                    <h3 class="report-title">Nombramientos Activos</h3>
                    <p class="report-description">Contratos y nombramientos vigentes en el sistema</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('nombramientos', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('nombramientos', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte de Cumpleaños -->
                <div class="report-card">
                    <div class="report-icon">🎂</div>
                    <h3 class="report-title">Cumpleaños del Mes</h3>
                    <p class="report-description">Funcionarios que cumplen años en el mes actual</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('cumpleanos', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('cumpleanos', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
                
                <!-- Reporte de Documentos por Vencer -->
                <div class="report-card">
                    <div class="report-icon">⏰</div>
                    <h3 class="report-title">Documentos por Vencer</h3>
                    <p class="report-description">Documentos que vencen en los próximos 30 días</p>
                    <div class="report-actions">
                        <a href="#" onclick="generarReporte('por_vencer', 'pdf'); return false;" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                            📄 PDF
                        </a>
                        <a href="#" onclick="generarReporte('por_vencer', 'excel'); return false;" class="btn btn-excel" style="flex: 1; text-align: center; text-decoration: none;">
                            📊 Excel
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Información Adicional -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">ℹ️ Información sobre Reportes</h2>
                </div>
                <div style="padding: 24px;">
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 12px 0; border-bottom: 1px solid var(--color-border-light);">
                            <strong>📄 PDF:</strong> Reportes formateados con membrete ISPEB, ideales para impresión y archivo oficial
                        </li>
                        <li style="padding: 12px 0; border-bottom: 1px solid var(--color-border-light);">
                            <strong>📊 Excel:</strong> Datos en formato de hoja de cálculo para análisis y manipulación
                        </li>
                        <li style="padding: 12px 0; border-bottom: 1px solid var(--color-border-light);">
                            <strong>🔍 Filtros:</strong> Los filtros se aplican automáticamente a todos los reportes que genere
                        </li>
                        <li style="padding: 12px 0;">
                            <strong>🔒 Seguridad:</strong> Todos los reportes registran la acción en el log de auditoría
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function obtenerFiltros() {
            const params = new URLSearchParams();
            
            const fechaDesde = document.getElementById('fecha_desde').value;
            const fechaHasta = document.getElementById('fecha_hasta').value;
            const departamentoId = document.getElementById('departamento_id').value;
            const cargoId = document.getElementById('cargo_id').value;
            const estado = document.getElementById('estado').value;
            
            if (fechaDesde) params.append('fecha_desde', fechaDesde);
            if (fechaHasta) params.append('fecha_hasta', fechaHasta);
            if (departamentoId) params.append('departamento_id', departamentoId);
            if (cargoId) params.append('cargo_id', cargoId);
            if (estado) params.append('estado', estado);
            
            return params.toString();
        }
        
        function generarReporte(tipo, formato) {
            const filtros = obtenerFiltros();
            const url = formato === 'pdf' 
                ? `generar_pdf.php?tipo=${tipo}&${filtros}`
                : `exportar_excel.php?tipo=${tipo}&${filtros}`;
            
            if (formato === 'pdf') {
                window.open(url, '_blank');
            } else {
                window.location.href = url;
            }
        }
        
        function limpiarFiltros() {
            document.getElementById('fecha_desde').value = '';
            document.getElementById('fecha_hasta').value = '';
            document.getElementById('departamento_id').value = '';
            document.getElementById('cargo_id').value = '';
            document.getElementById('estado').value = '';
        }
    </script>
</body>
</html>
