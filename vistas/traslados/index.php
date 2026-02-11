<?php
/**
 * Módulo de Traslados
 * Sistema ISPEB - Gestión de Expedientes Digitales
 * 
 * Permite registrar y consultar traslados de funcionarios entre departamentos
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

// Verificar permisos (nivel 1-2)
if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO'");
$total_traslados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$traslados_anio = $stmt->fetch()['total'];

// Obtener registros de traslados
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.departamento_origen')) as departamento_origen,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.departamento_destino')) as departamento_destino,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento,
        ha.ruta_archivo_pdf,
        ha.nombre_archivo_original,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'TRASLADO'
    ORDER BY ha.fecha_evento DESC, ha.created_at DESC
");
$traslados = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../../config/icons.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslados - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/swal-modern.css">
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?php echo APP_URL; ?>/publico/js/filtros-tiempo-real.js"></script>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .btn-nuevo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            max-width: 400px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f7fafc;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        tbody tr:hover {
            background: #f7fafc;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-from {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-to {
            background: #dcfce7;
            color: #166534;
        }

        .btn-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-link:hover {
            color: #764ba2;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-data-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php include '../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../layout/header.php'; ?>

        <div class="content-wrapper">
            <!-- Header con botón -->
            <div class="page-header">
                <h1 class="header-title"><?php echo Icon::get('arrow-right-circle'); ?> Traslados</h1>
                <button class="btn-nuevo" onclick="abrirModalTraslado()">
                    <?php echo Icon::get('plus'); ?>
                    Nuevo Traslado
                </button>
            </div>

            <!-- Estadísticas -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total Traslados</div>
                    <div class="stat-value"><?php echo number_format($total_traslados); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Traslados <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo number_format($traslados_anio); ?></div>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="content-card">
                <div class="search-bar">
                    <input type="text" 
                           id="buscarTraslado" 
                           class="search-input" 
                           placeholder="Buscar por cédula, nombre, departamento...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaTraslados">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Motivo</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($traslados)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="no-data">
                                            <div class="no-data-icon"><?php echo Icon::get('arrow-right-circle'); ?></div>
                                            <p>No hay traslados registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($traslados as $tras): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($tras['fecha_evento'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($tras['nombres'] . ' ' . $tras['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($tras['cedula']); ?></td>
                                        <td>
                                            <span class="badge badge-from"><?php echo htmlspecialchars($tras['departamento_origen'] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-to"><?php echo htmlspecialchars($tras['departamento_destino'] ?? '-'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($tras['motivo'], 0, 40)) . (strlen($tras['motivo']) > 40 ? '...' : ''); ?></td>
                                        <td>
                                            <?php if ($tras['ruta_archivo_pdf']): ?>
                                                <a href="<?php echo APP_URL . '/' . $tras['ruta_archivo_pdf']; ?>" 
                                                   target="_blank" 
                                                   class="btn-link">
                                                    <?php echo Icon::get('file-text'); ?> Ver
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #cbd5e0;">Sin archivo</span>
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
        // Inicializar filtro de búsqueda
        initSimpleTableSearch('buscarTraslado', 'tablaTraslados');

        /**
         * Abre modal para registrar nuevo traslado
         */
        async function abrirModalTraslado() {
            // Cargar funcionarios activos
            const funcionariosRes = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/listar.php');
            const funcionariosData = await funcionariosRes.json();

            if (!funcionariosData.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }

            const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');

            // Cargar departamentos
            const deptosRes = await fetch('<?php echo APP_URL; ?>/vistas/admin/ajax/get_departamentos.php');
            const deptosData = await deptosRes.json();

            if (!deptosData.success) {
                Swal.fire('Error', 'No se pudieron cargar los departamentos', 'error');
                return;
            }

            const departamentos = deptosData.data;

            // Mostrar modal
            const { value: formValues } = await Swal.fire({
                title: '🔄 Nuevo Traslado',
                html: `
                    <div style="text-align: left;">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `
                                    <option value="${f.id}" data-depto="${f.departamento_id}">
                                        ${f.nombres} ${f.apellidos} - ${f.cedula}
                                    </option>
                                `).join('')}
                            </select>
                        </div>

                        <div style="margin-bottom: 16px; padding: 12px; background: #f7fafc; border-radius: 8px; display: none;" id="depto-actual-box">
                            <smallstyle="color: #718096;">Departamento actual: <strong id="depto-actual-text">-</strong></small>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Departamento Destino *</label>
                            <select id="swal-departamento" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione el departamento destino...</option>
                                ${departamentos.map(d => `
                                    <option value="${d.id}">
                                        ${d.nombre}
                                    </option>
                                `).join('')}
                            </select>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha del Traslado *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 100%; padding: 10px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Motivo *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" style="width: 100%; padding: 10px; min-height: 100px;" placeholder="Describa el motivo del traslado..."></textarea>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento (PDF) - Opcional</label>
                            <input type="file" id="swal-pdf" accept="application/pdf" class="swal2-file" style="width: 100%; padding: 10px;">
                            <small style="color: #718096; font-size: 12px;">Tamaño máximo: 5MB</small>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Registrar Traslado',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#667eea',
                didOpen: () => {
                    // Mostrar departamento actual al seleccionar funcionario
                    const funcionarioSelect = document.getElementById('swal-funcionario');
                    const deptoActualBox = document.getElementById('depto-actual-box');
                    const deptoActualText = document.getElementById('depto-actual-text');

                    funcionarioSelect.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption.value) {
                            const deptoId = selectedOption.dataset.depto;
                            const depto = departamentos.find(d => d.id == deptoId);
                            if (depto) {
                                deptoActualText.textContent = depto.nombre;
                                deptoActualBox.style.display = 'block';
                            } else {
                                deptoActualBox.style.display = 'none';
                            }
                        } else {
                            deptoActualBox.style.display = 'none';
                        }
                    });
                },
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const departamento_destino_id = document.getElementById('swal-departamento').value;
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const archivo_pdf = document.getElementById('swal-pdf').files[0];

                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!departamento_destino_id) { Swal.showValidationMessage('Seleccione el departamento destino'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!motivo || motivo.trim().length < 10) { Swal.showValidationMessage('El motivo debe tener al menos 10 caracteres'); return false; }
                    if (archivo_pdf && archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    if (archivo_pdf && archivo_pdf.type !== 'application/pdf') { Swal.showValidationMessage('Solo se permiten archivos PDF'); return false; }

                    return { funcionario_id, departamento_destino_id, fecha_evento, motivo, archivo_pdf };
                }
            });

            if (!formValues) return;

            // Procesar
            Swal.fire({ title: 'Procesando...', html: 'Registrando traslado...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_traslado');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('departamento_destino_id', formValues.departamento_destino_id);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('motivo', formValues.motivo);

                if (formValues.archivo_pdf) {
                    formData.append('archivo_pdf', formValues.archivo_pdf);
                }

                const response = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Traslado Registrado',
                        html: `
                            <p>${result.message}</p>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                                <p style="margin: 0; font-size: 13px;"><strong>✓ Nuevo departamento:</strong> ${result.data.departamento_nuevo}</p>
                            </div>
                        `,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al registrar traslado', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
