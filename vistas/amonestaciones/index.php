<?php
/**
 * Módulo de Amonestaciones
 * Sistema SIGED - Gestión de Expedientes Digitales
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amonestaciones - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
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
                <?= Icon::get('alert-circle') ?>
                Amonestaciones
            </h1>
            <button class="btn-primary" onclick="abrirModalAmonestacion()">
                <?= Icon::get('plus') ?>
                Registrar Falta
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid" style="margin-bottom: 30px;">
            <div class="kpi-card">
                <div class="kpi-icon gradient-red">
                    <?= Icon::get('alert-circle') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_amonestaciones) ?></div>
                    <div class="kpi-label">Total Amonestaciones</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-orange">
                    <?= Icon::get('calendar') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($amonestaciones_anio) ?></div>
                    <div class="kpi-label">Amonestaciones <?= date('Y') ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-red">
                    <?= Icon::get('alert-triangle') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($faltas_graves) ?></div>
                    <div class="kpi-label">Faltas Muy Graves</div>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card-modern">
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <input type="text" 
                           id="buscarAmonestacion" 
                           class="search-input" 
                           placeholder="Buscar por cédula, nombre...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaAmonestaciones" class="table-modern">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Tipo de Falta</th>
                                <th>Motivo</th>
                                <th>Sanción</th>
                                <th style="text-align: center; width: 100px;">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($amonestaciones)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('alert-circle', 'width: 64px; height: 64px; opacity: 0.3;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay amonestaciones registradas</div>
                                            <p class="empty-state-description">Las amonestaciones aparecerán aquí una vez que sean registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($amonestaciones as $amon): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($amon['fecha_evento'])) ?></td>
                                        <td><strong><?= htmlspecialchars($amon['nombres'] . ' ' . $amon['apellidos']) ?></strong></td>
                                        <td><?= htmlspecialchars($amon['cedula']) ?></td>
                                        <td>
                                            <?php
                                            $badge_class = 'badge-info';
                                            if ($amon['tipo_falta'] == 'muy_grave') $badge_class = 'badge-danger';
                                            elseif ($amon['tipo_falta'] == 'grave') $badge_class = 'badge-warning';
                                            elseif ($amon['tipo_falta'] == 'leve') $badge_class = 'badge-secondary';
                                            
                                            $tipo_falta_label = [
                                                'muy_grave' => 'Muy Grave',
                                                'grave' => 'Grave',
                                                'leve' => 'Leve'
                                            ][$amon['tipo_falta']] ?? $amon['tipo_falta'];
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= $tipo_falta_label ?></span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($amon['motivo'], 0, 40)) . (strlen($amon['motivo']) > 40 ? '...' : '') ?></td>
                                        <td><?= htmlspecialchars(substr($amon['sancion'], 0, 30)) . (strlen($amon['sancion']) > 30 ? '...' : '') ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($amon['ruta_archivo_pdf']): ?>
                                                <a href="<?= APP_URL . '/' . $amon['ruta_archivo_pdf'] ?>" 
                                                   target="_blank" 
                                                   class="btn-icon" 
                                                   title="Ver documento">
                                                    <?= Icon::get('file-text') ?>
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
    // Filtro en tiempo real
    inicializarFiltros({
        module: 'amonestaciones',
        searchId: 'buscarAmonestacion',
        tableBodySelector: '#tablaAmonestaciones tbody',
        countSelector: null
    });

    /**
     * MODAL DE REGISTRO DE AMONESTACIÓN
     */
    async function abrirModalAmonestacion() {
        // Cargar funcionarios activos
        const response = await fetch('<?= APP_URL ?>/vistas/funcionarios/ajax/listar.php');
        const data = await response.json();

        if (!data.success) {
            Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
            return;
        }

        const funcionarios = data.data.filter(f => f.estado === 'activo');

        Swal.fire({
            title: 'Registrar Amonestación',
            html: `
                <div class="swal-form-grid swal-form-grid-2col">
                    <div class="swal-field">
                        <label class="swal-label">Funcionario *</label>
                        <select id="swal-funcionario" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione un funcionario</option>
                            ${funcionarios.map(f => `
                                <option value="${f.id}">${f.nombres} ${f.apellidos} - ${f.cedula}</option>
                            `).join('')}
                        </select>
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">Tipo de Falta *</label>
                        <select id="swal-tipo-falta" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione tipo</option>
                            <option value="leve">Leve</option>
                            <option value="grave">Grave</option>
                            <option value="muy_grave">Muy Grave</option>
                        </select>
                    </div>

                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">Motivo de la Falta *</label>
                        <textarea id="swal-motivo" class="swal2-textarea" rows="3" placeholder="Describa el motivo..." style="width: 95%;"></textarea>
                    </div>

                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">Sanción Aplicada *</label>
                        <textarea id="swal-sancion" class="swal2-textarea" rows="2" placeholder="Describa la sanción..." style="width: 95%;"></textarea>
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">Fecha de la Falta *</label>
                        <input type="date" id="swal-fecha" class="swal2-input" value="${new Date().toISOString().split('T')[0]}" style="width: 95%;">
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">Documento PDF (Opcional)</label>
                        <input type="file" id="swal-pdf" accept=".pdf" class="swal2-file" style="width: 100%;">
                        <small style="color: #64748B; font-size: 12px;">Máximo 5MB</small>
                    </div>
                </div>
            `,
            width: '700px',
            showCancelButton: true,
            confirmButtonText: 'Registrar Amonestación',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                const tipo_falta = document.getElementById('swal-tipo-falta').value;
                const motivo = document.getElementById('swal-motivo').value.trim();
                const sancion = document.getElementById('swal-sancion').value.trim();
                const fecha = document.getElementById('swal-fecha').value;
                const archivo_pdf = document.getElementById('swal-pdf').files[0];

                if (!funcionario_id) {
                    Swal.showValidationMessage('Seleccione un funcionario');
                    return false;
                }
                if (!tipo_falta) {
                    Swal.showValidationMessage('Seleccione el tipo de falta');
                    return false;
                }
                if (!motivo) {
                    Swal.showValidationMessage('Ingrese el motivo');
                    return false;
                }
                if (!sancion) {
                    Swal.showValidationMessage('Ingrese la sanción aplicada');
                    return false;
                }
                if (!fecha) {
                    Swal.showValidationMessage('Ingrese la fecha');
                    return false;
                }
                if (archivo_pdf && archivo_pdf.size > 5 * 1024 * 1024) {
                    Swal.showValidationMessage('El archivo no debe superar 5MB');
                    return false;
                }

                return { funcionario_id, tipo_falta, motivo, sancion, fecha, archivo_pdf };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                registrarAmonestacion(result.value);
            }
        });
    }

    async function registrarAmonestacion(datos) {
        Swal.fire({
            title: 'Procesando...',
            html: 'Registrando amonestación...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('accion', 'registrar_amonestacion');
            formData.append('funcionario_id', datos.funcionario_id);
            formData.append('tipo_falta', datos.tipo_falta);
            formData.append('motivo', datos.motivo);
            formData.append('sancion', datos.sancion);
            formData.append('fecha_evento', datos.fecha);
            if (datos.archivo_pdf) {
                formData.append('archivo_pdf', datos.archivo_pdf);
            }

            const response = await fetch('../funcionarios/ajax/gestionar_historial.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Amonestación Registrada',
                    text: 'La amonestación se registró correctamente'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.error || 'Error al registrar amonestación'
                });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo conectar al servidor'
            });
        }
    }
    </script>
</body>
</html>
