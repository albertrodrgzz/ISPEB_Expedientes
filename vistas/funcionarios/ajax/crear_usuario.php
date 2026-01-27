<?php
/**
 * AJAX: Crear usuario para un funcionario
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json');

try {
    verificarSesion();
    
    // Solo nivel 1 y 2 pueden crear usuarios
    if (!verificarNivel(2)) {
        throw new Exception('No tiene permisos para crear usuarios');
    }
    
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    $funcionario_id = $data['funcionario_id'] ?? null;
    $nivel_acceso = $data['nivel_acceso'] ?? 4;
    $username_custom = $data['username'] ?? null;
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido');
    }
    
    // Solo nivel 1 puede asignar nivel 1
    if ($nivel_acceso == 1 && !verificarNivel(1)) {
        throw new Exception('Solo administradores pueden crear otros administradores');
    }
    
    $db = getDB();
    
    // Obtener datos del funcionario
    $stmt = $db->prepare("
        SELECT 
            f.id,
            f.cedula,
            f.nombres,
            f.apellidos,
            f.email,
            d.nombre as departamento
        FROM funcionarios f
        LEFT JOIN departamentos d ON f.departamento_id = d.id
        WHERE f.id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch();
    
    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado');
    }
    
    // Verificar que no tenga usuario ya
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE cedula = ?");
    $stmt->execute([$funcionario['cedula']]);
    if ($stmt->fetch()) {
        throw new Exception('Este funcionario ya tiene un usuario asignado');
    }
    
    // Generar username si no se proporcionó
    if (!$username_custom) {
        // Formato: primera letra nombre + apellido + últimos 4 dígitos cédula
        $primera_letra = strtolower(substr($funcionario['nombres'], 0, 1));
        $apellido = strtolower(preg_replace('/[^a-zA-Z]/', '', $funcionario['apellidos']));
        $apellido = explode(' ', $apellido)[0]; // Primer apellido
        $ultimos_digitos = substr(preg_replace('/[^0-9]/', '', $funcionario['cedula']), -4);
        
        $username_base = $primera_letra . $apellido . $ultimos_digitos;
        $username = $username_base;
        
        // Verificar unicidad
        $counter = 1;
        while (true) {
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) {
                break;
            }
            $counter++;
            $username = $username_base . '_' . $counter;
        }
    } else {
        $username = $username_custom;
        
        // Verificar que el username no exista
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('El nombre de usuario ya existe');
        }
    }
    
    // Generar contraseña temporal segura
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password_temporal = '';
    for ($i = 0; $i < 12; $i++) {
        $password_temporal .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    
    // Crear usuario
    $stmt = $db->prepare("
        INSERT INTO usuarios (
            username,
            password_hash,
            cedula,
            nivel_acceso,
            departamento,
            estado,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'activo', NOW())
    ");
    
    $password_hash = password_hash($password_temporal, PASSWORD_DEFAULT);
    
    $stmt->execute([
        $username,
        $password_hash,
        $funcionario['cedula'],
        $nivel_acceso,
        $funcionario['departamento']
    ]);
    
    $usuario_id = $db->lastInsertId();
    
    // Registrar en auditoría
    registrarAuditoria(
        'CREAR_USUARIO',
        'usuarios',
        $usuario_id,
        null,
        [
            'username' => $username,
            'funcionario_id' => $funcionario_id,
            'funcionario' => $funcionario['nombres'] . ' ' . $funcionario['apellidos'],
            'nivel_acceso' => $nivel_acceso
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'usuario' => [
            'id' => $usuario_id,
            'username' => $username,
            'password_temporal' => $password_temporal,
            'nivel_acceso' => $nivel_acceso,
            'funcionario' => $funcionario['nombres'] . ' ' . $funcionario['apellidos']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
