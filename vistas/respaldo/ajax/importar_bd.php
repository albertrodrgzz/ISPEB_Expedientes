<?php
/**
 * AJAX: Importar Base de Datos
 * Procesa un archivo SQL para restaurar la base de datos
 */

// Aumentar límites de ejecución
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

// Verificar sesión y permisos
verificarSesion();
if (!verificarNivel(1)) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    // Verificar que se haya subido un archivo
    if (!isset($_FILES['archivo_sql']) || $_FILES['archivo_sql']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ningún archivo o hubo un error en la carga');
    }
    
    $archivo = $_FILES['archivo_sql'];
    
    // Validar extensión
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension !== 'sql') {
        throw new Exception('Solo se permiten archivos .sql');
    }
    
    // Validar tamaño (50MB máximo)
    if ($archivo['size'] > 50 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande (máximo 50MB)');
    }
    
    // Leer contenido del archivo
    $contenidoSQL = file_get_contents($archivo['tmp_name']);
    
    if (empty($contenidoSQL)) {
        throw new Exception('El archivo está vacío');
    }
    
    // Conectar a la base de datos
    $db = getDB();
    
    // Configurar la conexión
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $db->exec('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
    $db->exec('SET NAMES utf8mb4');
    
    try {
        // Eliminar comentarios SQL
        $contenidoSQL = preg_replace('/^--.*$/m', '', $contenidoSQL);
        $contenidoSQL = preg_replace('/\/\*.*?\*\//s', '', $contenidoSQL);
        
        // Dividir en sentencias respetando strings y delimitadores
        $sentencias = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($contenidoSQL);
        
        for ($i = 0; $i < $len; $i++) {
            $char = $contenidoSQL[$i];
            $prevChar = $i > 0 ? $contenidoSQL[$i-1] : '';
            
            // Detectar strings
            if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }
            
            // Punto y coma fuera de string = fin de sentencia
            if ($char === ';' && !$inString) {
                $sentencia = trim($buffer);
                if (!empty($sentencia) && strlen($sentencia) > 3) {
                    $sentencias[] = $sentencia;
                }
                $buffer = '';
            } else {
                $buffer .= $char;
            }
        }
        
        // Agregar última sentencia
        $sentencia = trim($buffer);
        if (!empty($sentencia) && strlen($sentencia) > 3) {
            $sentencias[] = $sentencia;
        }
        
        $ejecutadas = 0;
        $omitidas = 0;
        $errores = [];
        
        foreach ($sentencias as $index => $sentencia) {
            $sentencia = trim($sentencia);
            
            // Ignorar sentencias vacías
            if (empty($sentencia)) {
                continue;
            }
            
            // Ignorar comandos SET que ya ejecutamos
            $upperSentencia = strtoupper(substr($sentencia, 0, 50));
            if (strpos($upperSentencia, 'SET FOREIGN_KEY_CHECKS') !== false ||
                strpos($upperSentencia, 'SET SQL_MODE') !== false ||
                strpos($upperSentencia, 'SET TIME_ZONE') !== false ||
                strpos($upperSentencia, 'SET NAMES') !== false) {
                $omitidas++;
                continue;
            }
            
            try {
                $db->exec($sentencia);
                $ejecutadas++;
            } catch (PDOException $e) {
                $errorCode = $e->getCode();
                $errorMsg = $e->getMessage();
                
                // Códigos de error que podemos ignorar (son esperados)
                $erroresIgnorables = [
                    '42S01', // Tabla ya existe
                    '42S02', // Tabla no existe (en DROP)
                    '23000', // Violación de integridad (duplicados)
                    'HY000', // Error general (incluye 1393 - vista no modificable)
                    '1393',  // No se puede modificar vista
                ];
                
                // Si es un error ignorable, solo contarlo como advertencia
                $esIgnorable = false;
                foreach ($erroresIgnorables as $codigo) {
                    if (strpos($errorCode, $codigo) !== false || strpos($errorMsg, $codigo) !== false) {
                        $esIgnorable = true;
                        break;
                    }
                }
                
                // También ignorar si el mensaje contiene "already exists" o "doesn't exist"
                if (stripos($errorMsg, 'already exists') !== false || 
                    stripos($errorMsg, "doesn't exist") !== false ||
                    stripos($errorMsg, 'ya existe') !== false ||
                    stripos($errorMsg, 'view') !== false ||
                    stripos($errorMsg, 'vista') !== false) {
                    $esIgnorable = true;
                }
                
                if ($esIgnorable) {
                    // Solo registrar como advertencia, no como error crítico
                    $omitidas++;
                } else {
                    // Error real
                    $errores[] = [
                        'linea' => $index + 1,
                        'sentencia' => substr($sentencia, 0, 100),
                        'error' => $errorMsg,
                        'codigo' => $errorCode
                    ];
                    
                    // Si hay demasiados errores REALES, abortar
                    if (count($errores) > 20) {
                        throw new Exception(
                            'Demasiados errores críticos durante la importación. ' .
                            'Primer error: ' . $errores[0]['error'] . 
                            ' (Código: ' . $errores[0]['codigo'] . ') en línea ' . $errores[0]['linea']
                        );
                    }
                }
            }
        }
        
        // Restaurar configuración
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
        
        // Registrar en auditoría
        registrarAuditoria(
            'IMPORTAR_BD', 
            'sistema', 
            null, 
            "Importación: {$archivo['name']}, {$ejecutadas} sentencias ejecutadas, {$omitidas} omitidas, " . count($errores) . " errores"
        );
        
        // Preparar mensaje de respuesta
        $mensaje = "Base de datos importada exitosamente.\n";
        $mensaje .= "• Sentencias ejecutadas: {$ejecutadas}\n";
        $mensaje .= "• Sentencias omitidas/duplicadas: {$omitidas}\n";
        
        if (count($errores) > 0) {
            $mensaje .= "• Errores críticos: " . count($errores);
        } else {
            $mensaje .= "• Sin errores críticos";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'sentencias_ejecutadas' => $ejecutadas,
            'sentencias_omitidas' => $omitidas,
            'advertencias' => count($errores),
            'detalles_errores' => array_slice($errores, 0, 5) // Solo primeros 5 errores
        ]);
        
    } catch (Exception $e) {
        // Restaurar configuración
        try {
            $db->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Exception $ex) {
            // Ignorar
        }
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error al importar BD: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
