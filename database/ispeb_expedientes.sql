-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-01-2026 a las 12:26:35
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ispeb_expedientes`
--
CREATE DATABASE IF NOT EXISTS `ispeb_expedientes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ispeb_expedientes`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activos_tecnologicos`
--

DROP TABLE IF EXISTS `activos_tecnologicos`;
CREATE TABLE `activos_tecnologicos` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED DEFAULT NULL,
  `tipo` enum('Laptop','PC','Radio','Tablet','TelÃÂ©fono','Switch','Router','Otro') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serial` varchar(100) NOT NULL,
  `estado` enum('Asignado','Disponible','En ReparaciÃÂ³n','Dado de Baja') DEFAULT 'Disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `activos_tecnologicos`
--

INSERT INTO `activos_tecnologicos` (`id`, `funcionario_id`, `tipo`, `marca`, `modelo`, `serial`, `estado`, `fecha_adquisicion`, `fecha_asignacion`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Laptop', 'HP', 'ProBook 450 G8', 'HP-LAP-001', 'Disponible', '2024-01-15', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(2, NULL, 'Laptop', 'Dell', 'Latitude 5420', 'DELL-LAP-002', 'Disponible', '2024-02-20', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(3, NULL, 'PC', 'HP', 'EliteDesk 800 G6', 'HP-PC-001', 'Disponible', '2023-11-10', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(4, NULL, 'PC', 'Dell', 'OptiPlex 7090', 'DELL-PC-002', 'Disponible', '2023-12-05', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(5, NULL, 'Radio', 'Motorola', 'DGP5550', 'MOT-RAD-001', 'Disponible', '2023-08-05', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(6, NULL, 'Radio', 'Motorola', 'DGP5550', 'MOT-RAD-002', 'Disponible', '2023-08-05', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(7, NULL, 'Tablet', 'Samsung', 'Galaxy Tab A8', 'SAM-TAB-001', 'Disponible', '2024-03-12', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(8, NULL, 'Switch', 'Cisco', 'Catalyst 2960', 'CISCO-SW-001', 'Disponible', '2023-06-10', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(9, NULL, 'Router', 'Cisco', 'ISR 4331', 'CISCO-RT-001', 'Disponible', '2023-07-15', NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(100) NOT NULL COMMENT 'Ej: LOGIN, CREAR_FUNCIONARIO, ELIMINAR_DOCUMENTO',
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(10) UNSIGNED DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `tabla_afectada`, `registro_id`, `datos_anteriores`, `datos_nuevos`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:36:48'),
(2, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:36:50'),
(3, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:36:53'),
(4, 4, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:37:08'),
(5, 4, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:37:22'),
(6, 11, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:37:33'),
(7, 11, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 04:37:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargas_familiares`
--

DROP TABLE IF EXISTS `cargas_familiares`;
CREATE TABLE `cargas_familiares` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `parentesco` enum('Hijo/a','CÃÂ³nyuge','Padre','Madre','Hermano/a','Otro') NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargos`
--

DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_cargo` varchar(100) NOT NULL,
  `nivel_acceso` tinyint(4) NOT NULL COMMENT '1=Admin Total, 2=Operativo, 3=Solo Lectura',
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cargos`
--

INSERT INTO `cargos` (`id`, `nombre_cargo`, `nivel_acceso`, `descripcion`, `created_at`) VALUES
(1, 'Director de la Dirección', 1, 'Máxima autoridad de la Dirección de Telemática - Acceso total al sistema', '2026-01-21 04:19:39'),
(2, 'Jefe de Dirección', 1, 'Segundo al mando - Acceso total al sistema', '2026-01-21 04:19:39'),
(3, 'Jefe de Departamento', 2, 'Responsable de un departamento específico - Acceso operativo limitado a su departamento', '2026-01-21 04:19:39'),
(4, 'Secretaria', 2, 'Personal administrativo - Acceso operativo para gestión de expedientes', '2026-01-21 04:19:39'),
(5, 'Asistente', 3, 'Personal de apoyo - Solo lectura y descarga de documentos', '2026-01-21 04:19:39'),
(6, 'Técnico', 3, 'Personal técnico - Solo lectura y descarga de documentos', '2026-01-21 04:19:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

DROP TABLE IF EXISTS `departamentos`;
CREATE TABLE `departamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`, `descripcion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Soporte Técnico', 'Departamento encargado del soporte técnico a usuarios internos y externos', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(2, 'Sistemas', 'Departamento de desarrollo y mantenimiento de sistemas informáticos', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(3, 'Redes y Telecomunicaciones', 'Departamento de infraestructura de redes y comunicaciones', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(4, 'Atención al Usuario', 'Departamento de atención y servicio al usuario final', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(5, 'Reparaciones Electrónicas', 'Departamento de reparación y mantenimiento de equipos electrónicos', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `funcionarios`
--

