<?php
/**
 * Módulo de Amonestaciones
 * Diseño: Enterprise Standard (Corregido: Token CSRF y Estilos)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';

verificarSesion();

// Permisos (Nivel 1-2)
if (!verificarNivel(2)) {
    $_SESSION['error'] = 'Acceso no autorizado';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// --- ESTADÍSTICAS (KPIs) ---
// 1. Amonestaciones este mes
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$total_mes = $stmt->fetch()['total'];

// 2. Graves/Muy Graves (Histórico)
// Nota: Buscamos en el JSON si el tipo_falta es grave o muy_grave
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND (JSON_EXTRACT(detalles, '$.tipo_falta') = 'grave' OR JSON_EXTRACT(detalles, '$.tipo_falta') = 'muy_grave')");
$total_graves = $stmt->fetch()['total'];

// 3. Total Histórico
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION'");
$total_historico = $stmt->fetch()['total'];

// --- LISTADO DE AMONESTACIONES ---
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        ha.fecha_evento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta')) as tipo_falta,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.sancion')) as sancion,
        ha.ruta_archivo_pdf,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'AMONESTACION'
    ORDER BY ha.fecha_evento DESC
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
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?= APP_URL ?>/publico/js/filtros-tiempo-real.js"></script>

    <style>
        /* Estilos KPI y Generales (Estandarizados) */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        
        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; flex-shrink: 0;
        }
        
        /* Gradientes de Alerta */
        .gradient-red { background: linear-gradient(135deg, #EF4444, #B91C1C); }
        .gradient-orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .gradient-gray { background: linear-gradient(135deg, #64748B, #475569); }
        
        .kpi-details { display: flex; flex-direction: column; }
        .kpi-value { font-size: 24px; font-weight: 700; color: #1E293B; margin: 0; line-height: 1.2; }
        .kpi-label { font-size: 13px; color: #64748B; font-weight: 600; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }

        /* Input File del Modal */
        .swal2-file {
            background: #ffffff !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 10px !important;
            font-size: 14px !important;
            width: 100% !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
            transition: all 0.2s !important;
        }
        .swal2-file:hover { border-color: #cbd5e1 !important; }
        
        /* Badges de Gravedad */
        .badge-leve { background: #FEF3C7; color: #92400E; }
        .badge-grave { background: #FFEDD5; color: #C2410C; }
        .badge-muy_grave { background: #FEE2E2; color: #B91C1C; font-weight: 700; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin: 0; display: flex; align-items: center; gap: 12px;">
                    <?= Icon::get('alert-triangle') ?>
                    Amonestaciones
                </h1>
                <button class="btn-primary" onclick="abrirModalAmonestacion()">
                    <?= Icon::get('plus') ?>
                    Registrar Falta
                </button>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon gradient-orange">
                        <?= Icon::get('calendar') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-value"><?= number_format($total_mes) ?></div>
                        <div class="kpi-label">Faltas este Mes</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-red">
                        <?= Icon::get('alert-circle') ?>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-value"><?= number_format($total_graves) ?></div>
                        <div class="kpi-label">Graves / Muy Graves</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon gradient-gray">
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
                        <input type="text" id="buscarAmonestacion" class="search-input" placeholder="🔍 Buscar por funcionario o motivo..." style="width: 100%; max-width: 400px;">
                    </div>
                    
                    <div class="table-wrapper">
                        <table id="tablaAmonestaciones" class="table-modern">
                            <thead>
                                <tr>
                                    <th>Funcionario</th>
                                    <th>Fecha</th>
                                    <th>Gravedad</th>
                                    <th>Motivo</th>
                                    <th>Sanción</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($amonestaciones)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state" style="text-align: center; padding: 40px; color: #94A3B8;">
                                                <div style="margin-bottom: 10px; opacity: 0.5;">
                                                    <?= Icon::get('check-circle', 'width:64px; height:64px;') ?>
                                                </div>
                                                <div style="font-weight: 600; font-size: 16px;">No hay amonestaciones</div>
                                                <p style="font-size: 13px;">Todo el personal tiene historial limpio.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($amonestaciones as $a): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600; color: #1E293B;">
                                                    <?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']) ?>
                                                </div>
                                                <small style="color: #64748B;"><?= htmlspecialchars($a['cedula']) ?></small>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y', strtotime($a['fecha_evento'])) ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $tipo = $a['tipo_falta'];
                                                    $label = ucfirst(str_replace('_', ' ', $tipo));
                                                ?>
                                                <span class="badge badge-<?= $tipo ?>" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                                    <?= $label ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; color: #334155;">
                                                    <?= htmlspecialchars($a['motivo']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; color: #64748B;">
                                                    <?= htmlspecialchars($a['sancion']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; justify-content: center; gap: 8px;">
                                                    <a href="../funcionarios/ver.php?id=<?= $a['funcionario_id'] ?>" 
                                                       class="btn-icon" title="Ver Expediente" style="color: #64748B;">
                                                        <?= Icon::get('eye') ?>
                                                    </a>
                                                    <?php if ($a['ruta_archivo_pdf']): ?>
                                                        <a href="<?= APP_URL . '/' . $a['ruta_archivo_pdf'] ?>" 
                                                           target="_blank" 
                                                           class="btn-icon" title="Ver Acta PDF" style="color: #EF4444;">
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

    // Inicializar buscador
    document.addEventListener('DOMContentLoaded', function() {
        if(typeof initFiltroTiempoReal === 'function') {
            initFiltroTiempoReal('buscarAmonestacion', 'tablaAmonestaciones');
        }
    });

    /**
     * MODAL: REGISTRAR AMONESTACIÓN
     */
    async function abrirModalAmonestacion() {
        try {
            Swal.fire({
                title: 'Cargando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // 1. Obtener Funcionarios (Ruta Absoluta)
            const response = await fetch(`${APP_URL}/vistas/funcionarios/ajax/listar.php`);
            if (!response.ok) throw new Error('Error de conexión');
            const data = await response.json();
            if (!data.success) throw new Error('No se pudieron cargar los datos');
            
            const funcionarios = data.data.filter(f => f.estado === 'activo');
            
            Swal.close();

            // 2. Mostrar Modal
            const { value: formValues } = await Swal.fire({
                title: 'Registrar Amonestación',
                width: '700px',
                html: `
                    <div class="swal-form-grid-2col">
                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('user') ?> Funcionario
                            </label>
                            <select id="swal-funcionario" class="swal2-select">
                                <option value="">Seleccione...</option>
                                ${funcionarios.map(f => `<option value="${f.id}">${f.nombres} ${f.apellidos} (${f.cedula})</option>`).join('')}
                            </select>
                        </div>

                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('calendar') ?> Fecha del Evento
                            </label>
                            <input type="date" id="swal-fecha" class="swal2-input" value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="swal-form-group">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('alert-triangle') ?> Gravedad de la Falta
                            </label>
                            <select id="swal-tipo" class="swal2-select">
                                <option value="leve">Leve</option>
                                <option value="grave">Grave</option>
                                <option value="muy_grave">Muy Grave</option>
                            </select>
                        </div>

                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('file-text') ?> Motivo de la falta
                            </label>
                            <textarea id="swal-motivo" class="swal2-textarea" rows="2" placeholder="Describa brevemente lo sucedido..."></textarea>
                        </div>

                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('gavel') ?> Sanción Aplicada
                            </label>
                            <input type="text" id="swal-sancion" class="swal2-input" placeholder="Ej: Amonestación escrita, Suspensión de 3 días...">
                        </div>
                        
                        <div class="swal-form-group" style="grid-column: span 2;">
                            <label class="swal-label swal-label-required">
                                <?= Icon::get('file-text') ?> Acta de Amonestación (PDF)
                            </label>
                            <input type="file" id="swal-archivo" class="swal2-file" accept="application/pdf">
                            <div class="swal-helper" style="font-size: 12px; color: #94A3B8; margin-top: 5px;">
                                <?= Icon::get('info') ?> El acta firmada es obligatoria.
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Registrar Falta',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#EF4444', // Rojo para alerta
                preConfirm: () => {
                    const func = document.getElementById('swal-funcionario').value;
                    const fecha = document.getElementById('swal-fecha').value;
                    const tipo = document.getElementById('swal-tipo').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const sancion = document.getElementById('swal-sancion').value;
                    const file = document.getElementById('swal-archivo').files[0];

                    if (!func || !fecha || !tipo || !motivo || !sancion || !file) {
                        Swal.showValidationMessage('Todos los campos son obligatorios, incluyendo el archivo PDF.');
                        return false;
                    }
                    return { func, fecha, tipo, motivo, sancion, file };
                }
            });

            if (formValues) {
                guardarAmonestacion(formValues);
            }

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function guardarAmonestacion(datos) {
        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const formData = new FormData();
        // ⚠️ INCLUSIÓN CRÍTICA DEL TOKEN CSRF
        formData.append('csrf_token', '<?= generarTokenCSRF() ?>');
        
        formData.append('accion', 'registrar_amonestacion');
        formData.append('funcionario_id', datos.func);
        formData.append('fecha_evento', datos.fecha);
        formData.append('tipo_falta', datos.tipo);
        formData.append('motivo', datos.motivo);
        formData.append('sancion', datos.sancion);
        formData.append('archivo_pdf', datos.file); // El archivo es obligatorio aquí

        try {
            const res = await fetch(`${APP_URL}/vistas/funcionarios/ajax/gestionar_historial.php`, {
                method: 'POST',
                body: formData
            });
            
            // Manejo de errores HTTP (como el 403 Forbidden)
            if (!res.ok) {
                if (res.status === 403) throw new Error('Permiso denegado o Token de seguridad vencido.');
                throw new Error(`Error del servidor: ${res.status}`);
            }

            const text = await res.text();
            let result;
            try { 
                result = JSON.parse(text); 
            } catch (e) { 
                console.error("Respuesta no JSON:", text);
                throw new Error("Error inesperado del servidor."); 
            }

            if (result.success) {
                await Swal.fire({
                    icon: 'success', 
                    title: '¡Registrado!', 
                    text: 'La amonestación ha sido guardada correctamente.',
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
                'alert-triangle': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                'calendar': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                'file-text': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'gavel': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 13l-6.5-6.5a2.12 2.12 0 1 1 3-3L17 10"></path><path d="M20 13l-6.5 6.5a2.12 2.12 0 1 1-3-3L17 10"></path><path d="M4 20l4-4"></path></svg>',
                'info': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
            };
            return icons[name] || '';
        }
    };
    </script>
</body>
</html>