<?php
/**
 * Constancia de Trabajo — SIGED
 * Refactorizada v2.0: cintillo completo, mb_strtoupper, antigüedad dinámica, sin firma.
 */

ob_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

verificarSesion();

// ---- Obtener ID del funcionario ----
$nivel_acceso = $_SESSION['nivel_acceso'] ?? 3;

if ($nivel_acceso >= 3) {
    // Nivel 3: solo puede ver su propia constancia
    $id = (int)($_SESSION['funcionario_id'] ?? 0);
} else {
    $id = (int)($_GET['id'] ?? 0);
}

if ($id <= 0) {
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
    INNER JOIN cargos  c ON f.cargo_id        = c.id
    INNER JOIN departamentos d ON f.departamento_id = d.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    ob_end_clean();
    die('Funcionario no encontrado.');
}

// Validación extra de seguridad para nivel 3:
// Si es nivel 3, el $id ya fue forzado a su propio funcionario_id (líneas 18-23).
// Esta segunda validación es una capa defensiva adicional.
if ($nivel_acceso >= 3 && ((int)($_SESSION['funcionario_id'] ?? 0)) !== $id) {
    ob_end_clean();
    die('Acceso denegado: no puede ver la constancia de otro funcionario.');
}

// =========================================================
// HELPER: convertir texto a ISO-8859-1 con mayúsculas perfectas
// Primero mb_strtoupper (respeta acentos), luego convierte encoding
// =========================================================
function pdf_upper(string $str): string {
    return mb_convert_encoding(mb_strtoupper($str, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
}
function pdf_enc(string $str): string {
    return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
}

// =========================================================
// ANTIGÜEDAD DINÁMICA
// =========================================================
$fechaIngreso = new DateTime($funcionario['fecha_ingreso']);
$hoy          = new DateTime();
$diff         = $fechaIngreso->diff($hoy);

$anios  = $diff->y;
$meses  = $diff->m;
$dias   = $diff->d;

if ($anios >= 1) {
    // 1 año o más → mostrar años y meses
    $antiguedad  = $anios . ' año' . ($anios != 1 ? 's' : '');
    if ($meses > 0) {
        $antiguedad .= ' y ' . $meses . ' mes' . ($meses != 1 ? 'es' : '');
    }
} elseif ($meses >= 1) {
    // Menos de 1 año pero >= 1 mes → mostrar meses y días
    $antiguedad  = $meses . ' mes' . ($meses != 1 ? 'es' : '');
    if ($dias > 0) {
        $antiguedad .= ' y ' . $dias . ' día' . ($dias != 1 ? 's' : '');
    }
} else {
    // Menos de 1 mes → solo días
    $antiguedad = $dias . ' día' . ($dias != 1 ? 's' : '');
}

// =========================================================
// FECHAS EN ESPAÑOL
// =========================================================
$mesesES = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo',     4=>'Abril',
    5=>'Mayo',  6=>'Junio',   7=>'Julio',      8=>'Agosto',
    9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];
$fecha_emision      = date('d') . ' de ' . $mesesES[(int)date('m')] . ' de ' . date('Y');
$dia_ing            = $fechaIngreso->format('d');
$mes_ing            = $mesesES[(int)$fechaIngreso->format('m')];
$anio_ing           = $fechaIngreso->format('Y');
$fecha_ingreso_txt  = "$dia_ing de $mes_ing de $anio_ing";

// Género gramatical
$genero_texto = ($funcionario['genero'] === 'F') ? 'la ciudadana' : 'el ciudadano';

// Nombre completo en MAYÚSCULAS con acentos correctos
$nombre_completo = mb_strtoupper($funcionario['nombres'] . ' ' . $funcionario['apellidos'], 'UTF-8');

// =========================================================
// FPDF
// =========================================================
require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

class PDF_Constancia extends FPDF
{
    function Header()
    {
        // ── Cintillo a todo el ancho (190mm) ──
        $logo = __DIR__ . '/../../publico/imagenes/cintillo.png';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 10, 190);
        }
        // Sin encabezado de texto (REPÚBLICA...) — solo el cintillo visual
        $this->Ln(32); // espacio debajo del cintillo
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10,
            mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo(),
            0, 0, 'C');
    }
}

$pdf = new PDF_Constancia();
$pdf->AddPage();
$pdf->SetMargins(25, 15, 25);

// ---- Título ----
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, pdf_enc('CONSTANCIA DE TRABAJO'), 0, 1, 'C');
$pdf->Ln(10);

// ---- Párrafo 1 ----
$pdf->SetFont('Arial', '', 12);
$texto1 = "Quien suscribe, Director de la Dirección de Telemática del Instituto "
        . "de Salud Pública del Estado Bolívar (ISPEB), por medio de la presente "
        . "hace constar que $genero_texto:";
$pdf->MultiCell(0, 6, pdf_enc($texto1), 0, 'J');
$pdf->Ln(5);

// ---- Datos en negrita centrada ----
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 7, pdf_upper($nombre_completo), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6,
    pdf_enc('Titular de la Cédula de Identidad N° ' . $funcionario['cedula']),
    0, 1, 'C');
$pdf->Ln(5);

// ---- Párrafo 2 ----
$cargo_upper = mb_strtoupper($funcionario['nombre_cargo'], 'UTF-8');
$depto_upper = mb_strtoupper($funcionario['departamento'], 'UTF-8');

$texto2 = "Presta sus servicios en esta institución desde el $fecha_ingreso_txt, "
        . "acumulando una antigüedad de $antiguedad, desempeñando actualmente el cargo de "
        . "$cargo_upper, adscrito al departamento de $depto_upper.";
$pdf->MultiCell(0, 6, pdf_upper($texto2), 0, 'J');
$pdf->Ln(5);

// ---- Párrafo 3 ----
$texto3 = "Constancia que se expide a petición de la parte interesada "
        . "en Ciudad Bolívar, a los $fecha_emision.";
$pdf->MultiCell(0, 6, pdf_enc($texto3), 0, 'J');
$pdf->Ln(25);

// ---- SIN FIRMA — eliminada por instrucción del usuario ----

// ---- Pie institucional ----
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 4,
    pdf_enc("Instituto de Salud Pública del Estado Bolívar\n"
          . "Dirección de Telemática — Ciudad Bolívar, Estado Bolívar"),
    0, 'C');

// ---- Auditoría ----
registrarAuditoria('GENERAR_CONSTANCIA', 'funcionarios', $id, null, [
    'funcionario'  => $nombre_completo,
    'generado_por' => $_SESSION['nombre_completo'] ?? 'Usuario'
]);

// ---- Limpiar buffer y enviar PDF ----
if (ob_get_length()) ob_end_clean();

$nombre_pdf = 'Constancia_Trabajo_'
    . str_replace(' ', '_', $funcionario['nombres']) . '_'
    . str_replace(' ', '_', $funcionario['apellidos']) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombre_pdf . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('I', $nombre_pdf);
exit;