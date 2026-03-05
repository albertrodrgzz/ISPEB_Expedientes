SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. TABLA: cargos
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_cargo` varchar(100) NOT NULL,
  `nivel_acceso` tinyint(4) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_cargo` (`nombre_cargo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cargos` (`id`, `nombre_cargo`, `nivel_acceso`, `descripcion`, `created_at`) VALUES
(1, 'Director de la Dirección', 1, 'Máxima autoridad', '2026-01-21 04:19:39'),
(2, 'Jefe de Dirección', 1, 'Segundo al mando', '2026-01-21 04:19:39'),
(3, 'Jefe de Departamento', 2, 'Responsable operativo', '2026-01-21 04:19:39'),
(4, 'Secretaria', 2, 'Personal administrativo', '2026-01-21 04:19:39'),
(5, 'Asistente', 3, 'Personal de apoyo', '2026-01-21 04:19:39'),
(6, 'Técnico', 3, 'Personal técnico', '2026-01-21 04:19:39'),
(7, 'Pasante de Pruebas', 3, '', '2026-01-27 09:44:50');

-- --------------------------------------------------------
-- 2. TABLA: departamentos
-- --------------------------------------------------------
DROP TABLE IF EXISTS `departamentos`;
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

INSERT INTO `departamentos` (`id`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Soporte Técnico', 'Soporte técnico interno y externo', 'activo'),
(2, 'Sistemas', 'Desarrollo y mantenimiento', 'activo'),
(3, 'Redes y Telecomunicaciones', 'Infraestructura de redes', 'activo'),
(4, 'Atención al Usuario', 'Servicio al usuario final', 'activo'),
(5, 'Reparaciones Electrónicas', 'Mantenimiento electrónico', 'activo'),
(6, 'Calidad de Software', 'Prueba', 'inactivo');

-- --------------------------------------------------------
-- 3. TABLA: funcionarios
-- --------------------------------------------------------
DROP TABLE IF EXISTS `funcionarios`;
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
  `nivel_educativo` enum('Primaria','Bachiller','TSU','Universitario','Postgrado','MaestrÃ­a','Doctorado') DEFAULT NULL,
  `titulo_obtenido` varchar(200) DEFAULT NULL,
  `fecha_ingreso_admin_publica` date DEFAULT NULL,
  `cantidad_hijos` tinyint(3) UNSIGNED DEFAULT 0,
  `cargo_id` int(10) UNSIGNED NOT NULL,
  `departamento_id` int(10) UNSIGNED NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `foto` varchar(255) DEFAULT 'default-avatar.png',
  `estado` enum('activo','vacaciones','reposo','inactivo') DEFAULT 'activo',
  `tiene_amonestaciones_graves` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertando tus 51 funcionarios (resumen de los primeros y últimos)
INSERT INTO `funcionarios` (`id`, `cedula`, `nombres`, `apellidos`, `fecha_nacimiento`, `genero`, `telefono`, `email`, `cargo_id`, `departamento_id`, `fecha_ingreso`, `estado`) VALUES
(1, 'V-12345678', 'Carlos', 'Rodríguez', '1980-03-15', 'M', '0412-1234567', 'crodriguez@ispeb.gob.ve', 1, 2, '2015-01-10', 'activo'),
(2, 'V-13456789', 'María', 'Núñez', '1982-07-22', 'F', '0424-2345678', 'mgonzalez@ispeb.gob.ve', 2, 2, '2016-03-15', 'activo'),
-- [Aquí el resto de tus funcionarios del 3 al 48]
(49, '31087083', 'Albert Nazareth', 'Rodriguez Sifontes', '2005-11-08', 'M', '04249399005', 'albertrodrigrez7@gmail.com', 6, 1, '2026-01-28', 'activo'),
(50, '8899490', 'Albert', 'Rodriguez', '2026-02-09', 'M', '04162895115', 'albertrodrigrez7@gmail.com', 6, 4, '2026-02-09', 'activo'),
(51, '12193581', 'Mayling', 'Sifontes', '1976-10-28', 'F', '04120869764', 'mayling@gmail.com', 4, 2, '2026-02-27', 'activo');

-- --------------------------------------------------------
-- 4. TABLA: usuarios
-- --------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') DEFAULT 'activo',
  `registro_completado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `funcionario_id` (`funcionario_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `funcionario_id`, `username`, `password_hash`, `estado`, `registro_completado`) VALUES
(1, 1, 'crodriguez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'activo', 1),
(53, 49, 'arodriguez', '$2y$10$QyNu3maaw4xihlQUfw22I.QzPr6GCrIEqE4u/C.r6VTJgOBDgooQm', 'activo', 1);

-- --------------------------------------------------------
-- 5. TABLA: auditoria (SIN CHECK JSON VALID)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(10) UNSIGNED DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertando tus registros de auditoría (resumen del inicio y fin)
INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGOUT', '::1', '2026-01-21 04:36:48'),
(390, 1, 'CREAR_FUNCIONARIO', '::1', '2026-03-01 18:13:42');

-- --------------------------------------------------------
-- 6. TABLA: historial_administrativo
-- --------------------------------------------------------
DROP TABLE IF EXISTS `historial_administrativo`;
CREATE TABLE `historial_administrativo` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('NOMBRAMIENTO','VACACION','REINCORPORACION','AMONESTACION','REMOCION','TRASLADO','DESPIDO','RENUNCIA') NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `detalles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `historial_administrativo` (`id`, `funcionario_id`, `tipo_evento`, `fecha_evento`, `registrado_por`) VALUES
(4, 49, 'NOMBRAMIENTO', '2026-02-09', 1),
(11, 38, 'VACACION', '2026-02-10', 1),
(24, 39, 'VACACION', '2026-03-05', 1);

-- --------------------------------------------------------
-- 7. VISTAS (LIMPIAS SIN DEFINER)
-- --------------------------------------------------------
DROP VIEW IF EXISTS `vista_funcionarios_completo`;
CREATE VIEW `vista_funcionarios_completo` AS 
SELECT f.id, f.cedula, CONCAT(f.nombres,' ',f.apellidos) AS nombre_completo, 
TIMESTAMPDIFF(YEAR,f.fecha_nacimiento,CURDATE()) AS edad, 
c.nombre_cargo, d.nombre AS departamento, f.estado 
FROM funcionarios f 
JOIN cargos c ON f.cargo_id = c.id 
JOIN departamentos d ON f.departamento_id = d.id;

DROP VIEW IF EXISTS `vista_usuarios_activos`;
CREATE VIEW `vista_usuarios_activos` AS 
SELECT u.id AS usuario_id, u.username, u.estado AS estado_usuario, 
f.cedula, CONCAT(f.nombres,' ',f.apellidos) AS nombre_completo 
FROM usuarios u 
JOIN funcionarios f ON u.funcionario_id = f.id 
WHERE u.estado = 'activo';

COMMIT;