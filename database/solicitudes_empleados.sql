-- ============================================================
-- TABLA: solicitudes_empleados
-- Sistema ISPEB - Portal de Autogestión Nivel 3
-- Flujo: Empleado solicita → RRHH/Admin aprueba con documento
--
-- NOTA: funcionarios.id y usuarios.id son INT(10) UNSIGNED,
--       por eso funcionario_id y revisado_por deben serlo también.
-- ============================================================

CREATE TABLE IF NOT EXISTS `solicitudes_empleados` (
  `id`                       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `funcionario_id`           INT(10) UNSIGNED NOT NULL            COMMENT 'FK → funcionarios.id',
  `tipo_solicitud`           ENUM('vacaciones','permiso') NOT NULL,
  `fecha_inicio`             DATE           NOT NULL,
  `fecha_fin`                DATE           NOT NULL,
  `motivo`                   TEXT           NOT NULL,
  `estado`                   ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `revisado_por`             INT(10) UNSIGNED NULL               COMMENT 'FK → usuarios.id (quien aprobó/rechazó)',
  `observaciones_respuesta`  TEXT           NULL                 COMMENT 'Respuesta del revisor',
  `ruta_archivo_aprobacion`  VARCHAR(500)   NULL                 COMMENT 'Ruta relativa del memo firmado/sellado',
  `created_at`               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  CONSTRAINT `fk_solicitudes_funcionario`
    FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_solicitudes_revisor`
    FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  KEY `idx_funcionario`    (`funcionario_id`),
  KEY `idx_estado`         (`estado`),
  KEY `idx_tipo_solicitud` (`tipo_solicitud`),
  KEY `idx_created_at`     (`created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Solicitudes de vacaciones y permisos enviadas por empleados Nivel 3';