DROP TABLE IF EXISTS `funcionarios`;
CREATE TABLE `funcionarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('M','F','Otro') DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `nivel_educativo` enum('Primaria','Bachiller','TSU','Universitario','Postgrado','MaestrÃÂ­a','Doctorado') DEFAULT NULL,
  `titulo_obtenido` varchar(200) DEFAULT NULL,
  `fecha_ingreso_admin_publica` date DEFAULT NULL,
  `cantidad_hijos` tinyint(3) UNSIGNED DEFAULT 0,
  `cargo_id` int(10) UNSIGNED NOT NULL,
  `departamento_id` int(10) UNSIGNED NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `foto` varchar(255) DEFAULT 'default-avatar.png',
  `estado` enum('activo','vacaciones','reposo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `funcionarios`
--

INSERT INTO `funcionarios` (`id`, `cedula`, `nombres`, `apellidos`, `fecha_nacimiento`, `genero`, `telefono`, `email`, `direccion`, `nivel_educativo`, `titulo_obtenido`, `fecha_ingreso_admin_publica`, `cantidad_hijos`, `cargo_id`, `departamento_id`, `fecha_ingreso`, `foto`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'V-12345678', 'Carlos', 'Rodríguez', '1980-03-15', 'M', '0412-1234567', 'crodriguez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', NULL, 0, 1, 2, '2015-01-10', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:31:06'),
(2, 'V-13456789', 'María', 'Núñez', '1982-07-22', 'F', '0424-2345678', 'mgonzalez@ispeb.gob.ve', NULL, 'Postgrado', 'Especialista en Redes', NULL, 0, 2, 2, '2016-03-15', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:31:06'),
(3, 'V-14567890', 'Luis', 'Núñez', '1978-11-08', 'M', '0414-3456789', 'lmartinez@ispeb.gob.ve', NULL, '', 'Magíster en Gestión Tecnológica', NULL, 0, 1, 1, '2014-06-20', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(4, 'V-15678901', 'Ana', 'Pérez', '1985-05-12', 'F', '0426-4567890', 'aperez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniera en Electrónica', NULL, 0, 3, 1, '2017-02-14', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(5, 'V-16789012', 'José', 'Núñez', '1983-09-25', 'M', '0412-5678901', 'jhernandez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Telecomunicaciones', NULL, 0, 3, 3, '2016-08-10', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:31:06'),
(6, 'V-17890123', 'Carmen', 'López', '1986-01-30', 'F', '0424-6789012', 'clopez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, 0, 3, 4, '2018-04-05', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(7, 'V-18901234', 'Pedro', 'García', '1984-12-18', 'M', '0414-7890123', 'pgarcia@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero Electrónico', NULL, 0, 3, 5, '2017-11-22', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(8, 'V-19012345', 'Laura', 'Ramírez', '1990-04-08', 'F', '0426-8901234', 'lramirez@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, 0, 4, 2, '2019-01-15', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:31:06'),
(9, 'V-20123456', 'Sofía', 'Torres', '1992-08-14', 'F', '0412-9012345', 'storres@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', NULL, 0, 4, 1, '2020-03-10', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(10, 'V-21234567', 'Isabella', 'Flores', '1991-06-20', 'F', '0424-0123456', 'iflores@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', NULL, 0, 4, 4, '2019-09-05', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(11, 'V-22345678', 'Miguel', 'Núñez', '1988-02-11', 'M', '0414-1234567', 'msanchez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Computación', NULL, 0, 6, 1, '2018-05-20', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(12, 'V-23456789', 'Roberto', 'Díaz', '1989-07-16', 'M', '0426-2345678', 'rdiaz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', NULL, 0, 6, 1, '2019-02-12', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(13, 'V-24567890', 'Fernando', 'Morales', '1987-11-22', 'M', '0412-3456789', 'fmorales@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Computación', NULL, 0, 6, 1, '2017-08-18', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(14, 'V-25678901', 'Andrés', 'Castro', '1990-03-28', 'M', '0424-4567890', 'acastro@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Sistemas', NULL, 0, 6, 1, '2020-01-22', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(15, 'V-26789012', 'Daniel', 'Ruiz', '1991-09-05', 'M', '0414-5678901', 'druiz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Redes', NULL, 0, 6, 1, '2020-06-15', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(16, 'V-27890123', 'Gabriel', 'Ortiz', '1986-12-30', 'M', '0426-6789012', 'gortiz@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', NULL, 0, 6, 1, '2018-11-08', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(17, 'V-28901234', 'Ricardo', 'Vargas', '1989-05-17', 'M', '0412-7890123', 'rvargas@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, 0, 6, 1, '2019-07-25', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(18, 'V-29012345', 'Javier', 'Mendoza', '1988-08-24', 'M', '0424-8901234', 'jmendoza@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', NULL, 0, 6, 2, '2018-10-12', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(19, 'V-30123456', 'Alberto', 'Silva', '1990-01-19', 'M', '0414-9012345', 'asilva@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Programación', NULL, 0, 6, 2, '2020-02-28', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(20, 'V-31234567', 'Sergio', 'Rojas', '1987-06-13', 'M', '0426-0123456', 'srojas@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Computación', NULL, 0, 6, 2, '2017-12-05', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(21, 'V-32345678', 'Héctor', 'Navarro', '1991-10-07', 'M', '0412-1234568', 'hnavarro@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Desarrollo Web', NULL, 0, 6, 2, '2021-03-18', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(22, 'V-33456789', 'Raúl', 'Medina', '1989-04-21', 'M', '0424-2345679', 'rmedina@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Software', NULL, 0, 6, 2, '2019-08-22', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:31:06'),
(23, 'V-34567890', 'Gustavo', 'Reyes', '1986-11-15', 'M', '0414-3456790', 'greyes@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Telecomunicaciones', NULL, 0, 6, 3, '2017-05-30', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(24, 'V-35678901', 'Arturo', 'Guerrero', '1988-07-09', 'M', '0426-4567891', 'aguerrero@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Redes', NULL, 0, 6, 3, '2018-09-14', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(25, 'V-36789012', 'Eduardo', 'Núñez', '1990-02-26', 'M', '0412-5678902', 'ejimenez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Redes', NULL, 0, 6, 3, '2020-04-08', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:31:06'),
(26, 'V-37890123', 'Francisco', 'Romero', '1987-09-12', 'M', '0424-6789013', 'fromero@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Telecomunicaciones', NULL, 0, 6, 3, '2018-01-25', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(27, 'V-38901234', 'Marcos', 'Aguilar', '1991-05-03', 'M', '0414-7890124', 'maguilar@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Comunicaciones', NULL, 0, 6, 3, '2021-02-16', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(28, 'V-39012345', 'Víctor', 'Cruz', '1989-12-28', 'M', '0426-8901235', 'vcruz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Fibra Óptica', NULL, 0, 6, 3, '2019-11-07', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(29, 'V-40123456', 'Valentina', 'Moreno', '1993-03-14', 'F', '0412-9012346', 'vmoreno@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Atención al Cliente', NULL, 0, 5, 4, '2021-05-10', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(30, 'V-41234567', 'Camila', 'Guzmán', '1994-08-19', 'F', '0424-0123457', 'cguzman@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, 0, 5, 4, '2022-01-20', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(31, 'V-42345678', 'Daniela', 'Vega', '1992-01-25', 'F', '0414-1234569', 'dvega@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', NULL, 0, 5, 4, '2020-09-15', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(32, 'V-43456789', 'Gabriela', 'Paredes', '1995-06-11', 'F', '0426-2345670', 'gparedes@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', NULL, 0, 5, 4, '2022-07-05', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(33, 'V-44567890', 'Natalia', 'Campos', '1993-11-07', 'F', '0412-3456780', 'ncampos@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, 0, 5, 4, '2021-10-18', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(34, 'V-45678901', 'Andrea', 'Ramos', '1994-04-22', 'F', '0424-4567891', 'aramos@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, 0, 5, 4, '2022-03-12', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(35, 'V-46789012', 'Óscar', 'Fuentes', '1987-10-16', 'M', '0414-5678903', 'ofuentes@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', NULL, 0, 6, 5, '2018-06-28', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(36, 'V-47890123', 'Iván', 'Salazar', '1988-05-29', 'M', '0426-6789014', 'isalazar@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero Electrónico', NULL, 0, 6, 5, '2019-04-15', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(37, 'V-48901234', 'Emilio', 'Cortés', '1990-12-04', 'M', '0412-7890125', 'ecortes@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Reparación', NULL, 0, 6, 5, '2020-08-20', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(38, 'V-49012345', 'Adrián', 'Peña', '1986-07-18', 'M', '0424-8901236', 'apena@ispeb.gob.ve', NULL, 'TSU', 'Ingeniero Electrónico', NULL, 0, 6, 5, '2017-10-05', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(39, 'V-50123456', 'Mauricio', 'Ibarra', '1989-02-23', 'M', '0414-9012347', 'mibarra@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Electrónica', NULL, 0, 6, 5, '2019-12-11', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(40, 'V-51234567', 'Rodrigo', 'Molina', '1991-09-08', 'M', '0426-0123458', 'rmolina@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Mantenimiento', NULL, 0, 6, 5, '2021-06-22', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(41, 'V-52345678', 'Esteban', 'Carrillo', '1988-04-13', 'M', '0412-1234570', 'ecarrillo@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', NULL, 0, 6, 5, '2018-12-18', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(42, 'V-53456789', 'Paola', 'Núñez', '1994-11-26', 'F', '0424-2345671', 'pnunez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', NULL, 0, 5, 2, '2022-04-14', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(43, 'V-54567890', 'Lucía', 'Espinoza', '1993-06-30', 'F', '0414-3456781', 'lespinoza@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, 0, 5, 1, '2021-08-25', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(44, 'V-55678901', 'María', 'Núñez', '1995-01-15', 'F', '0426-4567892', 'mbenitez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, 0, 5, 3, '2022-09-08', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:34:29'),
(45, 'V-56789012', 'Alejandra', 'Soto', '1992-08-21', 'F', '0412-5678904', 'asoto@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', NULL, 0, 5, 5, '2020-11-30', 'default-avatar.png', 'activo', '2026-01-21 04:19:39', '2026-01-21 04:19:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_administrativo`
--

