<?php
/**
 * Vista: Módulo de Nombramientos
 * Gestión y consulta de nombramientos de personal
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$total_nombramientos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$nombramientos_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$empleados_nombrados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO' AND (fecha_fin IS NULL OR fecha_fin > CURDATE())");
$nombramientos_activos = $stmt->fetch()['total'];

// Obtener departamentos
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener nombramientos
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.titulo')) as titulo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.categoria')) as categoria,
        ha.fecha_evento as fecha_inicio,
        ha.fecha_fin,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at,
        CASE 
            WHEN ha.fecha_fin IS NULL OR ha.fecha_fin > CURDATE() THEN 'Activo'
            ELSE 'Finalizado'
        END as estado
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY ha.fecha_evento DESC
");
$nombramientos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nombramientos - <?php echo APP_NAME; ?></title>
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
            background: linear-gradient(135deg, #00a8cc 0%, #005f73 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #4cc9f0 0%, #00a8cc 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #0a9396 0%, #005f73 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #06d6a0 0%, #0a9396 100%);
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
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .badge-activo {
            padding: 4px 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-finalizado {
            padding: 4px 12px;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Nombramientos</div>
                    <div class="stat-value"><?php echo $total_nombramientos; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nombramientos <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $nombramientos_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Empleados Nombrados</div>
                    <div class="stat-value"><?php echo $empleados_nombrados; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nombramientos Activos</div>
                    <div class="stat-value"><?php echo $nombramientos_activos; ?></div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filter-panel">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">🔍 Filtros de Búsqueda</h3>
                <div class="filter-grid">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar Empleado</label>
                        <input type="text" id="search-empleado" class="search-input" placeholder="Nombre, apellido o cédula..." style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento</label>
                        <select id="filter-departamento" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Estado</label>
                        <select id="filter-estado" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <option value="Activo">Activo</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="button" onclick="limpiarFiltros()" class="btn" style="width: 100%; background: #e2e8f0; color: #2d3748;">Limpiar</button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📄 Registro de Nombramientos</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($nombramientos)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📄</div>
                                        <p>No hay registros de nombramientos</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($nombramientos as $nom): ?>
                                    <tr class="nombramiento-row" 
                                        data-empleado="<?php echo strtolower($nom['nombres'] . ' ' . $nom['apellidos'] . ' ' . $nom['cedula']); ?>"
                                        data-departamento="<?php echo $nom['departamento']; ?>"
                                        data-estado="<?php echo $nom['estado']; ?>">
                                        <td><strong><?php echo htmlspecialchars($nom['nombres'] . ' ' . $nom['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($nom['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($nom['departamento']); ?></span></td>
                                        <td><?php echo htmlspecialchars($nom['titulo'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($nom['categoria'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($nom['fecha_inicio'])); ?></td>
                                        <td><?php echo $nom['fecha_fin'] ? date('d/m/Y', strtotime($nom['fecha_fin'])) : 'N/A'; ?></td>
                                        <td><span class="badge-<?php echo strtolower($nom['estado']); ?>"><?php echo $nom['estado']; ?></span></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $nom['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($nom['ruta_archivo']): ?>
                                                <a href="../../<?php echo $nom['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
    
    
    <!-- Filtros en Tiempo Real -->
    <script src="../../publico/js/filtros-tiempo-real.js"></script>
    <script>
        inicializarFiltros({
            module: 'nombramientos',
            searchId: 'search-empleado',
            filterIds: ['filter-departamento', 'filter-estado'],
            tableBodySelector: 'table tbody',
            countSelector: '.card-subtitle'
        });
    </script>
</body>
</html>
