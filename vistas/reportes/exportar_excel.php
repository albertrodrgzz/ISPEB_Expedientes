<?php
/**
 * Exportación a Excel (HTML-table con formato institucional)
 * SIGED — Sistema de Gestión de Expedientes Digitales
 * v2.0: BOM UTF-8, colores institucionales, tabla HTML para ms-excel
 */

ob_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

if (!verificarNivel(2)) {
    ob_end_clean();
    die('No tiene permisos para exportar datos.');
}

$tipo = $_GET['tipo'] ?? 'general';
$db   = getDB();

registrarAuditoria('EXPORTAR_EXCEL', null, null, null, ['tipo' => $tipo]);

// =========================================================
// CABECERAS HTTP — Excel con tabla HTML (xls)
// =========================================================
$filename = 'SIGED_Reporte_' . strtoupper($tipo) . '_' . date('Ymd_His') . '.xls';

if (ob_get_length()) ob_end_clean();

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Expires: 0');

// BOM UTF-8 — garantiza que Excel reconozca acentos
echo "\xEF\xBB\xBF";

// =========================================================
// ESTILOS CSS inline para Excel
// =========================================================
$style_header_title = 'background:#0056B3;color:#FFFFFF;font-weight:bold;font-size:14pt;font-family:Arial;text-align:center;padding:10px;';
$style_subheader    = 'background:#E8F4FD;color:#003366;font-size:10pt;font-family:Arial;text-align:center;padding:5px;';
$style_th           = 'background:#D6D6D6;color:#1A1A1A;font-weight:bold;font-size:10pt;font-family:Arial;text-align:center;border:1px solid #AAAAAA;padding:6px;';
$style_td_left      = 'font-size:10pt;font-family:Arial;text-align:left;border:1px solid #DDDDDD;padding:5px;';
$style_td_center    = 'font-size:10pt;font-family:Arial;text-align:center;border:1px solid #DDDDDD;padding:5px;';
$style_td_alt       = 'background:#F0F7FF;font-size:10pt;font-family:Arial;text-align:left;border:1px solid #DDDDDD;padding:5px;';
$style_td_alt_c     = 'background:#F0F7FF;font-size:10pt;font-family:Arial;text-align:center;border:1px solid #DDDDDD;padding:5px;';
$style_footer_row   = 'background:#0056B3;color:#FFFFFF;font-weight:bold;font-size:10pt;font-family:Arial;text-align:right;padding:6px;border:1px solid #004494;';

// =========================================================
// FUNCIÓN HELPER: fila de encabezado institucional
// =========================================================
function excelHeader(string $titulo, string $subtitulo, int $colspan): void {
    global $style_header_title, $style_subheader;
    $fecha = date('d/m/Y H:i:s');
    echo '<tr><td colspan="' . $colspan . '" style="' . $style_header_title . '">'
       . htmlspecialchars($titulo) . '</td></tr>' . "\n";
    echo '<tr><td colspan="' . $colspan . '" style="' . $style_subheader . '">'
       . htmlspecialchars($subtitulo) . ' &nbsp;|&nbsp; Generado: ' . $fecha . '</td></tr>' . "\n";
    echo '<tr><td colspan="' . $colspan . '" style="height:8px;"></td></tr>' . "\n";
}