DROP TABLE IF EXISTS `historial_administrativo`;
CREATE TABLE `historial_administrativo` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('NOMBRAMIENTO','VACACION','AMONESTACION','REMOCION','TRASLADO','DESPIDO','RENUNCIA') NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'Para vacaciones: fecha de finalizaciÃÂ³n',
  `detalles` text DEFAULT NULL COMMENT 'JSON con datos especÃÂ­ficos: motivo, tipo_falta, sancion, etc.',
  `ruta_archivo_pdf` varchar(255) DEFAULT NULL,
  `nombre_archivo_original` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `cargo_anterior_id` int(10) UNSIGNED DEFAULT NULL,
  `cargo_nuevo_id` int(10) UNSIGNED DEFAULT NULL,
  `departamento_anterior_id` int(10) UNSIGNED DEFAULT NULL,
  `departamento_nuevo_id` int(10) UNSIGNED DEFAULT NULL,
  `tipo_movimiento` enum('ascenso','traslado','descenso','rotacion') NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `documento_soporte` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_seguridad_catalogo`
--

DROP TABLE IF EXISTS `preguntas_seguridad_catalogo`;
CREATE TABLE `preguntas_seguridad_catalogo` (
  `id` int(10) UNSIGNED NOT NULL,
  `pregunta` varchar(255) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `preguntas_seguridad_catalogo`
--

INSERT INTO `preguntas_seguridad_catalogo` (`id`, `pregunta`, `activa`, `orden`, `created_at`) VALUES
(1, '¿Cuál es el nombre de tu primera mascota?', 1, 1, '2026-01-21 04:19:39'),
(2, '¿En qué ciudad naciste?', 1, 2, '2026-01-21 04:19:39'),
(3, '¿Cuál es el apellido de soltera de tu madre?', 1, 3, '2026-01-21 04:19:39'),
(4, '¿Cuál es el nombre de tu mejor amigo de la infancia?', 1, 4, '2026-01-21 04:19:39'),
(5, '¿Cuál fue el nombre de tu primera escuela?', 1, 5, '2026-01-21 04:19:39'),
(6, '¿Cuál es tu comida favorita?', 1, 6, '2026-01-21 04:19:39'),
(7, '¿En qué año conociste a tu pareja?', 1, 7, '2026-01-21 04:19:39'),
(8, '¿Cuál es el nombre de tu libro favorito?', 1, 8, '2026-01-21 04:19:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ultimo_acceso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `datos_sesion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'NULL para usuarios pendientes de registro',
  `email_recuperacion` varchar(150) DEFAULT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint(4) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') DEFAULT 'activo',
  `registro_completado` tinyint(1) DEFAULT 0 COMMENT 'Indica si el usuario completÃÂ³ su registro',
  `pregunta_seguridad_1` varchar(255) DEFAULT NULL,
  `respuesta_seguridad_1` varchar(255) DEFAULT NULL,
  `pregunta_seguridad_2` varchar(255) DEFAULT NULL,
  `respuesta_seguridad_2` varchar(255) DEFAULT NULL,
  `pregunta_seguridad_3` varchar(255) DEFAULT NULL,
  `respuesta_seguridad_3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `funcionario_id`, `username`, `password_hash`, `email_recuperacion`, `token_recuperacion`, `token_expiracion`, `ultimo_acceso`, `intentos_fallidos`, `bloqueado_hasta`, `estado`, `registro_completado`, `pregunta_seguridad_1`, `respuesta_seguridad_1`, `pregunta_seguridad_2`, `respuesta_seguridad_2`, `pregunta_seguridad_3`, `respuesta_seguridad_3`, `created_at`, `updated_at`) VALUES
(1, 1, 'crodriguez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'crodriguez@ispeb.gob.ve', NULL, NULL, '2026-01-21 00:36:50', 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:36:50'),
(2, 2, 'mgonzalez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'mgonzalez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(3, 3, 'lmartinez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'lmartinez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(4, 4, 'aperez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'aperez@ispeb.gob.ve', NULL, NULL, '2026-01-21 00:37:08', 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:37:08'),
(5, 5, 'jhernandez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'jhernandez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(6, 6, 'clopez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'clopez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(7, 7, 'pgarcia', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'pgarcia@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(8, 8, 'lramirez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'lramirez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(9, 9, 'storres', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'storres@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(10, 10, 'iflores', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'iflores@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(11, 11, 'msanchez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'msanchez@ispeb.gob.ve', NULL, NULL, '2026-01-21 00:37:33', 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:37:33'),
(12, 12, 'rdiaz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rdiaz@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(13, 13, 'fmorales', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'fmorales@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(14, 14, 'acastro', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'acastro@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(15, 15, 'druiz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'druiz@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(16, 16, 'gortiz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'gortiz@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(17, 17, 'rvargas', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rvargas@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(18, 18, 'jmendoza', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'jmendoza@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(19, 19, 'asilva', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'asilva@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(20, 20, 'srojas', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'srojas@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(21, 21, 'hnavarro', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'hnavarro@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(22, 22, 'rmedina', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rmedina@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(23, 23, 'greyes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'greyes@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(24, 24, 'aguerrero', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'aguerrero@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(25, 25, 'ejimenez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ejimenez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(26, 26, 'fromero', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'fromero@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(27, 27, 'maguilar', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'maguilar@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(28, 28, 'vcruz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'vcruz@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(29, 29, 'vmoreno', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'vmoreno@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(30, 30, 'cguzman', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'cguzman@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(31, 31, 'dvega', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'dvega@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(32, 32, 'gparedes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'gparedes@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(33, 33, 'ncampos', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ncampos@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(34, 34, 'aramos', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'aramos@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(35, 35, 'ofuentes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ofuentes@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(36, 36, 'isalazar', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'isalazar@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(37, 37, 'ecortes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ecortes@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(38, 38, 'apena', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'apena@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(39, 39, 'mibarra', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'mibarra@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(40, 40, 'rmolina', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rmolina@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(41, 41, 'ecarrillo', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ecarrillo@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(42, 42, 'pnunez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'pnunez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(43, 43, 'lespinoza', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'lespinoza@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(44, 44, 'mbenitez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'mbenitez@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39'),
(45, 45, 'asoto', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'asoto@ispeb.gob.ve', NULL, NULL, NULL, 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 04:19:39', '2026-01-21 04:19:39');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_funcionarios_completo`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_funcionarios_completo`;
CREATE TABLE `vista_funcionarios_completo` (
`id` int(10) unsigned
,`cedula` varchar(20)
,`nombre_completo` varchar(201)
,`nombres` varchar(100)
,`apellidos` varchar(100)
,`fecha_nacimiento` date
,`edad` bigint(21)
,`genero` enum('M','F','Otro')
,`telefono` varchar(20)
,`email` varchar(150)
,`direccion` text
,`nivel_educativo` enum('Primaria','Bachiller','TSU','Universitario','Postgrado','MaestrÃÂ­a','Doctorado')
,`titulo_obtenido` varchar(200)
,`cantidad_hijos` tinyint(3) unsigned
,`nombre_cargo` varchar(100)
,`nivel_acceso` tinyint(4)
,`departamento` varchar(150)
,`fecha_ingreso` date
,`antiguedad_anos` bigint(21)
,`foto` varchar(255)
,`estado` enum('activo','vacaciones','reposo','inactivo')
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_usuarios_activos`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_usuarios_activos`;
CREATE TABLE `vista_usuarios_activos` (
`usuario_id` int(10) unsigned
,`username` varchar(50)
,`estado_usuario` enum('activo','inactivo','bloqueado')
,`ultimo_acceso` datetime
,`funcionario_id` int(10) unsigned
,`cedula` varchar(20)
,`nombre_completo` varchar(201)
,`nombre_cargo` varchar(100)
,`nivel_acceso` tinyint(4)
,`departamento` varchar(150)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_funcionarios_completo`
--
DROP TABLE IF EXISTS `vista_funcionarios_completo`;

