<?php
/**
 * Vista: Expediente Digital (Ver Funcionario)
 * Versión completa con tabs funcionales
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

// Verificar permisos de acceso
if (!verificarDepartamento($id) && $_SESSION['nivel_acceso'] > 2) {
    $_SESSION['error'] = 'No tiene permisos para ver este expediente';
    header('Location: index.php');
    exit;
}

// Tab activo
$tab = $_GET['tab'] ?? 'info';

// Permisos del usuario actual
$puede_editar = verificarDepartamento($id);
$puede_eliminar = puedeEliminar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente: <?php echo htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js">
    <style>
        .expediente-header {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .expediente-avatar {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: var(--color-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .expediente-info {
            flex: 1;
        }
        
        .expediente-nombre {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 8px;
        }
        
        .expediente-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            color: var(--color-text-light);
            margin-bottom: 12px;
        }
        
        .expediente-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .expediente-actions {
            display: flex;
            gap: 12px;
        }
        
        .tabs {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .tabs-header {
            display: flex;
            border-bottom: 1px solid var(--color-border-light);
            overflow-x: auto;
        }
        
        .tab-link {
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text-light);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .tab-link:hover {
            color: var(--color-text);
            background: var(--color-bg);
        }
        
        .tab-link.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            background: var(--color-bg);
        }
        
        .tab-content {
            padding: 32px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .info-item {
            padding: 16px;
            background: var(--color-bg);
            border-radius: var(--radius-md);
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 15px;
            color: var(--color-text);
            font-weight: 500;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--color-border-light);
        }
        
        .form-upload {
            background: var(--color-bg);
            padding: 24px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-row-full {
            grid-column: 1 / -1;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--color-border-light);
        }
        
        .data-table th {
            background: var(--color-bg);
            font-weight: 600;
            font-size: 13px;
            color: var(--color-text);
        }
        
        .data-table td {
            font-size: 14px;
            color: var(--color-text);
        }
        
        .data-table tbody tr:hover {
            background: var(--color-bg);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-light);
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: var(--radius-sm);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            padding: 20px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }
        
        .stats-label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .stats-value {
            font-size: 32px;
            font-weight: 700;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .progress-fill {
            height: 100%;
            background: white;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .expediente-header {
                flex-direction: column;
                text-align: center;
            }
            
            .info-grid,
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Expediente Digital</h1>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                    ← Volver al Listado
                </a>
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
                        <div class="expediente-meta-item">
                            <span>📋</span>
                            <span><?php echo htmlspecialchars($funcionario['cedula']); ?></span>
                        </div>
                        <div class="expediente-meta-item">
                            <span>💼</span>
                            <span><?php echo htmlspecialchars($funcionario['nombre_cargo']); ?></span>
                        </div>
                        <div class="expediente-meta-item">
                            <span>🏢</span>
                            <span><?php echo htmlspecialchars($funcionario['departamento']); ?></span>
                        </div>
                    </div>
                    
                    <?php
                    $badge_class = 'badge-success';
                    $estado_texto = 'Activo';
                    
                    switch ($funcionario['estado']) {
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
                </div>
                
                <div class="expediente-actions">
                    <?php if ($puede_editar): ?>
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            ✏️ Editar
                        </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn" style="background: #e2e8f0; color: #2d3748;">
                        🖨️ Imprimir
                    </button>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <div class="tabs-header">
                    <a href="?id=<?php echo $id; ?>&tab=info" class="tab-link <?php echo $tab == 'info' ? 'active' : ''; ?>">
                        📄 Información Personal
                    </a>
                    <a href="?id=<?php echo $id; ?>&tab=nombramientos" class="tab-link <?php echo $tab == 'nombramientos' ? 'active' : ''; ?>">
                        📝 Nombramientos
                    </a>
                    <a href="?id=<?php echo $id; ?>&tab=vacaciones" class="tab-link <?php echo $tab == 'vacaciones' ? 'active' : ''; ?>">
                        🏖️ Vacaciones
                    </a>
                    <a href="?id=<?php echo $id; ?>&tab=amonestaciones" class="tab-link <?php echo $tab == 'amonestaciones' ? 'active' : ''; ?>">
                        ⚠️ Amonestaciones
                    </a>
                    <?php if (puedeAprobarDespidos()): ?>
                        <a href="?id=<?php echo $id; ?>&tab=salidas" class="tab-link <?php echo $tab == 'salidas' ? 'active' : ''; ?>">
                            🚪 Salidas
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content">
                    <div id="alert-container"></div>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <span>✓</span>
                            <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                        </div>
                        <?php if (isset($_SESSION['username_generado'])): ?>
                            <div class="alert alert-info" style="background: #dbeafe; color: #1e40af; border-color: #93c5fd;">
                                <span>🔑</span>
                                <div>
                                    <strong>Cuenta de usuario creada</strong><br>
                                    <span style="font-size: 13px;">
                                        Usuario: <strong><?php echo htmlspecialchars($_SESSION['username_generado']); ?></strong> | 
                                        Cédula: <strong><?php echo htmlspecialchars($_SESSION['cedula_empleado']); ?></strong>
                                    </span><br>
                                    <span style="font-size: 12px; opacity: 0.9;">
                                        El empleado debe completar su registro en <a href="../../registro.php" style="color: #1e40af; font-weight: 600;">registro.php</a> usando su cédula.
                                    </span>
                                </div>
                            </div>
                            <?php 
                            unset($_SESSION['username_generado']);
                            unset($_SESSION['cedula_empleado']);
                            ?>
                        <?php endif; ?>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if ($tab == 'info'): ?>
                        <!-- TAB: Información Personal -->
                        <h3 class="section-title">Datos Personales</h3>
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
                                    <?php 
                                    if ($funcionario['fecha_nacimiento']) {
                                        echo formatearFecha($funcionario['fecha_nacimiento']) . ' (' . $funcionario['edad'] . ' años)';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Género</div>
                                <div class="info-value">
                                    <?php 
                                    $generos = ['M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro'];
                                    echo $generos[$funcionario['genero']] ?? '-';
                                    ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value"><?php echo htmlspecialchars($funcionario['telefono'] ?? '-'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Correo Electrónico</div>
                                <div class="info-value"><?php echo htmlspecialchars($funcionario['email'] ?? '-'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Dirección</div>
                                <div class="info-value"><?php echo htmlspecialchars($funcionario['direccion'] ?? '-'); ?></div>
                            </div>
                        </div>
                        
                        <h3 class="section-title" style="margin-top: 32px;">Datos Laborales</h3>
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
                                    <?php echo formatearFecha($funcionario['fecha_ingreso']); ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Antigüedad</div>
                                <div class="info-value"><?php echo $funcionario['antiguedad_anos']; ?> año(s)</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Estado</div>
                                <div class="info-value">
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $estado_texto; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Fecha de Registro</div>
                                <div class="info-value">
                                    <?php echo formatearFecha(date('Y-m-d', strtotime($funcionario['created_at']))); ?>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif ($tab == 'nombramientos'): ?>
                        <!-- TAB: Nombramientos -->
                        <h3 class="section-title">Control de Nombramientos y Contratación</h3>
                        
                        <?php if ($puede_editar): ?>
                        <div class="form-upload">
                            <h4 style="margin-bottom: 16px; font-size: 16px; font-weight: 600;">Registrar Nuevo Nombramiento</h4>
                            <form id="form-nombramiento" enctype="multipart/form-data">
                                <input type="hidden" name="funcionario_id" value="<?php echo $id; ?>">
                                
                                <div class="form-row">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Categoría</label>
                                        <select name="categoria" class="search-input" style="width: 100%;">
                                            <option value="">Seleccione...</option>
                                            <option value="Contrato Fijo">Contrato Fijo</option>
                                            <option value="Contrato Temporal">Contrato Temporal</option>
                                            <option value="Nombramiento">Nombramiento</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Título *</label>
                                        <input type="text" name="titulo" class="search-input" style="width: 100%;" required placeholder="Ej: Nombramiento como Técnico">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Fecha de Inicio *</label>
                                        <input type="date" name="fecha_inicio" class="search-input" style="width: 100%;" required>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Fecha de Fin</label>
                                        <input type="date" name="fecha_fin" class="search-input" style="width: 100%;">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Descripción</label>
                                        <textarea name="descripcion" class="search-input" style="width: 100%; min-height: 80px;" placeholder="Detalles adicionales..."></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Documento (PDF, JPG, PNG) *</label>
                                        <input type="file" name="archivo" class="search-input" style="width: 100%;" accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Guardar Nombramiento</button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <div id="nombramientos-list">
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 24px; margin-bottom: 12px;">⏳</div>
                                <p style="color: #a0aec0;">Cargando nombramientos...</p>
                            </div>
                        </div>
                        
                    <?php elseif ($tab == 'vacaciones'): ?>
                        <!-- TAB: Vacaciones -->
                        <h3 class="section-title">Control Vacacional</h3>
                        
                        <div id="vacaciones-stats" style="display: none;">
                            <div class="stats-card">
                                <div class="stats-label">Días Disponibles</div>
                                <div class="stats-value"><span id="dias-disponibles">0</span> días</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progress-vacaciones" style="width: 0%"></div>
                                </div>
                                <div style="margin-top: 8px; font-size: 13px; opacity: 0.9;">
                                    <span id="dias-usados">0</span> días usados de <span id="dias-totales">0</span> días
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($puede_editar): ?>
                        <div class="form-upload">
                            <h4 style="margin-bottom: 16px; font-size: 16px; font-weight: 600;">Registrar Período de Vacaciones</h4>
                            <form id="form-vacacion" enctype="multipart/form-data">
                                <input type="hidden" name="funcionario_id" value="<?php echo $id; ?>">
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Título *</label>
                                        <input type="text" name="titulo" class="search-input" style="width: 100%;" required placeholder="Ej: Vacaciones 2024">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Fecha de Inicio *</label>
                                        <input type="date" name="fecha_inicio" class="search-input" style="width: 100%;" required>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Fecha de Fin *</label>
                                        <input type="date" name="fecha_fin" class="search-input" style="width: 100%;" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Observaciones</label>
                                        <textarea name="descripcion" class="search-input" style="width: 100%; min-height: 60px;" placeholder="Observaciones adicionales..."></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Solicitud Firmada (Opcional)</label>
                                        <input type="file" name="archivo" class="search-input" style="width: 100%;" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Registrar Vacaciones</button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <div id="vacaciones-list">
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 24px; margin-bottom: 12px;">⏳</div>
                                <p style="color: #a0aec0;">Cargando historial de vacaciones...</p>
                            </div>
                        </div>
                        
                    <?php elseif ($tab == 'amonestaciones'): ?>
                        <!-- TAB: Amonestaciones -->
                        <h3 class="section-title">Control de Amonestaciones y Sanciones</h3>
                        
                        <?php if ($puede_editar): ?>
                        <div class="form-upload">
                            <h4 style="margin-bottom: 16px; font-size: 16px; font-weight: 600;">Registrar Nueva Amonestación</h4>
                            <form id="form-amonestacion" enctype="multipart/form-data">
                                <input type="hidden" name="funcionario_id" value="<?php echo $id; ?>">
                                
                                <div class="form-row">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Título *</label>
                                        <input type="text" name="titulo" class="search-input" style="width: 100%;" required placeholder="Ej: Amonestación Verbal">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Fecha de la Falta *</label>
                                        <input type="date" name="fecha_falta" class="search-input" style="width: 100%;" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Tipo de Falta *</label>
                                        <select name="tipo_falta" class="search-input" style="width: 100%;" required>
                                            <option value="">Seleccione...</option>
                                            <option value="Leve">Leve</option>
                                            <option value="Grave">Grave</option>
                                            <option value="Muy Grave">Muy Grave</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Sanción Aplicada</label>
                                        <input type="text" name="sancion_aplicada" class="search-input" style="width: 100%;" placeholder="Ej: Amonestación escrita">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Descripción del Incidente</label>
                                        <textarea name="descripcion" class="search-input" style="width: 100%; min-height: 80px;" placeholder="Describa el incidente..."></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-row-full">
                                        <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Memorándum Firmado (PDF, JPG, PNG) *</label>
                                        <input type="file" name="archivo" class="search-input" style="width: 100%;" accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Registrar Amonestación</button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <div id="amonestaciones-list">
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 24px; margin-bottom: 12px;">⏳</div>
                                <p style="color: #a0aec0;">Cargando historial de amonestaciones...</p>
                            </div>
                        </div>
                        
                    <?php elseif ($tab == 'salidas'): ?>
                        <!-- TAB: Salidas -->
                        <div class="empty-state">
                            <div class="empty-state-icon">🚪</div>
                            <h3>Registro de Salidas</h3>
                            <p>Aquí se registrarán despidos y renuncias</p>
                            <p style="margin-top: 12px; font-size: 14px; color: #a0aec0;">Funcionalidad en desarrollo</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const funcionarioId = <?php echo $id; ?>;
        const puedeEditar = <?php echo $puede_editar ? 'true' : 'false'; ?>;
        const puedeEliminar = <?php echo $puede_eliminar ? 'true' : 'false'; ?>;
        const nivelAcceso = <?php echo $_SESSION['nivel_acceso']; ?>;
        
        // Funciones de utilidad
        function showAlert(message, type = 'success') {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <span>${type === 'success' ? '✓' : '⚠️'}</span>
                <span>${message}</span>
            `;
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-VE', { year: 'numeric', month: 'long', day: 'numeric' });
        }
        
        function formatFileSize(bytes) {
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }
        
        // NOMBRAMIENTOS
        <?php if ($tab == 'nombramientos'): ?>
        function loadNombramientos() {
            fetch(`ajax/get_nombramientos.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderNombramientos(data.data);
                    } else {
                        document.getElementById('nombramientos-list').innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">❌</div>
                                <p>Error: ${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('nombramientos-list').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <p>Error al cargar los nombramientos</p>
                        </div>
                    `;
                });
        }
        
        function renderNombramientos(nombramientos) {
            const container = document.getElementById('nombramientos-list');
            
            if (nombramientos.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📝</div>
                        <h4>No hay nombramientos registrados</h4>
                        <p>Los nombramientos aparecerán aquí una vez que sean registrados</p>
                    </div>
                `;
                return;
            }
            
            let html = '<table class="data-table"><thead><tr>';
            html += '<th>Categoría</th>';
            html += '<th>Título</th>';
            html += '<th>Fecha Inicio</th>';
            html += '<th>Fecha Fin</th>';
            html += '<th>Documento</th>';
            if (puedeEliminar) html += '<th>Acciones</th>';
            html += '</tr></thead><tbody>';
            
            nombramientos.forEach(nom => {
                html += '<tr>';
                html += `<td>${nom.categoria || '-'}</td>`;
                html += `<td>${nom.titulo}</td>`;
                html += `<td>${formatDate(nom.fecha_inicio)}</td>`;
                html += `<td>${formatDate(nom.fecha_fin)}</td>`;
                html += `<td>`;
                if (nom.ruta_archivo) {
                    html += `<a href="../../subidas/${nom.ruta_archivo}" target="_blank" class="btn-small" style="background: var(--color-primary); color: white; text-decoration: none;">
                        📄 Ver (${formatFileSize(nom.tamano_archivo)})
                    </a>`;
                } else {
                    html += '-';
                }
                html += `</td>`;
                if (puedeEliminar) {
                    html += `<td>
                        <button onclick="deleteNombramiento(${nom.id})" class="btn-small" style="background: var(--color-danger); color: white;">
                            🗑️ Eliminar
                        </button>
                    </td>`;
                }
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        function deleteNombramiento(id) {
            confirmarPeligro(
                'Esta acción eliminará permanentemente este nombramiento del sistema.',
                '¿Está seguro de eliminar este nombramiento?',
                'Sí, eliminar',
                'Cancelar'
            ).then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Eliminando nombramiento...');
                    
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('ajax/delete_nombramiento.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            mostrarExito('Nombramiento eliminado correctamente').then(() => {
                                loadNombramientos();
                            });
                        } else {
                            mostrarError(data.message || 'Error al eliminar');
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        mostrarError('Error al procesar la solicitud');
                        console.error(error);
                    });
                }
            });
        }
        
        <?php if ($puede_editar): ?>
        document.getElementById('form-nombramiento').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            
            fetch('ajax/upload_nombramiento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    loadNombramientos();
                } else {
                    showAlert(data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al guardar el nombramiento', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Nombramiento';
            });
        });
        <?php endif; ?>
        
        loadNombramientos();
        <?php endif; ?>
        
        // VACACIONES
        <?php if ($tab == 'vacaciones'): ?>
        function loadVacaciones() {
            fetch(`ajax/get_vacaciones.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderVacaciones(data.data, data.calculo);
                    } else {
                        document.getElementById('vacaciones-list').innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">❌</div>
                                <p>Error: ${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('vacaciones-list').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <p>Error al cargar las vacaciones</p>
                        </div>
                    `;
                });
        }
        
        function renderVacaciones(vacaciones, calculo) {
            // Mostrar estadísticas
            if (calculo) {
                document.getElementById('vacaciones-stats').style.display = 'block';
                document.getElementById('dias-disponibles').textContent = calculo.dias_disponibles;
                document.getElementById('dias-usados').textContent = calculo.dias_usados;
                document.getElementById('dias-totales').textContent = calculo.dias_disponibles;
                
                const porcentaje = (calculo.dias_usados / calculo.dias_disponibles) * 100;
                document.getElementById('progress-vacaciones').style.width = porcentaje + '%';
            }
            
            const container = document.getElementById('vacaciones-list');
            
            if (vacaciones.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🏖️</div>
                        <h4>No hay vacaciones registradas</h4>
                        <p>Los períodos de vacaciones aparecerán aquí una vez que sean registrados</p>
                    </div>
                `;
                return;
            }
            
            let html = '<table class="data-table"><thead><tr>';
            html += '<th>Período</th>';
            html += '<th>Fecha Inicio</th>';
            html += '<th>Fecha Fin</th>';
            html += '<th>Días</th>';
            html += '<th>Documento</th>';
            if (puedeEliminar) html += '<th>Acciones</th>';
            html += '</tr></thead><tbody>';
            
            vacaciones.forEach(vac => {
                html += '<tr>';
                html += `<td>${vac.titulo}</td>`;
                html += `<td>${formatDate(vac.fecha_inicio)}</td>`;
                html += `<td>${formatDate(vac.fecha_fin)}</td>`;
                html += `<td><strong>${vac.dias_totales}</strong> días</td>`;
                html += `<td>`;
                if (vac.ruta_archivo) {
                    html += `<a href="../../subidas/${vac.ruta_archivo}" target="_blank" class="btn-small" style="background: var(--color-primary); color: white; text-decoration: none;">
                        📄 Ver
                    </a>`;
                } else {
                    html += '-';
                }
                html += `</td>`;
                if (puedeEliminar) {
                    html += `<td>
                        <button onclick="deleteVacacion(${vac.id})" class="btn-small" style="background: var(--color-danger); color: white;">
                            🗑️ Eliminar
                        </button>
                    </td>`;
                }
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        function deleteVacacion(id) {
            confirmarPeligro(
                'Esta acción eliminará permanentemente este registro de vacaciones.',
                '¿Está seguro de eliminar este registro?',
                'Sí, eliminar',
                'Cancelar'
            ).then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Eliminando registro...');
                    
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('ajax/delete_vacacion.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            mostrarExito('Registro eliminado correctamente').then(() => {
                                loadVacaciones();
                            });
                        } else {
                            mostrarError(data.message || 'Error al eliminar');
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        mostrarError('Error al procesar la solicitud');
                        console.error(error);
                    });
                }
            });
        }
        
        <?php if ($puede_editar): ?>
        document.getElementById('form-vacacion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registrando...';
            
            fetch('ajax/upload_vacacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message + ` (${data.dias_totales} días)`, 'success');
                    this.reset();
                    loadVacaciones();
                } else {
                    showAlert(data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al registrar las vacaciones', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Registrar Vacaciones';
            });
        });
        <?php endif; ?>
        
        loadVacaciones();
        <?php endif; ?>
        
        // AMONESTACIONES
        <?php if ($tab == 'amonestaciones'): ?>
        function loadAmonestaciones() {
            fetch(`ajax/get_amonestaciones.php?funcionario_id=${funcionarioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAmonestaciones(data.data);
                    } else {
                        document.getElementById('amonestaciones-list').innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">❌</div>
                                <p>Error: ${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('amonestaciones-list').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <p>Error al cargar las amonestaciones</p>
                        </div>
                    `;
                });
        }
        
        function renderAmonestaciones(amonestaciones) {
            const container = document.getElementById('amonestaciones-list');
            
            if (amonestaciones.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <h4>No hay amonestaciones registradas</h4>
                        <p>Este funcionario no tiene amonestaciones en su expediente</p>
                    </div>
                `;
                return;
            }
            
            let html = '<table class="data-table"><thead><tr>';
            html += '<th>Título</th>';
            html += '<th>Fecha Falta</th>';
            html += '<th>Tipo</th>';
            html += '<th>Sanción</th>';
            html += '<th>Documento</th>';
            if (nivelAcceso === 1) html += '<th>Acciones</th>';
            html += '</tr></thead><tbody>';
            
            amonestaciones.forEach(amon => {
                html += '<tr>';
                html += `<td>${amon.titulo}</td>`;
                html += `<td>${formatDate(amon.fecha_falta)}</td>`;
                html += `<td><span class="badge ${amon.tipo_falta === 'Muy Grave' ? 'badge-danger' : (amon.tipo_falta === 'Grave' ? 'badge-warning' : 'badge-info')}">${amon.tipo_falta}</span></td>`;
                html += `<td>${amon.sancion_aplicada || '-'}</td>`;
                html += `<td>`;
                if (amon.ruta_archivo) {
                    html += `<a href="../../subidas/${amon.ruta_archivo}" target="_blank" class="btn-small" style="background: var(--color-primary); color: white; text-decoration: none;">
                        📄 Ver Memorándum
                    </a>`;
                } else {
                    html += '-';
                }
                html += `</td>`;
                if (nivelAcceso === 1) {
                    html += `<td>
                        <button onclick="deleteAmonestacion(${amon.id})" class="btn-small" style="background: var(--color-danger); color: white;">
                            🗑️ Eliminar
                        </button>
                    </td>`;
                }
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        function deleteAmonestacion(id) {
            confirmarPeligro(
                'Esta acción solo debe realizarse en casos excepcionales. El registro se eliminará permanentemente del sistema.',
                '¿Está seguro de eliminar esta amonestación?',
                'Sí, eliminar',
                'Cancelar'
            ).then((result) => {
                if (result.isConfirmed) {
                    mostrarCargando('Eliminando amonestación...');
                    
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('ajax/delete_amonestacion.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        cerrarCargando();
                        if (data.success) {
                            mostrarExito('Amonestación eliminada correctamente').then(() => {
                                loadAmonestaciones();
                            });
                        } else {
                            mostrarError(data.message || 'Error al eliminar');
                        }
                    })
                    .catch(error => {
                        cerrarCargando();
                        mostrarError('Error al procesar la solicitud');
                        console.error(error);
                    });
                }
            });
        }
        
        <?php if ($puede_editar): ?>
        document.getElementById('form-amonestacion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registrando...';
            
            fetch('ajax/upload_amonestacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    loadAmonestaciones();
                } else {
                    showAlert(data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al registrar la amonestación', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Registrar Amonestación';
            });
        });
        <?php endif; ?>
        
        loadAmonestaciones();
        <?php endif; ?>
    </script>
    
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="../../publico/js/sweetalert-utils.js"></script>
</body>
</html>
