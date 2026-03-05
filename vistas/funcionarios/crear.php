<?php
/**
 * Vista: Crear Funcionario
 * Con validaciones robustas: formato cédula, duplicados (cédula/teléfono/email),
 * y generación de username como 1ra letra del nombre + apellido.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

verificarSesion();
if (!verificarNivel(2)) {
    header('Location: ../dashboard/index.php');
    exit;
}

$csrfToken = generarTokenCSRF();
$errores   = [];   // array campo => mensaje
$success   = '';

// ─── Helpers de validación ───────────────────────────────────────────────────
function validarCedulaVenezolana(string $cedula): bool {
    // Acepta: solo dígitos, 6-9 números (sin prefijo V/E)
    return (bool) preg_match('/^\d{6,9}$/', trim($cedula));
}

function validarTelefonoVenezolano(string $tel): bool {
    // Acepta: 0416..., 0412..., 0424..., etc. (10-11 dígitos con 0 inicial o sin prefijo)
    $tel = preg_replace('/[^0-9]/', '', $tel);
    // Acepta con prefijo 0, +58 o 0058, o directo. Operadoras: 412,414,416,424,426
    return (bool) preg_match('/^(\+58|0058|0)?4(1[2-9]|2[24-6])\d{7}$/', $tel) || strlen($tel) === 11;
}
// ─────────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
        die('Error de seguridad: Token CSRF inválido. Recargue la página.');
    }

    $datos = [
        'cedula'           => preg_replace('/[^0-9]/', '', strtoupper(trim(limpiar($_POST['cedula'])))),
        'nombres'          => trim(limpiar($_POST['nombres'])),
        'apellidos'        => trim(limpiar($_POST['apellidos'])),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
        'genero'           => $_POST['genero'] ?? null,
        'telefono'         => preg_replace('/[^0-9]/', '', trim(limpiar($_POST['telefono'] ?? ''))),
        'email'            => strtolower(trim(limpiar($_POST['email'] ?? ''))),
        'direccion'        => trim(limpiar($_POST['direccion'] ?? '')),
        'cargo_id'         => $_POST['cargo_id']         ?? '',
        'departamento_id'  => $_POST['departamento_id']  ?? '',
        'fecha_ingreso'    => $_POST['fecha_ingreso']    ?? '',
        'estado'           => $_POST['estado'] ?? 'activo',
    ];

    // ── Validaciones de campos obligatorios ──────────────────────────────────
    if (empty($datos['cedula'])) {
        $errores['cedula'] = 'La cédula es obligatoria.';
    } elseif (!validarCedulaVenezolana($datos['cedula'])) {
        $errores['cedula'] = 'Ingrese solo los números de la cédula, sin prefijo V ni guiones (ej: 12345678).';
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
        $errores['telefono'] = 'Ingrese el teléfono sin guiones ni letras (ej: 04121234567).';
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

    // Fecha de nacimiento: debe ser mayor de 18 años
    if (!empty($datos['fecha_nacimiento'])) {
        $edad = (int) date_diff(
            date_create($datos['fecha_nacimiento']),
            date_create('today')
        )->y;
        if ($edad < 18) {
            $errores['fecha_nacimiento'] = 'El funcionario debe ser mayor de 18 años.';
        }
    }

    // ── Validaciones de duplicados (solo si no hay errores de formato) ────────
    if (empty($errores)) {
        $modeloFuncionario = new Funcionario();

        // Verificar cédula: si existe y es inactivo → reactivar; si activo → error
        $existenteCedula = $modeloFuncionario->obtenerPorCedula($datos['cedula']);
        if ($existenteCedula) {
            if ($existenteCedula['estado'] === 'inactivo') {
                // Reingreso
                $reactivado = $modeloFuncionario->reactivarFuncionario($existenteCedula['id'], $datos);
                if ($reactivado) {
                    registrarAuditoria('REACTIVAR_FUNCIONARIO', 'funcionarios', $existenteCedula['id'],
                        ['estado_anterior' => 'inactivo'],
                        ['estado_nuevo' => 'activo', 'cargo_id' => $datos['cargo_id'],
                         'departamento_id' => $datos['departamento_id'], 'fecha_ingreso' => $datos['fecha_ingreso']]
                    );
                    $_SESSION['success'] = '✅ Funcionario reactivado exitosamente (Reingreso al sistema).';
                    header('Location: ver.php?id=' . $existenteCedula['id']);
                    exit;
                }
                $errores['cedula'] = 'Error al reactivar el funcionario. Intente de nuevo.';
            } else {
                $nombre = htmlspecialchars($existenteCedula['nombres'] . ' ' . $existenteCedula['apellidos']);
                $errores['cedula'] = "Ya existe un funcionario activo con esa cédula: <strong>$nombre</strong>.";
            }
        }

        // Verificar teléfono y email duplicados (excluyendo reingresos ya detectados)
        if (empty($errores)) {
            $duplicados = $modeloFuncionario->verificarDuplicados($datos);
            // Solo añadir duplicados de teléfono/email (cédula ya fue verificada arriba)
            foreach (['telefono', 'email'] as $campo) {
                if (isset($duplicados[$campo])) {
                    $errores[$campo] = $duplicados[$campo];
                }
            }
        }
    }

    // ── Sin errores: crear funcionario ───────────────────────────────────────
    if (empty($errores)) {
        $modeloFuncionario = $modeloFuncionario ?? new Funcionario();
        $id = $modeloFuncionario->crear($datos);

        if ($id) {
            registrarAuditoria('CREAR_FUNCIONARIO', 'funcionarios', $id, null, $datos);
            $_SESSION['success'] = '✅ Funcionario registrado exitosamente. Se generó su usuario de acceso automáticamente.';
            header('Location: ver.php?id=' . $id);
            exit;
        }
        $errores['general'] = 'Error interno al guardar el funcionario. Intente de nuevo.';
    }
}

// Cargar listas
$db           = getDB();
$departamentos = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
$cargos        = $db->query("SELECT * FROM cargos ORDER BY nivel_acceso, nombre_cargo")->fetchAll();

// Helper para repintar el campo con clase de error
function fieldClass(string $campo, array $errores): string {
    return isset($errores[$campo]) ? 'form-control is-invalid' : 'form-control';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Funcionario - <?php echo APP_NAME; ?></title>
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

        /* Mensajes de error/ok bajo cada campo */
        .field-feedback {
            font-size: 12.5px; margin-top: 5px; min-height: 18px;
            display: flex; align-items: center; gap: 5px;
        }
        .field-feedback.error { color: var(--color-danger); }
        .field-feedback.ok    { color: #38a169; }
        .field-feedback.check { color: #d69e2e; }

        .form-actions {
            display: flex; gap: 12px; justify-content: flex-end;
            margin-top: 32px; padding-top: 24px;
            border-top: 1px solid var(--color-border-light);
        }
        .alert-error-list {
            background: #fff5f5; border: 1px solid #fed7d7;
            border-radius: var(--radius-md); padding: 16px 20px;
            margin-bottom: 24px; color: #c53030;
        }
        .alert-error-list ul { margin: 8px 0 0 18px; }
        .alert-error-list li { margin-bottom: 4px; }

        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php
            $pageTitle    = 'Nuevo Funcionario';
            $headerAction = '<a href="index.php" class="btn" style="background:#e2e8f0;color:#2d3748;text-decoration:none;">← Volver al Listado</a>';
            include __DIR__ . '/../layout/header.php';
        ?>

        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Datos del Funcionario</h2>
                    <p class="card-subtitle">Complete el formulario. Los campos marcados con <span style="color:var(--color-danger)">*</span> son obligatorios.</p>
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

                    <form id="formCrear" method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-grid">

                            <!-- Cédula -->
                            <div class="form-group">
                                <label for="cedula">Cédula <span class="required">*</span></label>
                                <input type="text" id="cedula" name="cedula"
                                    class="<?php echo fieldClass('cedula',$errores); ?>"
                                    placeholder="Ej: 12345678"
                                    value="<?php echo htmlspecialchars($_POST['cedula'] ?? ''); ?>"
                                    autocomplete="off"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    required>
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
                                    value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>">
                                <div class="field-feedback <?php echo isset($errores['fecha_nacimiento']) ? 'error' : ''; ?>" id="fb-fecha_nacimiento">
                                    <?php if (isset($errores['fecha_nacimiento'])) echo '❌ ' . $errores['fecha_nacimiento']; ?>
                                </div>
                            </div>

                            <!-- Nombres -->
                            <div class="form-group">
                                <label for="nombres">Nombres <span class="required">*</span></label>
                                <input type="text" id="nombres" name="nombres"
                                    class="<?php echo fieldClass('nombres',$errores); ?>"
                                    placeholder="María Alejandra"
                                    value="<?php echo htmlspecialchars($_POST['nombres'] ?? ''); ?>"
                                    required>
                                <div class="field-feedback <?php echo isset($errores['nombres']) ? 'error' : ''; ?>" id="fb-nombres">
                                    <?php if (isset($errores['nombres'])) echo '❌ ' . $errores['nombres']; ?>
                                </div>
                            </div>

                            <!-- Apellidos -->
                            <div class="form-group">
                                <label for="apellidos">Apellidos <span class="required">*</span></label>
                                <input type="text" id="apellidos" name="apellidos"
                                    class="<?php echo fieldClass('apellidos',$errores); ?>"
                                    placeholder="Pérez González"
                                    value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>"
                                    required>
                                <div class="field-feedback <?php echo isset($errores['apellidos']) ? 'error' : ''; ?>" id="fb-apellidos">
                                    <?php if (isset($errores['apellidos'])) echo '❌ ' . $errores['apellidos']; ?>
                                </div>
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
                                <div class="field-feedback" id="fb-genero"></div>
                            </div>

                            <!-- Teléfono -->
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono"
                                    class="<?php echo fieldClass('telefono',$errores); ?>"
                                    placeholder="04121234567"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                                <div class="field-feedback <?php echo isset($errores['telefono']) ? 'error' : ''; ?>" id="fb-telefono">
                                    <?php if (isset($errores['telefono'])) echo '❌ ' . $errores['telefono']; ?>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" id="email" name="email"
                                    class="<?php echo fieldClass('email',$errores); ?>"
                                    placeholder="correo@ispeb.gob.ve"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <div class="field-feedback <?php echo isset($errores['email']) ? 'error' : ''; ?>" id="fb-email">
                                    <?php if (isset($errores['email'])) echo '❌ ' . $errores['email']; ?>
                                </div>
                            </div>

                            <!-- Cargo -->
                            <div class="form-group">
                                <label for="cargo_id">Cargo <span class="required">*</span></label>
                                <select id="cargo_id" name="cargo_id"
                                    class="<?php echo fieldClass('cargo_id',$errores); ?>" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?php echo $cargo['id']; ?>"
                                            <?php echo ($_POST['cargo_id'] ?? '') == $cargo['id'] ? 'selected' : ''; ?>>
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
                                            <?php echo ($_POST['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
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
                                    value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? ''); ?>"
                                    required>
                                <div class="field-feedback <?php echo isset($errores['fecha_ingreso']) ? 'error' : ''; ?>" id="fb-fecha_ingreso">
                                    <?php if (isset($errores['fecha_ingreso'])) echo '❌ ' . $errores['fecha_ingreso']; ?>
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" class="form-control">
                                    <option value="activo"    <?php echo ($_POST['estado'] ?? 'activo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="vacaciones" <?php echo ($_POST['estado'] ?? '') == 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                    <option value="reposo"    <?php echo ($_POST['estado'] ?? '') == 'reposo' ? 'selected' : ''; ?>>Reposo</option>
                                </select>
                                <div class="field-feedback" id="fb-estado"></div>
                            </div>

                            <!-- Dirección -->
                            <div class="form-group form-grid-full">
                                <label for="direccion">Dirección</label>
                                <textarea id="direccion" name="direccion" class="form-control"
                                    placeholder="Dirección completa del funcionario"><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                                <div class="field-feedback" id="fb-direccion"></div>
                            </div>

                        </div><!-- /form-grid -->

                        <div class="form-actions">
                            <a href="index.php" class="btn" style="background:#e2e8f0;color:#2d3748;text-decoration:none;">
                                Cancelar
                            </a>
                            <button type="submit" id="btnGuardar" class="btn btn-primary">
                                Guardar Funcionario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    /**
     * Validaciones en tiempo real — Módulo Crear Funcionario
     */
    (function () {
        'use strict';

        const AJAX_URL = '<?php echo APP_URL; ?>/vistas/funcionarios/ajax/verificar_duplicado.php';

        // Campos con verificación de duplicados en el servidor
        const camposDuplicados = ['cedula', 'telefono', 'email'];

        // Estado de validez por campo
        const estado = {};
        camposDuplicados.forEach(c => { estado[c] = null; }); // null = sin verificar

        // ── Utilidades de UI ──────────────────────────────────────────────────
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

        // ── Validación de formato de cédula (cliente) ─────────────────────────
        document.getElementById('cedula').addEventListener('blur', function () {
            const raw = this.value.trim();
            // Quitar prefijo V/E y guiones
            const val = raw.replace(/^[VvEeJj]-?/, '').replace(/[^0-9]/g, '');
            this.value = val;
            if (!val) { clearFeedback('cedula'); estado['cedula'] = false; return; }

            const ok = /^\d{6,9}$/.test(val);
            if (!ok) {
                setFeedback('cedula', 'error', '❌ Ingrese solo los números (6-9 dígitos)');
                estado['cedula'] = false;
                return;
            }
            // Verificar duplicado en servidor
            verificarEnServidor('cedula', val);
        });

        // ── Input en tiempo real: solo dígitos para cédula ───────────────────
        document.getElementById('cedula').addEventListener('input', function () {
            // Quitar prefijo V/E y guiones si el usuario los escribe
            this.value = this.value.replace(/^[VvEeJj]-?/, '').replace(/[^0-9]/g, '');
        });

        // ── Validación de teléfono (cliente + servidor) ───────────────────────
        // (manejado por el listener de abajo)

        // ── Validación de email (cliente + servidor) ──────────────────────────
        document.getElementById('email').addEventListener('blur', function () {
            const val = this.value.trim().toLowerCase();
            this.value = val;
            if (!val) { clearFeedback('email'); estado['email'] = true; return; }

            const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
            if (!ok) {
                setFeedback('email', 'error', '❌ Ingrese un correo válido. Ejemplo: usuario@ispeb.gob.ve');
                estado['email'] = false;
                return;
            }
            verificarEnServidor('email', val);
        });

        // ── Llamada AJAX genérica ─────────────────────────────────────────────
        let debounce = {};
        function verificarEnServidor(campo, valor) {
            clearTimeout(debounce[campo]);
            setFeedback(campo, 'check', '⏳ Verificando...');
            estado[campo] = null;

            debounce[campo] = setTimeout(() => {
                const params = new URLSearchParams({ campo, valor });
                fetch(AJAX_URL + '?' + params.toString())
                    .then(r => r.json())
                    .then(data => {
                        if (data.disponible) {
                            setFeedback(campo, 'ok', '✅ Disponible');
                            estado[campo] = true;
                        } else {
                            setFeedback(campo, 'error', '❌ ' + data.mensaje);
                            estado[campo] = false;
                        }
                    })
                    .catch(() => {
                        clearFeedback(campo);
                        estado[campo] = true; // si falla AJAX, dejar pasar (backend lo captura)
                    });
            }, 400);
        }

        // ── Formateo automático de cédula mientras escribe ────────────────────
        document.getElementById('cedula').addEventListener('input', function () {
            // Autoformatea: si empieza con V o E sin guión, lo agrega
            let v = this.value.toUpperCase().replace(/[^VE0-9\-]/g, '');
            if (/^[VE]\d/.test(v) && v[1] !== '-') {
                v = v[0] + '-' + v.slice(1);
            }
            this.value = v;
        });

        // ── Teléfono: solo dígitos ──────────────────────────────────────────
        document.getElementById('telefono').addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('telefono').addEventListener('blur', function () {
            const val = this.value.replace(/[^0-9]/g, '');
            this.value = val;
            if (!val) { clearFeedback('telefono'); estado['telefono'] = true; return; }
            const ok = /^(0|0058|\+58)?4(1[246]|2[246])\d{7}$/.test(val) || val.length === 11;
            if (!ok) {
                setFeedback('telefono', 'error', '❌ Ej: 04121234567 (11 dígitos)');
                estado['telefono'] = false;
                return;
            }
            verificarEnServidor('telefono', val);
        });

        // ── Validación del nombre / apellido ──────────────────────────────────
        ['nombres', 'apellidos'].forEach(id => {
            document.getElementById(id).addEventListener('blur', function () {
                const val = this.value.trim();
                if (!val) {
                    setFeedback(id, 'error', '❌ Este campo es obligatorio.');
                } else if (/\d/.test(val)) {
                    setFeedback(id, 'error', '❌ No se permiten números en este campo.');
                } else {
                    clearFeedback(id);
                }
            });
        });

        // ── Bloquear submit si hay errores de duplicados activos ──────────────
        document.getElementById('formCrear').addEventListener('submit', function (e) {
            let hayError = false;
            camposDuplicados.forEach(c => {
                if (estado[c] === false) hayError = true;
                if (estado[c] === null) {
                    const val = document.getElementById(c)?.value.trim();
                    if (c === 'cedula' && val && !/^\d{6,9}$/.test(val)) {
                        setFeedback(c, 'error', '❌ Ingrese solo los números de la cédula.');
                        hayError = true;
                    }
                }
            });
            if (hayError) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    })();
    </script>

    <script src="<?php echo APP_URL; ?>/publico/js/ux-mejoras.js"></script>
</body>
</html>
