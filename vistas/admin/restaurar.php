<?php
/**
 * Vista: Restaurar Base de Datos
 * Solo accesible para nivel 1
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos
verificarSesion();

if (!verificarNivel(1)) {
    $_SESSION['error'] = 'Solo los administradores pueden restaurar la base de datos';
    header('Location: index.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar formulario de restauración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_sql'])) {
    // Aumentar tiempo de ejecución y memoria
    set_time_limit(300); // 5 minutos
    ini_set('memory_limit', '256M');
    
    try {
        // Validar archivo
        if (!isset($_FILES['archivo_sql']) || $_FILES['archivo_sql']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
            ];
            $error_code = $_FILES['archivo_sql']['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new Exception($upload_errors[$error_code] ?? 'Error desconocido al subir el archivo');
        }
        
        $archivo = $_FILES['archivo_sql'];
        
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            throw new Exception('Solo se permiten archivos .sql');
        }
        
        // Validar tamaño (máximo 50MB)
        if ($archivo['size'] > 50 * 1024 * 1024) {
            throw new Exception('El archivo no debe superar los 50MB');
        }
        
        // Leer contenido del archivo
        $sql_content = file_get_contents($archivo['tmp_name']);
        
        if (empty($sql_content)) {
            throw new Exception('El archivo está vacío');
        }
        
        // Conectar a la base de datos
        $db = getDB();
        
        // Registrar en auditoría ANTES de la restauración
        try {
            registrarAuditoria('RESTAURAR_BD_INICIO', null, null, null, [
                'archivo' => $archivo['name'],
                'tamano' => $archivo['size']
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar auditoría: " . $e->getMessage());
        }
        
        // Deshabilitar foreign key checks y autocommit
        $db->exec('SET FOREIGN_KEY_CHECKS=0');
        $db->exec('SET AUTOCOMMIT=0');
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Dividir el contenido en statements individuales
        // Limpiar comentarios y líneas vacías
        $sql_content = preg_replace('/^--.*$/m', '', $sql_content); // Comentarios de línea
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content); // Comentarios de bloque
        
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && strlen($stmt) > 5; // Ignorar statements muy cortos
            }
        );
        
        // Ejecutar cada statement
        $ejecutados = 0;
        $errores = 0;
        $errores_detalle = [];
        
        foreach ($statements as $index => $statement) {
            try {
                $db->exec($statement);
                $ejecutados++;
            } catch (PDOException $e) {
                $errores++;
                $error_msg = "Statement #" . ($index + 1) . ": " . $e->getMessage();
                error_log($error_msg);
                $errores_detalle[] = $error_msg;
                
                // Si hay demasiados errores, abortar
                if ($errores > 50) {
                    throw new Exception("Demasiados errores durante la restauración. Proceso abortado.");
                }
            }
        }
        
        // Commit de la transacción
        $db->commit();
        
        // Rehabilitar foreign key checks y autocommit
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
        $db->exec('SET AUTOCOMMIT=1');
        
        // Registrar resultado en auditoría
        try {
            registrarAuditoria('RESTAURAR_BD_COMPLETADO', null, null, null, [
                'ejecutados' => $ejecutados,
                'errores' => $errores
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar auditoría final: " . $e->getMessage());
        }
        
        if ($errores > 0) {
            $mensaje = "Restauración completada con advertencias. Ejecutados: $ejecutados statements. Errores: $errores.";
            if ($errores <= 5) {
                $mensaje .= " Detalles: " . implode("; ", array_slice($errores_detalle, 0, 5));
            }
        } else {
            $mensaje = "✅ Base de datos restaurada exitosamente. Se ejecutaron $ejecutados statements sin errores.";
        }
        
    } catch (Exception $e) {
        // Rollback si hay transacción activa
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        // Rehabilitar configuraciones
        if (isset($db)) {
            try {
                $db->exec('SET FOREIGN_KEY_CHECKS=1');
                $db->exec('SET AUTOCOMMIT=1');
            } catch (Exception $ex) {
                // Ignorar errores al rehabilitar
            }
        }
        
        $error = 'Error al restaurar la base de datos: ' . $e->getMessage();
        error_log("Error crítico en restauración: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

// Obtener lista de respaldos disponibles
$directorio_respaldos = __DIR__ . '/../../database/respaldos/';
$respaldos_disponibles = [];

if (file_exists($directorio_respaldos)) {
    $archivos = scandir($directorio_respaldos);
    foreach ($archivos as $archivo) {
        if (pathinfo($archivo, PATHINFO_EXTENSION) === 'sql') {
            $ruta_completa = $directorio_respaldos . $archivo;
            $respaldos_disponibles[] = [
                'nombre' => $archivo,
                'tamano' => filesize($ruta_completa),
                'fecha' => filemtime($ruta_completa)
            ];
        }
    }
    // Ordenar por fecha descendente
    usort($respaldos_disponibles, function($a, $b) {
        return $b['fecha'] - $a['fecha'];
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Base de Datos - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../publico/css/estilos.css">
    <style>
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 12px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warning-box ul {
            color: #856404;
            margin-left: 20px;
        }
        
        .warning-box li {
            margin-bottom: 8px;
        }
        
        .upload-area {
            background: var(--color-white);
            border: 2px dashed var(--color-border);
            border-radius: var(--radius-lg);
            padding: 40px;
            text-align: center;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: var(--color-primary);
            background: var(--color-bg);
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-top: 16px;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: var(--color-primary);
            color: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background: var(--color-primary-dark);
            transform: translateY(-2px);
        }
        
        .selected-file {
            margin-top: 16px;
            padding: 12px;
            background: var(--color-bg);
            border-radius: var(--radius-md);
            font-size: 14px;
        }
        
        .backups-list {
            background: var(--color-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .backup-item {
            padding: 16px;
            border-bottom: 1px solid var(--color-border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-name {
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
        }
        
        .backup-meta {
            font-size: 13px;
            color: var(--color-text-light);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Restaurar Base de Datos</h1>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn" style="background: #e2e8f0; color: #2d3748; text-decoration: none;">
                    ← Volver a Administración
                </a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Advertencia -->
            <div class="warning-box">
                <h3>⚠️ ADVERTENCIA IMPORTANTE</h3>
                <ul>
                    <li><strong>Esta acción sobrescribirá TODOS los datos actuales de la base de datos</strong></li>
                    <li>Se recomienda hacer un respaldo antes de restaurar</li>
                    <li>Asegúrese de que el archivo SQL sea compatible con la estructura actual</li>
                    <li>El proceso puede tardar varios minutos dependiendo del tamaño del archivo</li>
                    <li>No cierre esta ventana durante el proceso de restauración</li>
                </ul>
            </div>
            
            <!-- Mensajes -->
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 24px;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success" style="margin-bottom: 24px;">
                    ✓ <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulario de Restauración -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Subir Archivo SQL</h2>
                </div>
                <div style="padding: 24px;">
                    <form method="POST" enctype="multipart/form-data" id="form-restaurar">
                        <div class="upload-area">
                            <div class="upload-icon">📤</div>
                            <h3 style="margin-bottom: 8px;">Seleccione un archivo SQL para restaurar</h3>
                            <p style="color: var(--color-text-light); margin-bottom: 16px;">
                                Tamaño máximo: 50MB
                            </p>
                            
                            <div class="file-input-wrapper">
                                <input 
                                    type="file" 
                                    name="archivo_sql" 
                                    id="archivo_sql" 
                                    accept=".sql"
                                    required
                                    onchange="mostrarArchivoSeleccionado(this)"
                                >
                                <label for="archivo_sql" class="file-input-label">
                                    📁 Seleccionar Archivo
                                </label>
                            </div>
                            
                            <div id="selected-file" class="selected-file" style="display: none;"></div>
                        </div>
                        
                        <div style="display: flex; gap: 12px; justify-content: center;">
                            <button type="submit" class="btn btn-primary" onclick="return confirmarRestauracion()">
                                🔄 Restaurar Base de Datos
                            </button>
                            <a href="respaldo.php" class="btn" style="background: #10b981; color: white; text-decoration: none;">
                                💾 Hacer Respaldo Primero
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Respaldos Disponibles -->
            <?php if (count($respaldos_disponibles) > 0): ?>
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h2 class="card-title">Respaldos Disponibles en el Servidor</h2>
                        <p class="card-subtitle">Archivos en la carpeta database/respaldos/</p>
                    </div>
                    <div class="backups-list">
                        <?php foreach ($respaldos_disponibles as $respaldo): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <div class="backup-name">📄 <?php echo htmlspecialchars($respaldo['nombre']); ?></div>
                                    <div class="backup-meta">
                                        Tamaño: <?php echo formatearTamano($respaldo['tamano']); ?> • 
                                        Fecha: <?php echo date('d/m/Y H:i:s', $respaldo['fecha']); ?>
                                    </div>
                                </div>
                                <div>
                                    <a href="../../database/respaldos/<?php echo urlencode($respaldo['nombre']); ?>" 
                                       class="btn-small" 
                                       style="background: var(--color-primary); color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px;">
                                        ⬇️ Descargar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Información Adicional -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">ℹ️ Información sobre la Restauración</h2>
                </div>
                <div style="padding: 24px;">
                    <h4 style="margin-bottom: 12px;">¿Cuándo usar la restauración?</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid var(--color-border-light);">
                            ✓ Recuperación de datos después de un error
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid var(--color-border-light);">
                            ✓ Migración de datos desde otro servidor
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid var(--color-border-light);">
                            ✓ Restaurar a un punto anterior en el tiempo
                        </li>
                        <li style="padding: 8px 0;">
                            ✓ Pruebas con datos de producción en desarrollo
                        </li>
                    </ul>
                    
                    <h4 style="margin-top: 24px; margin-bottom: 12px;">Proceso de restauración:</h4>
                    <ol style="margin-left: 20px;">
                        <li style="margin-bottom: 8px;">Se deshabilitan las restricciones de claves foráneas</li>
                        <li style="margin-bottom: 8px;">Se ejecutan todos los statements SQL del archivo</li>
                        <li style="margin-bottom: 8px;">Se rehabilitan las restricciones de claves foráneas</li>
                        <li style="margin-bottom: 8px;">Se registra la acción en el log de auditoría</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function mostrarArchivoSeleccionado(input) {
            const selectedFileDiv = document.getElementById('selected-file');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                selectedFileDiv.innerHTML = `
                    <strong>Archivo seleccionado:</strong> ${file.name}<br>
                    <strong>Tamaño:</strong> ${sizeMB} MB
                `;
                selectedFileDiv.style.display = 'block';
            } else {
                selectedFileDiv.style.display = 'none';
            }
        }
        
        function confirmarRestauracion() {
            const confirmacion = confirm(
                '⚠️ ADVERTENCIA CRÍTICA\n\n' +
                'Esta acción SOBRESCRIBIRÁ TODOS los datos actuales de la base de datos.\n\n' +
                '¿Está ABSOLUTAMENTE SEGURO de que desea continuar?\n\n' +
                'Se recomienda hacer un respaldo antes de proceder.'
            );
            
            if (confirmacion) {
                const segundaConfirmacion = confirm(
                    'Esta es su última oportunidad para cancelar.\n\n' +
                    '¿Confirma que desea RESTAURAR la base de datos?'
                );
                
                if (segundaConfirmacion) {
                    // Mostrar indicador de carga
                    const form = document.getElementById('form-restaurar');
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '⏳ Restaurando... Por favor espere';
                    return true;
                }
            }
            
            return false;
        }
    </script>
</body>
</html>
