-- =============================================
-- Stored Procedures para Sistema ISPEB
-- =============================================

DELIMITER $$

-- =============================================
-- Procedimiento: sp_validar_retiro
-- Descripción: Valida si un funcionario puede ser retirado
--              verificando que no tenga activos asignados
-- Parámetros: p_funcionario_id - ID del funcionario
-- Retorna: total_activos - Número de activos asignados
-- =============================================
DROP PROCEDURE IF EXISTS sp_validar_retiro$$

CREATE PROCEDURE sp_validar_retiro(
    IN p_funcionario_id INT UNSIGNED
)
BEGIN
    SELECT 
        COUNT(*) as total_activos,
        GROUP_CONCAT(
            CONCAT(tipo, ' - ', marca, ' ', modelo, ' (', serial, ')')
            SEPARATOR ', '
        ) as lista_activos
    FROM activos_tecnologicos
    WHERE funcionario_id = p_funcionario_id
    AND estado = 'Asignado';
END$$

DELIMITER ;
