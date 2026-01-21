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
            <!-- Header del Expediente -->
            <div class="expediente-header">
                <div class="expediente-avatar">
                    <?php echo strtoupper(substr($funcionario['nombres'], 0, 1) . substr($funcionario['apellidos'], 0, 1)); ?>
                </div>
                <div class="expediente-info">
                    <h2 class="expediente-nombre">
                        <?php echo htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?>
                    </h2>
                    <div class="expediente-meta">
                        <span class="expediente-meta-item">
                            <strong>Cédula:</strong> <?php echo htmlspecialchars($funcionario['cedula']); ?>
                        </span>
                        <span class="expediente-meta-item">
                            <strong>Cargo:</strong> <?php echo htmlspecialchars($funcionario['nombre_cargo']); ?>
                        </span>
                        <span class="expediente-meta-item">
                            <strong>Departamento:</strong> <?php echo htmlspecialchars($funcionario['departamento']); ?>
                        </span>
                        <span class="badge badge-success">
                            <?php echo ucfirst($funcionario['estado']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Pestañas Moderno -->
            <div class="tabs-grid-container">
                <div class="tabs-grid">
                    <div class="tab-card active" style="--gradient: var(--tab-info);" onclick="switchTab('info', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <div class="tab-title">Información Personal</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-cargas);" onclick="switchTab('cargas', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </span>
                            <div class="tab-title">Cargas Familiares</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-activos);" onclick="switchTab('activos', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                </svg>
                            </span>
                            <div class="tab-title">Activos Asignados</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-vacaciones);" onclick="switchTab('vacaciones-calc', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
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
                            </span>
                            <div class="tab-title">Calculadora Vacaciones</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-riesgo);" onclick="switchTab('riesgo', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </span>
                            <div class="tab-title">Barra de Riesgo</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-nombramientos);" onclick="switchTab('nombramientos', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <line x1="10" y1="9" x2="8" y2="9"></line>
                                </svg>
                            </span>
                            <div class="tab-title">Nombramientos</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-historial-vac);" onclick="switchTab('vacaciones', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </span>
                            <div class="tab-title">Historial Vacaciones</div>
                        </div>
                    </div>
                    
                    <div class="tab-card" style="--gradient: var(--tab-amonestaciones);" onclick="switchTab('amonestaciones', this)">
                        <div class="tab-card-content">
                            <span class="tab-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                            </span>
                            <div class="tab-title">Amonestaciones</div>
                        </div>
                    </div>
                </div>
            </div>
            
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
            
            <!-- TAB 3: Activos Asignados -->
            <div id="tab-activos" class="tab-content">
                <div class="content-card">
                    <h3 class="section-title">
                        <span class="section-title-icon">💻</span>
                        Activos Tecnológicos Asignados
                    </h3>
                    <div id="activos-container">
                        <div class="empty-state">
                            <div class="empty-state-icon">⏳</div>
                            <p class="empty-state-text">Cargando información...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TAB 4: Calculadora de Vacaciones -->
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
        const funcionarioId = <?php echo $id; ?>;
        let currentTab = 'info';
        
        // Sistema de Pestañas
        function switchTab(tabName, element) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Desactivar todas las tarjetas
            document.querySelectorAll('.tab-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Activar pestaña seleccionada
            document.getElementById('tab-' + tabName).classList.add('active');
            element.classList.add('active');
            
            currentTab = tabName;
            
            // Cargar datos según la pestaña
            switch(tabName) {
                case 'cargas':
                    cargarCargasFamiliares();
                    break;
                case 'activos':
                    cargarActivos();
                    break;
                case 'vacaciones-calc':
                    calcularVacaciones();
                    break;
                case 'riesgo':
                    cargarBarraRiesgo();
                    break;
                case 'nombramientos':
                    cargarNombramientos();
                    break;
                case 'vacaciones':
                    cargarVacaciones();
                    break;
                case 'amonestaciones':
                    cargarAmonestaciones();
                    break;
            }
        }
        
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
                    alert('Carga familiar guardada exitosamente');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar la carga familiar');
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
        
        // TAB 6, 7, 8: Historial
        function cargarNombramientos() {
            const db = <?php echo json_encode(getDB()); ?>;
            // Por implementar: consulta a tabla nombramientos
            document.getElementById('nombramientos-container').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <p class="empty-state-text">No hay nombramientos registrados</p>
                </div>
            `;
        }
        
        function cargarVacaciones() {
            document.getElementById('vacaciones-historial-container').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🌴</div>
                    <p class="empty-state-text">No hay vacaciones registradas</p>
                </div>
            `;
        }
        
        function cargarAmonestaciones() {
            document.getElementById('amonestaciones-container').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚡</div>
                    <p class="empty-state-text">No hay amonestaciones registradas</p>
                </div>
            `;
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalCarga')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCarga();
            }
        });
    </script>
</body>
</html>
