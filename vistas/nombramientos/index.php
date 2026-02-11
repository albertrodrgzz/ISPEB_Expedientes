<?php
/**
 * Módulo de Nombramientos
 * Sistema SIGED - Gestión de Expedientes Digitales
 * 
 * Ejemplo perfecto de diseño Enterprise con SweetAlert2 inteligente
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

// Verificar permisos (nivel 1-2)
if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$total_nombramientos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$nombramientos_anio = $stmt->fetch()['total'];

// Obtener registros de nombramientos
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.cargo')) as cargo_actual,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.departamento')) as departamento,
        ha.fecha_evento,
        ha.ruta_archivo_pdf,
        ha.nombre_archivo_original,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY ha.fecha_evento DESC, ha.created_at DESC
");
$nombramientos = $stmt->fetchAll();

// Obtener departamentos para el select del modal
$stmt_departamentos = $db->query("SELECT id, nombre FROM departamentos ORDER BY nombre ASC");
$departamentos = $stmt_departamentos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nombramientos - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?= APP_URL ?>/publico/js/filtros-tiempo-real.js"></script>
</head>
<body>
    <?php include '../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../layout/header.php'; ?>
        
        <!-- Header con botón -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin: 0; display: flex; align-items: center; gap: 12px;">
                <?= Icon::get('file-text') ?>
                Nombramientos
            </h1>
            <button class="btn-primary" onclick="abrirModalNombramiento()">
                <?= Icon::get('plus') ?>
                Nuevo Nombramiento
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid" style="margin-bottom: 30px;">
            <div class="kpi-card">
                <div class="kpi-icon gradient-blue">
                    <?= Icon::get('file-text') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_nombramientos) ?></div>
                    <div class="kpi-label">Total Nombramientos</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-cyan">
                    <?= Icon::get('calendar') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($nombramientos_anio) ?></div>
                    <div class="kpi-label">Nombramientos <?= date('Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card-modern">
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <input type="text" 
                           id="buscarNombramiento" 
                           class="search-input" 
                           placeholder="🔍 Buscar por cédula, nombre, cargo...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaNombramientos" class="table-modern">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th style="text-align: center; width: 80px;">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($nombramientos)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('file-text', 'width: 64px; height: 64px; opacity: 0.3;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay nombramientos registrados</div>
                                            <p class="empty-state-description">Los nombramientos aparecerán aquí una vez que sean registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($nombramientos as $nom): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($nom['fecha_evento'])) ?></td>
                                        <td><strong><?= htmlspecialchars($nom['nombres'] . ' ' . $nom['apellidos']) ?></strong></td>
                                        <td><?= htmlspecialchars($nom['cedula']) ?></td>
                                        <td>
                                            <span class="badge badge-success"><?= htmlspecialchars($nom['cargo_actual'] ?? 'N/A') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($nom['departamento'] ?? 'N/A') ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($nom['ruta_archivo_pdf']): ?>
                                                <a href="<?= APP_URL . '/' . $nom['ruta_archivo_pdf'] ?>" 
                                                   target="_blank" 
                                                   class="btn-icon" 
                                                   title="Ver documento">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--color-text-lighter);">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    /**
     * SWEETALERT2 INTELIGENTE - EJEMPLO PERFECTO
     * Autoselección de cargo actual al elegir funcionario
     */
    
    let funcionariosData = [];
    let cargosData = [];

    /**
     * Abrir modal de Nombramiento con SweetAlert2 inteligente
     */
    async function abrirModalNombramiento() {
        // Precargar datos
        await Promise.all([
            cargarFuncionariosData(),
            cargarCargosData()
        ]);

        Swal.fire({
            title: 'Nuevo Nombramiento',
            html: `
                <div class="swal-form-grid-2col">
                    <div class="swal-form-group">
                        <label class="swal-label swal-label-required">
                            <?= Icon::get('user') ?>
                            Funcionario
                        </label>
                        <select id="swal-funcionario" class="swal2-select">
                            <option value="">Seleccione un funcionario</option>
                        </select>
                    </div>

                    <div class="swal-form-group">
                        <label class="swal-label swal-label-required">
                            <?= Icon::get('briefcase') ?>
                            Nuevo Cargo
                        </label>
                        <select id="swal-cargo" class="swal2-select">
                            <option value="">Seleccione un cargo</option>
                        </select>
                        <div id="cargo-hint" class="swal-hint">
                            ℹ️ Cargo actual seleccionado por defecto
                        </div>
                    </div>

                    <div class="swal-form-group">
                        <label class="swal-label swal-label-required">
                            <?= Icon::get('building') ?>
                            Departamento
                        </label>
                        <select id="swal-departamento" class="swal2-select">
                            <option value="">Seleccione un departamento</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['nombre']) ?>">
                                    <?= htmlspecialchars($dept['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="swal-form-group">
                        <label class="swal-label swal-label-required">
                            <?= Icon::get('calendar') ?>
                            Fecha de Nombramiento
                        </label>
                        <input type="date" id="swal-fecha" class="swal2-input" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="swal-form-grid" style="margin-top: 20px;">
                    <div class="swal-form-group">
                        <label class="swal-label">
                            <?= Icon::get('file-text') ?>
                            Documento PDF
                        </label>
                        <input type="file" id="swal-pdf" class="swal2-file" accept=".pdf">
                        <div class="swal-helper">
                            <?= Icon::get('info') ?>
                            Opcional - Solo archivos PDF
                        </div>
                    </div>
                </div>
            `,
            width: '700px',
            showCancelButton: true,
            confirmButtonText: 'Registrar Nombramiento',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                // Poblar selects
                poblarSelectFuncionarios();
                poblarSelectCargos();
                
                // Configurar autoselección inteligente
                configurarAutoseleccion();
            },
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                const cargo_id = document.getElementById('swal-cargo').value;
                const departamento = document.getElementById('swal-departamento').value;
                const fecha = document.getElementById('swal-fecha').value;
                const pdf = document.getElementById('swal-pdf').files[0];

                // Validación
                if (!funcionario_id) {
                    Swal.showValidationMessage('Debe seleccionar un funcionario');
                    return false;
                }
                if (!cargo_id) {
                    Swal.showValidationMessage('Debe seleccionar un cargo');
                    return false;
                }
                if (!departamento.trim()) {
                    Swal.showValidationMessage('Debe seleccionar un departamento');
                    return false;
                }
                if (!fecha) {
                    Swal.showValidationMessage('Debe ingresar la fecha');
                    return false;
                }

                return { funcionario_id, cargo_id, departamento, fecha, pdf };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                registrarNombramiento(result.value);
            }
        });
    }

    /**
     * Cargar datos de funcionarios vía AJAX
     */
    async function cargarFuncionariosData() {
        try {
            const response = await fetch('<?= APP_URL ?>/vistas/nombramientos/ajax/obtener_funcionarios.php');
            const data = await response.json();
            
            if (data.success) {
                funcionariosData = data.data;
            } else {
                throw new Error(data.error || 'Error al cargar funcionarios');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
        }
    }

    /**
     * Cargar datos de cargos vía AJAX
     */
    async function cargarCargosData() {
        try {
            const response = await fetch('<?= APP_URL ?>/vistas/nombramientos/ajax/obtener_cargos.php');
            const data = await response.json();
            
            if (data.success) {
                cargosData = data.data;
            } else {
                throw new Error(data.error || 'Error al cargar cargos');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudieron cargar los cargos', 'error');
        }
    }

    /**
     * Poblar select de funcionarios con data-cargo-id
     */
    function poblarSelectFuncionarios() {
        const select = document.getElementById('swal-funcionario');
        
        funcionariosData.forEach(func => {
            const option = document.createElement('option');
            option.value = func.id;
            option.textContent = `${func.nombres} ${func.apellidos} - ${func.cedula}`;
            option.dataset.cargoId = func.cargo_id; // CLAVE: data-cargo-id
            select.appendChild(option);
        });
    }

    /**
     * Poblar select de cargos
     */
    function poblarSelectCargos() {
        const select = document.getElementById('swal-cargo');
        
        cargosData.forEach(cargo => {
            const option = document.createElement('option');
            option.value = cargo.id;
            option.textContent = cargo.nombre_cargo;
            select.appendChild(option);
        });
    }

    /**
     * LÓGICA INTELIGENTE: Autoselección de cargo actual
     */
    function configurarAutoseleccion() {
        const selectFuncionario = document.getElementById('swal-funcionario');
        const selectCargo = document.getElementById('swal-cargo');
        const cargoHint = document.getElementById('cargo-hint');

        selectFuncionario.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const cargoIdActual = selectedOption.dataset.cargoId;

            if (cargoIdActual) {
                // Autoseleccionar cargo actual
                selectCargo.value = cargoIdActual;
                
                // Mostrar hint informativo con clase
                cargoHint.classList.add('show');
                
                // Ocultar hint después de 3 segundos
                setTimeout(() => {
                    cargoHint.classList.remove('show');
                }, 3000);
            } else {
                cargoHint.classList.remove('show');
            }
        });
    }

    /**
     * Registrar nombramiento vía FormData
     */
    async function registrarNombramiento(data) {
        const formData = new FormData();
        formData.append('funcionario_id', data.funcionario_id);
        formData.append('cargo_id', data.cargo_id);
        formData.append('departamento', data.departamento);
        formData.append('fecha', data.fecha);
        
        if (data.pdf) {
            formData.append('pdf', data.pdf);
        }

        try {
            Swal.fire({
                title: 'Procesando...',
                text: 'Registrando nombramiento',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await fetch('<?= APP_URL ?>/vistas/nombramientos/ajax/registrar.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Nombramiento registrado correctamente',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error(result.error || 'Error al registrar');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo registrar el nombramiento'
            });
        }
    }

    /**
     * Inicializar filtros de búsqueda en tiempo real
     */
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('buscarNombramiento');
        const table = document.getElementById('tablaNombramientos');
        
        if (searchInput && table) {
            initFiltroTiempoReal('buscarNombramiento', 'tablaNombramientos');
        }
    });
    </script>
</body>
</html>
