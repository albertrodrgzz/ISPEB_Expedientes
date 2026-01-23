<?php
/**
 * Vista: Módulo de Traslados
 * Gestión y consulta de traslados de personal
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder al módulo de traslados';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO'");
$total_traslados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$traslados_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO'");
$empleados_trasladados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.departamento_destino')) as dept, COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' GROUP BY dept ORDER BY total DESC LIMIT 1");
$dept_popular = $stmt->fetch();

// Obtener departamentos para filtros
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener traslados
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.departamento_origen')) as departamento_origen,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.departamento_destino')) as departamento_destino,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento as fecha_traslado,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'TRASLADO'
    ORDER BY ha.fecha_evento DESC
");
$traslados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslados - <?php echo APP_NAME; ?></title>
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
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-card:nth-child(4) {
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
                    <div class="stat-label">Total Traslados</div>
                    <div class="stat-value"><?php echo $total_traslados; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Traslados <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $traslados_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Empleados Trasladados</div>
                    <div class="stat-value"><?php echo $empleados_trasladados; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Departamento Más Común</div>
                    <div class="stat-value" style="font-size: 18px; margin-top: 8px;"><?php echo $dept_popular['dept'] ?? 'N/A'; ?></div>
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
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento Origen</label>
                        <select id="filter-origen" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento Destino</label>
                        <select id="filter-destino" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
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
                    <h2 class="card-title">🔄 Registro de Traslados</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento Origen</th>
                                <th>Departamento Destino</th>
                                <th>Fecha</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="traslados-tbody">
                            <?php if (empty($traslados)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🔄</div>
                                        <p>No hay registros de traslados</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($traslados as $tras): ?>
                                    <tr class="traslado-row" 
                                        data-empleado="<?php echo strtolower($tras['nombres'] . ' ' . $tras['apellidos'] . ' ' . $tras['cedula']); ?>"
                                        data-origen="<?php echo $tras['departamento_origen']; ?>"
                                        data-destino="<?php echo $tras['departamento_destino']; ?>">
                                        <td><strong><?php echo htmlspecialchars($tras['nombres'] . ' ' . $tras['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($tras['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #fee2e2; color: #991b1b; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($tras['departamento_origen']); ?></span></td>
                                        <td><span style="padding: 4px 12px; background: #dcfce7; color: #166534; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($tras['departamento_destino']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($tras['fecha_traslado'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($tras['motivo'], 0, 50)) . (strlen($tras['motivo']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $tras['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($tras['ruta_archivo']): ?>
                                                <a href="../../<?php echo $tras['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
        const searchInput = document.getElementById('search-empleado');
        const filterOrigen = document.getElementById('filter-origen');
        const filterDestino = document.getElementById('filter-destino');
        const rows = document.querySelectorAll('.traslado-row');
        
        function aplicarFiltros() {
            const searchTerm = searchInput.value.toLowerCase();
            const origen = filterOrigen.value;
            const destino = filterDestino.value;
            
            rows.forEach(row => {
                const empleado = row.dataset.empleado;
                const rowOrigen = row.dataset.origen;
                const rowDestino = row.dataset.destino;
                
                const matchSearch = empleado.includes(searchTerm);
                const matchOrigen = !origen || rowOrigen === origen;
                const matchDestino = !destino || rowDestino === destino;
                
                row.style.display = (matchSearch && matchOrigen && matchDestino) ? '' : 'none';
            });
        }
        
        searchInput.addEventListener('input', aplicarFiltros);
        filterOrigen.addEventListener('change', aplicarFiltros);
        filterDestino.addEventListener('change', aplicarFiltros);
        
        function limpiarFiltros() {
            searchInput.value = '';
            filterOrigen.value = '';
            filterDestino.value = '';
            aplicarFiltros();
        }
    </script>
</body>
</html>
