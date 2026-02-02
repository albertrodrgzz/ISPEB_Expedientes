<?php
/**
 * Generador de Constancia de Trabajo
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';

// Verificar sesión
verificarSesion();

// Obtener ID del funcionario
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('ID de funcionario no válido');
}

$db = getDB();

// Obtener datos del funcionario
$stmt = $db->prepare("
    SELECT 
        f.id,
        f.cedula,
        f.nombres,
        f.apellidos,
        f.fecha_ingreso,
        c.nombre_cargo,
        d.nombre as departamento,
        f.genero
    FROM funcionarios f
    INNER JOIN cargos c ON f.cargo_id = c.id
    INNER JOIN departamentos d ON f.departamento_id = d.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    die('Funcionario no encontrado');
}

// VALIDACIÓN DE SEGURIDAD
$usuario_id = $_SESSION['funcionario_id'];
$nivel_acceso = $_SESSION['nivel_acceso'];

// Si es nivel 3 (usuario normal) solo puede ver su propia constancia
if ($nivel_acceso == 3 && $usuario_id != $id) {
    die('Acceso denegado. No tiene permisos para generar esta constancia.');
}

// Calcular antigüedad
$fecha_ingreso = new DateTime($funcionario['fecha_ingreso']);
$fecha_actual = new DateTime();
$diferencia = $fecha_ingreso->diff($fecha_actual);
$antiguedad = $diferencia->y . ' año' . ($diferencia->y != 1 ? 's' : '');
if ($diferencia->m > 0) {
    $antiguedad .= ' y ' . $diferencia->m . ' mes' . ($diferencia->m != 1 ? 'es' : '');
}

// Formatear fecha actual en español
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$dia = date('d');
$mes = $meses[(int)date('m')];
$anio = date('Y');
$fecha_emision = "$dia de $mes de $anio";

// Formatear fecha de ingreso
$dia_ingreso = $fecha_ingreso->format('d');
$mes_ingreso = $meses[(int)$fecha_ingreso->format('m')];
$anio_ingreso = $fecha_ingreso->format('Y');
$fecha_ingreso_texto = "$dia_ingreso de $mes_ingreso de $anio_ingreso";

// Determinar género para el texto
$genero_texto = ($funcionario['genero'] == 'F') ? 'la ciudadana' : 'el ciudadano';

// Crear PDF usando FPDF
require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

class PDF extends FPDF
{
    function Header()
    {
        // Logo ISPEB (izquierda)
        $logo_path = __DIR__ . '/../../publico/imagenes/cintillo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 30);
        }
        
        // Membrete derecha
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(150, 10);
        $this->MultiCell(50, 4, utf8_decode("REPÚBLICA BOLIVARIANA\nDE VENEZUELA\nGOBERNACIÓN DEL\nESTADO BOLÍVAR"), 0, 'C');
        
        $this->Ln(15);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

// Crear instancia del PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetMargins(25, 25, 25);

// Título
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('CONSTANCIA DE TRABAJO'), 0, 1, 'C');
$pdf->Ln(10);

// Cuerpo del documento
$pdf->SetFont('Arial', '', 12);

// Párrafo 1
$texto1 = "Quien suscribe, Director de la Dirección de Telemática del Instituto de Salud Pública del Estado Bolívar (ISPEB), por medio de la presente hace constar que $genero_texto:";
$pdf->MultiCell(0, 6, utf8_decode($texto1), 0, 'J');
$pdf->Ln(5);

// Datos del funcionario (centrado y en negrita)
$pdf->SetFont('Arial', 'B', 12);
$nombre_completo = strtoupper($funcionario['nombres'] . ' ' . $funcionario['apellidos']);
$pdf->Cell(0, 6, utf8_decode($nombre_completo), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, utf8_decode('Titular de la Cédula de Identidad N° ' . $funcionario['cedula']), 0, 1, 'C');
$pdf->Ln(5);

// Párrafo 2
$texto2 = "Presta sus servicios en esta institución desde el $fecha_ingreso_texto, acumulando una antigüedad de $antiguedad, desempeñando actualmente el cargo de " . strtoupper($funcionario['nombre_cargo']) . ", adscrito al departamento de " . strtoupper($funcionario['departamento']) . ".";
$pdf->MultiCell(0, 6, utf8_decode($texto2), 0, 'J');
$pdf->Ln(5);

// Párrafo 3
$texto3 = "Constancia que se expide a petición de la parte interesada en Ciudad Bolívar, a los $fecha_emision.";
$pdf->MultiCell(0, 6, utf8_decode($texto3), 0, 'J');
$pdf->Ln(20);

// Firma
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(15);
$pdf->Cell(0, 6, '_________________________________', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, utf8_decode('DIRECTOR DE TELEMÁTICA'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'ISPEB', 0, 1, 'C');

// Pie de página adicional
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, utf8_decode("Instituto de Salud Pública del Estado Bolívar\nDirección de Telemática\nCiudad Bolívar - Estado Bolívar"), 0, 'C');

// Registrar en auditoría
registrarAuditoria('GENERAR_CONSTANCIA', 'funcionarios', $id, null, [
    'funcionario' => $nombre_completo,
    'generado_por' => $_SESSION['nombre_completo']
]);

// Nombre del archivo
$nombre_archivo = 'Constancia_Trabajo_' . str_replace(' ', '_', $funcionario['nombres']) . '_' . str_replace(' ', '_', $funcionario['apellidos']) . '.pdf';

// Headers para forzar visualización en navegador
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombre_archivo . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Salida del PDF
$pdf->Output('I', $nombre_archivo); // 'I' = Inline (previsualizar en navegador)