DROP VIEW IF EXISTS `vista_funcionarios_completo`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_funcionarios_completo`  AS SELECT `f`.`id` AS `id`, `f`.`cedula` AS `cedula`, concat(`f`.`nombres`,' ',`f`.`apellidos`) AS `nombre_completo`, `f`.`nombres` AS `nombres`, `f`.`apellidos` AS `apellidos`, `f`.`fecha_nacimiento` AS `fecha_nacimiento`, timestampdiff(YEAR,`f`.`fecha_nacimiento`,curdate()) AS `edad`, `f`.`genero` AS `genero`, `f`.`telefono` AS `telefono`, `f`.`email` AS `email`, `f`.`direccion` AS `direccion`, `f`.`nivel_educativo` AS `nivel_educativo`, `f`.`titulo_obtenido` AS `titulo_obtenido`, `f`.`cantidad_hijos` AS `cantidad_hijos`, `c`.`nombre_cargo` AS `nombre_cargo`, `c`.`nivel_acceso` AS `nivel_acceso`, `d`.`nombre` AS `departamento`, `f`.`fecha_ingreso` AS `fecha_ingreso`, timestampdiff(YEAR,`f`.`fecha_ingreso`,curdate()) AS `antiguedad_anos`, `f`.`foto` AS `foto`, `f`.`estado` AS `estado`, `f`.`created_at` AS `created_at`, `f`.`updated_at` AS `updated_at` FROM ((`funcionarios` `f` join `cargos` `c` on(`f`.`cargo_id` = `c`.`id`)) join `departamentos` `d` on(`f`.`departamento_id` = `d`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_usuarios_activos`
--
DROP TABLE IF EXISTS `vista_usuarios_activos`;

