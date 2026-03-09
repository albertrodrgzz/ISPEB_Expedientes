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
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <script src="<?= APP_URL ?>/publico/js/filtros-tiempo-real.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $pageTitle = 'Directorio de Funcionarios';
        include __DIR__ . '/../layout/header.php'; 
        ?>
        
        <div class="module-container">
            <!-- Header Título y Botón -->
            <div class="module-header-title">
                <div class="module-title-group">
                    <?= Icon::get('users') ?>
                    <h2 class="module-title-text">Directorio de Funcionarios</h2>
                </div>
                <?php if (verificarNivel(2)): ?>
                    <a href="crear.php" class="btn-primary" style="padding: 10px 20px; border-radius: 8px;">
                        <?= Icon::get('plus') ?> Nuevo Funcionario
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card-solid bg-solid-blue">
                    <div class="kpi-icon">
                        <?= Icon::get('users') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">Total Funcionarios</div>
                        <div class="kpi-value"><?= $total_funcionarios ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-green">
                    <div class="kpi-icon">
                        <?= Icon::get('check-circle') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">Activos</div>
                        <div class="kpi-value"><?= $funcionarios_activos ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-orange">
                    <div class="kpi-icon">
                        <?= Icon::get('sun') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">En Vacaciones</div>
                        <div class="kpi-value"><?= $funcionarios_vacaciones ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-cyan">
                    <div class="kpi-icon">
                        <?= Icon::get('star') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-label">Nuevos (30 días)</div>
                        <div class="kpi-value"><?= $funcionarios_nuevos ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros Flat -->
            <div class="flat-filter-bar" style="margin-top: 32px;">
                <div class="filter-item" style="flex: 2;">
                    <label class="filter-label">BUSCAR</label>
                    <input type="text" id="buscarFuncionario" class="form-control" placeholder="Nombre, cédula...">
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">DEPARTAMENTO</label>
                    <select id="filtroDepartamento" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($departamentos as $dep):
                            $depVal = strtolower($dep['nombre']);
                        ?>
                            <option value="<?= htmlspecialchars($depVal) ?>" <?= $filtros['departamento_id'] == $dep['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">CARGO</label>
                    <select id="filtroCargo" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($cargos as $cargo):
                            $cargoVal = strtolower($cargo['nombre_cargo']);
                        ?>
                            <option value="<?= htmlspecialchars($cargoVal) ?>" <?= $filtros['cargo_id'] == $cargo['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cargo['nombre_cargo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1;">
                    <label class="filter-label">ESTADO</label>
                    <select id="filtroEstado" class="form-control">
                        <option value="">Todos</option>
                        <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="vacaciones" <?= $filtros['estado'] === 'vacaciones' ? 'selected' : '' ?>>Vacaciones</option>
                        <option value="inactivo" <?= $filtros['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 0 0 auto;">
                    <button id="btnLimpiar" class="btn-flat-outline">
                        Limpiar
                    </button>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="table-wrapper">
                <table id="tablaFuncionarios" class="table-modern">
                    <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th>Antigüedad</th>
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($funcionarios)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('users', 'opacity: 0.3; width: 64px; height: 64px;') ?>
                                            </div>
                                            <div class="empty-state-title">No se encontraron funcionarios</div>
                                            <p class="empty-state-description">Intenta ajustar los filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($funcionarios as $f): ?>
                                    <?php
                                    // Calcular antigüedad para mostrar
                                    $antiguedad_texto = '0 años';
                                    if ($f['fecha_ingreso']) {
                                        $fi = new DateTime($f['fecha_ingreso']);
                                        $now = new DateTime();
                                        $diff = $now->diff($fi);
                                        $antiguedad_texto = $diff->y . ' años';
                                    }
                                    
                                    // Datos para filtrado JS
                                    $dataDept = strtolower($f['nombre_departamento'] ?? $f['departamento']);
                                    $dataCargo = strtolower($f['nombre_cargo']);
                                    $dataEstado = strtolower($f['estado']);
                                    ?>
                                    <tr data-departamento="<?= $dataDept ?>" data-cargo="<?= $dataCargo ?>" data-estado="<?= $dataEstado ?>">
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div class="avatar-circle" style="
                                                    width: 40px; 
                                                    height: 40px; 
                                                    border-radius: 50%; 
                                                    background: var(--color-primary); 
                                                    color: white; 
                                                    display: flex; 
                                                    align-items: center; 
                                                    justify-content: center; 
                                                    font-weight: 700;
                                                    font-size: 14px;
                                                ">
                                                    <?= strtoupper(substr($f['nombres'], 0, 1) . substr($f['apellidos'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($f['nombres'] . ' ' . $f['apellidos']) ?></div>
                                                    <div style="font-size: 12px; color: var(--color-text-light);"><?= htmlspecialchars($f['cedula']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($f['nombre_cargo']) ?></td>
                                        <td><?= htmlspecialchars($f['nombre_departamento'] ?? $f['departamento']) ?></td>
                                        <td><?= $antiguedad_texto ?></td>
                                        <td style="text-align: center;">
                                            <?php
                                            $estadoClass = 'badge-success';
                                            if ($f['estado'] === 'vacaciones') $estadoClass = 'badge-warning';
                                            if ($f['estado'] === 'inactivo') $estadoClass = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $estadoClass ?>">
                                                <?= ucfirst($f['estado']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 8px;">
                                                <a href="ver.php?id=<?= $f['id'] ?>" class="btn-icon" title="Ver Expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if (verificarNivel(2)): ?>
                                                    <a href="editar.php?id=<?= $f['id'] ?>" class="btn-icon" title="Editar">
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
                <!-- Contador de resultados -->
                <div style="padding: 12px 16px; border-top: 1px solid var(--color-border-light); font-size: 13px; color: var(--color-text-light);">
                    Mostrando <strong id="contadorVisible">0</strong> de <strong id="contadorTotal">0</strong> funcionarios
                </div> <!-- End Table Wrapper -->
            </div> <!-- End Module Container -->
        </div> <!-- End Main Content -->
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Inicializando filtros en tiempo real...');
        
        // Verificar si la librería cargó
        const totalRows = document.querySelectorAll('#tablaFuncionarios tbody tr').length;
        document.getElementById('contadorTotal').textContent = totalRows;
        document.getElementById('contadorVisible').textContent = totalRows;

        if (typeof initTableFilters === 'function') {
            const filters = initTableFilters({
                tableId: 'tablaFuncionarios',
                searchInputId: 'buscarFuncionario',
                selectFilters: [
                    { id: 'filtroDepartamento', dataAttribute: 'departamento' },
                    { id: 'filtroCargo', dataAttribute: 'cargo' },
                    { id: 'filtroEstado', dataAttribute: 'estado' }
                ],
                onFilter: function(visible, total) {
                    document.getElementById('contadorVisible').textContent = visible;
                    document.getElementById('contadorTotal').textContent = total;
                }
            });

            // Botón limpiar
            document.getElementById('btnLimpiar').addEventListener('click', function() {
                if (filters && typeof filters.clearFilters === 'function') {
                    filters.clearFilters();
                }
            });

        } else {
            console.error('La función initTableFilters no está definida. Verifique filtros-tiempo-real.js');
        }
    });

    /* Responsive adjustments for filters */
    const style = document.createElement('style');
    style.innerHTML = `
        @media (max-width: 1024px) {
            .filters-grid {
                grid-template-columns: 1fr 1fr !important;
            }
            .filters-grid > div:last-child {
                grid-column: span 2;
            }
        }
        @media (max-width: 640px) {
            .filters-grid {
                grid-template-columns: 1fr !important;
            }
            .filters-grid > div:last-child {
                grid-column: span 1;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
