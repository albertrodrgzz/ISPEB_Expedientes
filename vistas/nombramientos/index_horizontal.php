<?php
/**
 * Módulo de Nombramientos - Versión Horizontal Mejorada
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

// Obtener estadísticas
$pdo = getDB();

$total_nombramientos = $pdo->query("
    SELECT COUNT(*) FROM historial_administrativo 
    WHERE tipo_evento = 'NOMBRAMIENTO'
")->fetchColumn();

$nombramientos_mes = $pdo->query("
    SELECT COUNT(*) FROM historial_administrativo 
    WHERE tipo_evento = 'NOMBRAMIENTO' 
    AND MONTH(fecha_evento) = MONTH(CURRENT_DATE())
    AND YEAR(fecha_evento) = YEAR(CURRENT_DATE())
")->fetchColumn();

// Obtener lista de nombramientos con datos completos
$stmt = $pdo->query("
    SELECT 
        h.id,
        h.funcionario_id,
        h.fecha_evento,
        h.detalles,
        h.ruta_archivo_pdf,
        h.created_at,
        f.cedula,
        f.nombres,
        f.apellidos,
        c.nombre_cargo as cargo_actual
    FROM historial_administrativo h
    INNER JOIN funcionarios f ON h.funcionario_id = f.id
    LEFT JOIN cargos c ON f.cargo_id = c.id
    WHERE h.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY h.created_at DESC
    LIMIT 100
");

$nombramientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .btn-nuevo {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        }

        .stats-grid {
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
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .btn-link {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-link:hover {
            color: #0284c7;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
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

            <!-- Estadíst

icas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Nombramientos</div>
                    <div class="stat-value"><?php echo number_format($total_nombramientos); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Este Mes</div>
                    <div class="stat-value"><?php echo number_format($nombramientos_mes); ?></div>
                </div>
            </div>

            <!-- Tabla de nombramientos -->
            <div class="content-card">
                <div class="search-bar">
                    <input type="text" 
                           id="buscar" 
                           class="search-input" 
                           placeholder="🔍 Buscar por nombre, cédula o cargo...">
                </div>

                <?php if (count($nombramientos) > 0): ?>
                    <table id="tabla-nombramientos">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Cédula</th>
                                <th>Nuevo Cargo</th>
                                <th>Fecha</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nombramientos as $nom): ?>
                                <?php
                                    $detalles = json_decode($nom['detalles'], true);
                                    $cargo_nuevo = $detalles['cargo'] ?? 'N/A';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($nom['nombres'] . ' ' . $nom['apellidos']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($nom['cedula']); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($cargo_nuevo); ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($nom['fecha_evento'])); ?></td>
                                    <td>
                                        <?php if ($nom['ruta_archivo_pdf']): ?>
                                            <a href="<?php echo APP_URL . '/' . $nom['ruta_archivo_pdf']; ?>" 
                                               target="_blank" 
                                               class="btn-link">
                                                Ver Documento
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">Sin documento</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📋</div>
                        <p>No hay nombramientos registrados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
                    title: '<div style="display: flex; align-items: center; gap: 12px; justify-content: center; font-size: 24px; font-weight: 700; color: #1e293b;"><svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span>Nuevo Nombramiento</span></div>',
                    html: `
                        <style>
                            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
                            .form-group { text-align: left; }
                            .form-label { display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px; color: #334155; font-size: 13px; }
                            .form-input, .form-select { width: 100%; padding: 11px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; font-family: inherit; }
                            .form-input:focus, .form-select:focus { border-color: #0ea5e9; outline: none; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
                            .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
                            .file-input-button { width: 100%; padding: 12px 16px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 500; color: #475569; font-size: 14px; }
                            .file-input-button:hover { border-color: #0ea5e9; background: #f0f9ff; color: #0ea5e9; }
                            .file-input-button.has-file { background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border-color: #06b6d4; color: #0e7490; }
                            .file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
                            .info-box { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-top: 12px; }
                            .info-box-content { display: flex; align-items: start; gap: 10px; font-size: 12px; color: #92400e; line-height: 1.6; }
                            .cargo-actual-box { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: none; }
                            .cargo-actual-content { display: flex; align-items: center; gap: 8px; color: #1e40af; font-size: 13px; font-weight: 500; }
                        </style>
                        <div style="max-width: 800px; margin: 0 auto;">
                            <!-- Fila 1: Funcionario y Nuevo Cargo -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Funcionario <span style="color: #ef4444;">*</span>
                                    </label>
                                    <select id="swal-funcionario" class="form-select">
                                        <option value="">Seleccionar...</option>
                                        ${funcionarios.map(f => `
                                            <option value="${f.id}" data-cargo="${f.nombre_cargo || 'Sin cargo'}">
                                                ${f.nombres} ${f.apellidos}
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        Nuevo Cargo <span style="color: #ef4444;">*</span>
                                    </label>
                                    <select id="swal-cargo" class="form-select">
                                        <option value="">Seleccionar...</option>
                                        ${cargos.map(c => `<option value="${c.id}">${c.nombre_cargo}</option>`).join('')}
                                    </select>
                                </div>
                            </div>

                            <!-- Cargo Actual Info -->
                            <div class="cargo-actual-box" id="cargo-actual-box">
                                <div class="cargo-actual-content">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Cargo actual: <strong id="cargo-actual-text">-</strong></span>
                                </div>
                            </div>

                            <!-- Fila 2: Fecha -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Fecha de Nombramiento <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input type="date" id="swal-fecha" class="form-input" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Documento de Respaldo <span style="color: #ef4444;">*</span>
                                    </label>
                                    <div class="file-input-wrapper">
                                        <label class="file-input-button" id="file-label">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <span id="file-label-text">Seleccionar archivo</span>
                                        </label>
                                        <input type="file" id="swal-pdf" accept="application/pdf,image/png,image/jpeg,image/jpg">
                                    </div>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="info-box">
                                <div class="info-box-content">
                                    <svg width="18" height="18" fill="#f59e0b" viewBox="0 0 24 24" style="flex-shrink: 0;"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div>
                                        <strong>Formatos aceptados:</strong> PDF, JPG, PNG<br>
                                        <strong>Tamaño máximo:</strong> 5 MB
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '900px',
                    showCancelButton: true,
                    confirmButtonText: '<div style="display: flex; align-items: center; gap: 8px;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Registrar</span></div>',
                    cancelButtonText: '<div style="display: flex; align-items: center; gap: 8px;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg><span>Cancelar</span></div>',
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
                            .swal-modern-popup { border-radius: 16px !important; padding: 30px 20px !important; }
                            .swal-btn { padding: 11px 24px !important; border-radius: 8px !important; font-weight: 600 !important; font-size: 14px !important; transition: all 0.2s !important; display: inline-flex !important; align-items: center !important; }
                            .swal-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12) !important; }
                        `;
                        document.head.appendChild(style);
                        
                        // Mostrar cargo actual
                        document.getElementById('swal-funcionario').addEventListener('change', function() {
                            const opt = this.options[this.selectedIndex];
                            const cargo = opt.dataset.cargo;
                            const box = document.getElementById('cargo-actual-box');
                            const text = document.getElementById('cargo-actual-text');
                            if (cargo && this.value) {
                                text.textContent = cargo;
                                box.style.display = 'block';
                            } else {
                                box.style.display = 'none';
                            }
                        });
                        
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
                        const nuevo_cargo_id = document.getElementById('swal-cargo').value;
                        const fecha_evento = document.getElementById('swal-fecha').value;
                        const archivo_pdf = document.getElementById('swal-pdf').files[0];

                        if (!funcionario_id) { Swal.showValidationMessage('⚠️ Seleccione un funcionario'); return false; }
                        if (!nuevo_cargo_id) { Swal.showValidationMessage('⚠️ Seleccione el nuevo cargo'); return false; }
                        if (!fecha_evento) { Swal.showValidationMessage('⚠️ Ingrese la fecha'); return false; }
                        if (!archivo_pdf) { Swal.showValidationMessage('⚠️ El documento es obligatorio'); return false; }
                        if (archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage(`⚠️ Archivo muy grande (${(archivo_pdf.size / 1024 / 1024).toFixed(2)} MB). Máximo: 5 MB`); return false; }

                        const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                        if (!validTypes.includes(archivo_pdf.type)) { Swal.showValidationMessage('⚠️ Solo PDF, JPG o PNG'); return false; }

                        return { funcionario_id, nuevo_cargo_id, fecha_evento, archivo_pdf };
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
                formData.append('nuevo_cargo_id', formValues.nuevo_cargo_id);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('archivo_pdf', formValues.archivo_pdf);

                const response = await fetch('<?php echo APP_URL; ?>/vistas/funcionarios/ajax/gestionar_historial.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Respuesta:', text);
                    throw new Error('Respuesta no es JSON');
                }

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '✓ Registrado',
                        html: `<p style="margin-bottom: 12px;">${result.message}</p>
                               <div style="background: #dcfce7; padding: 12px; border-radius: 8px; font-size: 13px;">
                                   <strong>Cargo anterior:</strong> ${result.data.cargo_anterior}<br>
                                   <strong>Cargo nuevo:</strong> ${result.data.cargo_nuevo}
                               </div>`,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error, confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'No se pudo conectar', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
