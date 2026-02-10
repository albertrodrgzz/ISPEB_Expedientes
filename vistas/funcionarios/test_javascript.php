<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test JavaScript cargarNombramientos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        #nombramientos-container { border: 1px solid #ddd; padding: 20px; margin-top: 20px; min-height: 200px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test de función cargarNombramientos()</h1>
        
        <?php
        require_once __DIR__ . '/../../config/database.php';
        $pdo = getDB();
        
        // Obtener funcionario con nombramiento
        $stmt = $pdo->query("
            SELECT DISTINCT f.id, f.nombres, f.apellidos 
            FROM funcionarios f
            INNER JOIN historial_administrativo h ON f.id = h.funcionario_id
            WHERE h.tipo_evento = 'NOMBRAMIENTO'
            LIMIT 1
        ");
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$funcionario) {
            echo "<p class='error'>❌ No hay funcionarios con nombramientos</p>";
            exit;
        }
        
        $id = $funcionario['id'];
        ?>
        
        <p><strong>Funcionario:</strong> <?php echo $funcionario['nombres'] . ' ' . $funcionario['apellidos']; ?></p>
        <p><strong>ID:</strong> <?php echo $id; ?></p>
        
        <button onclick="cargarNombramientos()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🔄 Cargar Nombramientos
        </button>
        
        <div id="nombramientos-container">
            <p style="text-align: center; color: #999;">Click el botón para cargar...</p>
        </div>
        
        <h2>Log de Consola:</h2>
        <pre id="console-log"></pre>
    </div>
    
    <script>
        const funcionarioId = <?php echo $id; ?>;
        const consoleLog = document.getElementById('console-log');
        
        // Interceptar console.log
        const originalLog = console.log;
        const originalError = console.error;
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            consoleLog.textContent += 'LOG: ' + args.join(' ') + '\n';
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            consoleLog.textContent += 'ERROR: ' + args.join(' ') + '\n';
            consoleLog.style.color = 'red';
        };
        
        function cargarNombramientos() {
            const container = document.getElementById('nombramientos-container');
            
            console.log('=== INICIANDO cargarNombramientos ===');
            console.log('Funcionario ID:', funcionarioId);
            
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;">Cargando...</div>';
            
            const url = `ajax/obtener_historial.php?funcionario_id=${funcionarioId}&tipo_evento=NOMBRAMIENTO`;
            console.log('URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    
                    // Intentar parsear como JSON
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        
                        if (data.success && data.total > 0) {
                            console.log('✅ Tiene nombramientos:', data.total);
                            let html = '<div class="table-responsive"><table class="data-table" style="width: 100%; border-collapse: collapse;"><thead><tr>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd;">Fecha</th>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd;">Cargo</th>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd;">Departamento</th>';
                            html += '<th style="padding: 10px; border: 1px solid #ddd;">Documento</th>';
                            html += '</tr></thead><tbody>';
                            
                            data.data.forEach(item => {
                                const detalles = item.detalles || {};
                                console.log('Item detalles:', detalles);
                                html += '<tr>';
                                html += `<td style="padding: 10px; border: 1px solid #ddd;"><strong>${item.fecha_evento_formateada}</strong></td>`;
                                html += `<td style="padding: 10px; border: 1px solid #ddd;">${detalles.cargo || 'N/A'}</td>`;
                                html += `<td style="padding: 10px; border: 1px solid #ddd;">${detalles.departamento || 'N/A'}</td>`;
                                html += '<td style="padding: 10px; border: 1px solid #ddd;">';
                                if (item.tiene_archivo) {
                                    html += `<a href="../../${item.ruta_archivo_pdf}" target="_blank">📥 Ver PDF</a>`;
                                } else {
                                    html += '-';
                                }
                                html += '</td></tr>';
                            });
                            
                            html += '</tbody></table></div>';
                            container.innerHTML = html;
                            console.log('✅ Tabla renderizada correctamente');
                        } else {
                            console.log('⚠️ No hay nombramientos');
                            container.innerHTML = `
                                <div style="text-align: center; padding: 40px;">
                                    <div style="font-size: 48px;">📄</div>
                                    <p style="color: #999;">No hay nombramientos registrados</p>
                                </div>
                            `;
                        }
                    } catch (e) {
                        console.error('❌ Error parseando JSON:', e);
                        console.error('Texto recibido:', text.substring(0, 500));
                        container.innerHTML = `
                            <div style="padding: 20px; background: #fee; border: 1px solid #fcc;">
                                <strong>Error parseando respuesta</strong>
                                <pre>${text.substring(0, 500)}</pre>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('❌ Error en fetch:', error);
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <div style="font-size: 48px; color: red;">⚠️</div>
                            <p style="color: red;">Error al cargar nombramientos</p>
                            <p>${error.message}</p>
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>
