<?php
/**
 * Vista: Módulo de Despidos
 * Gestión y consulta de despidos de personal
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
$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'DESPIDO'");
$total_despidos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'DESPIDO' AND YEAR(fecha_evento) = YEAR(CURDATE())");
$despidos_anio = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'DESPIDO' AND MONTH(fecha_evento) = MONTH(CURDATE()) AND YEAR(fecha_evento) = YEAR(CURDATE())");
$despidos_mes = $stmt->fetch()['total'];

// Obtener departamentos
$departamentos = $db->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();

// Obtener despidos
$stmt = $db->query("
    SELECT 
        ha.id,
        ha.funcionario_id,
        f.cedula,
        f.nombres,
        f.apellidos,
        d.nombre as departamento,
        JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.motivo')) as motivo,
        ha.fecha_evento as fecha_despido,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.created_at
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    LEFT JOIN departamentos d ON f.departamento_id = d.id
    WHERE ha.tipo_evento = 'DESPIDO'
    ORDER BY ha.fecha_evento DESC
");
$despidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despidos - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ef476f 0%, #f78c6b 100%);
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
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
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
                    <div class="stat-label">Total Despidos</div>
                    <div class="stat-value"><?php echo $total_despidos; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Despidos <?php echo date('Y'); ?></div>
                    <div class="stat-value"><?php echo $despidos_anio; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Despidos Este Mes</div>
                    <div class="stat-value"><?php echo $despidos_mes; ?></div>
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
            
            
            <!-- Botón Procesar Despido -->
            <div style="margin-bottom: 24px; display: flex; justify-content: flex-end;">
                <button type="button" onclick="abrirModalDespido()" class="btn" style="padding: 12px 24px; font-size: 16px; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
                    <span style="font-size: 20px;">⚠️</span>
                    Procesar Baja (Despido)
                </button>
            </div>
            
            <!-- Tabla -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">❌ Registro de Despidos</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Departamento</th>
                                <th>Fecha Despido</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($despidos)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">❌</div>
                                        <p>No hay registros de despidos</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($despidos as $desp): ?>
                                    <tr class="despido-row" 
                                        data-empleado="<?php echo strtolower($desp['nombres'] . ' ' . $desp['apellidos'] . ' ' . $desp['cedula']); ?>"
                                        data-departamento="<?php echo $desp['departamento']; ?>"
                                        data-anio="<?php echo date('Y', strtotime($desp['fecha_despido'])); ?>">
                                        <td><strong><?php echo htmlspecialchars($desp['nombres'] . ' ' . $desp['apellidos']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($desp['cedula']); ?></td>
                                        <td><span style="padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($desp['departamento']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($desp['fecha_despido'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($desp['motivo'], 0, 60)) . (strlen($desp['motivo']) > 60 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="../funcionarios/ver.php?id=<?php echo $desp['funcionario_id']; ?>" class="btn" style="padding: 4px 12px; font-size: 12px;">Ver</a>
                                            <?php if ($desp['ruta_archivo']): ?>
                                                <a href="../../<?php echo $desp['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 4px 12px; font-size: 12px;">📥</a>
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
    
    <!-- Filtros en Tiempo Real -->
    <script src="../../publico/js/filtros-tiempo-real.js"></script>
    <script>
        inicializarFiltros({
            module: 'despidos',
            searchId: 'search-empleado',
            filterIds: ['filter-departamento', 'filter-anio'],
            tableBodySelector: 'table tbody',
            countSelector: '.card-subtitle'
        });

        // Función para abrir modal de despido
        async function abrirModalDespido() {
            // Obtener lista de funcionarios activos
            const funcionariosResponse = await fetch('../funcionarios/ajax/listar.php');
            const funcionarios = await funcionariosResponse.json();

            if (!funcionarios.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los funcionarios'
                });
                return;
            }

            // Filtrar solo funcionarios activos
            const funcionariosActivos = funcionarios.data.filter(f => f.estado === 'activo');

            if (funcionariosActivos.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin Funcionarios',
                    text: 'No hay funcionarios activos para procesar'
                });
                return;
            }

            // Crear opciones
            const funcionariosOptions = funcionariosActivos
                .map(f => `<option value="${f.id}">${f.nombres} ${f.apellidos} (${f.cedula}) - ${f.cargo_nombre || 'Sin cargo'}</option>`)
                .join('');

            const { value: formValues } = await Swal.fire({
                title: '⚠️ Procesar Baja por Despido',
                html: `
                    <div style="background: #fef2f2; border: 2px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 12px; color: #991b1b;">
                            <span style="font-size: 32px;">⚠️</span>
                            <div style="text-align: left; flex: 1;">
                                <strong style="display: block; font-size: 16px; margin-bottom: 4px;">ADVERTENCIA IMPORTANTE</strong>
                                <p style="margin: 0; font-size: 14px;">Esta acción es <strong>IRREVERSIBLE</strong>. El funcionario y su usuario serán <strong>DESACTIVADOS</strong> permanentemente en el sistema.</p>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: left;">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Funcionario a Despedir *</label>
                            <select id="swal-funcionario" class="swal2-input" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <option value="">Seleccione un funcionario...</option>
                                ${funcionariosOptions}
                            </select>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Fecha del Despido *</label>
                            <input type="date" id="swal-fecha" class="swal2-input" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" value="${new Date().toISOString().split('T')[0]}">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Motivo del Despido *</label>
                            <textarea id="swal-motivo" class="swal2-textarea" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; min-height: 120px;" placeholder="Describa detalladamente el motivo del despido..."></textarea>
                            <small style="color: #718096; font-size: 12px;">Este campo es obligatorio y debe tener al menos 20 caracteres.</small>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748;">Documento PDF (Opcional)</label>
                            <input type="file" id="swal-archivo" accept=".pdf" class="swal2-file" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <small style="color: #718096; font-size: 12px;">Resolución o documento oficial (Tamaño máximo: 5MB)</small>
                        </div>
                    </div>
                `,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: 'Confirmar Despido',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                preConfirm: () => {
                    const funcionario_id = document.getElementById('swal-funcionario').value;
                    const fecha_evento = document.getElementById('swal-fecha').value;
                    const motivo = document.getElementById('swal-motivo').value;
                    const archivo = document.getElementById('swal-archivo').files[0];

                    // Validaciones
                    if (!funcionario_id) {
                        Swal.showValidationMessage('Debe seleccionar un funcionario');
                        return false;
                    }
                    if (!fecha_evento) {
                        Swal.showValidationMessage('Debe ingresar la fecha del despido');
                        return false;
                    }
                    if (!motivo || motivo.trim().length < 20) {
                        Swal.showValidationMessage('El motivo debe tener al menos 20 caracteres');
                        return false;
                    }
                    if (archivo && archivo.size > 5 * 1024 * 1024) {
                        Swal.showValidationMessage('El archivo no puede superar 5MB');
                        return false;
                    }
                    if (archivo && archivo.type !== 'application/pdf') {
                        Swal.showValidationMessage('Solo se permiten archivos PDF');
                        return false;
                    }

                    return {
                        funcionario_id,
                        fecha_evento,
                        motivo,
                        archivo
                    };
                }
            });

            if (formValues) {
                // Segunda confirmación con advertencia más fuerte
                const confirmacion = await Swal.fire({
                    title: '¿Está completamente seguro?',
                    html: `
                        <div style="text-align: left; color: #1f2937;">
                            <p style="margin-bottom: 12px;"><strong>Al confirmar esta acción:</strong></p>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">El funcionario será marcado como <strong>INACTIVO</strong></li>
                                <li style="margin-bottom: 8px;">Su usuario será <strong>DESACTIVADO</strong> y no podrá ingresar al sistema</li>
                                <li style="margin-bottom: 8px;">Esta acción quedará registrada en el historial administrativo</li>
                                <li style="margin-bottom: 8px;">La acción <strong>NO SE PUEDE DESHACER</strong></li>
                            </ul>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, procesar despido',
                    cancelButtonText: 'No, cancelar',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#10b981'
                });

                if (confirmacion.isConfirmed) {
                    registrarDespido(formValues);
                }
            }
        }

        // Función para registrar el despido
        async function registrarDespido(data) {
            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                html: 'Registrando despido y desactivando funcionario...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                // Preparar FormData
                const formData = new FormData();
                formData.append('accion', 'registrar_despido');
                formData.append('funcionario_id', data.funcionario_id);
                formData.append('tipo_evento', 'DESPIDO');
                formData.append('fecha_evento', data.fecha_evento);
                formData.append('motivo', data.motivo);
                
                if (data.archivo) {
                    formData.append('archivo_pdf', data.archivo);
                }

                // Enviar al backend
                const response = await fetch('../funcionarios/ajax/gestionar_historial.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Despido Procesado',
                        html: `
                            <p>El despido se ha registrado exitosamente.</p>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-top: 16px; text-align: left;">
                                <p style="margin: 0; font-size: 14px;"><strong>✓ Funcionario:</strong> Inactivo</p>
                                <p style="margin: 8px 0 0 0; font-size: 14px;"><strong>✓ Usuario:</strong> Desactivado</p>
                            </div>
                        `,
                        confirmButtonColor: '#10b981'
                    });

                    // Recargar página
                    window.location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Procesar',
                        text: result.error || 'Ocurrió un error al registrar el despido',
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Por favor, inténtelo de nuevo.',
                    confirmButtonColor: '#ef4444'
                });
            }
        }
    </script>
</body>
</html>
