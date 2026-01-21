<?php
/**
 * Vista: Log de Auditoría
 * Solo accesible para nivel 1
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos
verificarSesion();

if (!verificarNivel(1)) {
    $_SESSION['error'] = 'Solo los administradores pueden acceder a la auditoría';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Filtros
$filtro_accion = $_GET['accion'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$pagina = $_GET['pagina'] ?? 1;
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

// Construir query
$where = [];
$params = [];

if ($filtro_accion) {
    $where[] = "a.accion LIKE ?";
    $params[] = "%$filtro_accion%";
}

if ($filtro_usuario) {
    $where[] = "a.usuario_id = ?";
    $params[] = $filtro_usuario;
}

if ($filtro_fecha_desde) {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $filtro_fecha_hasta;
}

$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Obtener total de registros
$stmt = $db->prepare("SELECT COUNT(*) as total FROM auditoria a $where_clause");
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener registros de auditoría
$stmt = $db->prepare("
    SELECT 
        a.*,
        CONCAT(f.nombres, ' ', f.apellidos) AS usuario_nombre
    FROM auditoria a
    LEFT JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN funcionarios f ON u.funcionario_id = f.id
    $where_clause
    ORDER BY a.created_at DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmt->execute($params);
$registros = $stmt->fetchAll();

// Obtener lista de usuarios para filtro
$stmt = $db->query("
    SELECT DISTINCT
        u.id,
        CONCAT(f.nombres, ' ', f.apellidos) AS nombre
    FROM usuarios u
    INNER JOIN funcionarios f ON u.funcionario_id = f.id
    ORDER BY nombre
");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .filters {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--color-text);
        }
        
        .pagination .active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }
        
        .log-details {
            font-size: 12px;
            color: var(--color-text-light);
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Log de Auditoría</h1>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                    ← Volver a Administración
                </a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Filtros -->
            <div class="filters">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Filtros de Búsqueda</h3>
                <form method="GET" action="">
                    <div class="filter-grid">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Acción</label>
                            <input type="text" name="accion" class="search-input" style="width: 100%;" placeholder="Ej: LOGIN" value="<?php echo htmlspecialchars($filtro_accion); ?>">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Usuario</label>
                            <select name="usuario" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <?php foreach ($usuarios as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $filtro_usuario == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Desde</label>
                            <input type="date" name="fecha_desde" class="search-input" style="width: 100%;" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Hasta</label>
                            <input type="date" name="fecha_hasta" class="search-input" style="width: 100%;" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="auditoria.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">Limpiar</a>
                    </div>
                </form>
            </div>
            
            <!-- Tabla de Auditoría -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Registros de Auditoría</h2>
                        <p class="card-subtitle"><?php echo number_format($total_registros); ?> registro(s) encontrado(s)</p>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Tabla</th>
                                <th>IP</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($registros) > 0): ?>
                                <?php foreach ($registros as $reg): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($reg['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($reg['usuario_nombre'] ?? 'Sistema'); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($reg['accion']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($reg['tabla_afectada'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['ip_address'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($reg['datos_nuevos'] || $reg['datos_anteriores']): ?>
                                                <button onclick="verDetalles(<?php echo $reg['id']; ?>)" class="btn-small" style="background: var(--color-primary); color: white;">
                                                    Ver
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #a0aec0;">
                                        No se encontraron registros
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?php echo $pagina - 1; ?>&accion=<?php echo urlencode($filtro_accion); ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>">
                                ← Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                            <?php if ($i == $pagina): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?pagina=<?php echo $i; ?>&accion=<?php echo urlencode($filtro_accion); ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?>&accion=<?php echo urlencode($filtro_accion); ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>">
                                Siguiente →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function verDetalles(id) {
            // Aquí se podría implementar un modal con los detalles JSON
            alert('Funcionalidad de detalles en desarrollo. ID: ' + id);
        }
    </script>
</body>
</html>
