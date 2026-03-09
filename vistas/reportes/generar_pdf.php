<?php
/**
 * Generador de Reportes PDF
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 * Maneja: constancia | listado | historial
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

$tipo = $_GET['tipo'] ?? 'listado';

// ---------------------------------------------------------------
// TIPO: CONSTANCIA DE TRABAJO
// ---------------------------------------------------------------
if ($tipo === 'constancia') {
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$funcionario_id) {
        ob_end_clean();
        die('ID de funcionario no válido.');
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            f.id, f.cedula, f.nombres, f.apellidos,
            f.fecha_ingreso, f.genero,
            c.nombre_cargo,
            d.nombre AS departamento
        FROM funcionarios f
        INNER JOIN cargos c        ON f.cargo_id        = c.id
        INNER JOIN departamentos d ON f.departamento_id = d.id
        WHERE f.id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $f = $stmt->fetch();

    if (!$f) {
        ob_end_clean();
        die('Funcionario no encontrado.');
    }

    // ---- Helpers de encoding ----
    $pdf_enc   = fn(string $s): string => mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    $pdf_upper = fn(string $s): string => mb_convert_encoding(mb_strtoupper($s, 'UTF-8'), 'ISO-8859-1', 'UTF-8');

    // ---- Antigüedad dinámica ----
    $fechaIngreso = new DateTime($f['fecha_ingreso']);
    $diff = $fechaIngreso->diff(new DateTime());
    $anios_ant = $diff->y;  $meses_ant = $diff->m;  $dias_ant = $diff->d;

    if ($anios_ant >= 1) {
        $antiguedad = $anios_ant . ' año' . ($anios_ant != 1 ? 's' : '');
        if ($meses_ant > 0) $antiguedad .= ' y ' . $meses_ant . ' mes' . ($meses_ant != 1 ? 'es' : '');
    } elseif ($meses_ant >= 1) {
        $antiguedad = $meses_ant . ' mes' . ($meses_ant != 1 ? 'es' : '');
        if ($dias_ant > 0) $antiguedad .= ' y ' . $dias_ant . ' día' . ($dias_ant != 1 ? 's' : '');
    } else {
        $antiguedad = $dias_ant . ' día' . ($dias_ant != 1 ? 's' : '');
    }

    // ---- Fechas en español ----
    $mesesES = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $fecha_emision     = date('d') . ' de ' . $mesesES[(int)date('m')] . ' de ' . date('Y');
    $fecha_ingreso_txt = $fechaIngreso->format('d') . ' de ' . $mesesES[(int)$fechaIngreso->format('m')] . ' de ' . $fechaIngreso->format('Y');
    $genero_txt        = ($f['genero'] === 'F') ? 'la ciudadana' : 'el ciudadano';
    $nombre_completo   = mb_strtoupper($f['nombres'] . ' ' . $f['apellidos'], 'UTF-8');

    require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

    class PDF_Constancia extends FPDF {
        function Header() {
            // Cintillo a todo el ancho (190mm) — sin texto de membrete
            $logo = __DIR__ . '/../../publico/imagenes/cintillo.png';
            if (file_exists($logo)) $this->Image($logo, 10, 10, 190);
            $this->Ln(32); // espacio tras el cintillo
        }
        function Footer() {
            // Pie institucional al final real de la página
            $this->SetY(-28);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(120, 120, 120);
            $this->MultiCell(0, 4,
                mb_convert_encoding(
                    "Instituto de Salud Pública del Estado Bolívar\n"
                  . "Dirección de Telemática — Ciudad Bolívar, Estado Bolívar",
                    'ISO-8859-1', 'UTF-8'),
                0, 'C');
            $this->SetTextColor(160, 160, 160);
            $this->Cell(0, 4,
                mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo(),
                0, 0, 'C');
        }
    }

    $pdf = new PDF_Constancia();
    $pdf->AddPage();
    $pdf->SetMargins(25, 15, 25);
    $pdf->Ln(8);

    // Título
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, $pdf_enc('CONSTANCIA DE TRABAJO'), 0, 1, 'C');
    $pdf->Ln(10);

    // Párrafo 1
    $pdf->SetFont('Arial', '', 12);
    $texto1 = "Quien suscribe, Director de la Dirección de Telemática del Instituto "
            . "de Salud Pública del Estado Bolívar (ISPEB), por medio de la presente "
            . "hace constar que $genero_txt:";
    $pdf->MultiCell(0, 6, $pdf_enc($texto1), 0, 'J');
    $pdf->Ln(5);

    // Datos centrados en negrita
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 7, $pdf_upper($nombre_completo), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 6, $pdf_enc('Titular de la Cédula de Identidad N° ' . $f['cedula']), 0, 1, 'C');
    $pdf->Ln(5);

    // Párrafo 2
    $cargo_up = mb_strtoupper($f['nombre_cargo'], 'UTF-8');
    $depto_up = mb_strtoupper($f['departamento'],  'UTF-8');
    $texto2 = "Presta sus servicios en esta institución desde el $fecha_ingreso_txt, "
            . "acumulando una antigüedad de $antiguedad, desempeñando actualmente el cargo de "
            . "$cargo_up, adscrito al departamento de $depto_up.";
    $pdf->MultiCell(0, 6, $pdf_upper($texto2), 0, 'J');
    $pdf->Ln(5);

    // Párrafo 3
    $texto3 = "Constancia que se expide a petición de la parte interesada "
            . "en Ciudad Bolívar, a los $fecha_emision.";
    $pdf->MultiCell(0, 6, $pdf_enc($texto3), 0, 'J');

    // Sin firma — eliminada por instrucción del usuario

    registrarAuditoria('GENERAR_CONSTANCIA', 'funcionarios', $funcionario_id, null, [
        'funcionario'  => $nombre_completo,
        'generado_por' => $_SESSION['nombre_completo'] ?? 'Usuario'
    ]);

    $nombre_pdf = 'Constancia_' . str_replace(' ', '_', $f['nombres']) . '_' . str_replace(' ', '_', $f['apellidos']) . '.pdf';
    if (ob_get_length()) ob_end_clean();
    $pdf->Output('I', $nombre_pdf);
    exit;
}

// ---------------------------------------------------------------
// TIPO: LISTADO DE PERSONAL
// ---------------------------------------------------------------
if ($tipo === 'listado') {
    $estado = $_GET['estado'] ?? 'activo';        // activo | inactivo | todos
    $orden  = $_GET['orden']  ?? 'apellidos';     // apellidos | departamento | cargo

    $db = getDB();

    $query = "
        SELECT
            f.cedula,
            f.nombres,
            f.apellidos,
            f.estado,
            f.fecha_ingreso,
            c.nombre_cargo,
            d.nombre AS departamento,
            TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad
        FROM funcionarios f
        LEFT JOIN cargos c ON f.cargo_id = c.id
        LEFT JOIN departamentos d ON f.departamento_id = d.id
        WHERE 1=1
    ";
    $params = [];

    if ($estado !== 'todos') {
        $query .= " AND f.estado = ?";
        $params[] = $estado;
    }

    // Ordenamiento
    switch ($orden) {
        case 'departamento': $query .= " ORDER BY d.nombre ASC, f.apellidos ASC"; break;
        case 'cargo':        $query .= " ORDER BY c.nombre_cargo ASC, f.apellidos ASC"; break;
        default:             $query .= " ORDER BY f.apellidos ASC, f.nombres ASC"; break;
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $funcionarios = $stmt->fetchAll();

    require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

    $titulo_estado = ($estado === 'todos') ? 'Todos los Funcionarios' : 'Funcionarios ' . ucfirst($estado) . 's';

    class PDF_Listado extends FPDF {
        public $titulo_reporte = '';
        function Header() {
            // Cintillo a todo el ancho landscape: 277mm
            $logo = __DIR__ . '/../../publico/imagenes/cintillo.png';
            if (file_exists($logo)) $this->Image($logo, 10, 10, 277);
            $this->Ln(30);
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10, mb_convert_encoding('REPORTE GENERAL DE FUNCIONARIOS - SIGED','ISO-8859-1','UTF-8'), 0, 1, 'C');
            $this->SetFont('Arial','',10);
            $this->Cell(0,5, mb_convert_encoding($this->titulo_reporte . ' | Fecha: ' . date('d/m/Y'),'ISO-8859-1','UTF-8'), 0, 1, 'C');
            $this->Ln(5);
            // Cabeceras — anchos: 30+80+75+60+32 = 277mm
            $this->SetFont('Arial','B',9);
            $this->SetFillColor(15, 76, 129);
            $this->SetTextColor(255);
            $this->Cell(30, 8, mb_convert_encoding('CÉDULA','ISO-8859-1','UTF-8'),        1, 0, 'C', true);
            $this->Cell(80, 8, mb_convert_encoding('NOMBRES Y APELLIDOS','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
            $this->Cell(75, 8, 'CARGO',           1, 0, 'C', true);
            $this->Cell(60, 8, 'DEPARTAMENTO',    1, 0, 'C', true);
            $this->Cell(32, 8, 'ESTADO',          1, 1, 'C', true);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->SetTextColor(128);
            $this->Cell(0,10, mb_convert_encoding('Página ','ISO-8859-1','UTF-8') . $this->PageNo() . ' / {nb}', 0, 0, 'C');
        }
    }

    $pdf = new PDF_Listado();
    $pdf->titulo_reporte = $titulo_estado;
    $pdf->AliasNbPages();
    $pdf->AddPage('L'); // Landscape para más columnas
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(0);

    if (empty($funcionarios)) {
        $pdf->Cell(277, 10, mb_convert_encoding('No se encontraron registros.','ISO-8859-1','UTF-8'), 1, 1, 'C');
    } else {
        $fill = false;
        foreach ($funcionarios as $row) {
            $pdf->SetFillColor($fill ? 240 : 255, $fill ? 244 : 255, $fill ? 250 : 255);
            $nombre = mb_convert_encoding($row['nombres'] . ' ' . $row['apellidos'], 'ISO-8859-1', 'UTF-8');
            $cargo  = mb_convert_encoding($row['nombre_cargo'] ?? 'N/A', 'ISO-8859-1', 'UTF-8');
            $depto  = mb_convert_encoding($row['departamento'] ?? 'N/A', 'ISO-8859-1', 'UTF-8');
            $est    = mb_convert_encoding(ucfirst($row['estado']), 'ISO-8859-1', 'UTF-8');

            // Anchos: 30+80+75+60+32 = 277mm
            $pdf->Cell(30, 7, $row['cedula'],             1, 0, 'C', $fill);
            $pdf->Cell(80, 7, substr($nombre, 0, 52),     1, 0, 'L', $fill);
            $pdf->Cell(75, 7, substr($cargo,  0, 45),     1, 0, 'L', $fill);
            $pdf->Cell(60, 7, substr($depto,  0, 38),     1, 0, 'L', $fill);
            $pdf->Cell(32, 7, $est,                       1, 1, 'C', $fill);
            $fill = !$fill;
        }
        // Fila de total
        $pdf->SetFont('Arial','B',8);
        $pdf->SetFillColor(220, 230, 245);
        $pdf->Cell(277, 7, mb_convert_encoding('TOTAL: ' . count($funcionarios) . ' funcionario(s)','ISO-8859-1','UTF-8'), 1, 1, 'R', true);
    }

    registrarAuditoria('GENERAR_REPORTE_PDF','funcionarios',null,null,[
        'tipo_reporte' => 'listado',
        'filtros'      => "Estado: $estado, Orden: $orden"
    ]);

    if (ob_get_length()) ob_end_clean();
    $pdf->Output('I', 'Listado_Personal_' . date('Ymd') . '.pdf');
    exit;
}

// ---------------------------------------------------------------
// TIPO: HISTORIAL DE MOVIMIENTOS
// ---------------------------------------------------------------
if ($tipo === 'historial') {
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $evento         = $_GET['evento'] ?? 'todos';

    if (!$funcionario_id) {
        ob_end_clean();
        die('ID de funcionario no válido.');
    }

    $db = getDB();

    // Datos del funcionario
    $stmt = $db->prepare("
        SELECT f.cedula, f.nombres, f.apellidos, c.nombre_cargo, d.nombre AS departamento, f.fecha_ingreso
        FROM funcionarios f
        LEFT JOIN cargos c ON f.cargo_id = c.id
        LEFT JOIN departamentos d ON f.departamento_id = d.id
        WHERE f.id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $f = $stmt->fetch();

    if (!$f) {
        ob_end_clean();
        die('Funcionario no encontrado.');
    }

    // Movimientos
    $query = "
        SELECT tipo_evento, fecha_evento, fecha_fin, detalles, created_at
        FROM historial_administrativo
        WHERE funcionario_id = ?
    ";
    $params = [$funcionario_id];

    if ($evento !== 'todos') {
        $query .= " AND tipo_evento = ?";
        $params[] = $evento;
    }
    $query .= " ORDER BY fecha_evento DESC, created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll();

    require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

    $nombre_funcionario = strtoupper($f['nombres'] . ' ' . $f['apellidos']);

    class PDF_Historial extends FPDF {
        public $nombre_func = '';
        function Header() {
            $logo = __DIR__ . '/../../publico/imagenes/cintillo.png';
            if (file_exists($logo)) $this->Image($logo, 10, 10, 190);
            $this->Ln(30);
            $this->SetFont('Arial','B',13);
            $this->Cell(0,8, mb_convert_encoding('HISTORIAL DE MOVIMIENTOS ADMINISTRATIVOS','ISO-8859-1','UTF-8'), 0, 1, 'C');
            $this->SetFont('Arial','',10);
            $this->Cell(0,5, mb_convert_encoding('Funcionario: ' . $this->nombre_func . ' | Fecha: ' . date('d/m/Y'),'ISO-8859-1','UTF-8'), 0, 1, 'C');
            $this->Ln(5);
            // Cabecera tabla
            $this->SetFont('Arial','B',8);
            $this->SetFillColor(245,158,11);
            $this->SetTextColor(255);
            $this->Cell(30,8,'TIPO EVENTO', 1, 0, 'C', true);
            $this->Cell(25,8, mb_convert_encoding('FECHA EVENTO','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
            $this->Cell(25,8, mb_convert_encoding('FECHA FIN','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
            $this->Cell(110,8, mb_convert_encoding('DESCRIPCIÓN / DETALLES','ISO-8859-1','UTF-8'), 1, 1, 'C', true);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->SetTextColor(128);
            $this->Cell(0,10, mb_convert_encoding('Página ','ISO-8859-1','UTF-8') . $this->PageNo() . ' / {nb}', 0, 0, 'C');
        }
    }

    $pdf = new PDF_Historial();
    $pdf->nombre_func = mb_convert_encoding($nombre_funcionario,'ISO-8859-1','UTF-8');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(0);

    if (empty($movimientos)) {
        $pdf->Cell(190,10, mb_convert_encoding('No se encontraron eventos registrados para los criterios seleccionados.','ISO-8859-1','UTF-8'), 1, 1, 'C');
    } else {
        $fill = false;
        foreach ($movimientos as $m) {
            $pdf->SetFillColor($fill ? 253 : 255, $fill ? 246 : 255, $fill ? 222 : 255);

            // Construir descripción a partir de detalles JSON
            $descripcion = '';
            if (!empty($m['detalles'])) {
                $det = json_decode($m['detalles'], true);
                if (is_array($det)) {
                    $partes = [];
                    foreach (['descripcion','titulo','motivo','cargo_nuevo','departamento_destino','tipo_falta','sancion_aplicada','dias_vacaciones'] as $k) {
                        if (!empty($det[$k])) $partes[] = ucfirst($k) . ': ' . $det[$k];
                    }
                    $descripcion = implode(' | ', $partes);
                }
            }
            if (empty($descripcion)) $descripcion = '—';

            $tipo   = mb_convert_encoding($m['tipo_evento'],'ISO-8859-1','UTF-8');
            $fecha  = $m['fecha_evento'] ? date('d/m/Y', strtotime($m['fecha_evento'])) : '-';
            $fin    = $m['fecha_fin']    ? date('d/m/Y', strtotime($m['fecha_fin']))    : '-';
            $desc   = mb_convert_encoding(substr($descripcion,0,120),'ISO-8859-1','UTF-8');

            $pdf->Cell(30,7,$tipo,  1,0,'C',$fill);
            $pdf->Cell(25,7,$fecha, 1,0,'C',$fill);
            $pdf->Cell(25,7,$fin,   1,0,'C',$fill);
            $pdf->Cell(110,7,$desc, 1,1,'L',$fill);
            $fill = !$fill;
        }
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(190,7, mb_convert_encoding('TOTAL: ' . count($movimientos) . ' evento(s) registrado(s)','ISO-8859-1','UTF-8'), 1, 1, 'R');
    }

    // Resumen del funcionario al pie
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(230,245,255);
    $pdf->Cell(190,6, mb_convert_encoding('DATOS DEL FUNCIONARIO','ISO-8859-1','UTF-8'), 1, 1, 'C', true);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(95,6, mb_convert_encoding('Cédula: ' . $f['cedula'],'ISO-8859-1','UTF-8'), 1, 0, 'L');
    $pdf->Cell(95,6, mb_convert_encoding('Cargo: ' . ($f['nombre_cargo'] ?? 'N/A'),'ISO-8859-1','UTF-8'), 1, 1, 'L');
    $pdf->Cell(95,6, mb_convert_encoding('Departamento: ' . ($f['departamento'] ?? 'N/A'),'ISO-8859-1','UTF-8'), 1, 0, 'L');
    $pdf->Cell(95,6, mb_convert_encoding('Fecha Ingreso: ' . ($f['fecha_ingreso'] ? date('d/m/Y', strtotime($f['fecha_ingreso'])) : 'N/A'),'ISO-8859-1','UTF-8'), 1, 1, 'L');

    registrarAuditoria('GENERAR_REPORTE_PDF','historial_administrativo',$funcionario_id,null,[
        'tipo_reporte' => 'historial',
        'filtros'      => "Funcionario: $funcionario_id, Evento: $evento"
    ]);

    if (ob_get_length()) ob_end_clean();
    $pdf->Output('I', 'Historial_' . $funcionario_id . '_' . date('Ymd') . '.pdf');
    exit;
}

// ---------------------------------------------------------------
// Tipo no reconocido
// ---------------------------------------------------------------
ob_end_clean();
die('Tipo de reporte no válido. Use: constancia, listado o historial.');