// =========================================================
// TIPO: GENERAL (Reporte de todos los funcionarios)
// =========================================================
if ($tipo === 'general') {

    $estado = $_GET['estado'] ?? 'activo';
    $orden  = $_GET['orden']  ?? 'apellidos';

    $query = "
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
        LEFT JOIN cargos c        ON f.cargo_id        = c.id
        LEFT JOIN departamentos d ON f.departamento_id  = d.id
        WHERE 1=1
    ";
    $params = [];

    if ($estado !== 'todos') {
        $query   .= " AND f.estado = ?";
        $params[] = $estado;
    }
    switch ($orden) {
        case 'departamento': $query .= " ORDER BY d.nombre ASC, f.apellidos ASC"; break;
        case 'cargo':        $query .= " ORDER BY c.nombre_cargo ASC, f.apellidos ASC"; break;
        default:             $query .= " ORDER BY f.apellidos ASC, f.nombres ASC";
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $titulo_estado = match($estado) {
        'activo'   => 'Funcionarios Activos',
        'inactivo' => 'Funcionarios Inactivos',
        default    => 'Todos los Funcionarios'
    };

    $cols = 6; // Cédula, Nombres, Cargo, Departamento, Antigüedad, Estado

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";

    // Fila 1: Título grande
    excelHeader(
        'SIGED — REPORTE GENERAL DE FUNCIONARIOS',
        $titulo_estado,
        $cols
    );

    // Fila 3: Cabeceras de columnas
    echo '<tr>';
    foreach (['Cédula','Nombres y Apellidos','Cargo','Departamento','Antigüedad','Estado'] as $h) {
        echo '<th style="' . $GLOBALS['style_th'] . '">' . $h . '</th>';
    }
    echo '</tr>' . "\n";

    // Filas de datos
    $n = 0;
    foreach ($filas as $row) {
        $alt  = ($n % 2 === 1);
        $tdL  = $alt ? $GLOBALS['style_td_alt']   : $GLOBALS['style_td_left'];
        $tdC  = $alt ? $GLOBALS['style_td_alt_c']  : $GLOBALS['style_td_center'];
        $nombre = htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']);
        $cargo  = htmlspecialchars($row['nombre_cargo'] ?? 'N/A');
        $depto  = htmlspecialchars($row['departamento']  ?? 'N/A');
        $ant    = $row['antiguedad'] . ' año' . ($row['antiguedad'] != 1 ? 's' : '');
        $est    = ucfirst($row['estado']);

        echo '<tr>';
        echo '<td style="' . $tdC . '">' . htmlspecialchars($row['cedula']) . '</td>';
        echo '<td style="' . $tdL . '">' . $nombre . '</td>';
        echo '<td style="' . $tdL . '">' . $cargo   . '</td>';
        echo '<td style="' . $tdL . '">' . $depto   . '</td>';
        echo '<td style="' . $tdC . '">' . $ant     . '</td>';
        echo '<td style="' . $tdC . '">' . htmlspecialchars($est) . '</td>';
        echo '</tr>' . "\n";
        $n++;
    }

    // Fila de total
    echo '<tr><td colspan="' . $cols . '" style="' . $GLOBALS['style_footer_row'] . '">TOTAL: '
       . count($filas) . ' funcionario(s)</td></tr>' . "\n";

    echo '</table></body></html>';
    exit;
}

// =========================================================
// TIPO: DEPARTAMENTO
// =========================================================
if ($tipo === 'departamento') {
    $stmt_depts = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre");
    $departamentos = $stmt_depts->fetchAll();

    $cols = 5;

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";

    excelHeader('SIGED — REPORTE POR DEPARTAMENTO', 'Todos los departamentos activos', $cols);

    foreach ($departamentos as $dept) {
        // Encabezado de sección por departamento
        echo '<tr><td colspan="' . $cols . '" style="background:#003366;color:#FFFFFF;font-weight:bold;font-family:Arial;padding:6px;">'
           . 'DEPARTAMENTO: ' . htmlspecialchars($dept['nombre']) . '</td></tr>' . "\n";

        echo '<tr>';
        foreach (['Cédula','Nombres y Apellidos','Cargo','Fecha Ingreso','Antigüedad'] as $h) {
            echo '<th style="' . $GLOBALS['style_th'] . '">' . $h . '</th>';
        }
        echo '</tr>' . "\n";

        $stmt = $db->prepare("
            SELECT f.cedula, f.nombres, f.apellidos, c.nombre_cargo, f.fecha_ingreso,
                   TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad
            FROM funcionarios f
            INNER JOIN cargos c ON f.cargo_id = c.id
            WHERE f.departamento_id = ? AND f.estado != 'inactivo'
            ORDER BY f.apellidos, f.nombres
        ");
        $stmt->execute([$dept['id']]);
        $n = 0; $count = 0;
        while ($row = $stmt->fetch()) {
            $alt = ($n % 2 === 1);
            $tdL = $alt ? $GLOBALS['style_td_alt']  : $GLOBALS['style_td_left'];
            $tdC = $alt ? $GLOBALS['style_td_alt_c'] : $GLOBALS['style_td_center'];
            echo '<tr>';
            echo '<td style="'.$tdC.'">' . htmlspecialchars($row['cedula']) . '</td>';
            echo '<td style="'.$tdL.'">' . htmlspecialchars($row['nombres'].' '.$row['apellidos']) . '</td>';
            echo '<td style="'.$tdL.'">' . htmlspecialchars($row['nombre_cargo'] ?? 'N/A') . '</td>';
            echo '<td style="'.$tdC.'">' . date('d/m/Y', strtotime($row['fecha_ingreso'])) . '</td>';
            echo '<td style="'.$tdC.'">' . $row['antiguedad'] . ' año(s)' . '</td>';
            echo '</tr>' . "\n";
            $n++; $count++;
        }
        echo '<tr><td colspan="'.$cols.'" style="'.$GLOBALS['style_footer_row'].'">Subtotal: '.$count.' funcionario(s)</td></tr>' . "\n";
        echo '<tr><td colspan="'.$cols.'" style="height:10px;"></td></tr>' . "\n";
    }

    echo '</table></body></html>';
    exit;
}

// =========================================================
// TIPO: VACACIONES (Control vacacional LOTTT)
// =========================================================
if ($tipo === 'vacaciones') {
    $anio = (int)date('Y');
    $cols = 7;

    $stmt = $db->query("
        SELECT
            f.cedula, f.nombres, f.apellidos,
            d.nombre AS departamento,
            TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad,
            COALESCE(
                SUM(
                    CASE WHEN YEAR(ha.fecha_evento) = YEAR(CURDATE())
                         THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(ha.detalles,'$.dias_habiles')) AS UNSIGNED)
                         ELSE 0 END
                ), 0
            ) AS dias_usados
        FROM funcionarios f
        INNER JOIN departamentos d ON f.departamento_id = d.id
        LEFT JOIN historial_administrativo ha ON ha.funcionario_id = f.id AND ha.tipo_evento = 'VACACION'
        WHERE f.estado != 'inactivo'
        GROUP BY f.id, f.cedula, f.nombres, f.apellidos, d.nombre, f.fecha_ingreso
        ORDER BY f.apellidos, f.nombres
    ");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";

    excelHeader('SIGED — CONTROL VACACIONAL ' . $anio, 'LOTTT: 15 días hábiles el 1er año, +1 día por año adicional, máximo 30', $cols);

    echo '<tr>';
    foreach (['Cédula','Nombres y Apellidos','Departamento','Antigüedad','Días Correspondientes','Días Usados','Días Disponibles'] as $h) {
        echo '<th style="'.$GLOBALS['style_th'].'">'.$h.'</th>';
    }
    echo '</tr>' . "\n";

    $n = 0;
    while ($row = $stmt->fetch()) {
        $anios_s   = (int)$row['antiguedad'];
        $dias_tot  = ($anios_s >= 1) ? min(15 + ($anios_s - 1), 30) : 0;
        $dias_us   = (int)$row['dias_usados'];
        $dias_disp = max(0, $dias_tot - $dias_us);

        $colorDisp = $dias_disp <= 0 ? '#FECACA' : ($dias_disp <= 5 ? '#FDE68A' : '#A7F3D0');

        $alt = ($n % 2 === 1);
        $tdL = $alt ? $GLOBALS['style_td_alt']  : $GLOBALS['style_td_left'];
        $tdC = $alt ? $GLOBALS['style_td_alt_c'] : $GLOBALS['style_td_center'];

        echo '<tr>';
        echo '<td style="'.$tdC.'">' . htmlspecialchars($row['cedula']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['nombres'].' '.$row['apellidos']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['departamento']) . '</td>';
        echo '<td style="'.$tdC.'">' . $anios_s . ' año(s)' . '</td>';
        echo '<td style="'.$tdC.'">' . $dias_tot . '</td>';
        echo '<td style="'.$tdC.'">' . $dias_us  . '</td>';
        echo '<td style="background:'.$colorDisp.';text-align:center;font-weight:bold;font-family:Arial;font-size:10pt;border:1px solid #DDDDDD;padding:5px;">' . $dias_disp . '</td>';
        echo '</tr>' . "\n";
        $n++;
    }
    echo '</table></body></html>';
    exit;
}

// =========================================================
// TIPO: AMONESTACIONES
// =========================================================
if ($tipo === 'amonestaciones') {
    $cols = 7;
    $stmt = $db->query("
        SELECT
            ha.fecha_evento AS fecha_falta,
            f.cedula, f.nombres, f.apellidos,
            d.nombre AS departamento,
            JSON_UNQUOTE(JSON_EXTRACT(ha.detalles,'$.tipo_falta'))  AS tipo_falta,
            JSON_UNQUOTE(JSON_EXTRACT(ha.detalles,'$.motivo'))       AS motivo,
            JSON_UNQUOTE(JSON_EXTRACT(ha.detalles,'$.sancion'))      AS sancion
        FROM historial_administrativo ha
        INNER JOIN funcionarios f ON ha.funcionario_id = f.id
        INNER JOIN departamentos d ON f.departamento_id = d.id
        WHERE ha.tipo_evento = 'AMONESTACION'
        ORDER BY ha.fecha_evento DESC
    ");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";

    excelHeader('SIGED — HISTORIAL DE AMONESTACIONES', 'Año: ' . date('Y'), $cols);

    echo '<tr>';
    foreach (['Fecha','Cédula','Nombres y Apellidos','Departamento','Tipo de Falta','Motivo','Sanción'] as $h) {
        echo '<th style="'.$GLOBALS['style_th'].'">'.$h.'</th>';
    }
    echo '</tr>' . "\n";

    $n = 0;
    while ($row = $stmt->fetch()) {
        $alt = ($n % 2 === 1);
        $tdL = $alt ? $GLOBALS['style_td_alt']  : $GLOBALS['style_td_left'];
        $tdC = $alt ? $GLOBALS['style_td_alt_c'] : $GLOBALS['style_td_center'];
        echo '<tr>';
        echo '<td style="'.$tdC.'">' . ($row['fecha_falta'] ? date('d/m/Y', strtotime($row['fecha_falta'])) : '—') . '</td>';
        echo '<td style="'.$tdC.'">' . htmlspecialchars($row['cedula']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['nombres'].' '.$row['apellidos']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['departamento']) . '</td>';
        echo '<td style="'.$tdC.'">' . htmlspecialchars($row['tipo_falta'] ?? '—') . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['motivo'] ?? '—') . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['sancion'] ?? '—') . '</td>';
        echo '</tr>' . "\n";
        $n++;
    }
    echo '</table></body></html>';
    exit;
}

