<?php
/**
 * Vista: Expediente Digital - Sistema de 8 Pestañas
 * Versión Ultra Moderna - Sin Scroll
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

// Verificar sesión
verificarSesion();

// Obtener ID del funcionario
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del funcionario
$modeloFuncionario = new Funcionario();
$funcionario = $modeloFuncionario->obtenerPorId($id);

if (!$funcionario) {
    $_SESSION['error'] = 'Funcionario no encontrado';
    header('Location: index.php');
    exit;
}

// Verificar permisos
if (!verificarDepartamento($id) && $_SESSION['nivel_acceso'] > 2) {
    $_SESSION['error'] = 'No tiene permisos para ver este expediente';
    header('Location: index.php');
    exit;
}

$puede_editar = verificarDepartamento($id);

// Verificar si el funcionario tiene usuario
$db = getDB();
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.username,
        c.nivel_acceso,
        u.estado,
        u.password_hash
    FROM usuarios u
    INNER JOIN funcionarios f ON u.funcionario_id = f.id
    INNER JOIN cargos c ON f.cargo_id = c.id
    WHERE f.cedula = ?
");
$stmt->execute([$funcionario['cedula']]);
$usuario_existente = $stmt->fetch();

// Determinar estado del usuario
$tiene_usuario = false;
$estado_usuario = 'sin_usuario';
$username_display = '';

if ($usuario_existente) {
    $tiene_usuario = true;
    $username_display = $usuario_existente['username'];
    
    if ($usuario_existente['password_hash'] === 'PENDING') {
        $estado_usuario = 'pendiente';
    } elseif ($usuario_existente['estado'] === 'inactivo') {
        $estado_usuario = 'inactivo';
    } else {
        $estado_usuario = 'activo';
    }
}

// Calcular edad
$edad = '';
if ($funcionario['fecha_nacimiento']) {
    $fecha_nac = new DateTime($funcionario['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y . ' años';
}

// Calcular antigüedad
$antiguedad = '';
if ($funcionario['fecha_ingreso']) {
    $fecha_ing = new DateTime($funcionario['fecha_ingreso']);
    $hoy = new DateTime();
    $diff = $hoy->diff($fecha_ing);
    $antiguedad = $diff->y . ' años';
    if ($diff->m > 0) $antiguedad .= ', ' . $diff->m . ' meses';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente: <?php echo htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        :root {
            --tab-info: linear-gradient(135deg, #00a8cc 0%, #005f73 100%);
            --tab-cargas: linear-gradient(135deg, #0a9396 0%, #005f73 100%);
            --tab-activos: linear-gradient(135deg, #4cc9f0 0%, #00a8cc 100%);
            --tab-vacaciones: linear-gradient(135deg, #06d6a0 0%, #0a9396 100%);
            --tab-riesgo: linear-gradient(135deg, #ffd166 0%, #ef476f 100%);
            --tab-nombramientos: linear-gradient(135deg, #00a8cc 0%, #0088aa 100%);
            --tab-historial-vac: linear-gradient(135deg, #06d6a0 0%, #4cc9f0 100%);
            --tab-amonestaciones: linear-gradient(135deg, #ef476f 0%, #ffd166 100%);
        }
        
        /* Header del Expediente */
        .expediente-header {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .expediente-avatar {
            width: 100px;
            height: 100px;
            border-radius: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }
        
        .expediente-info {
            flex: 1;
        }
        
        .expediente-nombre {
            font-size: 32px;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .expediente-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
            color: #718096;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        
        .expediente-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .expediente-meta-item strong {
            color: #2d3748;
        }
        
        .badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ========================================
           EXPEDIENTE CONTAINER - SIDEBAR LAYOUT
           ======================================== */
        
        .expediente-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 24px;
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .expediente-sidebar {
            border-right: 1px solid #e2e8f0;
            padding-right: 24px;
            position: sticky;
            top: 24px;
            align-self: start;
            max-height: calc(100vh - 48px);
            overflow-y: auto;
        }
        
        .foto-perfil {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .expediente-sidebar h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 4px;
            text-align: center;
        }
        
        .expediente-sidebar .cargo {
            font-size: 13px;
            color: #718096;
            margin: 0 0 12px;
            text-align: center;
            display: block;
        }
        
        .expediente-sidebar .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
            letter-spacing: 0.3px;
            margin: 0 auto 20px;
            display: block;
            width: fit-content;
        }
        
        .nav-menu {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 4px;
            border-radius: 8px;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .nav-item svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            stroke-width: 2;
            pointer-events: none;
        }
        
        .nav-item span {
            pointer-events: none;
        }
        
        .nav-item:hover {
            background: #f7fafc;
            color: #2d3748;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #00a8cc 0%, #0088aa 100%);
            color: white;
            border-left-color: #005f73;
            font-weight: 600;
        }
        
        .nav-item.active svg {
            color: white;
        }
        
        .expediente-content {
            min-height: 500px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .expediente-container {
                grid-template-columns: 1fr;
            }
            
            .expediente-sidebar {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                padding-right: 0;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
                gap: 8px;
                border-top: none;
                padding-top: 0;
                margin-top: 16px;
            }
            
            .nav-item {
                flex-shrink: 0;
                min-width: 140px;
                font-size: 13px;
            }
        }
        
        .badge-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        /* Sistema de Pestañas Grid Moderno */
        .tabs-grid-container {
            margin-bottom: 32px;
        }
        
        .tabs-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .tab-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .tab-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .tab-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
        }
        
        .tab-card:hover::before {
            opacity: 1;
        }
        
        .tab-card.active {
            border-color: transparent;
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .tab-card.active::before {
            opacity: 1;
            height: 100%;
        }
        
        .tab-card.active .tab-card-content {
            position: relative;
            z-index: 1;
        }
        
        .tab-card.active .tab-icon {
            color: white;
        }
        
        .tab-card.active .tab-title {
            color: white;
        }
        
        .tab-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
            transition: all 0.3s ease;
        }
        
        .tab-title {
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
            transition: all 0.3s ease;
        }
        
        /* Contenido de Pestañas */
        .tab-content {
            display: none;
            animation: fadeInUp 0.4s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 3px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title-icon {
            font-size: 28px;
        }
        
        /* Grid de Información */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            padding: 20px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            border-left: 4px solid #00a8cc;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 16px;
            color: #1a202c;
            font-weight: 600;
        }
        
        /* Tablas Modernas */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .data-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .data-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table th:first-child {
            border-top-left-radius: 12px;
        }
        
        .data-table th:last-child {
            border-top-right-radius: 12px;
        }
        
        .data-table td {
            padding: 16px;
            font-size: 14px;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .data-table tbody tr:hover {
            background: #f7fafc;
            transform: scale(1.01);
        }
        
        /* Calculadora de Vacaciones */
        .calculator-card {
            background: linear-gradient(135deg, #06d6a0 0%, #4cc9f0 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 12px 32px rgba(67, 233, 123, 0.3);
        }
        
        .calculator-result {
            font-size: 72px;
            font-weight: 800;
            margin: 20px 0;
            text-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .calculator-label {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 500;
        }
        
        /* Barra de Riesgo */
        .risk-bar-container {
            margin: 32px 0;
        }
        
        .risk-bar {
            width: 100%;
            height: 60px;
            background: #e2e8f0;
            border-radius: 30px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .risk-fill {
            height: 100%;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .risk-fill.sin-riesgo {
            background: linear-gradient(90deg, #10b981, #059669);
        }
        
        .risk-fill.riesgo-bajo {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }
        
        .risk-fill.riesgo-alto {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }
        
        /* Estado Vacío */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #a0aec0;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.4;
        }
        
        .empty-state-text {
            font-size: 16px;
            font-weight: 500;
        }
        
        /* Modal Moderno */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1a202c;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #00a8cc;
            box-shadow: 0 0 0 3px rgba(0, 168, 204, 0.1);
        }
        
        /* Alertas */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .tabs-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .expediente-header {
                flex-direction: column;
                text-align: center;
            }
            
            .tabs-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .expediente-nombre {
                font-size: 24px;
            }
            
            .calculator-result {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <a href="index.php" class="btn" style="margin-right: 16px;">← Volver</a>
                <h1 class="page-title">Expediente Digital</h1>
            </div>
            <div class="header-right">
                <a href="../reportes/constancia_trabajo.php?id=<?php echo $id; ?>" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; margin-right: 12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-right: 6px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                    Constancia de Trabajo
                </a>
                <?php if ($puede_editar): ?>
                    <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-right: 6px;">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Editar
                    </a>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- EXPEDIENTE CONTAINER CON SIDEBAR -->
            <div class="expediente-container">
                <!-- SIDEBAR -->
                <aside class="expediente-sidebar">
                    <div class="text-center">
                        <div class="foto-perfil">
                            <?php echo strtoupper(substr($funcionario['nombres'], 0, 1) . substr($funcionario['apellidos'], 0, 1)); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?></h3>
                        <span class="cargo"><?php echo htmlspecialchars($funcionario['nombre_cargo']); ?></span>
                        <span class="badge badge-success">
                            <?php echo ucfirst($funcionario['estado']); ?>
                        </span>
                        
                        <!-- Estado de Usuario -->
                        <div id="user-status-container" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                            <?php if ($tiene_usuario): ?>
                                <?php if ($estado_usuario === 'activo'): ?>
                                    <span class="badge badge-success" style="display: block; margin-bottom: 8px;">
                                        👤 <?php echo htmlspecialchars($username_display); ?>
                                    </span>
                                    <small style="color: #718096; font-size: 12px;">Cuenta activa</small>
                                <?php elseif ($estado_usuario === 'pendiente'): ?>
                                    <span class="badge badge-warning" style="display: block; margin-bottom: 8px;">
                                        ⏳ Registro Pendiente
                                    </span>
                                    <small style="color: #718096; font-size: 12px;">Usuario: <?php echo htmlspecialchars($username_display); ?></small>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="display: block; margin-bottom: 8px;">
                                        🚫 Usuario Inactivo
                                    </span>
                                    <small style="color: #718096; font-size: 12px;">Usuario: <?php echo htmlspecialchars($username_display); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (verificarNivel(2)): ?>
                                    <span class="badge badge-danger" style="display: block; margin-bottom: 8px;">
                                        ❌ Sin acceso al sistema
                                    </span>
                                    <button onclick="abrirModalCrearUsuario()" class="btn btn-primary" style="width: 100%; margin-top: 8px; font-size: 13px; padding: 8px 12px;">
                                        + Crear Usuario
                                    </button>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="display: block;">
                                        Sin acceso al sistema
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Menú de Navegación -->
                    <nav class="nav-menu">
                        <div class="nav-item active" onclick="switchTab('info', this)" data-tab="info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Información Personal</span>
                        </div>
                        
                        <div class="nav-item" onclick="switchTab('cargas', this)" data-tab="cargas">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span>Cargas Familiares</span>
                        </div>
                        
                        
                        <div class="nav-item" onclick="switchTab('vacaciones-calc', this)" data-tab="vacaciones-calc">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="5"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                            </svg>
                            <span>Calculadora Vacaciones</span>
                        </div>
                        
                        <div class="nav-item" onclick="switchTab('riesgo', this)" data-tab="riesgo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <span>Barra de Riesgo</span>
                        </div>
                        
                        <div class="nav-item" onclick="switchTab('nombramientos', this)" data-tab="nombramientos">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <line x1="10" y1="9" x2="8" y2="9"></line>
                            </svg>
                            <span>Nombramientos</span>
                        </div>
                        
                        <div class="nav-item" onclick="switchTab('vacaciones', this)" data-tab="vacaciones">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Historial Vacaciones</span>
                        </div>
                        
                        <div class="nav-item" onclick="switchTab('amonestaciones', this)" data-tab="amonestaciones">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                            </svg>
                            <span>Amonestaciones</span>
                        </div>
                        
                        <div class="nav-item" onclick="switchTab('salidas', this)" data-tab="salidas">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span>Retiros/Despidos</span>
                        </div>
                    </nav>
                </aside>
                
                <script>
                // Define switchTab function early so onclick handlers work
                const funcionarioId = <?php echo $id; ?>;
                let currentTab = 'info';
                
                function switchTab(tabName, element) {
                    console.log('switchTab called:', tabName, element);
                    
                    // Ocultar todas las pestañas
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    
                    // Desactivar todas las tarjetas y nav-items
                    document.querySelectorAll('.tab-card').forEach(card => {
                        card.classList.remove('active');
                    });
                    document.querySelectorAll('.nav-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // Activar pestaña seleccionada
                    const tabElement = document.getElementById('tab-' + tabName);
                    if (tabElement) {
                        tabElement.classList.add('active');
                        console.log('Tab activated:', 'tab-' + tabName);
                    } else {
                        console.error('Tab not found:', 'tab-' + tabName);
                    }
                    
                    element.classList.add('active');
                    
                    currentTab = tabName;
                    
                    // Cargar datos según la pestaña
                    switch(tabName) {
                        case 'cargas':
                            if (typeof cargarCargasFamiliares === 'function') cargarCargasFamiliares();
                            break;
                        case 'activos':
                            if (typeof cargarActivos === 'function') cargarActivos();
                            break;
                        case 'vacaciones-calc':
                            if (typeof calcularVacaciones === 'function') calcularVacaciones();
                            break;
                        case 'riesgo':
                            if (typeof cargarBarraRiesgo === 'function') cargarBarraRiesgo();
                            break;
                        case 'nombramientos':
                            if (typeof cargarNombramientos === 'function') cargarNombramientos();
                            break;
                        case 'vacaciones':
                            if (typeof cargarVacaciones === 'function') cargarVacaciones();
                            break;
                        case 'amonestaciones':
                            if (typeof cargarAmonestaciones === 'function') cargarAmonestaciones();
                            break;
                        case 'salidas':
                            if (typeof cargarSalidas === 'function') cargarSalidas();
                            break;
                    }
                }
                </script>
                
                <!-- CONTENIDO -->
                <main class="expediente-content">
            
            <!-- Contenido de las Pestañas -->
            
            <!-- TAB 1: Información Personal -->
            <div id="tab-info" class="tab-content active">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        Datos Personales
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Nombres Completos</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['nombres']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Apellidos</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['apellidos']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Cédula de Identidad</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['cedula']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Fecha de Nacimiento</div>
                            <div class="info-value">
                                <?php echo $funcionario['fecha_nacimiento'] ? date('d/m/Y', strtotime($funcionario['fecha_nacimiento'])) . ' (' . $edad . ')' : 'No registrada'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Género</div>
                            <div class="info-value"><?php echo $funcionario['genero'] == 'M' ? 'Masculino' : ($funcionario['genero'] == 'F' ? 'Femenino' : 'Otro'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Teléfono</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['telefono'] ?? 'No registrado'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['email'] ?? 'No registrado'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Dirección</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['direccion'] ?? 'No registrada'); ?></div>
                        </div>
                    </div>
                    
                    <h3 class="section-title" style="margin-top: 40px;">
                        <span class="section-title-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        </span>
                        Información Laboral
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Cargo</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['nombre_cargo']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Departamento</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['departamento']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Fecha de Ingreso</div>
                            <div class="info-value">
                                <?php echo date('d/m/Y', strtotime($funcionario['fecha_ingreso'])) . ' (' . $antiguedad . ')'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <span class="badge badge-success"><?php echo ucfirst($funcionario['estado']); ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nivel Educativo</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['nivel_educativo'] ?? 'No registrado'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Título Obtenido</div>
                            <div class="info-value"><?php echo htmlspecialchars($funcionario['titulo_obtenido'] ?? 'No registrado'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Cantidad de Hijos</div>
                            <div class="info-value"><?php echo $funcionario['cantidad_hijos'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TAB 2: Cargas Familiares -->
            <div id="tab-cargas" class="tab-content">
                <div class="content-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 class="section-title" style="margin: 0;">
                            <span class="section-title-icon">👨‍👩‍👧‍👦</span>
                            Cargas Familiares
                        </h3>
                        <?php if ($puede_editar): ?>
                            <button class="btn btn-primary" onclick="abrirModalCarga()">+ Agregar Carga</button>
                        <?php endif; ?>
                    </div>
                    <div id="cargas-container">
                        <div class="empty-state">
                            <div class="empty-state-icon">⏳</div>
                            <p class="empty-state-text">Cargando información...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <!-- TAB 3: Calculadora de Vacaciones -->
            <div id="tab-vacaciones-calc" class="tab-content">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">🏖️</span>
                        Calculadora de Vacaciones
                    </h3>
                    <div class="calculator-card">
                        <div class="calculator-label">Días de Vacaciones Pendientes</div>
                        <div class="calculator-result" id="vacaciones-pendientes">--</div>
                        <div class="calculator-label">
                            <span id="vacaciones-detalle">Calculando...</span>
                        </div>
                    </div>
                    <div id="vacaciones-alerta"></div>
                </div>
            </div>
            
            <!-- TAB 5: Barra de Riesgo -->
            <div id="tab-riesgo" class="tab-content">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">⚠️</span>
                        Nivel de Riesgo por Amonestaciones
                    </h3>
                    <div class="risk-bar-container">
                        <div class="risk-bar">
                            <div id="risk-fill" class="risk-fill sin-riesgo" style="width: 0%">
                                <span id="risk-text">Cargando...</span>
                            </div>
                        </div>
                    </div>
                    <div id="risk-mensaje"></div>
                </div>
            </div>
            
            <!-- TAB 6: Nombramientos -->
            <div id="tab-nombramientos" class="tab-content">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">📄</span>
                        Historial de Nombramientos
                    </h3>
                    <div id="nombramientos-container">
                        <div class="empty-state">
                            <div class="empty-state-icon">📄</div>
                            <p class="empty-state-text">No hay nombramientos registrados</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TAB 7: Vacaciones -->
            <div id="tab-vacaciones" class="tab-content">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">🌴</span>
                        Historial de Vacaciones
                    </h3>
                    <div id="vacaciones-historial-container">
                        <div class="empty-state">
                            <div class="empty-state-icon">🌴</div>
                            <p class="empty-state-text">No hay vacaciones registradas</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TAB 8: Amonestaciones -->
            <div id="tab-amonestaciones" class="tab-content">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">⚡</span>
                        Historial de Amonestaciones
                    </h3>
                    <div id="amonestaciones-container">
                        <div class="empty-state">
                            <div class="empty-state-icon">⚡</div>
                            <p class="empty-state-text">No hay amonestaciones registradas</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TAB 9: Retiros/Despidos -->
            <div id="tab-salidas" class="tab-content">
                <div class="content-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 class="section-title" style="margin: 0;">
                            <span class="section-title-icon">🚪</span>
                            Retiros y Despidos
                        </h3>
                        <?php if ($funcionario['estado'] === 'activo'): ?>
                        <button type="button" onclick="procesarSalidaDesdePerfil()" class="btn" style="padding: 10px 20px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                            <span>⚠️</span>
                            Procesar Salida
                        </button>
                        <?php endif; ?>
                    </div>
                    <div id="salidas-container">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <p class="empty-state-text">No hay registros de retiros o despidos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para Cargas Familiares -->
    <div id="modalCarga" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Agregar Carga Familiar</h3>
            <form id="formCarga" onsubmit="guardarCarga(event)">
                <input type="hidden" id="carga_id" name="id">
                <input type="hidden" name="funcionario_id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label>Nombres</label>
                    <input type="text" name="nombres" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Parentesco</label>
                    <select name="parentesco" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="Hijo/a">Hijo/a</option>
                        <option value="Cónyuge">Cónyuge</option>
                        <option value="Padre/Madre">Padre/Madre</option>
                        <option value="Hermano/a">Hermano/a</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Cédula</label>
                    <input type="text" name="cedula" class="form-control">
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn" onclick="cerrarModalCarga()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // TAB 2: Cargas Familiares
        function cargarCargasFamiliares() {
            fetch(`ajax/get_cargas_familiares.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('cargas-container');
                    
                    if (data.success && data.cargas.length > 0) {
                        let html = '<table class="data-table"><thead><tr>';
                        html += '<th>Nombres</th><th>Apellidos</th><th>Parentesco</th><th>Edad</th><th>Cédula</th>';
                        html += '</tr></thead><tbody>';
                        
                        data.cargas.forEach(carga => {
                            html += `<tr>
                                <td>${carga.nombres}</td>
                                <td>${carga.apellidos}</td>
                                <td>${carga.parentesco}</td>
                                <td>${carga.edad} años</td>
                                <td>${carga.cedula || 'N/A'}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">👨‍👩‍👧‍👦</div>
                                <p class="empty-state-text">No hay cargas familiares registradas</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('cargas-container').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <p class="empty-state-text">Error al cargar las cargas familiares</p>
                        </div>
                    `;
                });
        }
        
        // Modal Cargas
        function abrirModalCarga() {
            document.getElementById('modalCarga').classList.add('active');
            document.getElementById('formCarga').reset();
        }
        
        function cerrarModalCarga() {
            document.getElementById('modalCarga').classList.remove('active');
        }
        
        function guardarCarga(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('ajax/save_carga_familiar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalCarga();
                    cargarCargasFamiliares();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Carga familiar guardada exitosamente',
                        confirmButtonColor: '#00a8cc',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo guardar la carga familiar',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo guardar la carga familiar. Intente nuevamente.',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
        
        // TAB 3: Activos Asignados
        function cargarActivos() {
            fetch(`ajax/get_activos.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('activos-container');
                    
                    if (data.success && data.activos.length > 0) {
                        let html = `<div style="margin-bottom: 20px;"><strong>Total de activos asignados:</strong> ${data.total_activos}</div>`;
                        html += '<table class="data-table"><thead><tr>';
                        html += '<th>Tipo</th><th>Marca</th><th>Modelo</th><th>Serial</th><th>Estado</th>';
                        html += '</tr></thead><tbody>';
                        
                        data.activos.forEach(activo => {
                            html += `<tr>
                                <td>${activo.tipo}</td>
                                <td>${activo.marca}</td>
                                <td>${activo.modelo}</td>
                                <td>${activo.serial}</td>
                                <td><span class="badge badge-success">${activo.estado}</span></td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">💻</div>
                                <p class="empty-state-text">No hay activos asignados</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('activos-container').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <p class="empty-state-text">Error al cargar los activos</p>
                        </div>
                    `;
                });
        }
        
        // TAB 4: Calculadora de Vacaciones
        function calcularVacaciones() {
            fetch(`ajax/calcular_vacaciones.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('vacaciones-pendientes').textContent = data.dias_pendientes;
                        document.getElementById('vacaciones-detalle').textContent = 
                            `${data.dias_disponibles} disponibles - ${data.dias_usados} usados`;
                        
                        const alertaDiv = document.getElementById('vacaciones-alerta');
                        let alertClass = 'alert-success';
                        let alertIcon = '✅';
                        
                        if (data.alerta === 'alta') {
                            alertClass = 'alert-error';
                            alertIcon = '⚠️';
                        } else if (data.alerta === 'media') {
                            alertClass = 'alert-warning';
                            alertIcon = '⚡';
                        }
                        
                        alertaDiv.innerHTML = `
                            <div class="alert ${alertClass}">
                                <span style="font-size: 20px;">${alertIcon}</span>
                                <span>${data.mensaje}</span>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // TAB 5: Barra de Riesgo
        function cargarBarraRiesgo() {
            fetch(`ajax/contar_amonestaciones.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const fill = document.getElementById('risk-fill');
                        const text = document.getElementById('risk-text');
                        const mensaje = document.getElementById('risk-mensaje');
                        
                        // Calcular porcentaje (máximo 5 amonestaciones = 100%)
                        const porcentaje = Math.min((data.total / 5) * 100, 100);
                        
                        fill.style.width = porcentaje + '%';
                        fill.className = 'risk-fill ' + data.nivel_riesgo;
                        text.textContent = `${data.total} Amonestación${data.total !== 1 ? 'es' : ''}`;
                        
                        if (data.mensaje) {
                            mensaje.innerHTML = `
                                <div class="alert alert-error" style="margin-top: 24px;">
                                    <span style="font-size: 20px;">⚠️</span>
                                    <span>${data.mensaje}</span>
                                </div>
                            `;
                        } else {
                            mensaje.innerHTML = '';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // TAB 6, 7, 8, 9: Historial
        function cargarNombramientos() {
            const funcionarioId = <?php echo $id; ?>;
            const container = document.getElementById('nombramientos-container');
            
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;">Cargando...</div>';
            
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=NOMBRAMIENTO`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.total > 0) {
                        let html = '<div class="table-responsive"><table class="data-table" style="width: 100%;"><thead><tr>';
                        html += '<th>Fecha</th><th>Cargo</th><th>Departamento</th><th>Documento</th></tr></thead><tbody>';
                        
                        data.data.forEach(item => {
                            const detalles = item.detalles || {};
                            html += '<tr>';
                            html += `<td><strong>${item.fecha_evento_formateada}</strong></td>`;
                            html += `<td>${detalles.cargo || 'N/A'}</td>`;
                            html += `<td><span style="padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 12px;">${detalles.departamento || 'N/A'}</span></td>`;
                            html += '<td>';
                            if (item.tiene_archivo) {
                                html += `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥 Ver PDF</a>`;
                            } else {
                                html += '-';
                            }
                            html += '</td></tr>';
                        });
                        
                        html += '</tbody></table></div>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">📄</div>
                                <p class="empty-state-text">No hay nombramientos registrados</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">⚠️</div>
                            <p class="empty-state-text">Error al cargar nombramientos</p>
                        </div>
                    `;
                });
        }
        
        function cargarVacaciones() {
            const funcionarioId = <?php echo $id; ?>;
            const container = document.getElementById('vacaciones-historial-container');
            
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;">Cargando...</div>';
            
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=VACACION`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.total > 0) {
                        let html = '<div class="table-responsive"><table class="data-table" style="width: 100%;"><thead><tr>';
                        html += '<th>Fecha Inicio</th><th>Fecha Fin</th><th>Días</th><th>Observaciones</th><th>Documento</th></tr></thead><tbody>';
                        
                        data.data.forEach(item => {
                            const detalles = item.detalles || {};
                            html += '<tr>';
                            html += `<td><strong>${item.fecha_evento_formateada}</strong></td>`;
                            html += `<td><strong>${item.fecha_fin_formateada || '-'}</strong></td>`;
                            html += `<td><span style="padding: 4px 12px; background: #d1fae5; color: #065f46; border-radius: 12px; font-size: 12px;">${detalles.dias_habiles || 0} días</span></td>`;
                            html += `<td>${detalles.observaciones || '-'}</td>`;
                            html += '<td>';
                            if (item.tiene_archivo) {
                                html += `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥 Ver PDF</a>`;
                            } else {
                                html += '-';
                            }
                            html += '</td></tr>';
                        });
                        
                        html += '</tbody></table></div>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">🌴</div>
                                <p class="empty-state-text">No hay vacaciones registradas</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">⚠️</div>
                            <p class="empty-state-text">Error al cargar vacaciones</p>
                        </div>
                    `;
                });
        }
        
        function cargarAmonestaciones() {
            const funcionarioId = <?php echo $id; ?>;
            const container = document.getElementById('amonestaciones-container');
            
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;">Cargando...</div>';
            
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=AMONESTACION`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.total > 0) {
                        let html = '<div class="table-responsive"><table class="data-table" style="width: 100%;"><thead><tr>';
                        html += '<th>Fecha</th><th>Tipo Falta</th><th>Motivo</th><th>Sanción</th><th>Documento</th></tr></thead><tbody>';
                        
                        data.data.forEach(item => {
                            const detalles = item.detalles || {};
                            const tipoFalta = detalles.tipo_falta || 'leve';
                            let badgeClass = 'background: #fef3c7; color: #92400e;';
                            if (tipoFalta === 'grave') badgeClass = 'background: #fed7aa; color: #7c2d12;';
                            if (tipoFalta === 'muy_grave') badgeClass = 'background: #fecaca; color: #991b1b;';
                            
                            html += '<tr>';
                            html += `<td><strong>${item.fecha_evento_formateada}</strong></td>`;
                            html += `<td><span style="padding: 4px 12px; ${badgeClass} border-radius: 12px; font-size: 12px; text-transform: capitalize;">${tipoFalta.replace('_', ' ')}</span></td>`;
                            html += `<td style="max-width: 300px;">${detalles.motivo || '-'}</td>`;
                            html += `<td>${detalles.sancion || '-'}</td>`;
                            html += '<td>';
                            if (item.tiene_archivo) {
                                html += `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥 Ver PDF</a>`;
                            } else {
                                html += '-';
                            }
                            html += '</td></tr>';
                        });
                        
                        html += '</tbody></table></div>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">⚡</div>
                                <p class="empty-state-text">No hay amonestaciones registradas</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">⚠️</div>
                            <p class="empty-state-text">Error al cargar amonestaciones</p>
                        </div>
                    `;
                });
        }
        
        function cargarSalidas() {
            const funcionarioId = <?php echo $id; ?>;
            const container = document.getElementById('salidas-container');
            
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;">Cargando...</div>';
            
            // Buscar tanto despidos como renuncias
            Promise.all([
                fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=DESPIDO`).then(r => r.json()),
                fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=RENUNCIA`).then(r => r.json())
            ])
            .then(([despidos, renuncias]) => {
                const allSalidas = [];
                if (despidos.success) allSalidas.push(...despidos.data);
                if (renuncias.success) allSalidas.push(...renuncias.data);
                
                if (allSalidas.length > 0) {
                    // Ordenar por fecha
                    allSalidas.sort((a, b) => new Date(b.fecha_evento) - new Date(a.fecha_evento));
                    
                    let html = '<div class="table-responsive"><table class="data-table" style="width: 100%;"><thead><tr>';
                    html += '<th>Tipo</th><th>Fecha</th><th>Motivo</th><th>Cargo al Retiro</th><th>Documento</th></tr></thead><tbody>';
                    
                    allSalidas.forEach(item => {
                        const detalles = item.detalles || {};
                        const esDespido = item.tipo_evento === 'DESPIDO';
                        
                        html += '<tr>';
                        html += `<td><span style="padding: 4px 12px; background: ${esDespido ? '#fecaca; color: #991b1b' : '#dbeafe; color: #1e40af'}; border-radius: 12px; font-size: 12px;">${item.tipo_evento}</span></td>`;
                        html += `<td><strong>${item.fecha_evento_formateada}</strong></td>`;
                        html += `<td style="max-width: 350px;">${detalles.motivo || '-'}</td>`;
                        html += `<td>${detalles.cargo_al_retiro || '-'}</td>`;
                        html += '<td>';
                        if (item.tiene_archivo) {
                            html += `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥 Ver PDF</a>`;
                        } else {
                            html += '-';
                        }
                        html += '</td></tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <p class="empty-state-text">No hay registros de retiros o despidos</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">⚠️</div>
                        <p class="empty-state-text">Error al cargar registros de salidas</p>
                    </div>
                `;
            });
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalCarga')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCarga();
            }
        });
        
        // ==========================================
        // FUNCIONALIDAD DE CREACIÓN DE USUARIO
        // ==========================================
        
        const funcionarioId = <?php echo $id; ?>;
        const funcionarioNombre = '<?php echo addslashes($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?>';
        
        function abrirModalCrearUsuario() {
            Swal.fire({
                title: 'Crear Cuenta de Usuario',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 20px; color: #718096;">
                            <strong>Funcionario:</strong> ${funcionarioNombre}
                        </p>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #4a5568;">
                                Nivel de Acceso *
                            </label>
                            <select id="nivel_acceso" class="swal2-input" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                                <option value="4">Nivel 4 - Usuario Regular</option>
                                <option value="3">Nivel 3 - Jefe de Departamento</option>
                                <option value="2">Nivel 2 - Secretaría/RRHH</option>
                                <?php if (verificarNivel(1)): ?>
                                <option value="1">Nivel 1 - Administrador</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div style="background: #f7fafc; padding: 12px; border-radius: 8px; margin-top: 16px;">
                            <small style="color: #718096;">
                                ℹ️ El sistema generará automáticamente un nombre de usuario y contraseña temporal.
                            </small>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Crear Usuario',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#00a8cc',
                cancelButtonColor: '#6c757d',
                width: '500px',
                preConfirm: () => {
                    const nivel = document.getElementById('nivel_acceso').value;
                    if (!nivel) {
                        Swal.showValidationMessage('Debe seleccionar un nivel de acceso');
                        return false;
                    }
                    return { nivel_acceso: nivel };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    crearUsuario(result.value.nivel_acceso);
                }
            });
        }
        
        function crearUsuario(nivelAcceso) {
            mostrarCargando('Creando usuario...');
            
            fetch('ajax/crear_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    funcionario_id: funcionarioId,
                    nivel_acceso: nivelAcceso
                })
            })
            .then(response => response.json())
            .then(data => {
                cerrarCargando();
                
                if (data.success) {
                    // Mostrar credenciales generadas
                    Swal.fire({
                        title: '✅ Usuario Creado Exitosamente',
                        html: `
                            <div style="text-align: left; background: #f7fafc; padding: 20px; border-radius: 12px; margin: 20px 0;">
                                <p style="margin-bottom: 16px; color: #2d3748;">
                                    <strong>Funcionario:</strong> ${data.usuario.funcionario}
                                </p>
                                <div style="background: white; padding: 16px; border-radius: 8px; border: 2px solid #00a8cc; margin-bottom: 12px;">
                                    <p style="margin: 0 0 8px 0; font-size: 13px; color: #718096; font-weight: 600;">NOMBRE DE USUARIO</p>
                                    <p style="margin: 0; font-size: 18px; font-weight: 700; color: #00a8cc; font-family: monospace;">
                                        ${data.usuario.username}
                                    </p>
                                </div>
                                <div style="background: white; padding: 16px; border-radius: 8px; border: 2px solid #ef476f; margin-bottom: 16px;">
                                    <p style="margin: 0 0 8px 0; font-size: 13px; color: #718096; font-weight: 600;">CONTRASEÑA TEMPORAL</p>
                                    <p style="margin: 0; font-size: 18px; font-weight: 700; color: #ef476f; font-family: monospace;">
                                        ${data.usuario.password_temporal}
                                    </p>
                                </div>
                                <div style="background: #fff3cd; padding: 12px; border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <p style="margin: 0; font-size: 13px; color: #856404;">
                                        ⚠️ <strong>Importante:</strong> El empleado debe completar su registro en la página de inicio usando su cédula.
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#10b981',
                        width: '600px'
                    }).then(() => {
                        // Recargar la página para actualizar el estado
                        location.reload();
                    });
                } else {
                    mostrarError(data.message || 'Error al crear el usuario');
                }
            })
            .catch(error => {
                cerrarCargando();
                mostrarError('Error de conexión al crear el usuario');
                console.error(error);
            });
        }
        
        // ==========================================
        // PROCESAR SALIDA DESDE PERFIL
        // ==========================================
        
        async function procesarSalidaDesdePerfil() {
            const funcionarioId = <?php echo $id; ?>;
            const funcionarioNombre = '<?php echo addslashes($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?>';
            
            // Selector de tipo de salida
            const { value: tipoSalida } = await Swal.fire({
                title: 'Tipo de Baja',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 20px; color: #718096;">
                            <strong>Funcionario:</strong> ${funcionarioNombre}
                        </p>
                        <p style="margin-bottom: 16px; font-weight: 600; color: #2d3748;">Seleccione el tipo de baja a procesar:</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '❌ Despido',
                denyButtonText: '📝 Renuncia',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                denyButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280'
            });
            
            if (!tipoSalida) return;
            
            const esDespido = tipoSalida === true; // true = confirmed (despido), false = denied (renuncia)
            const tipoEvento = esDespido ? 'DESPIDO' : 'RENUNCIA';
            const titulo = esDespido ? '⚠️ Procesar Despido' : '📝 Procesar Renuncia';
            const colorBoton = esDespido ? '#dc2626' : '#3b82f6';
            const minCaracteres = esDespido ? 20 : 15;
            
            // Formulario de datos
            const { value: formValues } = await Swal.fire({
                title: titulo,
                html: `
                    ${esDespido ? `
                    <div style="background: #fef2f2; border: 2px solid #fca5a5; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                        <div style="display: flex; align-items: center; gap: 10px; color: #991b1b;">
                            <span style="font-size: 28px;">⚠️</span>
                            <div style="text-align: left; flex: 1;">
                                <strong style="display: block; font-size: 14px; margin-bottom: 3px;">ADVERTENCIA</strong>
                                <p style="margin: 0; font-size: 12px;">Esta acción es <strong>IRREVERSIBLE</strong>. El funcionario será <strong>DESACTIVADO</strong>.</p>
                            </div>
                        </div>
                    </div>
                    ` : `
                    <div style="background: #eff6ff; border: 2px solid #93c5fd; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                        <div style="display: flex; align-items: center; gap: 10px; color: #1e40af;">
                            <span style="font-size: 24px;">ℹ️</span>
                            <div style="text-align: left; flex: 1;">
                                <p style="margin: 0; font-size: 12px;">El funcionario será marcado como inactivo en el sistema.</p>
                            </div>
                        </div>
                    </div>
                    `}
                    
                    <div style="text-align: left;">
                        <div style="margin-bottom: 14px; background: #f7fafc; padding: 12px; border-radius: 8px;">
                            <strong style="color: #2d3748;">Funcionario:</strong> ${funcionarioNombre}
                        </div>
                        
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Fecha del ${esDespido ? 'Despido' : 'Renuncia'} *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 100%; padding: 9px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Motivo *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" style="width: 100%; padding: 9px; min-height: 100px;" placeholder="${esDespido ? 'Describa detalladamente el motivo del despido...' : 'Ejemplo: Renuncia voluntaria - Motivos personales'}"></textarea>
                            <small style="color: #718096; font-size: 12px;">Mínimo ${minCaracteres} caracteres</small>
                        </div>
                        
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Documento PDF (Opcional)</label>
                            <input type="file" id="swal-archivo" accept=".pdf" class="swal2-file" style="width: 100%; padding: 9px;">
                            <small style="color: #718096; font-size: 12px;">Tamaño máximo: 5MB</small>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: `Procesar ${esDespido ? 'Despido' : 'Renuncia'}`,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: colorBoton,
                cancelButtonColor: '#6b7280',
                preConfirm: () => {
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const archivo = document.getElementById('swal-archivo').files[0];
                    
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!motivo || motivo.trim().length < minCaracteres) { Swal.showValidationMessage(`El motivo debe tener al menos ${minCaracteres} caracteres`); return false; }
                    if (archivo && archivo.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    if (archivo && archivo.type !== 'application/pdf') { Swal.showValidationMessage('Solo archivos PDF'); return false; }
                    
                    return { fecha_evento, motivo, archivo };
                }
            });
            
            if (!formValues) return;
            
            // Confirmación final para despidos
            if (esDespido) {
                const confirmacion = await Swal.fire({
                    title: '¿Está completamente seguro?',
                    html: `
                        <div style="text-align: left; color: #1f2937;">
                            <p style="margin-bottom: 12px;"><strong>Al confirmar:</strong></p>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 6px;">Funcionario: <strong>INACTIVO</strong></li>
                                <li style="margin-bottom: 6px;">Usuario: <strong>DESACTIVADO</strong></li>
                                <li style="margin-bottom: 6px;">Acción: <strong>NO SE PUEDE DESHACER</strong></li>
                            </ul>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, procesar',
                    cancelButtonText: 'No, cancelar',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#10b981'
                });
                
                if (!confirmacion.isConfirmed) return;
            }
            
            // Procesar
            Swal.fire({ title: 'Procesando...', html: `Registrando ${tipoEvento.toLowerCase()}...`, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const formData = new FormData();
                formData.append('accion', 'registrar_despido');
                formData.append('funcionario_id', funcionarioId);
                formData.append('tipo_evento', tipoEvento);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('motivo', formValues.motivo);
                if (formValues.archivo) formData.append('archivo_pdf', formValues.archivo);
                
                const response = await fetch('ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: `${esDespido ? 'Despido' : 'Renuncia'} Procesado`,
                        html: `
                            <p>El registro se completó exitosamente.</p>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 10px; margin-top: 14px; text-align: left;">
                                <p style="margin: 0; font-size: 13px;"><strong>✓ Funcionario:</strong> Inactivo</p>
                                <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Usuario:</strong> Desactivado</p>
                            </div>
                        `,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al procesar', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../publico/js/sweetalert-utils.js"></script>

    
    <!-- UX Mejoras: Feedback automático de formularios -->
    <script src="<?php echo APP_URL; ?>/publico/js/ux-mejoras.js"></script>
</body>
</html>
