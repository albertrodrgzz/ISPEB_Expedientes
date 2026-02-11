<?php
/**
 * Módulo de Vacaciones
 * Sistema SIGED - Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

// Verificar sesión y permisos (solo nivel 1 y 2)
verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder al módulo de vacaciones';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas para el dashboard de vacaciones
$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION'");
$total_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$vacaciones_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND fecha_evento > CURDATE()");
$vacaciones_programadas = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_totales'))), 0) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$dias_usados_anio = $stmt->fetch()['total'];

// Obtener departamentos para filtros
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener registros de vacaciones con información del funcionario
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        ha.fecha_evento as fecha_inicio,
        ha.fecha_fin,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.dias_totales')) as dias_totales,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.periodo')) as periodo,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.nombre_archivo_original,
        ha.created_at,
        CASE 
            WHEN ha.fecha_evento > CURDATE() THEN 'Programada'
            WHEN ha.fecha_fin < CURDATE() THEN 'Finalizada'
            ELSE 'En curso'
        END as estado_vacacion
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'VACACION'
    ORDER BY ha.fecha_evento DESC
");
$vacaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacaciones - <?= APP_NAME ?></title>
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
                <?= Icon::get('sun') ?>
                Vacaciones
            </h1>
            <button class="btn-primary" onclick="abrirModalVacaciones()">
                <?= Icon::get('plus') ?>
                Registrar Vacaciones
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid" style="margin-bottom: 30px;">
            <div class="kpi-card">
                <div class="kpi-icon gradient-blue">
                    <?= Icon::get('calendar') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_vacaciones) ?></div>
                    <div class="kpi-label">Total Vacaciones</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-cyan">
                    <?= Icon::get('calendar') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($vacaciones_anio) ?></div>
                    <div class="kpi-label">Vacaciones <?= date('Y') ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-green">
                    <?= Icon::get('clock') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($vacaciones_programadas) ?></div>
                    <div class="kpi-label">Programadas</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-orange">
                    <?= Icon::get('sun') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($dias_usados_anio) ?></div>
                    <div class="kpi-label">Días Usados <?= date('Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card-modern">
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <input type="text" 
                           id="buscarVacacion" 
                           class="search-input" 
                           placeholder="Buscar por cédula, nombre, departamento...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaVacaciones" class="table-modern">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días</th>
                                <th>Estado</th>
                                <th style="text-align: center; width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vacaciones)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('sun', 'width: 64px; height: 64px; opacity: 0.3;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay registros de vacaciones</div>
                                            <p class="empty-state-description">Las vacaciones aparecerán aquí una vez que sean registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vacaciones as $vac): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($vac['nombres'] . ' ' . $vac['apellidos']) ?></strong></td>
                                        <td><?= htmlspecialchars($vac['cedula']) ?></td>
                                        <td><?= htmlspecialchars($vac['departamento']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($vac['fecha_inicio'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($vac['fecha_fin'])) ?></td>
                                        <td><strong><?= $vac['dias_totales'] ?></strong></td>
                                        <td>
                                            <?php
                                            $badge_class = 'badge-info';
                                            if ($vac['estado_vacacion'] == 'Finalizada') $badge_class = 'badge-secondary';
                                            if ($vac['estado_vacacion'] == 'En curso') $badge_class = 'badge-success';
                                            if ($vac['estado_vacacion'] == 'Programada') $badge_class = 'badge-warning';
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= $vac['estado_vacacion'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="../funcionarios/ver.php?id=<?= $vac['funcionario_id'] ?>" 
                                               class="btn-icon" 
                                               title="Ver funcionario">
                                                <?= Icon::get('eye') ?>
                                            </a>
                                            <?php if ($vac['ruta_archivo']): ?>
                                                <a href="<?= APP_URL . '/' . $vac['ruta_archivo'] ?>" 
                                                   target="_blank" 
                                                   class="btn-icon" 
                                                   title="Ver documento">
                                                    <?= Icon::get('file-text') ?>
                                                </a>
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
        module: 'vacaciones',
        searchId: 'buscarVacacion',
        tableBodySelector: '#tablaVacaciones tbody',
        countSelector: null
    });

    /**
     * MODAL DE REGISTRO DE VACACIONES
     */
    async function abrirModalVacaciones() {
        // Cargar funcionarios activos
        const response = await fetch('../funcionarios/ajax/listar.php');
        const data = await response.json();
        
        if (!data.success) {
            Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
            return;
        }
        
        const funcionarios = data.data.filter(f => f.estado === 'activo');
        
        Swal.fire({
            title: 'Registrar Vacaciones',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('user')}
                            Funcionario *
                        </label>
                        <select id="swal-funcionario" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione un funcionario</option>
                            ${funcionarios.map(f => `
                                <option value="${f.id}">
                                    ${f.nombres} ${f.apellidos} - ${f.cedula}
                                </option>
                            `).join('')}
                        </select>
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('calendar')}
                            Fecha Inicio *
                        </label>
                        <input type="date" id="swal-fecha-inicio" class="swal2-input" style="width: 95%;">
                    </div>

                    <div class="swal-field">
                        < label class="swal-label">
                            ${Icon.get('calendar')}
                            Fecha Fin *
                        </label>
                        <input type="date" id="swal-fecha-fin" class="swal2-input" style="width: 95%;">
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('file-text')}
                            Documento PDF (Opcional)
                        </label>
                        <input type="file" id="swal-pdf" accept=".pdf" class="swal2-file" style="width: 100%;">
                        <small style="color: #64748B; font-size: 12px;">Máximo 5MB</small>
                    </div>

                    <div class="swal-hint" style="grid-column: 1 / -1;">
                        Los días totales se calcularán automáticamente
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Registrar Vacaciones',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                const fecha_inicio = document.getElementById('swal-fecha-inicio').value;
                const fecha_fin = document.getElementById('swal-fecha-fin').value;
                const archivo_pdf = document.getElementById('swal-pdf').files[0];
                
                if (!funcionario_id) {
                    Swal.showValidationMessage('Seleccione un funcionario');
                    return false;
                }
                if (!fecha_inicio || !fecha_fin) {
                    Swal.showValidationMessage('Ingrese las fechas de inicio y fin');
                    return false;
                }
                if (new Date(fecha_fin) < new Date(fecha_inicio)) {
                    Swal.showValidationMessage('La fecha de fin debe ser posterior a la fecha de inicio');
                    return false;
                }
                if (archivo_pdf && archivo_pdf.size > 5 * 1024 * 1024) {
                    Swal.showValidationMessage('El archivo no debe superar 5MB');
                    return false;
                }
                
                return { funcionario_id, fecha_inicio, fecha_fin, archivo_pdf };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                registrarVacaciones(result.value);
            }
        });
    }

    async function registrarVacaciones(datos) {
        Swal.fire({
            title: 'Procesando...',
            html: 'Registrando vacaciones...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        try {
            const formData = new FormData();
            formData.append('accion', 'registrar_vacacion');
            formData.append('funcionario_id', datos.funcionario_id);
            formData.append('fecha_inicio', datos.fecha_inicio);
            formData.append('fecha_fin', datos.fecha_fin);
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
                    title: 'Vacaciones Registradas',
                    text: 'Las vacaciones se registraron correctamente'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.error || 'Error al registrar vacaciones'
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

    // Helper para generar iconos en JS
    const Icon = {
        get: (name, style = '') => {
            const icons = {
                'user': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
                'calendar': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                'file-text': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>'
            };
            return icons[name] || '';
        }
    };
    </script>
</body>
</html>
