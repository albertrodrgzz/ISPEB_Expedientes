<?php
/**
 * INDEX.PHP - Punto de Entrada Único
 * Lógica del controlador de Login
 */

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/seguridad.php';

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['usuario_id']) && isset($_SESSION['funcionario_id'])) {
    header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
    exit;
}

// Procesar login
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = limpiar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingrese usuario y contraseña';
    } else {
        try {
            $db = getDB();
            
            // Buscar usuario con sus datos completos
            $stmt = $db->prepare("
                SELECT 
                    u.id AS usuario_id, u.username, u.password_hash, u.estado AS estado_usuario,
                    u.intentos_fallidos, u.bloqueado_hasta, u.registro_completado,
                    f.id AS funcionario_id, f.nombres, f.apellidos, f.cedula, f.foto AS foto,
                    f.departamento_id, c.id AS cargo_id, c.nombre_cargo, c.nivel_acceso,
                    d.nombre AS departamento
                FROM usuarios u
                INNER JOIN funcionarios f ON u.funcionario_id = f.id
                INNER JOIN cargos c ON f.cargo_id = c.id
                INNER JOIN departamentos d ON f.departamento_id = d.id
                WHERE u.username = ? AND u.estado = 'activo' AND f.estado = 'activo'
            ");
            
            $stmt->execute([$username]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                $error = 'Usuario o contraseña incorrectos';
            } else {
                // Verificar si completó el registro
                if (!$usuario['registro_completado']) {
                    $_SESSION['registro_pendiente_cedula'] = $usuario['cedula'];
                    header('Location: ' . APP_URL . '/registro.php');
                    exit;
                }
                
                // Verificar que tenga contraseña
                if (is_null($usuario['password_hash']) || $usuario['password_hash'] === '') {
                    $error = 'Error en la configuración de su cuenta. Contacte al administrador.';
                } else {
                    // Verificar si está bloqueado
                    if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
                        $error = 'Usuario bloqueado temporalmente. Intente más tarde.';
                    } else {
                        // Verificar contraseña SOLO con hash bcrypt
                        $password_valida = password_verify($password, $usuario['password_hash']);
                        
                        if ($password_valida) {
                            // Login exitoso
                            $stmt = $db->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?");
                            $stmt->execute([$usuario['usuario_id']]);
                            
                            inicializarSesion($usuario);
                            registrarAuditoria('LOGIN');
                            
                            header('Location: ' . APP_URL . '/vistas/dashboard/index.php');
                            exit;
                        } else {
                            // Contraseña incorrecta
                            $intentos = $usuario['intentos_fallidos'] + 1;
                            if ($intentos >= 5) {
                                $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                                $stmt = $db->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
                                $stmt->execute([$intentos, $bloqueado_hasta, $usuario['usuario_id']]);
                                $error = 'Demasiados intentos fallidos. Usuario bloqueado por 15 minutos.';
                            } else {
                                $stmt = $db->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
                                $stmt->execute([$intentos, $usuario['usuario_id']]);
                                $error = 'Usuario o contraseña incorrectos';
                            }
                            registrarAuditoria('LOGIN_FALLIDO', 'usuarios', $usuario['usuario_id']);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema. Por favor, intente más tarde.';
        }
    }
}

// Mostrar mensajes de sesión
if (isset($_GET['error']) && $_GET['error'] === 'sesion_expirada') {
    $mensaje = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
}
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $mensaje = 'Ha cerrado sesión exitosamente.';
}

// ==========================================
// CARGAR LA VISTA HTML
// ==========================================
require_once __DIR__ . '/vistas/auth/login.php';