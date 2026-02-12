<?php
/**
 * Vista: Expediente Digital - Diseño Profesional (Versión Blindada v5.0)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';
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
    <title>Expediente: <?= htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']) ?> - <?= APP_NAME ?></title>
    
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/sidebar-fix.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/header-fix.css">

    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        /* Estilos específicos para el expediente */
        .expediente-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* Contenido Principal */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--color-border);
        }
        
        .section-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-text);
            margin: 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .info-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        
        .info-value {
            font-size: 15px;
            color: #1e293b;
            font-weight: 500;
        }
        
        /* ESTILOS DEL MODAL (Corregidos) */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); /* Fondo oscuro transparente */
            backdrop-filter: blur(2px);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex; /* Mostrar como flexbox para centrar */
        }

        .modal-content {
            background-color: #fff;
            padding: 24px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #0F4C81;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .expediente-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="index.php" class="btn-secondary">
                    <?= Icon::get('arrow-left') ?>
                    Volver
                </a>
                <h1>Expediente Digital</h1>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <a href="../reportes/constancia_trabajo.php?id=<?= $id ?>" target="_blank" class="btn-primary" style="background: var(--color-success);">
                    <?= Icon::get('file-text') ?>
                    Constancia
                </a>
                <?php if ($puede_editar): ?>
                    <a href="editar.php?id=<?= $id ?>" class="btn-primary">
                        <?= Icon::get('edit') ?>
                        Editar
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="expediente-layout">
            <aside class="card-modern" style="padding: 0; overflow: hidden; background: white; position: sticky; top: 24px;">
                
                <div style="background: linear-gradient(135deg, #0F4C81 0%, #00A8E8 100%); padding: 30px 20px; text-align: center;">
                    <div style="width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; background: white; padding: 3px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <?php if (!empty($funcionario['foto'])): ?>
                            <img src="<?= APP_URL ?>/subidas/fotos/<?= $funcionario['foto'] ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: #E2E8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748B; font-size: 40px; font-weight: bold;">
                                <?= strtoupper(substr($funcionario['nombres'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 style="color: white; margin: 0; font-weight: 600; font-size: 16px;">
                        <?= htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']) ?>
                    </h5>
                    <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0; font-size: 13px;">
                        <?= htmlspecialchars($funcionario['nombre_cargo']) ?>
                    </p>
                     <?php
                        $badge_style = 'border: 1px solid rgba(255,255,255,0.4); background: rgba(255,255,255,0.15); color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 8px; display: inline-block;';
                        if ($funcionario['estado'] == 'vacaciones') $badge_style .= 'background: rgba(245, 158, 11, 0.2); border-color: rgba(245, 158, 11, 0.5);';
                        if ($funcionario['estado'] == 'inactivo') $badge_style .= 'background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.5);';
                    ?>
                    <span style="<?= $badge_style ?>"><?= ucfirst($funcionario['estado']) ?></span>
                </div>

                <div class="profile-menu-list" style="padding: 15px; display: flex; flex-direction: column; gap: 5px;">
                    <?php 
                    $menu_items = [
                        ['id' => 'tab-info', 'label' => 'Información Personal', 'icon' => 'user'],
                        ['id' => 'tab-cargas', 'label' => 'Cargas Familiares', 'icon' => 'users'],
                        ['id' => 'tab-nombramientos', 'label' => 'Historial Nombramientos', 'icon' => 'file-text'],
                        ['id' => 'tab-vacaciones', 'label' => 'Historial Vacaciones', 'icon' => 'sun'],
                        ['id' => 'tab-riesgo', 'label' => 'Barra de Riesgo', 'icon' => 'activity'],
                        ['id' => 'tab-amonestaciones', 'label' => 'Amonestaciones', 'icon' => 'alert-circle'],
                        ['id' => 'tab-salidas', 'label' => 'Retiros/Despidos', 'icon' => 'log-out'],
                    ];
                    
                    foreach($menu_items as $item): 
                        $isActive = ($item['id'] === 'tab-info'); // La primera activa por defecto
                    ?>
                        <button onclick="showTab('<?= $item['id'] ?>', this)" 
                           class="profile-nav-link <?= $isActive ? 'active' : '' ?>"
                           style="
                               width: 100%;
                               display: flex;
                               align-items: center;
                               gap: 12px;
                               padding: 12px 15px;
                               border: none;
                               background: <?= $isActive ? '#E0F2FE' : 'transparent' ?>;
                               color: <?= $isActive ? '#0F4C81' : '#64748B' ?>;
                               border-radius: 8px;
                               cursor: pointer;
                               font-size: 14px;
                               font-weight: 500;
                               text-align: left;
                               transition: all 0.2s;
                           "
                           onmouseover="this.style.background='#F1F5F9'; this.style.color='#0F4C81'"
                           onmouseout="if(!this.classList.contains('active')){ this.style.background='transparent'; this.style.color='#64748B' } else { this.style.background='#E0F2FE' }"
                        >
                            <?= Icon::get($item['icon'], 'width:18px;') ?>
                            <?= $item['label'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                 <div style="padding: 20px; border-top: 1px solid var(--color-border); background: #f8fafc;">
                    <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">
                        Acceso al Sistema
                    </div>
                    
                    <?php if ($tiene_usuario): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= $estado_usuario == 'activo' ? '#10b981' : '#ef4444' ?>;"></div>
                            <span style="font-size: 13px; font-weight: 500; color: #1e293b;">
                                <?= htmlspecialchars($username_display) ?>
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                            <?= ucfirst($estado_usuario) ?>
                        </div>
                    <?php else: ?>
                        <?php if (verificarNivel(2)): ?>
                            <button onclick="abrirModalCrearUsuario()" class="btn-primary" style="width: 100%; font-size: 13px; padding: 8px;">
                                Crear Usuario
                            </button>
                        <?php else: ?>
                            <span style="font-size: 13px; color: #64748b;">Sin acceso</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </aside>
            
            <main>
                <div id="tab-info" class="tab-content active">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('user', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                <h2>Datos Personales</h2>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-card">
                                    <div class="info-label">Nombres Completos</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['nombres']) ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Apellidos</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['apellidos']) ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Cédula de Identidad</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['cedula']) ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Fecha de Nacimiento</div>
                                    <div class="info-value">
                                        <?= $funcionario['fecha_nacimiento'] ? date('d/m/Y', strtotime($funcionario['fecha_nacimiento'])) . " ($edad)" : 'No registrada' ?>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Género</div>
                                    <div class="info-value"><?= $funcionario['genero'] == 'M' ? 'Masculino' : ($funcionario['genero'] == 'F' ? 'Femenino' : 'Otro') ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Teléfono</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['telefono'] ?? 'No registrado') ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['email'] ?? 'No registrado') ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['direccion'] ?? 'No registrada') ?></div>
                                </div>
                            </div>
                            
                            <div class="section-header" style="margin-top: 40px;">
                                <?= Icon::get('briefcase', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                <h2>Información Laboral</h2>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-card">
                                    <div class="info-label">Cargo</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['nombre_cargo']) ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Departamento</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['departamento']) ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Fecha de Ingreso</div>
                                    <div class="info-value">
                                        <?= date('d/m/Y', strtotime($funcionario['fecha_ingreso'])) . " ($antiguedad)" ?>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Nivel Educativo</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['nivel_educativo'] ?? 'No registrado') ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Título Obtenido</div>
                                    <div class="info-value"><?= htmlspecialchars($funcionario['titulo_obtenido'] ?? 'No registrado') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-cargas" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header" style="justify-content: space-between; border-bottom: none;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?= Icon::get('users', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                    <h2>Cargas Familiares</h2>
                                </div>
                                <?php if ($puede_editar): ?>
                                    <button class="btn-primary" onclick="abrirModalCarga()">
                                        <?= Icon::get('plus') ?> Agregar
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div id="cargas-container">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><?= Icon::get('users', 'opacity: 0.3; width: 48px; height: 48px;') ?></div>
                                    <div class="empty-state-text">Cargando información...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-nombramientos" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('file-text', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                <h2>Historial de Nombramientos</h2>
                            </div>
                            <div id="nombramientos-container">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><?= Icon::get('file-text', 'opacity: 0.3; width: 48px; height: 48px;') ?></div>
                                    <div class="empty-state-text">No hay nombramientos registrados</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-vacaciones" class="tab-content">
                    <div class="card-modern" style="background: linear-gradient(135deg, #0F4C81 0%, #118AB2 100%); color: white; margin-bottom: 24px;">
                        <div class="card-body">
                            <h3 style="margin: 0 0 16px 0; font-size: 16px; opacity: 0.9;">Días Pendientes</h3>
                            <div style="font-size: 48px; font-weight: 800; line-height: 1;" id="vacaciones-pendientes">--</div>
                            <div id="vacaciones-detalle" style="margin-top: 8px; font-size: 14px; opacity: 0.8;">Calculando...</div>
                        </div>
                    </div>
                    
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('sun', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                <h2>Historial de Vacaciones</h2>
                            </div>
                            <div id="vacaciones-historial-container">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><?= Icon::get('sun', 'opacity: 0.3; width: 48px; height: 48px;') ?></div>
                                    <div class="empty-state-text">No hay vacaciones registradas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-riesgo" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('activity', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                <h2>Nivel de Riesgo</h2>
                            </div>
                            
                            <div style="margin: 32px 0;">
                                <div style="width: 100%; height: 40px; background: #e2e8f0; border-radius: 20px; overflow: hidden; position: relative;">
                                    <div id="risk-fill" style="width: 0%; height: 100%; background: #10b981; transition: width 1s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                                        <span id="risk-text">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                            <div id="risk-mensaje"></div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-amonestaciones" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('alert-circle', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                <h2>Historial de Amonestaciones</h2>
                            </div>
                            <div id="amonestaciones-container">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><?= Icon::get('alert-circle', 'opacity: 0.3; width: 48px; height: 48px;') ?></div>
                                    <div class="empty-state-text">No hay amonestaciones registradas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-salidas" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header" style="justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?= Icon::get('log-out', 'width: 24px; height: 24px; color: var(--color-primary);') ?>
                                    <h2>Retiros y Despidos</h2>
                                </div>
                                <?php if ($funcionario['estado'] === 'activo'): ?>
                                    <button class="btn-primary" style="background: var(--color-danger);" onclick="procesarSalidaDesdePerfil()">
                                        <?= Icon::get('slash') ?> Procesar Salida
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div id="salidas-container">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><?= Icon::get('log-out', 'opacity: 0.3; width: 48px; height: 48px;') ?></div>
                                    <div class="empty-state-text">No hay registros de salidas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </main>
        </div>
    </div>
    
    <div id="modalCarga" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Agregar Carga Familiar</h3>
            <form id="formCarga" onsubmit="guardarCarga(event)">
                <input type="hidden" id="carga_id" name="id">
                <input type="hidden" name="funcionario_id" value="<?= $id ?>">
                
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
                    <button type="button" class="btn-secondary" onclick="cerrarModalCarga()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const funcionarioId = <?= $id ?>;
        const currentYear = new Date().getFullYear();

        // Función para cambiar de pestaña (Blindada para usar onclick en línea)
        function showTab(tabName, element) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
                tab.classList.remove('active');
            });
            
            // Desactivar todos los botones del menú
            document.querySelectorAll('.profile-nav-link').forEach(item => {
                item.classList.remove('active');
                item.style.background = 'transparent';
                item.style.color = '#64748B';
            });
            
            // Mostrar contenido seleccionado
            const activeTab = document.getElementById(tabName);
            if(activeTab) {
                activeTab.style.display = 'block';
                activeTab.classList.add('active');
            }
            
            // Activar botón visualmente
            if(element) {
                element.classList.add('active');
                element.style.background = '#E0F2FE';
                element.style.color = '#0F4C81';
            }
            
            // Cargar datos específicos si es necesario
            const realTabName = tabName.replace('tab-', '');
            switch(realTabName) {
                case 'cargas': if(typeof cargarCargasFamiliares === 'function') cargarCargasFamiliares(); break;
                case 'nombramientos': if(typeof cargarNombramientos === 'function') cargarNombramientos(); break;
                case 'vacaciones': 
                    if(typeof calcularVacaciones === 'function') calcularVacaciones(); 
                    if(typeof cargarVacaciones === 'function') cargarVacaciones();
                    break;
                case 'riesgo': if(typeof cargarBarraRiesgo === 'function') cargarBarraRiesgo(); break;
                case 'amonestaciones': if(typeof cargarAmonestaciones === 'function') cargarAmonestaciones(); break;
                case 'salidas': if(typeof cargarSalidas === 'function') cargarSalidas(); break;
            }
        }
        
        // Modal Functions
        function abrirModalCarga() {
            document.getElementById('modalCarga').classList.add('active');
            document.getElementById('formCarga').reset();
        }
        
        function cerrarModalCarga() {
            document.getElementById('modalCarga').classList.remove('active');
        }
        
        // Data Loading Functions
        function cargarCargasFamiliares() {
            fetch(`ajax/get_cargas_familiares.php?funcionario_id=${funcionarioId}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('cargas-container');
                if (data.success && data.cargas.length > 0) {
                    let html = '<table class="table-modern"><thead><tr><th>Nombre</th><th>Parentesco</th><th>Edad</th></tr></thead><tbody>';
                    data.cargas.forEach(c => {
                        html += `<tr>
                            <td>${c.nombres} ${c.apellidos}</td>
                            <td><span class="badge badge-info">${c.parentesco}</span></td>
                            <td>${c.edad} años</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                }
            });
        }
        
        function cargarNombramientos() {
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=NOMBRAMIENTO`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('nombramientos-container');
                if (data.success && data.total > 0) {
                    let html = '<table class="table-modern"><thead><tr><th>Fecha</th><th>Detalles</th><th>PDF</th></tr></thead><tbody>';
                    data.data.forEach(item => {
                        const detalles = item.detalles || {};
                        html += `<tr>
                            <td>${item.fecha_evento_formateada}</td>
                            <td>${detalles.cargo || '-'} <br> <small>${detalles.departamento || ''}</small></td>
                            <td>${item.tiene_archivo ? `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn-icon"><?= Icon::get('download') ?></a>` : '-'}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                }
            });
        }
        
        function cargarAmonestaciones() {
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=AMONESTACION`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('amonestaciones-container');
                if (data.success && data.total > 0) {
                    let html = '<table class="table-modern"><thead><tr><th>Fecha</th><th>Gravedad</th><th>Motivo</th><th>PDF</th></tr></thead><tbody>';
                    data.data.forEach(item => {
                        html += `<tr>
                            <td>${item.fecha_evento_formateada}</td>
                            <td><span class="badge badge-warning">Amonestación</span></td>
                            <td>${item.observaciones || ''}</td>
                            <td>${item.tiene_archivo ? `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn-icon"><?= Icon::get('download') ?></a>` : '-'}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                }
            });
        }
        
        function calcularVacaciones() {
            fetch(`ajax/calcular_vacaciones.php?funcionario_id=${funcionarioId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const vac = data.data.vacaciones;
                    document.getElementById('vacaciones-pendientes').textContent = vac.tiene_derecho ? vac.dias_pendientes : '-';
                    document.getElementById('vacaciones-detalle').textContent = vac.tiene_derecho ? 
                        `${vac.dias_correspondientes} correspondientes - ${vac.dias_tomados} usados` : 'No califica';
                }
            });
        }
        
        function cargarVacaciones() {
             fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=VACACION`)
             .then(res => res.json())
             .then(data => {
                const container = document.getElementById('vacaciones-historial-container');
                if (data.success && data.total > 0) {
                    let html = '<table class="table-modern"><thead><tr><th>Fecha</th><th>Días</th><th>Observaciones</th></tr></thead><tbody>';
                      data.data.forEach(item => {
                        const detalles = item.detalles || {};
                        html += `<tr>
                            <td>${item.fecha_evento_formateada}</td>
                            <td>${detalles.dias_habiles} días</td>
                            <td>${detalles.observaciones}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                }
             });
        }
        
        function cargarBarraRiesgo() {
            fetch(`ajax/contar_amonestaciones.php?funcionario_id=${funcionarioId}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const pct = Math.min((data.total / 5) * 100, 100);
                    const fill = document.getElementById('risk-fill');
                    fill.style.width = pct + '%';
                    fill.textContent = `${data.total} Amonestaciones`;
                    if(data.total == 0) fill.style.background = '#10b981';
                    else if(data.total < 3) fill.style.background = '#f59e0b';
                    else fill.style.background = '#ef4444'; 
                }
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('modalCarga')) {
                cerrarModalCarga();
            }
        }
    </script>
</body>
</html>