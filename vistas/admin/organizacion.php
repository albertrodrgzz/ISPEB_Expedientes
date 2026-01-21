<?php
/**
 * Vista: Panel de Administración - Gestión de Departamentos
 * Solo accesible para nivel 1 (Administradores)
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos
verificarSesion();

if (!verificarNivel(1)) {
    $_SESSION['error'] = 'Solo los administradores pueden acceder a esta sección';
    header('Location: ../dashboard/index.php');
    exit;
}

$db = getDB();

// Obtener departamentos
$stmt = $db->query("
    SELECT 
        d.*,
        COUNT(f.id) as total_funcionarios
    FROM departamentos d
    LEFT JOIN funcionarios f ON d.id = f.departamento_id AND f.estado = 'activo'
    GROUP BY d.id
    ORDER BY d.nombre
");
$departamentos = $stmt->fetchAll();

// Obtener cargos
$stmt = $db->query("
    SELECT 
        c.*,
        COUNT(f.id) as total_funcionarios
    FROM cargos c
    LEFT JOIN funcionarios f ON c.id = f.cargo_id AND f.estado = 'activo'
    GROUP BY c.id
    ORDER BY c.nivel_acceso, c.nombre_cargo
");
$cargos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Organizacional - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .org-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--color-border);
        }
        
        .org-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--color-text-light);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }
        
        .org-tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .org-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .org-card {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .org-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .org-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }
        
        .org-card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .org-card-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .org-card-desc {
            color: var(--color-text-light);
            font-size: 14px;
            margin-bottom: 16px;
            min-height: 40px;
        }
        
        .org-card-stats {
            display: flex;
            gap: 16px;
            padding: 12px 0;
            border-top: 1px solid var(--color-border-light);
            margin-bottom: 16px;
        }
        
        .org-stat {
            flex: 1;
        }
        
        .org-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .org-stat-label {
            font-size: 12px;
            color: var(--color-text-light);
        }
        
        .org-card-actions {
            display: flex;
            gap: 8px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 32px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 24px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
        
        .nivel-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .nivel-1 {
            background: #fef3c7;
            color: #92400e;
        }
        
        .nivel-2 {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .nivel-3 {
            background: #e0e7ff;
            color: #4338ca;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Gestión Organizacional</h1>
                <p style="color: var(--color-text-light); margin-top: 4px;">Administrar departamentos y cargos del sistema</p>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                    ← Volver al Panel
                </a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Tabs -->
            <div class="org-tabs">
                <button class="org-tab active" onclick="switchTab('departamentos')">🏢 Departamentos</button>
                <button class="org-tab" onclick="switchTab('cargos')">👔 Cargos</button>
            </div>
            
            <!-- Tab: Departamentos -->
            <div id="tab-departamentos" class="tab-content active">
                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <p style="color: var(--color-text-light);">
                        Total de departamentos: <strong><?php echo count($departamentos); ?></strong>
                    </p>
                    <button class="btn btn-primary" onclick="openCreateDepartamentoModal()">
                        ➕ Nuevo Departamento
                    </button>
                </div>
                
                <div class="org-grid">
                    <?php foreach ($departamentos as $dept): ?>
                        <div class="org-card">
                            <div class="org-card-header">
                                <h3 class="org-card-title"><?php echo htmlspecialchars($dept['nombre']); ?></h3>
                                <span class="org-card-badge <?php echo $dept['estado'] == 'activo' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo ucfirst($dept['estado']); ?>
                                </span>
                            </div>
                            <p class="org-card-desc">
                                <?php echo htmlspecialchars($dept['descripcion'] ?? 'Sin descripción'); ?>
                            </p>
                            <div class="org-card-stats">
                                <div class="org-stat">
                                    <div class="org-stat-value"><?php echo $dept['total_funcionarios']; ?></div>
                                    <div class="org-stat-label">Funcionarios</div>
                                </div>
                            </div>
                            <div class="org-card-actions">
                                <button class="btn" style="flex: 1; font-size: 13px;" onclick="editDepartamento(<?php echo $dept['id']; ?>)">
                                    ✏️ Editar
                                </button>
                                <button class="btn" style="flex: 1; font-size: 13px; background: #fef3c7; color: #92400e;" 
                                        onclick="toggleDepartamento(<?php echo $dept['id']; ?>, '<?php echo $dept['estado']; ?>')">
                                    🔄 <?php echo $dept['estado'] == 'activo' ? 'Desactivar' : 'Activar'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab: Cargos -->
            <div id="tab-cargos" class="tab-content">
                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <p style="color: var(--color-text-light);">
                        Total de cargos: <strong><?php echo count($cargos); ?></strong>
                    </p>
                    <button class="btn btn-primary" onclick="openCreateCargoModal()">
                        ➕ Nuevo Cargo
                    </button>
                </div>
                
                <div class="org-grid">
                    <?php foreach ($cargos as $cargo): ?>
                        <div class="org-card">
                            <div class="org-card-header">
                                <h3 class="org-card-title"><?php echo htmlspecialchars($cargo['nombre_cargo']); ?></h3>
                                <span class="nivel-badge nivel-<?php echo $cargo['nivel_acceso']; ?>">
                                    Nivel <?php echo $cargo['nivel_acceso']; ?>
                                </span>
                            </div>
                            <p class="org-card-desc">
                                <?php echo htmlspecialchars($cargo['descripcion'] ?? 'Sin descripción'); ?>
                            </p>
                            <div class="org-card-stats">
                                <div class="org-stat">
                                    <div class="org-stat-value"><?php echo $cargo['total_funcionarios']; ?></div>
                                    <div class="org-stat-label">Funcionarios</div>
                                </div>
                            </div>
                            <div class="org-card-actions">
                                <button class="btn" style="flex: 1; font-size: 13px;" onclick="editCargo(<?php echo $cargo['id']; ?>)">
                                    ✏️ Editar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Crear Departamento -->
    <div id="createDepartamentoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Crear Nuevo Departamento</h2>
            </div>
            <form id="createDepartamentoForm" onsubmit="return handleCreateDepartamento(event)">
                <div class="form-group">
                    <label class="form-label">Nombre del Departamento *</label>
                    <input type="text" name="nombre" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-textarea"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal('createDepartamentoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Departamento</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Crear Cargo -->
    <div id="createCargoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Crear Nuevo Cargo</h2>
            </div>
            <form id="createCargoForm" onsubmit="return handleCreateCargo(event)">
                <div class="form-group">
                    <label class="form-label">Nombre del Cargo *</label>
                    <input type="text" name="nombre_cargo" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nivel de Acceso *</label>
                    <select name="nivel_acceso" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="1">Nivel 1 - Administrador Total</option>
                        <option value="2">Nivel 2 - Operativo</option>
                        <option value="3">Nivel 3 - Solo Lectura</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-textarea"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal('createCargoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cargo</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.org-tab').forEach(btn => btn.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function openCreateDepartamentoModal() {
            document.getElementById('createDepartamentoModal').classList.add('active');
        }
        
        function openCreateCargoModal() {
            document.getElementById('createCargoModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function handleCreateDepartamento(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('ajax/crear_departamento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Departamento creado exitosamente');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error al crear departamento');
                console.error(error);
            });
            
            return false;
        }
        
        function handleCreateCargo(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('ajax/crear_cargo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Cargo creado exitosamente');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error al crear cargo');
                console.error(error);
            });
            
            return false;
        }
        
        function toggleDepartamento(id, estadoActual) {
            const nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';
            const confirmMsg = `¿Está seguro de ${nuevoEstado === 'activo' ? 'activar' : 'desactivar'} este departamento?`;
            
            if (!confirm(confirmMsg)) return;
            
            fetch('ajax/toggle_departamento.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ departamento_id: id, nuevo_estado: nuevoEstado })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Estado actualizado');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }
        
        function editDepartamento(id) {
            alert('Funcionalidad de edición en desarrollo. ID: ' + id);
        }
        
        function editCargo(id) {
            alert('Funcionalidad de edición en desarrollo. ID: ' + id);
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
