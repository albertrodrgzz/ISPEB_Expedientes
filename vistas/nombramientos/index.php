<?php
/**
 * Vista: Módulo de Nombramientos
 * Gestión y consulta de nombramientos de personal
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder a este módulo';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$total_nombramientos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$nombramientos_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$empleados_nombrados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO' AND (fecha_fin IS NULL OR fecha_fin > CURDATE())");
$nombramientos_activos = $stmt->fetch()['total'];

// Obtener departamentos
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener nombramientos
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.titulo')) as titulo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.categoria')) as categoria,
        ha.fecha_evento as fecha_inicio,
        ha.fecha_fin,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at,
        CASE 
            WHEN ha.fecha_fin IS NULL OR ha.fecha_fin > CURDATE() THEN 'Activo'
            ELSE 'Finalizado'
        END as estado
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY ha.fecha_evento DESC
");
$nombramientos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nombramientos - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #00a8cc 0%, #005f73 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #4cc9f0 0%, #00a8cc 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #0a9396 0%, #005f73 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #06d6a0 0%, #0a9396 100%);
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.95;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
        }
        
        .filter-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .badge-activo {
            padding: 4px 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-finalizado {
            padding: 4px 12px;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Nombramientos</div>
                    <div class="stat-value"><?php echo $total_nombramientos; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nombramientos <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $nombramientos_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Empleados Nombrados</div>
                    <div class="stat-value"><?php echo $empleados_nombrados; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nombramientos Activos</div>
                    <div class="stat-value"><?php echo $nombramientos_activos; ?></div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filter-panel">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">🔍 Filtros de Búsqueda</h3>
                <div class="filter-grid">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar</label>
                        <input type="text" id="searchNombramientos" class="search-input" placeholder="🔍 Buscar nombramiento..." style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento</label>
                        <select id="filter-departamento" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Estado</label>
                        <select id="filter-estado" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <option value="Activo">Activo</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="button" onclick="limpiarFiltros()" class="btn" style="width: 100%; background: #e2e8f0; color: #2d3748;">Limpiar</button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title">
                        <h2 class="card-title">📄 Registro de Nombramientos</h2>
                        <p class="card-subtitle"><?php echo count($nombramientos); ?> nombramientos registrados</p>
                    </div>
                    <button onclick="abrirModalNombramiento()" class="btn btn-primary" style="padding: 10px 20px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">➕</span>
                        Nuevo Nombramiento
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table" id="nombramientosTable">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($nombramientos)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📄</div>
                                        <p>No hay registros de nombramientos</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($nombramientos as $nom): ?>
                                    <tr class="nombramiento-row" 
                                        data-empleado="<?php echo strtolower($nom['nombres'] . ' ' . $nom['apellidos'] . ' ' . $nom['cedula']); ?>"
                                        data-departamento="<?php echo $nom['departamento']; ?>"
                                        data-estado="<?php echo $nom['estado']; ?>">
                                        <td><strong><?php echo htmlspecialchars($nom['nombres'] . ' ' . $nom['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($nom['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($nom['departamento']); ?></span></td>
                                        <td><?php echo htmlspecialchars($nom['titulo'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($nom['categoria'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($nom['fecha_inicio'])); ?></td>
                                        <td><?php echo $nom['fecha_fin'] ? date('d/m/Y', strtotime($nom['fecha_fin'])) : 'N/A'; ?></td>
                                        <td><span class="badge-<?php echo strtolower($nom['estado']); ?>"><?php echo $nom['estado']; ?></span></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $nom['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($nom['ruta_archivo']): ?>
                                                <a href="../../<?php echo $nom['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
    
    
    <!-- SweetAlert2 -->
    <script src="<?php echo APP_URL; ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    
    <script>
        // Real-time search filter for nombramientos
        document.getElementById('searchNombramientos')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#nombramientosTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Funcionalidad de filtros
        function limpiarFiltros() {
            document.getElementById('searchNombramientos').value = '';
            document.getElementById('filter-departamento').value = '';
            document.getElementById('filter-estado').value = '';
            aplicarFiltros();
        }
        
        function aplicarFiltros() {
            const searchTerm = document.getElementById('searchNombramientos').value.toLowerCase();
            const departamento = document.getElementById('filter-departamento').value;
            const estado = document.getElementById('filter-estado').value;
            const rows = document.querySelectorAll('.nombramiento-row');
            
            rows.forEach(row => {
                const matchSearch = row.dataset.empleado.includes(searchTerm);
                const matchDept = !departamento || row.dataset.departamento === departamento;
                const matchEstado = !estado || row.dataset.estado === estado;
                
                row.style.display = (matchSearch && matchDept && matchEstado) ? '' : 'none';
            });
        }
        
        document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-estado')?.addEventListener('change', aplicarFiltros);
        
        // ===================================================
        // MODAL NUEVO NOMBRAMIENTO
        // ===================================================
        
        async function abrirModalNombramiento() {
            // Cargar funcionarios y cargos
            const [funcionariosRes, cargosRes] = await Promise.all([
                fetch('../funcionarios/ajax/listar.php'),
                fetch('../admin/ajax/get_cargos.php')
            ]);
            
            const funcionariosData = await funcionariosRes.json();
            const cargosData = await cargosRes.json();
            
            if (!funcionariosData.success || !cargosData.success) {
                Swal.fire('Error', 'No se pudieron cargar los datos necesarios', 'error');
                return;
            }
            
            const funcionarios = funcionariosData.data.filter(f => f.estado === 'activo');
            const cargos = cargosData.data;
            
            const { value: formValues } = await Swal.fire({
                title: '➕ Nuevo Nombramiento',
                html: `
                    <div style="text-align: left;">
                        <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                            <div style="display: flex; align-items: center; gap: 10px; color: #1e40af;">
                                <span style="font-size: 24px;">ℹ️</span>
                                <p style="margin: 0; font-size: 13px;">El cargo del funcionario se actualizará automáticamente al registrar el nombramiento.</p>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionarios.map(f => `
                                    <option value="${f.id}" data-cargo="${f.nombre_cargo}">
                                        ${f.nombres} ${f.apellidos} - ${f.cedula} (${f.nombre_cargo})
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 16px; padding: 12px; background: #f7fafc; border-radius: 8px; display: none;" id="cargo-actual-box">
                            <small style="color: #718096;">Cargo actual: <strong id="cargo-actual-text">-</strong></small>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Nuevo Cargo *</label>
                            <select id="swal-cargo" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione el nuevo cargo...</option>
                                ${cargos.map(c => `
                                    <option value="${c.id}">
                                        ${c.nombre_cargo} (Nivel ${c.nivel_acceso})
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha de Efecto *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 100%; padding: 10px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento (PDF o Imagen) *</label>
                            <input type="file" id="swal-pdf" accept="application/pdf,image/png,image/jpeg" class="swal2-file" style="width: 100%; padding: 10px;">
                            <small style="color: #718096; font-size: 12px;">⚠️ Obligatorio - PDF, JPG o PNG - Máximo 5MB</small>
                            <div id="file-preview" style="margin-top: 12px; display: none;"></div>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Registrar Nombramiento',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#00a8cc',
                didOpen: () => {
                    // Mostrar cargo actual al seleccionar funcionario
                    document.getElementById('swal-funcionario').addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        const cargoActual = selectedOption.dataset.cargo;
                        if (cargoActual) {
                            document.getElementById('cargo-actual-text').textContent = cargoActual;
                            document.getElementById('cargo-actual-box').style.display = 'block';
                        } else {
                            document.getElementById('cargo-actual-box').style.display = 'none';
                        }
                    });
                    
                    // Preview de archivo (imagen o PDF)
                    document.getElementById('swal-pdf').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        const previewContainer = document.getElementById('file-preview');
                        
                        if (!file) {
                            previewContainer.style.display = 'none';
                            return;
                        }
                        
                        const fileType = file.type;
                        const fileName = file.name;
                        const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                        
                        if (fileType.startsWith('image/')) {
                            // Preview de imagen
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                previewContainer.innerHTML = `
                                    <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 12px; padding: 14px; text-align: center;">
                                        <div style="margin-bottom: 10px;">
                                            <img src="${event.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        </div>
                                        <div style="font-size: 12px; color: #166534;">
                                            <strong>📷 ${fileName}</strong><br>
                                            Tamaño: ${fileSize} MB
                                        </div>
                                    </div>
                                `;
                                previewContainer.style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        } else if (fileType === 'application/pdf') {
                            // Icono de PDF
                            previewContainer.innerHTML = `
                                <div style="background: #fef3c7; border: 2px solid #fbbf24; border-radius: 12px; padding: 14px; display: flex; align-items: center; gap: 12px;">
                                    <div style="font-size: 48px;">📄</div>
                                    <div style="flex: 1; font-size: 12px; color: #92400e;">
                                        <strong>${fileName}</strong><br>
                                        Tamaño: ${fileSize} MB
                                    </div>
                                </div>
                            `;
                            previewContainer.style.display = 'block';
                        } else {
                            previewContainer.style.display = 'none';
                        }
                    });
                },
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const nuevo_cargo_id = document.getElementById('swal-cargo').value;
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const archivo_pdf = document.getElementById('swal-pdf').files[0];
                    
                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!nuevo_cargo_id) { Swal.showValidationMessage('Seleccione el nuevo cargo'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!archivo_pdf) { Swal.showValidationMessage('El documento es obligatorio'); return false; }
                    if (archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    
                    const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                    if (!validTypes.includes(archivo_pdf.type)) { 
                        Swal.showValidationMessage('Solo se permiten archivos PDF, JPG o PNG'); 
                        return false; 
                    }
                    
                    return { funcionario_id, nuevo_cargo_id, fecha_evento, archivo_pdf };
                }
            });
            
            if (!formValues) return;
            
            // Procesar
            Swal.fire({ title: 'Procesando...', html: 'Registrando nombramiento...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_nombramiento');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('nuevo_cargo_id', formValues.nuevo_cargo_id);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('archivo_pdf', formValues.archivo_pdf);
                
                const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Nombramiento Registrado',
                        html: `
                            <p>El nombramiento se registró exitosamente.</p>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                                <p style="margin: 0; font-size: 13px;"><strong>✓ Cargo anterior:</strong> ${result.data.cargo_anterior}</p>
                                <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Cargo nuevo:</strong> ${result.data.cargo_nuevo}</p>
                            </div>
                        `,
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al registrar nombramiento', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
