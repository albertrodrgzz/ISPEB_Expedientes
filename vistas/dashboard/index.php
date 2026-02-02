<?php
/**
 * Dashboard Principal
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión
verificarSesion();

// Obtener estadísticas
$db = getDB();

// Total de funcionarios
$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado != 'inactivo'");
$total_personal = $stmt->fetch()['total'];

// Funcionarios activos
$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
$activos = $stmt->fetch()['total'];

// Funcionarios de vacaciones
$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
$de_vacaciones = $stmt->fetch()['total'];

// Alertas (nombramientos recientes en 30 días)
$stmt = $db->query("
    SELECT COUNT(*) as total 
    FROM historial_administrativo 
    WHERE tipo_evento = 'NOMBRAMIENTO' 
    AND fecha_evento BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
");
$por_vencer = $stmt->fetch()['total'];

// Listado reciente de funcionarios
$stmt = $db->query("
    SELECT 
        f.id,
        f.cedula,
        CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
        f.foto AS foto,
        c.nombre_cargo,
        d.nombre AS departamento,
        f.estado
    FROM funcionarios f
    INNER JOIN cargos c ON f.cargo_id = c.id
    INNER JOIN departamentos d ON f.departamento_id = d.id
    WHERE f.estado != 'inactivo'
    ORDER BY f.created_at DESC
    LIMIT 10
");
$funcionarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <span>👥</span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Personal</div>
                        <div class="kpi-value"><?php echo $total_personal; ?></div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <span>✓</span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Activos</div>
                        <div class="kpi-value"><?php echo $activos; ?></div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <span>☀</span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">De Vacaciones</div>
                        <div class="kpi-value"><?php echo $de_vacaciones; ?></div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <span>⚠</span>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Por Vencer</div>
                        <div class="kpi-value"><?php echo $por_vencer; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos Estadísticos -->
            <div class="charts-grid">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Distribución por Departamento</h3>
                            <p class="card-subtitle">Personal activo por área</p>
                        </div>
                    </div>
                    <div style="padding: var(--spacing-xl);">
                        <canvas id="departmentChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Tendencia Mensual</h3>
                            <p class="card-subtitle">Ingresos y egresos del año</p>
                        </div>
                    </div>
                    <div style="padding: var(--spacing-xl);">
                        <canvas id="trendChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Nómina Reciente -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Nómina Reciente</h2>
                        <p class="card-subtitle">Últimos funcionarios registrados en el sistema</p>
                    </div>
                    <div class="card-actions">
                        <input type="text" id="buscar" class="search-input" placeholder="🔍 Buscar funcionario...">
                        <?php if (verificarNivel(2)): ?>
                            <a href="../funcionarios/crear.php" class="btn btn-primary">
                                <span>+</span> Nuevo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-funcionarios">
                            <?php foreach ($funcionarios as $func): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?php 
                                                $iniciales = strtoupper(substr($func['nombre_completo'], 0, 2));
                                                echo $iniciales;
                                                ?>
                                            </div>
                                            <div>
                                                <div class="user-name"><?php echo htmlspecialchars($func['nombre_completo']); ?></div>
                                                <div class="user-id"><?php echo htmlspecialchars($func['cedula']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($func['nombre_cargo']); ?></td>
                                    <td><?php echo htmlspecialchars($func['departamento']); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-success';
                                        $estado_texto = 'Activo';
                                        
                                        switch ($func['estado']) {
                                            case 'vacaciones':
                                                $badge_class = 'badge-warning';
                                                $estado_texto = 'Vacaciones';
                                                break;
                                            case 'reposo':
                                                $badge_class = 'badge-info';
                                                $estado_texto = 'Reposo';
                                                break;
                                            case 'inactivo':
                                                $badge_class = 'badge-danger';
                                                $estado_texto = 'Inactivo';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $estado_texto; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../funcionarios/ver.php?id=<?php echo $func['id']; ?>" class="btn-icon" title="Ver expediente">
                                            →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../publico/js/app.js"></script>
    <script>
        // Configuración global de Chart.js
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#718096';
        
        // Gráfico de Distribución por Departamento (Doughnut)
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Soporte Técnico', 'Sistemas', 'Redes y Telecom', 'Atención al Usuario', 'Reparaciones'],
                datasets: [{
                    data: [<?php 
                        $db = getDB();
                        $depts = $db->query("SELECT d.nombre, COUNT(f.id) as total FROM departamentos d LEFT JOIN funcionarios f ON d.id = f.departamento_id AND f.estado = 'activo' GROUP BY d.id ORDER BY d.id")->fetchAll();
                        echo implode(',', array_column($depts, 'total'));
                    ?>],
                    backgroundColor: [
                        '#8b5cf6',
                        '#06b6d4',
                        '#3b82f6',
                        '#14b8a6',
                        '#f59e0b'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                }
            }
        });
        
        // Gráfico de Tendencia Mensual (Line)
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Ingresos',
                    data: [3, 2, 4, 3, 5, 2, 4, 3, 2, 4, 3, 2],
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#06b6d4',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }, {
                    label: 'Egresos',
                    data: [1, 3, 2, 1, 2, 3, 1, 2, 3, 1, 2, 1],
                    borderColor: '#ef476f',
                    backgroundColor: 'rgba(239, 71, 111, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ef476f',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            padding: 10
                        }
                    }
                }
            }
        });
        
        // Real-time search filter for funcionarios
        document.getElementById('buscar')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#tabla-funcionarios tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
