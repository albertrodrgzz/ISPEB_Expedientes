<?php
/**
 * Vista: Importar Base de Datos
 * Permite importar un archivo SQL para restaurar la base de datos
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1 - Directores)
verificarSesion();
if (!verificarNivel(1)) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$pageTitle = 'Importar Base de Datos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . APP_NAME; ?></title>
    
    <!-- Estilos globales -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/publico/css/estilos.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../layout/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../layout/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1>
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <?php echo $pageTitle; ?>
                    </h1>
                    <p class="page-description">Restaure la base de datos desde un archivo SQL de respaldo</p>
                </div>

                <div class="content-grid">
                    <!-- Card de Importación -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Seleccionar Archivo SQL</h2>
                            <p>Cargue un archivo de respaldo para restaurar la base de datos</p>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger" style="margin-bottom: 20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <div>
                                    <strong>¡ADVERTENCIA!</strong>
                                    <p>Esta acción sobrescribirá TODOS los datos actuales de la base de datos. Asegúrese de tener un respaldo antes de continuar.</p>
                                </div>
                            </div>

                            <form id="formImportar" enctype="multipart/form-data">
                                <div class="upload-area" id="uploadArea">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p class="upload-text">Arrastre el archivo SQL aquí o haga clic para seleccionar</p>
                                    <p class="upload-subtext">Archivos .sql (máximo 50MB)</p>
                                    <input type="file" id="archivoSQL" name="archivo_sql" accept=".sql" style="display: none;">
                                </div>

                                <div id="archivoSeleccionado" style="display: none; margin-top: 20px;">
                                    <div class="file-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        <div class="file-details">
                                            <span id="nombreArchivo" class="file-name"></span>
                                            <span id="tamanoArchivo" class="file-size"></span>
                                        </div>
                                        <button type="button" class="btn-remove" onclick="removerArchivo()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-primary" onclick="importarBaseDatos()" style="width: 100%; margin-top: 20px;" id="btnImportar" disabled>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    Importar y Restaurar Base de Datos
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Card de Información -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Instrucciones</h2>
                        </div>
                        <div class="card-body">
                            <div class="instructions">
                                <h3>Antes de importar:</h3>
                                <ol>
                                    <li>Asegúrese de tener un respaldo actual de la base de datos</li>
                                    <li>Verifique que el archivo SQL sea válido y compatible</li>
                                    <li>Cierre todas las sesiones activas de otros usuarios</li>
                                    <li>Notifique al personal sobre el mantenimiento</li>
                                </ol>

                                <h3 style="margin-top: 24px;">Formatos aceptados:</h3>
                                <ul>
                                    <li>Archivos .sql generados por MySQL/MariaDB</li>
                                    <li>Archivos exportados desde este sistema</li>
                                    <li>Respaldos generados con mysqldump</li>
                                </ul>

                                <h3 style="margin-top: 24px;">Limitaciones:</h3>
                                <ul>
                                    <li>Tamaño máximo: 50 MB</li>
                                    <li>Tiempo máximo de ejecución: 5 minutos</li>
                                    <li>Solo archivos .sql sin comprimir</li>
                                </ul>
                            </div>

                            <div class="alert alert-info" style="margin-top: 20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <div>
                                    <strong>Nota:</strong>
                                    <p>El proceso de importación puede tardar varios minutos dependiendo del tamaño del archivo.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const uploadArea = document.getElementById('uploadArea');
    const archivoInput = document.getElementById('archivoSQL');
    const btnImportar = document.getElementById('btnImportar');

    // Click en área de carga
    uploadArea.addEventListener('click', () => {
        archivoInput.click();
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#00a8cc';
        uploadArea.style.background = '#e7f5ff';
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#cbd5e1';
        uploadArea.style.background = '#f8f9fa';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#cbd5e1';
        uploadArea.style.background = '#f8f9fa';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            archivoInput.files = files;
            mostrarArchivo(files[0]);
        }
    });

    // Selección de archivo
    archivoInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            mostrarArchivo(e.target.files[0]);
        }
    });

    function mostrarArchivo(file) {
        if (!file.name.endsWith('.sql')) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo inválido',
                text: 'Solo se permiten archivos .sql',
                confirmButtonColor: '#00a8cc'
            });
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El archivo no debe superar los 50 MB',
                confirmButtonColor: '#00a8cc'
            });
            return;
        }

        document.getElementById('nombreArchivo').textContent = file.name;
        document.getElementById('tamanoArchivo').textContent = formatBytes(file.size);
        document.getElementById('archivoSeleccionado').style.display = 'block';
        btnImportar.disabled = false;
    }

    function removerArchivo() {
        archivoInput.value = '';
        document.getElementById('archivoSeleccionado').style.display = 'none';
        btnImportar.disabled = true;
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function importarBaseDatos() {
        if (!archivoInput.files.length) {
            Swal.fire({
                icon: 'warning',
                title: 'No hay archivo',
                text: 'Debe seleccionar un archivo SQL',
                confirmButtonColor: '#00a8cc'
            });
            return;
        }

        Swal.fire({
            title: '¿Importar base de datos?',
            html: '<strong style="color: #dc3545;">ADVERTENCIA:</strong> Esta acción sobrescribirá todos los datos actuales.<br><br>¿Está seguro de continuar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('archivo_sql', archivoInput.files[0]);

                Swal.fire({
                    title: 'Importando base de datos...',
                    html: 'Por favor espere, esto puede tardar varios minutos.<br><strong>No cierre esta ventana.</strong>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('<?php echo APP_URL; ?>/vistas/respaldo/ajax/importar_bd.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Importación exitosa',
                            text: data.message,
                            confirmButtonColor: '#00a8cc'
                        }).then(() => {
                            removerArchivo();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error en la importación',
                            text: data.error,
                            confirmButtonColor: '#00a8cc'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al procesar la solicitud: ' + error.message,
                        confirmButtonColor: '#00a8cc'
                    });
                });
            }
        });
    }
    </script>

    <style>
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    .upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover {
        border-color: #00a8cc;
        background: #e7f5ff;
    }

    .upload-area svg {
        color: #00a8cc;
        margin-bottom: 16px;
    }

    .upload-text {
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }

    .upload-subtext {
        font-size: 14px;
        color: #718096;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .file-info svg {
        color: #00a8cc;
        flex-shrink: 0;
    }

    .file-details {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .file-name {
        font-weight: 600;
        color: #2d3748;
    }

    .file-size {
        font-size: 14px;
        color: #718096;
    }

    .btn-remove {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background 0.2s;
    }

    .btn-remove:hover {
        background: #fee;
    }

    .instructions h3 {
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 12px;
    }

    .instructions ol,
    .instructions ul {
        margin-left: 20px;
        color: #4a5568;
        line-height: 1.8;
    }

    .instructions li {
        margin-bottom: 8px;
    }

    .alert {
        display: flex;
        gap: 12px;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid;
    }

    .alert svg {
        flex-shrink: 0;
        margin-top: 2px;
    }

    .alert-danger {
        background: #fee;
        border-color: #fcc;
        color: #dc3545;
    }

    .alert-info {
        background: #e7f5ff;
        border-color: #74c0fc;
        color: #1971c2;
    }

    @media (max-width: 1024px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
