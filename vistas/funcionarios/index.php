<?php
/**
 * Vista: Listado de Funcionarios
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

// Verificar sesión
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
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();
$cargos = $db->query("SELECT * FROM cargos ORDER BY nivel_acceso, nombre_cargo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Funcionarios - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Directorio de Funcionarios</h1>
            </div>
            
            <div class="header-right">
                <?php if (verificarNivel(2)): ?>
                    <a href="crear.php" class="btn btn-primary">
                        <span>+</span> Nuevo Funcionario
                    </a>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2 class="card-title">Filtros de Búsqueda</h2>
                </div>
                <div style="padding: 24px;">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div class="form-group" style="margin: 0;">
                            <label for="buscar" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar</label>
                            <input 
                                type="text" 
                                id="searchFuncionarios" 
                                name="buscar" 
                                class="search-input" 
                                placeholder="🔍 Buscar funcionario..."
                                value="<?php echo htmlspecialchars($filtros['buscar']); ?>"
                                style="width: 100%;"
                            >
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label for="departamento_id" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento</label>
                            <select id="departamento_id" name="departamento_id" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $filtros['departamento_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label for="cargo_id" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Cargo</label>
                            <select id="cargo_id" name="cargo_id" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <?php foreach ($cargos as $cargo): ?>
                                    <option value="<?php echo $cargo['id']; ?>" <?php echo $filtros['cargo_id'] == $cargo['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label for="estado" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Estado</label>
                            <select id="estado" name="estado" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <option value="activo" <?php echo $filtros['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="vacaciones" <?php echo $filtros['estado'] == 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                <option value="reposo" <?php echo $filtros['estado'] == 'reposo' ? 'selected' : ''; ?>>Reposo</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; align-items: flex-end; gap: 8px;">
                            <button type="submit" class="btn" style="flex: 1; background: #4299e1; color: white;">Buscar</button>
                            <a href="index.php" class="btn" style="flex: 1; background: #e2e8f0; color: #2d3748; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabla de Funcionarios -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Listado de Funcionarios</h2>
                        <p class="card-subtitle"><?php echo count($funcionarios); ?> funcionario(s) encontrado(s)</p>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table" id="funcionariosTable">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th>Antigüedad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($funcionarios)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #a0aec0;">
                                        No se encontraron funcionarios
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($funcionarios as $func): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($func['nombres'], 0, 1) . substr($func['apellidos'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($func['nombre_completo']); ?></div>
                                                    <div class="user-id"><?php echo htmlspecialchars($func['cedula']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($func['nombre_cargo']); ?></td>
                                        <td><?php echo htmlspecialchars($func['departamento']); ?></td>
                                        <td><?php echo $func['antiguedad_anos']; ?> año(s)</td>
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
                                            <div style="display: flex; gap: 8px;">
                                                <a href="ver.php?id=<?php echo $func['id']; ?>" class="btn-icon" title="Ver expediente">
                                                    👁️
                                                </a>
                                                <?php if (verificarDepartamento($func['id'])): ?>
                                                    <a href="editar.php?id=<?php echo $func['id']; ?>" class="btn-icon" title="Editar">
                                                        ✏️
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
    
    
    <script src="../../publico/js/app.js"></script>
    <script>
        // Real-time search filter for funcionarios
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
