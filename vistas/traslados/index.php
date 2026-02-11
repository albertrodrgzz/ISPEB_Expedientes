<?php
/**
 * Módulo de Traslados
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslados - <?= APP_NAME ?></title>
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
                <?= Icon::get('arrow-right-circle') ?>
                Traslados
            </h1>
            <button class="btn-primary" onclick="abrirModalTraslado()">
                <?= Icon::get('plus') ?>
                Nuevo Traslado
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid" style="margin-bottom: 30px;">
            <div class="kpi-card">
                <div class="kpi-icon gradient-blue">
                    <?= Icon::get('arrow-right-circle') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_traslados) ?></div>
                    <div class="kpi-label">Total Traslados</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-cyan">
                    <?= Icon::get('calendar') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($traslados_anio) ?></div>
                    <div class="kpi-label">Traslados <?= date('Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card-modern">
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <input type="text" 
                           id="buscarTraslado" 
                           class="search-input" 
                           placeholder="Buscar por cédula, nombre, departamento...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaTraslados" class="table-modern">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Desde</th>
                                <th>Hacia</th>
                                <th>Motivo</th>
                                <th style="text-align: center; width: 100px;">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($traslados)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('arrow-right-circle', 'width: 64px; height: 64px; opacity: 0.3;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay traslados registrados</div>
                                            <p class="empty-state-description">Los traslados aparecerán aquí una vez que sean registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($traslados as $tras): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($tras['fecha_evento'])) ?></td>
                                        <td><strong><?= htmlspecialchars($tras['nombres'] . ' ' . $tras['apellidos']) ?></strong></td>
                                        <td><?= htmlspecialchars($tras['cedula']) ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?= htmlspecialchars($tras['departamento_origen'] ?? '-') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary"><?= htmlspecialchars($tras['departamento_destino'] ?? '-') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($tras['motivo'], 0, 40)) . (strlen($tras['motivo']) > 40 ? '...' : '') ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($tras['ruta_archivo_pdf']): ?>
                                                <a href="<?= APP_URL . '/' . $tras['ruta_archivo_pdf'] ?>" 
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
        module: 'traslados',
        searchId: 'buscarTraslado',
        tableBodySelector: '#tablaTraslados tbody',
        countSelector: null
    });

    /**
     * MODAL DE REGISTRO DE TRASLADO
     */
    async function abrirModalTraslado() {
        // Cargar funcionarios activos
        const funcionariosRes = await fetch('<?= APP_URL ?>/vistas/funcionarios/ajax/listar.php');
        const funcionariosData = await funcionariosRes.json();

        if (!funcionariosData.success) {
            Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
            return;
        }

        // Cargar departamentos
        const departamentosRes = await fetch('<?= APP_URL ?>/config/get_departamentos.php');
        const departamentosData = await departamentosRes.json();

        const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');
        const departamentos = departamentosData.data || [];

        Swal.fire({
            title: 'Registrar Traslado',
            html: `
                <div class="swal-form-grid swal-form-grid-2col">
                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('user')}
                            Funcionario *
                        </label>
                        <select id="swal-funcionario" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione un funcionario</option>
                            ${funcionarios.map(f => `
                                <option value="${f.id}" data-dept="${f.nombre_departamento || ''}">
                                    ${f.nombres} ${f.apellidos} - ${f.nombre_departamento || 'Sin dept'}
                                </option>
                            `).join('')}
                        </select>
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('building')}
                            Nuevo Departamento *
                        </label>
                        <select id="swal-departamento-destino" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione departamento</option>
                            ${departamentos.map(d => `<option value="${d.nombre}">${d.nombre}</option>`).join('')}
                        </select>
                    </div>

                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">
                            ${Icon.get('file-text')}
                            Motivo del Traslado *
                        </label>
                        <textarea id="swal-motivo" class="swal2-textarea" rows="3" placeholder="Describa el motivo del traslado..." style="width: 95%;"></textarea>
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('calendar')}
                            Fecha de Traslado *
                        </label>
                        <input type="date" id="swal-fecha" class="swal2-input" value="${new Date().toISOString().split('T')[0]}" style="width: 95%;">
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('file-text')}
                            Documento PDF (Opcional)
                        </label>
                        <input type="file" id="swal-pdf" accept=".pdf" class="swal2-file" style="width: 100%;">
                        <small style="color: #64748B; font-size: 12px;">Máximo 5MB</small>
                    </div>
                </div>
            `,
            width: '700px',
            showCancelButton: true,
            confirmButtonText: 'Registrar Traslado',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                const departamento_destino = document.getElementById('swal-departamento-destino').value;
                const motivo = document.getElementById('swal-motivo').value.trim();
                const fecha = document.getElementById('swal-fecha').value;
                const archivo_pdf = document.getElementById('swal-pdf').files[0];
                
                if (!funcionario_id) {
                    Swal.showValidationMessage('Seleccione un funcionario');
                    return false;
                }
                if (!departamento_destino) {
                    Swal.showValidationMessage('Seleccione el departamento destino');
                    return false;
                }
                if (!motivo) {
                    Swal.showValidationMessage('Ingrese el motivo del traslado');
                    return false;
                }
                if (!fecha) {
                    Swal.showValidationMessage('Ingrese la fecha del traslado');
                    return false;
                }
                if (archivo_pdf && archivo_pdf.size > 5 * 1024 * 1024) {
                    Swal.showValidationMessage('El archivo no debe superar 5MB');
                    return false;
                }
                
                // Obtener departamento origen
                const selectFunc = document.getElementById('swal-funcionario');
                const selectedOption = selectFunc.options[selectFunc.selectedIndex];
                const departamento_origen = selectedOption.getAttribute('data-dept');
                
                return { funcionario_id, departamento_origen, departamento_destino, motivo, fecha, archivo_pdf };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                registrarTraslado(result.value);
            }
        });
    }

    async function registrarTraslado(datos) {
        Swal.fire({
            title: 'Procesando...',
            html: 'Registrando traslado...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        try {
            const formData = new FormData();
            formData.append('accion', 'registrar_traslado');
            formData.append('funcionario_id', datos.funcionario_id);
            formData.append('departamento_origen', datos.departamento_origen);
            formData.append('departamento_destino', datos.departamento_destino);
            formData.append('motivo', datos.motivo);
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
                    title: 'Traslado Registrado',
                    text: 'El traslado se registró correctamente'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.error || 'Error al registrar traslado'
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
                'building': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>',
                'calendar': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                'file-text': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>'
            };
            return icons[name] || '';
        }
    };
    </script>
</body>
</html>
