<?php
/**
 * Exportador a Excel (CSV/XLSX)
 * Versión básica usando CSV (compatible con Excel)
 * 
 * NOTA: Para archivos XLSX profesionales, se recomienda instalar PhpSpreadsheet:
 * composer require phpoffice/phpspreadsheet
 * 
 * Documentación: https://phpspreadsheet.readthedocs.io/
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión y permisos
verificarSesion();

if (!verificarNivel(2)) {
    die('No tiene permisos para exportar datos');
}

$tipo = $_GET['tipo'] ?? 'general';
$db = getDB();

// Registrar en auditoría
registrarAuditoria('EXPORTAR_EXCEL', null, null, null, ['tipo' => $tipo]);

// Configurar headers para descarga de archivo Excel
$filename = 'reporte_' . $tipo . '_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Abrir output stream
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel reconozca correctamente los caracteres especiales)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Función helper para escribir fila
function escribirFila($output, $datos) {
    fputcsv($output, $datos, ',', '"');
}

// Generar contenido según el tipo de reporte
switch ($tipo) {
    case 'general':
        // Encabezados
        escribirFila($output, ['LISTADO GENERAL DE FUNCIONARIOS - ISPEB']);
        escribirFila($output, ['Generado por: ' . $_SESSION['nombre_completo'] . ' | Fecha: ' . date('d/m/Y H:i:s')]);
        escribirFila($output, []); // Línea en blanco
        
        // Columnas
        escribirFila($output, ['Cédula', 'Nombres', 'Apellidos', 'Cargo', 'Departamento', 'Fecha Ingreso', 'Antigüedad (años)', 'Estado']);
        
        // Datos
        $stmt = $db->query("
            SELECT 
                f.cedula,
                f.nombres,
                f.apellidos,
                c.nombre_cargo,
                d.nombre AS departamento,
                f.fecha_ingreso,
                TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad,
                f.estado
            FROM funcionarios f
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.estado != 'inactivo'
            ORDER BY f.apellidos, f.nombres
        ");
        
        while ($row = $stmt->fetch()) {
            escribirFila($output, [
                $row['cedula'],
                $row['nombres'],
                $row['apellidos'],
                $row['nombre_cargo'],
                $row['departamento'],
                date('d/m/Y', strtotime($row['fecha_ingreso'])),
                $row['antiguedad'],
                ucfirst($row['estado'])
            ]);
        }
        break;
        
    case 'departamento':
        // Encabezados
        escribirFila($output, ['REPORTE POR DEPARTAMENTO - ISPEB']);
        escribirFila($output, ['Generado por: ' . $_SESSION['nombre_completo'] . ' | Fecha: ' . date('d/m/Y H:i:s')]);
        escribirFila($output, []);
        
        $stmt_depts = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre");
        $departamentos = $stmt_depts->fetchAll();
        
        foreach ($departamentos as $dept) {
            escribirFila($output, []);
            escribirFila($output, ['DEPARTAMENTO: ' . $dept['nombre']]);
            escribirFila($output, ['Cédula', 'Nombres', 'Apellidos', 'Cargo', 'Fecha Ingreso', 'Antigüedad (años)']);
            
            $stmt = $db->prepare("
                SELECT 
                    f.cedula,
                    f.nombres,
                    f.apellidos,
                    c.nombre_cargo,
                    f.fecha_ingreso,
                    TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad
                FROM funcionarios f
                INNER JOIN cargos c ON f.cargo_id = c.id
                WHERE f.departamento_id = ? AND f.estado != 'inactivo'
                ORDER BY f.apellidos, f.nombres
            ");
            $stmt->execute([$dept['id']]);
            
            $count = 0;
            while ($row = $stmt->fetch()) {
                escribirFila($output, [
                    $row['cedula'],
                    $row['nombres'],
                    $row['apellidos'],
                    $row['nombre_cargo'],
                    date('d/m/Y', strtotime($row['fecha_ingreso'])),
                    $row['antiguedad']
                ]);
                $count++;
            }
            
            escribirFila($output, ['TOTAL: ' . $count . ' funcionario(s)']);
        }
        break;
        
    case 'vacaciones':
        // Encabezados
        escribirFila($output, ['CONTROL VACACIONAL - ISPEB']);
        escribirFila($output, ['Año: ' . date('Y')]);
        escribirFila($output, ['Generado por: ' . $_SESSION['nombre_completo'] . ' | Fecha: ' . date('d/m/Y H:i:s')]);
        escribirFila($output, []);
        
        // Columnas
        escribirFila($output, ['Cédula', 'Nombres', 'Apellidos', 'Departamento', 'Antigüedad (años)', 'Días Disponibles', 'Días Usados', 'Días Pendientes']);
        
        // Datos
        $stmt = $db->query("
            SELECT 
                f.cedula,
                f.nombres,
                f.apellidos,
                d.nombre AS departamento,
                TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad,
                (15 + TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE())) AS dias_disponibles,
                COALESCE(SUM(CASE WHEN YEAR(ed.fecha_inicio) = YEAR(CURDATE()) THEN ed.dias_totales ELSE 0 END), 0) AS dias_usados
            FROM funcionarios f
            INNER JOIN departamentos d ON f.departamento_id = d.id
            LEFT JOIN vacaciones ed ON f.id = ed.funcionario_id
            WHERE f.estado != 'inactivo'
            GROUP BY f.id
            ORDER BY f.apellidos, f.nombres
        ");
        
        while ($row = $stmt->fetch()) {
            $pendientes = $row['dias_disponibles'] - $row['dias_usados'];
            escribirFila($output, [
                $row['cedula'],
                $row['nombres'],
                $row['apellidos'],
                $row['departamento'],
                $row['antiguedad'],
                $row['dias_disponibles'],
                $row['dias_usados'],
                $pendientes
            ]);
        }
        break;
        
    case 'amonestaciones':
        // Encabezados
        escribirFila($output, ['HISTORIAL DE AMONESTACIONES - ISPEB']);
        escribirFila($output, ['Año: ' . date('Y')]);
        escribirFila($output, ['Generado por: ' . $_SESSION['nombre_completo'] . ' | Fecha: ' . date('d/m/Y H:i:s')]);
        escribirFila($output, []);
        
        // Columnas
        escribirFila($output, ['Fecha Falta', 'Cédula', 'Nombres', 'Apellidos', 'Departamento', 'Tipo de Falta', 'Título', 'Sanción Aplicada']);
        
        // Datos
        $stmt = $db->query("
            SELECT 
                f.cedula,
                f.nombres,
                f.apellidos,
                d.nombre AS departamento,
                JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.descripcion')) as titulo,
                ha.fecha_evento AS fecha_falta,
                JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.tipo_falta')) as tipo_falta,
                JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.sancion_aplicada')) as sancion_aplicada
            FROM historial_administrativo ha
            INNER JOIN funcionarios f ON ha.funcionario_id = f.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE ha.tipo_evento = 'AMONESTACION' AND YEAR(ha.fecha_evento) = YEAR(CURDATE())
            ORDER BY ha.fecha_evento DESC
        ");
        
        while ($row = $stmt->fetch()) {
            escribirFila($output, [
                date('d/m/Y', strtotime($row['fecha_falta'])),
                $row['cedula'],
                $row['nombres'],
                $row['apellidos'],
                $row['departamento'],
                $row['tipo_falta'],
                $row['titulo'],
                $row['sancion_aplicada'] ?? '-'
            ]);
        }
        break;
        
    case 'antiguedad':
        // Encabezados
        escribirFila($output, ['ANTIGÜEDAD DEL PERSONAL - ISPEB']);
        escribirFila($output, ['Generado por: ' . $_SESSION['nombre_completo'] . ' | Fecha: ' . date('d/m/Y H:i:s')]);
        escribirFila($output, []);
        
        // Columnas
        escribirFila($output, ['Cédula', 'Nombres', 'Apellidos', 'Cargo', 'Departamento', 'Fecha Ingreso', 'Antigüedad (años)', 'Antigüedad (meses)']);
        
        // Datos
        $stmt = $db->query("
            SELECT 
                f.cedula,
                f.nombres,
                f.apellidos,
                c.nombre_cargo,
                d.nombre AS departamento,
                f.fecha_ingreso,
                TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad_anos,
                TIMESTAMPDIFF(MONTH, f.fecha_ingreso, CURDATE()) % 12 AS antiguedad_meses
            FROM funcionarios f
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.estado != 'inactivo'
            ORDER BY f.fecha_ingreso ASC
        ");
        
        while ($row = $stmt->fetch()) {
            escribirFila($output, [
                $row['cedula'],
                $row['nombres'],
                $row['apellidos'],
                $row['nombre_cargo'],
                $row['departamento'],
                date('d/m/Y', strtotime($row['fecha_ingreso'])),
                $row['antiguedad_anos'],
                $row['antiguedad_meses']
            ]);
        }
        break;
        
    case 'nombramientos':
        // Encabezados
        escribirFila($output, ['NOMBRAMIENTOS ACTIVOS - ISPEB']);
        escribirFila($output, ['Generado por: ' . $_SESSION['nombre_completo'] . ' | Fecha: ' . date('d/m/Y H:i:s')]);
        escribirFila($output, []);
        
        // Columnas
        escribirFila($output, ['Cédula', 'Nombres', 'Apellidos', 'Departamento', 'Categoría', 'Título', 'Fecha Inicio', 'Fecha Fin']);
        
        // Datos
        $stmt = $db->query("
            SELECT 
                f.cedula,
                f.nombres,
                f.apellidos,
                d.nombre AS departamento,
                JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.categoria')) as categoria,
                JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.titulo')) as titulo,
                ha.fecha_evento as fecha_inicio,
                ha.fecha_fin
            FROM historial_administrativo ha
            INNER JOIN funcionarios f ON ha.funcionario_id = f.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE ha.tipo_evento = 'NOMBRAMIENTO'
            ORDER BY ha.fecha_evento DESC
        ");
        
        while ($row = $stmt->fetch()) {
            escribirFila($output, [
                $row['cedula'],
                $row['nombres'],
                $row['apellidos'],
                $row['departamento'],
                $row['categoria'] ?? '-',
                $row['titulo'],
                date('d/m/Y', strtotime($row['fecha_inicio'])),
                $row['fecha_fin'] ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Indefinido'
            ]);
        }
        break;
        
    default:
        escribirFila($output, ['ERROR: Tipo de reporte no válido']);
        break;
}

fclose($output);
exit;
