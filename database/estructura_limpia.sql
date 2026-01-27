-- ========================================================
-- SISTEMA DE GESTIÓN DE EXPEDIENTES DIGITALES - ISPEB
-- Script de Estructura Limpia (Versión 3.1)
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. CREACIÓN DE LA BASE DE DATOS
DROP DATABASE IF EXISTS `ispeb_expedientes`;
CREATE DATABASE IF NOT EXISTS `ispeb_expedientes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ispeb_expedientes`;

-- ========================================================
-- 2. TABLAS CATÁLOGO (DEPARTAMENTOS Y CARGOS)
-- ========================================================

CREATE TABLE `departamentos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cargos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_cargo` varchar(100) NOT NULL,
  `nivel_acceso` tinyint(4) NOT NULL COMMENT '1=Director, 2=Jefe/Coord, 3=Analista/Técnico',
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_cargo` (`nombre_cargo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 3. TABLA MAESTRA: FUNCIONARIOS
-- ========================================================

CREATE TABLE `funcionarios` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('M','F','Otro') DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `nivel_educativo` enum('Primaria','Bachiller','TSU','Universitario','Postgrado','Maestría','Doctorado') DEFAULT NULL,
  `titulo_obtenido` varchar(200) DEFAULT NULL,
  `fecha_ingreso_admin_publica` date DEFAULT NULL,
  `cantidad_hijos` tinyint(3) UNSIGNED DEFAULT 0,
  `cargo_id` int(10) UNSIGNED NOT NULL,
  `departamento_id` int(10) UNSIGNED NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `foto` varchar(255) DEFAULT 'default-avatar.png',
  `estado` enum('activo','vacaciones','reposo','inactivo','suspendido') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_cargo` (`cargo_id`),
  KEY `idx_departamento` (`departamento_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_busqueda` (`nombres`, `apellidos`, `cedula`),
  CONSTRAINT `fk_func_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`),
  CONSTRAINT `fk_func_depto` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 4. GESTIÓN DE ACCESO: USUARIOS Y SESIONES
-- ========================================================

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `email_recuperacion` varchar(150) DEFAULT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint(4) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') DEFAULT 'activo',
  `registro_completado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `funcionario_id` (`funcionario_id`),
  CONSTRAINT `fk_user_func` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sesiones` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ultimo_acceso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `datos_sesion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_sesion` (`usuario_id`),
  CONSTRAINT `fk_sesion_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 5. MÓDULOS DE GESTIÓN (ACTIVOS, HISTORIAL, FAMILIA)
-- ========================================================

CREATE TABLE `activos_tecnologicos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) UNSIGNED DEFAULT NULL,
  `tipo` enum('Laptop','PC','Radio','Tablet','Teléfono','Switch','Router','Otro') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serial` varchar(100) NOT NULL,
  `estado` enum('Asignado','Disponible','En Reparación','Dado de Baja') DEFAULT 'Disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial` (`serial`),
  KEY `idx_func_activo` (`funcionario_id`),
  KEY `idx_estado_activo` (`estado`),
  CONSTRAINT `fk_activo_func` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `historial_administrativo` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('NOMBRAMIENTO','VACACION','AMONESTACION','REMOCION','TRASLADO','DESPIDO','RENUNCIA') NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'Solo para vacaciones o reposos',
  `detalles` longtext DEFAULT NULL CHECK (json_valid(`detalles`)) COMMENT 'JSON con motivo, sanción, días, etc.',
  `ruta_archivo_pdf` varchar(255) DEFAULT NULL,
  `nombre_archivo_original` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_func_hist` (`funcionario_id`),
  KEY `idx_tipo_evento` (`tipo_evento`),
  CONSTRAINT `fk_hist_func` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_user` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cargas_familiares` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `parentesco` enum('Hijo/a','Cónyuge','Padre','Madre','Hermano/a','Otro') NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_func_carga` (`funcionario_id`),
  CONSTRAINT `fk_carga_func` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 6. SEGURIDAD Y AUDITORÍA
-- ========================================================

CREATE TABLE `auditoria` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(10) UNSIGNED DEFAULT NULL,
  `datos_anteriores` longtext DEFAULT NULL,
  `datos_nuevos` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`usuario_id`),
  KEY `idx_audit_accion` (`accion`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `preguntas_seguridad_catalogo` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pregunta` varchar(255) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 7. PROCEDIMIENTOS ALMACENADOS (LÓGICA CRÍTICA)
-- ========================================================

DELIMITER //

-- Procedimiento para validar si un funcionario puede ser retirado
-- Retorna BLOQUEADO si tiene activos asignados
CREATE PROCEDURE sp_validar_retiro(IN p_funcionario_id INT)
BEGIN
    DECLARE v_cantidad INT;
    DECLARE v_detalles TEXT;

    -- Contar activos asignados (Laptops, Radios, etc.)
    SELECT COUNT(*), GROUP_CONCAT(CONCAT(tipo, ' ', marca) SEPARATOR ', ')
    INTO v_cantidad, v_detalles
    FROM activos_tecnologicos
    WHERE funcionario_id = p_funcionario_id 
    AND estado = 'Asignado';

    -- Devolver resultado a la aplicación
    SELECT 
        CASE WHEN v_cantidad > 0 THEN 'BLOQUEADO' ELSE 'PERMITIDO' END as estado_retiro,
        v_cantidad as activos_pendientes,
        IFNULL(v_detalles, 'Ninguno') as lista_activos;
END //

DELIMITER ;

-- Insertar Preguntas de Seguridad Base
INSERT INTO `preguntas_seguridad_catalogo` (`pregunta`, `orden`) VALUES
('¿Cuál es el nombre de tu primera mascota?', 1),
('¿En qué ciudad naciste?', 2),
('¿Cuál es el apellido de soltera de tu madre?', 3),
('¿Cuál es el nombre de tu mejor amigo de la infancia?', 4),
('¿Cuál es tu comida favorita?', 5);

COMMIT;