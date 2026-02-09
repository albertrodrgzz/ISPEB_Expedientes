<?php
/**
 * Vista: Módulo de Remociones
 * Gestión y consulta de remociones de personal
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
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'REMOCION'");
$total_remociones = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'REMOCION' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$remociones_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'REMOCION' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$remociones_mes = $stmt->fetch()['total'];

// Obtener departamentos
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener remociones
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento as fecha_remocion,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'REMOCION'
    ORDER BY ha.fecha_evento DESC
");
$remociones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remociones - <?php echo APP_NAME; ?></title>
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
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
            background: linear-gradient(135deg, #c44569 0%, #f8b500 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #ee5a6f 0%, #f29263 100%);
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
                    <div class="stat-label">Total Remociones</div>
                    <div class="stat-value"><?php echo $total_remociones; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Remociones <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $remociones_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Remociones Este Mes</div>
                    <div class="stat-value"><?php echo $remociones_mes; ?></div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filter-panel">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">🔍 Filtros de Búsqueda</h3>
                <div class="filter-grid">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar Empleado</label>
                        <input type="text" id="search-empleado" class="search-input" placeholder="Nombre, apellido o cédula..." style="width: 100%;">
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
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Año</label>
                        <select id="filter-anio" class="search-input" style="width: 100%;">
                            <option value="">Todos</option>
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
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
                    <div>
                        <h2 class="card-title">🚫 Registro de Remociones</h2>
                        <p class="card-subtitle"><?php echo count($remociones); ?> registros</p>
                    </div>
                    <button onclick="abrirModalRemocion()" class="btn btn-primary" style="padding: 10px 20px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">🚫</span>
                        Procesar Remoción
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Remoción</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($remociones)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🚫</div>
                                        <p>No hay registros de remociones</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($remociones as $rem): ?>
                                    <tr class="remocion-row" 
                                        data-empleado="<?php echo strtolower($rem['nombres'] . ' ' . $rem['apellidos'] . ' ' . $rem['cedula']); ?>"
                                        data-departamento="<?php echo $rem['departamento']; ?>"
                                        data-anio="<?php echo date('Y', strtotime($rem['fecha_remocion'])); ?>">
                                        <td><strong><?php echo htmlspecialchars($rem['nombres'] . ' ' . $rem['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($rem['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #fee2e2; color: #991b1b; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($rem['departamento']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($rem['fecha_remocion'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($rem['motivo'], 0, 60)) . (strlen($rem['motivo']) > 60 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $rem['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($rem['ruta_archivo']): ?>
                                                <a href="../../<?php echo $rem['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
    <!-- Filtros en Tiempo Real -->
    <script src="../../publico/js/filtros-tiempo-real.js"></script>
    <script>
        inicializarFiltros({
            module: 'remociones',
            searchId: 'search-empleado',
            filterIds: ['filter-departamento', 'filter-anio'],
            tableBodySelector: 'table tbody',
            countSelector: '.card-subtitle'
        });
        
        // Limpiar filtros
        function limpiarFiltros() {
            document.getElementById('search-empleado').value = '';
            document.getElementById('filter-departamento').value = '';
            document.getElementById('filter-anio').value = '';
            // Trigger filtros
            const event = new Event('input');
            document.getElementById('search-empleado').dispatchEvent(event);
        }
        
        // ===========================================
        // MODAL PROCESAR REMOCION
        // ===========================================
        
        async function abrirModalRemocion() {
            // Cargar funcionarios activos
            const response = await fetch('../funcionarios/ajax/listar.php');
            const data = await response.json();
            
            if (!data.success) {
                Swal.fire('Error', 'No se pudieron cargar los funcionarios', 'error');
                return;
            }
            
            const funcionarios = data.data.filter(f => f.estado === 'activo');
            
            const { value: formValues } = await Swal.fire({
                title: '🚫 Procesar Remoción de Cargo',
                html: `
                    <div style="text-align: left;">
                        <div style="background: #fee2e2; border: 2px solid #dc2626; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                            <div style="display: flex; align-items: center; gap: 10px; color: #7f1d1d;">
                                <span style="font-size: 24px;">⚠️</span>
                                <p style="margin: 0; font-size: 13px;">La remoción implica la separación del funcionario de su cargo. El documento PDF es OBLIGATORIO.</p>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario a Remover *</label>
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
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Motivo de la Remoción *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" rows="4" placeholder="Describa el motivo de la remoción..." style="width: 95%; padding: 10px; resize: vertical;"></textarea>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha de Efecto *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 95%; padding: 10px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento PDF (Resolución/Oficio) *</label>
                            <input type="file" id="swal-pdf" accept=".pdf" class="swal2-file" style="width: 100%; padding: 10px;">
                            <small style="color: #dc2626; font-size: 12px; font-weight: 600;">⚠️ OBLIGATORIO - Máximo 5MB</small>
                        </div>
                        
                        <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 12px; margin-top: 16px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" id="swal-mantener-activo" style="width: 18px; height: 18px;">
                                <span>Mantener funcionario activo (sin asignación de cargo)</span>
                            </label>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Procesar Remoción',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const motivo = document.getElementById('swal-motivo').value.trim();
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const archivo_pdf = document.getElementById('swal-pdf').files[0];
                    const mantener_activo = document.getElementById('swal-mantener-activo').checked;
                    
                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!motivo) { Swal.showValidationMessage('Ingrese el motivo de la remoción'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!archivo_pdf) { Swal.showValidationMessage('El documento PDF es OBLIGATORIO'); return false; }
                    if (archivo_pdf.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    if (archivo_pdf.type !== 'application/pdf') { Swal.showValidationMessage('Solo archivos PDF'); return false; }
                    
                    return { funcionario_id, motivo, fecha_evento, archivo_pdf, mantener_activo };
                }
            });
            
            if (!formValues) return;
            
            // Procesar
            Swal.fire({ title: 'Procesando...', html: 'Registrando remoción...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generarTokenCSRF(); ?>');
                formData.append('accion', 'registrar_remocion');
                formData.append('funcionario_id', formValues.funcionario_id);
                formData.append('motivo', formValues.motivo);
                formData.append('fecha_evento', formValues.fecha_evento);
                formData.append('mantener_activo', formValues.mantener_activo ? '1' : '0');
                formData.append('archivo_pdf', formValues.archivo_pdf);
                
                const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Remoción Procesada',
                        html: `
                            <p>La remoción se procesó correctamente.</p>
                            <div style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; margin-top: 14px; text-align: left;">
                                <p style="margin: 0; font-size: 13px;"><strong>✓ Fecha:</strong> ${formValues.fecha_evento}</p>
                                <p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Estado:</strong> ${formValues.mantener_activo ? 'Activo sin cargo' : 'Inactivo'}</p>
                            </div>
                        `,
                        confirmButtonColor: '#dc2626'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al procesar remoción', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
