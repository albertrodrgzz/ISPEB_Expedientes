<?php
/**
 * Dashboard Principal
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 * Bifurca la vista según nivel de acceso (Nivel 3 = portal empleado)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

$nivel_acceso      = $_SESSION['nivel_acceso'] ?? 3;
$funcionario_id    = $_SESSION['funcionario_id'] ?? 0;
$db                = getDB();

/* ================================================================
   RAMA NIVEL 3 – Portal de Autogestión
   ================================================================ */
if ($nivel_acceso >= 3) {

    // Datos propios del empleado
    $stmt = $db->prepare("
        SELECT f.id, f.cedula, CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
               f.foto, f.estado, f.fecha_ingreso,
               c.nombre_cargo, d.nombre AS departamento
        FROM   funcionarios f
        INNER JOIN cargos c       ON f.cargo_id       = c.id
        INNER JOIN departamentos d ON f.departamento_id = d.id
        WHERE  f.id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $mi_perfil = $stmt->fetch();

    if (!$mi_perfil) {
        session_destroy();
        header('Location: ' . APP_URL . '/index.php?error=sesion_invalida');
        exit;
    }

    // Calcular antigüedad
    $antiguedad = '';
    if ($mi_perfil['fecha_ingreso']) {
        $diff      = (new DateTime())->diff(new DateTime($mi_perfil['fecha_ingreso']));
        $antiguedad = $diff->y . ' año' . ($diff->y != 1 ? 's' : '');
        if ($diff->m > 0) $antiguedad .= ', ' . $diff->m . ' mes' . ($diff->m != 1 ? 'es' : '');
    }

    // Mis solicitudes recientes (si la tabla existe)
    $mis_solicitudes    = [];
    $kpi_pendientes     = 0;
    $kpi_aprobadas      = 0;
    $kpi_rechazadas     = 0;
    try {
        $stmt = $db->prepare("
            SELECT id, tipo_solicitud, fecha_inicio, fecha_fin, estado, created_at
            FROM   solicitudes_empleados
            WHERE  funcionario_id = ?
            ORDER BY created_at DESC LIMIT 5
        ");
        $stmt->execute([$funcionario_id]);
        $mis_solicitudes = $stmt->fetchAll();

        $stmt2 = $db->prepare("
            SELECT
                SUM(estado = 'pendiente')  AS pendientes,
                SUM(estado = 'aprobada')   AS aprobadas,
                SUM(estado = 'rechazada')  AS rechazadas
            FROM solicitudes_empleados WHERE funcionario_id = ?
        ");
        $stmt2->execute([$funcionario_id]);
        $kpis          = $stmt2->fetch();
        $kpi_pendientes = (int)($kpis['pendientes'] ?? 0);
        $kpi_aprobadas  = (int)($kpis['aprobadas']  ?? 0);
        $kpi_rechazadas = (int)($kpis['rechazadas'] ?? 0);
    } catch (Exception $e) { /* tabla aún no creada */ }

    $badge_estado = match($mi_perfil['estado']) {
        'activo'    => 'badge-success',
        'vacaciones'=> 'badge-warning',
        'reposo'    => 'badge-info',
        default     => 'badge-secondary'
    };
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Portal – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        /* ── Portal Nivel 3 ── */
        .portal-hero {
            background: linear-gradient(135deg, #0F4C81 0%, #0284C7 60%, #06b6d4 100%);
            border-radius: 20px;
            padding: 32px;
            color: white;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(15, 76, 129, .35);
        }
        .portal-avatar {
            width: 90px; height: 90px; border-radius: 50%;
            border: 4px solid rgba(255,255,255,.4);
            background: rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 34px; font-weight: 800; color: white;
            flex-shrink: 0; overflow: hidden;
        }
        .portal-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .portal-hero h1  { font-size: 22px; margin: 0 0 6px; font-weight: 800; }
        .portal-hero p   { margin: 0; opacity: .85; font-size: 14px; }
        .portal-badge    { display: inline-block; margin-top: 10px; padding: 4px 14px;
                           background: rgba(255,255,255,.2); border-radius: 20px;
                           font-size: 12px; font-weight: 700; }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .quick-card {
            background: white; border-radius: 16px; padding: 24px;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            text-decoration: none; color: inherit;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            border: 2px solid transparent;
            transition: all .25s ease;
        }
        .quick-card:hover {
            border-color: #0284C7;
            box-shadow: 0 6px 24px rgba(2,132,199,.18);
            transform: translateY(-3px);
        }
        .quick-card .qc-icon {
            width: 54px; height: 54px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
        }
        .quick-card .qc-label { font-size: 14px; font-weight: 700; color: #1e293b; text-align: center; }
        .qc-blue   { background: #dbeafe; color: #1d4ed8; }
        .qc-green  { background: #d1fae5; color: #065f46; }
        .qc-yellow { background: #fef9c3; color: #854d0e; }
        .qc-purple { background: #ede9fe; color: #5b21b6; }

        .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .kpi-mini { background: white; border-radius: 14px; padding: 20px;
                    text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .kpi-mini .val { font-size: 32px; font-weight: 800; }
        .kpi-mini .lbl { font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 600; text-transform: uppercase; }
        .c-pending { color: #f59e0b; }
        .c-approved { color: #10b981; }
        .c-rejected { color: #ef4444; }

        .solicitud-row { display: flex; align-items: center; justify-content: space-between;
                         padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
        .solicitud-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>

        <div class="content-wrapper">

            <!-- Hero card del empleado -->
            <div class="portal-hero">
                <div class="portal-avatar">
                    <?php if ($mi_perfil['foto']): ?>
                        <img src="<?= APP_URL ?>/subidas/fotos/<?= htmlspecialchars($mi_perfil['foto']) ?>" alt="Foto">
                    <?php else: ?>
                        <?= strtoupper(substr($mi_perfil['nombre_completo'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h1>Bienvenido, <?= htmlspecialchars(explode(' ', $mi_perfil['nombre_completo'])[0]) ?> 👋</h1>
                    <p><?= htmlspecialchars($mi_perfil['nombre_cargo']) ?> &bull; <?= htmlspecialchars($mi_perfil['departamento']) ?></p>
                    <p style="margin-top:6px; opacity:.8"><strong>Antigüedad:</strong> <?= $antiguedad ?></p>
                    <span class="portal-badge"><?= ucfirst($mi_perfil['estado']) ?></span>
                </div>
            </div>

            <!-- Accesos Rápidos -->
            <h2 style="font-size:16px; font-weight:700; color:#1e293b; margin-bottom:16px;">Accesos Rápidos</h2>
            <div class="quick-grid">
                <a href="<?= APP_URL ?>/vistas/funcionarios/ver.php" class="quick-card">
                    <div class="qc-icon qc-blue"><?= Icon::get('user') ?></div>
                    <span class="qc-label">Mi Expediente</span>
                </a>
                <a href="<?= APP_URL ?>/vistas/reportes/constancia_trabajo.php" target="_blank" class="quick-card">
                    <div class="qc-icon qc-green"><?= Icon::get('file-text') ?></div>
                    <span class="qc-label">Constancia de Trabajo</span>
                </a>
                <a href="<?= APP_URL ?>/vistas/solicitudes/mis_solicitudes.php" class="quick-card">
                    <div class="qc-icon qc-purple"><?= Icon::get('send') ?></div>
                    <span class="qc-label">Mis Solicitudes</span>
                </a>
                <a href="<?= APP_URL ?>/vistas/reportes/index.php" class="quick-card">
                    <div class="qc-icon qc-yellow"><?= Icon::get('bar-chart') ?></div>
                    <span class="qc-label">Mis Reportes</span>
                </a>
            </div>

            <!-- KPIs de solicitudes -->
            <div class="kpi-row">
                <div class="kpi-mini">
                    <div class="val c-pending"><?= $kpi_pendientes ?></div>
                    <div class="lbl">Pendientes</div>
                </div>
                <div class="kpi-mini">
                    <div class="val c-approved"><?= $kpi_aprobadas ?></div>
                    <div class="lbl">Aprobadas</div>
                </div>
                <div class="kpi-mini">
                    <div class="val c-rejected"><?= $kpi_rechazadas ?></div>
                    <div class="lbl">Rechazadas</div>
                </div>
            </div>

            <!-- Mis solicitudes recientes -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Mis Solicitudes Recientes</h2>
                        <p class="card-subtitle">Últimas 5 solicitudes enviadas</p>
                    </div>
                    <div class="card-actions">
                        <a href="<?= APP_URL ?>/vistas/solicitudes/mis_solicitudes.php" class="btn btn-primary">
                            Ver todas
                        </a>
                    </div>
                </div>
                <div style="padding: 0 24px 24px;">
                    <?php if (empty($mis_solicitudes)): ?>
                        <div style="text-align:center; padding:40px; color:#94a3b8;">
                            <?= Icon::get('inbox') ?>
                            <p style="margin-top:12px;">No has enviado solicitudes todavía.<br>
                            <a href="<?= APP_URL ?>/vistas/solicitudes/mis_solicitudes.php" style="color:#0284C7; font-weight:600;">Crear primera solicitud →</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($mis_solicitudes as $sol): ?>
                            <?php
                                $badge_sol = match($sol['estado']) {
                                    'aprobada'  => 'background:#d1fae5;color:#065f46',
                                    'rechazada' => 'background:#fee2e2;color:#991b1b',
                                    default     => 'background:#fef9c3;color:#854d0e'
                                };
                                $tipo_label = $sol['tipo_solicitud'] === 'vacaciones' ? '🌴 Vacaciones' : '📋 Permiso';
                            ?>
                            <div class="solicitud-row">
                                <div>
                                    <div style="font-weight:700; font-size:14px;"><?= $tipo_label ?></div>
                                    <div style="font-size:12px; color:#64748b; margin-top:3px;">
                                        <?= date('d/m/Y', strtotime($sol['fecha_inicio'])) ?>
                                        — <?= date('d/m/Y', strtotime($sol['fecha_fin'])) ?>
                                    </div>
                                </div>
                                <span style="padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; <?= $badge_sol ?>">
                                    <?= ucfirst($sol['estado']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /content-wrapper -->
    </div><!-- /main-content -->

    <script src="<?= APP_URL ?>/publico/js/app.js"></script>
</body>
</html>
<?php
/* ================================================================
   RAMA NIVEL 1/2 – Dashboard Global de Administración
   ================================================================ */
} else {

    // Total de funcionarios
    $stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado != 'inactivo'");
    $total_personal = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
    $activos = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
    $de_vacaciones = $stmt->fetch()['total'];

    $stmt = $db->query("
        SELECT COUNT(*) as total
        FROM historial_administrativo
        WHERE tipo_evento = 'NOMBRAMIENTO'
        AND fecha_evento BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
    ");
    $por_vencer = $stmt->fetch()['total'];

    // Solicitudes pendientes (si la tabla existe)
    $sol_pendientes = 0;
    try {
        $sol_pendientes = $db->query("SELECT COUNT(*) FROM solicitudes_empleados WHERE estado='pendiente'")->fetchColumn();
    } catch (Exception $e) {}

    $stmt = $db->query("
        SELECT
            f.id, f.cedula,
            CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
            f.foto, c.nombre_cargo, d.nombre AS departamento, f.estado
        FROM funcionarios f
        INNER JOIN cargos c ON f.cargo_id = c.id
        INNER JOIN departamentos d ON f.departamento_id = d.id
        WHERE f.estado != 'inactivo'
        ORDER BY f.created_at DESC LIMIT 10
    ");
    $funcionarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    <script src="<?php echo APP_URL; ?>/publico/vendor/chart.js/chart.umd.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>

        <div class="content-wrapper">
            <!-- KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card kpi-primary">
                    <div class="kpi-icon"><?php echo Icon::get('users'); ?></div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Personal</div>
                        <div class="kpi-value"><?php echo $total_personal; ?></div>
                    </div>
                </div>

                <div class="kpi-card kpi-success">
                    <div class="kpi-icon"><?php echo Icon::get('check-circle'); ?></div>
                    <div class="kpi-content">
                        <div class="kpi-label">Activos</div>
                        <div class="kpi-value"><?php echo $activos; ?></div>
                    </div>
                </div>

                <div class="kpi-card kpi-warning">
                    <div class="kpi-icon"><?php echo Icon::get('sun'); ?></div>
                    <div class="kpi-content">
                        <div class="kpi-label">De Vacaciones</div>
                        <div class="kpi-value"><?php echo $de_vacaciones; ?></div>
                    </div>
                </div>

                <div class="kpi-card kpi-info">
                    <div class="kpi-icon"><?php echo Icon::get('bell'); ?></div>
                    <div class="kpi-content">
                        <div class="kpi-label">Nombr. Recientes</div>
                        <div class="kpi-value"><?php echo $por_vencer; ?></div>
                    </div>
                </div>

                <?php if ($sol_pendientes > 0): ?>
                <div class="kpi-card" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); border-left: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(217,119,6,0.35);">
                    <div class="kpi-icon" style="color: rgba(255,255,255,0.9)"><?php echo Icon::get('inbox'); ?></div>
                    <div class="kpi-content">
                        <div class="kpi-label" style="color: rgba(255,255,255,0.85)">Solicitudes Pendientes</div>
                        <div class="kpi-value" style="color: #ffffff"><?php echo $sol_pendientes; ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Gráficos -->
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
                        <p class="card-subtitle">Últimos funcionarios registrados</p>
                    </div>
                    <div class="card-actions">
                        <input type="text" id="buscar" class="search-input" placeholder="Buscar funcionario...">
                        <?php if (verificarNivel(2)): ?>
                            <a href="<?= APP_URL ?>/vistas/funcionarios/crear.php" class="btn btn-primary">
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
                                                <?= strtoupper(substr($func['nombre_completo'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div class="user-name"><?= htmlspecialchars($func['nombre_completo']) ?></div>
                                                <div class="user-id"><?= htmlspecialchars($func['cedula']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($func['nombre_cargo']) ?></td>
                                    <td><?= htmlspecialchars($func['departamento']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-success';
                                        $estado_texto = 'Activo';
                                        switch ($func['estado']) {
                                            case 'vacaciones': $badge_class = 'badge-warning'; $estado_texto = 'Vacaciones'; break;
                                            case 'reposo':     $badge_class = 'badge-info';    $estado_texto = 'Reposo';     break;
                                            case 'inactivo':   $badge_class = 'badge-danger';  $estado_texto = 'Inactivo';   break;
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $estado_texto ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= APP_URL ?>/vistas/funcionarios/ver.php?id=<?= $func['id'] ?>" class="btn-icon" title="Ver expediente">
                                            <?= Icon::get('arrow-right') ?>
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

    <script src="<?php echo APP_URL; ?>/publico/js/app.js"></script>
    <script>
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#718096';

        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        <?php
        $depts = $db->query("SELECT d.nombre, COUNT(f.id) as total FROM departamentos d LEFT JOIN funcionarios f ON d.id = f.departamento_id AND f.estado = 'activo' GROUP BY d.id ORDER BY d.id")->fetchAll();
        ?>
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($depts, 'nombre')) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('intval', array_column($depts, 'total'))) ?>,
                    backgroundColor: ['#8b5cf6','#06b6d4','#3b82f6','#14b8a6','#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: true,
                plugins: { legend: { position:'bottom', labels:{ padding:15, usePointStyle:true } } } }
        });

        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
                datasets: [{
                    label: 'Ingresos', data: [3,2,4,3,5,2,4,3,2,4,3,2],
                    borderColor:'#06b6d4', backgroundColor:'rgba(6,182,212,.1)',
                    tension:0.4, fill:true, pointRadius:4, pointBackgroundColor:'#06b6d4', pointBorderColor:'#fff', pointBorderWidth:2
                },{
                    label: 'Egresos', data: [1,3,2,1,2,3,1,2,3,1,2,1],
                    borderColor:'#ef476f', backgroundColor:'rgba(239,71,111,.1)',
                    tension:0.4, fill:true, pointRadius:4, pointBackgroundColor:'#ef476f', pointBorderColor:'#fff', pointBorderWidth:2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                interaction: { intersect:false, mode:'index' },
                plugins: { legend: { position:'bottom', labels:{ padding:15, usePointStyle:true } } },
                scales: {
                    y: { beginAtZero:true, grid:{ color:'rgba(0,0,0,.05)', drawBorder:false }, ticks:{ padding:10 } },
                    x: { grid:{ display:false, drawBorder:false }, ticks:{ padding:10 } }
                }
            }
        });

        document.getElementById('buscar')?.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('#tabla-funcionarios tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
<?php } ?>
