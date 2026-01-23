<?php
/**
 * Vista: Módulo de Remociones
 * Gestión y consulta de remociones de personal
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
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'REMOCION'");
$total_remociones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'REMOCION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$remociones_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'REMOCION' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$remociones_mes = $stmt->fetch()['total'];

// Obtener departamentos
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener remociones
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento as fecha_remocion,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'REMOCION'
    ORDER BY ha.fecha_evento DESC
");
$remociones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remociones - <?php echo APP_NAME; ?></title>
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
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
            background: linear-gradient(135deg, #c44569 0%, #f8b500 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #ee5a6f 0%, #f29263 100%);
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
                    <div class="stat-label">Total Remociones</div>
                    <div class="stat-value"><?php echo $total_remociones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Remociones <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $remociones_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Remociones Este Mes</div>
                    <div class="stat-value"><?php echo $remociones_mes; ?></div>
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
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Año</label>
                        <select id="filter-anio" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
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
                    <h2 class="card-title">🚫 Registro de Remociones</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Remoción</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($remociones)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🚫</div>
                                        <p>No hay registros de remociones</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($remociones as $rem): ?>
                                    <tr class="remocion-row" 
                                        data-empleado="<?php echo strtolower($rem['nombres'] . ' ' . $rem['apellidos'] . ' ' . $rem['cedula']); ?>"
                                        data-departamento="<?php echo $rem['departamento']; ?>"
                                        data-anio="<?php echo date('Y', strtotime($rem['fecha_remocion'])); ?>">
                                        <td><strong><?php echo htmlspecialchars($rem['nombres'] . ' ' . $rem['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($rem['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #fee2e2; color: #991b1b; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($rem['departamento']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($rem['fecha_remocion'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($rem['motivo'], 0, 60)) . (strlen($rem['motivo']) > 60 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $rem['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($rem['ruta_archivo']): ?>
                                                <a href="../../<?php echo $rem['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
        const filterDepartamento = document.getElementById('filter-departamento');
        const filterAnio = document.getElementById('filter-anio');
        const rows = document.querySelectorAll('.remocion-row');
        
        function aplicarFiltros() {
            const searchTerm = searchInput.value.toLowerCase();
            const departamento = filterDepartamento.value;
            const anio = filterAnio.value;
            
            rows.forEach(row => {
                const empleado = row.dataset.empleado;
                const rowDepartamento = row.dataset.departamento;
                const rowAnio = row.dataset.anio;
                
                const matchSearch = empleado.includes(searchTerm);
                const matchDepartamento = !departamento || rowDepartamento === departamento;
                const matchAnio = !anio || rowAnio === anio;
                
                row.style.display = (matchSearch && matchDepartamento && matchAnio) ? '' : 'none';
            });
        }
        
        searchInput.addEventListener('input', aplicarFiltros);
        filterDepartamento.addEventListener('change', aplicarFiltros);
        filterAnio.addEventListener('change', aplicarFiltros);
        
        function limpiarFiltros() {
            searchInput.value = '';
            filterDepartamento.value = '';
            filterAnio.value = '';
            aplicarFiltros();
        }
    </script>
</body>
</html>
