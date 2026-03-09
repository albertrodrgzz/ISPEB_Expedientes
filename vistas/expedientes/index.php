<?php
/**
 * Vista: Expedientes Digitales
 * Diseño idéntico al módulo de Funcionarios
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

$db = getDB();

// Estadísticas
$stats_nombramientos  = (int)$db->query("SELECT COUNT(*) FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'")->fetchColumn();
$stats_vacaciones     = (int)$db->query("SELECT COUNT(*) FROM historial_administrativo WHERE tipo_evento = 'VACACION'")->fetchColumn();
$stats_amonestaciones = (int)$db->query("SELECT COUNT(*) FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION'")->fetchColumn();
$stats_traslados      = (int)$db->query("SELECT COUNT(*) FROM historial_administrativo WHERE tipo_evento = 'TRASLADO'")->fetchColumn();
$stats_total          = $stats_nombramientos + $stats_vacaciones + $stats_amonestaciones + $stats_traslados;

// Lista de funcionarios para filtro
$funcionarios_lista = $db->query("SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre_completo FROM funcionarios ORDER BY nombres, apellidos")->fetchAll();

// Consulta de documentos
$stmt = $db->query("
    SELECT
        ha.id,
        ha.funcionario_id,
        ha.tipo_evento,
        CASE
            WHEN ha.tipo_evento = 'NOMBRAMIENTO'  THEN COALESCE(NULLIF(CONCAT('Nombramiento - ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.categoria'))), 'Nombramiento - null'), 'Nombramiento')
            WHEN ha.tipo_evento = 'VACACION'      THEN COALESCE(NULLIF(CONCAT('Vacaciones - ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.periodo'))), 'Vacaciones - null'), 'Vacación')
            WHEN ha.tipo_evento = 'AMONESTACION'  THEN COALESCE(NULLIF(CONCAT('Amonestación - ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta'))), 'Amonestación - null'), 'Amonestación')
            WHEN ha.tipo_evento = 'TRASLADO'      THEN 'Traslado'
            WHEN ha.tipo_evento = 'REMOCION'      THEN 'Remoción'
            ELSE ha.tipo_evento
        END AS titulo,
        ha.fecha_evento,
        ha.fecha_fin,
        ha.ruta_archivo_pdf,
        ha.created_at,
        CONCAT(f.nombres, ' ', f.apellidos) AS nombre_funcionario,
        f.cedula,
        c.nombre_cargo,
        d.nombre AS departamento
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    INNER JOIN cargos c ON f.cargo_id = c.id
    INNER JOIN departamentos d ON f.departamento_id = d.id
    ORDER BY ha.created_at DESC
    LIMIT 500
");
$documentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expedientes Digitales - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <script src="<?= APP_URL ?>/publico/js/filtros-tiempo-real.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php 
        $pageTitle = Icon::get('file') . ' Expedientes Digitales';
        include __DIR__ . '/../layout/header.php'; 
        ?>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card kpi-primary">
                <div class="kpi-icon">
                    <?= Icon::get('file') ?>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Total Documentos</div>
                    <div class="kpi-value"><?= $stats_total ?></div>
                </div>
            </div>

            <div class="kpi-card kpi-info">
                <div class="kpi-icon">
                    <?= Icon::get('clipboard') ?>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Nombramientos</div>
                    <div class="kpi-value"><?= $stats_nombramientos ?></div>
                </div>
            </div>

            <div class="kpi-card kpi-success">
                <div class="kpi-icon">
                    <?= Icon::get('sun') ?>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Vacaciones</div>
                    <div class="kpi-value"><?= $stats_vacaciones ?></div>
                </div>
            </div>

            <div class="kpi-card kpi-warning">
                <div class="kpi-icon">
                    <?= Icon::get('alert-triangle') ?>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Amonestaciones</div>
                    <div class="kpi-value"><?= $stats_amonestaciones ?></div>
                </div>
            </div>

            <div class="kpi-card kpi-danger">
                <div class="kpi-icon">
                    <?= Icon::get('arrow-right') ?>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Traslados</div>
                    <div class="kpi-value"><?= $stats_traslados ?></div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card-modern">
            <div class="card-body" style="padding: 20px;">
                <div class="filters-grid" style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 100px; gap: 16px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block; color: var(--color-text-light);">BUSCAR</label>
                        <input type="text" id="buscarExpediente" class="form-control" placeholder="Nombre, cédula, tipo..." style="padding: 8px 12px; height: 38px; font-size: 14px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block; color: var(--color-text-light);">TIPO DE DOCUMENTO</label>
                        <select id="filtroTipo" class="form-control" style="padding: 8px 12px; height: 38px; font-size: 14px;">
                            <option value="">Todos</option>
                            <option value="nombramiento">Nombramiento</option>
                            <option value="vacacion">Vacaciones</option>
                            <option value="amonestacion">Amonestación</option>
                            <option value="traslado">Traslado</option>
                            <option value="remocion">Remoción</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block; color: var(--color-text-light);">FUNCIONARIO</label>
                        <select id="filtroFuncionario" class="form-control" style="padding: 8px 12px; height: 38px; font-size: 14px;">
                            <option value="">Todos</option>
                            <?php foreach ($funcionarios_lista as $func): ?>
                                <option value="<?= $func['id'] ?>"><?= htmlspecialchars($func['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <button id="btnLimpiar" class="btn-secondary" style="height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; padding: 0;">
                            <?= Icon::get('x', 'width: 16px; height: 16px; margin-right: 4px;') ?> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card-modern">
            <div class="card-body">
                <div class="table-wrapper">
                    <table id="tablaExpedientes" class="table-modern">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Fecha Fin</th>
                                <th style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documentos)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <?= Icon::get('file', 'opacity: 0.3; width: 64px; height: 64px;') ?>
                                            </div>
                                            <div class="empty-state-title">No hay documentos registrados</div>
                                            <p class="empty-state-description">Los documentos aparecerán aquí cuando se registren eventos administrativos.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($documentos as $doc): ?>
                                    <?php
                                    $tipo = strtolower($doc['tipo_evento']);
                                    $tipoLabels = [
                                        'nombramiento' => 'Nombramiento',
                                        'vacacion'     => 'Vacaciones',
                                        'amonestacion' => 'Amonestación',
                                        'traslado'     => 'Traslado',
                                        'remocion'     => 'Remoción',
                                    ];
                                    $badgeClases = [
                                        'nombramiento' => 'badge-info',
                                        'vacacion'     => 'badge-success',
                                        'amonestacion' => 'badge-warning',
                                        'traslado'     => 'badge-primary',
                                        'remocion'     => 'badge-danger',
                                    ];
                                    $tipoLabel = $tipoLabels[$tipo] ?? ucfirst($tipo);
                                    $badgeClass = $badgeClases[$tipo] ?? 'badge-info';
                                    $iniciales = strtoupper(
                                        substr($doc['nombre_funcionario'], 0, 1) .
                                        (strpos($doc['nombre_funcionario'], ' ') !== false
                                            ? substr(strstr($doc['nombre_funcionario'], ' '), 1, 1)
                                            : '')
                                    );
                                    ?>
                                    <tr data-tipo="<?= $tipo ?>" data-funcionario="<?= $doc['funcionario_id'] ?>">
                                        <td>
                                            <div style="display:flex;align-items:center;gap:12px;">
                                                <div style="width:40px;height:40px;border-radius:50%;background:var(--color-primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                                    <?= $iniciales ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight:600;color:var(--color-text);"><?= htmlspecialchars($doc['nombre_funcionario']) ?></div>
                                                    <div style="font-size:12px;color:var(--color-text-light);"><?= htmlspecialchars($doc['cedula']) ?> — <?= htmlspecialchars($doc['departamento']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>"><?= $tipoLabel ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight:500;color:var(--color-text);max-width:260px;">
                                                <?= htmlspecialchars($doc['titulo']) ?>
                                            </div>
                                        </td>
                                        <td><?= $doc['fecha_evento'] ? date('d/m/Y', strtotime($doc['fecha_evento'])) : '—' ?></td>
                                        <td><?= $doc['fecha_fin'] ? date('d/m/Y', strtotime($doc['fecha_fin'])) : '—' ?></td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;justify-content:center;gap:8px;">
                                                <a href="../funcionarios/ver.php?id=<?= $doc['funcionario_id'] ?>" class="btn-icon" title="Ver expediente">
                                                    <?= Icon::get('eye') ?>
                                                </a>
                                                <?php if ($doc['ruta_archivo_pdf']): ?>
                                                    <a href="<?= APP_URL ?>/<?= htmlspecialchars($doc['ruta_archivo_pdf']) ?>" class="btn-icon" title="Descargar PDF" target="_blank">
                                                        <?= Icon::get('download') ?>
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
                <!-- Contador -->
                <div style="padding:12px 16px;border-top:1px solid var(--color-border-light);font-size:13px;color:var(--color-text-light);">
                    Mostrando <strong id="contadorVisible">0</strong> de <strong id="contadorTotal">0</strong> documentos
                </div>
            </div>
        </div>

    </div><!-- /main-content -->

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const totalRows = document.querySelectorAll('#tablaExpedientes tbody tr[data-tipo]').length;
        document.getElementById('contadorTotal').textContent = totalRows;
        document.getElementById('contadorVisible').textContent = totalRows;

        if (typeof initTableFilters === 'function') {
            const filters = initTableFilters({
                tableId: 'tablaExpedientes',
                searchInputId: 'buscarExpediente',
                selectFilters: [
                    { id: 'filtroTipo',        dataAttribute: 'tipo' },
                    { id: 'filtroFuncionario', dataAttribute: 'funcionario' }
                ],
                onFilter: function (visible, total) {
                    document.getElementById('contadorVisible').textContent = visible;
                    document.getElementById('contadorTotal').textContent = total;
                }
            });

            document.getElementById('btnLimpiar').addEventListener('click', function () {
                if (filters && typeof filters.clearFilters === 'function') {
                    filters.clearFilters();
                }
            });
        }
    });

    /* Responsive */
    const style = document.createElement('style');
    style.innerHTML = `
        .badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-info    { background:#dbeafe; color:#1e40af; }
        .badge-success { background:#d1fae5; color:#065f46; }
        .badge-warning { background:#fef3c7; color:#92400e; }
        .badge-danger  { background:#fee2e2; color:#991b1b; }
        .badge-primary { background:#ede9fe; color:#5b21b6; }

        @media (max-width: 1024px) {
            .filters-grid { grid-template-columns: 1fr 1fr !important; }
            .filters-grid > div:last-child { grid-column: span 2; }
        }
        @media (max-width: 640px) {
            .filters-grid { grid-template-columns: 1fr !important; }
            .filters-grid > div:last-child { grid-column: span 1; }
        }
    `;
    document.head.appendChild(style);
    </script>

</body>
</html>
