<?php
/**
 * Vista: Exportar Base de Datos
 * Permite exportar la base de datos completa o tablas específicas
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos (solo nivel 1 - Directores)
verificarSesion();
if (!verificarNivel(1)) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

$pageTitle = 'Exportar Base de Datos';
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
                    <p class="page-description">Descargue una copia de seguridad de la base de datos del sistema</p>
                </div>

                <div class="content-grid">
                    <!-- Card de Exportación Completa -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Exportación Completa</h2>
                            <p>Descargue un respaldo completo de toda la base de datos</p>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info" style="margin-bottom: 20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <div>
                                    <strong>Información:</strong>
                                    <p>El archivo SQL contendrá la estructura completa y todos los datos de la base de datos.</p>
                                </div>
                            </div>

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
                                Descargar Respaldo Completo
                            </button>
                        </div>
                    </div>

                    <!-- Card de Información -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Información del Respaldo</h2>
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
                                    <span class="info-label">Compresión:</span>
                                    <span class="info-value">No comprimido</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Nombre del archivo:</span>
                                    <span class="info-value">backup_YYYYMMDD_HHMMSS.sql</span>
                                </div>
                            </div>

                            <div class="alert alert-warning" style="margin-top: 20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <div>
                                    <strong>Recomendaciones:</strong>
                                    <ul style="margin: 10px 0 0 20px;">
                                        <li>Realice respaldos periódicamente</li>
                                        <li>Almacene los respaldos en un lugar seguro</li>
                                        <li>Verifique la integridad del archivo descargado</li>
                                        <li>Mantenga múltiples copias en diferentes ubicaciones</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function exportarBaseDatos() {
        const incluirEstructura = document.getElementById('incluir_estructura').checked;
        const incluirDatos = document.getElementById('incluir_datos').checked;
        const dropTables = document.getElementById('drop_tables').checked;

        if (!incluirEstructura && !incluirDatos) {
            Swal.fire({
                icon: 'warning',
                title: 'Opciones inválidas',
                text: 'Debe seleccionar al menos una opción (estructura o datos)',
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
                // Mostrar loading
                Swal.fire({
                    title: 'Generando respaldo...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Crear formulario para descargar
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

                // Cerrar loading después de un momento
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
    </script>

    <style>
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    .info-grid {
        display: grid;
        gap: 16px;
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

    .alert-info {
        background: #e7f5ff;
        border-color: #74c0fc;
        color: #1971c2;
    }

    .alert-warning {
        background: #fff3cd;
        border-color: #ffc107;
        color: #856404;
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

    @media (max-width: 1024px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
