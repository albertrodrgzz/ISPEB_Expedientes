<?php
/**
 * Modelo: Funcionario
 * Gestión de funcionarios del sistema
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Usuario.php';

class Funcionario {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Obtener todos los funcionarios
     */
    public function obtenerTodos($filtros = []) {
        $sql = "
            SELECT 
                f.id,
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
                f.nombres,
                f.apellidos,
                f.fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) AS edad,
                f.genero,
                f.telefono,
                f.email,
                f.direccion,
                f.cargo_id,
                c.nombre_cargo,
                c.nivel_acceso,
                f.departamento_id,
                d.nombre AS departamento,
                f.fecha_ingreso,
                TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad_anos,
                f.foto AS foto,
                f.estado,
                f.created_at
            FROM funcionarios f
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Filtro por departamento
        if (!empty($filtros['departamento_id'])) {
            $sql .= " AND f.departamento_id = ?";
            $params[] = $filtros['departamento_id'];
        }
        
        // Filtro por cargo
        if (!empty($filtros['cargo_id'])) {
            $sql .= " AND f.cargo_id = ?";
            $params[] = $filtros['cargo_id'];
        }
        
        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $sql .= " AND f.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        // Búsqueda por texto
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (f.nombres LIKE ? OR f.apellidos LIKE ? OR f.cedula LIKE ?)";
            $buscar = '%' . $filtros['buscar'] . '%';
            $params[] = $buscar;
            $params[] = $buscar;
            $params[] = $buscar;
        }
        
        $sql .= " ORDER BY f.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener funcionario por ID
     */
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("
            SELECT 
                f.*,
                TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) AS edad,
                TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad_anos,
                c.nombre_cargo,
                c.nivel_acceso,
                d.nombre AS departamento
            FROM funcionarios f
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener funcionario por cédula
     */
    public function obtenerPorCedula($cedula) {
        $stmt = $this->db->prepare("SELECT * FROM funcionarios WHERE cedula = ?");
        $stmt->execute([$cedula]);
        return $stmt->fetch();
    }
    
    /**
     * Crear nuevo funcionario
     */
    public function crear($datos) {
        $stmt = $this->db->prepare("
            INSERT INTO funcionarios 
            (cedula, nombres, apellidos, fecha_nacimiento, genero, telefono, email, direccion, 
             cargo_id, departamento_id, fecha_ingreso, foto, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $resultado = $stmt->execute([
            $datos['cedula'],
            $datos['nombres'],
            $datos['apellidos'],
            $datos['fecha_nacimiento'] ?? null,
            $datos['genero'] ?? null,
            $datos['telefono'] ?? null,
            $datos['email'] ?? null,
            $datos['direccion'] ?? null,
            $datos['cargo_id'],
            $datos['departamento_id'],
            $datos['fecha_ingreso'],
            $datos['foto'] ?? null,
            $datos['estado'] ?? 'activo'
        ]);
        
        if ($resultado) {
            $funcionario_id = $this->db->lastInsertId();
            
            // Crear usuario pendiente automáticamente
            $this->crearUsuarioPendiente($funcionario_id, $datos['cedula'], $datos['email']);
            
            return $funcionario_id;
        }
        
        return false;
    }
    
    /**
     * Crear usuario pendiente para el funcionario
     */
    private function crearUsuarioPendiente($funcionario_id, $cedula, $email = null) {
        try {
            // Generar username desde la cédula (ej: V-12345678 -> v12345678)
            $username = strtolower(str_replace(['-', ' '], '', $cedula));
            
            $modeloUsuario = new Usuario();
            $usuario_id = $modeloUsuario->crearPendiente($funcionario_id, $username, $email);
            
            if ($usuario_id) {
                error_log("Usuario pendiente creado: ID=$usuario_id, Username=$username");
                return $usuario_id;
            }
        } catch (Exception $e) {
            error_log("Error al crear usuario pendiente: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Actualizar funcionario
     */
    public function actualizar($id, $datos) {
        $stmt = $this->db->prepare("
            UPDATE funcionarios SET
                cedula = ?,
                nombres = ?,
                apellidos = ?,
                fecha_nacimiento = ?,
                genero = ?,
                telefono = ?,
                email = ?,
                direccion = ?,
                cargo_id = ?,
                departamento_id = ?,
                fecha_ingreso = ?,
                foto = ?,
                estado = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $datos['cedula'],
            $datos['nombres'],
            $datos['apellidos'],
            $datos['fecha_nacimiento'] ?? null,
            $datos['genero'] ?? null,
            $datos['telefono'] ?? null,
            $datos['email'] ?? null,
            $datos['direccion'] ?? null,
            $datos['cargo_id'],
            $datos['departamento_id'],
            $datos['fecha_ingreso'],
            $datos['foto'] ?? null,
            $datos['estado'] ?? 'activo',
            $id
        ]);
    }
    
    /**
     * Eliminar funcionario (soft delete)
     */
    public function eliminar($id) {
        $stmt = $this->db->prepare("UPDATE funcionarios SET estado = 'inactivo' WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Eliminar funcionario permanentemente
     */
    public function eliminarPermanente($id) {
        $stmt = $this->db->prepare("DELETE FROM funcionarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Obtener estadísticas
     */
    public function obtenerEstadisticas() {
        $stats = [];
        
        // Total
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado != 'inactivo'");
        $stats['total'] = $stmt->fetch()['total'];
        
        // Activos
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
        $stats['activos'] = $stmt->fetch()['total'];
        
        // De vacaciones
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
        $stats['vacaciones'] = $stmt->fetch()['total'];
        
        // Por departamento
        $stmt = $this->db->query("
            SELECT d.nombre, COUNT(f.id) as total
            FROM departamentos d
            LEFT JOIN funcionarios f ON d.id = f.departamento_id AND f.estado != 'inactivo'
            GROUP BY d.id, d.nombre
        ");
        $stats['por_departamento'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Subir foto de perfil
     */
    public function actualizarFoto($id, $nombreArchivo) {
        $stmt = $this->db->prepare("UPDATE funcionarios SET foto = ? WHERE id = ?");
        return $stmt->execute([$nombreArchivo, $id]);
    }
}
