<?php
/**
 * Vista: Panel de Administración
 * Solo accesible para nivel 1 (Administradores)
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1)
verificarSesion();

if (!verificarNivel(1)) {
    $_SESSION['error'] = 'Solo los administradores pueden acceder a esta sección';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas del sistema
$stmt = $db->query("SELECT COUNT(*) as total FROM auditoria");
$total_logs = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'");
$total_usuarios_activos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
$total_usuarios = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
$total_funcionarios = $stmt->fetch()['total'];

// Contar documentos de historial_administrativo
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo");
$total_documentos = $stmt->fetch()['total'];

// Obtener tamaño de la base de datos
$stmt = $db->query("
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = '" . DB_NAME . "'
");
$db_size = $stmt->fetch()['size_mb'] ?? 0;

// Obtener últimas acciones de auditoría
$stmt = $db->query("
    SELECT 
        a.accion,
        a.tabla_afectada,
        a.ip_address,
        a.created_at,
        CONCAT(f.nombres, ' ', f.apellidos) AS usuario
    FROM auditoria a
    LEFT JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN funcionarios f ON u.funcionario_id = f.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$ultimas_acciones = $stmt->fetchAll();

// Obtener lista de usuarios para gestión
$stmt = $db->query("
    SELECT 
        u.id,
        u.username,
        u.estado,
        u.ultimo_acceso,
        u.intentos_fallidos,
        CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
        f.cedula,
        c.nombre_cargo,
        c.nivel_acceso,
        d.nombre AS departamento
    FROM usuarios u
    INNER JOIN funcionarios f ON u.funcionario_id = f.id
    INNER JOIN cargos c ON f.cargo_id = c.id
    INNER JOIN departamentos d ON f.departamento_id = d.id
    ORDER BY u.id ASC
");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--color-border);
            padding-bottom: 0;
        }
        
        .admin-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--color-text-light);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }
        
        .admin-tab:hover {
            color: var(--color-primary);
        }
        
        .admin-tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border-radius: var(--radius-lg);
            padding: 24px;
            color: white;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card.secondary {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .stat-card.tertiary {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .stat-card.quaternary {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stat-icon {
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .users-table-container {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-box {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .search-input {
            padding: 10px 16px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 14px;
            min-width: 300px;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            padding: 6px 12px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-edit:hover {
            background: #bfdbfe;
        }
        
        .btn-toggle {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-toggle:hover {
            background: #fde68a;
        }
        
        .btn-reset {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-reset:hover {
            background: #fecaca;
        }
        
        .activity-item {
            padding: 16px;
            border-bottom: 1px solid var(--color-border-light);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--color-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-action {
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
        }
        
        .activity-meta {
            font-size: 13px;
            color: var(--color-text-light);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .quick-action-card {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: var(--color-primary);
        }
        
        .quick-action-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .quick-action-title {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .quick-action-desc {
            font-size: 13px;
            color: var(--color-text-light);
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
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 24px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Panel de Administración</h1>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estadísticas Principales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_usuarios_activos; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon">👤</div>
                    <div class="stat-value"><?php echo $total_funcionarios; ?></div>
                    <div class="stat-label">Funcionarios</div>
                </div>
                <div class="stat-card tertiary">
                    <div class="stat-icon">📄</div>
                    <div class="stat-value"><?php echo $total_documentos; ?></div>
                    <div class="stat-label">Documentos</div>
                </div>
                <div class="stat-card quaternary">
                    <div class="stat-icon">💾</div>
                    <div class="stat-value"><?php echo $db_size; ?> MB</div>
                    <div class="stat-label">Tamaño BD</div>
                </div>
            </div>
            
            <!-- Tabs de Navegación -->
            <div class="admin-tabs">
                <button class="admin-tab active" onclick="switchTab('overview')">📊 Resumen</button>
                <button class="admin-tab" onclick="switchTab('users')">👥 Usuarios</button>
                <button class="admin-tab" onclick="switchTab('tools')">🛠️ Herramientas</button>
                <button class="admin-tab" onclick="switchTab('activity')">📋 Actividad</button>
            </div>
            
            <!-- Tab: Resumen -->
            <div id="tab-overview" class="tab-content active">
                <div class="quick-actions">
                    <a href="#" onclick="switchTab('users'); return false;" class="quick-action-card">
                        <div class="quick-action-icon">👥</div>
                        <div class="quick-action-title">Gestionar Usuarios</div>
                        <div class="quick-action-desc">Crear y administrar cuentas</div>
                    </a>
                    <a href="organizacion.php" class="quick-action-card">
                        <div class="quick-action-icon">🏢</div>
                        <div class="quick-action-title">Organización</div>
                        <div class="quick-action-desc">Departamentos y cargos</div>
                    </a>
                    <a href="respaldo.php" class="quick-action-card">
                        <div class="quick-action-icon">💾</div>
                        <div class="quick-action-title">Respaldo BD</div>
                        <div class="quick-action-desc">Generar copia de seguridad</div>
                    </a>
                    <a href="restaurar.php" class="quick-action-card">
                        <div class="quick-action-icon">🔄</div>
                        <div class="quick-action-title">Restaurar BD</div>
                        <div class="quick-action-desc">Restaurar desde archivo</div>
                    </a>
                    <a href="auditoria.php" class="quick-action-card">
                        <div class="quick-action-icon">📋</div>
                        <div class="quick-action-title">Auditoría</div>
                        <div class="quick-action-desc">Ver log de acciones</div>
                    </a>
                    <a href="#" onclick="alert('Próximamente: Configuración avanzada del sistema'); return false;" class="quick-action-card" style="opacity: 0.7;">
                        <div class="quick-action-icon">⚙️</div>
                        <div class="quick-action-title">Configuración</div>
                        <div class="quick-action-desc">Ajustes del sistema</div>
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Actividad Reciente</h2>
                    </div>
                    <div style="padding: 0;">
                        <?php if (count($ultimas_acciones) > 0): ?>
                            <?php foreach ($ultimas_acciones as $accion): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icono = '📝';
                                        if (strpos($accion['accion'], 'LOGIN') !== false) $icono = '🔐';
                                        elseif (strpos($accion['accion'], 'CREAR') !== false) $icono = '➕';
                                        elseif (strpos($accion['accion'], 'ELIMINAR') !== false) $icono = '🗑️';
                                        elseif (strpos($accion['accion'], 'ACTUALIZAR') !== false) $icono = '✏️';
                                        echo $icono;
                                        ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-action">
                                            <?php echo htmlspecialchars($accion['accion']); ?>
                                            <?php if ($accion['tabla_afectada']): ?>
                                                <span style="color: var(--color-text-light);">en <?php echo htmlspecialchars($accion['tabla_afectada']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-meta">
                                            <?php echo htmlspecialchars($accion['usuario'] ?? 'Sistema'); ?> • 
                                            <?php echo date('d/m/Y H:i:s', strtotime($accion['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--color-text-light);">
                                No hay actividad reciente
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Usuarios -->
            <div id="tab-users" class="tab-content">
                <div class="users-table-container">
                    <div class="table-header">
                        <div class="search-box">
                            <input type="text" id="searchUsers" class="search-input" placeholder="🔍 Buscar usuario...">
                        </div>
                        <button class="btn btn-primary" onclick="openCreateUserModal()">
                            ➕ Crear Usuario
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table class="table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Funcionario</th>
                                    <th>Cargo</th>
                                    <th>Nivel</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['nombre_completo']); ?><br>
                                            <small style="color: var(--color-text-light);"><?php echo htmlspecialchars($user['cedula']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['nombre_cargo']); ?></td>
                                        <td>
                                            <span class="badge badge-info">Nivel <?php echo $user['nivel_acceso']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'badge-success';
                                            if ($user['estado'] == 'inactivo') $badge_class = 'badge-danger';
                                            elseif ($user['estado'] == 'bloqueado') $badge_class = 'badge-warning';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($user['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($user['ultimo_acceso']) {
                                                echo date('d/m/Y H:i', strtotime($user['ultimo_acceso']));
                                            } else {
                                                echo '<span style="color: var(--color-text-light);">Nunca</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Editar">
                                                    ✏️
                                                </button>
                                                <button class="btn-icon btn-toggle" onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['estado']; ?>')" title="Cambiar estado">
                                                    🔄
                                                </button>
                                                <button class="btn-icon btn-reset" onclick="resetPassword(<?php echo $user['id']; ?>)" title="Resetear contraseña">
                                                    🔑
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Herramientas -->
            <div id="tab-tools" class="tab-content">
                <div class="quick-actions">
                    <a href="#" onclick="switchTab('users'); return false;" class="quick-action-card">
                        <div class="quick-action-icon">👥</div>
                        <div class="quick-action-title">Gestión de Usuarios</div>
                        <div class="quick-action-desc">Crear y administrar cuentas de usuario</div>
                    </a>
                    <a href="organizacion.php" class="quick-action-card">
                        <div class="quick-action-icon">🏢</div>
                        <div class="quick-action-title">Gestión Organizacional</div>
                        <div class="quick-action-desc">Administrar departamentos y cargos</div>
                    </a>
                    <a href="respaldo.php" class="quick-action-card">
                        <div class="quick-action-icon">💾</div>
                        <div class="quick-action-title">Respaldo de Base de Datos</div>
                        <div class="quick-action-desc">Generar copia de seguridad completa</div>
                    </a>
                    <a href="restaurar.php" class="quick-action-card">
                        <div class="quick-action-icon">🔄</div>
                        <div class="quick-action-title">Restaurar Base de Datos</div>
                        <div class="quick-action-desc">Restaurar desde archivo SQL</div>
                    </a>
                    <a href="auditoria.php" class="quick-action-card">
                        <div class="quick-action-icon">📋</div>
                        <div class="quick-action-title">Log de Auditoría</div>
                        <div class="quick-action-desc">Consultar historial de acciones</div>
                    </a>
                    <div class="quick-action-card" style="opacity: 0.6; cursor: not-allowed;">
                        <div class="quick-action-icon">⚙️</div>
                        <div class="quick-action-title">Configuración Avanzada</div>
                        <div class="quick-action-desc">Próximamente</div>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Actividad -->
            <div id="tab-activity" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Últimas Acciones del Sistema</h2>
                        <a href="auditoria.php" class="btn btn-primary">Ver Todo</a>
                    </div>
                    <div style="padding: 0;">
                        <?php
                        $stmt = $db->query("
                            SELECT 
                                a.accion,
                                a.tabla_afectada,
                                a.ip_address,
                                a.created_at,
                                CONCAT(f.nombres, ' ', f.apellidos) AS usuario
                            FROM auditoria a
                            LEFT JOIN usuarios u ON a.usuario_id = u.id
                            LEFT JOIN funcionarios f ON u.funcionario_id = f.id
                            ORDER BY a.created_at DESC
                            LIMIT 15
                        ");
                        $actividad = $stmt->fetchAll();
                        ?>
                        <?php if (count($actividad) > 0): ?>
                            <?php foreach ($actividad as $accion): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icono = '📝';
                                        if (strpos($accion['accion'], 'LOGIN') !== false) $icono = '🔐';
                                        elseif (strpos($accion['accion'], 'CREAR') !== false) $icono = '➕';
                                        elseif (strpos($accion['accion'], 'ELIMINAR') !== false) $icono = '🗑️';
                                        elseif (strpos($accion['accion'], 'ACTUALIZAR') !== false) $icono = '✏️';
                                        echo $icono;
                                        ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-action">
                                            <?php echo htmlspecialchars($accion['accion']); ?>
                                            <?php if ($accion['tabla_afectada']): ?>
                                                <span style="color: var(--color-text-light);">en <?php echo htmlspecialchars($accion['tabla_afectada']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-meta">
                                            <?php echo htmlspecialchars($accion['usuario'] ?? 'Sistema'); ?> • 
                                            <?php echo date('d/m/Y H:i:s', strtotime($accion['created_at'])); ?> • 
                                            IP: <?php echo htmlspecialchars($accion['ip_address'] ?? '-'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--color-text-light);">
                                No hay actividad registrada
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Crear Usuario -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Crear Nuevo Usuario</h2>
            </div>
            <form id="createUserForm" onsubmit="return handleCreateUser(event)">
                <div class="form-group">
                    <label class="form-label">Funcionario</label>
                    <select name="funcionario_id" class="form-select" required>
                        <option value="">Seleccione un funcionario...</option>
                        <?php
                        $stmt = $db->query("
                            SELECT f.id, f.cedula, CONCAT(f.nombres, ' ', f.apellidos) AS nombre
                            FROM funcionarios f
                            LEFT JOIN usuarios u ON f.id = u.funcionario_id
                            WHERE u.id IS NULL AND f.estado = 'activo'
                            ORDER BY f.nombres, f.apellidos
                        ");
                        $funcionarios_sin_usuario = $stmt->fetchAll();
                        foreach ($funcionarios_sin_usuario as $func):
                        ?>
                            <option value="<?php echo $func['id']; ?>">
                                <?php echo htmlspecialchars($func['nombre'] . ' - ' . $func['cedula']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" name="username" class="form-input" required minlength="4">
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña Temporal</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeCreateUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.admin-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Search users
        document.getElementById('searchUsers')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Modal functions
        function openCreateUserModal() {
            document.getElementById('createUserModal').classList.add('active');
        }
        
        function closeCreateUserModal() {
            document.getElementById('createUserModal').classList.remove('active');
            document.getElementById('createUserForm').reset();
        }
        
        // Create user
        function handleCreateUser(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('ajax/crear_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Usuario creado exitosamente');
                    closeCreateUserModal();
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error al crear usuario');
                console.error(error);
            });
            
            return false;
        }
        
        // Toggle user status
        function toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'activo' ? 'inactivo' : 'activo';
            const confirmMsg = `¿Está seguro de ${newStatus === 'activo' ? 'activar' : 'desactivar'} este usuario?`;
            
            if (!confirm(confirmMsg)) return;
            
            fetch('ajax/toggle_usuario.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ usuario_id: userId, nuevo_estado: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Estado actualizado');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }
        
        // Reset password
        function resetPassword(userId) {
            const newPassword = prompt('Ingrese la nueva contraseña temporal:');
            if (!newPassword || newPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }
            
            fetch('ajax/reset_password.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ usuario_id: userId, nueva_password: newPassword })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Contraseña actualizada exitosamente');
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }
        
        // Edit user (placeholder)
        function editUser(userId) {
            alert('Funcionalidad de edición en desarrollo. ID: ' + userId);
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
