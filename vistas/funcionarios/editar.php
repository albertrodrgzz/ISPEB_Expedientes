<?php
/**
 * Vista: Editar Funcionario
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

// Verificar sesión
verificarSesion();

// Obtener ID
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Verificar permisos
if (!verificarDepartamento($id)) {
    $_SESSION['error'] = 'No tiene permisos para editar este funcionario';
    header('Location: index.php');
    exit;
}

$modeloFuncionario = new Funcionario();
$funcionario = $modeloFuncionario->obtenerPorId($id);

if (!$funcionario) {
    $_SESSION['error'] = 'Funcionario no encontrado';
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        'foto' => $funcionario['foto'],
        'estado' => $_POST['estado'] ?? 'activo'
    ];
    
    // Validaciones
    if (empty($datos['cedula']) || empty($datos['nombres']) || empty($datos['apellidos'])) {
        $error = 'Los campos cédula, nombres y apellidos son obligatorios';
    } elseif (empty($datos['cargo_id']) || empty($datos['departamento_id']) || empty($datos['fecha_ingreso'])) {
        $error = 'Debe seleccionar cargo, departamento y fecha de ingreso';
    } else {
        // Verificar si la cédula ya existe (excepto el actual)
        $existente = $modeloFuncionario->obtenerPorCedula($datos['cedula']);
        if ($existente && $existente['id'] != $id) {
            $error = 'Ya existe otro funcionario con esa cédula';
        } else {
            // Actualizar funcionario
            if ($modeloFuncionario->actualizar($id, $datos)) {
                registrarAuditoria('ACTUALIZAR_FUNCIONARIO', 'funcionarios', $id, $funcionario, $datos);
                $_SESSION['success'] = 'Funcionario actualizado exitosamente';
                header('Location: ver.php?id=' . $id);
                exit;
            } else {
                $error = 'Error al actualizar el funcionario';
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
    <title>Editar Funcionario - <?php echo APP_NAME; ?></title>
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
                <h1 class="page-title">Editar Funcionario</h1>
            </div>
            <div class="header-right">
                <a href="ver.php?id=<?php echo $id; ?>" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                    ← Volver al Expediente
                </a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Datos del Funcionario</h2>
                    <p class="card-subtitle">Editando: <?php echo htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?></p>
                </div>
                
                <div style="padding: 32px;">
                    <?php if ($error): ?>
                        <div class="alert alert-error" style="margin-bottom: 24px;">
                            <span>⚠️</span>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-grid">
                            <!-- Cédula -->
                            <div class="form-group">
                                <label for="cedula">Cédula <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="cedula" 
                                    name="cedula" 
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['cedula'] ?? $funcionario['cedula']); ?>"
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
                                    value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? $funcionario['fecha_nacimiento']); ?>"
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
                                    required
                                    value="<?php echo htmlspecialchars($_POST['nombres'] ?? $funcionario['nombres']); ?>"
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
                                    required
                                    value="<?php echo htmlspecialchars($_POST['apellidos'] ?? $funcionario['apellidos']); ?>"
                                >
                            </div>
                            
                            <!-- Género -->
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select id="genero" name="genero" class="form-control">
                                    <option value="">Seleccione...</option>
                                    <option value="M" <?php echo ($_POST['genero'] ?? $funcionario['genero']) == 'M' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo ($_POST['genero'] ?? $funcionario['genero']) == 'F' ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?php echo ($_POST['genero'] ?? $funcionario['genero']) == 'Otro' ? 'selected' : ''; ?>>Otro</option>
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
                                    value="<?php echo htmlspecialchars($_POST['telefono'] ?? $funcionario['telefono']); ?>"
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
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? $funcionario['email']); ?>"
                                >
                            </div>
                            
                            <!-- Cargo -->
                            <div class="form-group">
                                <label for="cargo_id">Cargo <span class="required">*</span></label>
                                <select id="cargo_id" name="cargo_id" class="form-control" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?php echo $cargo['id']; ?>" <?php echo ($_POST['cargo_id'] ?? $funcionario['cargo_id']) == $cargo['id'] ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $dept['id']; ?>" <?php echo ($_POST['departamento_id'] ?? $funcionario['departamento_id']) == $dept['id'] ? 'selected' : ''; ?>>
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
                                    value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? $funcionario['fecha_ingreso']); ?>"
                                >
                            </div>
                            
                            <!-- Estado -->
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" class="form-control">
                                    <option value="activo" <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="vacaciones" <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                    <option value="reposo" <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'reposo' ? 'selected' : ''; ?>>Reposo</option>
                                    <?php if (puedeEliminar()): ?>
                                        <option value="inactivo" <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="form-group form-grid-full">
                                <label for="direccion">Dirección</label>
                                <textarea 
                                    id="direccion" 
                                    name="direccion" 
                                    class="form-control"
                                ><?php echo htmlspecialchars($_POST['direccion'] ?? $funcionario['direccion']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="ver.php?id=<?php echo $id; ?>" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
