<?php
/**
 * Funciones de Seguridad y Middleware
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 */

// Cargar configuración de sesiones
require_once __DIR__ . '/sesiones.php';

/**
 * Verificar si el usuario está autenticado
 */
function verificarSesion() {
    // Verificar timeout de sesión
    verificarTimeoutSesion();
    
    // Verificar integridad de sesión
    verificarIntegridadSesion();
    
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['funcionario_id'])) {
        header('Location: ' . APP_URL . '/index.php?error=sesion_expirada');
        exit;
    }
    
    // Actualizar último acceso
    $_SESSION['ultimo_acceso'] = time();
}

/**
 * Verificar nivel de acceso requerido
 * @param int $nivel_requerido Nivel mínimo requerido (1, 2 o 3)
 * @return bool
 */
function verificarNivel($nivel_requerido) {
    verificarSesion();
    
    if (!isset($_SESSION['nivel_acceso'])) {
        return false;
    }
    
    // Nivel 1 tiene acceso a todo
    // Nivel 2 tiene acceso a nivel 2 y 3
    // Nivel 3 solo tiene acceso a nivel 3
    return $_SESSION['nivel_acceso'] <= $nivel_requerido;
}

/**
 * Verificar si el usuario puede editar un funcionario
 * Los Jefes de Departamento solo pueden editar personal de su departamento
 * @param int $funcionario_id ID del funcionario a editar
 * @return bool
 */
function verificarDepartamento($funcionario_id) {
    verificarSesion();
    
    // Nivel 1 puede editar a cualquiera
    if ($_SESSION['nivel_acceso'] == 1) {
        return true;
    }
    
    // Nivel 2: Verificar si es del mismo departamento
    if ($_SESSION['nivel_acceso'] == 2 && $_SESSION['cargo'] == 'Jefe de Departamento') {
        $db = getDB();
        $stmt = $db->prepare("SELECT departamento_id FROM funcionarios WHERE id = ?");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch();
        
        return $funcionario && $funcionario['departamento_id'] == $_SESSION['departamento_id'];
    }
    
    // Nivel 2 (Secretaria) puede editar a cualquiera
    if ($_SESSION['nivel_acceso'] == 2) {
        return true;
    }
    
    // Nivel 3 no puede editar
    return false;
}

/**
 * Verificar si el usuario puede eliminar registros
 * Solo Nivel 1 y 2 pueden eliminar
 * @return bool
 */
function puedeEliminar() {
    verificarSesion();
    return $_SESSION['nivel_acceso'] <= 2;
}

/**
 * Verificar si el usuario puede aprobar despidos
 * Solo Director y Jefe de Dirección
 * @return bool
 */
function puedeAprobarDespidos() {
    verificarSesion();
    return $_SESSION['nivel_acceso'] == 1;
}

/**
 * Registrar acción en auditoría
 * @param string $accion Acción realizada
 * @param string $tabla Tabla afectada
 * @param int $registro_id ID del registro afectado
 * @param array $datos_anteriores Datos antes del cambio
 * @param array $datos_nuevos Datos después del cambio
 */
function registrarAuditoria($accion, $tabla = null, $registro_id = null, $datos_anteriores = null, $datos_nuevos = null) {
    try {
        $db = getDB();
        
        // Establecer variables de sesión para los triggers
        if (isset($_SESSION['usuario_id'])) {
            $db->exec("SET @current_user_id = " . $_SESSION['usuario_id']);
        }
        $db->exec("SET @current_ip = '" . obtenerIP() . "'");
        
        $stmt = $db->prepare("
            INSERT INTO auditoria 
            (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $accion,
            $tabla,
            $registro_id,
            $datos_anteriores ? json_encode($datos_anteriores) : null,
            $datos_nuevos ? json_encode($datos_nuevos) : null,
            obtenerIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
    }
}



/**
 * Sanitizar entrada de datos
 * @param string $data Datos a sanitizar
 * @return string
 */
function limpiar($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generar token CSRF
 * @return string
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 * @param string $token Token a verificar
 * @return bool
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    if (isset($_SESSION['usuario_id'])) {
        registrarAuditoria('LOGOUT');
    }
    
    session_unset();
    session_destroy();
    
    // Limpiar cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    header('Location: ' . APP_URL . '/index.php?logout=success');
    exit;
}

/**
 * Formatear fecha en español
 * @param string $fecha Fecha en formato Y-m-d
 * @return string
 */
function formatearFecha($fecha) {
    if (!$fecha) return '-';
    
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('m', $timestamp)];
    $ano = date('Y', $timestamp);
    
    return "$dia de $mes de $ano";
}

/**
 * Formatear tamaño de archivo
 * @param int $bytes Tamaño en bytes
 * @return string
 */
function formatearTamano($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