DROP VIEW IF EXISTS `vista_usuarios_activos`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_usuarios_activos`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`username` AS `username`, `u`.`estado` AS `estado_usuario`, `u`.`ultimo_acceso` AS `ultimo_acceso`, `f`.`id` AS `funcionario_id`, `f`.`cedula` AS `cedula`, concat(`f`.`nombres`,' ',`f`.`apellidos`) AS `nombre_completo`, `c`.`nombre_cargo` AS `nombre_cargo`, `c`.`nivel_acceso` AS `nivel_acceso`, `d`.`nombre` AS `departamento` FROM (((`usuarios` `u` join `funcionarios` `f` on(`u`.`funcionario_id` = `f`.`id`)) join `cargos` `c` on(`f`.`cargo_id` = `c`.`id`)) join `departamentos` `d` on(`f`.`departamento_id` = `d`.`id`)) WHERE `u`.`estado` = 'activo' ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activos_tecnologicos`
--
ALTER TABLE `activos_tecnologicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial` (`serial`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_serial` (`serial`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_fecha` (`created_at`);

--
-- Indices de la tabla `cargas_familiares`
--
ALTER TABLE `cargas_familiares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_parentesco` (`parentesco`);

--
-- Indices de la tabla `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_cargo` (`nombre_cargo`),
  ADD KEY `idx_nivel_acceso` (`nivel_acceso`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `idx_cedula` (`cedula`),
  ADD KEY `idx_cargo` (`cargo_id`),
  ADD KEY `idx_departamento` (`departamento_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_nombres` (`nombres`,`apellidos`);

--
-- Indices de la tabla `historial_administrativo`
--
ALTER TABLE `historial_administrativo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_tipo_evento` (`tipo_evento`),
  ADD KEY `idx_fecha_evento` (`fecha_evento`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cargo_anterior_id` (`cargo_anterior_id`),
  ADD KEY `cargo_nuevo_id` (`cargo_nuevo_id`),
  ADD KEY `departamento_anterior_id` (`departamento_anterior_id`),
  ADD KEY `departamento_nuevo_id` (`departamento_nuevo_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_fecha` (`fecha_movimiento`);

--
-- Indices de la tabla `preguntas_seguridad_catalogo`
--
ALTER TABLE `preguntas_seguridad_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pregunta` (`pregunta`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_ultimo_acceso` (`ultimo_acceso`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_id` (`funcionario_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_registro_completado` (`registro_completado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activos_tecnologicos`
--
ALTER TABLE `activos_tecnologicos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cargas_familiares`
--
ALTER TABLE `cargas_familiares`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `historial_administrativo`
--
ALTER TABLE `historial_administrativo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `preguntas_seguridad_catalogo`
--
ALTER TABLE `preguntas_seguridad_catalogo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `activos_tecnologicos`
--
ALTER TABLE `activos_tecnologicos`
  ADD CONSTRAINT `activos_tecnologicos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cargas_familiares`
--
ALTER TABLE `cargas_familiares`
  ADD CONSTRAINT `cargas_familiares_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD CONSTRAINT `funcionarios_ibfk_1` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`),
  ADD CONSTRAINT `funcionarios_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`);

--
-- Filtros para la tabla `historial_administrativo`
--
ALTER TABLE `historial_administrativo`
  ADD CONSTRAINT `historial_administrativo_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_administrativo_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`cargo_anterior_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_3` FOREIGN KEY (`cargo_nuevo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_4` FOREIGN KEY (`departamento_anterior_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_5` FOREIGN KEY (`departamento_nuevo_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_6` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
