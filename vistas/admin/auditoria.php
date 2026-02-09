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
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js">
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 32px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--color-border);
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .detail-item {
            padding: 12px;
            background: var(--color-bg);
            border-radius: var(--radius-md);
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--color-text-light);
            margin-bottom: 4px;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--color-text);
        }
        
        .json-viewer {
            background: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: var(--radius-md);
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-bottom: 16px;
        }
        
        .json-key {
            color: #60a5fa;
        }
        
        .json-string {
            color: #34d399;
        }
        
        .json-number {
            color: #fbbf24;
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
    
    <!-- Modal: Detalles de Auditoría -->
    <div id="detallesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Detalles del Evento de Auditoría</h2>
            </div>
            <div id="detallesContenido">
                <!-- Se llenará dinámicamente -->
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                <button class="btn" onclick="cerrarModal()">Cerrar</button>
            </div>
        </div>
    </div>
    
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="../../publico/js/sweetalert-utils.js"></script>
    
    <script>
        function verDetalles(id) {
            mostrarCargando('Cargando detalles...');
            
            fetch(`ajax/obtener_evento_auditoria.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    cerrarCargando();
                    
                    if (data.success) {
                        mostrarModalDetalles(data.data);
                    } else {
                        mostrarError(data.message);
                    }
                })
                .catch(error => {
                    cerrarCargando();
                    mostrarError('Error al cargar los detalles');
                    console.error(error);
                });
        }
        
        function mostrarModalDetalles(evento) {
            let html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Usuario</div>
                        <div class="detail-value">${evento.usuario_nombre || 'Sistema'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Acción</div>
                        <div class="detail-value">${evento.accion}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tabla Afectada</div>
                        <div class="detail-value">${evento.tabla_afectada || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registro ID</div>
                        <div class="detail-value">${evento.registro_id || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Dirección IP</div>
                        <div class="detail-value">${evento.ip_address || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Fecha/Hora</div>
                        <div class="detail-value">${new Date(evento.created_at).toLocaleString('es-VE')}</div>
                    </div>
                </div>
            `;
            
            if (evento.datos_anteriores) {
                html += `
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; margin-top: 24px;">📋 Datos Anteriores</h3>
                    <div class="json-viewer">${formatJSON(evento.datos_anteriores)}</div>
                `;
            }
            
            if (evento.datos_nuevos) {
                html += `
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; margin-top: 16px;">✨ Datos Nuevos</h3>
                    <div class="json-viewer">${formatJSON(evento.datos_nuevos)}</div>
                `;
            }
            
            document.getElementById('detallesContenido').innerHTML = html;
            document.getElementById('detallesModal').classList.add('active');
        }
        
        function formatJSON(obj) {
            if (typeof obj === 'string') {
                try {
                    obj = JSON.parse(obj);
                } catch (e) {
                    return obj;
                }
            }
            
            return JSON.stringify(obj, null, 2)
                .replace(/"([^"]+)":/g, '<span class="json-key">"$1"</span>:')
                .replace(/: "([^"]+)"/g, ': <span class="json-string">"$1"</span>')
                .replace(/: (\d+)/g, ': <span class="json-number">$1</span>');
        }
        
        function cerrarModal() {
            document.getElementById('detallesModal').classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('detallesModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
