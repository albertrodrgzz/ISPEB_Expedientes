<?php
/**
 * Perfil del Usuario
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

$db = getDB();

// Obtener datos completos del usuario
$stmt = $db->prepare("
    SELECT 
        u.id as usuario_id,
        u.username,
        u.estado as estado_usuario,
        u.ultimo_acceso,
        u.created_at as fecha_creacion_usuario,
        f.id as funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        f.fecha_nacimiento,
        f.genero,
        f.telefono,
        f.email,
        f.direccion,
        f.nivel_educativo,
        f.titulo_obtenido,
        f.fecha_ingreso,
        f.foto,
        f.estado as estado_funcionario,
        c.nombre_cargo,
        c.nivel_acceso,
        d.nombre as departamento
    FROM usuarios u
    INNER JOIN funcionarios f ON u.funcionario_id = f.id
    LEFT JOIN cargos c ON f.cargo_id = c.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ' . APP_URL . '/config/logout.php');
    exit;
}

// Calcular años de servicio
$fecha_ingreso = new DateTime($usuario['fecha_ingreso']);
$hoy = new DateTime();
$anos_servicio = $hoy->diff($fecha_ingreso)->y;
$meses_servicio = $hoy->diff($fecha_ingreso)->m;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Header del Perfil con Avatar Grande -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 40px; margin-bottom: 32px; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);">
                <div style="display: flex; align-items: center; gap: 32px;">
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: white; color: #667eea; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 48px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                        <?php echo strtoupper(substr($usuario['nombres'], 0, 1) . substr($usuario['apellidos'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1; color: white;">
                        <h1 style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700;">
                            <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </h1>
                        <p style="margin: 0 0 16px 0; font-size: 18px; opacity: 0.9;">
                            <?php echo htmlspecialchars($usuario['nombre_cargo']); ?>
                        </p>
                        <div style="display: flex; gap: 24px; font-size: 14px; opacity: 0.85;">
                            <div>
                                <span style="opacity: 0.7;">📧</span> <?php echo htmlspecialchars($usuario['email'] ?? 'No registrado'); ?>
                            </div>
                            <div>
                                <span style="opacity: 0.7;">📞</span> <?php echo htmlspecialchars($usuario['telefono'] ?? 'No registrado'); ?>
                            </div>
                            <div>
                                <span style="opacity: 0.7;">🏢</span> <?php echo htmlspecialchars($usuario['departamento']); ?>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: center; background: rgba(255,255,255,0.15); padding: 20px 32px; border-radius: 12px; backdrop-filter: blur(10px);">
                        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px;">
                            <?php echo $anos_servicio; ?>
                        </div>
                        <div style="font-size: 14px; opacity: 0.9;">
                            Años de Servicio
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Información -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px;">
                <!-- Información Personal -->
                <div class="card" style="background: linear-gradient(to bottom right, #ffffff, #f8fafc);">
                    <div class="card-header" style="border-bottom: 2px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                👤
                            </div>
                            <h3 class="card-title" style="margin: 0;">Información Personal</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-grid">
                            <div class="profile-info-item">
                                <div class="profile-info-label">Cédula de Identidad</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['cedula']); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Nombres</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['nombres']); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Apellidos</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['apellidos']); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Fecha de Nacimiento</div>
                                <div class="profile-info-value">
                                    <?php 
                                    if ($usuario['fecha_nacimiento']) {
                                        $fecha_nac = new DateTime($usuario['fecha_nacimiento']);
                                        $edad = $hoy->diff($fecha_nac)->y;
                                        echo date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) . " <span style='color: #718096;'>($edad años)</span>";
                                    } else {
                                        echo '<span style="color: #cbd5e0;">No registrado</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Género</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['genero'] ?? 'No especificado'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información Laboral -->
                <div class="card" style="background: linear-gradient(to bottom right, #ffffff, #f8fafc);">
                    <div class="card-header" style="border-bottom: 2px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                💼
                            </div>
                            <h3 class="card-title" style="margin: 0;">Información Laboral</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-grid">
                            <div class="profile-info-item">
                                <div class="profile-info-label">Cargo</div>
                                <div class="profile-info-value" style="font-weight: 600; color: #8b5cf6;">
                                    <?php echo htmlspecialchars($usuario['nombre_cargo']); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Departamento</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['departamento']); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Fecha de Ingreso</div>
                                <div class="profile-info-value">
                                    <?php echo date('d/m/Y', strtotime($usuario['fecha_ingreso'])); ?>
                                    <span style="color: #718096;">(<?php echo $anos_servicio; ?> años, <?php echo $meses_servicio; ?> meses)</span>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Nivel Educativo</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['nivel_educativo'] ?? 'No especificado'); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Título Obtenido</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($usuario['titulo_obtenido'] ?? 'No especificado'); ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Estado</div>
                                <div>
                                    <span class="badge badge-<?php echo $usuario['estado_funcionario'] === 'activo' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($usuario['estado_funcionario']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de Cuenta -->
                <div class="card" style="background: linear-gradient(to bottom right, #ffffff, #f8fafc);">
                    <div class="card-header" style="border-bottom: 2px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                🔐
                            </div>
                            <h3 class="card-title" style="margin: 0;">Cuenta de Usuario</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-grid">
                            <div class="profile-info-item">
                                <div class="profile-info-label">Nombre de Usuario</div>
                                <div class="profile-info-value" style="font-family: 'Courier New', monospace; background: #f7fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 600; color: #10b981;">
                                    <?php echo htmlspecialchars($usuario['username']); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Nivel de Acceso</div>
                                <div>
                                    <span class="badge badge-primary" style="font-size: 14px; padding: 6px 12px;">
                                        Nivel <?php echo $usuario['nivel_acceso']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Estado de Cuenta</div>
                                <div>
                                    <span class="badge badge-<?php echo $usuario['estado_usuario'] === 'activo' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($usuario['estado_usuario']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Último Acceso</div>
                                <div class="profile-info-value" style="font-size: 13px;">
                                    <?php 
                                    if ($usuario['ultimo_acceso']) {
                                        echo date('d/m/Y H:i:s', strtotime($usuario['ultimo_acceso']));
                                    } else {
                                        echo '<span style="color: #cbd5e0;">Primer acceso</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Cuenta Creada</div>
                                <div class="profile-info-value"><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion_usuario'])); ?></div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #e2e8f0;">
                            <button onclick="cambiarContrasena()" class="btn btn-primary" style="width: 100%; background: linear-gradient(135deg, #10b981, #059669); border: none; padding: 12px; font-weight: 600;">
                                🔒 Cambiar Contraseña
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones Rápidas -->
            <div class="card" style="background: linear-gradient(135deg, #f8fafc, #ffffff);">
                <div class="card-header">
                    <h3 class="card-title">⚡ Acciones Rápidas</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <a href="<?php echo APP_URL; ?>/vistas/funcionarios/ver.php?id=<?php echo $usuario['funcionario_id']; ?>" class="quick-action-card">
                            <div class="quick-action-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">📄</div>
                            <div>
                                <div class="quick-action-title">Ver Mi Expediente</div>
                                <div class="quick-action-desc">Consulta tu expediente completo</div>
                            </div>
                        </a>
                        <a href="<?php echo APP_URL; ?>/vistas/dashboard/" class="quick-action-card">
                            <div class="quick-action-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">🏠</div>
                            <div>
                                <div class="quick-action-title">Ir al Dashboard</div>
                                <div class="quick-action-desc">Volver a la página principal</div>
                            </div>
                        </a>
                        <a href="<?php echo APP_URL; ?>/vistas/funcionarios/" class="quick-action-card">
                            <div class="quick-action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">👥</div>
                            <div>
                                <div class="quick-action-title">Ver Funcionarios</div>
                                <div class="quick-action-desc">Lista completa de personal</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .profile-info-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .profile-info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .profile-info-label {
        font-size: 12px;
        font-weight: 600;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .profile-info-value {
        font-size: 15px;
        color: #1a202c;
        font-weight: 500;
    }

    .quick-action-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .quick-action-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
    }

    .quick-action-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .quick-action-title {
        font-size: 16px;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 4px;
    }

    .quick-action-desc {
        font-size: 13px;
        color: #718096;
    }
    </style>

    <script src="../../publico/js/app.js"></script>
    <script src="../../publico/js/sweetalert-utils.js"></script>
    <script>
    function cambiarContrasena() {
        Swal.fire({
            title: 'Cambiar Contraseña',
            html: `
                <div style="text-align: left;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568;">Contraseña Actual</label>
                        <input type="password" id="current-password" class="swal2-input" placeholder="Ingrese su contraseña actual" style="width: 90%; margin: 0;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568;">Nueva Contraseña</label>
                        <input type="password" id="new-password" class="swal2-input" placeholder="Mínimo 6 caracteres" style="width: 90%; margin: 0;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568;">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirm-password" class="swal2-input" placeholder="Repita la nueva contraseña" style="width: 90%; margin: 0;">
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Cambiar Contraseña',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            preConfirm: () => {
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (!currentPassword || !newPassword || !confirmPassword) {
                    Swal.showValidationMessage('Todos los campos son obligatorios');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    Swal.showValidationMessage('La nueva contraseña debe tener al menos 6 caracteres');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    Swal.showValidationMessage('Las contraseñas no coinciden');
                    return false;
                }
                
                return { currentPassword, newPassword };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Aquí iría la llamada AJAX para cambiar la contraseña
                Swal.fire({
                    icon: 'info',
                    title: 'Funcionalidad Pendiente',
                    text: 'La funcionalidad de cambio de contraseña se implementará próximamente',
                    confirmButtonColor: '#10b981'
                });
            }
        });
    }
    </script>
</body>
</html>
