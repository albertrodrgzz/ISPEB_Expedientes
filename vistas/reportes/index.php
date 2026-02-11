<?php
/**
 * Módulo de Reportes
 * Sistema SIGED - Gestión de Expedientes Digitales
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

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
        <?php include '../layout/header.php'; ?>
        
        <!-- Header con botón -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin: 0; display: flex; align-items: center; gap: 12px;">
                <?= Icon::get('pie-chart') ?>
                Reportes y Documentos
            </h1>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid" style="margin-bottom: 30px;">
            <div class="kpi-card">
                <div class="kpi-icon gradient-blue">
                    <?= Icon::get('users') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_funcionarios) ?></div>
                    <div class="kpi-label">Funcionarios Activos</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-cyan">
                    <?= Icon::get('activity') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($total_movimientos) ?></div>
                    <div class="kpi-label">Movimientos <?= date('Y') ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon gradient-orange">
                    <?= Icon::get('file-text') ?>
                </div>
                <div class="kpi-details">
                    <div class="kpi-value"><?= number_format($tipos_eventos) ?></div>
                    <div class="kpi-label">Tipos de Eventos</div>
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
                    <button class="report-button" onclick="abrirFormConstancia()">
                        <?= Icon::get('file-text') ?>
                        Generar Constancia
                    </button>
                </div>
            </div>

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
     * Abre formulario para Listado de Personal
     */
    async function abrirFormListado() {
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
                </div>
            `,
            width: '500px',
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return {
                    estado: document.getElementById('swal-estado').value,
                    orden: document.getElementById('swal-orden').value
                };
            }
        });

        if (formValues) {
            window.open(
                `generar_pdf.php?tipo=listado&estado=${formValues.estado}&orden=${formValues.orden}`,
                '_blank'
            );
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
    </script>
</body>
</html>
