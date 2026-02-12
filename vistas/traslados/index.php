<?php
/**
 * Módulo de Traslados
 * Versión: v7.0 - Corrección Error 500 (Procesamiento seguro en PHP)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

// Permisos
if (!verificarNivel(2)) {
    $_SESSION['error'] = 'Acceso no autorizado';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// --- 1. CARGAR DEPARTAMENTOS (Consulta Simplificada) ---
// Eliminamos "WHERE estado='activo'" para evitar error 500 si la columna no existe
$stmtDeps = $db->query("SELECT * FROM departamentos ORDER BY nombre ASC");
$departamentos = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
$departamentosJson = json_encode($departamentos);

// --- 2. ESTADÍSTICAS (KPIs) ---
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$traslados_mes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$traslados_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'TRASLADO'");
$total_historico = $stmt->fetch()['total'];

// --- 3. LISTADO DE TRASLADOS (Consulta Segura) ---
// Traemos 'detalles' puro para procesarlo en PHP y evitar errores SQL
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        ha.fecha_evento,
        ha.detalles, 
        ha.ruta_archivo_pdf,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'TRASLADO'
    ORDER BY ha.fecha_evento DESC
");
$traslados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslados - <?= APP_NAME ?></title>
    
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?= APP_URL ?>/publico/js/filtros-tiempo-real.js"></script>

    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; margin-bottom: 30px; }
        .kpi-card { background: #FFFFFF; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; flex-shrink: 0; }
        
        .gradient-purple { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
        .gradient-orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .gradient-teal { background: linear-gradient(135deg, #14B8A6, #0D9488); }
        
        .kpi-details { display: flex; flex-direction: column; }
        .kpi-value { font-size: 24px; font-weight: 700; color: #1E293B; margin: 0; line-height: 1.2; }
        .kpi-label { font-size: 13px; color: #64748B; font-weight: 600; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }

        /* Estilo Botón Archivo */
        .swal2-file { background: #ffffff !important; border: 2px solid #e2e8f0 !important; border-radius: 8px !important; padding: 10px !important; font-size: 14px !important; width: 100% !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; transition: all 0.2s !important; }
        .swal2-file:hover { border-color: #cbd5e1 !important; }
        
        /* Flecha de cambio */
        .dept-change { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .dept-arrow { color: #94A3B8; font-size: 16px; }
        .dept-old { color: #64748B; text-decoration: line-through; opacity: 0.7; font-size: 12px; }
        .dept-new { color: #0F4C81; font-weight: 600; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin: 0; display: flex; align-items: center; gap: 12px;">
                    <?= Icon::get('briefcase') ?>
                    Gestión de Traslados
                </h1>
                <button class="btn-primary" onclick="abrirModalTraslado()">
                    <?= Icon::get('plus') ?>
                    Registrar Traslado
                </button>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon gradient-purple">
                        <?= Icon::get('refresh-cw') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-value"><?= number_format($traslados_mes) ?></div>
                        <div class="kpi-label">Traslados este Mes</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-orange">
                        <?= Icon::get('calendar') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-value"><?= number_format($traslados_anio) ?></div>
                        <div class="kpi-label">Traslados este Año</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-teal">
                        <?= Icon::get('file-text') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-value"><?= number_format($total_historico) ?></div>
                        <div class="kpi-label">Total Histórico</div>
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-body">
                    <div style="margin-bottom: 20px;">
                        <input type="text" id="buscarTraslado" class="search-input" placeholder="🔍 Buscar por funcionario, cédula..." style="width: 100%; max-width: 400px;">
                    </div>
                    
                    <div class="table-wrapper">
                        <table id="tablaTraslados" class="table-modern">
                            <thead>
                                <tr>
                                    <th>Funcionario</th>
                                    <th>Cédula</th>
                                    <th>Movimiento (Departamento)</th>
                                    <th>Fecha Efectiva</th>
                                    <th>Motivo</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($traslados)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state" style="text-align: center; padding: 40px; color: #94A3B8;">
                                                <div style="margin-bottom: 10px; opacity: 0.5;">
                                                    <?= Icon::get('briefcase', 'width:64px; height:64px;') ?>
                                                </div>
                                                <div style="font-weight: 600; font-size: 16px;">No hay traslados registrados</div>
                                                <p style="font-size: 13px;">Use el botón superior para registrar uno.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($traslados as $t): 
                                        // PROCESAMIENTO SEGURO DEL JSON EN PHP
                                        $detalles = json_decode($t['detalles'], true) ?? [];
                                        
                                        // Intentamos obtener los datos con las claves correctas usadas en el backend
                                        $dep_origen = $detalles['departamento_origen'] ?? $detalles['departamento_anterior'] ?? 'N/A';
                                        $dep_destino = $detalles['departamento_destino'] ?? $detalles['departamento_nuevo'] ?? 'N/A';
                                        $motivo = $detalles['motivo'] ?? $detalles['observaciones'] ?? 'Sin motivo';
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($t['nombres'] . ' ' . $t['apellidos']) ?></strong></td>
                                            <td><?= htmlspecialchars($t['cedula']) ?></td>
                                            <td>
                                                <div class="dept-change">
                                                    <span class="dept-old"><?= htmlspecialchars($dep_origen) ?></span>
                                                    <span class="dept-arrow">➜</span>
                                                    <span class="dept-new"><?= htmlspecialchars($dep_destino) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y', strtotime($t['fecha_evento'])) ?>
                                            </td>
                                            <td>
                                                <span style="font-size: 12px; color: #64748B;">
                                                    <?= htmlspecialchars($motivo) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; justify-content: center; gap: 8px;">
                                                    <a href="../funcionarios/ver.php?id=<?= $t['funcionario_id'] ?>" 
                                                       class="btn-icon" title="Ver Expediente" style="color: #64748B;">
                                                        <?= Icon::get('eye') ?>
                                                    </a>
                                                    <?php if ($t['ruta_archivo_pdf']): ?>
                                                        <a href="<?= APP_URL . '/' . $t['ruta_archivo_pdf'] ?>" 
                                                           target="_blank" 
                                                           class="btn-icon" title="Ver Resolución PDF" style="color: #EF4444;">
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
    </div>

    <script>
    const APP_URL = "<?= APP_URL ?>";
    const DEPARTAMENTOS = <?= $departamentosJson ?>; 

    // Inicializar buscador
    document.addEventListener('DOMContentLoaded', function() {
        if(typeof initFiltroTiempoReal === 'function') {
            initFiltroTiempoReal('buscarTraslado', 'tablaTraslados');
        }
    });

    /**
     * MODAL: REGISTRAR TRASLADO
     */
    async function abrirModalTraslado() {
        try {
            Swal.fire({
                title: 'Cargando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // 1. Obtener Funcionarios
            const response = await fetch(`${APP_URL}/vistas/funcionarios/ajax/listar.php`);
            if (!response.ok) throw new Error('Error de conexión');
            const data = await response.json();
            if (!data.success) throw new Error('No se pudieron cargar los datos');
            
            const funcionarios = data.data.filter(f => f.estado === 'activo');
            
            Swal.close();

            // 2. Construir Opciones de Dept
            const deptOptions = DEPARTAMENTOS.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('');

            // 3. Mostrar Modal
            const { value: formValues } = await Swal.fire({
                title: 'Registrar Traslado',
                width: '700px',
                html: `
                    <div class="swal-form-grid-2col">
                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('user') ?> Funcionario
                            </label>
                            <select id="swal-funcionario" class="swal2-select">
                                <option value="">Seleccione...</option>
                                ${funcionarios.map(f => `<option value="${f.id}" data-dept="${f.departamento_nombre || 'No definido'}">${f.nombres} ${f.apellidos} (${f.cedula})</option>`).join('')}
                            </select>
                        </div>

                        <div class="swal-form-group">
                            <label class="swal-label">
                                <?= Icon::get('briefcase') ?> Dept. Actual
                            </label>
                            <input type="text" id="swal-dept-actual" class="swal2-input" disabled style="background: #F1F5F9; color: #64748B;">
                        </div>
                        
                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('arrow-right') ?> Nuevo Dept.
                            </label>
                            <select id="swal-dept-nuevo" class="swal2-select">
                                <option value="">Seleccione Destino...</option>
                                ${deptOptions}
                            </select>
                        </div>

                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('calendar') ?> Fecha Efectiva
                            </label>
                            <input type="date" id="swal-fecha" class="swal2-input" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('file-text') ?> Motivo / Resolución
                            </label>
                            <input type="text" id="swal-motivo" class="swal2-input" placeholder="Ej: Resolución RRHH-2024-001">
                        </div>
                        
                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label">
                                <?= Icon::get('file-text') ?> Orden de Traslado (PDF)
                            </label>
                            <input type="file" id="swal-archivo" class="swal2-file" accept="application/pdf">
                            <div class="swal-helper" style="font-size: 12px; color: #94A3B8; margin-top: 5px;">
                                <?= Icon::get('info') ?> Opcional - Solo archivos PDF
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Procesar Traslado',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0F4C81',
                didOpen: () => {
                    const selectFunc = document.getElementById('swal-funcionario');
                    const inputActual = document.getElementById('swal-dept-actual');
                    
                    selectFunc.addEventListener('change', (e) => {
                        const selectedOption = e.target.options[e.target.selectedIndex];
                        const dept = selectedOption.getAttribute('data-dept');
                        inputActual.value = dept || 'No definido';
                    });
                },
                preConfirm: () => {
                    const func = document.getElementById('swal-funcionario').value;
                    const dept_new = document.getElementById('swal-dept-nuevo').value;
                    const fecha = document.getElementById('swal-fecha').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const file = document.getElementById('swal-archivo').files[0];

                    if (!func || !dept_new || !fecha || !motivo) {
                        Swal.showValidationMessage('Complete todos los campos obligatorios');
                        return false;
                    }
                    return { func, dept_new, fecha, motivo, file };
                }
            });

            if (formValues) {
                guardarTraslado(formValues);
            }

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function guardarTraslado(datos) {
        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const formData = new FormData();
        formData.append('csrf_token', '<?= generarTokenCSRF() ?>'); // Token de seguridad
        
        formData.append('accion', 'registrar_traslado');
        formData.append('funcionario_id', datos.func);
        formData.append('departamento_destino_id', datos.dept_new);
        formData.append('fecha_evento', datos.fecha);
        formData.append('motivo', datos.motivo);
        if (datos.file) formData.append('archivo_pdf', datos.file);

        try {
            const res = await fetch(`${APP_URL}/vistas/funcionarios/ajax/gestionar_historial.php`, {
                method: 'POST',
                body: formData
            });
            
            const text = await res.text();
            let result;
            try { 
                result = JSON.parse(text); 
            } catch (e) { 
                console.error("Error respuesta servidor:", text);
                throw new Error("Error del servidor. Consulte la consola."); 
            }

            if (result.success) {
                await Swal.fire({
                    icon: 'success', 
                    title: '¡Traslado Exitoso!', 
                    text: 'El funcionario ha sido movido al nuevo departamento.',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#0F4C81'
                });
                window.location.reload();
            } else {
                throw new Error(result.error);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        }
    }

    // Helper de Iconos JS
    const Icon = {
        get: (name) => {
            const icons = {
                'user': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
                'briefcase': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
                'arrow-right': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
                'calendar': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                'file-text': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'info': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
            };
            return icons[name] || '';
        }
    };
    </script>
</body>
</html>