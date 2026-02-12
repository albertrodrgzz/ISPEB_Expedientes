<?php
/**
 * Módulo de Renuncias
 * Sistema SIGED - Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'RENUNCIA'");
$total_renuncias = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'RENUNCIA' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$renuncias_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'RENUNCIA' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$renuncias_mes = $stmt->fetch()['total'];

// Obtener renuncias
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento as fecha_renuncia,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'RENUNCIA'
    ORDER BY ha.fecha_evento DESC
");
$renuncias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renuncias - <?= APP_NAME ?></title>
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
        <div class="page-header">
            <h1>
                <?= Icon::get('log-out') ?>
                Renuncias
            </h1>
            <button class="btn-primary" onclick="abrirModalRenuncia()">
                <?= Icon::get('plus') ?>
                Procesar Renuncia
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon gradient-blue">
                    <?= Icon::get('log-out') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_renuncias) ?></div>
                    <div class="kpi-label">Total Renuncias</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-cyan">
                    <?= Icon::get('calendar') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($renuncias_anio) ?></div>
                    <div class="kpi-label">Renuncias <?= date('Y') ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-orange">
                    <?= Icon::get('clock') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($renuncias_mes) ?></div>
                    <div class="kpi-label">Renuncias Este Mes</div>
                </div>
            </div>
        </div>

        <!-- Tabla de registros -->
        <div class="card-modern">
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--color-text-light); margin-bottom: 6px; text-transform: uppercase;">
                            <?= Icon::get('search', 'width: 14px; height: 14px; display: inline; vertical-align: middle;') ?>
                            Buscar Renuncia
                        </label>
                        <input type="text" 
                               id="buscarRenuncia" 
                               class="search-input" 
                               placeholder="Cédula, nombre, departamento...">
                    </div>
                </div>

                <div class="table-wrapper">
                    <table id="tablaRenuncias" class="table-modern">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Renuncia</th>
                                <th>Motivo</th>
                                <th style="text-align: center; width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($renuncias)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('log-out', 'width: 64px; height: 64px; opacity: 0.3;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay renuncias registradas</div>
                                            <p class="empty-state-description">Las renuncias aparecerán aquí una vez que sean procesadas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($renuncias as $ren): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($ren['nombres'] . ' ' . $ren['apellidos']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($ren['cedula']) ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?= htmlspecialchars($ren['departamento'] ?? 'N/A') ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($ren['fecha_renuncia'])) ?></td>
                                        <td><?= htmlspecialchars(substr($ren['motivo'], 0, 50)) . (strlen($ren['motivo']) > 50 ? '...' : '') ?></td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 8px;">
                                                <a href="../funcionarios/ver.php?id=<?= $ren['funcionario_id'] ?>" 
                                                   class="btn-icon" 
                                                   title="Ver funcionario">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if ($ren['ruta_archivo']): ?>
                                                    <a href="<?= APP_URL . '/' . $ren['ruta_archivo'] ?>" 
                                                       target="_blank" 
                                                       class="btn-icon" 
                                                       title="Ver documento">
                                                        <?= Icon::get('file-text') ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
        module: 'renuncias',
        searchId: 'buscarRenuncia',
        tableBodySelector: '#tablaRenuncias tbody',
        countSelector: null
    });

    /**
     * MODAL DE PROCESAMIENTO DE RENUNCIA
     */
    async function abrirModalRenuncia() {
        const funcionariosResponse = await fetch('<?= APP_URL ?>/vistas/funcionarios/ajax/listar.php');
        const funcionarios = await funcionariosResponse.json();

        if (!funcionarios.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los funcionarios' });
            return;
        }

        const funcionariosActivos = funcionarios.data.filter(f => f.estado === 'activo');
        if (funcionariosActivos.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin Funcionarios', text: 'No hay funcionarios activos' });
            return;
        }

        Swal.fire({
            title: 'Procesar Renuncia',
            html: `
                <div class="swal-hint" style="background: #eff6ff; border: 2px solid #93c5fd; padding: 14px; margin-bottom: 18px;">
                    ${Icon.get('alert-circle')} <strong>Importante:</strong> El funcionario será DESACTIVADO en el sistema al confirmar esta renuncia.
                </div>
                
                <div class="swal-form-grid">
                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">
                            ${Icon.get('user')}
                            Funcionario que Renuncia *
                        </label>
                        <select id="swal-funcionario" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione...</option>
                            ${funcionariosActivos.map(f => `
                                <option value="${f.id}">${f.nombres} ${f.apellidos} (${f.cedula})</option>
                            `).join('')}
                        </select>
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('calendar')}
                            Fecha de Renuncia *
                        </label>
                        <input type="date" id="swal-fecha" class="swal2-input" style="width: 95%;" value="${new Date().toISOString().split('T')[0]}">
                    </div>

                    <div class="swal-field">
                        <label class="swal-label">
                            ${Icon.get('file-text')}
                            Carta de Renuncia (Opcional)
                        </label>
                        <input type="file" id="swal-archivo" accept=".pdf" class="swal2-file" style="width: 100%;">
                        <small style="color: #64748B; font-size: 12px;">Máximo 5MB</small>
                    </div>

                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">
                            ${Icon.get('file-text')}
                            Motivo / Observaciones *
                        </label>
                        <textarea id="swal-motivo" class="swal2-textarea" rows="3" placeholder="Ejemplo: Renuncia voluntaria - Motivos personales" style="width: 95%;"></textarea>
                        <small style="color: #64748B; font-size: 12px;">Mínimo 15 caracteres</small>
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Procesar Renuncia',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                const fecha_evento = document.getElementById('swal-fecha').value;
                const motivo = document.getElementById('swal-motivo').value.trim();
                const archivo = document.getElementById('swal-archivo').files[0];

                if (!funcionario_id) {
                    Swal.showValidationMessage('Seleccione un funcionario');
                    return false;
                }
                if (!fecha_evento) {
                    Swal.showValidationMessage('Ingrese la fecha');
                    return false;
                }
                if (!motivo || motivo.length < 15) {
                    Swal.showValidationMessage('El motivo debe tener al menos 15 caracteres');
                    return false;
                }
                if (archivo && archivo.size > 5 * 1024 * 1024) {
                    Swal.showValidationMessage('Archivo muy grande (máx 5MB)');
                    return false;
                }

                return { funcionario_id, fecha_evento, motivo, archivo };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                registrarRenuncia(result.value);
            }
        });
    }

    async function registrarRenuncia(data) {
        Swal.fire({
            title: 'Procesando...',
            html: 'Registrando renuncia...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('accion', 'registrar_despido');
            formData.append('funcionario_id', data.funcionario_id);
            formData.append('tipo_evento', 'RENUNCIA');
            formData.append('fecha_evento', data.fecha_evento);
            formData.append('motivo', data.motivo);
            if (data.archivo) formData.append('archivo_pdf', data.archivo);

            const response = await fetch('../funcionarios/ajax/gestionar_historial.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Renuncia Procesada',
                    html: `
                        <p>La renuncia se registró exitosamente.</p>
                        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                            <p style="margin: 0; font-size: 13px;"><strong>✓ Funcionario:</strong> Inactivo</p>
                            <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Usuario:</strong> Desactivado</p>
                        </div>
                    `
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.error || 'Error al procesar'
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
                'file-text': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'alert-circle': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' + style + '" width="18" height="18"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
            };
            return icons[name] || '';
        }
    };
    </script>
</body>
</html>
