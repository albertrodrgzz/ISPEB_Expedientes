<?php
/**
 * AJAX: Validar cédula para registro
 * Verifica que el funcionario existe y tiene un usuario pendiente de completar registro
 * Si no tiene usuario, lo crea automáticamente
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/username_generator.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $cedula = trim($_POST['cedula'] ?? '');
    
    if (empty($cedula)) {
        throw new Exception('La cédula es requerida');
    }
    
    $db = getDB();
    
    // Buscar usuario con registro pendiente
    $stmt = $db->prepare("
        SELECT 
            u.id as usuario_id,
            u.username,
            u.registro_completado,
            f.id as funcionario_id,
            f.cedula,
            f.nombres,
            f.apellidos
        FROM usuarios u
        INNER JOIN funcionarios f ON u.funcionario_id = f.id
        WHERE f.cedula = ? AND u.registro_completado = 0
    ");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        // Verificar si el funcionario existe
        $stmt = $db->prepare("
            SELECT id, cedula, nombres, apellidos
            FROM funcionarios
            WHERE cedula = ?
        ");
        $stmt->execute([$cedula]);
        $funcionario = $stmt->fetch();
        
        if (!$funcionario) {
            throw new Exception('No se encontró un funcionario con esa cédula');
        }
        
        // Verificar si ya tiene usuario completado
        $stmt = $db->prepare("
            SELECT u.id
            FROM usuarios u
            INNER JOIN funcionarios f ON u.funcionario_id = f.id
            WHERE f.cedula = ? AND u.registro_completado = 1
        ");
        $stmt->execute([$cedula]);
        if ($stmt->fetch()) {
            throw new Exception('Este funcionario ya completó su registro. Por favor inicie sesión.');
        }
        
        // El funcionario existe pero no tiene usuario - Crear usuario automáticamente
        $username = generarUsernameUnico($db, $funcionario['nombres'], $funcionario['apellidos']);
        
        $stmt = $db->prepare("
            INSERT INTO usuarios (funcionario_id, username, password_hash, estado, registro_completado)
            VALUES (?, ?, NULL, 'activo', 0)
        ");
        $stmt->execute([$funcionario['id'], $username]);
        
        $nuevo_usuario_id = $db->lastInsertId();
        
        // Preparar datos del usuario recién creado
        $usuario = [
            'usuario_id' => $nuevo_usuario_id,
            'username' => $username,
            'cedula' => $funcionario['cedula'],
            'nombres' => $funcionario['nombres'],
            'apellidos' => $funcionario['apellidos'],
            'funcionario_id' => $funcionario['id']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'usuario_id' => $usuario['usuario_id'],
            'username' => $usuario['username'],
            'cedula' => $usuario['cedula'],
            'nombres' => $usuario['nombres'],
            'apellidos' => $usuario['apellidos'],
            'funcionario_id' => $usuario['funcionario_id']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
