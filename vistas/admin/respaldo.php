<?php
/**
 * Generador de Respaldo de Base de Datos
 * Solo accesible para nivel 1
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos
verificarSesion();

if (!verificarNivel(1)) {
    die('Solo los administradores pueden generar respaldos');
}

// Registrar en auditoría
registrarAuditoria('GENERAR_RESPALDO_BD');

// Configuración
$fecha = date('Y-m-d_His');
$filename = 'respaldo_ispeb_' . $fecha . '.sql';
$directorio_respaldos = __DIR__ . '/../../database/respaldos/';

// Crear directorio si no existe
if (!file_exists($directorio_respaldos)) {
    mkdir($directorio_respaldos, 0755, true);
}

$ruta_completa = $directorio_respaldos . $filename;

// Generar respaldo usando mysqldump
$comando = sprintf(
    'mysqldump --user=%s --password=%s --host=%s %s > %s',
    DB_USER,
    DB_PASS,
    DB_HOST,
    DB_NAME,
    escapeshellarg($ruta_completa)
);

// Ejecutar comando
exec($comando, $output, $return_var);

// Verificar si se generó el archivo
if (file_exists($ruta_completa) && filesize($ruta_completa) > 0) {
    // Configurar headers para descarga
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($ruta_completa));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Enviar archivo
    readfile($ruta_completa);
    exit;
} else {
    // Si mysqldump no está disponible, usar método PHP
    try {
        $db = getDB();
        $respaldo = "-- =====================================================\n";
        $respaldo .= "-- RESPALDO DE BASE DE DATOS - ISPEB\n";
        $respaldo .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $respaldo .= "-- Generado por: " . $_SESSION['nombre_completo'] . "\n";
        $respaldo .= "-- =====================================================\n\n";
        $respaldo .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Obtener todas las tablas
        $stmt = $db->query("SHOW TABLES");
        $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tablas as $tabla) {
            // Estructura de la tabla
            $stmt = $db->query("SHOW CREATE TABLE `$tabla`");
            $create = $stmt->fetch();
            $respaldo .= "-- Tabla: $tabla\n";
            $respaldo .= "DROP TABLE IF EXISTS `$tabla`;\n";
            $respaldo .= $create['Create Table'] . ";\n\n";
            
            // Datos de la tabla
            $stmt = $db->query("SELECT * FROM `$tabla`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                $respaldo .= "-- Datos de $tabla\n";
                foreach ($rows as $row) {
                    $valores = array_map(function($val) use ($db) {
                        return $val === null ? 'NULL' : $db->quote($val);
                    }, array_values($row));
                    
                    $respaldo .= "INSERT INTO `$tabla` VALUES (" . implode(', ', $valores) . ");\n";
                }
                $respaldo .= "\n";
            }
        }
        
        $respaldo .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Guardar archivo
        file_put_contents($ruta_completa, $respaldo);
        
        // Descargar
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($respaldo));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $respaldo;
        exit;
        
    } catch (Exception $e) {
        die('Error al generar el respaldo: ' . $e->getMessage());
    }
}
