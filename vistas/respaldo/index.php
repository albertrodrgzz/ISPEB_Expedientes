<?php
/**
 * Vista: Respaldo de Base de Datos
 * Módulo principal con opciones de exportar e importar
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1 - Directores)
verificarSesion();
if (!verificarNivel(1)) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$pageTitle = 'Respaldo de Base de Datos';
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
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <?php echo $pageTitle; ?>
                    </h1>
                    <p class="page-description">Gestione los respaldos de la base de datos del sistema</p>
                </div>

                <!-- Grid de opciones principales -->
                <div class="options-grid">
                    <!-- Opción: Exportar -->
                    <div class="option-card export-card">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                        <h2>Exportar Base de Datos</h2>
                        <p>Descargue una copia de seguridad completa de la base de datos en formato SQL</p>
                        <div class="option-features">
                            <div class="feature">✓ Estructura de tablas</div>
                            <div class="feature">✓ Todos los datos</div>
                            <div class="feature">✓ Formato SQL estándar</div>
                        </div>
                        <button class="btn btn-primary" onclick="mostrarExportar()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Exportar Ahora
                        </button>
                    </div>

                    <!-- Opción: Importar -->
                    <div class="option-card import-card">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <h2>Importar Base de Datos</h2>
                        <p>Restaure la base de datos desde un archivo SQL de respaldo previamente exportado</p>
                        <div class="option-features">
                            <div class="feature">✓ Archivos .sql</div>
                            <div class="feature">✓ Hasta 50 MB</div>
                            <div class="feature">✓ Restauración completa</div>
                        </div>
                        <button class="btn btn-danger" onclick="mostrarImportar()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            Importar Ahora
                        </button>
                    </div>
                </div>

                <!-- Sección de Exportar (oculta inicialmente) -->
                <div id="seccionExportar" class="backup-section" style="display: none;">
                    <div class="section-header">
                        <h2>Exportar Base de Datos</h2>
                        <button class="btn-close" onclick="ocultarSecciones()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <div class="content-grid">
                        <div class="card">
                            <div class="card-header">
                                <h3>Opciones de Exportación</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="incluir_estructura" checked>
                                        Incluir estructura de tablas (CREATE TABLE)
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="incluir_datos" checked>
                                        Incluir datos (INSERT INTO)
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="drop_tables">
                                        Incluir DROP TABLE IF EXISTS
                                    </label>
                                </div>

                                <button type="button" class="btn btn-primary" onclick="exportarBaseDatos()" style="width: 100%; margin-top: 20px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Descargar Respaldo
                                </button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3>Información del Respaldo</h3>
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Formato:</span>
                                        <span class="info-value">SQL (.sql)</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Codificación:</span>
                                        <span class="info-value">UTF-8</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Nombre:</span>
                                        <span class="info-value">backup_YYYYMMDD_HHMMSS.sql</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Importar (oculta inicialmente) -->
                <div id="seccionImportar" class="backup-section" style="display: none;">
                    <div class="section-header">
                        <h2>Importar Base de Datos</h2>
                        <button class="btn-close" onclick="ocultarSecciones()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <div class="alert alert-danger" style="margin-bottom: 24px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <div>
                            <strong>¡ADVERTENCIA!</strong>
                            <p>Esta acción sobrescribirá TODOS los datos actuales. Asegúrese de tener un respaldo antes de continuar.</p>
                        </div>
                    </div>

                    <div class="content-grid">
                        <div class="card">
                            <div class="card-header">
                                <h3>Seleccionar Archivo SQL</h3>
                            </div>
                            <div class="card-body">
                                <form id="formImportar" enctype="multipart/form-data">
                                    <div class="upload-area" id="uploadArea">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        <p class="upload-text">Arrastre el archivo SQL aquí o haga clic</p>
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

                                    <button type="button" class="btn btn-danger" onclick="importarBaseDatos()" style="width: 100%; margin-top: 20px;" id="btnImportar" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        Importar y Restaurar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3>Instrucciones</h3>
                            </div>
                            <div class="card-body">
                                <h4>Antes de importar:</h4>
                                <ol>
                                    <li>Asegúrese de tener un respaldo actual</li>
                                    <li>Verifique que el archivo SQL sea válido</li>
                                    <li>Cierre sesiones activas de otros usuarios</li>
                                </ol>

                                <h4 style="margin-top: 20px;">Limitaciones:</h4>
                                <ul>
                                    <li>Tamaño máximo: 50 MB</li>
                                    <li>Solo archivos .sql sin comprimir</li>
                                    <li>Tiempo máximo: 5 minutos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function mostrarExportar() {
        document.querySelector('.options-grid').style.display = 'none';
        document.getElementById('seccionExportar').style.display = 'block';
        document.getElementById('seccionImportar').style.display = 'none';
    }

    function mostrarImportar() {
        document.querySelector('.options-grid').style.display = 'none';
        document.getElementById('seccionExportar').style.display = 'none';
        document.getElementById('seccionImportar').style.display = 'block';
    }

    function ocultarSecciones() {
        document.querySelector('.options-grid').style.display = 'grid';
        document.getElementById('seccionExportar').style.display = 'none';
        document.getElementById('seccionImportar').style.display = 'none';
        removerArchivo();
    }

    // ===== EXPORTAR =====
    function exportarBaseDatos() {
        const incluirEstructura = document.getElementById('incluir_estructura').checked;
        const incluirDatos = document.getElementById('incluir_datos').checked;
        const dropTables = document.getElementById('drop_tables').checked;

        if (!incluirEstructura && !incluirDatos) {
            Swal.fire({
                icon: 'warning',
                title: 'Opciones inválidas',
                text: 'Debe seleccionar al menos una opción',
                confirmButtonColor: '#00a8cc'
            });
            return;
        }

        Swal.fire({
            title: '¿Exportar base de datos?',
            text: 'Se descargará un archivo SQL con el respaldo completo',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00a8cc',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, exportar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Generando respaldo...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo APP_URL; ?>/vistas/respaldo/ajax/exportar_bd.php';
                
                const inputEstructura = document.createElement('input');
                inputEstructura.type = 'hidden';
                inputEstructura.name = 'incluir_estructura';
                inputEstructura.value = incluirEstructura ? '1' : '0';
                form.appendChild(inputEstructura);
                
                const inputDatos = document.createElement('input');
                inputDatos.type = 'hidden';
                inputDatos.name = 'incluir_datos';
                inputDatos.value = incluirDatos ? '1' : '0';
                form.appendChild(inputDatos);
                
                const inputDrop = document.createElement('input');
                inputDrop.type = 'hidden';
                inputDrop.name = 'drop_tables';
                inputDrop.value = dropTables ? '1' : '0';
                form.appendChild(inputDrop);
                
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Respaldo generado',
                        text: 'El archivo se está descargando',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }, 1000);
            }
        });
    }

    // ===== IMPORTAR =====
    const uploadArea = document.getElementById('uploadArea');
    const archivoInput = document.getElementById('archivoSQL');
    const btnImportar = document.getElementById('btnImportar');

    uploadArea.addEventListener('click', () => archivoInput.click());

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
            html: '<strong style="color: #dc3545;">ADVERTENCIA:</strong> Esta acción sobrescribirá todos los datos.<br><br>¿Continuar?',
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
                    title: 'Importando...',
                    html: 'Por favor espere.<br><strong>No cierre esta ventana.</strong>',
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
                            ocultarSecciones();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            confirmButtonColor: '#00a8cc'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error: ' + error.message,
                        confirmButtonColor: '#00a8cc'
                    });
                });
            }
        });
    }
    </script>

    <style>
    .options-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
        margin-top: 32px;
    }

    .option-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .option-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .export-card {
        border-top: 4px solid #00a8cc;
    }

    .import-card {
        border-top: 4px solid #dc3545;
    }

    .option-icon {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }

    .export-card .option-icon {
        background: linear-gradient(135deg, #e7f5ff 0%, #d0ebff 100%);
        color: #00a8cc;
    }

    .import-card .option-icon {
        background: linear-gradient(135deg, #ffe5e5 0%, #ffd0d0 100%);
        color: #dc3545;
    }

    .option-card h2 {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 12px;
    }

    .option-card p {
        color: #718096;
        margin-bottom: 24px;
        line-height: 1.6;
    }

    .option-features {
        width: 100%;
        margin-bottom: 24px;
    }

    .feature {
        padding: 8px 0;
        color: #4a5568;
        font-size: 14px;
    }

    .option-card .btn {
        width: 100%;
        margin-top: auto;
    }

    .backup-section {
        margin-top: 32px;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e2e8f0;
    }

    .section-header h2 {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
    }

    .btn-close {
        background: #f1f5f9;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        color: #64748b;
    }

    .btn-close:hover {
        background: #e2e8f0;
        color: #334155;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .info-grid {
        display: grid;
        gap: 12px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
    }

    .info-value {
        color: #00a8cc;
        font-weight: 500;
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

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px 0;
    }

    .form-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
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

    .card h4 {
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 12px;
    }

    .card ol,
    .card ul {
        margin-left: 20px;
        color: #4a5568;
        line-height: 1.8;
    }

    .card li {
        margin-bottom: 8px;
    }

    @media (max-width: 1024px) {
        .options-grid,
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
