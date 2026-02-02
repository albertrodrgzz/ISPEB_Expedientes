<?php
/**
 * Generador de nombres de usuario automático
 * Formato: [letras_nombre][apellido]
 * Si existe, incrementa las letras del nombre: c → ca → car → carl → carlo → carlos
 */

/**
 * Genera un username único basado en nombre y apellido
 * 
 * @param PDO $db Conexión a la base de datos
 * @param string $nombres Nombres del funcionario
 * @param string $apellidos Apellidos del funcionario
 * @return string Username único generado
 */
function generarUsernameUnico($db, $nombres, $apellidos) {
    // Limpiar y normalizar
    $nombres = strtolower(trim($nombres));
    $apellidos = strtolower(trim($apellidos));
    
    // Remover acentos y caracteres especiales
    $nombres = quitarAcentos($nombres);
    $apellidos = quitarAcentos($apellidos);
    
    // Obtener solo el primer apellido
    $apellido_partes = explode(' ', $apellidos);
    $primer_apellido = preg_replace('/[^a-z]/', '', $apellido_partes[0]);
    
    // Obtener solo el primer nombre
    $nombre_partes = explode(' ', $nombres);
    $primer_nombre = preg_replace('/[^a-z]/', '', $nombre_partes[0]);
    
    if (empty($primer_nombre) || empty($primer_apellido)) {
        throw new Exception('Nombre o apellido inválido para generar username');
    }
    
    // Intentar con 1 letra, luego 2, luego 3, etc.
    $max_letras = min(strlen($primer_nombre), 10); // Máximo 10 letras del nombre
    
    for ($num_letras = 1; $num_letras <= $max_letras; $num_letras++) {
        $letras_nombre = substr($primer_nombre, 0, $num_letras);
        $username = $letras_nombre . $primer_apellido;
        
        // Verificar si el username está disponible
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        
        if (!$stmt->fetch()) {
            // Username disponible
            return $username;
        }
    }
    
    // Si llegamos aquí, incluso con el nombre completo ya existe
    // Agregar números al final
    $username_base = $primer_nombre . $primer_apellido;
    $counter = 1;
    
    while ($counter < 1000) { // Límite de seguridad
        $username = $username_base . $counter;
        
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        
        if (!$stmt->fetch()) {
            return $username;
        }
        
        $counter++;
    }
    
    throw new Exception('No se pudo generar un username único');
}

/**
 * Quita acentos y caracteres especiales de una cadena
 * 
 * @param string $texto Texto a limpiar
 * @return string Texto sin acentos
 */
function quitarAcentos($texto) {
    $acentos = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'ñ' => 'n', 'Ñ' => 'n',
        'ü' => 'u', 'Ü' => 'u'
    ];
    
    return strtr($texto, $acentos);
}
