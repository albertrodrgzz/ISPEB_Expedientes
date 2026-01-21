-- =====================================================
-- RESPALDO DE BASE DE DATOS - ISPEB
-- Fecha: 2026-01-19 19:03:31
-- Generado por: Administrador Sistema
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de auditoria
INSERT INTO `auditoria` VALUES ('1', NULL, 'LOGIN_FALLIDO', 'usuarios', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 18:33:12');
INSERT INTO `auditoria` VALUES ('2', NULL, 'LOGIN_FALLIDO', 'usuarios', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 18:33:34');
INSERT INTO `auditoria` VALUES ('3', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 18:36:02');
INSERT INTO `auditoria` VALUES ('4', '1', 'LOGOUT', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 18:36:12');
INSERT INTO `auditoria` VALUES ('5', '1', 'LOGIN', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 18:46:43');
INSERT INTO `auditoria` VALUES ('6', '1', 'GENERAR_RESPALDO_BD', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 19:03:31');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de cargos
INSERT INTO `cargos` VALUES ('1', 'Director de la Dirección', '1', 'Máxima autoridad de la Dirección de Telemática - Acceso total al sistema', '2026-01-19 18:33:00');
INSERT INTO `cargos` VALUES ('2', 'Jefe de Dirección', '1', 'Segundo al mando - Acceso total al sistema', '2026-01-19 18:33:00');
INSERT INTO `cargos` VALUES ('3', 'Jefe de Departamento', '2', 'Responsable de un departamento específico - Acceso operativo limitado a su departamento', '2026-01-19 18:33:00');
INSERT INTO `cargos` VALUES ('4', 'Secretaria', '2', 'Personal administrativo - Acceso operativo para gestión de expedientes', '2026-01-19 18:33:00');
INSERT INTO `cargos` VALUES ('5', 'Asistente', '3', 'Personal de apoyo - Solo lectura y descarga de documentos', '2026-01-19 18:33:00');
INSERT INTO `cargos` VALUES ('6', 'Técnico', '3', 'Personal técnico - Solo lectura y descarga de documentos', '2026-01-19 18:33:00');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de departamentos
INSERT INTO `departamentos` VALUES ('1', 'Soporte Técnico', 'Departamento encargado del soporte técnico a usuarios internos y externos', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');
INSERT INTO `departamentos` VALUES ('2', 'Sistemas', 'Departamento de desarrollo y mantenimiento de sistemas informáticos', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');
INSERT INTO `departamentos` VALUES ('3', 'Redes y Telecomunicaciones', 'Departamento de infraestructura de redes y comunicaciones', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');
INSERT INTO `departamentos` VALUES ('4', 'Atención al Usuario', 'Departamento de atención y servicio al usuario final', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');
INSERT INTO `departamentos` VALUES ('5', 'Reparaciones Electrónicas', 'Departamento de reparación y mantenimiento de equipos electrónicos', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');

-- Tabla: expedientes_docs
DROP TABLE IF EXISTS `expedientes_docs`;
CREATE TABLE `expedientes_docs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(10) unsigned NOT NULL,
  `tipo_documento` enum('nombramiento','vacaciones','reposo','amonestacion','despido','renuncia','otro') NOT NULL,
  `categoria` varchar(100) DEFAULT NULL COMMENT 'Ej: Contrato Fijo, Temporal, Verbal, Escrita',
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `dias_totales` int(11) DEFAULT NULL,
  `tipo_falta` varchar(100) DEFAULT NULL,
  `sancion_aplicada` varchar(255) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `aprobado_por` int(10) unsigned DEFAULT NULL COMMENT 'ID del usuario que aprobó',
  `fecha_aprobacion` datetime DEFAULT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  `nombre_archivo_original` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamano_archivo` int(11) DEFAULT NULL COMMENT 'Tamaño en bytes',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `aprobado_por` (`aprobado_por`),
  KEY `idx_funcionario` (`funcionario_id`),
  KEY `idx_tipo_documento` (`tipo_documento`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_fecha_fin` (`fecha_fin`),
  CONSTRAINT `expedientes_docs_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expedientes_docs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expedientes_docs_ibfk_3` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de funcionarios
INSERT INTO `funcionarios` VALUES ('1', 'V-12345678', 'Administrador', 'Sistema', '1980-01-01', 'M', '0412-1234567', 'admin@ispeb.gob.ve', NULL, '1', '2', '2020-01-01', 'default-avatar.png', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');

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
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('1', '¿Cuál es el nombre de tu primera mascota?', '1', '1', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('2', '¿En qué ciudad naciste?', '1', '2', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('3', '¿Cuál es el apellido de soltera de tu madre?', '1', '3', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('4', '¿Cuál es el nombre de tu mejor amigo de la infancia?', '1', '4', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('5', '¿Cuál fue el nombre de tu primera escuela?', '1', '5', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('6', '¿Cuál es tu comida favorita?', '1', '6', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('7', '¿En qué año conociste a tu pareja?', '1', '7', '2026-01-19 18:33:00');
INSERT INTO `preguntas_seguridad_catalogo` VALUES ('8', '¿Cuál es el nombre de tu libro favorito?', '1', '8', '2026-01-19 18:33:00');

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
  `registro_completado` tinyint(1) DEFAULT 0 COMMENT 'Indica si el usuario completó su registro',
  `pregunta_seguridad_1` varchar(255) DEFAULT NULL COMMENT 'Primera pregunta de seguridad',
  `respuesta_seguridad_1` varchar(255) DEFAULT NULL COMMENT 'Hash de la primera respuesta',
  `pregunta_seguridad_2` varchar(255) DEFAULT NULL COMMENT 'Segunda pregunta de seguridad',
  `respuesta_seguridad_2` varchar(255) DEFAULT NULL COMMENT 'Hash de la segunda respuesta',
  `pregunta_seguridad_3` varchar(255) DEFAULT NULL COMMENT 'Tercera pregunta de seguridad',
  `respuesta_seguridad_3` varchar(255) DEFAULT NULL COMMENT 'Hash de la tercera respuesta',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `funcionario_id` (`funcionario_id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_estado` (`estado`),
  KEY `idx_registro_completado` (`registro_completado`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de usuarios
INSERT INTO `usuarios` VALUES ('1', '1', 'admin', 'admin123', 'admin@ispeb.gob.ve', NULL, NULL, '2026-01-19 18:46:43', '0', NULL, 'activo', '1', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-19 18:33:00', '2026-01-19 18:46:43');

-- Tabla: vista_funcionarios_completo
DROP TABLE IF EXISTS `vista_funcionarios_completo`;
;

-- Datos de vista_funcionarios_completo
INSERT INTO `vista_funcionarios_completo` VALUES ('1', 'V-12345678', 'Administrador Sistema', 'Administrador', 'Sistema', '1980-01-01', '46', 'M', '0412-1234567', 'admin@ispeb.gob.ve', NULL, 'Director de la Dirección', '1', 'Sistemas', '2020-01-01', '6', 'default-avatar.png', 'activo', '2026-01-19 18:33:00', '2026-01-19 18:33:00');

-- Tabla: vista_usuarios_activos
DROP TABLE IF EXISTS `vista_usuarios_activos`;
;

-- Datos de vista_usuarios_activos
INSERT INTO `vista_usuarios_activos` VALUES ('1', 'admin', 'activo', '2026-01-19 18:46:43', '1', 'V-12345678', 'Administrador Sistema', 'Director de la Dirección', '1', 'Sistemas');

-- Tabla: vista_usuarios_pendientes
DROP TABLE IF EXISTS `vista_usuarios_pendientes`;
;

SET FOREIGN_KEY_CHECKS=1;