// =========================================================
// TIPO: NOMBRAMIENTOS
// =========================================================
if ($tipo === 'nombramientos') {
    $cols = 6;
    $stmt = $db->query("
        SELECT
            f.cedula, f.nombres, f.apellidos,
            d.nombre AS departamento,
            JSON_UNQUOTE(JSON_EXTRACT(ha.detalles,'$.cargo')) AS cargo_json,
            c.nombre_cargo,
            ha.fecha_evento AS fecha_inicio,
            ha.fecha_fin
        FROM historial_administrativo ha
        INNER JOIN funcionarios f   ON ha.funcionario_id    = f.id
        INNER JOIN departamentos d  ON f.departamento_id    = d.id
        LEFT  JOIN cargos c         ON f.cargo_id           = c.id
        WHERE ha.tipo_evento = 'NOMBRAMIENTO'
        ORDER BY ha.fecha_evento DESC
    ");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";

    excelHeader('SIGED — HISTORIAL DE NOMBRAMIENTOS', 'Todos los registros de nombramientos', $cols);

    echo '<tr>';
    foreach (['Cédula','Nombres y Apellidos','Departamento','Cargo','Fecha Inicio','Fecha Fin'] as $h) {
        echo '<th style="'.$GLOBALS['style_th'].'">'.$h.'</th>';
    }
    echo '</tr>' . "\n";

    $n = 0;
    while ($row = $stmt->fetch()) {
        $alt   = ($n % 2 === 1);
        $tdL   = $alt ? $GLOBALS['style_td_alt']  : $GLOBALS['style_td_left'];
        $tdC   = $alt ? $GLOBALS['style_td_alt_c'] : $GLOBALS['style_td_center'];
        $cargo = $row['cargo_json'] ?? $row['nombre_cargo'] ?? 'N/A';
        echo '<tr>';
        echo '<td style="'.$tdC.'">' . htmlspecialchars($row['cedula']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['nombres'].' '.$row['apellidos']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($row['departamento']) . '</td>';
        echo '<td style="'.$tdL.'">' . htmlspecialchars($cargo) . '</td>';
        echo '<td style="'.$tdC.'">' . ($row['fecha_inicio'] ? date('d/m/Y', strtotime($row['fecha_inicio'])) : '—') . '</td>';
        echo '<td style="'.$tdC.'">' . ($row['fecha_fin']    ? date('d/m/Y', strtotime($row['fecha_fin']))    : 'Indefinido') . '</td>';
        echo '</tr>' . "\n";
        $n++;
    }
    echo '</table></body></html>';
    exit;
}

// Tipo no reconocido
echo "\xEF\xBB\xBF";
echo '<html><body><table><tr><td>ERROR: Tipo de reporte no válido.</td></tr></table></body></html>';
exit;
