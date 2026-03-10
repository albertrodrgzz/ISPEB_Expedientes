<?php
/**
 * Vista: Módulo de Vacaciones
 * Gestión y consulta de vacaciones del personal
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder al módulo de vacaciones';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION'");
$total_empleados_con_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
$empleados_en_vacaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_totales'))), 0) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$dias_usados_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION' AND fecha_evento >= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())");
$vacaciones_programadas = $stmt->fetch()['total'];

$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener registros de vacaciones actualizando el estado dinámico basado en hoy
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
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.titulo')) as titulo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.descripcion')) as descripcion,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.nombre_archivo_original,
        ha.created_at,
        CASE 
            WHEN CURDATE() < ha.fecha_evento THEN 'Programada'
            WHEN CURDATE() >= ha.fecha_evento AND CURDATE() <= ha.fecha_fin THEN 'En curso'
            WHEN CURDATE() = ha.fecha_evento THEN 'Iniciando hoy'
            ELSE 'Finalizada'
        END as estado_vacacion
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'VACACION'
    ORDER BY ha.fecha_evento DESC
");
$vacaciones = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../../config/icons.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacaciones - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <link rel="stylesheet" href="../../publico/css/responsive.css">
    <link rel="stylesheet" href="../../publico/css/modern-components.css">
    <link rel="stylesheet" href="../../publico/css/swal-modern.css">
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $pageTitle = 'Gestión de Vacaciones';
        include __DIR__ . '/../layout/header.php'; 
        ?>
        
        <div class="module-container">
            <div class="module-header-title">
                <div class="module-title-group">
                    <?= Icon::get('sun') ?>
                    <h2 class="module-title-text">Vacaciones</h2>
                </div>
                <button class="btn-primary" onclick="abrirModalVacaciones()" style="padding: 10px 20px; border-radius: 8px;">
                    <?= Icon::get('plus') ?> Nueva Solicitud
                </button>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card-solid bg-solid-blue">
                    <div class="kpi-icon"><?= Icon::get('users') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Empleados Histórico</div>
                        <div class="kpi-value"><?= $total_empleados_con_vacaciones ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-green">
                    <div class="kpi-icon"><?= Icon::get('sun') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">De Vacaciones Hoy</div>
                        <div class="kpi-value"><?= $empleados_en_vacaciones ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-orange">
                    <div class="kpi-icon"><?= Icon::get('clock') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Días Usados (<?= date('Y') ?>)</div>
                        <div class="kpi-value"><?= $dias_usados_anio ?></div>
                    </div>
                </div>
                
                <div class="kpi-card-solid bg-solid-purple">
                    <div class="kpi-icon"><?= Icon::get('calendar') ?></div>
                    <div class="kpi-details">
                        <div class="kpi-label">Programadas</div>
                        <div class="kpi-value"><?= $vacaciones_programadas ?></div>
                    </div>
                </div>
            </div>

            <div class="flat-filter-bar" style="margin-top: 32px;">
                <div class="filter-item" style="flex: 2;">
                    <label class="filter-label">BUSCAR</label>
                    <input type="text" id="searchVacaciones" class="form-control" placeholder="Buscar funcionario...">
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">DEPARTAMENTO</label>
                    <select id="filter-departamento" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($departamentos as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['nombre']); ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1.5;">
                    <label class="filter-label">ESTADO</label>
                    <select id="filter-estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="Programada">Programada</option>
                        <option value="En curso">En curso</option>
                        <option value="Finalizada">Finalizada</option>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1;">
                    <label class="filter-label">AÑO</label>
                    <select id="filter-anio" class="form-control">
                        <option value="">Todos</option>
                        <?php 
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--): 
                        ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 0 0 auto;">
                    <button type="button" onclick="limpiarFiltros()" class="btn-flat-outline">Limpiar</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table-modern" id="vacacionesTable">
                    <thead>
                        <tr>
                            <th>Funcionario</th>
                            <th>Cédula</th>
                            <th>Departamento</th>
                            <th>Período</th>
                            <th>Días</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vacaciones)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-st">
                                        <div class="empty-st-ico"><?= Icon::get('sun') ?></div>
                                        <p class="empty-state-title">Sin registros de vacaciones</p>
                                        <p class="empty-state-desc">Las vacaciones registradas aparecerán aquí.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vacaciones as $vac):
                                $nombre_completo = $vac['nombres'] . ' ' . $vac['apellidos'];
                                $iniciales = strtoupper(mb_substr($vac['nombres'],0,1) . mb_substr($vac['apellidos'],0,1));
                                $colors = ['#0F4C81','#0288D1','#8B5CF6','#10B981','#F59E0B','#14B8A6'];
                                $color = $colors[abs(crc32($vac['cedula'])) % count($colors)];
                                $badgeStyle = match($vac['estado_vacacion']) {
                                    'En curso'       => 'background:#D1FAE5;color:#065F46;',
                                    'Iniciando hoy'  => 'background:#DBEAFE;color:#1E40AF;',
                                    'Programada'     => 'background:#FEF3C7;color:#92400E;',
                                    default      => 'background:#F1F5F9;color:#475569;'
                                };
                            ?>
                                <tr class="vacacion-row"
                                    data-empleado="<?php echo strtolower($nombre_completo . ' ' . $vac['cedula']); ?>"
                                    data-departamento="<?php echo $vac['departamento']; ?>"
                                    data-estado="<?php echo $vac['estado_vacacion']; ?>"
                                    data-anio="<?php echo date('Y', strtotime($vac['fecha_inicio'])); ?>">
                                    <td>
                                        <div class="fn-cell">
                                            <div class="fn-avatar" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc)">
                                                <?= $iniciales ?>
                                            </div>
                                            <div class="fn-info">
                                                <strong><?php echo htmlspecialchars($nombre_completo); ?></strong>
                                                <small><span class="fn-cedula"><?php echo htmlspecialchars($vac['cedula']); ?></span></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size:13px;color:#64748B;"><?php echo htmlspecialchars($vac['departamento']); ?></td>
                                    <td>
                                        <span style="font-size:13px;white-space:nowrap;">
                                            <?php echo date('d/m/Y', strtotime($vac['fecha_inicio'])); ?>
                                            <span style="color:#CBD5E1;margin:0 3px;">→</span>
                                            <?php echo date('d/m/Y', strtotime($vac['fecha_fin'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background:#F1F5F9;padding:3px 9px;border-radius:6px;font-weight:700;font-size:12px;color:#475569;white-space:nowrap;">
                                            <?php echo $vac['dias_totales']; ?>d
                                        </span>
                                    </td>
                                    <td>
                                        <span style="<?= $badgeStyle ?>padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;white-space:nowrap;">
                                            <?php echo $vac['estado_vacacion']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="tbl-actions">
                                            <a href="../funcionarios/ver.php?id=<?php echo $vac['funcionario_id']; ?>"
                                               class="btn-ic ic-view" title="Ver Expediente">
                                                <?= Icon::get('eye') ?>
                                            </a>
                                            <?php if ($vac['ruta_archivo']): ?>
                                                <a href="../../<?php echo $vac['ruta_archivo']; ?>" target="_blank"
                                                   class="btn-ic ic-pdf" title="Descargar PDF">
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
    
    <script src="../../publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        document.getElementById('searchVacaciones')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#vacacionesTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
        
        function limpiarFiltros() {
            document.getElementById('searchVacaciones').value = '';
            document.getElementById('filter-departamento').value = '';
            document.getElementById('filter-estado').value = '';
            document.getElementById('filter-anio').value = '';
            aplicarFiltros();
        }
        
        function aplicarFiltros() {
            const search = document.getElementById('searchVacaciones').value.toLowerCase();
            const dept = document.getElementById('filter-departamento').value;
            const est = document.getElementById('filter-estado').value;
            const anio = document.getElementById('filter-anio').value;
            
            document.querySelectorAll('.vacacion-row').forEach(row => {
                const matchSearch = row.dataset.empleado.includes(search);
                const matchDept = !dept || row.dataset.departamento === dept;
                const matchEstado = !est || row.dataset.estado === est;
                const matchAnio = !anio || row.dataset.anio === anio;
                row.style.display = (matchSearch && matchDept && matchEstado && matchAnio) ? '' : 'none';
            });
        }
        
        document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-estado')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-anio')?.addEventListener('change', aplicarFiltros);
        
        let datosVacaciones = null;

        async function abrirModalVacaciones() {
            try {
                const res = await fetch('../funcionarios/ajax/listar.php');
                const result = await res.json();
                const funcionarios = result.data.filter(f => f.estado === 'activo');
                
                const { value: formValues } = await Swal.fire({
                    title: '<div style="display: flex; align-items: center; gap: 12px; justify-content: center; font-size: 22px; font-weight: 700; color: #1e293b;"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span>Nueva Vacación</span></div>',
                    html: `
                        <style>
                            .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
                            .file-input-button { width: 100%; padding: 11px 14px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 9px; font-weight: 500; color: #475569; font-size: 13px; }
                            .file-input-button:hover { border-color: #0F4C81; background: #eff6ff; color: #0F4C81; }
                            .file-input-button.has-file { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-color: #06d6a0; color: #059669; }
                            .file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
                            .info-box { background: linear-gradient(135deg, #1e3a5f 0%, #1565a0 100%); border-left: 3px solid #f59e0b; padding: 11px 14px; border-radius: 8px; margin-top: 10px; }
                            .info-box-content { display: flex; align-items: start; gap: 9px; font-size: 11.5px; color: rgba(255,255,255,0.88); line-height: 1.5; }
                            .lottt-card { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #06d6a0; border-radius: 12px; padding: 18px; margin-bottom: 18px; display: none; }
                            .lottt-title { font-size: 13px; color: #047857; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
                            .lottt-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
                            .lottt-stat { text-align: center; }
                            .lottt-stat-label { font-size: 11px; color: #059669; font-weight: 500; margin-bottom: 4px; }
                            .lottt-stat-value { font-size: 24px; font-weight: 700; color: #047857; }
                            .return-date-card { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 3px solid #3b82f6; padding: 14px; border-radius: 8px; margin-bottom: 18px; display: none; }
                            .return-date-content { display: flex; align-items: center; gap: 10px; color: #1e40af; font-size: 13.5px; font-weight: 600; }
                            .error-message { background: #fee2e2; border-left: 3px solid #ef4444; padding: 11px 14px; border-radius: 8px; margin-bottom: 18px; display: none; color: #991b1b; font-size: 12px; font-weight: 500; }
                        </style>
                        <div style="max-width: 750px; margin: 0 auto;">
                            <div class="swal-form-group" style="margin-bottom: 18px;">
                                <label class="swal-label">Funcionario <span style="color: #ef4444;">*</span></label>
                                <select id="swal-funcionario" class="swal2-select">
                                    <option value="">Seleccionar...</option>
                                    ${funcionarios.map(f => `<option value="${f.id}">${f.nombres} ${f.apellidos} - ${f.cedula}</option>`).join('')}
                                </select>
                            </div>

                            <div id="error-message" class="error-message"></div>

                            <div id="lottt-card" class="lottt-card">
                                <div class="lottt-title">Derecho a Vacaciones (LOTTT)</div>
                                <div class="lottt-stats">
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Años Servicio</div>
                                        <div class="lottt-stat-value" id="años-servicio">-</div>
                                    </div>
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Días Totales</div>
                                        <div class="lottt-stat-value" id="dias-totales">-</div>
                                    </div>
                                    <div class="lottt-stat">
                                        <div class="lottt-stat-label">Disponibles</div>
                                        <div class="lottt-stat-value" id="dias-disponibles">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="swal-form-grid-2col">
                                <div class="swal-form-group">
                                    <label class="swal-label">Fecha Inicio <span style="color: #ef4444;">*</span></label>
                                    <input type="date" id="swal-fecha-inicio" class="swal2-input" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="swal-form-group">
                                    <label class="swal-label">Días a Tomar <span style="color: #ef4444;">*</span></label>
                                    <select id="swal-dias-selector" class="swal2-select" disabled>
                                        <option value="">Primero seleccione funcionario...</option>
                                    </select>
                                </div>
                            </div>

                            <div id="return-date-card" class="return-date-card">
                                <div class="return-date-content">
                                    <div>Fecha de retorno al trabajo: <strong id="fecha-retorno-display">-</strong></div>
                                </div>
                            </div>

                            <div class="swal-form-grid-2col">
                                <div class="swal-form-group">
                                    <label class="swal-label">Observaciones</label>
                                    <textarea id="swal-observaciones" class="swal2-textarea" placeholder="Motivo o comentarios..."></textarea>
                                </div>
                                <div class="swal-form-group">
                                    <label class="swal-label">Documento Aval <span style="color: #ef4444;">*</span></label>
                                    <div class="file-input-wrapper">
                                        <label class="file-input-button" id="file-label" for="swal-pdf">
                                            <span id="file-label-text">Seleccionar archivo</span>
                                        </label>
                                        <input type="file" id="swal-pdf" accept="application/pdf,image/png,image/jpeg,image/jpg">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '850px',
                    showCancelButton: true,
                    confirmButtonText: 'Registrar Vacación',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#06d6a0',
                    cancelButtonColor: '#64748b',
                    didOpen: () => {
                        const funcionarioSelect = document.getElementById('swal-funcionario');
                        const errorMessage = document.getElementById('error-message');
                        const lotttCard = document.getElementById('lottt-card');
                        const diasSelector = document.getElementById('swal-dias-selector');
                        const fechaInicio = document.getElementById('swal-fecha-inicio');
                        const returnDateCard = document.getElementById('return-date-card');
                        const fileInput = document.getElementById('swal-pdf');
                        const fileLabelText = document.getElementById('file-label-text');

                        funcionarioSelect.addEventListener('change', async (e) => {
                            if (!e.target.value) return;
                            const res = await fetch(`ajax/calcular_dias_vacaciones.php?funcionario_id=${e.target.value}`);
                            const data = await res.json();
                            datosVacaciones = data;
                            
                            if (!data.cumple_requisito) {
                                lotttCard.style.display = 'none';
                                errorMessage.textContent = '⚠️ El funcionario no cumple 1 año de servicio.';
                                errorMessage.style.display = 'block';
                                diasSelector.disabled = true;
                            } else {
                                errorMessage.style.display = 'none';
                                lotttCard.style.display = 'block';
                                document.getElementById('años-servicio').textContent = data.data.años_servicio;
                                document.getElementById('dias-totales').textContent = data.data.dias_totales;
                                document.getElementById('dias-disponibles').textContent = data.data.dias_disponibles;
                                
                                diasSelector.disabled = false;
                                let options = '<option value="">Seleccione días...</option>';
                                for (let i = 1; i <= data.data.dias_disponibles; i++) {
                                    options += `<option value="${i}">${i} día${i > 1 ? 's' : ''}</option>`;
                                }
                                diasSelector.innerHTML = options;
                            }
                        });

                        const calcularRetorno = async () => {
                            if (!diasSelector.value || !fechaInicio.value) return;
                            const res = await fetch(`ajax/calcular_fecha_retorno.php?fecha_inicio=${fechaInicio.value}&dias_habiles=${diasSelector.value}`);
                            const data = await res.json();
                            if(data.success) {
                                document.getElementById('fecha-retorno-display').textContent = data.data.fecha_retorno_formateada;
                                returnDateCard.style.display = 'block';
                            }
                        };

                        diasSelector.addEventListener('change', calcularRetorno);
                        fechaInicio.addEventListener('change', calcularRetorno);

                        fileInput.addEventListener('change', (e) => {
                            if (e.target.files.length > 0) {
                                fileLabelText.textContent = e.target.files[0].name;
                                document.getElementById('file-label').classList.add('has-file');
                            }
                        });
                    },
                    preConfirm: () => {
                        return {
                            funcionario_id: document.getElementById('swal-funcionario').value,
                            fecha_inicio: document.getElementById('swal-fecha-inicio').value,
                            dias_solicitar: document.getElementById('swal-dias-selector').value,
                            observaciones: document.getElementById('swal-observaciones').value,
                            archivo_pdf: document.getElementById('swal-pdf').files[0]
                        };
                    }
                });

                if (formValues) {
                    const formData = new FormData();
                    formData.append('accion', 'registrar_vacacion');
                    for (const key in formValues) formData.append(key, formValues[key]);

                    const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    if (result.success) {
                        Swal.fire({ icon: 'success', title: 'Vacación Registrada' }).then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: result.error });
                    }
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
            }
        }
    </script>
</body>
</html>