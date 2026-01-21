<?php
/**
 * Modelo: Usuario
 * Gestión de usuarios y autenticación
 */

require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crear($funcionario_id, $username, $password, $email_recuperacion = null) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (funcionario_id, username, password_hash, email_recuperacion, estado)
            VALUES (?, ?, ?, ?, 'activo')
        ");
        
        return $stmt->execute([$funcionario_id, $username, $password_hash, $email_recuperacion]);
    }
    
    /**
     * Crear usuario pendiente (sin contraseña) para registro en dos etapas
     */
    public function crearPendiente($funcionario_id, $username, $email_recuperacion = null) {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (funcionario_id, username, password_hash, email_recuperacion, estado, registro_completado)
            VALUES (?, ?, NULL, ?, 'activo', FALSE)
        ");
        
        if ($stmt->execute([$funcionario_id, $username, $email_recuperacion])) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Obtener usuario por username
     */
    public function obtenerPorUsername($username) {
        $stmt = $this->db->prepare("
            SELECT 
                u.*,
                f.nombres,
                f.apellidos,
                f.cedula,
                f.foto AS foto,
                f.cargo_id,
                f.departamento_id,
                c.nombre_cargo,
                c.nivel_acceso,
                d.nombre AS departamento
            FROM usuarios u
            INNER JOIN funcionarios f ON u.funcionario_id = f.id
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener usuario por cédula del funcionario
     */
    public function obtenerPorCedula($cedula) {
        $stmt = $this->db->prepare("
            SELECT 
                u.*,
                f.nombres,
                f.apellidos,
                f.cedula,
                f.foto AS foto,
                f.cargo_id,
                f.departamento_id,
                c.nombre_cargo,
                c.nivel_acceso,
                d.nombre AS departamento
            FROM usuarios u
            INNER JOIN funcionarios f ON u.funcionario_id = f.id
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.cedula = ?
        ");
        $stmt->execute([$cedula]);
        return $stmt->fetch();
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuario_id, $nueva_password) {
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$password_hash, $usuario_id]);
    }
    
    /**
     * Completar registro con contraseña y preguntas de seguridad
     */
    public function completarRegistro($usuario_id, $password, $preguntas_respuestas) {
        try {
            $this->db->beginTransaction();
            
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Hash de las respuestas de seguridad
            $respuesta_1_hash = password_hash(strtolower(trim($preguntas_respuestas['respuesta_1'])), PASSWORD_DEFAULT);
            $respuesta_2_hash = password_hash(strtolower(trim($preguntas_respuestas['respuesta_2'])), PASSWORD_DEFAULT);
            $respuesta_3_hash = password_hash(strtolower(trim($preguntas_respuestas['respuesta_3'])), PASSWORD_DEFAULT);
            
            // Actualizar usuario
            $stmt = $this->db->prepare("
                UPDATE usuarios SET
                    password_hash = ?,
                    pregunta_seguridad_1 = ?,
                    respuesta_seguridad_1 = ?,
                    pregunta_seguridad_2 = ?,
                    respuesta_seguridad_2 = ?,
                    pregunta_seguridad_3 = ?,
                    respuesta_seguridad_3 = ?,
                    registro_completado = TRUE
                WHERE id = ?
            ");
            
            $resultado = $stmt->execute([
                $password_hash,
                $preguntas_respuestas['pregunta_1'],
                $respuesta_1_hash,
                $preguntas_respuestas['pregunta_2'],
                $respuesta_2_hash,
                $preguntas_respuestas['pregunta_3'],
                $respuesta_3_hash,
                $usuario_id
            ]);
            
            $this->db->commit();
            return $resultado;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al completar registro: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el usuario completó su registro
     */
    public function verificarRegistroCompletado($usuario_id) {
        $stmt = $this->db->prepare("SELECT registro_completado FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $resultado = $stmt->fetch();
        return $resultado ? (bool)$resultado['registro_completado'] : false;
    }
    
    /**
     * Generar token de recuperación
     */
    public function generarTokenRecuperacion($usuario_id) {
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET token_recuperacion = ?, token_expiracion = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$token, $expiracion, $usuario_id]);
        return $token;
    }
    
    /**
     * Verificar token de recuperación
     */
    public function verificarToken($token) {
        $stmt = $this->db->prepare("
            SELECT * FROM usuarios 
            WHERE token_recuperacion = ? 
            AND token_expiracion > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    
    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso($usuario_id) {
        $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        return $stmt->execute([$usuario_id]);
    }
    
    /**
     * Bloquear usuario
     */
    public function bloquear($usuario_id, $minutos = 15) {
        $bloqueado_hasta = date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"));
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET estado = 'bloqueado', bloqueado_hasta = ?
            WHERE id = ?
        ");
        return $stmt->execute([$bloqueado_hasta, $usuario_id]);
    }
    
    /**
     * Desbloquear usuario
     */
    public function desbloquear($usuario_id) {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET estado = 'activo', bloqueado_hasta = NULL, intentos_fallidos = 0
            WHERE id = ?
        ");
        return $stmt->execute([$usuario_id]);
    }
}
