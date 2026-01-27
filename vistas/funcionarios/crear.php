<?php
/**
 * Vista: Crear Funcionario
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

// Verificar sesión y permisos (solo Nivel 1 y 2)
verificarSesion();
if (!verificarNivel(2)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Generar token CSRF
$csrfToken = generarTokenCSRF();

$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VALIDACIÓN CSRF - CRÍTICO PARA SEGURIDAD
    if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
        die('Error de seguridad: Token CSRF inválido. Por favor, recargue la página e intente nuevamente.');
    }
    
    $datos = [
        'cedula' => limpiar($_POST['cedula']),
        'nombres' => limpiar($_POST['nombres']),
        'apellidos' => limpiar($_POST['apellidos']),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
        'genero' => $_POST['genero'] ?? null,
        'telefono' => limpiar($_POST['telefono'] ?? ''),
        'email' => limpiar($_POST['email'] ?? ''),
        'direccion' => limpiar($_POST['direccion'] ?? ''),
        'cargo_id' => $_POST['cargo_id'],
        'departamento_id' => $_POST['departamento_id'],
        'fecha_ingreso' => $_POST['fecha_ingreso'],
        'estado' => $_POST['estado'] ?? 'activo'
    ];
    
    // Validaciones
    if (empty($datos['cedula']) || empty($datos['nombres']) || empty($datos['apellidos'])) {
        $error = 'Los campos cédula, nombres y apellidos son obligatorios';
    } elseif (empty($datos['cargo_id']) || empty($datos['departamento_id']) || empty($datos['fecha_ingreso'])) {
        $error = 'Debe seleccionar cargo, departamento y fecha de ingreso';
    } else {
        $modeloFuncionario = new Funcionario();
        
        // Verificar si la cédula ya existe
        $funcionarioExistente = $modeloFuncionario->obtenerPorCedula($datos['cedula']);
        
        if ($funcionarioExistente) {
            // Si existe y está INACTIVO, reactivar (reingreso)
            if ($funcionarioExistente['estado'] === 'inactivo') {
                $reactivado = $modeloFuncionario->reactivarFuncionario(
                    $funcionarioExistente['id'], 
                    $datos
                );
                
                if ($reactivado) {
                    registrarAuditoria(
                        'REACTIVAR_FUNCIONARIO', 
                        'funcionarios', 
                        $funcionarioExistente['id'], 
                        ['estado_anterior' => 'inactivo'], 
                        [
                            'estado_nuevo' => 'activo', 
                            'cargo_id' => $datos['cargo_id'],
                            'departamento_id' => $datos['departamento_id'],
                            'fecha_ingreso' => $datos['fecha_ingreso']
                        ]
                    );
                    
                    $_SESSION['success'] = 'Funcionario reactivado exitosamente (Reingreso al sistema)';
                    header('Location: ver.php?id=' . $funcionarioExistente['id']);
                    exit;
                } else {
                    $error = 'Error al reactivar el funcionario';
                }
            } else {
                // Si está activo, mostrar error
                $error = 'Ya existe un funcionario activo con esa cédula';
            }
        } else {
            // No existe, crear nuevo funcionario
            $id = $modeloFuncionario->crear($datos);
            
            if ($id) {
                // Generar username desde la cédula
                $username = strtolower(str_replace(['-', ' '], '', $datos['cedula']));
                
                registrarAuditoria('CREAR_FUNCIONARIO', 'funcionarios', $id, null, $datos);
                $_SESSION['success'] = 'Funcionario creado exitosamente';
                $_SESSION['username_generado'] = $username;
                $_SESSION['cedula_empleado'] = $datos['cedula'];
                header('Location: ver.php?id=' . $id);
                exit;
            } else {
                $error = 'Error al crear el funcionario';
            }
        }
    }
}

// Obtener departamentos y cargos
$db = getDB();
$departamentos = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
$cargos = $db->query("SELECT * FROM cargos ORDER BY nivel_acceso, nombre_cargo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Funcionario - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .form-grid-full {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text);
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: var(--color-danger);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            border: 2px solid var(--color-border);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(0, 168, 204, 0.1);
        }
        
        select.form-control {
            cursor: pointer;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--color-border-light);
        }
        
        @media (max-width: 768px) {
            .form-grid {
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
                <h1 class="page-title">Nuevo Funcionario</h1>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                    ← Volver al Listado
                </a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Datos del Funcionario</h2>
                    <p class="card-subtitle">Complete el formulario con la información del nuevo funcionario</p>
                </div>
                
                <div style="padding: 32px;">
                    <?php if ($error): ?>
                        <div class="alert alert-error" style="margin-bottom: 24px;">
                            <span>⚠️</span>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <!-- Token CSRF para protección contra ataques -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="form-grid">
                            <!-- Cédula -->
                            <div class="form-group">
                                <label for="cedula">Cédula <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="cedula" 
                                    name="cedula" 
                                    class="form-control"
                                    placeholder="V-12345678"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['cedula'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Fecha de Nacimiento -->
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                <input 
                                    type="date" 
                                    id="fecha_nacimiento" 
                                    name="fecha_nacimiento" 
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Nombres -->
                            <div class="form-group">
                                <label for="nombres">Nombres <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="nombres" 
                                    name="nombres" 
                                    class="form-control"
                                    placeholder="Juan Carlos"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['nombres'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Apellidos -->
                            <div class="form-group">
                                <label for="apellidos">Apellidos <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="apellidos" 
                                    name="apellidos" 
                                    class="form-control"
                                    placeholder="Pérez González"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Género -->
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select id="genero" name="genero" class="form-control">
                                    <option value="">Seleccione...</option>
                                    <option value="M" <?php echo ($_POST['genero'] ?? '') == 'M' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo ($_POST['genero'] ?? '') == 'F' ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?php echo ($_POST['genero'] ?? '') == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input 
                                    type="tel" 
                                    id="telefono" 
                                    name="telefono" 
                                    class="form-control"
                                    placeholder="0412-1234567"
                                    value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-control"
                                    placeholder="correo@ispeb.gob.ve"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Cargo -->
                            <div class="form-group">
                                <label for="cargo_id">Cargo <span class="required">*</span></label>
                                <select id="cargo_id" name="cargo_id" class="form-control" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?php echo $cargo['id']; ?>" <?php echo ($_POST['cargo_id'] ?? '') == $cargo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Departamento -->
                            <div class="form-group">
                                <label for="departamento_id">Departamento <span class="required">*</span></label>
                                <select id="departamento_id" name="departamento_id" class="form-control" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($departamentos as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo ($_POST['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Fecha de Ingreso -->
                            <div class="form-group">
                                <label for="fecha_ingreso">Fecha de Ingreso <span class="required">*</span></label>
                                <input 
                                    type="date" 
                                    id="fecha_ingreso" 
                                    name="fecha_ingreso" 
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? ''); ?>"
                                >
                            </div>
                            
                            <!-- Estado -->
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" class="form-control">
                                    <option value="activo" <?php echo ($_POST['estado'] ?? 'activo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="vacaciones" <?php echo ($_POST['estado'] ?? '') == 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                    <option value="reposo" <?php echo ($_POST['estado'] ?? '') == 'reposo' ? 'selected' : ''; ?>>Reposo</option>
                                </select>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="form-group form-grid-full">
                                <label for="direccion">Dirección</label>
                                <textarea 
                                    id="direccion" 
                                    name="direccion" 
                                    class="form-control"
                                    placeholder="Dirección completa del funcionario"
                                ><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Guardar Funcionario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- UX Mejoras: Feedback automático de formularios -->
    <script src="<?php echo APP_URL; ?>/publico/js/ux-mejoras.js"></script>
</body>
</html>
