<?php
/**
 * Módulo de Funcionarios - SIGED Enterprise
 * Sistema de Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

verificarSesion();

// Obtener filtros
$filtros = [
    'buscar' => $_GET['buscar'] ?? '',
    'departamento_id' => $_GET['departamento_id'] ?? '',
    'cargo_id' => $_GET['cargo_id'] ?? '',
    'estado' => $_GET['estado'] ?? ''
];

// Obtener funcionarios
$modeloFuncionario = new Funcionario();
$funcionarios = $modeloFuncionario->obtenerTodos($filtros);

// Obtener departamentos y cargos para filtros
$db = getDB();
$departamentos = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
$cargos = $db->query("SELECT * FROM cargos ORDER BY nivel_acceso, nombre_cargo")->fetchAll();

// Calcular estadísticas
$total_funcionarios = count($funcionarios);
$funcionarios_activos = count(array_filter($funcionarios, fn($f) => $f['estado'] === 'activo'));
$funcionarios_vacaciones = count(array_filter($funcionarios, fn($f) => $f['estado'] === 'vacaciones'));
$funcionarios_nuevos = count(array_filter($funcionarios, fn($f) => 
    strtotime($f['fecha_ingreso']) > strtotime('-30 days')
));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Funcionarios - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <script src="<?= APP_URL ?>/publico/js/filtros-tiempo-real.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <?= Icon::get('users') ?>
                Directorio de Funcionarios
            </h1>
            <?php if (verificarNivel(2)): ?>
                <a href="crear.php" class="btn-primary">
                    <?= Icon::get('plus') ?>
                    Nuevo Funcionario
                </a>
            <?php endif; ?>
        </div>
        
        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon gradient-blue">
                    <?= Icon::get('users') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= $total_funcionarios ?></div>
                    <div class="kpi-label">Total Funcionarios</div>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon gradient-green">
                    <?= Icon::get('check-circle') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= $funcionarios_activos ?></div>
                    <div class="kpi-label">Activos</div>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon gradient-cyan">
                    <?= Icon::get('sun') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= $funcionarios_vacaciones ?></div>
                    <div class="kpi-label">En Vacaciones</div>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon gradient-orange">
                    <?= Icon::get('user-plus') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= $funcionarios_nuevos ?></div>
                    <div class="kpi-label">Nuevos (30 días)</div>
                </div>
            </div>
        </div>
        
        <!-- Filters Card -->
        <div class="card-modern" style="margin-bottom: 24px;">
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <?= Icon::get('filter', 'width: 20px; height: 20px; stroke: var(--color-primary);') ?>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--color-text);">Filtros de Búsqueda</h3>
                </div>
                
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text-light); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= Icon::get('search', 'width: 14px; height: 14px; display: inline; vertical-align: middle;') ?>
                            Buscar
                        </label>
                        <input 
                            type="text" 
                            id="searchFuncionarios" 
                            name="buscar" 
                            class="search-input" 
                            placeholder="Nombre, cédula..."
                            value="<?= htmlspecialchars($filtros['buscar']) ?>"
                        >
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text-light); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= Icon::get('building', 'width: 14px; height: 14px; display: inline; vertical-align: middle;') ?>
                            Departamento
                        </label>
                        <select name="departamento_id" class="search-input">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= $filtros['departamento_id'] == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text-light); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= Icon::get('briefcase', 'width: 14px; height: 14px; display: inline; vertical-align: middle;') ?>
                            Cargo
                        </label>
                        <select name="cargo_id" class="search-input">
                            <option value="">Todos</option>
                            <?php foreach ($cargos as $cargo): ?>
                                <option value="<?= $cargo['id'] ?>" <?= $filtros['cargo_id'] == $cargo['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cargo['nombre_cargo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text-light); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= Icon::get('activity', 'width: 14px; height: 14px; display: inline; vertical-align: middle;') ?>
                            Estado
                        </label>
                        <select name="estado" class="search-input">
                            <option value="">Todos</option>
                            <option value="activo" <?= $filtros['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="vacaciones" <?= $filtros['estado'] == 'vacaciones' ? 'selected' : '' ?>>Vacaciones</option>
                            <option value="reposo" <?= $filtros['estado'] == 'reposo' ? 'selected' : '' ?>>Reposo</option>
                            <option value="inactivo" <?= $filtros['estado'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            <?= Icon::get('search') ?>
                            Buscar
                        </button>
                        <a href="index.php" class="btn-secondary" style="width: 100%; justify-content: center;">
                            <?= Icon::get('x') ?>
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Table Card -->
        <div class="card-modern">
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table-modern" id="funcionariosTable">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th>Antigüedad</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($funcionarios)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('users', 'width: 64px; height: 64px; opacity: 0.3;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay funcionarios</div>
                                            <p class="empty-state-description">
                                                No se encontraron funcionarios con los filtros seleccionados.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($funcionarios as $func): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($func['nombres'], 0, 1) . substr($func['apellidos'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?= htmlspecialchars($func['nombre_completo']) ?></div>
                                                    <div class="user-id"><?= htmlspecialchars($func['cedula']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($func['nombre_cargo']) ?></td>
                                        <td><?= htmlspecialchars($func['departamento']) ?></td>
                                        <td><?= $func['antiguedad_anos'] ?> año<?= $func['antiguedad_anos'] != 1 ? 's' : '' ?></td>
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
                                            <span class="badge <?= $badge_class ?>">
                                                <?= $estado_texto ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 8px;">
                                                <a href="ver.php?id=<?= $func['id'] ?>" class="btn-icon" title="Ver expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if (verificarDepartamento($func['id'])): ?>
                                                    <a href="editar.php?id=<?= $func['id'] ?>" class="btn-icon" title="Editar">
                                                        <?= Icon::get('edit') ?>
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
    
    <script>
        // Real-time search filter
        document.getElementById('searchFuncionarios')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#funcionariosTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
