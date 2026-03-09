<?php
/**
 * Vista: Editar Funcionario
 * Con validaciones robustas: formato cédula, duplicados (cédula/teléfono/email)
 * excluyendo el propio registro, y validación en tiempo real con AJAX.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

verificarSesion();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

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

$errores = [];
$csrfToken = generarTokenCSRF();

// ─── Helpers de validación ───────────────────────────────────────────────────
function validarCedulaVenezolana(string $cedula): bool {
    return (bool) preg_match('/^[VEve]-?\d{6,8}$/', trim($cedula));
}
function validarTelefonoVenezolano(string $tel): bool {
    $tel = preg_replace('/[\s\-()]/', '', $tel);
    return (bool) preg_match('/^(\+58|0058|0)?4(1[246]|2[246])\d{7}$/', $tel);
}
// ─────────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
        die('Error de seguridad: Token CSRF inválido. Recargue la página.');
    }

    $datos = [
        'cedula'           => strtoupper(trim(limpiar($_POST['cedula']))),
        'nombres'          => trim(limpiar($_POST['nombres'])),
        'apellidos'        => trim(limpiar($_POST['apellidos'])),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
        'genero'           => $_POST['genero'] ?? null,
        'telefono'         => trim(limpiar($_POST['telefono'] ?? '')),
        'email'            => strtolower(trim(limpiar($_POST['email'] ?? ''))),
        'direccion'        => trim(limpiar($_POST['direccion'] ?? '')),
        'cargo_id'         => $_POST['cargo_id']         ?? '',
        'departamento_id'  => $_POST['departamento_id']  ?? '',
        'fecha_ingreso'    => $_POST['fecha_ingreso']    ?? '',
        'foto'             => $funcionario['foto'],
        'estado'           => $_POST['estado'] ?? 'activo',
        'nivel_educativo'  => $_POST['nivel_educativo'] ?? null,
        'titulo_obtenido'  => trim(limpiar($_POST['titulo_obtenido'] ?? ''))
    ];

    // ── Validaciones de formato ───────────────────────────────────────────────
    if (empty($datos['cedula'])) {
        $errores['cedula'] = 'La cédula es obligatoria.';
    } elseif (!validarCedulaVenezolana($datos['cedula'])) {
        $errores['cedula'] = 'Formato inválido. Ejemplo: V-12345678 o E-1234567.';
    }

    if (empty($datos['nombres'])) {
        $errores['nombres'] = 'El nombre es obligatorio.';
    } elseif (!preg_match('/^[\p{L}\s\'\-\.]+$/u', $datos['nombres'])) {
        $errores['nombres'] = 'El nombre solo puede contener letras, espacios y guiones.';
    }

    if (empty($datos['apellidos'])) {
        $errores['apellidos'] = 'El apellido es obligatorio.';
    } elseif (!preg_match('/^[\p{L}\s\'\-\.]+$/u', $datos['apellidos'])) {
        $errores['apellidos'] = 'El apellido solo puede contener letras, espacios y guiones.';
    }

    if (!empty($datos['telefono']) && !validarTelefonoVenezolano($datos['telefono'])) {
        $errores['telefono'] = 'Formato de teléfono inválido. Ejemplo: 0412-1234567.';
    }

    if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El correo electrónico no tiene un formato válido.';
    }

    if (empty($datos['cargo_id'])) {
        $errores['cargo_id'] = 'Debe seleccionar un cargo.';
    }
    if (empty($datos['departamento_id'])) {
        $errores['departamento_id'] = 'Debe seleccionar un departamento.';
    }
    if (empty($datos['fecha_ingreso'])) {
        $errores['fecha_ingreso'] = 'La fecha de ingreso es obligatoria.';
    } elseif ($datos['fecha_ingreso'] > date('Y-m-d')) {
        $errores['fecha_ingreso'] = 'La fecha de ingreso no puede ser futura.';
    }

    if (!empty($datos['fecha_nacimiento'])) {
        $edad = (int) date_diff(
            date_create($datos['fecha_nacimiento']),
            date_create('today')
        )->y;
        if ($edad < 18) {
            $errores['fecha_nacimiento'] = 'El funcionario debe ser mayor de 18 años.';
        }
    }

    // ── Validaciones de duplicados (excluyendo este funcionario) ─────────────
    if (empty($errores)) {
        $duplicados = $modeloFuncionario->verificarDuplicados($datos, $id);
        foreach ($duplicados as $campo => $msg) {
            $errores[$campo] = $msg;
        }
    }

    // ── Guardar si no hay errores ─────────────────────────────────────────────
    if (empty($errores)) {
        if ($modeloFuncionario->actualizar($id, $datos)) {
            registrarAuditoria('ACTUALIZAR_FUNCIONARIO', 'funcionarios', $id, $funcionario, $datos);
            $_SESSION['success'] = '✅ Funcionario actualizado exitosamente.';
            header('Location: ver.php?id=' . $id);
            exit;
        }
        $errores['general'] = 'Error interno al guardar los cambios. Intente de nuevo.';
    }
}

$db           = getDB();
$departamentos = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
$cargos        = $db->query("SELECT * FROM cargos ORDER BY nivel_acceso, nombre_cargo")->fetchAll();

function fieldClass(string $campo, array $errores): string {
    return isset($errores[$campo]) ? 'form-control is-invalid' : 'form-control';
}

// Valor actual del campo (POST tiene prioridad para repintar errores)
function val(string $campo, array $funcionario): string {
    return htmlspecialchars($_POST[$campo] ?? $funcionario[$campo] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Funcionario - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    <style>
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .form-grid-full { grid-column: 1 / -1; }
        .form-group { margin-bottom: 0; position: relative; }
        .form-group label { display: block; font-size: 14px; font-weight: 500; color: var(--color-text); margin-bottom: 8px; }
        .form-group label .required { color: var(--color-danger); }
        .form-control {
            width: 100%; padding: 12px 16px; font-size: 15px;
            border: 2px solid var(--color-border); border-radius: var(--radius-md);
            transition: all 0.2s ease; font-family: inherit; box-sizing: border-box;
        }
        .form-control:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(0,168,204,0.12); }
        .form-control.is-invalid  { border-color: var(--color-danger); }
        .form-control.is-valid    { border-color: #38a169; }
        .form-control.is-checking { border-color: #d69e2e; }
        select.form-control  { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .field-feedback { font-size: 12.5px; margin-top: 5px; min-height: 18px; display: flex; align-items: center; gap: 5px; }
        .field-feedback.error { color: var(--color-danger); }
        .field-feedback.ok    { color: #38a169; }
        .field-feedback.check { color: #d69e2e; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--color-border-light); }
        .alert-error-list { background: #fff5f5; border: 1px solid #fed7d7; border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 24px; color: #c53030; }
        .alert-error-list ul { margin: 8px 0 0 18px; }
        .alert-error-list li { margin-bottom: 4px; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
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
                <a href="ver.php?id=<?php echo $id; ?>" class="btn" style="background:#e2e8f0;color:#2d3748;text-decoration:none;">
                    ← Volver al Expediente
                </a>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Datos del Funcionario</h2>
                    <p class="card-subtitle">Editando: <strong><?php echo htmlspecialchars($funcionario['nombres'] . ' ' . $funcionario['apellidos']); ?></strong></p>
                </div>

                <div style="padding: 32px;">
                    <?php if (!empty($errores)): ?>
                        <div class="alert-error-list">
                            <strong>⚠️ Por favor corrija los siguientes errores:</strong>
                            <ul>
                                <?php foreach ($errores as $msg): ?>
                                    <li><?php echo $msg; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="formEditar" method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-grid">

                            <!-- Cédula -->
                            <div class="form-group">
                                <label for="cedula">Cédula <span class="required">*</span></label>
                                <input type="text" id="cedula" name="cedula"
                                    class="<?php echo fieldClass('cedula',$errores); ?>"
                                    value="<?php echo val('cedula', $funcionario); ?>"
                                    autocomplete="off" required>
                                <div class="field-feedback <?php echo isset($errores['cedula']) ? 'error' : ''; ?>" id="fb-cedula">
                                    <?php if (isset($errores['cedula'])) echo '❌ ' . $errores['cedula']; ?>
                                </div>
                            </div>

                            <!-- Fecha de Nacimiento -->
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                    class="<?php echo fieldClass('fecha_nacimiento',$errores); ?>"
                                    max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                    value="<?php echo val('fecha_nacimiento', $funcionario); ?>">
                                <div class="field-feedback <?php echo isset($errores['fecha_nacimiento']) ? 'error' : ''; ?>" id="fb-fecha_nacimiento">
                                    <?php if (isset($errores['fecha_nacimiento'])) echo '❌ ' . $errores['fecha_nacimiento']; ?>
                                </div>
                            </div>

                            <!-- Nombres -->
                            <div class="form-group">
                                <label for="nombres">Nombres <span class="required">*</span></label>
                                <input type="text" id="nombres" name="nombres"
                                    class="<?php echo fieldClass('nombres',$errores); ?>"
                                    value="<?php echo val('nombres', $funcionario); ?>" required>
                                <div class="field-feedback <?php echo isset($errores['nombres']) ? 'error' : ''; ?>" id="fb-nombres">
                                    <?php if (isset($errores['nombres'])) echo '❌ ' . $errores['nombres']; ?>
                                </div>
                            </div>

                            <!-- Apellidos -->
                            <div class="form-group">
                                <label for="apellidos">Apellidos <span class="required">*</span></label>
                                <input type="text" id="apellidos" name="apellidos"
                                    class="<?php echo fieldClass('apellidos',$errores); ?>"
                                    value="<?php echo val('apellidos', $funcionario); ?>" required>
                                <div class="field-feedback <?php echo isset($errores['apellidos']) ? 'error' : ''; ?>" id="fb-apellidos">
                                    <?php if (isset($errores['apellidos'])) echo '❌ ' . $errores['apellidos']; ?>
                                </div>
                            </div>

                            <!-- Género -->
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <select id="genero" name="genero" class="form-control">
                                    <option value="">Seleccione...</option>
                                    <option value="M"    <?php echo ($_POST['genero'] ?? $funcionario['genero']) == 'M'    ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F"    <?php echo ($_POST['genero'] ?? $funcionario['genero']) == 'F'    ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?php echo ($_POST['genero'] ?? $funcionario['genero']) == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                                <div class="field-feedback" id="fb-genero"></div>
                            </div>

                            <!-- Teléfono -->
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono"
                                    class="<?php echo fieldClass('telefono',$errores); ?>"
                                    placeholder="0412-1234567"
                                    value="<?php echo val('telefono', $funcionario); ?>">
                                <div class="field-feedback <?php echo isset($errores['telefono']) ? 'error' : ''; ?>" id="fb-telefono">
                                    <?php if (isset($errores['telefono'])) echo '❌ ' . $errores['telefono']; ?>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" id="email" name="email"
                                    class="<?php echo fieldClass('email',$errores); ?>"
                                    value="<?php echo val('email', $funcionario); ?>">
                                <div class="field-feedback <?php echo isset($errores['email']) ? 'error' : ''; ?>" id="fb-email">
                                    <?php if (isset($errores['email'])) echo '❌ ' . $errores['email']; ?>
                                </div>
                            </div>

                            <!-- Nivel Educativo -->
                            <div class="form-group">
                                <label for="nivel_educativo">Nivel Educativo</label>
                                <select id="nivel_educativo" name="nivel_educativo" class="form-control">
                                    <option value="">Seleccione...</option>
                                    <option value="Primaria" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'Primaria' ? 'selected' : ''; ?>>Primaria</option>
                                    <option value="Bachiller" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'Bachiller' ? 'selected' : ''; ?>>Bachiller</option>
                                    <option value="TSU" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'TSU' ? 'selected' : ''; ?>>Técnico Superior (TSU)</option>
                                    <option value="Universitario" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'Universitario' ? 'selected' : ''; ?>>Universitario (Lic./Ing.)</option>
                                    <option value="Postgrado" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'Postgrado' ? 'selected' : ''; ?>>Especialización / Postgrado</option>
                                    <option value="Maestría" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'Maestría' ? 'selected' : ''; ?>>Maestría</option>
                                    <option value="Doctorado" <?php echo ($_POST['nivel_educativo'] ?? $funcionario['nivel_educativo']) == 'Doctorado' ? 'selected' : ''; ?>>Doctorado</option>
                                </select>
                            </div>

                            <!-- Título Obtenido / Profesión -->
                            <div class="form-group">
                                <label for="titulo_obtenido">Título Obtenido / Profesión</label>
                                <input type="text" id="titulo_obtenido" name="titulo_obtenido"
                                    class="form-control"
                                    placeholder="Ej: Ing. Informática, Lic. en Administración..."
                                    value="<?php echo val('titulo_obtenido', $funcionario); ?>">
                            </div>
                            
                            <!-- Cargo -->
                            <div class="form-group">
                                <label for="cargo_id">Cargo <span class="required">*</span></label>
                                <select id="cargo_id" name="cargo_id"
                                    class="<?php echo fieldClass('cargo_id',$errores); ?>" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?php echo $cargo['id']; ?>"
                                            <?php echo ($_POST['cargo_id'] ?? $funcionario['cargo_id']) == $cargo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cargo['nombre_cargo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="field-feedback <?php echo isset($errores['cargo_id']) ? 'error' : ''; ?>" id="fb-cargo_id">
                                    <?php if (isset($errores['cargo_id'])) echo '❌ ' . $errores['cargo_id']; ?>
                                </div>
                            </div>

                            <!-- Departamento -->
                            <div class="form-group">
                                <label for="departamento_id">Departamento <span class="required">*</span></label>
                                <select id="departamento_id" name="departamento_id"
                                    class="<?php echo fieldClass('departamento_id',$errores); ?>" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($departamentos as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"
                                            <?php echo ($_POST['departamento_id'] ?? $funcionario['departamento_id']) == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="field-feedback <?php echo isset($errores['departamento_id']) ? 'error' : ''; ?>" id="fb-departamento_id">
                                    <?php if (isset($errores['departamento_id'])) echo '❌ ' . $errores['departamento_id']; ?>
                                </div>
                            </div>

                            <!-- Fecha de Ingreso -->
                            <div class="form-group">
                                <label for="fecha_ingreso">Fecha de Ingreso <span class="required">*</span></label>
                                <input type="date" id="fecha_ingreso" name="fecha_ingreso"
                                    class="<?php echo fieldClass('fecha_ingreso',$errores); ?>"
                                    max="<?php echo date('Y-m-d'); ?>"
                                    value="<?php echo val('fecha_ingreso', $funcionario); ?>" required>
                                <div class="field-feedback <?php echo isset($errores['fecha_ingreso']) ? 'error' : ''; ?>" id="fb-fecha_ingreso">
                                    <?php if (isset($errores['fecha_ingreso'])) echo '❌ ' . $errores['fecha_ingreso']; ?>
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" class="form-control">
                                    <option value="activo"    <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'activo'    ? 'selected' : ''; ?>>Activo</option>
                                    <option value="vacaciones" <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                    <option value="reposo"    <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'reposo'    ? 'selected' : ''; ?>>Reposo</option>
                                    <?php if (puedeEliminar()): ?>
                                    <option value="inactivo"  <?php echo ($_POST['estado'] ?? $funcionario['estado']) == 'inactivo'  ? 'selected' : ''; ?>>Inactivo</option>
                                    <?php endif; ?>
                                </select>
                                <div class="field-feedback" id="fb-estado"></div>
                            </div>

                            <!-- Dirección -->
                            <div class="form-group form-grid-full">
                                <label for="direccion">Dirección</label>
                                <textarea id="direccion" name="direccion" class="form-control"><?php echo val('direccion', $funcionario); ?></textarea>
                                <div class="field-feedback" id="fb-direccion"></div>
                            </div>

                        </div><!-- /form-grid -->

                        <div class="form-actions">
                            <a href="ver.php?id=<?php echo $id; ?>" class="btn" style="background:#e2e8f0;color:#2d3748;text-decoration:none;">
                                Cancelar
                            </a>
                            <button type="submit" id="btnGuardar" class="btn btn-primary">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        'use strict';

        const AJAX_URL    = '<?php echo APP_URL; ?>/vistas/funcionarios/ajax/verificar_duplicado.php';
        const EXCLUIR_ID  = <?php echo $id; ?>;
        const camposDup   = ['cedula', 'telefono', 'email'];
        const estado      = {};
        camposDup.forEach(c => { estado[c] = null; });

        function setFeedback(campo, tipo, msg) {
            const fb  = document.getElementById('fb-' + campo);
            const inp = document.getElementById(campo);
            if (!fb || !inp) return;
            fb.className = 'field-feedback ' + tipo;
            fb.innerHTML = msg;
            inp.classList.remove('is-invalid', 'is-valid', 'is-checking');
            if (tipo === 'error') inp.classList.add('is-invalid');
            else if (tipo === 'ok') inp.classList.add('is-valid');
            else if (tipo === 'check') inp.classList.add('is-checking');
        }
        function clearFeedback(campo) {
            const fb  = document.getElementById('fb-' + campo);
            const inp = document.getElementById(campo);
            if (fb)  { fb.className = 'field-feedback'; fb.innerHTML = ''; }
            if (inp) inp.classList.remove('is-invalid', 'is-valid', 'is-checking');
        }

        let debounce = {};
        function verificarEnServidor(campo, valor) {
            clearTimeout(debounce[campo]);
            setFeedback(campo, 'check', '⏳ Verificando...');
            estado[campo] = null;
            debounce[campo] = setTimeout(() => {
                const params = new URLSearchParams({ campo, valor, excluir_id: EXCLUIR_ID });
                fetch(AJAX_URL + '?' + params.toString())
                    .then(r => r.json())
                    .then(data => {
                        if (data.disponible) { setFeedback(campo, 'ok', '✅ Sin conflictos'); estado[campo] = true; }
                        else { setFeedback(campo, 'error', '❌ ' + data.mensaje); estado[campo] = false; }
                    })
                    .catch(() => { clearFeedback(campo); estado[campo] = true; });
            }, 400);
        }

        // Cédula
        document.getElementById('cedula').addEventListener('input', function () {
            let v = this.value.toUpperCase().replace(/[^VE0-9\-]/g, '');
            if (/^[VE]\d/.test(v) && v[1] !== '-') v = v[0] + '-' + v.slice(1);
            this.value = v;
        });
        document.getElementById('cedula').addEventListener('blur', function () {
            const val = this.value.trim().toUpperCase();
            this.value = val;
            if (!val) { clearFeedback('cedula'); estado['cedula'] = false; return; }
            if (!/^[VE]-?\d{6,8}$/.test(val)) {
                setFeedback('cedula', 'error', '❌ Formato inválido. Ejemplo: V-12345678');
                estado['cedula'] = false; return;
            }
            verificarEnServidor('cedula', val);
        });

        // Teléfono
        document.getElementById('telefono').addEventListener('input', function () {
            let v = this.value.replace(/[^\d]/g, '');
            if (v.length > 4) v = v.slice(0, 4) + '-' + v.slice(4, 11);
            this.value = v;
        });
        document.getElementById('telefono').addEventListener('blur', function () {
            const val = this.value.trim();
            if (!val) { clearFeedback('telefono'); estado['telefono'] = true; return; }
            const limpio = val.replace(/[\s\-()]/g, '');
            if (!/^(\+58|0058|0)?4(1[246]|2[246])\d{7}$/.test(limpio)) {
                setFeedback('telefono', 'error', '❌ Formato inválido. Ejemplo: 0412-1234567');
                estado['telefono'] = false; return;
            }
            verificarEnServidor('telefono', val);
        });

        // Email
        document.getElementById('email').addEventListener('blur', function () {
            const val = this.value.trim().toLowerCase();
            this.value = val;
            if (!val) { clearFeedback('email'); estado['email'] = true; return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                setFeedback('email', 'error', '❌ Ingrese un correo válido.');
                estado['email'] = false; return;
            }
            verificarEnServidor('email', val);
        });

        // Nombre y Apellido
        ['nombres', 'apellidos'].forEach(id => {
            document.getElementById(id).addEventListener('blur', function () {
                const val = this.value.trim();
                if (!val) setFeedback(id, 'error', '❌ Este campo es obligatorio.');
                else if (/\d/.test(val)) setFeedback(id, 'error', '❌ No se permiten números.');
                else clearFeedback(id);
            });
        });

        // Bloquear submit si hay errores de duplicados
        document.getElementById('formEditar').addEventListener('submit', function (e) {
            let hayError = false;
            camposDup.forEach(c => { if (estado[c] === false) hayError = true; });
            if (hayError) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
        });
    })();
    </script>

    <script src="<?php echo APP_URL; ?>/publico/js/ux-mejoras.js"></script>
</body>
</html>
