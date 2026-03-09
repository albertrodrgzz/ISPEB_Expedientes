<?php
/**
 * Vista: Expediente Digital - V6.4 (Corrección Barra Riesgo y Detalles)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

verificarSesion();

$nivel_acceso = $_SESSION['nivel_acceso'] ?? 3;
$funcionario_id_sesion = $_SESSION['funcionario_id'] ?? 0;

// Nivel 3: siempre ver su propio expediente (ignorar ?id ajeno)
if ($nivel_acceso >= 3) {
    if ($funcionario_id_sesion <= 0) {
        // Sin funcionario_id en sesión → logout forzado
        session_destroy();
        header('Location: ' . APP_URL . '/index.php?error=sesion_invalida');
        exit;
    }
    $id = $funcionario_id_sesion;
} else {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        header('Location: index.php');
        exit;
    }
}

$modeloFuncionario = new Funcionario();
$funcionario = $modeloFuncionario->obtenerPorId($id);

if (!$funcionario) {
    $_SESSION['error'] = 'Funcionario no encontrado';
    $redirect = ($nivel_acceso >= 3) ? APP_URL . '/vistas/dashboard/index.php' : 'index.php';
    header('Location: ' . $redirect);
    exit;
}

// Permisos: Nivel 3 solo puede ver su propio expediente (ya forzado arriba)
// Nivel 1 y 2 usan verificarDepartamento
if ($nivel_acceso < 3 && !verificarDepartamento($id)) {
    $_SESSION['error'] = 'Acceso denegado';
    header('Location: index.php');
    exit;
}
$puede_editar = ($nivel_acceso < 3) && verificarDepartamento($id);

// Datos adicionales (Usuario, Edad)
$db = getDB();
$stmt = $db->prepare("SELECT u.id, u.username, u.estado FROM usuarios u WHERE u.funcionario_id = ?");
$stmt->execute([$id]);
$usuario_existente = $stmt->fetch();
$tiene_usuario = (bool)$usuario_existente;

$edad = '';
if ($funcionario['fecha_nacimiento']) {
    $edad = (new DateTime())->diff(new DateTime($funcionario['fecha_nacimiento']))->y . ' años';
}
$antiguedad = '';
if ($funcionario['fecha_ingreso']) {
    $diff = (new DateTime())->diff(new DateTime($funcionario['fecha_ingreso']));
    $antiguedad = $diff->y . ' años, ' . $diff->m . ' meses';
}

// ============================================================
// CONSULTA PDO: Historial de Nombramientos del funcionario
// Columnas reales: id, fecha_evento, fecha_fin, detalles (JSON),
//                 ruta_archivo_pdf, nombre_archivo_original
// ============================================================
$stmtNombramientos = $db->prepare(
    "SELECT id, fecha_evento, fecha_fin, detalles,
            ruta_archivo_pdf, nombre_archivo_original
     FROM historial_administrativo
     WHERE funcionario_id = ? AND tipo_evento = 'NOMBRAMIENTO'
     ORDER BY fecha_evento DESC"
);
$stmtNombramientos->execute([$id]);
$nombramientos = $stmtNombramientos->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// CALCULADORA DE VACACIONES (LOTTT)
// Regla: 15 días el 1er año, +1 día por cada año adicional, tope 30
// ============================================================
$vac_anios_servicio  = 0;
$vac_dias_totales    = 0;
$vac_dias_disfrutados = 0;
$vac_dias_disponibles = 0;
$historial_vacaciones = [];

if ($funcionario['fecha_ingreso']) {
    // 1. Años de servicio
    $fechaIngreso        = new DateTime($funcionario['fecha_ingreso']);
    $hoy                 = new DateTime();
    $vac_anios_servicio  = (int)$hoy->diff($fechaIngreso)->y;

    // 2. Días totales LOTTT (mínimo 15, máximo 30)
    if ($vac_anios_servicio >= 1) {
        $vac_dias_totales = min(15 + ($vac_anios_servicio - 1), 30);
    } else {
        $vac_dias_totales = 0; // Sin derecho hasta cumplir 1 año
    }

    // 3. Días disfrutados en el año actual
    $anio_actual = (int)date('Y');
    $stmtVacUsadas = $db->prepare(
        "SELECT COALESCE(
             SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_habiles')) AS UNSIGNED)),
             0
         ) AS total_usado
         FROM historial_administrativo
         WHERE funcionario_id = ?
           AND tipo_evento    = 'VACACION'
           AND YEAR(fecha_evento) = ?"
    );
    $stmtVacUsadas->execute([$id, $anio_actual]);
    $vac_dias_disfrutados = (int)($stmtVacUsadas->fetchColumn() ?? 0);

    // 4. Días disponibles
    $vac_dias_disponibles = max(0, $vac_dias_totales - $vac_dias_disfrutados);
}

// 5. Historial COMPLETO de vacaciones disfrutadas
$stmtVacHistorial = $db->prepare(
    "SELECT id, fecha_evento, fecha_fin, detalles,
            ruta_archivo_pdf, nombre_archivo_original
     FROM historial_administrativo
     WHERE funcionario_id = ? AND tipo_evento = 'VACACION'
     ORDER BY fecha_evento DESC"
);
$stmtVacHistorial->execute([$id]);
$historial_vacaciones = $stmtVacHistorial->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    

    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/sidebar-fix.css">
    
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        /* === Layout Principal === */
        .expediente-layout { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start; }
        .tab-content { display: none; animation: fadeIn 0.35s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .section-header h2 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .info-card { background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .info-value { font-size: 14px; color: #0f172a; font-weight: 500; }

        /* === Badges === */
        .badge-leve    { background: #FEF9C3; color: #854D0E; border: 1px solid #FEF08A; }
        .badge-grave   { background: #FFEDD5; color: #9A3412; border: 1px solid #FED7AA; }
        .badge-muy_grave { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

        /* === Timeline Nombramientos (Liquid Glass) === */
        .timeline { position: relative; padding-left: 40px; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px; top: 0; bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #0F4C81 0%, #0284C7 60%, transparent 100%);
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 24px;
            animation: fadeIn 0.4s ease both;
        }
        .timeline-dot {
            position: absolute;
            left: -32px;
            top: 16px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #0F4C81;
            border: 3px solid #E0F2FE;
            box-shadow: 0 0 0 3px rgba(15,76,129,0.15);
            transition: transform 0.2s;
        }
        .timeline-item:hover .timeline-dot { transform: scale(1.3); }
        .timeline-card {
            background: rgba(255,255,255,0.72);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(226,232,240,0.9);
            border-radius: 16px;
            padding: 18px 22px;
            box-shadow: 0 2px 16px rgba(15,76,129,0.07), 0 1px 4px rgba(0,0,0,0.04);
            transition: box-shadow 0.25s, transform 0.25s;
        }
        .timeline-card:hover {
            box-shadow: 0 8px 32px rgba(15,76,129,0.14), 0 2px 8px rgba(0,0,0,0.06);
            transform: translateY(-2px);
        }
        .timeline-fecha {
            font-size: 11px;
            font-weight: 700;
            color: #0284C7;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }
        .timeline-cargo {
            font-size: 16px;
            font-weight: 700;
            color: #0F4C81;
            margin-bottom: 4px;
        }
        .timeline-desc {
            font-size: 13px;
            color: #64748B;
            line-height: 1.55;
        }
        .timeline-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 14px;
        }
        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn-pdf:hover { opacity: 0.88; transform: translateY(-1px); }

        /* === Empty State === */
        .empty-state-modern {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
        }
        .empty-state-modern .es-icon {
            width: 72px; height: 72px;
            opacity: 0.18;
            margin-bottom: 18px;
        }
        .empty-state-modern .es-title {
            font-size: 16px;
            font-weight: 600;
            color: #94a3b8;
        }

        @media (max-width: 900px) { .expediente-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 16px;">
                <?php if ($nivel_acceso < 3): ?>
                <a href="index.php" class="btn-secondary">
                    <?= Icon::get('arrow-left') ?> Volver
                </a>
                <?php else: ?>
                <a href="<?= APP_URL ?>/vistas/dashboard/index.php" class="btn-secondary">
                    <?= Icon::get('arrow-left') ?> Inicio
                </a>
                <?php endif; ?>
                <h1><?= $nivel_acceso >= 3 ? 'Mi Expediente' : 'Expediente Digital' ?></h1>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="../reportes/constancia_trabajo.php?id=<?= $id ?>" target="_blank" class="btn-primary" style="background: #10b981; border:none;">
                    <?= Icon::get('file-text') ?> Constancia
                </a>
                <?php if ($puede_editar): ?>
                    <a href="editar.php?id=<?= $id ?>" class="btn-primary">
                        <?= Icon::get('edit') ?> Editar
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="expediente-layout">
            <aside class="card-modern" style="padding: 0; overflow: hidden; position: sticky; top: 20px;">
                <div style="background: linear-gradient(135deg, #0F4C81, #0284C7); padding: 30px 20px; text-align: center; color: white;">
                    <div style="width: 90px; height: 90px; margin: 0 auto 15px; border-radius: 50%; background: white; padding: 3px;">
                        <?php if (!empty($funcionario['foto']) && $funcionario['foto'] !== 'default-avatar.png'): ?>
                            <img src="<?= APP_URL ?>/subidas/fotos/<?= $funcionario['foto'] ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="
                                width: 100%; 
                                height: 100%; 
                                background: #0F4C81; 
                                border-radius: 50%; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center; 
                                color: #ffffff; 
                                font-weight: 700; 
                                font-size: 36px;
                                letter-spacing: 1px;
                            ">
                                <?= strtoupper(mb_substr(trim($funcionario['nombres']), 0, 1, 'UTF-8') . mb_substr(trim($funcionario['apellidos']), 0, 1, 'UTF-8')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 16px; margin: 0; font-weight: 700;"><?= htmlspecialchars($funcionario['nombres']) ?></h3>
                    <p style="font-size: 13px; opacity: 0.9; margin: 5px 0;"><?= htmlspecialchars($funcionario['nombre_cargo']) ?></p>
                    <span style="background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                        <?= ucfirst($funcionario['estado']) ?>
                    </span>
                </div>
                
                <div style="padding: 15px;">
                    <?php
                    // Tabs activos: se eliminaron Cargas Familiares y Retiros/Despidos
                    $tabs = [
                        ['id' => 'info',           'icon' => 'user',           'label' => 'Información Personal'],
                        ['id' => 'nombramientos',  'icon' => 'file-text',      'label' => 'Nombramiento'],
                        ['id' => 'vacaciones',     'icon' => 'sun',            'label' => 'Historial Vacaciones'],
                        ['id' => 'amonestaciones', 'icon' => 'alert-triangle', 'label' => 'Amonestaciones y Riesgo'],
                    ];
                    foreach($tabs as $tab): ?>
                        <button onclick="showTab('<?= $tab['id'] ?>', this)"
                                class="profile-nav-link <?= $tab['id'] === 'info' ? 'active' : '' ?>"
                                style="width:100%;text-align:left;padding:12px;background:transparent;border:none;display:flex;gap:10px;color:#64748B;cursor:pointer;border-radius:8px;font-size:14px;font-weight:500;">
                            <?= Icon::get($tab['icon'], 'width:18px') ?> <?= $tab['label'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </aside>
            
            <main>
                <div id="tab-info" class="tab-content active">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('user', 'color:#0F4C81') ?> <h2>Datos Personales</h2>
                            </div>
                            <div class="info-grid">
                                <div class="info-card"><div class="info-label">Nombres</div><div class="info-value"><?= $funcionario['nombres'] ?></div></div>
                                <div class="info-card"><div class="info-label">Apellidos</div><div class="info-value"><?= $funcionario['apellidos'] ?></div></div>
                                <div class="info-card"><div class="info-label">Cédula</div><div class="info-value"><?= $funcionario['cedula'] ?></div></div>
                                <div class="info-card"><div class="info-label">Edad</div><div class="info-value"><?= $edad ?></div></div>
                                <div class="info-card"><div class="info-label">Teléfono</div><div class="info-value"><?= $funcionario['telefono'] ?: '-' ?></div></div>
                                <div class="info-card"><div class="info-label">Email</div><div class="info-value"><?= $funcionario['email'] ?: '-' ?></div></div>
                            </div>
                            
                            <div class="section-header" style="margin-top: 30px;">
                                <?= Icon::get('briefcase', 'color:#0F4C81') ?> <h2>Información Laboral</h2>
                            </div>
                            <div class="info-grid">
                                <div class="info-card"><div class="info-label">Cargo</div><div class="info-value"><?= $funcionario['nombre_cargo'] ?></div></div>
                                <div class="info-card"><div class="info-label">Departamento</div><div class="info-value"><?= $funcionario['departamento'] ?></div></div>
                                <div class="info-card"><div class="info-label">Antigüedad</div><div class="info-value"><?= $antiguedad ?></div></div>
                                <div class="info-card"><div class="info-label">Profesión</div><div class="info-value"><?= $funcionario['titulo_obtenido'] ?: '-' ?></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-amonestaciones" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('alert-triangle', 'color:#EF4444') ?> <h2>Amonestaciones y Riesgo Laboral</h2>
                            </div>

                            <div style="background: #FFF7ED; padding: 20px; border-radius: 12px; border: 1px solid #FFEDD5; margin-bottom: 30px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h4 style="margin: 0; font-size: 14px; color: #9A3412; font-weight: 700;">NIVEL DE RIESGO (Causa de Despido: 3 Amonestaciones)</h4>
                                    <span id="risk-counter" style="font-weight: 800; font-size: 16px; color: #9A3412;">Calculando...</span>
                                </div>
                                <div style="width: 100%; height: 24px; background: #E5E7EB; border-radius: 12px; overflow: hidden;">
                                    <div id="risk-fill" style="width: 0%; height: 100%; background: #10B981; transition: width 1s ease, background 0.5s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; text-shadow: 0 1px 2px rgba(0,0,0,0.2);"></div>
                                </div>
                                <div id="risk-mensaje" style="margin-top: 10px; font-size: 13px; font-weight: 500; color: #4B5563;">Calculando nivel de riesgo...</div>
                            </div>

                            <h3 style="font-size: 15px; color: #0F4C81; margin-bottom: 15px; border-bottom: 2px solid #F1F5F9; padding-bottom: 8px;">Historial de Faltas</h3>
                            <div id="amonestaciones-container">
                                <div class="empty-state">
                                    <div class="empty-state-icon" style="opacity:0.3"><?= Icon::get('check-circle') ?></div>
                                    <div class="empty-state-text">Cargando historial...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====================================================
                     TAB: NOMBRAMIENTOS  (Renderizado PHP + Liquid Glass)
                     ==================================================== -->
                <div id="tab-nombramientos" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('file-text', 'color:#0F4C81') ?>
                                <h2>Nombramiento</h2>
                            </div>

                            <?php if (empty($nombramientos)): ?>
                                <!-- EMPTY STATE -->
                                <div class="empty-state-modern">
                                    <svg class="es-icon" xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                    <p class="es-title">Aún no hay Nombramientos registrados</p>
                                </div>

                            <?php else: ?>
                                <!-- TIMELINE LIQUID GLASS -->
                                <div class="timeline">
                                    <?php foreach ($nombramientos as $nom):
                                        // -------------------------------
                                        // Decodificar el campo JSON 'detalles'
                                        // El cargo va en detalles['cargo']
                                        // El PDF va en la columna directa ruta_archivo_pdf
                                        // -------------------------------
                                        $det = [];
                                        if (!empty($nom['detalles'])) {
                                            $det = json_decode($nom['detalles'], true) ?? [];
                                        }

                                        // Cargo: leer del JSON detalles
                                        $cargo_asignado = $det['cargo']
                                            ?? $det['cargo_asignado']
                                            ?? $det['nombre_cargo']
                                            ?? $det['puesto']
                                            ?? 'Cargo no especificado';

                                        // Notas adicionales opcionales del JSON
                                        $notas = $det['notas'] ?? $det['descripcion'] ?? $det['observaciones'] ?? null;

                                        // PDF: leer desde la COLUMNA DIRECTA de la tabla (no del JSON)
                                        $ruta_pdf  = $nom['ruta_archivo_pdf'] ?? null;
                                        $tiene_pdf = !empty($ruta_pdf);

                                        // Formatear fecha
                                        $fecha_fmt = !empty($nom['fecha_evento'])
                                            ? (new DateTime($nom['fecha_evento']))->format('d/m/Y')
                                            : '—';

                                        // Fecha fin (si aplica)
                                        $fecha_fin_fmt = !empty($nom['fecha_fin'])
                                            ? (new DateTime($nom['fecha_fin']))->format('d/m/Y')
                                            : null;
                                    ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-card">
                                                <div class="timeline-fecha">
                                                    <?= htmlspecialchars($fecha_fmt) ?>
                                                    <?php if ($fecha_fin_fmt): ?>
                                                        &rarr; <?= htmlspecialchars($fecha_fin_fmt) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-cargo"><?= htmlspecialchars($cargo_asignado) ?></div>
                                                <?php if (!empty($notas)): ?>
                                                    <div class="timeline-desc"><?= htmlspecialchars($notas) ?></div>
                                                <?php endif; ?>
                                                <div class="timeline-footer">
                                                    <?php if ($tiene_pdf): ?>
                                                        <!-- Botón activo: hay PDF adjunto -->
                                                        <a href="<?= APP_URL ?>/<?= htmlspecialchars($ruta_pdf) ?>"
                                                           target="_blank"
                                                           rel="noopener noreferrer"
                                                           class="btn-pdf"
                                                           title="Abrir Nombramiento en nueva pestaña">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                                <polyline points="14 2 14 8 20 8"/>
                                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                                            </svg>
                                                            Ver Nombramiento
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.8">
                                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                                <polyline points="15 3 21 3 21 9"/>
                                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                                            </svg>
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Botón deshabilitado: sin PDF adjunto -->
                                                        <span class="btn-pdf"
                                                              style="background:linear-gradient(135deg,#94a3b8,#64748b);cursor:not-allowed;opacity:0.6;"
                                                              title="No hay documento adjunto a este nombramiento">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                                <polyline points="14 2 14 8 20 8"/>
                                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                                            </svg>
                                                            Sin documento adjunto
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <!-- TAB: VACACIONES — Renderizado PHP + Liquid Glass -->
                <div id="tab-vacaciones" class="tab-content">

                    <!-- =========================================
                         BLOQUE 1: CARDS DE RESUMEN LOTTT
                         ========================================= -->
                    <div class="card-modern" style="margin-bottom:20px;">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('sun', 'color:#F59E0B') ?>
                                <h2>Mi Balance Vacacional <?= date('Y') ?></h2>
                            </div>

                            <?php if ($vac_anios_servicio < 1): ?>
                                <!-- Sin derecho todavía -->
                                <div class="empty-state-modern" style="padding:30px 20px;">
                                    <svg class="es-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    <p class="es-title">Aún no cumple 1 año de servicio</p>
                                    <p style="font-size:13px;color:#94a3b8;margin-top:6px;">El derecho a vacaciones se activa al completar el primer año según la LOTTT.</p>
                                </div>
                            <?php else: ?>
                                <?php
                                    // Color semafórico para días disponibles
                                    if ($vac_dias_disponibles <= 0) {
                                        $color_disp = '#EF4444'; $bg_disp = '#FEF2F2'; $border_disp = '#FECACA';
                                        $label_disp = 'Sin días disponibles';
                                    } elseif ($vac_dias_disponibles <= 5) {
                                        $color_disp = '#F59E0B'; $bg_disp = '#FFFBEB'; $border_disp = '#FDE68A';
                                        $label_disp = 'Pocos días';
                                    } else {
                                        $color_disp = '#10B981'; $bg_disp = '#ECFDF5'; $border_disp = '#A7F3D0';
                                        $label_disp = 'Disponibles';
                                    }
                                ?>
                                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;">

                                    <!-- Card: Años de Servicio -->
                                    <div style="background:linear-gradient(135deg,#EFF6FF,#DBEAFE);border:1px solid #BFDBFE;border-radius:16px;padding:20px;text-align:center;">
                                        <div style="font-size:11px;font-weight:700;color:#1D4ED8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Años de Servicio</div>
                                        <div style="font-size:36px;font-weight:800;color:#1D4ED8;line-height:1;"><?= $vac_anios_servicio ?></div>
                                        <div style="font-size:12px;color:#3B82F6;margin-top:4px;">año<?= $vac_anios_servicio !== 1 ? 's' : '' ?></div>
                                    </div>

                                    <!-- Card: Días Correspondientes -->
                                    <div style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE);border:1px solid #DDD6FE;border-radius:16px;padding:20px;text-align:center;">
                                        <div style="font-size:11px;font-weight:700;color:#6D28D9;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Días Correspondientes</div>
                                        <div style="font-size:36px;font-weight:800;color:#6D28D9;line-height:1;"><?= $vac_dias_totales ?></div>
                                        <div style="font-size:12px;color:#7C3AED;margin-top:4px;">días hábiles</div>
                                    </div>

                                    <!-- Card: Días Utilizados -->
                                    <div style="background:linear-gradient(135deg,#FFF7ED,#FFEDD5);border:1px solid #FED7AA;border-radius:16px;padding:20px;text-align:center;">
                                        <div style="font-size:11px;font-weight:700;color:#C2410C;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Días Utilizados <?= date('Y') ?></div>
                                        <div style="font-size:36px;font-weight:800;color:#C2410C;line-height:1;"><?= $vac_dias_disfrutados ?></div>
                                        <div style="font-size:12px;color:#EA580C;margin-top:4px;">días consumidos</div>
                                    </div>

                                    <!-- Card: Días Disponibles (color dinámico) -->
                                    <div style="background:linear-gradient(135deg,<?= $bg_disp ?>,<?= $bg_disp ?>);border:1px solid <?= $border_disp ?>;border-radius:16px;padding:20px;text-align:center;position:relative;overflow:hidden;">
                                        <div style="font-size:11px;font-weight:700;color:<?= $color_disp ?>;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Días Disponibles</div>
                                        <div style="font-size:36px;font-weight:800;color:<?= $color_disp ?>;line-height:1;"><?= $vac_dias_disponibles ?></div>
                                        <div style="font-size:12px;color:<?= $color_disp ?>;margin-top:4px;opacity:.8;"><?= $label_disp ?></div>
                                    </div>

                                </div>

                                <!-- Barra de progreso -->
                                <?php if ($vac_dias_totales > 0): ?>
                                    <?php $pct = min(100, round(($vac_dias_disfrutados / $vac_dias_totales) * 100)); ?>
                                    <div style="margin-top:20px;">
                                        <div style="display:flex;justify-content:space-between;font-size:12px;color:#64748B;margin-bottom:6px;">
                                            <span>Días usados: <strong style="color:#0F4C81;"><?= $vac_dias_disfrutados ?> / <?= $vac_dias_totales ?></strong></span>
                                            <span><?= $pct ?>% utilizado</span>
                                        </div>
                                        <div style="height:10px;background:#E2E8F0;border-radius:10px;overflow:hidden;">
                                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#EF4444' : ($pct >= 70 ? '#F59E0B' : '#10B981') ?>;border-radius:10px;transition:width 1s ease;"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- =========================================
                         BLOQUE 2: HISTORIAL DE VACACIONES
                         ========================================= -->
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('calendar', 'color:#0F4C81') ?>
                                <h2>Historial de Vacaciones Disfrutadas</h2>
                            </div>

                            <?php if (empty($historial_vacaciones)): ?>
                                <!-- EMPTY STATE -->
                                <div class="empty-state-modern">
                                    <svg class="es-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <p class="es-title">Aún no hay registros históricos de vacaciones disfrutadas</p>
                                </div>

                            <?php else: ?>
                                <!-- TIMELINE LIQUID GLASS -->
                                <div class="timeline">
                                    <?php foreach ($historial_vacaciones as $vac):
                                        $vd = [];
                                        if (!empty($vac['detalles'])) {
                                            $vd = json_decode($vac['detalles'], true) ?? [];
                                        }
                                        $dias_hab   = $vd['dias_habiles']   ?? '—';
                                        $f_retorno  = $vd['fecha_retorno']  ?? null;
                                        $observ     = $vd['observaciones']  ?? null;

                                        $f_inicio_fmt  = !empty($vac['fecha_evento'])
                                            ? (new DateTime($vac['fecha_evento']))->format('d/m/Y') : '—';
                                        $f_fin_fmt     = !empty($vac['fecha_fin'])
                                            ? (new DateTime($vac['fecha_fin']))->format('d/m/Y')
                                            : ($f_retorno ? (new DateTime($f_retorno))->format('d/m/Y') : '—');

                                        $anio_vac   = !empty($vac['fecha_evento'])
                                            ? (new DateTime($vac['fecha_evento']))->format('Y') : '—';
                                        $ruta_pdf   = $vac['ruta_archivo_pdf'] ?? null;
                                    ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot" style="background:#F59E0B;border-color:#FEF3C7;box-shadow:0 0 0 3px rgba(245,158,11,.15);"></div>
                                            <div class="timeline-card">
                                                <div class="timeline-fecha" style="color:#D97706;">
                                                    Período <?= $anio_vac ?>
                                                </div>
                                                <div class="timeline-cargo" style="color:#92400E;font-size:15px;">
                                                    <?= htmlspecialchars($f_inicio_fmt) ?> &rarr; <?= htmlspecialchars($f_fin_fmt) ?>
                                                </div>
                                                <?php if ($observ): ?>
                                                    <div class="timeline-desc"><?= htmlspecialchars($observ) ?></div>
                                                <?php endif; ?>

                                                <div class="timeline-footer" style="justify-content:space-between;align-items:center;margin-top:12px;">
                                                    <!-- Días usados badge -->
                                                    <span style="display:inline-flex;align-items:center;gap:5px;background:#FEF3C7;color:#92400E;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;border:1px solid #FDE68A;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                        <?= $dias_hab ?> día<?= $dias_hab != 1 ? 's' : '' ?> hábile<?= $dias_hab != 1 ? 's' : '' ?>
                                                    </span>

                                                    <!-- Botón PDF -->
                                                    <?php if (!empty($ruta_pdf)): ?>
                                                        <a href="<?= APP_URL ?>/<?= htmlspecialchars($ruta_pdf) ?>"
                                                           target="_blank" rel="noopener noreferrer"
                                                           class="btn-pdf"
                                                           style="background:linear-gradient(135deg,#F59E0B,#D97706);"
                                                           title="Ver documento de vacaciones">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                            Ver Aval
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="font-size:12px;color:#CBD5E1;font-style:italic;">Sin documento</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /tab-vacaciones -->
            </main>
        </div>
    </div>



    <script>
        const funcionarioId = <?= $id ?>;

        function showTab(id, el) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-'+id).classList.add('active');
            
            document.querySelectorAll('.profile-nav-link').forEach(b => {
                b.style.background = 'transparent'; b.style.color = '#64748B';
            });
            el.style.background = '#E0F2FE'; el.style.color = '#0F4C81';

            if (id === 'amonestaciones') {
                cargarBarraRiesgo();
                cargarAmonestaciones();
            }
            if (id === 'vacaciones') cargarVacaciones();
        }

        // --- LÓGICA DE BARRA DE RIESGO (3 STRIKES) ---
        function cargarBarraRiesgo() {
            fetch(`ajax/contar_amonestaciones.php?funcionario_id=${funcionarioId}`)
            .then(res => {
                if(!res.ok) throw new Error("Error en servidor");
                return res.json();
            })
            .then(data => {
                if(data.success) {
                    const count = parseInt(data.data.conteo.total);
                    const bar = document.getElementById('risk-fill');
                    const msg = document.getElementById('risk-mensaje');
                    const counter = document.getElementById('risk-counter');
                    
                    counter.innerText = count + " / 3";
                    
                    if(count === 0) {
                        bar.style.width = '2%'; bar.style.background = '#10B981';
                        msg.innerHTML = "✅ <strong>Historial Limpio:</strong> Sin riesgo actual.";
                        msg.style.color = "#059669";
                    } else if (count === 1) {
                        bar.style.width = '33%'; bar.style.background = '#F59E0B'; // Amarillo
                        bar.innerText = "PRECAUCIÓN";
                        msg.innerHTML = "⚠️ <strong>Precaución:</strong> Primera falta registrada.";
                        msg.style.color = "#D97706";
                    } else if (count === 2) {
                        bar.style.width = '66%'; bar.style.background = '#F97316'; // Naranja
                        bar.innerText = "RIESGO ALTO";
                        msg.innerHTML = "🚨 <strong>Riesgo Alto:</strong> A una falta del despido justificado.";
                        msg.style.color = "#EA580C";
                    } else {
                        bar.style.width = '100%'; bar.style.background = '#EF4444'; // Rojo
                        bar.innerText = "CAUSA DE DESPIDO";
                        msg.innerHTML = "⛔ <strong>CRÍTICO:</strong> Se ha alcanzado el límite de faltas para despido.";
                        msg.style.color = "#DC2626";
                    }
                }
            })
            .catch(err => {
                console.error("Error cargando riesgo:", err);
                document.getElementById('risk-mensaje').innerHTML = "<span style='color:red'>Error al conectar con servidor</span>";
            });
        }

        // --- LÓGICA DE TABLA DE AMONESTACIONES (DETALLADA) ---
        function cargarAmonestaciones() {
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=AMONESTACION`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('amonestaciones-container');
                if (data.success && data.total > 0) {
                    let html = `
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Gravedad</th>
                                    <th>Motivo / Sanción</th>
                                    <th style="text-align:right">PDF</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    
                    data.data.forEach(item => {
                        const d = item.detalles || {};
                        const tipo = d.tipo_falta || 'leve';
                        
                        // Badge con color correcto
                        let badgeClass = 'badge-leve';
                        let label = 'Leve';
                        if(tipo === 'grave') { badgeClass = 'badge-grave'; label = 'Grave'; }
                        if(tipo === 'muy_grave') { badgeClass = 'badge-muy_grave'; label = 'Muy Grave'; }

                        html += `
                            <tr>
                                <td style="white-space:nowrap; font-size:13px;">${item.fecha_evento_formateada}</td>
                                <td><span class="badge ${badgeClass}" style="padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">${label.toUpperCase()}</span></td>
                                <td>
                                    <div style="font-weight:600; font-size:13px; color:#334155;">${d.motivo || 'Sin motivo especificado'}</div>
                                    <div style="font-size:12px; color:#64748B; margin-top:2px;">Sanción: ${d.sancion || '-'}</div>
                                </td>
                                <td style="text-align:right">
                                    ${item.tiene_archivo ? 
                                      `<a href="../../${item.ruta_archivo_pdf}" target="_blank" class="btn-icon" title="Ver Acta" style="color:#EF4444;">
                                         <?= Icon::get('file-text') ?>
                                       </a>` : '<span style="color:#cbd5e1">-</span>'}
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `</tbody></table>`;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `<div class="empty-state"><div class="empty-state-text">No hay amonestaciones registradas</div></div>`;
                }
            });
        }

        // Vacaciones: carga vía AJAX (mantiene lógica existente)
        function cargarVacaciones() {
            const c = document.getElementById('vacaciones-historial-container');
            if (!c || c.dataset.loaded) return;
            c.innerHTML = '<div style="padding:20px;color:#94a3b8;font-size:14px;">Cargando...</div>';
            fetch(`ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=VACACION`)
                .then(r => r.json())
                .then(data => {
                    c.dataset.loaded = '1';
                    if (data.success && data.total > 0) {
                        let html = '<table class="table-modern"><thead><tr><th>Período</th><th>Días</th><th>PDF</th></tr></thead><tbody>';
                        data.data.forEach(v => {
                            const d = v.detalles || {};
                            html += `<tr>
                                <td>${v.fecha_evento_formateada}</td>
                                <td>${d.dias_disfrutados ?? '-'} días</td>
                                <td>${v.tiene_archivo ? `<a href="../../${v.ruta_archivo_pdf}" target="_blank" class="btn-icon" style="color:#0284C7;">📄</a>` : '-'}</td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                        c.innerHTML = html;
                    } else {
                        c.innerHTML = '<div class="empty-state-modern"><p class="es-title">Aún no hay Vacaciones registradas</p></div>';
                    }
                })
                .catch(() => { c.innerHTML = '<div class="empty-state-modern"><p class="es-title" style="color:#EF4444;">Error al cargar datos</p></div>'; });
        }
    </script>
</body>
</html>