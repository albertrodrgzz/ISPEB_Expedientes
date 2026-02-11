<?php
/**
 * Módulo de Amonestaciones
 * Sistema ISPEB - Gestión de Expedientes Digitales
 * 
 * Permite registrar y consultar faltas y amonestaciones disciplinarias
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
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION'");
$total_amonestaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$amonestaciones_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND JSON_EXTRACT(detalles, '$.tipo_falta') = 'muy_grave'");
$faltas_graves = $stmt->fetch()['total'];

// Obtener registros de amonestaciones
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta')) as tipo_falta,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.sancion')) as sancion,
        ha.fecha_evento,
        ha.ruta_archivo_pdf,
        ha.nombre_archivo_original,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'AMONESTACION'
    ORDER BY ha.fecha_evento DESC, ha.created_at DESC
");
$amonestaciones = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../../config/icons.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amonestaciones - <?php echo APP_NAME; ?></title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
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
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-leve {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-grave {
            background: #fed7aa;
            color: #9a3412;
        }

        .badge-muy-grave {
            background: #fecaca;
            color: #991b1b;
        }

        .btn-link {
            color: #f59e0b;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-link:hover {
            color: #ef4444;
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
                <h1 class="header-title"><?php echo Icon::get('alert-circle'); ?> Amonestaciones</h1>
                <button class="btn-nuevo" onclick="abrirModalAmonestacion()">
                    <?php echo Icon::get('plus'); ?>
                    Registrar Falta
                </button>
            </div>

            <!-- Estadísticas -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total Amonestaciones</div>
                    <div class="stat-value"><?php echo number_format($total_amonestaciones); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Amonestaciones <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo number_format($amonestaciones_anio); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Faltas Muy Graves</div>
                    <div class="stat-value"><?php echo number_format($faltas_graves); ?></div>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="content-card">
                <div class="search-bar">
                    <input type="text" 
                           id="buscarAmonestacion" 
                           class="search-input" 
                           placeholder="Buscar por cédula, nombre...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaAmonestaciones">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Tipo de Falta</th>
                                <th>Motivo</th>
                                <th>Sanción</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($amonestaciones)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="no-data">
                                            <div class="no-data-icon"><?php echo Icon::get('alert-circle'); ?></div>
                                            <p>No hay amonestaciones registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($amonestaciones as $amon): ?>
                                    <?php
                                        $tipo_falta = $amon['tipo_falta'] ?? 'leve';
                                        $badge_class = 'badge-leve';
                                        $badge_text = 'Leve';
                                        
                                        if ($tipo_falta === 'grave') {
                                            $badge_class = 'badge-grave';
                                            $badge_text = 'Grave';
                                        } elseif ($tipo_falta === 'muy_grave') {
                                            $badge_class = 'badge-muy-grave';
                                            $badge_text = 'Muy Grave';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($amon['fecha_evento'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($amon['nombres'] . ' ' . $amon['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($amon['cedula']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($amon['motivo'] ?? '-', 0, 40)) . (strlen($amon['motivo'] ?? '') > 40 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars(substr($amon['sancion'] ?? '-', 0, 30)) . (strlen($amon['sancion'] ?? '') > 30 ? '...' : ''); ?></td>
                                        <td>
                                            <?php if ($amon['ruta_archivo_pdf']): ?>
                                                <a href="<?php echo APP_URL . '/' . $amon['ruta_archivo_pdf']; ?>" 
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
        initSimpleTableSearch('buscarAmonestacion', 'tablaAmonestaciones');

        /**
         * Abre modal para registrar amonestación
         */
        async function abrirModalAmonestacion() {
            // Cargar funcionarios activos
            const funcionariosRes = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/listar.php');
            const funcionariosData = await funcionariosRes.json();

            if (!funcionariosData.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }

            const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');

            // Mostrar modal
            const { value: formValues } = await Swal.fire({
                title: '⚠️ Registrar Falta',
                html: `
                    <div style="text-align: left;">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `
                                    <option value="${f.id}">
                                        ${f.nombres} ${f.apellidos} - ${f.cedula}
                                    </option>
                                `).join('')}
                            </select>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Tipo de Falta *</label>
                            <select id="swal-tipo" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione el tipo...</option>
                                <option value="leve">Leve</option>
                                <option value="grave">Grave</option>
                                <option value="muy_grave">Muy Grave</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 100%; padding: 10px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Motivo *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" style="width: 100%; padding: 10px; min-height: 100px;" placeholder="Describa el motivo de la falta..."></textarea>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Sanción Aplicada *</label>
                            <textarea id="swal-sancion" class="swal2-textarea" style="width: 100%; padding: 10px; min-height: 80px;" placeholder="Describa la sanción aplicada..."></textarea>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento (PDF) *</label>
                            <input type="file" id="swal-pdf" accept="application/pdf" class="swal2-file" style="width: 100%; padding: 10px;">
                            <small style="color: #ef4444; font-size: 12px;">⚠️ OBLIGATORIO - Máximo 5MB</small>
                        </div>
                    </div>
                `,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: 'Registrar Amonestación',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f59e0b',
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const tipo_falta = document.getElementById('swal-tipo').value;
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const sancion = document.getElementById('swal-sancion').value;
                    const archivo_pdf = document.getElementById('swal-pdf').files[0];

                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!tipo_falta) { Swal.showValidationMessage('Seleccione el tipo de falta'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!motivo || motivo.trim().length < 10) { Swal.showValidationMessage('El motivo debe tener al menos 10 caracteres'); return false; }
                    if (!sancion || sancion.trim().length < 5) { Swal.showValidationMessage('La sanción debe tener al menos 5 caracteres'); return false; }
                    if (!archivo_pdf) { Swal.showValidationMessage('El documento PDF es OBLIGATORIO'); return false; }
                    if (archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    if (archivo_pdf.type !== 'application/pdf') { Swal.showValidationMessage('Solo se permiten archivos PDF'); return false; }

                    return { funcionario_id, tipo_falta, fecha_evento, motivo, sancion, archivo_pdf };
                }
            });

            if (!formValues) return;

            // Procesar
            Swal.fire({ title: 'Procesando...', html: 'Registrando amonestación...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_amonestacion');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('tipo_falta', formValues.tipo_falta);
                formData.append('motivo', formValues.motivo);
                formData.append('sancion', formValues.sancion);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('archivo_pdf', formValues.archivo_pdf);

                const response = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Amonestación Registrada',
                        html: `
                            <p>${result.message}</p>
                            ${result.data.marca_grave ? '<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; margin-top: 14px;"><p style="margin: 0; font-size: 13px; color: #991b1b;"><strong>⚠️ Atención:</strong> Se marcó al funcionario con amonestaciones graves.</p></div>' : ''}
                        `,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al registrar amonestación', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
