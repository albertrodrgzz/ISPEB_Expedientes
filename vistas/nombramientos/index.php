<?php
/**
 * Módulo de Nombramientos
 * Sistema ISPEB - Gestión de Expedientes Digitales
 * 
 * Permite registrar y consultar nombramientos de funcionarios
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

// Verificar permisos (nivel 1-2)
if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$total_nombramientos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$nombramientos_anio = $stmt->fetch()['total'];

// Obtener registros de nombramientos
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.cargo')) as cargo_actual,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.departamento')) as departamento,
        ha.fecha_evento,
        ha.ruta_archivo_pdf,
        ha.nombre_archivo_original,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    WHERE ha.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY ha.fecha_evento DESC, ha.created_at DESC
");
$nombramientos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nombramientos - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?php echo APP_URL; ?>/publico/js/filtros-tiempo-real.js"></script>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .btn-nuevo {
            background: linear-gradient(135deg, #00a8cc 0%, #005f73 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 168, 204, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 168, 204, 0.4);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #fc5c7d 0%, #6a82fb 100%);
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            max-width: 400px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #00a8cc;
            box-shadow: 0 0 0 3px rgba(0, 168, 204, 0.1);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f7fafc;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        tbody tr:hover {
            background: #f7fafc;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .btn-link {
            color: #00a8cc;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-link:hover {
            color: #005f73;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-data-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php include '../layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../layout/header.php'; ?>

        <div class="content-wrapper">
            <!-- Header con botón -->
            <div class="page-header">
                <h1 class="header-title">📋 Nombramientos</h1>
                <button class="btn-nuevo" onclick="abrirModalNombramiento()">
                    <span style="font-size: 20px;">➕</span>
                    Nuevo Nombramiento
                </button>
            </div>

            <!-- Estadísticas -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total Nombramientos</div>
                    <div class="stat-value"><?php echo number_format($total_nombramientos); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nombramientos <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo number_format($nombramientos_anio); ?></div>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="content-card">
                <div class="search-bar">
                    <input type="text" 
                           id="buscarNombramiento" 
                           class="search-input" 
                           placeholder="🔍 Buscar por cédula, nombre, cargo...">
                </div>

                <div class="table-wrapper">
                    <table id="tablaNombramientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($nombramientos)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="no-data">
                                            <div class="no-data-icon">📋</div>
                                            <p>No hay nombramientos registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($nombramientos as $nom): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($nom['fecha_evento'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($nom['nombres'] . ' ' . $nom['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($nom['cedula']); ?></td>
                                        <td>
                                            <span class="badge badge-success"><?php echo htmlspecialchars($nom['cargo_actual'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($nom['departamento'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($nom['ruta_archivo_pdf']): ?>
                                                <a href="<?php echo APP_URL . '/' . $nom['ruta_archivo_pdf']; ?>" 
                                                   target="_blank" 
                                                   class="btn-link">
                                                    📄 Ver
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #cbd5e0;">Sin archivo</span>
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
        // Inicializar filtro de búsqueda
        initSimpleTableSearch('buscarNombramiento', 'tablaNombramientos');

        /**
         * Abre modal HORIZONTAL para registrar nuevo nombramiento
         */
        async function abrirModalNombramiento() {
            try {
                // Cargar funcionarios activos
                const funcionariosRes = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/listar.php');
                
                if (!funcionariosRes.ok) {
                    throw new Error('Error al cargar funcionarios');
                }
                
                const funcionariosData = await funcionariosRes.json();

                if (!funcionariosData.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los funcionarios',
                        confirmButtonColor: '#ef4444'
                    });
                    return;
                }

                const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');

                // Cargar cargos
                const cargosRes = await fetch('<?php echo APP_URL; ?>/vistas/admin/ajax/get_cargos.php');
                
                if (!cargosRes.ok) {
                    throw new Error('Error al cargar cargos');
                }
                
                const cargosData = await cargosRes.json();

                if (!cargosData.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los cargos',
                        confirmButtonColor: '#ef4444'
                    });
                    return;
                }

                const cargos = cargosData.data;

                // Modal con diseño HORIZONTAL y profesional
                const { value: formValues } = await Swal.fire({
                    title: '<div style="display: flex; align-items: center; gap: 12px; justify-content: center; font-size: 22px; font-weight: 700; color: #1e293b;"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span>Nuevo Nombramiento</span></div>',
                    html: `
                        <style>
                            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
                            .form-group { text-align: left; }
                            .form-label { display: flex; align-items: center; gap: 7px; font-weight: 600; margin-bottom: 9px; color: #334155; font-size: 13px; }
                            .form-input, .form-select { width: 100%; padding: 10px 13px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 13.5px; transition: all 0.2s; background: white; font-family: inherit; }
                            .form-input:focus, .form-select:focus { border-color: #0ea5e9; outline: none; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
                            .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
                            .file-input-button { width: 100%; padding: 11px 14px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 9px; font-weight: 500; color: #475569; font-size: 13px; }
                            .file-input-button:hover { border-color: #0ea5e9; background: #f0f9ff; color: #0ea5e9; }
                            .file-input-button.has-file { background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border-color: #06b6d4; color: #0e7490; }
                            .file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
                            .info-box { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 3px solid #f59e0b; padding: 11px 14px; border-radius: 8px; margin-top: 10px; }
                            .info-box-content { display: flex; align-items: start; gap: 9px; font-size: 11.5px; color: #92400e; line-height: 1.5; }
                            .cargo-actual-box { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 3px solid #3b82f6; padding: 11px 14px; border-radius: 8px; margin-bottom: 18px; display: none; }
                            .cargo-actual-content { display: flex; align-items: center; gap: 7px; color: #1e40af; font-size: 12.5px; font-weight: 500; }
                        </style>
                        <div style="max-width: 750px; margin: 0 auto;">
                            <!-- Funcionario -->
                            <div class="form-group" style="margin-bottom: 18px;">
                                <label class="form-label">
                                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Funcionario <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="swal-funcionario" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    ${funcionarios.map(f => `
                                        <option value="${f.id}">
                                            ${f.nombres} ${f.apellidos}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>

                            <!-- Fecha y Documento -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Fecha <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input type="date" id="swal-fecha" class="form-input" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Documento <span style="color: #ef4444;">*</span>
                                    </label>
                                    <div class="file-input-wrapper">
                                        <label class="file-input-button" id="file-label" for="swal-pdf">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <span id="file-label-text">Seleccionar archivo</span>
                                        </label>
                                        <input type="file" id="swal-pdf" accept="application/pdf,image/png,image/jpeg,image/jpg">
                                    </div>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="info-box">
                                <div class="info-box-content">
                                    <svg width="16" height="16" fill="#f59e0b" viewBox="0 0 24 24" style="flex-shrink: 0;"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div>
                                        <strong>Formatos:</strong> PDF, JPG, PNG  •  <strong>Máx:</strong> 5 MB
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '850px',
                    showCancelButton: true,
                    confirmButtonText: '<div style="display: flex; align-items: center; gap: 7px;"><svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Registrar</span></div>',
                    cancelButtonText: '<div style="display: flex; align-items: center; gap: 7px;"><svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg><span>Cancelar</span></div>',
                    confirmButtonColor: '#0ea5e9',
                    cancelButtonColor: '#64748b',
                    customClass: {
                        popup: 'swal-modern-popup',
                        confirmButton: 'swal-btn',
                        cancelButton: 'swal-btn'
                    },
                    didOpen: () => {
                        const style = document.createElement('style');
                        style.textContent = `
                            .swal-modern-popup { border-radius: 14px !important; padding: 28px 18px !important; }
                            .swal-btn { padding: 10px 22px !important; border-radius: 7px !important; font-weight: 600 !important; font-size: 13.5px !important; transition: all 0.2s !important; display: inline-flex !important; align-items: center !important; }
                            .swal-btn:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(0, 0, 0, 0.11) !important; }
                        `;
                        document.head.appendChild(style);
                        
                        
                        // File input styling
                        document.getElementById('swal-pdf').addEventListener('change', function(e) {
                            const label = document.getElementById('file-label');
                            const labelText = document.getElementById('file-label-text');
                            if (e.target.files.length > 0) {
                                const file = e.target.files[0];
                                const size = (file.size / 1024 / 1024).toFixed(2);
                                labelText.textContent = `${file.name} (${size} MB)`;
                                label.classList.add('has-file');
                            } else {
                                labelText.textContent = 'Seleccionar archivo';
                                label.classList.remove('has-file');
                            }
                        });
                    },
                    preConfirm: () => {
                        const funcionario_id = document.getElementById('swal-funcionario').value;
                        const fecha_evento = document.getElementById('swal-fecha').value;
                        const archivo_pdf = document.getElementById('swal-pdf').files[0];

                        if (!funcionario_id) { Swal.showValidationMessage('⚠️ Seleccione un funcionario'); return false; }
                        if (!fecha_evento) { Swal.showValidationMessage('⚠️ Ingrese la fecha'); return false; }
                        if (!archivo_pdf) { Swal.showValidationMessage('⚠️ El documento es obligatorio'); return false; }
                        if (archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage(`⚠️ Archivo muy grande (${(archivo_pdf.size / 1024 / 1024).toFixed(2)} MB). Máximo: 5 MB`); return false; }

                        const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                        if (!validTypes.includes(archivo_pdf.type)) { Swal.showValidationMessage('⚠️ Solo PDF, JPG o PNG'); return false; }

                        return { funcionario_id, fecha_evento, archivo_pdf };
                    }
                });

                if (!formValues) return;

                // Loading
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Registrando nombramiento...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                // Enviar
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_nombramiento');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('archivo_pdf', formValues.archivo_pdf);

                // DEBUG: Log what we're sending
                console.log('=== SENDING TO SERVER ===');
                console.log('funcionario_id:', formValues.funcionario_id);
                console.log('fecha_evento:', formValues.fecha_evento);
                console.log('archivo_pdf:', formValues.archivo_pdf);
                console.log('archivo_pdf type:', formValues.archivo_pdf?.type);
                console.log('archivo_pdf size:', formValues.archivo_pdf?.size);

                const response = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/gestionar_historial.php', {
                    method: 'POST',
                    body: formData
                });

                // ALWAYS read response text first for debugging
                const responseText = await response.text();
                console.log('RAW SERVER RESPONSE:', responseText);

                if (!response.ok) {
                    try {
                        const errorData = JSON.parse(responseText);
                        Swal.fire({
                            icon: 'warning',
                            title: 'No se pudo registrar',
                            text: errorData.error,
                            confirmButtonColor: '#ef4444'
                        });
                        return;
                    } catch (parseError) {
                        console.error('Could not parse error as JSON:', parseError);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error del servidor',
                            html: `<pre style="text-align: left; max-height: 300px; overflow: auto; font-size: 11px;">${responseText.substring(0, 500)}</pre>`,
                            confirmButtonColor: '#ef4444'
                        });
                        return;
                    }
                }

                // Try to parse response as JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Response is not valid JSON:', responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error del servidor',
                        html: `<p>El servidor devolvió una respuesta inválida.</p><pre style="text-align: left; max-height: 300px; overflow: auto; font-size: 11px;">${responseText.substring(0, 500)}</pre>`,
                        confirmButtonColor: '#ef4444'
                    });
                    return;
                }

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '✓ Registrado',
                        html: `<p style="margin-bottom: 11px; color: #64748b;">${result.message}</p>
                               <div style="background: #dcfce7; padding: 11px; border-radius: 7px; font-size: 12.5px; color: #166534;">
                                   <strong>Funcionario:</strong> ${result.data.funcionario}<br>
                                   <strong>Cargo:</strong> ${result.data.cargo_actual}<br>
                                   <strong>Fecha:</strong> ${result.data.fecha}
                               </div>`,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error, confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error('Error completo:', error);
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: error.message || 'No se pudo conectar con el servidor', 
                    confirmButtonColor: '#ef4444' 
                });
            }
        }
    </script>
</body>
</html>
