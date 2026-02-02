-- =====================================================
-- RESPALDO DE BASE DE DATOS - ISPEB
-- Fecha: 2026-01-29 23:51:33
-- Generado por: Carlos Rodríguez
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- Tabla: activos_tecnologicos
DROP TABLE IF EXISTS `activos_tecnologicos`;
CREATE TABLE `activos_tecnologicos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) unsigned DEFAULT NULL,
  `tipo` enum('Laptop','PC','Radio','Tablet','TelÃÂ©fono','Switch','Router','Otro') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serial` varchar(100) NOT NULL,
  `estado` enum('Asignado','Disponible','En ReparaciÃÂ³n','Dado de Baja') DEFAULT 'Disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_asignacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial` (`serial`),
  KEY `idx_funcionario` (`funcionario_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_serial` (`serial`),
  CONSTRAINT `activos_tecnologicos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de activos_tecnologicos
INSERT INTO `activos_tecnologicos` VALUES ('1', NULL, 'Laptop', 'HP', 'ProBook 450 G8', 'HP-LAP-001', 'Disponible', '2024-01-15', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('2', NULL, 'Laptop', 'Dell', 'Latitude 5420', 'DELL-LAP-002', 'Disponible', '2024-02-20', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('3', NULL, 'PC', 'HP', 'EliteDesk 800 G6', 'HP-PC-001', 'Disponible', '2023-11-10', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('4', NULL, 'PC', 'Dell', 'OptiPlex 7090', 'DELL-PC-002', 'Disponible', '2023-12-05', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('5', NULL, 'Radio', 'Motorola', 'DGP5550', 'MOT-RAD-001', 'Disponible', '2023-08-05', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('6', NULL, 'Radio', 'Motorola', 'DGP5550', 'MOT-RAD-002', 'Disponible', '2023-08-05', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('7', NULL, 'Tablet', 'Samsung', 'Galaxy Tab A8', 'SAM-TAB-001', 'Disponible', '2024-03-12', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('8', NULL, 'Switch', 'Cisco', 'Catalyst 2960', 'CISCO-SW-001', 'Disponible', '2023-06-10', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `activos_tecnologicos` VALUES ('9', NULL, 'Router', 'Cisco', 'ISR 4331', 'CISCO-RT-001', 'Disponible', '2023-07-15', NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');

-- Tabla: auditoria
DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `accion` varchar(100) NOT NULL COMMENT 'Ej: LOGIN, CREAR_FUNCIONARIO, ELIMINAR_DOCUMENTO',
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(10) unsigned DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_fecha` (`created_at`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de auditoria
INSERT INTO `auditoria` VALUES ('1', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:36:48');
INSERT INTO `auditoria` VALUES ('2', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:36:50');
INSERT INTO `auditoria` VALUES ('3', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:36:53');
INSERT INTO `auditoria` VALUES ('4', '4', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:37:08');
INSERT INTO `auditoria` VALUES ('5', '4', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:37:22');
INSERT INTO `auditoria` VALUES ('6', '11', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:37:33');
INSERT INTO `auditoria` VALUES ('7', '11', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:37:42');
INSERT INTO `auditoria` VALUES ('8', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:27:35');
INSERT INTO `auditoria` VALUES ('9', '1', 'LOGIN', NULL, NULL, NULL, NULL, '192.168.1.5', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-21 07:42:02');
INSERT INTO `auditoria` VALUES ('10', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '34', NULL, '{\"funcionario\":\"ANDREA RAMOS\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:52:13');
INSERT INTO `auditoria` VALUES ('11', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '34', NULL, '{\"funcionario\":\"ANDREA RAMOS\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:52:13');
INSERT INTO `auditoria` VALUES ('12', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '34', NULL, '{\"funcionario\":\"ANDREA RAMOS\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:53:16');
INSERT INTO `auditoria` VALUES ('13', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '34', NULL, '{\"funcionario\":\"ANDREA RAMOS\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:53:16');
INSERT INTO `auditoria` VALUES ('14', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '44', NULL, '{\"funcionario\":\"MAR\\u00edA N\\u00fa\\u00f1EZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:53:47');
INSERT INTO `auditoria` VALUES ('15', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '44', NULL, '{\"funcionario\":\"MAR\\u00edA N\\u00fa\\u00f1EZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:53:47');
INSERT INTO `auditoria` VALUES ('16', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '44', NULL, '{\"funcionario\":\"MAR\\u00edA N\\u00fa\\u00f1EZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:55:36');
INSERT INTO `auditoria` VALUES ('17', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '44', NULL, '{\"funcionario\":\"MAR\\u00edA N\\u00fa\\u00f1EZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:55:36');
INSERT INTO `auditoria` VALUES ('18', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:55:41');
INSERT INTO `auditoria` VALUES ('19', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:55:43');
INSERT INTO `auditoria` VALUES ('20', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '30', NULL, '{\"funcionario\":\"CAMILA GUZM\\u00e1N\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:55:59');
INSERT INTO `auditoria` VALUES ('21', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '30', NULL, '{\"funcionario\":\"CAMILA GUZM\\u00e1N\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:55:59');
INSERT INTO `auditoria` VALUES ('22', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '30', NULL, '{\"funcionario\":\"CAMILA GUZM\\u00e1N\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:56:23');
INSERT INTO `auditoria` VALUES ('23', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '30', NULL, '{\"funcionario\":\"CAMILA GUZM\\u00e1N\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:56:23');
INSERT INTO `auditoria` VALUES ('24', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:56:38');
INSERT INTO `auditoria` VALUES ('25', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:56:40');
INSERT INTO `auditoria` VALUES ('26', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:56:53');
INSERT INTO `auditoria` VALUES ('27', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:56:53');
INSERT INTO `auditoria` VALUES ('28', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:11');
INSERT INTO `auditoria` VALUES ('29', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:11');
INSERT INTO `auditoria` VALUES ('30', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:33');
INSERT INTO `auditoria` VALUES ('31', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:33');
INSERT INTO `auditoria` VALUES ('32', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:52');
INSERT INTO `auditoria` VALUES ('33', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '38', NULL, '{\"funcionario\":\"ADRI\\u00e1N PE\\u00f1A\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:52');
INSERT INTO `auditoria` VALUES ('34', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:01:32');
INSERT INTO `auditoria` VALUES ('35', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:08:03');
INSERT INTO `auditoria` VALUES ('36', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:08:16');
INSERT INTO `auditoria` VALUES ('37', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:08:17');
INSERT INTO `auditoria` VALUES ('38', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:08:20');
INSERT INTO `auditoria` VALUES ('39', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:35:36');
INSERT INTO `auditoria` VALUES ('40', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 11:09:49');
INSERT INTO `auditoria` VALUES ('41', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 11:11:41');
INSERT INTO `auditoria` VALUES ('42', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 11:11:42');
INSERT INTO `auditoria` VALUES ('43', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 11:12:09');
INSERT INTO `auditoria` VALUES ('44', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '6', NULL, '{\"funcionario\":\"CARMEN L\\u00f3PEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 11:12:10');
INSERT INTO `auditoria` VALUES ('45', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 12:56:13');
INSERT INTO `auditoria` VALUES ('46', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 12:56:21');
INSERT INTO `auditoria` VALUES ('47', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:16:04');
INSERT INTO `auditoria` VALUES ('48', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:19:39');
INSERT INTO `auditoria` VALUES ('49', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:19:40');
INSERT INTO `auditoria` VALUES ('50', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:20:04');
INSERT INTO `auditoria` VALUES ('51', '1', 'GENERAR_REPORTE_PDF', NULL, NULL, NULL, '{\"tipo\":\"general\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:21:06');
INSERT INTO `auditoria` VALUES ('52', '1', 'GENERAR_REPORTE_PDF', NULL, NULL, NULL, '{\"tipo\":\"cumpleanos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:21:29');
INSERT INTO `auditoria` VALUES ('53', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:21:57');
INSERT INTO `auditoria` VALUES ('54', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:37:19');
INSERT INTO `auditoria` VALUES ('55', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:37:57');
INSERT INTO `auditoria` VALUES ('56', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:38:40');
INSERT INTO `auditoria` VALUES ('57', '1', 'CREAR_FUNCIONARIO', 'funcionarios', '46', NULL, '{\"cedula\":\"31087083\",\"nombres\":\"Albert\",\"apellidos\":\"Rodriguez\",\"fecha_nacimiento\":\"2005-11-08\",\"genero\":\"M\",\"telefono\":\"04249399005\",\"email\":\"albertrodrigrez7@gmail.com\",\"direccion\":\"Venezuela\",\"cargo_id\":\"6\",\"departamento_id\":\"1\",\"fecha_ingreso\":\"2026-01-21\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:43:15');
INSERT INTO `auditoria` VALUES ('58', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:43:31');
INSERT INTO `auditoria` VALUES ('59', NULL, 'COMPLETAR_REGISTRO', 'usuarios', '46', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:46:33');
INSERT INTO `auditoria` VALUES ('60', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:46:54');
INSERT INTO `auditoria` VALUES ('61', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:47:33');
INSERT INTO `auditoria` VALUES ('62', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:47:34');
INSERT INTO `auditoria` VALUES ('63', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:48:25');
INSERT INTO `auditoria` VALUES ('64', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:48:25');
INSERT INTO `auditoria` VALUES ('65', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:48:31');
INSERT INTO `auditoria` VALUES ('66', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 19:22:09');
INSERT INTO `auditoria` VALUES ('67', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 19:22:25');
INSERT INTO `auditoria` VALUES ('68', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 19:22:26');
INSERT INTO `auditoria` VALUES ('69', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 19:22:37');
INSERT INTO `auditoria` VALUES ('70', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 19:27:38');
INSERT INTO `auditoria` VALUES ('71', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 19:27:56');
INSERT INTO `auditoria` VALUES ('72', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 19:27:58');
INSERT INTO `auditoria` VALUES ('73', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 20:48:56');
INSERT INTO `auditoria` VALUES ('74', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 20:48:57');
INSERT INTO `auditoria` VALUES ('75', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 20:49:03');
INSERT INTO `auditoria` VALUES ('76', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 20:49:06');
INSERT INTO `auditoria` VALUES ('77', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 21:26:37');
INSERT INTO `auditoria` VALUES ('78', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 18:27:40');
INSERT INTO `auditoria` VALUES ('79', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 19:15:18');
INSERT INTO `auditoria` VALUES ('80', '1', 'GENERAR_REPORTE_PDF', NULL, NULL, NULL, '{\"tipo\":\"nombramientos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 19:25:36');
INSERT INTO `auditoria` VALUES ('81', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 19:25:47');
INSERT INTO `auditoria` VALUES ('82', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 19:36:11');
INSERT INTO `auditoria` VALUES ('83', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 19:50:50');
INSERT INTO `auditoria` VALUES ('84', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 21:32:13');
INSERT INTO `auditoria` VALUES ('85', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 21:48:39');
INSERT INTO `auditoria` VALUES ('86', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-26 21:50:17');
INSERT INTO `auditoria` VALUES ('87', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:20:33');
INSERT INTO `auditoria` VALUES ('88', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:26:01');
INSERT INTO `auditoria` VALUES ('89', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:26:17');
INSERT INTO `auditoria` VALUES ('90', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:26:21');
INSERT INTO `auditoria` VALUES ('91', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:26:39');
INSERT INTO `auditoria` VALUES ('92', '1', 'CREAR_DEPARTAMENTO', 'departamentos', '6', NULL, '{\"nombre\":\"Pruebas QA\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:27:15');
INSERT INTO `auditoria` VALUES ('93', '1', 'ACTUALIZAR_DEPARTAMENTO', 'departamentos', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:42:33');
INSERT INTO `auditoria` VALUES ('94', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:41:27');
INSERT INTO `auditoria` VALUES ('95', '1', 'CAMBIAR_ESTADO_DEPARTAMENTO', 'departamentos', '6', '{\"estado_anterior\":\"activo\"}', '{\"estado_nuevo\":\"inactivo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:44:20');
INSERT INTO `auditoria` VALUES ('96', '1', 'CREAR_CARGO', 'cargos', '7', NULL, '{\"nombre_cargo\":\"Pasante de Pruebas\",\"nivel_acceso\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:44:50');
INSERT INTO `auditoria` VALUES ('97', '1', 'ACTUALIZAR_CARGO', 'cargos', '7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:45:01');
INSERT INTO `auditoria` VALUES ('98', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 06:29:45');
INSERT INTO `auditoria` VALUES ('99', '1', 'CREAR_FUNCIONARIO', 'funcionarios', '47', NULL, '{\"cedula\":\"12193581\",\"nombres\":\"Mayling\",\"apellidos\":\"Sifontes\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"04120869764\",\"email\":\"mailing@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"4\",\"departamento_id\":\"2\",\"fecha_ingreso\":\"2026-01-27\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 06:31:26');
INSERT INTO `auditoria` VALUES ('100', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 06:39:17');
INSERT INTO `auditoria` VALUES ('101', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 17:15:38');
INSERT INTO `auditoria` VALUES ('102', '1', 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', '47', '{\"id\":47,\"cedula\":\"12193581\",\"nombres\":\"Mayling\",\"apellidos\":\"Sifontes\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"04120869764\",\"email\":\"mailing@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":4,\"departamento_id\":2,\"fecha_ingreso\":\"2026-01-27\",\"foto\":null,\"estado\":\"activo\",\"created_at\":\"2026-01-27 06:31:26\",\"updated_at\":\"2026-01-27 06:31:26\",\"edad\":49,\"antiguedad_anos\":0,\"nombre_cargo\":\"Secretaria\",\"nivel_acceso\":2,\"departamento\":\"Sistemas\"}', '{\"cedula\":\"12193581\",\"nombres\":\"Mayling\",\"apellidos\":\"Sifontes\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"04120869764\",\"email\":\"mailing@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"4\",\"departamento_id\":\"2\",\"fecha_ingreso\":\"2026-01-27\",\"foto\":null,\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 17:25:49');
INSERT INTO `auditoria` VALUES ('103', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:06:53');
INSERT INTO `auditoria` VALUES ('104', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:08:29');
INSERT INTO `auditoria` VALUES ('105', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:08:30');
INSERT INTO `auditoria` VALUES ('106', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:08:30');
INSERT INTO `auditoria` VALUES ('107', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:08:42');
INSERT INTO `auditoria` VALUES ('108', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '1', NULL, '{\"funcionario\":\"CARLOS RODR\\u00edGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:08:43');
INSERT INTO `auditoria` VALUES ('109', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 09:09:13');
INSERT INTO `auditoria` VALUES ('110', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:35:29');
INSERT INTO `auditoria` VALUES ('111', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:36:06');
INSERT INTO `auditoria` VALUES ('112', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:46:28');
INSERT INTO `auditoria` VALUES ('113', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:49:08');
INSERT INTO `auditoria` VALUES ('114', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:49:09');
INSERT INTO `auditoria` VALUES ('115', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:49:40');
INSERT INTO `auditoria` VALUES ('116', '1', 'GENERAR_CONSTANCIA', 'funcionarios', '46', NULL, '{\"funcionario\":\"ALBERT RODRIGUEZ\",\"generado_por\":\"Carlos Rodr\\u00edguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 14:49:41');
INSERT INTO `auditoria` VALUES ('117', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:09:54');
INSERT INTO `auditoria` VALUES ('118', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:10:01');
INSERT INTO `auditoria` VALUES ('119', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:12:21');
INSERT INTO `auditoria` VALUES ('120', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:09:55');
INSERT INTO `auditoria` VALUES ('121', '1', 'ACTUALIZAR_FUNCIONARIO', 'funcionarios', '47', '{\"id\":47,\"cedula\":\"12193581\",\"nombres\":\"Mayling\",\"apellidos\":\"Sifontes\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"04120869764\",\"email\":\"mailing@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"nivel_educativo\":null,\"titulo_obtenido\":null,\"fecha_ingreso_admin_publica\":null,\"cantidad_hijos\":0,\"cargo_id\":4,\"departamento_id\":2,\"fecha_ingreso\":\"2026-01-27\",\"foto\":null,\"estado\":\"activo\",\"created_at\":\"2026-01-27 06:31:26\",\"updated_at\":\"2026-01-27 17:25:49\",\"edad\":49,\"antiguedad_anos\":0,\"nombre_cargo\":\"Secretaria\",\"nivel_acceso\":2,\"departamento\":\"Sistemas\"}', '{\"cedula\":\"12193581\",\"nombres\":\"Mayling\",\"apellidos\":\"Sifontes\",\"fecha_nacimiento\":\"1976-10-28\",\"genero\":\"F\",\"telefono\":\"04120869764\",\"email\":\"mailing@gmail.com\",\"direccion\":\"Sector La lucha, Calle Campo Elias, Casa 7\",\"cargo_id\":\"4\",\"departamento_id\":\"2\",\"fecha_ingreso\":\"2026-01-27\",\"foto\":null,\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:15:47');
INSERT INTO `auditoria` VALUES ('122', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-28 21:18:38');
INSERT INTO `auditoria` VALUES ('123', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:23:22');
INSERT INTO `auditoria` VALUES ('124', '1', 'RESETEAR_PASSWORD', 'usuarios', '46', NULL, '{\"usuario\":\"31087083\",\"funcionario\":\"Albert Rodriguez\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:38:27');
INSERT INTO `auditoria` VALUES ('125', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:38:38');
INSERT INTO `auditoria` VALUES ('126', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:38:56');
INSERT INTO `auditoria` VALUES ('127', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:55:15');
INSERT INTO `auditoria` VALUES ('128', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:55:18');
INSERT INTO `auditoria` VALUES ('129', '1', 'CREAR_FUNCIONARIO', 'funcionarios', '48', NULL, '{\"cedula\":\"31087083\",\"nombres\":\"Albert Nazareth\",\"apellidos\":\"Rodriguez Sifontes\",\"fecha_nacimiento\":\"2005-11-08\",\"genero\":\"M\",\"telefono\":\"04249399005\",\"email\":\"albertrodrigrez7@gmail.com\",\"direccion\":\"Venezuela\",\"cargo_id\":\"6\",\"departamento_id\":\"1\",\"fecha_ingreso\":\"2026-01-28\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:56:51');
INSERT INTO `auditoria` VALUES ('130', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 21:58:08');
INSERT INTO `auditoria` VALUES ('131', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:13:29');
INSERT INTO `auditoria` VALUES ('132', '1', 'CREAR_FUNCIONARIO', 'funcionarios', '49', NULL, '{\"cedula\":\"31087083\",\"nombres\":\"Albert Nazareth\",\"apellidos\":\"Rodriguez Sifontes\",\"fecha_nacimiento\":\"2005-11-08\",\"genero\":\"M\",\"telefono\":\"04249399005\",\"email\":\"albertrodrigrez7@gmail.com\",\"direccion\":\"Venezuela\",\"cargo_id\":\"6\",\"departamento_id\":\"1\",\"fecha_ingreso\":\"2026-01-28\",\"estado\":\"activo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:14:15');
INSERT INTO `auditoria` VALUES ('133', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:14:27');
INSERT INTO `auditoria` VALUES ('134', NULL, 'REGISTRO_COMPLETADO', 'usuarios', '50', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:32:29');
INSERT INTO `auditoria` VALUES ('135', NULL, 'REGISTRO_COMPLETADO', 'usuarios', '51', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:40:05');
INSERT INTO `auditoria` VALUES ('136', NULL, 'REGISTRO_COMPLETADO', 'usuarios', '52', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:44:51');
INSERT INTO `auditoria` VALUES ('137', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:51:38');
INSERT INTO `auditoria` VALUES ('138', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:51:43');
INSERT INTO `auditoria` VALUES ('139', NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:51:51');
INSERT INTO `auditoria` VALUES ('140', NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:52:19');
INSERT INTO `auditoria` VALUES ('141', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:52:21');
INSERT INTO `auditoria` VALUES ('142', '1', 'RESETEAR_PASSWORD', 'usuarios', '52', NULL, '{\"usuario\":\"arodriguez\",\"funcionario\":\"Albert Nazareth Rodriguez Sifontes\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:52:42');
INSERT INTO `auditoria` VALUES ('143', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:52:53');
INSERT INTO `auditoria` VALUES ('144', NULL, 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:53:02');
INSERT INTO `auditoria` VALUES ('145', NULL, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:53:07');
INSERT INTO `auditoria` VALUES ('146', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:53:09');
INSERT INTO `auditoria` VALUES ('147', '1', 'CAMBIAR_ESTADO_USUARIO', 'usuarios', '52', '{\"estado_anterior\":\"activo\"}', '{\"estado_nuevo\":\"inactivo\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:53:24');
INSERT INTO `auditoria` VALUES ('148', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:53:37');
INSERT INTO `auditoria` VALUES ('149', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 22:54:02');
INSERT INTO `auditoria` VALUES ('150', NULL, 'REGISTRO_COMPLETADO', 'usuarios', '53', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 07:46:08');
INSERT INTO `auditoria` VALUES ('151', NULL, 'LOGIN_FALLIDO', 'usuarios', '53', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 07:46:19');
INSERT INTO `auditoria` VALUES ('152', '53', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 07:46:30');
INSERT INTO `auditoria` VALUES ('153', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 22:15:55');
INSERT INTO `auditoria` VALUES ('154', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 22:16:11');
INSERT INTO `auditoria` VALUES ('155', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 23:42:52');
INSERT INTO `auditoria` VALUES ('156', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 23:43:34');
INSERT INTO `auditoria` VALUES ('157', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 23:45:50');
INSERT INTO `auditoria` VALUES ('158', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 23:49:57');
INSERT INTO `auditoria` VALUES ('159', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 23:50:49');
INSERT INTO `auditoria` VALUES ('160', '1', 'GENERAR_RESPALDO_BD', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 23:51:32');

-- Tabla: cargas_familiares
DROP TABLE IF EXISTS `cargas_familiares`;
CREATE TABLE `cargas_familiares` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) unsigned NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `parentesco` enum('Hijo/a','CÃÂ³nyuge','Padre','Madre','Hermano/a','Otro') NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_funcionario` (`funcionario_id`),
  KEY `idx_parentesco` (`parentesco`),
  CONSTRAINT `cargas_familiares_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cargos
DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_cargo` varchar(100) NOT NULL,
  `nivel_acceso` tinyint(4) NOT NULL COMMENT '1=Admin Total, 2=Operativo, 3=Solo Lectura',
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_cargo` (`nombre_cargo`),
  KEY `idx_nivel_acceso` (`nivel_acceso`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de cargos
INSERT INTO `cargos` VALUES ('1', 'Director de la Dirección', '1', 'Máxima autoridad de la Dirección de Telemática - Acceso total al sistema', '2026-01-21 00:19:39');
INSERT INTO `cargos` VALUES ('2', 'Jefe de Dirección', '1', 'Segundo al mando - Acceso total al sistema', '2026-01-21 00:19:39');
INSERT INTO `cargos` VALUES ('3', 'Jefe de Departamento', '2', 'Responsable de un departamento específico - Acceso operativo limitado a su departamento', '2026-01-21 00:19:39');
INSERT INTO `cargos` VALUES ('4', 'Secretaria', '2', 'Personal administrativo - Acceso operativo para gestión de expedientes', '2026-01-21 00:19:39');
INSERT INTO `cargos` VALUES ('5', 'Asistente', '3', 'Personal de apoyo - Solo lectura y descarga de documentos', '2026-01-21 00:19:39');
INSERT INTO `cargos` VALUES ('6', 'Técnico', '3', 'Personal técnico - Solo lectura y descarga de documentos', '2026-01-21 00:19:39');
INSERT INTO `cargos` VALUES ('7', 'Pasante de Pruebas', '3', '', '2026-01-27 05:44:50');

-- Tabla: departamentos
DROP TABLE IF EXISTS `departamentos`;
CREATE TABLE `departamentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de departamentos
INSERT INTO `departamentos` VALUES ('1', 'Soporte Técnico', 'Departamento encargado del soporte técnico a usuarios internos y externos', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `departamentos` VALUES ('2', 'Sistemas', 'Departamento de desarrollo y mantenimiento de sistemas informáticos', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `departamentos` VALUES ('3', 'Redes y Telecomunicaciones', 'Departamento de infraestructura de redes y comunicaciones', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `departamentos` VALUES ('4', 'Atención al Usuario', 'Departamento de atención y servicio al usuario final', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `departamentos` VALUES ('5', 'Reparaciones Electrónicas', 'Departamento de reparación y mantenimiento de equipos electrónicos', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `departamentos` VALUES ('6', 'Calidad de Software', 'Prueba', 'inactivo', '2026-01-26 22:27:15', '2026-01-27 05:44:20');

-- Tabla: funcionarios
DROP TABLE IF EXISTS `funcionarios`;
CREATE TABLE `funcionarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `cantidad_hijos` tinyint(3) unsigned DEFAULT 0,
  `cargo_id` int(10) unsigned NOT NULL,
  `departamento_id` int(10) unsigned NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `foto` varchar(255) DEFAULT 'default-avatar.png',
  `estado` enum('activo','vacaciones','reposo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_cedula` (`cedula`),
  KEY `idx_cargo` (`cargo_id`),
  KEY `idx_departamento` (`departamento_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_nombres` (`nombres`,`apellidos`),
  CONSTRAINT `funcionarios_ibfk_1` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`),
  CONSTRAINT `funcionarios_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de funcionarios
INSERT INTO `funcionarios` VALUES ('1', 'V-12345678', 'Carlos', 'Rodríguez', '1980-03-15', 'M', '0412-1234567', 'crodriguez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', NULL, '0', '1', '2', '2015-01-10', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `funcionarios` VALUES ('2', 'V-13456789', 'María', 'Núñez', '1982-07-22', 'F', '0424-2345678', 'mgonzalez@ispeb.gob.ve', NULL, 'Postgrado', 'Especialista en Redes', NULL, '0', '2', '2', '2016-03-15', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `funcionarios` VALUES ('3', 'V-14567890', 'Luis', 'Núñez', '1978-11-08', 'M', '0414-3456789', 'lmartinez@ispeb.gob.ve', NULL, '', 'Magíster en Gestión Tecnológica', NULL, '0', '1', '1', '2014-06-20', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('4', 'V-15678901', 'Ana', 'Pérez', '1985-05-12', 'F', '0426-4567890', 'aperez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniera en Electrónica', NULL, '0', '3', '1', '2017-02-14', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('5', 'V-16789012', 'José', 'Núñez', '1983-09-25', 'M', '0412-5678901', 'jhernandez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Telecomunicaciones', NULL, '0', '3', '3', '2016-08-10', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `funcionarios` VALUES ('6', 'V-17890123', 'Carmen', 'López', '1986-01-30', 'F', '0424-6789012', 'clopez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, '0', '3', '4', '2018-04-05', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('7', 'V-18901234', 'Pedro', 'García', '1984-12-18', 'M', '0414-7890123', 'pgarcia@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero Electrónico', NULL, '0', '3', '5', '2017-11-22', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('8', 'V-19012345', 'Laura', 'Ramírez', '1990-04-08', 'F', '0426-8901234', 'lramirez@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, '0', '4', '2', '2019-01-15', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `funcionarios` VALUES ('9', 'V-20123456', 'Sofía', 'Torres', '1992-08-14', 'F', '0412-9012345', 'storres@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', NULL, '0', '4', '1', '2020-03-10', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('10', 'V-21234567', 'Isabella', 'Flores', '1991-06-20', 'F', '0424-0123456', 'iflores@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', NULL, '0', '4', '4', '2019-09-05', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('11', 'V-22345678', 'Miguel', 'Núñez', '1988-02-11', 'M', '0414-1234567', 'msanchez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Computación', NULL, '0', '6', '1', '2018-05-20', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('12', 'V-23456789', 'Roberto', 'Díaz', '1989-07-16', 'M', '0426-2345678', 'rdiaz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', NULL, '0', '6', '1', '2019-02-12', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('13', 'V-24567890', 'Fernando', 'Morales', '1987-11-22', 'M', '0412-3456789', 'fmorales@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Computación', NULL, '0', '6', '1', '2017-08-18', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('14', 'V-25678901', 'Andrés', 'Castro', '1990-03-28', 'M', '0424-4567890', 'acastro@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Sistemas', NULL, '0', '6', '1', '2020-01-22', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('15', 'V-26789012', 'Daniel', 'Ruiz', '1991-09-05', 'M', '0414-5678901', 'druiz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Redes', NULL, '0', '6', '1', '2020-06-15', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('16', 'V-27890123', 'Gabriel', 'Ortiz', '1986-12-30', 'M', '0426-6789012', 'gortiz@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', NULL, '0', '6', '1', '2018-11-08', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('17', 'V-28901234', 'Ricardo', 'Vargas', '1989-05-17', 'M', '0412-7890123', 'rvargas@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, '0', '6', '1', '2019-07-25', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('18', 'V-29012345', 'Javier', 'Mendoza', '1988-08-24', 'M', '0424-8901234', 'jmendoza@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', NULL, '0', '6', '2', '2018-10-12', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('19', 'V-30123456', 'Alberto', 'Silva', '1990-01-19', 'M', '0414-9012345', 'asilva@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Programación', NULL, '0', '6', '2', '2020-02-28', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('20', 'V-31234567', 'Sergio', 'Rojas', '1987-06-13', 'M', '0426-0123456', 'srojas@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Computación', NULL, '0', '6', '2', '2017-12-05', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('21', 'V-32345678', 'Héctor', 'Navarro', '1991-10-07', 'M', '0412-1234568', 'hnavarro@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Desarrollo Web', NULL, '0', '6', '2', '2021-03-18', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('22', 'V-33456789', 'Raúl', 'Medina', '1989-04-21', 'M', '0424-2345679', 'rmedina@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Software', NULL, '0', '6', '2', '2019-08-22', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `funcionarios` VALUES ('23', 'V-34567890', 'Gustavo', 'Reyes', '1986-11-15', 'M', '0414-3456790', 'greyes@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Telecomunicaciones', NULL, '0', '6', '3', '2017-05-30', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('24', 'V-35678901', 'Arturo', 'Guerrero', '1988-07-09', 'M', '0426-4567891', 'aguerrero@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Redes', NULL, '0', '6', '3', '2018-09-14', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('25', 'V-36789012', 'Eduardo', 'Núñez', '1990-02-26', 'M', '0412-5678902', 'ejimenez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Redes', NULL, '0', '6', '3', '2020-04-08', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `funcionarios` VALUES ('26', 'V-37890123', 'Francisco', 'Romero', '1987-09-12', 'M', '0424-6789013', 'fromero@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Telecomunicaciones', NULL, '0', '6', '3', '2018-01-25', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('27', 'V-38901234', 'Marcos', 'Aguilar', '1991-05-03', 'M', '0414-7890124', 'maguilar@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Comunicaciones', NULL, '0', '6', '3', '2021-02-16', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('28', 'V-39012345', 'Víctor', 'Cruz', '1989-12-28', 'M', '0426-8901235', 'vcruz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Fibra Óptica', NULL, '0', '6', '3', '2019-11-07', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('29', 'V-40123456', 'Valentina', 'Moreno', '1993-03-14', 'F', '0412-9012346', 'vmoreno@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Atención al Cliente', NULL, '0', '5', '4', '2021-05-10', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('30', 'V-41234567', 'Camila', 'Guzmán', '1994-08-19', 'F', '0424-0123457', 'cguzman@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, '0', '5', '4', '2022-01-20', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('31', 'V-42345678', 'Daniela', 'Vega', '1992-01-25', 'F', '0414-1234569', 'dvega@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', NULL, '0', '5', '4', '2020-09-15', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('32', 'V-43456789', 'Gabriela', 'Paredes', '1995-06-11', 'F', '0426-2345670', 'gparedes@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', NULL, '0', '5', '4', '2022-07-05', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('33', 'V-44567890', 'Natalia', 'Campos', '1993-11-07', 'F', '0412-3456780', 'ncampos@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, '0', '5', '4', '2021-10-18', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('34', 'V-45678901', 'Andrea', 'Ramos', '1994-04-22', 'F', '0424-4567891', 'aramos@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, '0', '5', '4', '2022-03-12', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('35', 'V-46789012', 'Óscar', 'Fuentes', '1987-10-16', 'M', '0414-5678903', 'ofuentes@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', NULL, '0', '6', '5', '2018-06-28', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('36', 'V-47890123', 'Iván', 'Salazar', '1988-05-29', 'M', '0426-6789014', 'isalazar@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero Electrónico', NULL, '0', '6', '5', '2019-04-15', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('37', 'V-48901234', 'Emilio', 'Cortés', '1990-12-04', 'M', '0412-7890125', 'ecortes@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Reparación', NULL, '0', '6', '5', '2020-08-20', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('38', 'V-49012345', 'Adrián', 'Peña', '1986-07-18', 'M', '0424-8901236', 'apena@ispeb.gob.ve', NULL, 'TSU', 'Ingeniero Electrónico', NULL, '0', '6', '5', '2017-10-05', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('39', 'V-50123456', 'Mauricio', 'Ibarra', '1989-02-23', 'M', '0414-9012347', 'mibarra@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Electrónica', NULL, '0', '6', '5', '2019-12-11', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('40', 'V-51234567', 'Rodrigo', 'Molina', '1991-09-08', 'M', '0426-0123458', 'rmolina@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Mantenimiento', NULL, '0', '6', '5', '2021-06-22', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('41', 'V-52345678', 'Esteban', 'Carrillo', '1988-04-13', 'M', '0412-1234570', 'ecarrillo@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', NULL, '0', '6', '5', '2018-12-18', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('42', 'V-53456789', 'Paola', 'Núñez', '1994-11-26', 'F', '0424-2345671', 'pnunez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', NULL, '0', '5', '2', '2022-04-14', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('43', 'V-54567890', 'Lucía', 'Espinoza', '1993-06-30', 'F', '0414-3456781', 'lespinoza@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', NULL, '0', '5', '1', '2021-08-25', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('44', 'V-55678901', 'María', 'Núñez', '1995-01-15', 'F', '0426-4567892', 'mbenitez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', NULL, '0', '5', '3', '2022-09-08', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `funcionarios` VALUES ('45', 'V-56789012', 'Alejandra', 'Soto', '1992-08-21', 'F', '0412-5678904', 'asoto@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', NULL, '0', '5', '5', '2020-11-30', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `funcionarios` VALUES ('49', '31087083', 'Albert Nazareth', 'Rodriguez Sifontes', '2005-11-08', 'M', '04249399005', 'albertrodrigrez7@gmail.com', 'Venezuela', NULL, NULL, NULL, '0', '6', '1', '2026-01-28', NULL, 'activo', '2026-01-28 22:14:15', '2026-01-28 22:14:15');

-- Tabla: historial_administrativo
DROP TABLE IF EXISTS `historial_administrativo`;
CREATE TABLE `historial_administrativo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) unsigned NOT NULL,
  `tipo_evento` enum('NOMBRAMIENTO','VACACION','AMONESTACION','REMOCION','TRASLADO','DESPIDO','RENUNCIA') NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'Para vacaciones: fecha de finalizaciÃÂ³n',
  `detalles` text DEFAULT NULL COMMENT 'JSON con datos especÃÂ­ficos: motivo, tipo_falta, sancion, etc.',
  `ruta_archivo_pdf` varchar(255) DEFAULT NULL,
  `nombre_archivo_original` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_funcionario` (`funcionario_id`),
  KEY `idx_tipo_evento` (`tipo_evento`),
  KEY `idx_fecha_evento` (`fecha_evento`),
  KEY `idx_fecha_fin` (`fecha_fin`),
  CONSTRAINT `historial_administrativo_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_administrativo_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: movimientos
DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) unsigned NOT NULL,
  `cargo_anterior_id` int(10) unsigned DEFAULT NULL,
  `cargo_nuevo_id` int(10) unsigned DEFAULT NULL,
  `departamento_anterior_id` int(10) unsigned DEFAULT NULL,
  `departamento_nuevo_id` int(10) unsigned DEFAULT NULL,
  `tipo_movimiento` enum('ascenso','traslado','descenso','rotacion') NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `documento_soporte` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cargo_anterior_id` (`cargo_anterior_id`),
  KEY `cargo_nuevo_id` (`cargo_nuevo_id`),
  KEY `departamento_anterior_id` (`departamento_anterior_id`),
  KEY `departamento_nuevo_id` (`departamento_nuevo_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_funcionario` (`funcionario_id`),
  KEY `idx_fecha` (`fecha_movimiento`),
  CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`cargo_anterior_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_ibfk_3` FOREIGN KEY (`cargo_nuevo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_ibfk_4` FOREIGN KEY (`departamento_anterior_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_ibfk_5` FOREIGN KEY (`departamento_nuevo_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_ibfk_6` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: preguntas_seguridad_catalogo
DROP TABLE IF EXISTS `preguntas_seguridad_catalogo`;
CREATE TABLE `preguntas_seguridad_catalogo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pregunta` varchar(255) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pregunta` (`pregunta`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de preguntas_seguridad_catalogo
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('1', '¿Cuál es el nombre de tu primera mascota?', '1', '1', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('2', '¿En qué ciudad naciste?', '1', '2', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('3', '¿Cuál es el apellido de soltera de tu madre?', '1', '3', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('4', '¿Cuál es el nombre de tu mejor amigo de la infancia?', '1', '4', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('5', '¿Cuál fue el nombre de tu primera escuela?', '1', '5', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('6', '¿Cuál es tu comida favorita?', '1', '6', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('7', '¿En qué año conociste a tu pareja?', '1', '7', '2026-01-21 00:19:39');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('8', '¿Cuál es el nombre de tu libro favorito?', '1', '8', '2026-01-21 00:19:39');

-- Tabla: sesiones
DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ultimo_acceso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `datos_sesion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_ultimo_acceso` (`ultimo_acceso`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) unsigned NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `funcionario_id` (`funcionario_id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_estado` (`estado`),
  KEY `idx_registro_completado` (`registro_completado`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de usuarios
INSERT INTO `usuarios` VALUES ('1', '1', 'crodriguez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'crodriguez@ispeb.gob.ve', NULL, NULL, '2026-01-29 23:50:49', '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-29 23:50:49');
INSERT INTO `usuarios` VALUES ('2', '2', 'mgonzalez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'mgonzalez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('3', '3', 'lmartinez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'lmartinez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('4', '4', 'aperez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'aperez@ispeb.gob.ve', NULL, NULL, '2026-01-21 00:37:08', '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:37:08');
INSERT INTO `usuarios` VALUES ('5', '5', 'jhernandez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'jhernandez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('6', '6', 'clopez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'clopez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('7', '7', 'pgarcia', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'pgarcia@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('8', '8', 'lramirez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'lramirez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('9', '9', 'storres', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'storres@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('10', '10', 'iflores', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'iflores@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('11', '11', 'msanchez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'msanchez@ispeb.gob.ve', NULL, NULL, '2026-01-21 00:37:33', '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:37:33');
INSERT INTO `usuarios` VALUES ('12', '12', 'rdiaz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rdiaz@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('13', '13', 'fmorales', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'fmorales@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('14', '14', 'acastro', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'acastro@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('15', '15', 'druiz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'druiz@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('16', '16', 'gortiz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'gortiz@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('17', '17', 'rvargas', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rvargas@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('18', '18', 'jmendoza', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'jmendoza@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('19', '19', 'asilva', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'asilva@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('20', '20', 'srojas', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'srojas@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('21', '21', 'hnavarro', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'hnavarro@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('22', '22', 'rmedina', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rmedina@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('23', '23', 'greyes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'greyes@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('24', '24', 'aguerrero', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'aguerrero@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('25', '25', 'ejimenez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ejimenez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('26', '26', 'fromero', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'fromero@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('27', '27', 'maguilar', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'maguilar@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('28', '28', 'vcruz', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'vcruz@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('29', '29', 'vmoreno', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'vmoreno@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('30', '30', 'cguzman', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'cguzman@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('31', '31', 'dvega', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'dvega@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('32', '32', 'gparedes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'gparedes@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('33', '33', 'ncampos', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ncampos@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('34', '34', 'aramos', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'aramos@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('35', '35', 'ofuentes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ofuentes@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('36', '36', 'isalazar', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'isalazar@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('37', '37', 'ecortes', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ecortes@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('38', '38', 'apena', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'apena@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('39', '39', 'mibarra', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'mibarra@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('40', '40', 'rmolina', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'rmolina@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('41', '41', 'ecarrillo', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'ecarrillo@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('42', '42', 'pnunez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'pnunez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('43', '43', 'lespinoza', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'lespinoza@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('44', '44', 'mbenitez', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'mbenitez@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('45', '45', 'asoto', '$2y$10$xa40rsKwiZjrzgt/sb4lTuviA61o3tFcmUjvJnJrUiepBVvjAKj6O', 'asoto@ispeb.gob.ve', NULL, NULL, NULL, '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `usuarios` VALUES ('53', '49', 'arodriguez', '$2y$10$QyNu3maaw4xihlQUfw22I.QzPr6GCrIEqE4u/C.r6VTJgOBDgooQm', NULL, NULL, NULL, '2026-01-29 07:46:30', '0', NULL, 'activo', '1', '¿Cuál es el nombre de tu primera mascota?', '$2y$10$4zTuJuxsb2s/FkkzGJS08enuc.dNCCA90hNLehbirmHAhfdO8/th6', '¿En qué ciudad naciste?', '$2y$10$yU5703P2te9zL0rbj2gVK.a5VRKPcXrA2O/rcRC/fzfE.pBqF2xFq', '¿Cuál es el nombre de tu mejor amigo de la infancia?', '$2y$10$KApVkv8SAsKV78fG7qlb3uvmgaB9qJThnNqgClDTlNS7xph90gYuK', '2026-01-29 07:40:18', '2026-01-29 07:46:30');

-- Tabla: vista_funcionarios_completo
DROP TABLE IF EXISTS `vista_funcionarios_completo`;
;

-- Datos de vista_funcionarios_completo
INSERT INTO `vista_funcionarios_completo` VALUES ('1', 'V-12345678', 'Carlos Rodríguez', 'Carlos', 'Rodríguez', '1980-03-15', '45', 'M', '0412-1234567', 'crodriguez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', '0', 'Director de la Dirección', '1', 'Sistemas', '2015-01-10', '11', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `vista_funcionarios_completo` VALUES ('3', 'V-14567890', 'Luis Núñez', 'Luis', 'Núñez', '1978-11-08', '47', 'M', '0414-3456789', 'lmartinez@ispeb.gob.ve', NULL, '', 'Magíster en Gestión Tecnológica', '0', 'Director de la Dirección', '1', 'Soporte Técnico', '2014-06-20', '11', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('2', 'V-13456789', 'María Núñez', 'María', 'Núñez', '1982-07-22', '43', 'F', '0424-2345678', 'mgonzalez@ispeb.gob.ve', NULL, 'Postgrado', 'Especialista en Redes', '0', 'Jefe de Dirección', '1', 'Sistemas', '2016-03-15', '9', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `vista_funcionarios_completo` VALUES ('4', 'V-15678901', 'Ana Pérez', 'Ana', 'Pérez', '1985-05-12', '40', 'F', '0426-4567890', 'aperez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniera en Electrónica', '0', 'Jefe de Departamento', '2', 'Soporte Técnico', '2017-02-14', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('5', 'V-16789012', 'José Núñez', 'José', 'Núñez', '1983-09-25', '42', 'M', '0412-5678901', 'jhernandez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Telecomunicaciones', '0', 'Jefe de Departamento', '2', 'Redes y Telecomunicaciones', '2016-08-10', '9', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `vista_funcionarios_completo` VALUES ('6', 'V-17890123', 'Carmen López', 'Carmen', 'López', '1986-01-30', '39', 'F', '0424-6789012', 'clopez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', '0', 'Jefe de Departamento', '2', 'Atención al Usuario', '2018-04-05', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('7', 'V-18901234', 'Pedro García', 'Pedro', 'García', '1984-12-18', '41', 'M', '0414-7890123', 'pgarcia@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero Electrónico', '0', 'Jefe de Departamento', '2', 'Reparaciones Electrónicas', '2017-11-22', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('8', 'V-19012345', 'Laura Ramírez', 'Laura', 'Ramírez', '1990-04-08', '35', 'F', '0426-8901234', 'lramirez@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', '0', 'Secretaria', '2', 'Sistemas', '2019-01-15', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `vista_funcionarios_completo` VALUES ('9', 'V-20123456', 'Sofía Torres', 'Sofía', 'Torres', '1992-08-14', '33', 'F', '0412-9012345', 'storres@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', '0', 'Secretaria', '2', 'Soporte Técnico', '2020-03-10', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('10', 'V-21234567', 'Isabella Flores', 'Isabella', 'Flores', '1991-06-20', '34', 'F', '0424-0123456', 'iflores@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', '0', 'Secretaria', '2', 'Atención al Usuario', '2019-09-05', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('29', 'V-40123456', 'Valentina Moreno', 'Valentina', 'Moreno', '1993-03-14', '32', 'F', '0412-9012346', 'vmoreno@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Atención al Cliente', '0', 'Asistente', '3', 'Atención al Usuario', '2021-05-10', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('30', 'V-41234567', 'Camila Guzmán', 'Camila', 'Guzmán', '1994-08-19', '31', 'F', '0424-0123457', 'cguzman@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', '0', 'Asistente', '3', 'Atención al Usuario', '2022-01-20', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('31', 'V-42345678', 'Daniela Vega', 'Daniela', 'Vega', '1992-01-25', '34', 'F', '0414-1234569', 'dvega@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', '0', 'Asistente', '3', 'Atención al Usuario', '2020-09-15', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('32', 'V-43456789', 'Gabriela Paredes', 'Gabriela', 'Paredes', '1995-06-11', '30', 'F', '0426-2345670', 'gparedes@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', '0', 'Asistente', '3', 'Atención al Usuario', '2022-07-05', '3', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('33', 'V-44567890', 'Natalia Campos', 'Natalia', 'Campos', '1993-11-07', '32', 'F', '0412-3456780', 'ncampos@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', '0', 'Asistente', '3', 'Atención al Usuario', '2021-10-18', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('34', 'V-45678901', 'Andrea Ramos', 'Andrea', 'Ramos', '1994-04-22', '31', 'F', '0424-4567891', 'aramos@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', '0', 'Asistente', '3', 'Atención al Usuario', '2022-03-12', '3', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('42', 'V-53456789', 'Paola Núñez', 'Paola', 'Núñez', '1994-11-26', '31', 'F', '0424-2345671', 'pnunez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Administración', '0', 'Asistente', '3', 'Sistemas', '2022-04-14', '3', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('43', 'V-54567890', 'Lucía Espinoza', 'Lucía', 'Espinoza', '1993-06-30', '32', 'F', '0414-3456781', 'lespinoza@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Ciencias', '0', 'Asistente', '3', 'Soporte Técnico', '2021-08-25', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('44', 'V-55678901', 'María Núñez', 'María', 'Núñez', '1995-01-15', '31', 'F', '0426-4567892', 'mbenitez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', '0', 'Asistente', '3', 'Redes y Telecomunicaciones', '2022-09-08', '3', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('45', 'V-56789012', 'Alejandra Soto', 'Alejandra', 'Soto', '1992-08-21', '33', 'F', '0412-5678904', 'asoto@ispeb.gob.ve', NULL, 'Bachiller', 'Bachiller en Humanidades', '0', 'Asistente', '3', 'Reparaciones Electrónicas', '2020-11-30', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('11', 'V-22345678', 'Miguel Núñez', 'Miguel', 'Núñez', '1988-02-11', '37', 'M', '0414-1234567', 'msanchez@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Computación', '0', 'Técnico', '3', 'Soporte Técnico', '2018-05-20', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('12', 'V-23456789', 'Roberto Díaz', 'Roberto', 'Díaz', '1989-07-16', '36', 'M', '0426-2345678', 'rdiaz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', '0', 'Técnico', '3', 'Soporte Técnico', '2019-02-12', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('13', 'V-24567890', 'Fernando Morales', 'Fernando', 'Morales', '1987-11-22', '38', 'M', '0412-3456789', 'fmorales@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Computación', '0', 'Técnico', '3', 'Soporte Técnico', '2017-08-18', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('14', 'V-25678901', 'Andrés Castro', 'Andrés', 'Castro', '1990-03-28', '35', 'M', '0424-4567890', 'acastro@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Sistemas', '0', 'Técnico', '3', 'Soporte Técnico', '2020-01-22', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('15', 'V-26789012', 'Daniel Ruiz', 'Daniel', 'Ruiz', '1991-09-05', '34', 'M', '0414-5678901', 'druiz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Redes', '0', 'Técnico', '3', 'Soporte Técnico', '2020-06-15', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('16', 'V-27890123', 'Gabriel Ortiz', 'Gabriel', 'Ortiz', '1986-12-30', '39', 'M', '0426-6789012', 'gortiz@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', '0', 'Técnico', '3', 'Soporte Técnico', '2018-11-08', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('17', 'V-28901234', 'Ricardo Vargas', 'Ricardo', 'Vargas', '1989-05-17', '36', 'M', '0412-7890123', 'rvargas@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Informática', '0', 'Técnico', '3', 'Soporte Técnico', '2019-07-25', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('18', 'V-29012345', 'Javier Mendoza', 'Javier', 'Mendoza', '1988-08-24', '37', 'M', '0424-8901234', 'jmendoza@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Sistemas', '0', 'Técnico', '3', 'Sistemas', '2018-10-12', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('19', 'V-30123456', 'Alberto Silva', 'Alberto', 'Silva', '1990-01-19', '36', 'M', '0414-9012345', 'asilva@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Programación', '0', 'Técnico', '3', 'Sistemas', '2020-02-28', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('20', 'V-31234567', 'Sergio Rojas', 'Sergio', 'Rojas', '1987-06-13', '38', 'M', '0426-0123456', 'srojas@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Computación', '0', 'Técnico', '3', 'Sistemas', '2017-12-05', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('21', 'V-32345678', 'Héctor Navarro', 'Héctor', 'Navarro', '1991-10-07', '34', 'M', '0412-1234568', 'hnavarro@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Desarrollo Web', '0', 'Técnico', '3', 'Sistemas', '2021-03-18', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('22', 'V-33456789', 'Raúl Medina', 'Raúl', 'Medina', '1989-04-21', '36', 'M', '0424-2345679', 'rmedina@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Software', '0', 'Técnico', '3', 'Sistemas', '2019-08-22', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `vista_funcionarios_completo` VALUES ('23', 'V-34567890', 'Gustavo Reyes', 'Gustavo', 'Reyes', '1986-11-15', '39', 'M', '0414-3456790', 'greyes@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Telecomunicaciones', '0', 'Técnico', '3', 'Redes y Telecomunicaciones', '2017-05-30', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('24', 'V-35678901', 'Arturo Guerrero', 'Arturo', 'Guerrero', '1988-07-09', '37', 'M', '0426-4567891', 'aguerrero@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Redes', '0', 'Técnico', '3', 'Redes y Telecomunicaciones', '2018-09-14', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('25', 'V-36789012', 'Eduardo Núñez', 'Eduardo', 'Núñez', '1990-02-26', '35', 'M', '0412-5678902', 'ejimenez@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Redes', '0', 'Técnico', '3', 'Redes y Telecomunicaciones', '2020-04-08', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:31:06');
INSERT INTO `vista_funcionarios_completo` VALUES ('26', 'V-37890123', 'Francisco Romero', 'Francisco', 'Romero', '1987-09-12', '38', 'M', '0424-6789013', 'fromero@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Telecomunicaciones', '0', 'Técnico', '3', 'Redes y Telecomunicaciones', '2018-01-25', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('27', 'V-38901234', 'Marcos Aguilar', 'Marcos', 'Aguilar', '1991-05-03', '34', 'M', '0414-7890124', 'maguilar@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Comunicaciones', '0', 'Técnico', '3', 'Redes y Telecomunicaciones', '2021-02-16', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:19:39');
INSERT INTO `vista_funcionarios_completo` VALUES ('28', 'V-39012345', 'Víctor Cruz', 'Víctor', 'Cruz', '1989-12-28', '36', 'M', '0426-8901235', 'vcruz@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Fibra Óptica', '0', 'Técnico', '3', 'Redes y Telecomunicaciones', '2019-11-07', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('35', 'V-46789012', 'Óscar Fuentes', 'Óscar', 'Fuentes', '1987-10-16', '38', 'M', '0414-5678903', 'ofuentes@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2018-06-28', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('36', 'V-47890123', 'Iván Salazar', 'Iván', 'Salazar', '1988-05-29', '37', 'M', '0426-6789014', 'isalazar@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero Electrónico', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2019-04-15', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('37', 'V-48901234', 'Emilio Cortés', 'Emilio', 'Cortés', '1990-12-04', '35', 'M', '0412-7890125', 'ecortes@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Reparación', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2020-08-20', '5', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('38', 'V-49012345', 'Adrián Peña', 'Adrián', 'Peña', '1986-07-18', '39', 'M', '0424-8901236', 'apena@ispeb.gob.ve', NULL, 'TSU', 'Ingeniero Electrónico', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2017-10-05', '8', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('39', 'V-50123456', 'Mauricio Ibarra', 'Mauricio', 'Ibarra', '1989-02-23', '36', 'M', '0414-9012347', 'mibarra@ispeb.gob.ve', NULL, 'Universitario', 'Ingeniero en Electrónica', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2019-12-11', '6', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('40', 'V-51234567', 'Rodrigo Molina', 'Rodrigo', 'Molina', '1991-09-08', '34', 'M', '0426-0123458', 'rmolina@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Mantenimiento', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2021-06-22', '4', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('41', 'V-52345678', 'Esteban Carrillo', 'Esteban', 'Carrillo', '1988-04-13', '37', 'M', '0412-1234570', 'ecarrillo@ispeb.gob.ve', NULL, 'TSU', 'Técnico en Electrónica', '0', 'Técnico', '3', 'Reparaciones Electrónicas', '2018-12-18', '7', 'default-avatar.png', 'activo', '2026-01-21 00:19:39', '2026-01-21 00:34:29');
INSERT INTO `vista_funcionarios_completo` VALUES ('49', '31087083', 'Albert Nazareth Rodriguez Sifontes', 'Albert Nazareth', 'Rodriguez Sifontes', '2005-11-08', '20', 'M', '04249399005', 'albertrodrigrez7@gmail.com', 'Venezuela', NULL, NULL, '0', 'Técnico', '3', 'Soporte Técnico', '2026-01-28', '0', NULL, 'activo', '2026-01-28 22:14:15', '2026-01-28 22:14:15');

-- Tabla: vista_usuarios_activos
DROP TABLE IF EXISTS `vista_usuarios_activos`;
;

-- Datos de vista_usuarios_activos
INSERT INTO `vista_usuarios_activos` VALUES ('1', 'crodriguez', 'activo', '2026-01-29 23:50:49', '1', 'V-12345678', 'Carlos Rodríguez', 'Director de la Dirección', '1', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('3', 'lmartinez', 'activo', NULL, '3', 'V-14567890', 'Luis Núñez', 'Director de la Dirección', '1', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('2', 'mgonzalez', 'activo', NULL, '2', 'V-13456789', 'María Núñez', 'Jefe de Dirección', '1', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('4', 'aperez', 'activo', '2026-01-21 00:37:08', '4', 'V-15678901', 'Ana Pérez', 'Jefe de Departamento', '2', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('5', 'jhernandez', 'activo', NULL, '5', 'V-16789012', 'José Núñez', 'Jefe de Departamento', '2', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('6', 'clopez', 'activo', NULL, '6', 'V-17890123', 'Carmen López', 'Jefe de Departamento', '2', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('7', 'pgarcia', 'activo', NULL, '7', 'V-18901234', 'Pedro García', 'Jefe de Departamento', '2', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('8', 'lramirez', 'activo', NULL, '8', 'V-19012345', 'Laura Ramírez', 'Secretaria', '2', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('9', 'storres', 'activo', NULL, '9', 'V-20123456', 'Sofía Torres', 'Secretaria', '2', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('10', 'iflores', 'activo', NULL, '10', 'V-21234567', 'Isabella Flores', 'Secretaria', '2', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('29', 'vmoreno', 'activo', NULL, '29', 'V-40123456', 'Valentina Moreno', 'Asistente', '3', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('30', 'cguzman', 'activo', NULL, '30', 'V-41234567', 'Camila Guzmán', 'Asistente', '3', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('31', 'dvega', 'activo', NULL, '31', 'V-42345678', 'Daniela Vega', 'Asistente', '3', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('32', 'gparedes', 'activo', NULL, '32', 'V-43456789', 'Gabriela Paredes', 'Asistente', '3', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('33', 'ncampos', 'activo', NULL, '33', 'V-44567890', 'Natalia Campos', 'Asistente', '3', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('34', 'aramos', 'activo', NULL, '34', 'V-45678901', 'Andrea Ramos', 'Asistente', '3', 'Atención al Usuario');
INSERT INTO `vista_usuarios_activos` VALUES ('42', 'pnunez', 'activo', NULL, '42', 'V-53456789', 'Paola Núñez', 'Asistente', '3', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('43', 'lespinoza', 'activo', NULL, '43', 'V-54567890', 'Lucía Espinoza', 'Asistente', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('44', 'mbenitez', 'activo', NULL, '44', 'V-55678901', 'María Núñez', 'Asistente', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('45', 'asoto', 'activo', NULL, '45', 'V-56789012', 'Alejandra Soto', 'Asistente', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('11', 'msanchez', 'activo', '2026-01-21 00:37:33', '11', 'V-22345678', 'Miguel Núñez', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('12', 'rdiaz', 'activo', NULL, '12', 'V-23456789', 'Roberto Díaz', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('13', 'fmorales', 'activo', NULL, '13', 'V-24567890', 'Fernando Morales', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('14', 'acastro', 'activo', NULL, '14', 'V-25678901', 'Andrés Castro', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('15', 'druiz', 'activo', NULL, '15', 'V-26789012', 'Daniel Ruiz', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('16', 'gortiz', 'activo', NULL, '16', 'V-27890123', 'Gabriel Ortiz', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('17', 'rvargas', 'activo', NULL, '17', 'V-28901234', 'Ricardo Vargas', 'Técnico', '3', 'Soporte Técnico');
INSERT INTO `vista_usuarios_activos` VALUES ('18', 'jmendoza', 'activo', NULL, '18', 'V-29012345', 'Javier Mendoza', 'Técnico', '3', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('19', 'asilva', 'activo', NULL, '19', 'V-30123456', 'Alberto Silva', 'Técnico', '3', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('20', 'srojas', 'activo', NULL, '20', 'V-31234567', 'Sergio Rojas', 'Técnico', '3', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('21', 'hnavarro', 'activo', NULL, '21', 'V-32345678', 'Héctor Navarro', 'Técnico', '3', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('22', 'rmedina', 'activo', NULL, '22', 'V-33456789', 'Raúl Medina', 'Técnico', '3', 'Sistemas');
INSERT INTO `vista_usuarios_activos` VALUES ('23', 'greyes', 'activo', NULL, '23', 'V-34567890', 'Gustavo Reyes', 'Técnico', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('24', 'aguerrero', 'activo', NULL, '24', 'V-35678901', 'Arturo Guerrero', 'Técnico', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('25', 'ejimenez', 'activo', NULL, '25', 'V-36789012', 'Eduardo Núñez', 'Técnico', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('26', 'fromero', 'activo', NULL, '26', 'V-37890123', 'Francisco Romero', 'Técnico', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('27', 'maguilar', 'activo', NULL, '27', 'V-38901234', 'Marcos Aguilar', 'Técnico', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('28', 'vcruz', 'activo', NULL, '28', 'V-39012345', 'Víctor Cruz', 'Técnico', '3', 'Redes y Telecomunicaciones');
INSERT INTO `vista_usuarios_activos` VALUES ('35', 'ofuentes', 'activo', NULL, '35', 'V-46789012', 'Óscar Fuentes', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('36', 'isalazar', 'activo', NULL, '36', 'V-47890123', 'Iván Salazar', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('37', 'ecortes', 'activo', NULL, '37', 'V-48901234', 'Emilio Cortés', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('38', 'apena', 'activo', NULL, '38', 'V-49012345', 'Adrián Peña', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('39', 'mibarra', 'activo', NULL, '39', 'V-50123456', 'Mauricio Ibarra', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('40', 'rmolina', 'activo', NULL, '40', 'V-51234567', 'Rodrigo Molina', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('41', 'ecarrillo', 'activo', NULL, '41', 'V-52345678', 'Esteban Carrillo', 'Técnico', '3', 'Reparaciones Electrónicas');
INSERT INTO `vista_usuarios_activos` VALUES ('53', 'arodriguez', 'activo', '2026-01-29 07:46:30', '49', '31087083', 'Albert Nazareth Rodriguez Sifontes', 'Técnico', '3', 'Soporte Técnico');

SET FOREIGN_KEY_CHECKS=1;
