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
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <link rel="stylesheet" href="../../publico/css/responsive.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js">
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
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Estadísticas Principales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg></div>
                    <div class="stat-value"><?php echo $total_usuarios_activos; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <div class="stat-value"><?php echo $total_funcionarios; ?></div>
                    <div class="stat-label">Funcionarios</div>
                </div>
                <div class="stat-card tertiary">
                    <div class="stat-icon"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    <div class="stat-value"><?php echo $total_documentos; ?></div>
                    <div class="stat-label">Documentos</div>
                </div>
                <div class="stat-card quaternary">
                    <div class="stat-icon"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg></div>
                    <div class="stat-value"><?php echo $db_size; ?> MB</div>
                    <div class="stat-label">Tama&ntilde;o BD</div>
                </div>
            </div>
            
            <!-- Tabs de Navegación -->
            <div class="admin-tabs">
                <button class="admin-tab active" onclick="switchTab('overview')">Resumen</button>
                <button class="admin-tab" onclick="switchTab('users')">Usuarios</button>
                <button class="admin-tab" onclick="switchTab('tools')">Herramientas</button>
                <button class="admin-tab" onclick="switchTab('activity')">Actividad</button>
            </div>
            
            <!-- Tab: Resumen — solo muestra la actividad reciente (las tarjetas de acción están en Herramientas) -->
            <div id="tab-overview" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Actividad Reciente</h2>
                        <a href="auditoria.php" class="btn btn-primary" style="font-size:13px;">Ver todo el log</a>
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
                            <input type="text" id="searchUsers" class="search-input" placeholder="Buscar usuario...">
                        </div>
                        <button class="btn btn-primary" onclick="openCreateUserModal()">
                            Crear Usuario
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
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button class="btn-icon btn-toggle" onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['estado']; ?>')" title="Cambiar estado">
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                </button>
                                                <button class="btn-icon btn-reset" onclick="resetPassword(<?php echo $user['id']; ?>)" title="Resetear contrase&ntilde;a">
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
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
                        <div class="quick-action-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg></div>
                        <div class="quick-action-title">Gesti&oacute;n de Usuarios</div>
                        <div class="quick-action-desc">Crear y administrar cuentas de usuario</div>
                    </a>
                    <a href="organizacion.php" class="quick-action-card">
                        <div class="quick-action-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                        <div class="quick-action-title">Gesti&oacute;n Organizacional</div>
                        <div class="quick-action-desc">Administrar departamentos y cargos</div>
                    </a>
                    <!-- UNA SOLA tarjeta de Respaldos — sin duplicados -->
                    <a href="<?= APP_URL ?>/vistas/respaldo/index.php" class="quick-action-card">
                        <div class="quick-action-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg></div>
                        <div class="quick-action-title">Gesti&oacute;n de Respaldos</div>
                        <div class="quick-action-desc">Exportar e importar base de datos</div>
                    </a>
                    <a href="auditoria.php" class="quick-action-card">
                        <div class="quick-action-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                        <div class="quick-action-title">Log de Auditor&iacute;a</div>
                        <div class="quick-action-desc">Consultar historial de acciones</div>
                    </a>
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
                                        if (strpos($accion['accion'], 'LOGIN') !== false)
                                            echo '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>';
                                        elseif (strpos($accion['accion'], 'CREAR') !== false)
                                            echo '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';
                                        elseif (strpos($accion['accion'], 'ELIMINAR') !== false)
                                            echo '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
                                        elseif (strpos($accion['accion'], 'ACTUALIZAR') !== false)
                                            echo '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                                        else
                                            echo '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
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
                    <div style="background: #e6f7ff; border-left: 4px solid #00a8cc; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                        <p style="margin: 0; color: #0066cc; font-size: 14px;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <strong>Usuario autom&aacute;tico:</strong> El nombre de usuario se generar&aacute; autom&aacute;ticamente como <code>[letra(s)_nombre][apellido]</code>
                        </p>
                    </div>
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
    
    <!-- Modal: Editar Usuario -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Usuario</h2>
            </div>
            <form id="editUserForm" onsubmit="return handleEditUser(event)">
                <input type="hidden" name="id" id="edit_user_id">
                <div class="form-group">
                    <label class="form-label">Funcionario</label>
                    <input type="text" id="edit_user_funcionario" class="form-input" readonly style="background: #f7fafc;">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" name="username" id="edit_user_username" class="form-input" required minlength="4">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="edit_user_email" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Cargo</label>
                    <select name="cargo_id" id="edit_user_cargo" class="form-select" required>
                        <option value="">Seleccione un cargo...</option>
                        <?php
                        $stmt = $db->query("SELECT id, nombre_cargo, nivel_acceso FROM cargos ORDER BY nivel_acceso, nombre_cargo");
                        $cargos_list = $stmt->fetchAll();
                        foreach ($cargos_list as $cargo):
                        ?>
                            <option value="<?php echo $cargo['id']; ?>">
                                <?php echo htmlspecialchars($cargo['nombre_cargo']) . ' (Nivel ' . $cargo['nivel_acceso'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeEditUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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
            const funcionarioSelect = e.target.querySelector('[name="funcionario_id"]');
            const funcionarioNombre = funcionarioSelect.options[funcionarioSelect.selectedIndex].text;
            
            Swal.fire({
                title: '¿Crear Usuario?',
                html: `¿Está seguro de crear un usuario para <strong>${funcionarioNombre}</strong>?<br><small style="color: #666;">El nombre de usuario se generará automáticamente</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#00a8cc',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Creando usuario...');
                    
                    fetch('ajax/crear_usuario.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Usuario Creado!',
                                html: `
                                    <p>El usuario ha sido creado exitosamente</p>
                                    <div style="background: #f0f9ff; border: 1px solid #00a8cc; border-radius: 8px; padding: 16px; margin-top: 16px;">
                                        <p style="margin: 0 0 8px 0; color: #0066cc; font-weight: bold;"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Credenciales:</p>
                                        <p style="margin: 4px 0; font-family: monospace; font-size: 16px;">
                                            <strong>Usuario:</strong> <span style="color: #00a8cc;">${data.username}</span>
                                        </p>
                                        <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                                                    <svg width="12" height="12" fill="none" stroke="#f59e0b" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    Comunique estas credenciales al funcionario
                                                </p>
                                    </div>
                                `,
                                confirmButtonColor: '#10b981',
                                confirmButtonText: 'Entendido'
                            }).then(() => {
                                closeCreateUserModal();
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo crear el usuario',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'No se pudo crear el usuario. Intente nuevamente.',
                            confirmButtonColor: '#ef4444'
                        });
                        console.error(error);
                    });
                }
            });
            
            return false;
        }
        
        // Toggle user status
        function toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'activo' ? 'inactivo' : 'activo';
            const confirmMsg = `¿Está seguro de ${newStatus === 'activo' ? 'activar' : 'desactivar'} este usuario?`;
            
            confirmarAccion(confirmMsg, '¿Cambiar estado?', 'Sí, cambiar', 'Cancelar').then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Actualizando estado...');
                    
                    fetch('ajax/toggle_usuario.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ usuario_id: userId, nuevo_estado: newStatus })
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            mostrarExito('Estado actualizado correctamente').then(() => {
                                location.reload();
                            });
                        } else {
                            mostrarError(data.message);
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        mostrarError('Error al actualizar el estado');
                        console.error(error);
                    });
                }
            });
        }
        
        // Reset password
        function resetPassword(userId) {
            Swal.fire({
                title: 'Resetear Contraseña',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 16px; color: #718096;">
                            Ingrese la nueva contraseña temporal para este usuario:
                        </p>
                        <input type="password" id="swal-password" class="swal2-input" placeholder="Nueva contraseña" style="width: 90%; margin: 0;">
                        <small style="display: block; margin-top: 8px; color: #a0aec0;">
                            Mínimo 6 caracteres
                        </small>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Resetear Contraseña',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#00a8cc',
                cancelButtonColor: '#6c757d',
                preConfirm: () => {
                    const password = document.getElementById('swal-password').value;
                    if (!password || password.length < 6) {
                        Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                        return false;
                    }
                    return password;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Actualizando contraseña...');
                    
                    fetch('ajax/reset_password.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ usuario_id: userId, nueva_password: result.value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Contraseña Actualizada!',
                                text: 'La contraseña temporal ha sido establecida correctamente',
                                confirmButtonColor: '#10b981',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo actualizar la contraseña',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'No se pudo actualizar la contraseña. Intente nuevamente.',
                            confirmButtonColor: '#ef4444'
                        });
                        console.error(error);
                    });
                }
            });
        }
        
        // Edit user
        function editUser(userId) {
            fetch(`ajax/obtener_usuario.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_user_id').value = data.data.id;
                        document.getElementById('edit_user_funcionario').value = data.data.nombre_completo + ' - ' + data.data.cedula;
                        document.getElementById('edit_user_username').value = data.data.username;
                        document.getElementById('edit_user_email').value = data.data.email || '';
                        document.getElementById('edit_user_cargo').value = data.data.cargo_id;
                        
                        document.getElementById('editUserModal').classList.add('active');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo cargar los datos del usuario',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo cargar los datos del usuario',
                        confirmButtonColor: '#ef4444'
                    });
                    console.error(error);
                });
        }
        
        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.remove('active');
            document.getElementById('editUserForm').reset();
        }
        
        function handleEditUser(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const username = formData.get('username');
            
            Swal.fire({
                title: '¿Guardar Cambios?',
                html: `¿Está seguro de actualizar el usuario <strong>"${username}"</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#00a8cc',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Actualizando usuario...');
                    
                    fetch('ajax/editar_usuario.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Usuario Actualizado!',
                                text: 'Los datos del usuario han sido actualizados correctamente',
                                confirmButtonColor: '#10b981',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                closeEditUserModal();
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo actualizar el usuario',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'No se pudo actualizar el usuario. Intente nuevamente.',
                            confirmButtonColor: '#ef4444'
                        });
                        console.error(error);
                    });
                }
            });
            
            return false;
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
    
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="../../publico/js/sweetalert-utils.js"></script>
</body>
</html>
