<?php
// Suprimir TODOS los errores y warnings ANTES de cualquier include
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/**
 * AJAX: Exportar Base de Datos
 * Genera un archivo SQL con el respaldo de la base de datos
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

// Verificar sesión y permisos
verificarSesion();
if (!verificarNivel(1)) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}

try {
    // Obtener opciones
    $incluirEstructura = isset($_POST['incluir_estructura']) && $_POST['incluir_estructura'] == '1';
    $incluirDatos = isset($_POST['incluir_datos']) && $_POST['incluir_datos'] == '1';
    $dropTables = isset($_POST['drop_tables']) && $_POST['drop_tables'] == '1';
    
    // Conectar a la base de datos
    $db = getDB();
    
    // Nombre del archivo
    $fecha = date('Ymd_His');
    $nombreArchivo = "backup_{$fecha}.sql";
    
    // Headers para descarga
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Iniciar output buffer
    ob_start();
    
    // Obtener nombre de usuario de forma segura
    $usuarioNombre = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 
                     (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Sistema');
    
    // Encabezado del archivo
    echo "-- =====================================================\n";
    echo "-- Respaldo de Base de Datos - Sistema ISPEB\n";
    echo "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Generado por: " . $usuarioNombre . "\n";
    echo "-- =====================================================\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n";
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "SET time_zone = \"+00:00\";\n";
    echo "SET NAMES utf8mb4;\n\n";
    
    // Obtener todas las tablas
    $stmt = $db->query("SHOW FULL TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_NUM);
    
    foreach ($tablas as $tablaInfo) {
        $tabla = $tablaInfo[0];
        $tipo = $tablaInfo[1]; // 'BASE TABLE' o 'VIEW'
        
        echo "\n-- =====================================================\n";
        echo "-- {$tipo}: {$tabla}\n";
        echo "-- =====================================================\n\n";
        
        // DROP TABLE/VIEW si se solicita
        if ($dropTables) {
            if ($tipo === 'VIEW') {
                echo "DROP VIEW IF EXISTS `{$tabla}`;\n\n";
            } else {
                echo "DROP TABLE IF EXISTS `{$tabla}`;\n\n";
            }
        }
        
        // Estructura de la tabla o vista
        if ($incluirEstructura) {
            if ($tipo === 'VIEW') {
                // Para vistas, usar SHOW CREATE VIEW
                $stmt = $db->query("SHOW CREATE VIEW `{$tabla}`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                
                if ($row && isset($row[1])) {
                    echo $row[1] . ";\n\n";
                }
            } else {
                // Para tablas, usar SHOW CREATE TABLE
                $stmt = $db->query("SHOW CREATE TABLE `{$tabla}`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                
                if ($row && isset($row[1])) {
                    echo $row[1] . ";\n\n";
                }
            }
        }
        
        // Datos SOLO para tablas (no para vistas)
        if ($incluirDatos && $tipo === 'BASE TABLE') {
            $stmt = $db->query("SELECT * FROM `{$tabla}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $columnas = array_keys($row);
                    $valores = array_values($row);
                    
                    // Escapar valores
                    $valoresEscapados = array_map(function($valor) use ($db) {
                        if ($valor === null) {
                            return 'NULL';
                        }
                        // Escapar manualmente para evitar problemas
                        $escaped = $db->quote($valor);
                        return $escaped;
                    }, $valores);
                    
                    echo "INSERT INTO `{$tabla}` (`" . implode('`, `', $columnas) . "`) VALUES (" . implode(', ', $valoresEscapados) . ");\n";
                }
                echo "\n";
            }
        }
    }
    
    echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
    echo "\n-- Fin del respaldo\n";
    
    // Enviar el contenido
    $content = ob_get_clean();
    echo $content;
    
    // Registrar en auditoría (después de enviar el archivo)
    registrarAuditoria('EXPORTAR_BD', 'sistema', null, 'Exportación de base de datos completa');
    
} catch (Exception $e) {
    // Limpiar buffer si existe
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    error_log("Error al exportar BD: " . $e->getMessage());
    http_response_code(500);
    echo "-- ERROR: No se pudo generar el respaldo\n";
    echo "-- " . $e->getMessage() . "\n";
}
