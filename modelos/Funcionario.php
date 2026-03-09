<?php
/**
 * Modelo: Funcionario
 * Gestión de funcionarios del sistema
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/username_generator.php';
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
        
        if (!empty($filtros['departamento_id'])) {
            $sql .= " AND f.departamento_id = ?";
            $params[] = $filtros['departamento_id'];
        }
        
        if (!empty($filtros['cargo_id'])) {
            $sql .= " AND f.cargo_id = ?";
            $params[] = $filtros['cargo_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND f.estado = ?";
            $params[] = $filtros['estado'];
        }
        
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
     * Verificar duplicados de campos únicos (cédula, teléfono, email)
     *
     * @param array  $datos      Arreglo con keys: cedula, telefono, email
     * @param int|null $excluir_id  ID del funcionario a excluir (para edición)
     * @return array  Arreglo de errores. Vacío si no hay conflictos.
     */
    public function verificarDuplicados(array $datos, $excluir_id = null): array {
        $errores = [];

        // — Cédula —
        $sql = "SELECT id, nombres, apellidos FROM funcionarios WHERE cedula = ?";
        $params = [$datos['cedula']];
        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        if ($fila) {
            $nombre = htmlspecialchars($fila['nombres'] . ' ' . $fila['apellidos']);
            $errores['cedula'] = "La cédula ya pertenece al funcionario: <strong>$nombre</strong>.";
        }

        // — Teléfono (solo si se proporcionó) —
        if (!empty($datos['telefono'])) {
            $sql = "SELECT id, nombres, apellidos FROM funcionarios WHERE REPLACE(REPLACE(telefono, '-', ''), ' ', '') = ? AND telefono != ''";
            $params = [$datos['telefono']];
            if ($excluir_id) {
                $sql .= " AND id != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $fila = $stmt->fetch();
            if ($fila) {
                $nombre = htmlspecialchars($fila['nombres'] . ' ' . $fila['apellidos']);
                $errores['telefono'] = "El teléfono ya está registrado para: <strong>$nombre</strong>.";
            }
        }

        // — Email (solo si se proporcionó) —
        if (!empty($datos['email'])) {
            $sql = "SELECT id, nombres, apellidos FROM funcionarios WHERE email = ? AND email != ''";
            $params = [$datos['email']];
            if ($excluir_id) {
                $sql .= " AND id != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $fila = $stmt->fetch();
            if ($fila) {
                $nombre = htmlspecialchars($fila['nombres'] . ' ' . $fila['apellidos']);
                $errores['email'] = "El correo electrónico ya está registrado para: <strong>$nombre</strong>.";
            }
        }

        return $errores;
    }
    
    /**
     * Crear nuevo funcionario
     */
    public function crear($datos) {
        $stmt = $this->db->prepare("
            INSERT INTO funcionarios 
            (cedula, nombres, apellidos, fecha_nacimiento, genero, telefono, email, direccion, 
             cargo_id, departamento_id, fecha_ingreso, foto, estado, nivel_educativo, titulo_obtenido)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $resultado = $stmt->execute([
            $datos['cedula'],
            $datos['nombres'],
            $datos['apellidos'],
            $datos['fecha_nacimiento'] ?? null,
            $datos['genero'] ?? null,
            $datos['telefono'] ?: null,
            $datos['email'] ?: null,
            $datos['direccion'] ?: null,
            $datos['cargo_id'],
            $datos['departamento_id'],
            $datos['fecha_ingreso'],
            $datos['foto'] ?? null,
            $datos['estado'] ?? 'activo',
            $datos['nivel_educativo'] ?? null,
            $datos['titulo_obtenido'] ?: null
        ]);
        
        if ($resultado) {
            $funcionario_id = $this->db->lastInsertId();
            // Crear usuario pendiente usando nombre + apellido (no cédula)
            $this->crearUsuarioPendiente($funcionario_id, $datos['nombres'], $datos['apellidos'], $datos['email'] ?? null);
            return $funcionario_id;
        }
        
        return false;
    }
    
    /**
     * Crear usuario pendiente para el funcionario
     * Username: 1ra letra del nombre + apellido (ej: mperez). Si existe, 2 letras, etc.
     */
    private function crearUsuarioPendiente($funcionario_id, $nombres, $apellidos, $email = null) {
        try {
            $username = generarUsernameUnico($this->db, $nombres, $apellidos);
            
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
                estado = ?,
                nivel_educativo = ?,
                titulo_obtenido = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $datos['cedula'],
            $datos['nombres'],
            $datos['apellidos'],
            $datos['fecha_nacimiento'] ?? null,
            $datos['genero'] ?? null,
            $datos['telefono'] ?: null,
            $datos['email'] ?: null,
            $datos['direccion'] ?: null,
            $datos['cargo_id'],
            $datos['departamento_id'],
            $datos['fecha_ingreso'],
            $datos['foto'] ?? null,
            $datos['estado'] ?? 'activo',
            $datos['nivel_educativo'] ?? null,
            $datos['titulo_obtenido'] ?: null,
            $id
        ]);
    }
    
    /**
     * Reactivar funcionario inactivo (reingreso)
     */
    public function reactivarFuncionario($id, $datosNuevos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE funcionarios SET
                    cargo_id = ?,
                    departamento_id = ?,
                    fecha_ingreso = ?,
                    estado = 'activo',
                    telefono = COALESCE(?, telefono),
                    email = COALESCE(?, email),
                    direccion = COALESCE(?, direccion)
                WHERE id = ?
            ");
            
            $resultado = $stmt->execute([
                $datosNuevos['cargo_id'],
                $datosNuevos['departamento_id'],
                $datosNuevos['fecha_ingreso'],
                $datosNuevos['telefono'] ?: null,
                $datosNuevos['email'] ?: null,
                $datosNuevos['direccion'] ?: null,
                $id
            ]);
            
            if ($resultado) {
                $this->registrarReingreso($id, $datosNuevos);
                $this->reactivarUsuario($id);
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error al reactivar funcionario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar evento de reingreso en historial administrativo
     */
    private function registrarReingreso($funcionario_id, $datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO historial_administrativo 
                (funcionario_id, tipo_evento, fecha_evento, detalles, registrado_por)
                VALUES (?, 'NOMBRAMIENTO', ?, ?, ?)
            ");
            
            $detalles = json_encode([
                'tipo'            => 'REINGRESO',
                'cargo_id'        => $datos['cargo_id'],
                'departamento_id' => $datos['departamento_id'],
                'observaciones'   => 'Reingreso automático al sistema'
            ]);
            
            $stmt->execute([
                $funcionario_id,
                $datos['fecha_ingreso'],
                $detalles,
                $_SESSION['usuario_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar reingreso: " . $e->getMessage());
        }
    }
    
    /**
     * Reactivar usuario asociado al funcionario
     */
    private function reactivarUsuario($funcionario_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET estado = 'activo', 
                    intentos_fallidos = 0,
                    bloqueado_hasta = NULL
                WHERE funcionario_id = ?
            ");
            $stmt->execute([$funcionario_id]);
        } catch (Exception $e) {
            error_log("Error al reactivar usuario: " . $e->getMessage());
        }
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
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado != 'inactivo'");
        $stats['total'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'activo'");
        $stats['activos'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM funcionarios WHERE estado = 'vacaciones'");
        $stats['vacaciones'] = $stmt->fetch()['total'];
        
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
