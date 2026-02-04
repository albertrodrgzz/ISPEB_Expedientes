-- ============================================
-- Script de Verificación - Sistema ISPEB v3.1
-- ============================================
-- Este script verifica que la limpieza estructural
-- se haya realizado correctamente.
--
-- INSTRUCCIONES:
-- 1. Ejecutar este script después de importar ispeb_expedientes.sql
-- 2. Revisar los resultados de cada consulta
-- 3. Todos los resultados deben coincidir con lo esperado

USE ispeb_expedientes;

-- ============================================
-- 1. Verificar que NO exista la tabla activos_tecnologicos
-- ============================================
SELECT 
    'Tabla activos_tecnologicos' AS verificacion,
    CASE 
        WHEN COUNT(*) = 0 THEN 'CORRECTO: Tabla eliminada'
        ELSE 'ERROR: La tabla aun existe'
    END AS resultado
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'ispeb_expedientes' 
AND TABLE_NAME = 'activos_tecnologicos';

-- ============================================
-- 2. Verificar que NO exista el procedimiento sp_validar_retiro
-- ============================================
SELECT 
    'Procedimiento sp_validar_retiro' AS verificacion,
    CASE 
        WHEN COUNT(*) = 0 THEN 'CORRECTO: Procedimiento eliminado'
        ELSE 'ERROR: El procedimiento aun existe'
    END AS resultado
FROM information_schema.ROUTINES 
WHERE ROUTINE_SCHEMA = 'ispeb_expedientes' 
AND ROUTINE_NAME = 'sp_validar_retiro';

-- ============================================
-- 3. Verificar columna detalles en historial_administrativo
-- ============================================
SELECT 
    'Columna detalles (tipo de dato)' AS verificacion,
    CASE 
        WHEN DATA_TYPE = 'longtext' THEN 'CORRECTO: Es LONGTEXT'
        ELSE CONCAT('ERROR: Es ', UPPER(DATA_TYPE))
    END AS resultado
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ispeb_expedientes' 
AND TABLE_NAME = 'historial_administrativo' 
AND COLUMN_NAME = 'detalles';

-- ============================================
-- 4. Verificar validación JSON en columna detalles
-- ============================================
SELECT 
    'Validacion JSON en detalles' AS verificacion,
    CASE 
        WHEN COUNT(*) > 0 THEN 'CORRECTO: Validacion JSON activa'
        ELSE 'ADVERTENCIA: Sin validacion JSON'
    END AS resultado
FROM information_schema.CHECK_CONSTRAINTS cc
JOIN information_schema.TABLE_CONSTRAINTS tc 
    ON cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
WHERE tc.TABLE_SCHEMA = 'ispeb_expedientes' 
AND tc.TABLE_NAME = 'historial_administrativo'
AND cc.CHECK_CLAUSE LIKE '%json_valid%';

-- ============================================
-- 5. Verificar índice idx_tipo_evento
-- ============================================
SELECT 
    'Indice idx_tipo_evento' AS verificacion,
    CASE 
        WHEN COUNT(*) > 0 THEN 'CORRECTO: Indice existe'
        ELSE 'ERROR: Indice no existe'
    END AS resultado
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'ispeb_expedientes' 
AND TABLE_NAME = 'historial_administrativo' 
AND INDEX_NAME = 'idx_tipo_evento';

-- ============================================
-- 6. Listar todas las tablas existentes
-- ============================================
SELECT 
    TABLE_NAME AS tabla,
    CONCAT(
        TABLE_ROWS, ' filas, ',
        ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 2), ' KB'
    ) AS info
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'ispeb_expedientes' 
AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- ============================================
-- 7. Estructura de historial_administrativo
-- ============================================
SELECT 
    COLUMN_NAME AS columna,
    CONCAT(DATA_TYPE, 
        CASE 
            WHEN CHARACTER_MAXIMUM_LENGTH IS NOT NULL THEN CONCAT('(', CHARACTER_MAXIMUM_LENGTH, ')')
            WHEN NUMERIC_PRECISION IS NOT NULL THEN CONCAT('(', NUMERIC_PRECISION, ')')
            ELSE ''
        END
    ) AS tipo,
    CASE 
        WHEN COLUMN_NAME = 'detalles' AND DATA_TYPE = 'longtext' THEN 'JSON VALIDADO'
        WHEN IS_NULLABLE = 'NO' THEN 'NOT NULL'
        ELSE 'NULL'
    END AS restriccion
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ispeb_expedientes' 
AND TABLE_NAME = 'historial_administrativo'
ORDER BY ORDINAL_POSITION;

-- ============================================
-- 8. Verificación final - Resumen
-- ============================================
SELECT 
    CASE 
        WHEN (
            NOT EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'ispeb_expedientes' AND TABLE_NAME = 'activos_tecnologicos')
            AND NOT EXISTS (SELECT 1 FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = 'ispeb_expedientes' AND ROUTINE_NAME = 'sp_validar_retiro')
            AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'ispeb_expedientes' AND TABLE_NAME = 'historial_administrativo' AND COLUMN_NAME = 'detalles' AND DATA_TYPE = 'longtext')
        ) THEN 'LIMPIEZA COMPLETADA CORRECTAMENTE'
        ELSE 'ADVERTENCIA: Revisar los resultados anteriores'
    END AS estado_final;
