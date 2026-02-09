<?php
/**
 * Vista: Expedientes Digitales
 * Listado centralizado de todos los documentos del sistema
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión
verificarSesion();

// Obtener filtros
$filtros = [
    'buscar' => $_GET['buscar'] ?? '',
    'tipo_documento' => $_GET['tipo_documento'] ?? '',
    'funcionario_id' => $_GET['funcionario_id'] ?? ''
];

// Obtener documentos combinados de las tablas existentes
$db = getDB();

// Obtener estadísticas
$stats_nombramientos = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'")->fetchColumn();
$stats_vacaciones = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'VACACION'")->fetchColumn();
$stats_amonestaciones = $db->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'AMONESTACION'")->fetchColumn();

$stats = [
    'total' => $stats_nombramientos + $stats_vacaciones + $stats_amonestaciones,
    'nombramientos' => $stats_nombramientos,
    'vacaciones' => $stats_vacaciones,
    'amonestaciones' => $stats_amonestaciones,
    'reposos' => 0
];

// Construir consulta desde historial_administrativo
$sql = "
    SELECT 
        ha.id,
        ha.funcionario_id,
        ha.tipo_evento as tipo_documento,
        CASE 
            WHEN ha.tipo_evento = 'NOMBRAMIENTO' THEN CONCAT('Nombramiento - ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.categoria')))
            WHEN ha.tipo_evento = 'VACACION' THEN CONCAT('Vacaciones - ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.periodo')))
            WHEN ha.tipo_evento = 'AMONESTACION' THEN CONCAT('Amonestación - ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta')))
            ELSE ha.tipo_evento
        END as titulo,
        CASE 
            WHEN ha.tipo_evento = 'NOMBRAMIENTO' THEN JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.descripcion'))
            WHEN ha.tipo_evento = 'VACACION' THEN CONCAT(JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.dias_totales')), ' días')
            WHEN ha.tipo_evento = 'AMONESTACION' THEN JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.sancion_aplicada'))
            ELSE NULL
        END as descripcion,
        ha.fecha_evento as fecha_inicio,
        ha.fecha_fin,
        ha.ruta_archivo_pdf as ruta_archivo,
        ha.nombre_archivo_original,
        ha.created_at,
        CONCAT(f.nombres, ' ', f.apellidos) AS nombre_funcionario,
        f.cedula,
        f.foto AS foto,
        c.nombre_cargo,
        d.nombre AS departamento
    FROM historial_administrativo ha
    INNER JOIN funcionarios f ON ha.funcionario_id = f.id
    INNER JOIN cargos c ON f.cargo_id = c.id
    INNER JOIN departamentos d ON f.departamento_id = d.id
    WHERE 1=1
";

$params = [];

// Aplicar filtros
if (!empty($filtros['buscar'])) {
    $sql .= " AND (CONCAT(f.nombres, ' ', f.apellidos) LIKE ? OR f.cedula LIKE ?)";
    $buscar = '%' . $filtros['buscar'] . '%';
    $params[] = $buscar;
    $params[] = $buscar;
}

if (!empty($filtros['funcionario_id'])) {
    $sql .= " AND ha.funcionario_id = ?";
    $params[] = $filtros['funcionario_id'];
}

if (!empty($filtros['tipo_documento'])) {
    $tipo_map = [
        'nombramiento' => 'NOMBRAMIENTO',
        'vacaciones' => 'VACACION',
        'amonestacion' => 'AMONESTACION'
    ];
    if (isset($tipo_map[$filtros['tipo_documento']])) {
        $sql .= " AND ha.tipo_evento = ?";
        $params[] = $tipo_map[$filtros['tipo_documento']];
    }
}

$sql .= " ORDER BY ha.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();

// Obtener lista de funcionarios para filtro
$funcionarios = $db->query("SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre_completo FROM funcionarios ORDER BY nombres, apellidos")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expedientes Digitales - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-card:nth-child(5) {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .doc-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .doc-type-nombramiento {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .doc-type-vacaciones {
            background: #fef3c7;
            color: #92400e;
        }
        
        .doc-type-amonestacion {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .doc-type-reposo {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .doc-type-despido {
            background: #fecaca;
            color: #7f1d1d;
        }
        
        .doc-type-renuncia {
            background: #fed7aa;
            color: #7c2d12;
        }
        
        .doc-type-otro {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Expedientes Digitales</h1>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Documentos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['nombramientos']); ?></div>
                    <div class="stat-label">Nombramientos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['vacaciones']); ?></div>
                    <div class="stat-label">Vacaciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['amonestaciones']); ?></div>
                    <div class="stat-label">Amonestaciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['reposos']); ?></div>
                    <div class="stat-label">Reposos</div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2 class="card-title">Filtros de Búsqueda</h2>
                </div>
                <div style="padding: 24px;">
                    <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div class="form-group" style="margin: 0;">
                            <label for="buscar" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Buscar</label>
                            <input 
                                type="text" 
                                id="buscar" 
                                name="buscar" 
                                class="search-input" 
                                placeholder="Nombre, cédula, título..."
                                value="<?php echo htmlspecialchars($filtros['buscar']); ?>"
                                style="width: 100%;"
                            >
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label for="tipo_documento" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Tipo de Documento</label>
                            <select id="tipo_documento" name="tipo_documento" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <option value="nombramiento" <?php echo $filtros['tipo_documento'] == 'nombramiento' ? 'selected' : ''; ?>>Nombramiento</option>
                                <option value="vacaciones" <?php echo $filtros['tipo_documento'] == 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                <option value="reposo" <?php echo $filtros['tipo_documento'] == 'reposo' ? 'selected' : ''; ?>>Reposo</option>
                                <option value="amonestacion" <?php echo $filtros['tipo_documento'] == 'amonestacion' ? 'selected' : ''; ?>>Amonestación</option>
                                <option value="despido" <?php echo $filtros['tipo_documento'] == 'despido' ? 'selected' : ''; ?>>Despido</option>
                                <option value="renuncia" <?php echo $filtros['tipo_documento'] == 'renuncia' ? 'selected' : ''; ?>>Renuncia</option>
                                <option value="otro" <?php echo $filtros['tipo_documento'] == 'otro' ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label for="funcionario_id" style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Funcionario</label>
                            <select id="funcionario_id" name="funcionario_id" class="search-input" style="width: 100%;">
                                <option value="">Todos</option>
                                <?php foreach ($funcionarios as $func): ?>
                                    <option value="<?php echo $func['id']; ?>" <?php echo $filtros['funcionario_id'] == $func['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($func['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: flex; align-items: flex-end; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Filtrar</button>
                            <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabla de Documentos -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Listado de Documentos</h2>
                        <p class="card-subtitle"><?php echo count($documentos); ?> documento(s) encontrado(s)</p>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Creado Por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documentos)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #a0aec0;">
                                        No se encontraron documentos
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($documentos as $doc): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar">
                                                    <?php 
                                                    $nombres = explode(' ', $doc['nombre_funcionario']);
                                                    echo strtoupper(substr($nombres[0], 0, 1) . (isset($nombres[1]) ? substr($nombres[1], 0, 1) : ''));
                                                    ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($doc['nombre_funcionario']); ?></div>
                                                    <div class="user-id"><?php echo htmlspecialchars($doc['cedula']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="doc-type-badge doc-type-<?php echo $doc['tipo_documento']; ?>">
                                                <?php echo ucfirst($doc['tipo_documento']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="max-width: 300px;">
                                                <div style="font-weight: 500;"><?php echo htmlspecialchars($doc['titulo']); ?></div>
                                                <?php if ($doc['descripcion']): ?>
                                                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                                        <?php echo htmlspecialchars(substr($doc['descripcion'], 0, 60)) . (strlen($doc['descripcion']) > 60 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo $doc['fecha_inicio'] ? date('d/m/Y', strtotime($doc['fecha_inicio'])) : '-'; ?></td>
                                        <td><?php echo $doc['fecha_fin'] ? date('d/m/Y', strtotime($doc['fecha_fin'])) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($doc['creado_por_nombre'] ?? 'Sistema'); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="../funcionarios/ver.php?id=<?php echo $doc['funcionario_id']; ?>" class="btn-icon" title="Ver expediente completo">
                                                    👁️
                                                </a>
                                                <?php if ($doc['ruta_archivo']): ?>
                                                    <a href="../../<?php echo htmlspecialchars($doc['ruta_archivo']); ?>" class="btn-icon" title="Descargar documento" target="_blank">
                                                        📥
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
    
    <script src="../../publico/js/app.js"></script>
</body>
</html>
