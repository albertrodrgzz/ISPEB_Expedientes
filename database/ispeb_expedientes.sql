-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-03-2026 a las 12:52:06
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
-- Estructura de tabla para la tabla `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY,
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
(1, 1, 'INSTALACION_BD', NULL, NULL, NULL, NULL, '127.0.0.1', 'SIGED SQL Installer v4.0', '2026-03-07 22:12:04'),
(2, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:12:32'),
(3, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-07 22:31:52'),
(4, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:35:55'),
(5, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-07 22:51:35'),
(6, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-07 22:52:07'),
(7, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-07 22:53:00'),
(8, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.248666', '2026-03-07 22:53:14'),
(9, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-07 22:53:43'),
(10, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-07 22:54:09'),
(11, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:54:25'),
(12, 1, 'GENERAR_CONSTANCIA', 'funcionarios', 1, NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Albert Rodriguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:55:34'),
(13, 1, 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', 1, '{\"id\":1,\"cedula\":\"V-12345678\",\"nombres\":\"Albert\",\"apellidos\":\"Rodriguez\",\"fecha_nacimiento\":\"1982-05-14\",\"genero\":\"M\",\"telefono\":\"0414-5551001\",\"email\":\"arodriguez@ispeb.gob.ve\",\"direccion\":null,\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":1,\"departamento_id\":1,\"fecha_ingreso\":\"2010-03-01\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"created_at\":\"2026-03-07 18:12:04\",\"updated_at\":\"2026-03-07 18:12:04\",\"edad\":43,\"antiguedad_anos\":16,\"nombre_cargo\":\"Jefe de Direcci\\u00f3n\",\"nivel_acceso\":1,\"departamento\":\"Direcci\\u00f3n de Telem\\u00e1tica\"}', '{\"cedula\":\"V-31087083\",\"nombres\":\"Albert Nazareth\",\"apellidos\":\"Rodriguez Sifontes\",\"fecha_nacimiento\":\"2005-11-08\",\"genero\":\"M\",\"telefono\":\"0424-9399005\",\"email\":\"albertro023@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"1\",\"departamento_id\":\"1\",\"fecha_ingreso\":\"2024-07-11\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:57:27'),
(14, 1, 'GENERAR_CONSTANCIA', 'funcionarios', 1, NULL, '{\"funcionario\":\"ALBERT NAZARETH RODRIGUEZ SIFONTES\",\"generado_por\":\"Albert Rodriguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:57:47'),
(15, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:58:28'),
(16, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 22:58:35'),
(17, 2, 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', 2, '{\"id\":2,\"cedula\":\"V-23456789\",\"nombres\":\"Ruben\",\"apellidos\":\"Rodriguez\",\"fecha_nacimiento\":\"1988-09-22\",\"genero\":\"M\",\"telefono\":\"0424-5552002\",\"email\":\"rrodriguez@ispeb.gob.ve\",\"direccion\":null,\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":2,\"departamento_id\":2,\"fecha_ingreso\":\"2015-06-15\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"created_at\":\"2026-03-07 18:12:04\",\"updated_at\":\"2026-03-07 18:12:04\",\"edad\":37,\"antiguedad_anos\":10,\"nombre_cargo\":\"Jefe de Departamento\",\"nivel_acceso\":2,\"departamento\":\"Sistemas\"}', '{\"cedula\":\"V-8899490\",\"nombres\":\"Ruben Jos\\u00e9\",\"apellidos\":\"Rodriguez Albillar\",\"fecha_nacimiento\":\"1967-02-27\",\"genero\":\"M\",\"telefono\":\"0416-2895115\",\"email\":\"rubenjrodriguez27@gmail.com\",\"direccion\":\"Venezuela\",\"cargo_id\":\"2\",\"departamento_id\":\"5\",\"fecha_ingreso\":\"2024-12-15\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:00:00'),
(18, 2, 'GENERAR_CONSTANCIA', 'funcionarios', 2, NULL, '{\"funcionario\":\"RUBEN JOS\\u00c9 RODRIGUEZ ALBILLAR\",\"generado_por\":\"Ruben Rodriguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:00:07'),
(19, 2, 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', 3, '{\"id\":3,\"cedula\":\"V-34567890\",\"nombres\":\"Mayling\",\"apellidos\":\"Sifontes\",\"fecha_nacimiento\":\"1995-02-10\",\"genero\":\"F\",\"telefono\":\"0412-5553003\",\"email\":\"msifontes@ispeb.gob.ve\",\"direccion\":null,\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":3,\"departamento_id\":1,\"fecha_ingreso\":\"2020-01-08\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"created_at\":\"2026-03-07 18:12:04\",\"updated_at\":\"2026-03-07 18:12:04\",\"edad\":31,\"antiguedad_anos\":6,\"nombre_cargo\":\"Secretaria\",\"nivel_acceso\":3,\"departamento\":\"Direcci\\u00f3n de Telem\\u00e1tica\"}', '{\"cedula\":\"V-12193581\",\"nombres\":\"Mayling Carolina\",\"apellidos\":\"Sifontes Gasc\\u00f3n\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"0412-0869764\",\"email\":\"maylingcsifontes81@gmai.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"4\",\"departamento_id\":\"4\",\"fecha_ingreso\":\"2025-05-05\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:01:49'),
(20, 2, 'GENERAR_REPORTE_PDF', 'funcionarios', NULL, NULL, '{\"tipo_reporte\":\"listado\",\"filtros\":\"Estado: activo, Orden: apellidos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:02:15'),
(21, 2, 'EXPORTAR_EXCEL', NULL, NULL, NULL, '{\"tipo\":\"general\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:02:28'),
(22, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:02:55'),
(23, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:03:03'),
(24, 3, 'GENERAR_CONSTANCIA', 'funcionarios', 3, NULL, '{\"funcionario\":\"MAYLING CAROLINA SIFONTES GASC\\u00d3N\",\"generado_por\":\"Mayling Carolina Sifontes Gasc\\u00f3n\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:03:14'),
(25, 3, 'GENERAR_CONSTANCIA', 'funcionarios', 3, NULL, '{\"funcionario\":\"MAYLING CAROLINA SIFONTES GASC\\u00d3N\",\"generado_por\":\"Mayling Carolina Sifontes Gasc\\u00f3n\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:03:25'),
(26, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:03:59'),
(27, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:54:22'),
(28, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:54:28'),
(29, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:58:50'),
(30, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:58:54'),
(31, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:59:00'),
(32, 1, 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', 1, '{\"id\":1,\"cedula\":\"V-31087083\",\"nombres\":\"Albert Nazareth\",\"apellidos\":\"Rodriguez Sifontes\",\"fecha_nacimiento\":\"2005-11-08\",\"genero\":\"M\",\"telefono\":\"0424-9399005\",\"email\":\"albertro023@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":1,\"departamento_id\":1,\"fecha_ingreso\":\"2024-07-11\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"created_at\":\"2026-03-07 18:12:04\",\"updated_at\":\"2026-03-07 18:57:27\",\"edad\":20,\"antiguedad_anos\":1,\"nombre_cargo\":\"Jefe de Direcci\\u00f3n\",\"nivel_acceso\":1,\"departamento\":\"Direcci\\u00f3n de Telem\\u00e1tica\"}', '{\"cedula\":\"V-31087083\",\"nombres\":\"Albert Nazareth\",\"apellidos\":\"Rodriguez Sifontes\",\"fecha_nacimiento\":\"2005-11-08\",\"genero\":\"M\",\"telefono\":\"0424-9399005\",\"email\":\"albertro023@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"1\",\"departamento_id\":\"1\",\"fecha_ingreso\":\"2024-07-11\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"nivel_educativo\":\"Universitario\",\"titulo_obtenido\":\"Ing. Inform\\u00e1tica\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:27:16'),
(33, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:27:31'),
(34, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:27:37'),
(35, 2, 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', 2, '{\"id\":2,\"cedula\":\"V-8899490\",\"nombres\":\"Ruben Jos\\u00e9\",\"apellidos\":\"Rodriguez Albillar\",\"fecha_nacimiento\":\"1967-02-27\",\"genero\":\"M\",\"telefono\":\"0416-2895115\",\"email\":\"rubenjrodriguez27@gmail.com\",\"direccion\":\"Venezuela\",\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":2,\"departamento_id\":5,\"fecha_ingreso\":\"2024-12-15\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"created_at\":\"2026-03-07 18:12:04\",\"updated_at\":\"2026-03-07 19:00:00\",\"edad\":59,\"antiguedad_anos\":1,\"nombre_cargo\":\"Jefe de Departamento\",\"nivel_acceso\":2,\"departamento\":\"Soporte T\\u00e9cnico\"}', '{\"cedula\":\"V-8899490\",\"nombres\":\"Ruben Jos\\u00e9\",\"apellidos\":\"Rodriguez Albillar\",\"fecha_nacimiento\":\"1967-02-27\",\"genero\":\"M\",\"telefono\":\"0416-2895115\",\"email\":\"rubenjrodriguez27@gmail.com\",\"direccion\":\"Venezuela\",\"cargo_id\":\"2\",\"departamento_id\":\"5\",\"fecha_ingreso\":\"2024-12-15\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"nivel_educativo\":\"TSU\",\"titulo_obtenido\":\"TSU en Relaciones Industriales\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:28:06'),
(36, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:28:18'),
(37, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:28:21'),
(38, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-08 00:28:59'),
(39, 2, 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', 3, '{\"id\":3,\"cedula\":\"V-12193581\",\"nombres\":\"Mayling Carolina\",\"apellidos\":\"Sifontes Gasc\\u00f3n\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"0412-0869764\",\"email\":\"maylingcsifontes81@gmai.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":4,\"departamento_id\":4,\"fecha_ingreso\":\"2025-05-05\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"created_at\":\"2026-03-07 18:12:04\",\"updated_at\":\"2026-03-07 19:01:49\",\"edad\":49,\"antiguedad_anos\":0,\"nombre_cargo\":\"Analista\",\"nivel_acceso\":3,\"departamento\":\"Atenci\\u00f3n al Usuario\"}', '{\"cedula\":\"V-12193581\",\"nombres\":\"Mayling Carolina\",\"apellidos\":\"Sifontes Gasc\\u00f3n\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"0412-0869764\",\"email\":\"maylingcsifontes81@gmai.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"4\",\"departamento_id\":\"4\",\"fecha_ingreso\":\"2025-05-05\",\"foto\":\"default-avatar.png\",\"estado\":\"activo\",\"nivel_educativo\":\"Universitario\",\"titulo_obtenido\":\"Lic. en Administraci\\u00f3n\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-08 00:29:44'),
(40, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-08 00:29:58'),
(41, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-08 00:30:05'),
(42, 3, 'GENERAR_CONSTANCIA', 'funcionarios', 3, NULL, '{\"funcionario\":\"MAYLING CAROLINA SIFONTES GASC\\u00d3N\",\"generado_por\":\"Mayling Carolina Sifontes Gasc\\u00f3n\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-08 00:30:29'),
(43, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:30:53'),
(44, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:31:36'),
(45, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 01:32:28'),
(46, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 01:39:59'),
(47, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:19:45'),
(48, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-08 02:24:33'),
(49, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:25:22'),
(50, 1, 'GENERAR_CONSTANCIA', 'funcionarios', 1, NULL, '{\"funcionario\":\"ALBERT NAZARETH RODRIGUEZ SIFONTES\",\"generado_por\":\"Albert Nazareth Rodriguez Sifontes\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:25:49'),
(51, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:26:23'),
(52, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:26:40'),
(53, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-08 02:32:56'),
(54, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:36:02'),
(55, 1, 'GENERAR_CONSTANCIA', 'funcionarios', 1, NULL, '{\"funcionario\":\"ALBERT NAZARETH RODRIGUEZ SIFONTES\",\"generado_por\":\"Albert Nazareth Rodriguez Sifontes\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 02:54:45'),
(56, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 16:48:44'),
(57, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 22:40:37'),
(58, 2, 'CREAR_FUNCIONARIO', 'funcionarios', 4, NULL, '{\"cedula\":\"31230388\",\"nombres\":\"Maria Luisa\",\"apellidos\":\"Lopez Martinez\",\"fecha_nacimiento\":\"2005-12-21\",\"genero\":\"F\",\"telefono\":\"04269305228\",\"email\":\"marialopez@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 28\",\"cargo_id\":\"3\",\"departamento_id\":\"1\",\"fecha_ingreso\":\"2026-03-06\",\"estado\":\"activo\",\"nivel_educativo\":\"Universitario\",\"titulo_obtenido\":\"Ing. Inform\\u00e1tica\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 22:43:24'),
(59, 2, 'REGISTRAR_NOMBRAMIENTO', 'historial_administrativo', 1, NULL, '{\"funcionario_id\":4,\"cargo_actual\":\"Secretaria\",\"fecha_evento\":\"2026-03-06\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 22:50:23'),
(60, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 22:52:06'),
(61, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 23:59:51'),
(62, 2, 'REGISTRAR_NOMBRAMIENTO', 'historial_administrativo', 2, NULL, '{\"funcionario_id\":1,\"cargo_actual\":\"Jefe de Direcci\\u00f3n\",\"fecha_evento\":\"2024-07-11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:01:13'),
(63, 2, 'GENERAR_CONSTANCIA', 'funcionarios', 2, NULL, '{\"funcionario\":\"RUBEN JOS\\u00c9 RODRIGUEZ ALBILLAR\",\"generado_por\":\"Ruben Jos\\u00e9 Rodriguez Albillar\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:01:25'),
(64, 2, 'REGISTRAR_NOMBRAMIENTO', 'historial_administrativo', 3, NULL, '{\"funcionario_id\":2,\"cargo_actual\":\"Jefe de Departamento\",\"fecha_evento\":\"2026-03-08\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:02:11'),
(65, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:04:34'),
(66, NULL, 'REGISTRO_COMPLETADO', 'usuarios', 4, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-09 00:11:57'),
(67, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-09 00:12:06'),
(68, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:12:30'),
(69, NULL, 'GENERAR_CONSTANCIA', 'funcionarios', 4, NULL, '{\"funcionario\":\"MARIA LUISA LOPEZ MARTINEZ\",\"generado_por\":\"Maria Luisa Lopez Martinez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:12:54'),
(70, NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:16:17'),
(71, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:16:29'),
(72, NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:57:38'),
(73, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:58:56'),
(74, NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 00:59:41'),
(75, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 01:01:18'),
(76, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 01:51:05'),
(77, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-09 01:54:34'),
(78, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 02:21:51'),
(79, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-09 02:23:07'),
(80, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 02:31:32'),
(81, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 03:45:29'),
(82, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 03:45:36'),
(83, 1, 'GENERAR_CONSTANCIA', 'funcionarios', 2, NULL, '{\"funcionario\":\"RUBEN JOS\\u00c9 RODRIGUEZ ALBILLAR\",\"generado_por\":\"Albert Nazareth Rodriguez Sifontes\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:01:35'),
(84, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320', '2026-03-09 04:01:51'),
(85, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-09 04:03:30'),
(86, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-03-09 04:04:15'),
(87, 1, 'LOGIN', NULL, NULL, NULL, NULL, '192.168.1.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-09 04:06:00'),
(88, 1, 'GENERAR_CONSTANCIA', 'funcionarios', 4, NULL, '{\"funcionario\":\"MARIA LUISA LOPEZ MARTINEZ\",\"generado_por\":\"Albert Nazareth Rodriguez Sifontes\"}', '192.168.1.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-09 04:06:21'),
(89, 1, 'LOGIN', NULL, NULL, NULL, NULL, '192.168.1.5', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:07:29'),
(90, 1, 'LOGIN', NULL, NULL, NULL, NULL, '192.168.1.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-09 04:07:47'),
(91, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:08:16'),
(92, 1, 'REGISTRAR_VACACION', 'historial_administrativo', 4, NULL, '{\"funcionario_id\":2,\"dias_habiles\":10,\"estado_actualizado\":\"vacaciones\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:24:27'),
(93, 1, 'REGISTRAR_TRASLADO', 'historial_administrativo', 5, NULL, '{\"funcionario_id\":4,\"departamento_origen\":\"Direcci\\u00f3n de Telem\\u00e1tica\",\"departamento_destino\":\"Atenci\\u00f3n al Usuario\",\"motivo\":\"Prueba\",\"departamento_id_actualizado\":4}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:26:47'),
(94, 1, 'REGISTRAR_AMONESTACION', 'historial_administrativo', 6, NULL, '{\"tipo_falta\":\"leve\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:27:46'),
(95, 1, 'GENERAR_REPORTE_PDF', 'historial_administrativo', 4, NULL, '{\"tipo_reporte\":\"historial\",\"filtros\":\"Funcionario: 4, Evento: todos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 04:28:23'),
(96, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:07:39'),
(97, 1, 'GENERAR_REPORTE_PDF', 'historial_administrativo', 1, NULL, '{\"tipo_reporte\":\"historial\",\"filtros\":\"Funcionario: 1, Evento: todos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:14:44'),
(98, 1, 'GENERAR_REPORTE_PDF', 'historial_administrativo', 4, NULL, '{\"tipo_reporte\":\"historial\",\"filtros\":\"Funcionario: 4, Evento: todos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:14:55'),
(99, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:19:29'),
(100, NULL, 'RECUPERAR_PASSWORD_INICIO', 'usuarios', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:19:40'),
(101, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:21:35'),
(102, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:34:37'),
(103, NULL, 'RECUPERAR_PASSWORD_INICIO', 'usuarios', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:34:43'),
(104, NULL, 'RECUPERAR_PASSWORD_VALIDACION_EXITOSA', 'usuarios', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:34:57'),
(105, NULL, 'RECUPERAR_PASSWORD_EXITO', 'usuarios', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:35:07'),
(106, NULL, 'LOGIN_FALLIDO', 'usuarios', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:35:17'),
(107, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:35:25'),
(108, NULL, 'CREAR_FUNCIONARIO', 'funcionarios', 5, NULL, '{\"cedula\":\"16759128\",\"nombres\":\"Juan Alberto\",\"apellidos\":\"Gonzales Perez\",\"fecha_nacimiento\":\"1997-11-20\",\"genero\":\"M\",\"telefono\":\"04248956974\",\"email\":\"juanperez@gmail.com\",\"direccion\":\"Venezuela\",\"cargo_id\":\"6\",\"departamento_id\":\"2\",\"fecha_ingreso\":\"2026-03-09\",\"estado\":\"activo\",\"nivel_educativo\":\"TSU\",\"titulo_obtenido\":\"TSU en Informatica\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 10:37:15'),
(109, NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:22:41'),
(110, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:22:46'),
(111, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:22:56'),
(112, NULL, 'REGISTRO_COMPLETADO', 'usuarios', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:23:26'),
(113, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:23:30'),
(114, NULL, 'GENERAR_CONSTANCIA', 'funcionarios', 5, NULL, '{\"funcionario\":\"JUAN ALBERTO GONZALES PEREZ\",\"generado_por\":\"Juan Alberto Gonzales Perez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:23:33'),
(115, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-09 11:24:07'),
(116, NULL, 'NUEVA_SOLICITUD', 'solicitudes_empleados', 1, NULL, '{\"tipo\":\"permiso\",\"fecha_inicio\":\"2026-03-09\",\"fecha_fin\":\"2026-03-09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:29:01'),
(117, NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:32:24'),
(118, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:34:11'),
(119, 1, 'EXPORTAR_BD', 'sistema', NULL, '\"Exportaci\\u00f3n de base de datos completa\"', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:34:26'),
(120, 1, 'APROBAR_SOLICITUD', 'solicitudes_empleados', 1, '{\"estado\":\"pendiente\"}', '{\"estado\":\"aprobada\",\"historial_id\":\"7\",\"ruta_archivo\":null,\"tipo_evento\":\"PERMISO\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:44:48'),
(121, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:45:01'),
(122, NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:45:17'),
(123, NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:45:49'),
(124, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:45:52'),
(125, 1, 'CAMBIAR_ESTADO_USUARIO', 'usuarios', 4, '{\"estado_anterior\":\"activo\"}', '{\"estado_nuevo\":\"inactivo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:46:45'),
(126, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:46:50'),
(127, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 11:47:01');

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
(1, 'Jefe de Dirección', 1, 'Responsable de dirección y coordinación administrativa', '2026-03-07 22:12:04'),
(2, 'Jefe de Departamento', 2, 'Responsable de un departamento funcional', '2026-03-07 22:12:04'),
(3, 'Secretaria', 2, 'Gestión administrativa y de correspondencia', '2026-03-07 22:12:04'),
(4, 'Analista', 3, 'Analista de sistemas o procesos', '2026-03-07 22:12:04'),
(5, 'Técnico', 3, 'Técnico especializado en su área', '2026-03-07 22:12:04'),
(6, 'Desarrollador de Software', 3, 'Diseño y desarrollo de aplicaciones institucionales', '2026-03-07 22:12:04');

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
(1, 'Dirección de Telemática', 'Coordinación de proyectos tecnológicos institucionales', 'activo', '2026-03-07 22:12:04', '2026-03-07 22:12:04'),
(2, 'Sistemas', 'Infraestructura tecnológica y soporte técnico', 'activo', '2026-03-07 22:12:04', '2026-03-07 22:12:04'),
(3, 'Redes y Telecomunicaciones', 'Administración de redes, comunicaciones y conectividad', 'activo', '2026-03-07 22:12:04', '2026-03-07 22:12:04'),
(4, 'Atención al Usuario', 'Mesa de servicio y soporte a la comunidad institucional', 'activo', '2026-03-07 22:12:04', '2026-03-07 22:12:04'),
(5, 'Soporte Técnico', 'Mantenimiento preventivo y correctivo de equipos', 'activo', '2026-03-07 22:12:04', '2026-03-07 22:12:04'),
(6, 'Reparaciones Electrónicas', 'Diagnóstico y reparación de equipos electrónicos', 'activo', '2026-03-07 22:12:04', '2026-03-07 22:12:04');

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
  `nivel_educativo` enum('Primaria','Bachiller','TSU','Universitario','Postgrado','Maestría','Doctorado') DEFAULT NULL,
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
(1, 'V-31087083', 'Albert Nazareth', 'Rodriguez Sifontes', '2005-11-08', 'M', '0424-9399005', 'albertro023@gmail.com', 'Sector La lucha, Calle Campo Elias, Casa 7', 'Universitario', 'Ing. Informática', NULL, 0, 1, 1, '2024-07-11', 'default-avatar.png', 'activo', '2026-03-07 22:12:04', '2026-03-08 00:27:16'),
(2, 'V-8899490', 'Ruben José', 'Rodriguez Albillar', '1967-02-27', 'M', '0416-2895115', 'rubenjrodriguez27@gmail.com', 'Venezuela', 'TSU', 'TSU en Relaciones Industriales', NULL, 0, 2, 5, '2024-12-15', 'default-avatar.png', 'activo', '2026-03-07 22:12:04', '2026-03-09 11:51:30'),
(3, 'V-12193581', 'Mayling Carolina', 'Sifontes Gascón', '1976-10-28', 'F', '0412-0869764', 'maylingcsifontes81@gmai.com', 'Sector La lucha, Calle Campo Elias, Casa 7', 'Universitario', 'Lic. en Administración', NULL, 0, 4, 4, '2025-05-05', 'default-avatar.png', 'activo', '2026-03-07 22:12:04', '2026-03-08 00:29:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_administrativo`
--

