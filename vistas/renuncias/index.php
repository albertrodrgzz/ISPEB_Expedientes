<?php
/**
 * Vista: Módulo de Renuncias
 * Gestión y consulta de renuncias de personal
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
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'RENUNCIA'");
$total_renuncias = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'RENUNCIA' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$renuncias_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'RENUNCIA' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$renuncias_mes = $stmt->fetch()['total'];

// Obtener departamentos
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener renuncias
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento as fecha_renuncia,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'RENUNCIA'
    ORDER BY ha.fecha_evento DESC
");
$renuncias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renuncias - <?php echo APP_NAME; ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
                    <div class="stat-label">Total Renuncias</div>
                    <div class="stat-value"><?php echo $total_renuncias; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Renuncias <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $renuncias_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Renuncias Este Mes</div>
                    <div class="stat-value"><?php echo $renuncias_mes; ?></div>
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
            
            
            <!-- Botón Procesar Renuncia -->
            <div style="margin-bottom: 24px; display: flex; justify-content: flex-end;">
                <button type="button" onclick="abrirModalRenuncia()" class="btn btn-primary" style="padding: 12px 24px; font-size: 16px; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #6366f1, #3b82f6);">
                    <span style="font-size: 20px;">📝</span>
                    Procesar Baja (Renuncia)
                </button>
            </div>
            
            <!-- Tabla -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📝 Registro de Renuncias</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Renuncia</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($renuncias)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📝</div>
                                        <p>No hay registros de renuncias</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($renuncias as $ren): ?>
                                    <tr class="renuncia-row" 
                                        data-empleado="<?php echo strtolower($ren['nombres'] . ' ' . $ren['apellidos'] . ' ' . $ren['cedula']); ?>"
                                        data-departamento="<?php echo $ren['departamento']; ?>"
                                        data-anio="<?php echo date('Y', strtotime($ren['fecha_renuncia'])); ?>">
                                        <td><strong><?php echo htmlspecialchars($ren['nombres'] . ' ' . $ren['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ren['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #fef3c7; color: #92400e; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($ren['departamento']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($ren['fecha_renuncia'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($ren['motivo'], 0, 60)) . (strlen($ren['motivo']) > 60 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $ren['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($ren['ruta_archivo']): ?>
                                                <a href="../../<?php echo $ren['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
            module: 'renuncias',
            searchId: 'search-empleado',
            filterIds: ['filter-departamento', 'filter-anio'],
            tableBodySelector: 'table tbody',
            countSelector: '.card-subtitle'
        });

        // Función para abrir modal de renuncia
        async function abrirModalRenuncia() {
            const funcionariosResponse = await fetch('../funcionarios/ajax/listar.php');
            const funcionarios = await funcionariosResponse.json();

            if (!funcionarios.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los funcionarios' });
                return;
            }

            const funcionariosActivos = funcionarios.data.filter(f => f.estado === 'activo');
            if (funcionariosActivos.length === 0) {
                Swal.fire({ icon: 'info', title: 'Sin Funcionarios', text: 'No hay funcionarios activos' });
                return;
            }

            const funcionariosOptions = funcionariosActivos
                .map(f => `<option value="${f.id}">${f.nombres} ${f.apellidos} (${f.cedula})</option>`)
                .join('');

            const { value: formValues } = await Swal.fire({
                title: '📝 Procesar Renuncia Voluntaria',
                html: `
                    <div style="background: #eff6ff; border: 2px solid #93c5fd; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
                        <div style="display: flex; align-items: center; gap: 10px; color: #1e40af;">
                            <span style="font-size: 28px;">ℹ️</span>
                            <div style="text-align: left; flex: 1;">
                                <strong style="display: block; font-size: 15px; margin-bottom: 3px;">Información Importante</strong>
                                <p style="margin: 0; font-size: 13px;">El funcionario será <strong>DESACTIVADO</strong> en el sistema al confirmar esta renuncia.</p>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: left;">
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Funcionario que Renuncia *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 9px; border: 1px solid #e2e8f0; border-radius: 7px;">
                                <option value="">Seleccione...</option>
                                ${funcionariosOptions}
                            </select>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Fecha de Renuncia *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 100%; padding: 9px; border: 1px solid #e2e8f0; border-radius: 7px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Motivo / Observaciones *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" style="width: 100%; padding: 9px; border: 1px solid #e2e8f0; border-radius: 7px; min-height: 100px;" placeholder="Ejemplo: Renuncia voluntaria - Motivos personales"></textarea>
                            <small style="color: #718096; font-size: 12px;">Mínimo 15 caracteres</small>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 7px; color: #2d3748;">Carta de Renuncia PDF (Opcional)</label>
                            <input type="file" id="swal-archivo" accept=".pdf" class="swal2-file" style="width: 100%; padding: 9px; border: 1px solid #e2e8f0; border-radius: 7px;">
                            <small style="color: #718096; font-size: 12px;">Tamaño máximo: 5MB</small>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Procesar Renuncia',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const archivo = document.getElementById('swal-archivo').files[0];

                    if (!funcionario_id) { Swal.showValidationMessage('Seleccione un funcionario'); return false; }
                    if (!fecha_evento) { Swal.showValidationMessage('Ingrese la fecha'); return false; }
                    if (!motivo || motivo.trim().length < 15) { Swal.showValidationMessage('El motivo debe tener al menos 15 caracteres'); return false; }
                    if (archivo && archivo.size > 5 * 1024 * 1024) { Swal.showValidationMessage('Archivo muy grande (máx 5MB)'); return false; }
                    if (archivo && archivo.type !== 'application/pdf') { Swal.showValidationMessage('Solo archivos PDF'); return false; }

                    return { funcionario_id, fecha_evento, motivo, archivo };
                }
            });

            if (formValues) {
                registrarRenuncia(formValues);
            }
        }

        async function registrarRenuncia(data) {
            Swal.fire({ title: 'Procesando...', html: 'Registrando renuncia...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const formData = new FormData();
                formData.append('accion', 'registrar_despido');
                formData.append('funcionario_id', data.funcionario_id);
                formData.append('tipo_evento', 'RENUNCIA');
                formData.append('fecha_evento', data.fecha_evento);
                formData.append('motivo', data.motivo);
                if (data.archivo) formData.append('archivo_pdf', data.archivo);

                const response = await fetch('../funcionarios/ajax/gestionar_historial.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Renuncia Procesada',
                        html: '<p>La renuncia se registró exitosamente.</p><div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 10px; margin-top: 14px; text-align: left;"><p style="margin: 0; font-size: 13px;"><strong>✓ Funcionario:</strong> Inactivo</p><p style="margin: 6px 0 0 0; font-size: 13px;"><strong>✓ Usuario:</strong> Desactivado</p></div>',
                        confirmButtonColor: '#10b981'
                    });
                    window.location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'Error al procesar', confirmButtonColor: '#ef4444' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo connect ar al servidor', confirmButtonColor: '#ef4444' });
            }
        }
    </script>
</body>
</html>
