<?php
/**
 * Módulo de Reportes
 * Sistema ISPEB - Gestión de Expedientes Digitales
 * 
 * Dashboard de reportes y generación de documentos PDF
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        .page-header {
            margin-bottom: 40px;
        }

        .header-title {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 12px 0;
        }

        .header-subtitle {
            font-size: 16px;
            color: #718096;
            margin: 0;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .report-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: #e2e8f0;
        }

        .report-card-header {
            padding: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .report-card:nth-child(2) .report-card-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .report-card:nth-child(3) .report-card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .report-icon {
            font-size: 48px;
            margin-bottom: 12px;
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
            background: #f7fafc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #4a5568;
        }

        .report-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .report-card:nth-child(2) .report-button {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .report-card:nth-child(3) .report-button {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .report-button:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stats-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 32px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include '../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../layout/header.php'; ?>

        <div class="content-wrapper">
            <!-- Header -->
            <div class="page-header">
                <h1 class="header-title">📊 Reportes y Documentos</h1>
                <p class="header-subtitle">Genere constancias, listados y reportes del sistema</p>
            </div>

            <!-- Estadísticas Banner -->
            <div class="stats-banner">
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($total_funcionarios); ?></div>
                    <div class="stat-label">Funcionarios Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($total_movimientos); ?></div>
                    <div class="stat-label">Movimientos <?php echo date('Y'); ?></div>
                </div>
            </div>

            <!-- Grid de Reportes -->
            <div class="reports-grid">
                <!-- Constancia de Trabajo -->
                <div class="report-card">
                    <div class="report-card-header">
                        <div class="report-icon">📄</div>
                        <h3 class="report-title">Constancia de Trabajo</h3>
                        <p class="report-description">Genera constancias laborales para funcionarios activos</p>
                    </div>
                    <div class="report-card-body">
                        <div class="report-info">
                            📝 Documento oficial que certifica la relación laboral, cargo y antigüedad del funcionario.
                        </div>
                        <button class="report-button" onclick="abrirFormConstancia()">
                            <span>📄</span>
                            Generar Constancia
                        </button>
                    </div>
                </div>

                <!-- Listado de Personal -->
                <div class="report-card">
                    <div class="report-card-header">
                        <div class="report-icon">👥</div>
                        <h3 class="report-title">Listado de Personal</h3>
                        <p class="report-description">Genera listados completos del personal</p>
                    </div>
                    <div class="report-card-body">
                        <div class="report-info">
                            📋 Reporte completo con todos los funcionarios activos, sus cargos y departamentos.
                        </div>
                        <button class="report-button" onclick="abrirFormListado()">
                            <span>👥</span>
                            Generar Listado
                        </button>
                    </div>
                </div>

                <!-- Historial de Movimientos -->
                <div class="report-card">
                    <div class="report-card-header">
                        <div class="report-icon">📊</div>
                        <h3 class="report-title">Historial de Movimientos</h3>
                        <p class="report-description">Reportes de nombramientos, traslados y más</p>
                    </div>
                    <div class="report-card-body">
                        <div class="report-info">
                            📈 Reporte detallado del historial administrativo de un funcionario específico.
                        </div>
                        <button class="report-button" onclick="abrirFormHistorial()">
                            <span>📊</span>
                            Generar Historial
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Abre formulario para Constancia de Trabajo
         */
        async function abrirFormConstancia() {
            // Cargar funcionarios activos
            const funcionariosRes = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/listar.php');
            const funcionariosData = await funcionariosRes.json();

            if (!funcionariosData.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }

            const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');

            const { value: formValues } = await Swal.fire({
                title: '📄 Constancia de Trabajo',
                html: `
                    <div style="text-align: left;">
                        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                            Seleccione el funcionario para generar la constancia laboral.
                        </p>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `
                                    <option value="${f.id}">
                                        ${f.nombres} ${f.apellidos} - ${f.cedula} (${f.nombre_cargo})
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
                confirmButtonColor: '#667eea',
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
                // Redirigir a generar_pdf.php con parámetros
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
                title: '👥 Listado de Personal',
                html: `
                    <div style="text-align: left;">
                        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                            Configure el reporte de personal que desea generar.
                        </p>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Estado</label>
                            <select id="swal-estado" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="activo">Solo Activos</option>
                                <option value="todos">Todos los Estados</option>
                                <option value="inactivo">Solo Inactivos</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Ordenar Por</label>
                            <select id="swal-orden" class="swal2-input" style="width: 100%; padding: 10px;">
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
                confirmButtonColor: '#f093fb',
                preConfirm: () => {
                    return {
                        estado: document.getElementById('swal-estado').value,
                        orden: document.getElementById('swal-orden').value
                    };
                }
            });

            if (formValues) {
                // Redirigir a generar_pdf.php con parámetros
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
            // Cargar funcionarios
            const funcionariosRes = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/listar.php');
            const funcionariosData = await funcionariosRes.json();

            if (!funcionariosData.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }

            const funcionarios = funcionariosData.data;

            const { value: formValues } = await Swal.fire({
                title: '📊 Historial de Movimientos',
                html: `
                    <div style="text-align: left;">
                        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                            Genere el historial completo de movimientos administrativos de un funcionario.
                        </p>
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
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Tipo de Eventos</label>
                            <select id="swal-tipo" class="swal2-input" style="width: 100%; padding: 10px;">
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
                confirmButtonColor: '#4facfe',
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
                // Redirigir a generar_pdf.php con parámetros
                window.open(
                    `generar_pdf.php?tipo=historial&funcionario_id=${formValues.funcionario_id}&evento=${formValues.tipo}`,
                    '_blank'
                );
            }
        }
    </script>
</body>
</html>