DROP TABLE IF EXISTS `historial_administrativo`;
CREATE TABLE `historial_administrativo` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('NOMBRAMIENTO','VACACION','AMONESTACION','REMOCION','TRASLADO','DESPIDO','RENUNCIA','PERMISO') NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'Para vacaciones y permisos: fecha de finalización',
  `detalles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON con datos específicos del evento',
  `ruta_archivo_pdf` varchar(255) DEFAULT NULL,
  `nombre_archivo_original` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `historial_administrativo`
--

INSERT INTO `historial_administrativo` (`id`, `funcionario_id`, `tipo_evento`, `fecha_evento`, `fecha_fin`, `detalles`, `ruta_archivo_pdf`, `nombre_archivo_original`, `registrado_por`, `created_at`, `updated_at`) VALUES
(2, 1, 'NOMBRAMIENTO', '2024-07-11', NULL, '{\"cargo\":\"Jefe de Dirección\",\"departamento\":\"Dirección de Telemática\",\"motivo\":\"Registro de nombramiento\"}', 'subidas/funcionarios/1/nombramientos/nombramientos_20260308_200113.pdf', 'NOMBRAMIENTO.pdf', 2, '2026-03-09 00:01:13', '2026-03-09 00:01:13'),
(3, 2, 'NOMBRAMIENTO', '2026-03-08', NULL, '{\"cargo\":\"Jefe de Departamento\",\"departamento\":\"Soporte Técnico\",\"motivo\":\"Registro de nombramiento\"}', 'subidas/funcionarios/2/nombramientos/nombramientos_20260308_200211.pdf', 'NOMBRAMIENTO.pdf', 2, '2026-03-09 00:02:11', '2026-03-09 00:02:11');

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
(1, '¿Cuál es el nombre de tu primera mascota?', 1, 1, '2026-03-07 22:12:04'),
(2, '¿En qué ciudad naciste?', 1, 2, '2026-03-07 22:12:04'),
(3, '¿Cuál es el apellido de soltera de tu madre?', 1, 3, '2026-03-07 22:12:04'),
(4, '¿Cuál es el nombre de tu mejor amigo de la infancia?', 1, 4, '2026-03-07 22:12:04'),
(5, '¿Cuál fue el nombre de tu primera escuela?', 1, 5, '2026-03-07 22:12:04'),
(6, '¿Cuál es tu comida favorita?', 1, 6, '2026-03-07 22:12:04'),
(7, '¿En qué año conociste a tu pareja?', 1, 7, '2026-03-07 22:12:04'),
(8, '¿Cuál es el nombre de tu libro favorito?', 1, 8, '2026-03-07 22:12:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_empleados`
--

