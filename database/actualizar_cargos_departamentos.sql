-- ============================================================
-- TAREA 1: Actualizar Departamentos y Cargos en la BD SIGED
-- Ejecutar en phpMyAdmin → ispeb_expedientes
-- ============================================================

-- 1. Agregar "Dirección de Telemática" como nuevo departamento
INSERT INTO `departamentos` (`nombre`, `descripcion`, `estado`)
VALUES ('Dirección de Telemática', 'Dirección General de Telemática', 'activo')
ON DUPLICATE KEY UPDATE `estado` = 'activo';

-- 2. Eliminar cargos: "Director de Dirección" (id=1 es Director de la Dirección, verificar),
--    "Asistente" (id=5), "Pasante de Pruebas" (id=7)
--    NOTA: Antes de borrar, reasignar funcionarios que tengan esos cargos al cargo "Técnico" (id=6)

-- Reasignar funcionarios con cargo "Asistente" (id=5) → Técnico (id=6)
UPDATE `funcionarios` SET `cargo_id` = 6 WHERE `cargo_id` = 5;

-- Reasignar funcionarios con cargo "Pasante de Pruebas" (id=7) → Técnico (id=6)
UPDATE `funcionarios` SET `cargo_id` = 6 WHERE `cargo_id` = 7;

-- Eliminar cargo "Asistente" (id=5)
DELETE FROM `cargos` WHERE `nombre_cargo` = 'Asistente';

-- Eliminar cargo "Pasante de Pruebas" (id=7)
DELETE FROM `cargos` WHERE `nombre_cargo` = 'Pasante de Pruebas';

-- 3. Agregar cargo "Analista" con nivel_acceso = 3
INSERT INTO `cargos` (`nombre_cargo`, `nivel_acceso`, `descripcion`)
VALUES ('Analista', 3, 'Analista de sistemas y procesos')
ON DUPLICATE KEY UPDATE `nivel_acceso` = 3;

-- 4. Confirmar nivel_acceso correcto para cargos existentes
UPDATE `cargos` SET `nivel_acceso` = 1 WHERE `nombre_cargo` = 'Jefe de Dirección';
UPDATE `cargos` SET `nivel_acceso` = 2 WHERE `nombre_cargo` = 'Jefe de Departamento';
UPDATE `cargos` SET `nivel_acceso` = 2 WHERE `nombre_cargo` = 'Secretaria';

-- ============================================================
-- FIN DE SCRIPT
-- ============================================================
