<?php
/**
 * Vista: Expediente Digital - V6.4 (Corrección Barra Riesgo y Detalles)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../config/icons.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

verificarSesion();

$id = $_GET['id'] ?? 0;
if (!$id) { header('Location: index.php'); exit; }

$modeloFuncionario = new Funcionario();
$funcionario = $modeloFuncionario->obtenerPorId($id);

if (!$funcionario) {
    $_SESSION['error'] = 'Funcionario no encontrado';
    header('Location: index.php');
    exit;
}

// Permisos
if (!verificarDepartamento($id) && $_SESSION['nivel_acceso'] > 2) {
    $_SESSION['error'] = 'Acceso denegado';
    header('Location: index.php');
    exit;
}
$puede_editar = verificarDepartamento($id);

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente - <?= APP_NAME ?></title>
    
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/estilos.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/modern-components.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/swal-modern.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/sidebar-fix.css">
    
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        /* Estilos Expediente */
        .expediente-layout { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start; }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .section-header h2 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .info-card { background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .info-value { font-size: 14px; color: #0f172a; font-weight: 500; }

        /* Badges Específicos */
        .badge-leve { background: #FEF9C3; color: #854D0E; border: 1px solid #FEF08A; }
        .badge-grave { background: #FFEDD5; color: #9A3412; border: 1px solid #FED7AA; }
        .badge-muy_grave { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px; }
        
        @media (max-width: 900px) { .expediente-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../layout/header.php'; ?>
        
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="index.php" class="btn-secondary">
                    <?= Icon::get('arrow-left') ?> Volver
                </a>
                <h1>Expediente Digital</h1>
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
                        <?php if ($funcionario['foto']): ?>
                            <img src="<?= APP_URL ?>/subidas/fotos/<?= $funcionario['foto'] ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: #E2E8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748B; font-weight: bold; font-size: 32px;">
                                <?= substr($funcionario['nombres'], 0, 1) ?>
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
                    $tabs = [
                        ['id' => 'info', 'icon' => 'user', 'label' => 'Información Personal'],
                        ['id' => 'cargas', 'icon' => 'users', 'label' => 'Cargas Familiares'],
                        ['id' => 'nombramientos', 'icon' => 'file-text', 'label' => 'Historial Nombramientos'],
                        ['id' => 'vacaciones', 'icon' => 'sun', 'label' => 'Historial Vacaciones'],
                        ['id' => 'amonestaciones', 'icon' => 'alert-triangle', 'label' => 'Amonestaciones y Riesgo'],
                        ['id' => 'salidas', 'icon' => 'log-out', 'label' => 'Retiros/Despidos']
                    ];
                    foreach($tabs as $tab): ?>
                        <button onclick="showTab('<?= $tab['id'] ?>', this)" class="profile-nav-link <?= $tab['id']=='info'?'active':'' ?>" style="width: 100%; text-align: left; padding: 12px; background: transparent; border: none; display: flex; gap: 10px; color: #64748B; cursor: pointer; border-radius: 8px; font-size: 14px; font-weight: 500;">
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

                <div id="tab-cargas" class="tab-content">
                    <div class="card-modern">
                        <div class="card-body">
                            <div class="section-header">
                                <?= Icon::get('users') ?> <h2>Cargas Familiares</h2>
                                <?php if ($puede_editar): ?><button class="btn-sm btn-primary" onclick="abrirModalCarga()">+ Agregar</button><?php endif; ?>
                            </div>
                            <div id="cargas-container">Loading...</div>
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

                <div id="tab-nombramientos" class="tab-content"><div class="card-modern"><div class="card-body"><div id="nombramientos-container"></div></div></div></div>
                <div id="tab-vacaciones" class="tab-content"><div class="card-modern"><div class="card-body"><div id="vacaciones-historial-container"></div></div></div></div>
                <div id="tab-salidas" class="tab-content"><div class="card-modern"><div class="card-body"><div id="salidas-container"></div></div></div></div>
            </main>
        </div>
    </div>

    <div id="modalCarga" class="modal">
        <div class="modal-content">
            <h3>Agregar Carga</h3>
            <button onclick="document.getElementById('modalCarga').classList.remove('active')">Cerrar</button>
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

            if(id === 'amonestaciones') {
                cargarBarraRiesgo();
                cargarAmonestaciones();
            }
            if(id === 'nombramientos') cargarNombramientos();
            if(id === 'vacaciones') cargarVacaciones();
            if(id === 'cargas') cargarCargasFamiliares();
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

        // Loaders de otras tabs (simplificados para el ejemplo)
        function cargarNombramientos() { /* ... código existente ... */ }
        function cargarVacaciones() { /* ... código existente ... */ }
        function cargarCargasFamiliares() { /* ... código existente ... */ }
    </script>
</body>
</html>