DROP TABLE IF EXISTS `solicitudes_empleados`;
CREATE TABLE `solicitudes_empleados` (
  `id` int(10) UNSIGNED NOT NULL,
  `funcionario_id` int(10) UNSIGNED NOT NULL,
  `tipo_solicitud` enum('vacaciones','permiso') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` text NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `revisado_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID del usuario que gestionó la solicitud',
  `observaciones_respuesta` text DEFAULT NULL,
  `ruta_archivo_aprobacion` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `registro_completado` tinyint(1) DEFAULT 0 COMMENT 'Indica si el usuario completó su registro',
  `pregunta_seguridad_1` int(10) UNSIGNED DEFAULT NULL,
  `respuesta_seguridad_1` varchar(255) DEFAULT NULL,
  `pregunta_seguridad_2` int(10) UNSIGNED DEFAULT NULL,
  `respuesta_seguridad_2` varchar(255) DEFAULT NULL,
  `pregunta_seguridad_3` int(10) UNSIGNED DEFAULT NULL,
  `respuesta_seguridad_3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `funcionario_id`, `username`, `password_hash`, `email_recuperacion`, `token_recuperacion`, `token_expiracion`, `ultimo_acceso`, `intentos_fallidos`, `bloqueado_hasta`, `estado`, `registro_completado`, `pregunta_seguridad_1`, `respuesta_seguridad_1`, `pregunta_seguridad_2`, `respuesta_seguridad_2`, `pregunta_seguridad_3`, `respuesta_seguridad_3`, `created_at`, `updated_at`) VALUES
(1, 1, 'arodriguez', '$2b$12$eGPU7TgGnM1uroaJ1Xu3e.RTsZAw6aFk76ci/cCfMMpVf5GEHEJSW', 'arodriguez@ispeb.gob.ve', NULL, NULL, '2026-03-09 07:47:01', 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-07 22:12:04', '2026-03-09 11:47:01'),
(2, 2, 'rrodriguez', '$2b$12$NrZpfu9imjqofQ6dye2Tsuv93Ay3SCOsk1iPv9fhQyjmqkNAzM40q', 'rrodriguez@ispeb.gob.ve', NULL, NULL, '2026-03-08 19:59:51', 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-07 22:12:04', '2026-03-08 23:59:51'),
(3, 3, 'msifontes', '$2b$12$EjzeyDks5N/1nXSGY0T69ub.CYKCwr6YXsOIrGrbIiFGqrWI1M56W', 'msifontes@ispeb.gob.ve', NULL, NULL, '2026-03-07 20:30:05', 0, NULL, 'activo', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-07 22:12:04', '2026-03-08 00:30:05');

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
,`nivel_educativo` enum('Primaria','Bachiller','TSU','Universitario','Postgrado','Maestría','Doctorado')
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
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_fecha` (`created_at`);

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
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_tipo_evento` (`tipo_evento`),
  ADD KEY `idx_fecha_evento` (`fecha_evento`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Indices de la tabla `preguntas_seguridad_catalogo`
--
ALTER TABLE `preguntas_seguridad_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pregunta` (`pregunta`);

--
-- Indices de la tabla `solicitudes_empleados`
--
ALTER TABLE `solicitudes_empleados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_revisor` (`revisado_por`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_id` (`funcionario_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_registro_completado` (`registro_completado`),
  ADD KEY `fk_usuario_preg1` (`pregunta_seguridad_1`),
  ADD KEY `fk_usuario_preg2` (`pregunta_seguridad_2`),
  ADD KEY `fk_usuario_preg3` (`pregunta_seguridad_3`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_administrativo`
--
ALTER TABLE `historial_administrativo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `preguntas_seguridad_catalogo`
--
ALTER TABLE `preguntas_seguridad_catalogo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `solicitudes_empleados`
--
ALTER TABLE `solicitudes_empleados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

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
-- Filtros para la tabla `solicitudes_empleados`
--
ALTER TABLE `solicitudes_empleados`
  ADD CONSTRAINT `solicitudes_empleados_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_empleados_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_preg1` FOREIGN KEY (`pregunta_seguridad_1`) REFERENCES `preguntas_seguridad_catalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuario_preg2` FOREIGN KEY (`pregunta_seguridad_2`) REFERENCES `preguntas_seguridad_catalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuario_preg3` FOREIGN KEY (`pregunta_seguridad_3`) REFERENCES `preguntas_seguridad_catalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
