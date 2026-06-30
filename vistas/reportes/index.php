<?php
/**
 * Módulo de Reportes
 * Sistema SIGED - Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

$nivel_acceso   = $_SESSION['nivel_acceso'] ?? 3;
$funcionario_id = $_SESSION['funcionario_id'] ?? 0;
$es_nivel3      = ($nivel_acceso >= 3);

// Verificar permisos (nivel 1-3 puede ver reportes)
if (!verificarNivel(3)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas rápidas
$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
$total_funcionarios = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE YEAR(created_at) = YEAR(CURDATE())");
$total_movimientos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT tipo_evento) as total FROM historial_administrativo");
$tipos_eventos = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .report-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--color-border);
        }

        .report-card-header {
            padding: 32px 24px;
            background: linear-gradient(135deg, #0F4C81 0%, #00A8E8 100%);
            color: white;
            text-align: center;
        }

        .report-card:nth-child(2) .report-card-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .report-card:nth-child(3) .report-card-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .report-icon svg {
            width: 48px;
            height: 48px;
            stroke: white;
            margin-bottom: 16px;
        }

        .report-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .report-description {
            font-size: 14px;
            opacity: 0.95;
            margin: 0;
        }

        .report-card-body {
            padding: 24px;
        }

        .report-info {
            background: var(--color-bg);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--color-text-light);
            line-height: 1.6;
        }

        .report-button {
            width: 100%;
            background: var(--gradient-brand);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .report-button:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(15, 76, 129, 0.3);
        }

        .report-card:nth-child(2) .report-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .report-card:nth-child(2) .report-button:hover {
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .report-card:nth-child(3) .report-button {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .report-card:nth-child(3) .report-button:hover {
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .report-button svg {
            width: 18px;
            height: 18px;
            stroke: white;
        }
    </style>
</head>
<body>
    <?php include '../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $pageTitle = Icon::get('pie-chart') . ' Reportes y Documentos';
        include '../layout/header.php'; 
        ?>
        
        <!-- KPI Cards -->
        <div class="kpi-grid" style="margin-bottom: 30px;">
            <div class="kpi-card-solid bg-solid-blue">
                <div class="kpi-icon">
                    <?= Icon::get('users') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-label">Funcionarios Activos</div>
                    <div class="kpi-value"><?= number_format($total_funcionarios) ?></div>
                </div>
            </div>
            <div class="kpi-card-solid bg-solid-green">
                <div class="kpi-icon">
                    <?= Icon::get('activity') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-label">Movimientos <?= date('Y') ?></div>
                    <div class="kpi-value"><?= number_format($total_movimientos) ?></div>
                </div>
            </div>
            <div class="kpi-card-solid bg-solid-orange">
                <div class="kpi-icon">
                    <?= Icon::get('file-text') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-label">Tipos de Eventos</div>
                    <div class="kpi-value"><?= number_format($tipos_eventos) ?></div>
                </div>
            </div>
        </div>

        <!-- Grid de Reportes -->
        <div class="reports-grid">
            <!-- Constancia de Trabajo -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-icon"><?= Icon::get('file-text', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Constancia de Trabajo</h3>
                    <p class="report-description">Genera constancias laborales oficiales</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Documento que certifica la relación laboral, cargo y antigüedad del funcionario.
                    </div>
                    <?php if ($es_nivel3): ?>
                        <!-- Nivel 3: genera directamente SU PROPIA constancia -->
                        <a href="<?= APP_URL ?>/vistas/reportes/constancia_trabajo.php" target="_blank" class="report-button" style="text-decoration:none;">
                            <?= Icon::get('file-text') ?>
                            Generar Mi Constancia
                        </a>
                    <?php else: ?>
                        <button class="report-button" onclick="abrirFormConstancia()">
                            <?= Icon::get('file-text') ?>
                            Generar Constancia
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$es_nivel3): ?>
            <!-- Los siguientes reportes son solo para Nivel 1/2 Administrativo -->

            <!-- Listado de Personal -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-icon"><?= Icon::get('users', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Listado de Personal</h3>
                    <p class="report-description">Listados completos del personal</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Reporte completo con funcionarios activos, cargos y departamentos.
                    </div>
                    <button class="report-button" onclick="abrirFormListado()">
                        <?= Icon::get('users') ?>
                        Generar Listado
                    </button>
                </div>
            </div>

            <!-- Historial de Movimientos -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-icon"><?= Icon::get('activity', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Historial de Movimientos</h3>
                    <p class="report-description">Reportes administrativos detallados</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Historial administrativo completo de un funcionario específico.
                    </div>
                    <button class="report-button" onclick="abrirFormHistorial()">
                        <?= Icon::get('activity') ?>
                        Generar Historial
                    </button>
                </div>
            </div>
            <!-- Reporte por Departamento -->
            <div class="report-card" style="--card-color: #7c3aed;">
                <div class="report-card-header" style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);">
                    <div class="report-icon"><?= Icon::get('grid', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Por Departamento</h3>
                    <p class="report-description">Personal agrupado por departamento</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Listado de todo el personal activo agrupado por departamento con subtotales.
                    </div>
                    <button class="report-button" style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);" onclick="abrirFormDepartamento()">
                        <?= Icon::get('grid') ?>
                        Generar Reporte
                    </button>
                </div>
            </div>

            <!-- Personal Ausente -->
            <div class="report-card" style="--card-color: #0891b2;">
                <div class="report-card-header" style="background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);">
                    <div class="report-icon"><?= Icon::get('calendar', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Personal Ausente</h3>
                    <p class="report-description">Funcionarios en vacaciones o reposo</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Muestra quienes están actualmente de vacaciones o en reposo medico.
                    </div>
                    <div style="display:flex;gap:8px;flex-direction:column;">
                        <button class="report-button" style="background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);" onclick="window.open('generar_pdf.php?tipo=ausentes','_blank')">
                            <?= Icon::get('file-text') ?> Ver PDF
                        </button>
                        <button class="report-button" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);" onclick="window.open('exportar_excel.php?tipo=ausentes','_blank')">
                            <?= Icon::get('download') ?> Exportar Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Control Vacacional -->
            <div class="report-card" style="--card-color: #16a34a;">
                <div class="report-card-header" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
                    <div class="report-icon"><?= Icon::get('sun', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Control Vacacional</h3>
                    <p class="report-description">Días LOTTT usados vs disponibles</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Días correspondientes, usados y disponibles según la LOTTT. Incluye semáforo de disponibilidad por funcionario.
                    </div>
                    <div style="display:flex;gap:8px;flex-direction:column;">
                        <button class="report-button" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);" onclick="window.open('generar_pdf.php?tipo=vacacional','_blank')">
                            <?= Icon::get('file-text') ?> Generar PDF
                        </button>
                        <button class="report-button" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); font-size:13px; padding:10px 16px;" onclick="window.open('exportar_excel.php?tipo=vacaciones','_blank')">
                            <?= Icon::get('download') ?> Exportar Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Amonestaciones -->
            <div class="report-card" style="--card-color: #dc2626;">
                <div class="report-card-header" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);">
                    <div class="report-icon"><?= Icon::get('alert-triangle', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Amonestaciones</h3>
                    <p class="report-description">Historial disciplinario del personal</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Listado de amonestaciones registradas con tipo de falta, motivo y sancion aplicada.
                    </div>
                    <button class="report-button" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);" onclick="abrirFormAmonestaciones()">
                        <?= Icon::get('alert-triangle') ?>
                        Generar Reporte
                    </button>
                </div>
            </div>

            <!-- Antiguedad -->
            <div class="report-card" style="--card-color: #d97706;">
                <div class="report-card-header" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <div class="report-icon"><?= Icon::get('award', 'width: 48px; height: 48px; stroke: white;') ?></div>
                    <h3 class="report-title">Antiguedad de Servicio</h3>
                    <p class="report-description">Ranking por anos de servicio</p>
                </div>
                <div class="report-card-body">
                    <div class="report-info">
                        <?= Icon::get('info') ?> Ranking de funcionarios activos ordenados por tiempo de servicio, con filtro de anos minimos.
                    </div>
                    <button class="report-button" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);" onclick="abrirFormAntiguedad()">
                        <?= Icon::get('award') ?>
                        Generar Reporte
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
    /**
     * Abre formulario para Constancia de Trabajo
     */
    async function abrirFormConstancia() {
        const funcionariosRes = await fetch('<?= APP_URL ?>/vistas/funcionarios/ajax/listar.php');
        const funcionariosData = await funcionariosRes.json();

        if (!funcionariosData.success) {
            Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
            return;
        }

        const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');

        const { value: formValues } = await Swal.fire({
            title: 'Constancia de Trabajo',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">Funcionario *</label>
                        <select id="swal-funcionario" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione un funcionario...</option>
                            ${funcionarios.map(f => `
                                <option value="${f.id}">
                                    ${f.nombres} ${f.apellidos} - ${f.cedula} (${f.nombre_cargo || 'Sin cargo'})
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
            `,
            width: '500px',
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                if (!funcionario_id) {
                    Swal.showValidationMessage('Seleccione un funcionario');
                    return false;
                }
                return { funcionario_id };
            }
        });

        if (formValues) {
            window.open(
                `generar_pdf.php?tipo=constancia&funcionario_id=${formValues.funcionario_id}`,
                '_blank'
            );
        }
    }

    /**
     * Abre formulario para Listado de Personal — elige PDF o Excel
     */
    async function abrirFormListado() {
        // Paso 1: recoger filtros
        const { value: formValues } = await Swal.fire({
            title: 'Listado de Personal',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field">
                        <label class="swal-label">Estado</label>
                        <select id="swal-estado" class="swal2-select" style="width: 100%;">
                            <option value="activo">Solo Activos</option>
                            <option value="todos">Todos los Estados</option>
                            <option value="inactivo">Solo Inactivos</option>
                        </select>
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Ordenar Por</label>
                        <select id="swal-orden" class="swal2-select" style="width: 100%;">
                            <option value="apellidos">Apellidos</option>
                            <option value="departamento">Departamento</option>
                            <option value="cargo">Cargo</option>
                        </select>
                    </div>
                    <div class="swal-field" style="grid-column: 1 / -1; margin-top: 8px;">
                        <label class="swal-label" style="margin-bottom:8px;display:block;">Formato de exportación</label>
                        <div style="display:flex;gap:12px;">
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #BFDBFE;border-radius:10px;cursor:pointer;background:#EFF6FF;">
                                <input type="radio" name="formato" value="pdf" checked style="accent-color:#1D4ED8;">
                                <span style="font-weight:600;color:#1D4ED8;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    PDF
                                </span>
                            </label>
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #A7F3D0;border-radius:10px;cursor:pointer;background:#ECFDF5;">
                                <input type="radio" name="formato" value="excel" style="accent-color:#059669;">
                                <span style="font-weight:600;color:#059669;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                                    Excel (.xls)
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            `,
            width: '520px',
            showCancelButton: true,
            confirmButtonText: 'Generar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const estado  = document.getElementById('swal-estado').value;
                const orden   = document.getElementById('swal-orden').value;
                const formato = document.querySelector('input[name="formato"]:checked')?.value ?? 'pdf';
                return { estado, orden, formato };
            }
        });

        if (formValues) {
            const { estado, orden, formato } = formValues;
            if (formato === 'excel') {
                window.open(
                    `exportar_excel.php?tipo=general&estado=${estado}&orden=${orden}`,
                    '_blank'
                );
            } else {
                window.open(
                    `generar_pdf.php?tipo=listado&estado=${estado}&orden=${orden}`,
                    '_blank'
                );
            }
        }
    }

    /**
     * Abre formulario para Historial de Movimientos
     */
    async function abrirFormHistorial() {
        const funcionariosRes = await fetch('<?= APP_URL ?>/vistas/funcionarios/ajax/listar.php');
        const funcionariosData = await funcionariosRes.json();

        if (!funcionariosData.success) {
            Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
            return;
        }

        const funcionarios = funcionariosData.data;

        const { value: formValues } = await Swal.fire({
            title: 'Historial de Movimientos',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">Funcionario *</label>
                        <select id="swal-funcionario" class="swal2-select" style="width: 100%;">
                            <option value="">Seleccione un funcionario...</option>
                            ${funcionarios.map(f => `
                                <option value="${f.id}">
                                    ${f.nombres} ${f.apellidos} - ${f.cedula}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="swal-field" style="grid-column: 1 / -1;">
                        <label class="swal-label">Tipo de Eventos</label>
                        <select id="swal-tipo" class="swal2-select" style="width: 100%;">
                            <option value="todos">Todos los Eventos</option>
                            <option value="NOMBRAMIENTO">Solo Nombramientos</option>
                            <option value="TRASLADO">Solo Traslados</option>
                            <option value="VACACION">Solo Vacaciones</option>
                            <option value="AMONESTACION">Solo Amonestaciones</option>
                        </select>
                    </div>
                </div>
            `,
            width: '500px',
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const funcionario_id = document.getElementById('swal-funcionario').value;
                if (!funcionario_id) {
                    Swal.showValidationMessage('Seleccione un funcionario');
                    return false;
                }
                return {
                    funcionario_id,
                    tipo: document.getElementById('swal-tipo').value
                };
            }
        });

        if (formValues) {
            window.open(
                `generar_pdf.php?tipo=historial&funcionario_id=${formValues.funcionario_id}&evento=${formValues.tipo}`,
                '_blank'
            );
        }
    }

    /**
     * Reporte por Departamento
     */
    async function abrirFormDepartamento() {
        const { value: formato } = await Swal.fire({
            title: 'Reporte por Departamento',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field" style="grid-column:1/-1;margin-top:8px;">
                        <label class="swal-label" style="margin-bottom:8px;display:block;">Formato de exportacion</label>
                        <div style="display:flex;gap:12px;">
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #BFDBFE;border-radius:10px;cursor:pointer;background:#EFF6FF;">
                                <input type="radio" name="fmt" value="pdf" checked style="accent-color:#1D4ED8;">
                                <span style="font-weight:600;color:#1D4ED8;">PDF</span>
                            </label>
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #A7F3D0;border-radius:10px;cursor:pointer;background:#ECFDF5;">
                                <input type="radio" name="fmt" value="excel" style="accent-color:#059669;">
                                <span style="font-weight:600;color:#059669;">Excel (.xls)</span>
                            </label>
                        </div>
                    </div>
                </div>
            `,
            width: '440px',
            showCancelButton: true,
            confirmButtonText: 'Generar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => document.querySelector('input[name="fmt"]:checked')?.value ?? 'pdf'
        });
        if (formato) {
            if (formato === 'excel') {
                window.open('exportar_excel.php?tipo=departamento', '_blank');
            } else {
                window.open('generar_pdf.php?tipo=departamento', '_blank');
            }
        }
    }

    /**
     * Reporte de Amonestaciones
     */
    async function abrirFormAmonestaciones() {
        const anioActual = new Date().getFullYear();
        let opts = '';
        for (let y = anioActual; y >= anioActual - 5; y--) {
            opts += `<option value="${y}">${y}</option>`;
        }
        const { value: formValues } = await Swal.fire({
            title: 'Reporte de Amonestaciones',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field" style="grid-column:1/-1;">
                        <label class="swal-label">Ano</label>
                        <select id="swal-anio" class="swal2-select" style="width:100%;">${opts}</select>
                    </div>
                    <div class="swal-field" style="grid-column:1/-1;margin-top:8px;">
                        <label class="swal-label" style="margin-bottom:8px;display:block;">Formato</label>
                        <div style="display:flex;gap:12px;">
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #FECACA;border-radius:10px;cursor:pointer;background:#FFF5F5;">
                                <input type="radio" name="fmtA" value="pdf" checked style="accent-color:#DC2626;">
                                <span style="font-weight:600;color:#DC2626;">PDF</span>
                            </label>
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #A7F3D0;border-radius:10px;cursor:pointer;background:#ECFDF5;">
                                <input type="radio" name="fmtA" value="excel" style="accent-color:#059669;">
                                <span style="font-weight:600;color:#059669;">Excel (.xls)</span>
                            </label>
                        </div>
                    </div>
                </div>
            `,
            width: '460px',
            showCancelButton: true,
            confirmButtonText: 'Generar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => ({
                anio: document.getElementById('swal-anio').value,
                formato: document.querySelector('input[name="fmtA"]:checked')?.value ?? 'pdf'
            })
        });
        if (formValues) {
            const { anio, formato } = formValues;
            if (formato === 'excel') {
                window.open(`exportar_excel.php?tipo=amonestaciones&anio=${anio}`, '_blank');
            } else {
                window.open(`generar_pdf.php?tipo=amonestaciones&anio=${anio}`, '_blank');
            }
        }
    }

    /**
     * Reporte de Antiguedad
     */
    async function abrirFormAntiguedad() {
        const { value: formValues } = await Swal.fire({
            title: 'Reporte de Antiguedad',
            html: `
                <div class="swal-form-grid">
                    <div class="swal-field" style="grid-column:1/-1;">
                        <label class="swal-label">Anos minimos de servicio (0 = todos)</label>
                        <select id="swal-anios" class="swal2-select" style="width:100%;">
                            <option value="0">Todos</option>
                            <option value="1">1 ano o mas</option>
                            <option value="3">3 anos o mas</option>
                            <option value="5">5 anos o mas</option>
                            <option value="10">10 anos o mas</option>
                            <option value="15">15 anos o mas</option>
                            <option value="20">20 anos o mas</option>
                        </select>
                    </div>
                    <div class="swal-field" style="grid-column:1/-1;margin-top:8px;">
                        <label class="swal-label" style="margin-bottom:8px;display:block;">Formato</label>
                        <div style="display:flex;gap:12px;">
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #FDE68A;border-radius:10px;cursor:pointer;background:#FFFBEB;">
                                <input type="radio" name="fmtAnt" value="pdf" checked style="accent-color:#D97706;">
                                <span style="font-weight:600;color:#D97706;">PDF</span>
                            </label>
                            <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #A7F3D0;border-radius:10px;cursor:pointer;background:#ECFDF5;">
                                <input type="radio" name="fmtAnt" value="excel" style="accent-color:#059669;">
                                <span style="font-weight:600;color:#059669;">Excel (.xls)</span>
                            </label>
                        </div>
                    </div>
                </div>
            `,
            width: '460px',
            showCancelButton: true,
            confirmButtonText: 'Generar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => ({
                anios: document.getElementById('swal-anios').value,
                formato: document.querySelector('input[name="fmtAnt"]:checked')?.value ?? 'pdf'
            })
        });
        if (formValues) {
            const { anios, formato } = formValues;
            if (formato === 'excel') {
                window.open(`exportar_excel.php?tipo=antiguedad&anios_min=${anios}`, '_blank');
            } else {
                window.open(`generar_pdf.php?tipo=antiguedad&anios_min=${anios}`, '_blank');
            }
        }
    }
    </script>
</body>
</html>
