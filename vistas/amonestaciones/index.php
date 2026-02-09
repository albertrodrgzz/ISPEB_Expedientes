<?php
/**
 * Vista: Módulo de Amonestaciones
 * Gestión y consulta de amonestaciones disciplinarias
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1 y 2)
verificarSesion();

if (!verificarNivel(2)) {
    $_SESSION['error'] = 'No tiene permisos para acceder al módulo de amonestaciones';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener estadísticas para el dashboard de amonestaciones
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION'");
$total_amonestaciones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$amonestaciones_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT funcionario_id) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$empleados_con_amonestaciones = $stmt->fetch()['total'];

// Obtener tipo de falta más común
$stmt = $db->query("
    SELECT JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.tipo_falta')) as tipo_falta, COUNT(*) as cantidad 
    FROM historial_administrativo 
    WHERE tipo_evento = 'AMONESTACION' AND JSON_EXTRACT(detalles, '$.tipo_falta') IS NOT NULL
    GROUP BY JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.tipo_falta')) 
    ORDER BY cantidad DESC 
    LIMIT 1
");
$falta_comun = $stmt->fetch();
$tipo_falta_comun = $falta_comun ? $falta_comun['tipo_falta'] : 'N/A';

// Obtener departamentos para filtros
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener tipos de falta únicos
$tipos_falta = $db->query("
    SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.tipo_falta')) as tipo_falta 
    FROM historial_administrativo 
    WHERE tipo_evento = 'AMONESTACION' AND JSON_EXTRACT(detalles, '$.tipo_falta') IS NOT NULL
    ORDER BY tipo_falta
")->fetchAll();

// Obtener registros de amonestaciones con información del funcionario
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        ha.fecha_evento as fecha_falta,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta')) as tipo_falta,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.sancion_aplicada')) as sancion_aplicada,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.titulo')) as titulo,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.descripcion')) as motivo,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.nombre_archivo_original,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
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
    <title>Amonestaciones - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ef476f 0%, #d62828 100%);
            color: white;
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #ff9f1c 0%, #ffd166 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
        
        .stat-subtitle {
            font-size: 12px;
            opacity: 0.85;
            margin-top: 8px;
        }
        
        .filter-panel {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border-radius: 2px;
        }
        
        .amonestaciones-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .amonestaciones-table thead {
            background: linear-gradient(135deg, #ef476f, #d62828);
            color: white;
        }
        
        .amonestaciones-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .amonestaciones-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--color-border-light);
            font-size: 14px;
        }
        
        .amonestaciones-table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .amonestaciones-table tbody tr:hover {
            background-color: #fef2f2;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-leve {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-grave {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-muy-grave {
            background: #fecaca;
            color: #7f1d1d;
        }
        
        .btn-icon {
            padding: 8px 12px;
            font-size: 12px;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view {
            background: #3b82f6;
            color: white;
        }
        
        .btn-view:hover {
            background: #2563eb;
        }
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn-download:hover {
            background: #059669;
        }
        
        .no-results {
            text-align: center;
            padding: 48px;
            color: var(--color-text-light);
        }
        
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">⚠️ Gestión de Amonestaciones</h1>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estadísticas Rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Amonestaciones</div>
                    <div class="stat-value"><?php echo $total_amonestaciones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Amonestaciones (<?php echo date('Y'); ?>)</div>
                    <div class="stat-value"><?php echo $amonestaciones_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Empleados Afectados (<?php echo date('Y'); ?>)</div>
                    <div class="stat-value"><?php echo $empleados_con_amonestaciones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Falta Más Común</div>
                    <div class="stat-value" style="font-size: 20px; line-height: 1.2;">
                        <?php echo htmlspecialchars($tipo_falta_comun); ?>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Filtros -->
            <div class="filter-panel">
                <h3 class="section-title">🔍 Filtros de Búsqueda</h3>
                <div class="filter-grid">
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar</label>
                        <input type="text" id="searchAmonestaciones" class="search-input" placeholder="🔍 Buscar amonestación..." style="width: 100%;">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Departamento</label>
                        <select id="filter-departamento" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['nombre']; ?>"><?php echo htmlspecialchars($dept['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Tipo de Falta</label>
                        <select id="filter-tipo-falta" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php foreach ($tipos_falta as $tipo): ?>
                                <?php if ($tipo['tipo_falta']): ?>
                                    <option value="<?php echo $tipo['tipo_falta']; ?>"><?php echo htmlspecialchars($tipo['tipo_falta']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Año</label>
                        <select id="filter-anio" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($i = $currentYear; $i >= $currentYear - 5; $i--): 
                            ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: flex-end; gap: 8px;">
                        <button type="button" onclick="limpiarFiltros()" class="btn" style="flex: 1; background: #e2e8f0; color: #2d3748;">
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Amonestaciones -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">📋 Registro de Amonestaciones</h2>
                        <p class="card-subtitle"><?php echo count($amonestaciones); ?> registros</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="abrirModalAmonestacion()" class="btn btn-primary" style="padding: 10px 20px; display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">⚠️</span>
                            Registrar Falta
                        </button>
                        <a href="../reportes/index.php#amonestaciones" class="btn" style="text-decoration: none; background: #e2e8f0; color: #2d3748;">
                            📄 Generar Reporte
                        </a>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="amonestaciones-table" id="amonestacionesTable">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Falta</th>
                                <th>Tipo de Falta</th>
                                <th>Sanción Aplicada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="amonestaciones-tbody">
                            <?php if (empty($amonestaciones)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="no-results">
                                            <div class="no-results-icon">⚠️</div>
                                            <p>No hay registros de amonestaciones</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($amonestaciones as $amon): ?>
                                    <tr class="amonestacion-row" 
                                        data-empleado="<?php echo strtolower($amon['nombres'] . ' ' . $amon['apellidos'] . ' ' . $amon['cedula']); ?>"
                                        data-departamento="<?php echo $amon['departamento']; ?>"
                                        data-tipo-falta="<?php echo $amon['tipo_falta']; ?>"
                                        data-anio="<?php echo date('Y', strtotime($amon['fecha_falta'])); ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($amon['nombres'] . ' ' . $amon['apellidos']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($amon['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($amon['departamento']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($amon['fecha_falta'])); ?></td>
                                        <td>
                                            <?php if ($amon['tipo_falta']): ?>
                                                <span class="badge badge-<?php echo strtolower(str_replace([' ', '-'], '', $amon['tipo_falta'])); ?>">
                                                    <?php echo htmlspecialchars($amon['tipo_falta']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">No especificado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($amon['sancion_aplicada'] ?: 'No especificada'); ?></td>
                                        <td>
                                            <a href="../funcionarios/view.php?id=<?php echo $amon['funcionario_id']; ?>" 
                                               class="btn-icon btn-view" 
                                               title="Ver Empleado">
                                                👁️
                                            </a>
                                            <?php if ($amon['ruta_archivo']): ?>
                                                <a href="../../<?php echo $amon['ruta_archivo']; ?>" 
                                                   class="btn-icon btn-download" 
                                                   target="_blank"
                                                   title="Descargar Documento">
                                                    📥
                                                </a>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Real-time search filter
        document.getElementById('searchAmonestaciones')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#amonestacionesTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Limpiar filtros
        function limpiarFiltros() {
            document.getElementById('searchAmonestaciones').value = '';
            document.getElementById('filter-departamento').value = '';
            document.getElementById('filter-tipo-falta').value = '';
            document.getElementById('filter-anio').value = '';
            aplicarFiltros();
        }
        
        // Aplicar filtros combinados
        function aplicarFiltros() {
            const searchTerm = document.getElementById('searchAmonestaciones').value.toLowerCase();
            const departamento = document.getElementById('filter-departamento').value;
            const tipoFalta = document.getElementById('filter-tipo-falta').value;
            const anio = document.getElementById('filter-anio').value;
            const rows = document.querySelectorAll('.amonestacion-row');
            
            rows.forEach(row => {
                const matchSearch = row.dataset.empleado.includes(searchTerm);
                const matchDept = !departamento || row.dataset.departamento === departamento;
                const matchTipo = !tipoFalta || row.dataset.tipoFalta === tipoFalta;
                const matchAnio = !anio || row.dataset.anio === anio;
                
                row.style.display = (matchSearch && matchDept && matchTipo && matchAnio) ? '' : 'none';
            });
        }
        
        document.getElementById('filter-departamento')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-tipo-falta')?.addEventListener('change', aplicarFiltros);
        document.getElementById('filter-anio')?.addEventListener('change', aplicarFiltros);
        
        // ===========================================
        // MODAL REGISTRAR AMONESTACION
        // ===========================================
        
        async function abrirModalAmonestacion() {
            // Cargar funcionarios activos
            const response = await fetch('../funcionarios/ajax/listar.php');
            const data = await response.json();
            
            if (!data.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }
            
            const funcionarios = data.data.filter(f => f.estado === 'activo');
            
            const { value: formValues } = await Swal.fire({
                title: '⚠️ Registrar Falta Disciplinaria',
                html: `
                    <div style="text-align: left;">
                        <div style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                            <div style="display: flex; align-items: center; gap: 10px; color: #991b1b;">
                                <span style="font-size: 24px;">⚠️</span>
                                <p style="margin: 0; font-size: 13px;">El documento PDF es OBLIGATORIO para registrar una amonestación.</p>
                            </div>
                        </div>
                        
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
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Tipo de Falta *</label>
                            <select id="swal-tipo-falta" class="swal2-input" style="width: 100%; padding: 10px;">
                                <option value="">Seleccione el tipo...</option>
                                <option value="leve">🟡 Leve</option>
                                <option value="grave">🟠 Grave</option>
                                <option value="muy_grave">🔴 Muy Grave</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Motivo de la Falta *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" rows="3" placeholder="Describa el motivo de la falta..." style="width: 95%; padding: 10px; resize: vertical;"></textarea>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Sanción Aplicada *</label>
                            <input type="text" id="swal-sancion" class="swal2-input" placeholder="Ej: Amonestación escrita" style="width: 95%; padding: 10px;">
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha del Evento *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 95%; padding: 10px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento PDF *</label>
                            <input type="file" id="swal-pdf" accept=".pdf" class="swal2-file" style="width: 100%; padding: 10px;">
                            <small style="color: #dc2626; font-size: 12px; font-weight: 600;">⚠️ OBLIGATORIO - Máximo 5MB</small>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Registrar Amonestación',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const tipo_falta = document.getElementById('swal-tipo-falta').value;
                    const motivo = document.getElementById('swal-motivo').value.trim();
                    const sancion = document.getElementById('swal-sancion').value.trim();
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const archivo_pdf = document.getElementById('swal-pdf').files[0];
                    
                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!tipo_falta) { Swal.showValidationMessage('Seleccione el tipo de falta'); return false; }
                    if (!motivo) { Swal.showValidationMessage('Ingrese el motivo'); return false; }
                    if (!sancion) { Swal.showValidationMessage('Ingrese la sanción aplicada'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!archivo_pdf) { Swal.showValidationMessage('El documento PDF es OBLIGATORIO'); return false; }
                    if (archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    if (archivo_pdf.type !== 'application/pdf') { Swal.showValidationMessage('Solo archivos PDF'); return false; }
                    
                    return { funcionario_id, tipo_falta, motivo, sancion, fecha_evento, archivo_pdf };
                }
            });
            
            if (!formValues) return;
            
            // Procesar
            Swal.fire({ title: 'Procesando...', html: 'Registrando amonestación...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_amonestacion');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('tipo_falta', formValues.tipo_falta);
                formData.append('motivo', formValues.motivo);
                formData.append('sancion', formValues.sancion);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('archivo_pdf', formValues.archivo_pdf);
                
                const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Amonestación Registrada',
                        html: `
                            <p>La amonestación se registró correctamente.</p>
                            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                                <p style="margin: 0; font-size: 13px;"><strong>✓ Tipo:</strong> ${formValues.tipo_falta}</p>
                                <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Fecha:</strong> ${formValues.fecha_evento}</p>
                            </div>
                        `,
                        confirmButtonColor: '#dc2626'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al registrar amonestación', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
