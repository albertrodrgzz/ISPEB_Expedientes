<?php
/**
 * Generador de Reportes en PDF
 * Versión básica sin librerías externas
 * 
 * NOTA: Para reportes profesionales, se recomienda instalar FPDF o TCPDF:
 * - FPDF: http://www.fpdf.org/
 * - TCPDF: https://tcpdf.org/
 * 
 * Instalación con Composer:
 * composer require setasign/fpdf
 * composer require tecnickcom/tcpdf
 */


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/seguridad.php';
require_once __DIR__ . '/../../modelos/Funcionario.php';

// Verificar sesión y permisos
verificarSesion();

if (!verificarNivel(2)) {
    die('No tiene permisos para generar reportes');
}

$tipo = $_GET['tipo'] ?? 'general';
$db = getDB();

// Registrar en auditoría
registrarAuditoria('GENERAR_REPORTE_PDF', null, null, null, ['tipo' => $tipo]);

// Configurar headers para PDF
header('Content-Type: text/html; charset=UTF-8');

// Función para generar HTML que se puede imprimir como PDF
function generarHTML($titulo, $contenido) {
    $fecha = date('d/m/Y H:i:s');
    $usuario = $_SESSION['nombre_completo'];
    
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>$titulo - ISPEB</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #00a8cc;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #00a8cc;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
            margin-top: 15px;
        }
        .meta-info {
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #00a8cc;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) {
            background: #f7fafc;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2d3748;
            margin-top: 25px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #00a8cc;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .print-button:hover {
            background: #0088aa;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-right: 6px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Imprimir / Guardar PDF
    </button>
    
    <div class="header">
        <div class="logo">📁 ISPEB</div>
        <div class="subtitle">Instituto de Previsión Social del Estado Bolívar</div>
        <div class="subtitle">Dirección de Telemática</div>
        <div class="report-title">$titulo</div>
        <div class="meta-info">
            Generado por: $usuario | Fecha: $fecha
        </div>
    </div>
    
    $contenido
    
    <div class="footer">
        <p>© ISPEB - Dirección de Telemática | Sistema de Gestión de Expedientes Digitales</p>
        <p>Este documento es de carácter confidencial y solo debe ser utilizado para fines oficiales</p>
    </div>
    
    <script>
        // Auto-print cuando se carga la página (opcional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
HTML;
}

// Generar contenido según el tipo de reporte
$contenido = '';

switch ($tipo) {
    case 'general':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
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
        $funcionarios = $stmt->fetchAll();
        
        $contenido .= '<table>';
        $contenido .= '<thead><tr>';
        $contenido .= '<th>Cédula</th>';
        $contenido .= '<th>Nombre Completo</th>';
        $contenido .= '<th>Cargo</th>';
        $contenido .= '<th>Departamento</th>';
        $contenido .= '<th>Antigüedad</th>';
        $contenido .= '<th>Estado</th>';
        $contenido .= '</tr></thead><tbody>';
        
        foreach ($funcionarios as $func) {
            $contenido .= '<tr>';
            $contenido .= '<td>' . htmlspecialchars($func['cedula']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['nombre_completo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['nombre_cargo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['departamento']) . '</td>';
            $contenido .= '<td>' . $func['antiguedad'] . ' año(s)</td>';
            $contenido .= '<td>' . ucfirst($func['estado']) . '</td>';
            $contenido .= '</tr>';
        }
        
        $contenido .= '</tbody></table>';
        $contenido .= '<p style="margin-top: 20px;"><strong>Total de funcionarios:</strong> ' . count($funcionarios) . '</p>';
        
        echo generarHTML('Listado General de Funcionarios', $contenido);
        break;
        
    case 'departamento':
        $stmt = $db->query("SELECT * FROM departamentos WHERE estado = 'activo' ORDER BY nombre");
        $departamentos = $stmt->fetchAll();
        
        foreach ($departamentos as $dept) {
            $contenido .= '<div class="section-title">' . htmlspecialchars($dept['nombre']) . '</div>';
            
            $stmt = $db->prepare("
                SELECT 
                    f.cedula,
                    CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
                    c.nombre_cargo,
                    f.fecha_ingreso,
                    TIMESTAMPDIFF(YEAR, f.fecha_ingreso, CURDATE()) AS antiguedad
                FROM funcionarios f
                INNER JOIN cargos c ON f.cargo_id = c.id
                WHERE f.departamento_id = ? AND f.estado != 'inactivo'
                ORDER BY f.apellidos, f.nombres
            ");
            $stmt->execute([$dept['id']]);
            $funcionarios = $stmt->fetchAll();
            
            if (count($funcionarios) > 0) {
                $contenido .= '<table>';
                $contenido .= '<thead><tr>';
                $contenido .= '<th>Cédula</th>';
                $contenido .= '<th>Nombre Completo</th>';
                $contenido .= '<th>Cargo</th>';
                $contenido .= '<th>Antigüedad</th>';
                $contenido .= '</tr></thead><tbody>';
                
                foreach ($funcionarios as $func) {
                    $contenido .= '<tr>';
                    $contenido .= '<td>' . htmlspecialchars($func['cedula']) . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($func['nombre_completo']) . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($func['nombre_cargo']) . '</td>';
                    $contenido .= '<td>' . $func['antiguedad'] . ' año(s)</td>';
                    $contenido .= '</tr>';
                }
                
                $contenido .= '</tbody></table>';
                $contenido .= '<p style="margin-top: 10px;"><strong>Total:</strong> ' . count($funcionarios) . ' funcionario(s)</p>';
            } else {
                $contenido .= '<p style="color: #666; font-style: italic;">No hay funcionarios en este departamento</p>';
            }
        }
        
        echo generarHTML('Reporte por Departamento', $contenido);
        break;
        
    case 'vacaciones':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
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
        $funcionarios = $stmt->fetchAll();
        
        $contenido .= '<table>';
        $contenido .= '<thead><tr>';
        $contenido .= '<th>Cédula</th>';
        $contenido .= '<th>Nombre Completo</th>';
        $contenido .= '<th>Departamento</th>';
        $contenido .= '<th>Días Disponibles</th>';
        $contenido .= '<th>Días Usados (' . date('Y') . ')</th>';
        $contenido .= '<th>Días Pendientes</th>';
        $contenido .= '</tr></thead><tbody>';
        
        foreach ($funcionarios as $func) {
            $pendientes = $func['dias_disponibles'] - $func['dias_usados'];
            $contenido .= '<tr>';
            $contenido .= '<td>' . htmlspecialchars($func['cedula']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['nombre_completo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['departamento']) . '</td>';
            $contenido .= '<td>' . $func['dias_disponibles'] . '</td>';
            $contenido .= '<td>' . $func['dias_usados'] . '</td>';
            $contenido .= '<td style="font-weight: bold; color: ' . ($pendientes > 0 ? '#10b981' : '#ef476f') . ';">' . $pendientes . '</td>';
            $contenido .= '</tr>';
        }
        
        $contenido .= '</tbody></table>';
        
        echo generarHTML('Control Vacacional', $contenido);
        break;
        
    case 'amonestaciones':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
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
        $amonestaciones = $stmt->fetchAll();
        
        if (count($amonestaciones) > 0) {
            $contenido .= '<table>';
            $contenido .= '<thead><tr>';
            $contenido .= '<th>Fecha</th>';
            $contenido .= '<th>Funcionario</th>';
            $contenido .= '<th>Departamento</th>';
            $contenido .= '<th>Tipo de Falta</th>';
            $contenido .= '<th>Sanción</th>';
            $contenido .= '</tr></thead><tbody>';
            
            foreach ($amonestaciones as $amon) {
                $contenido .= '<tr>';
                $contenido .= '<td>' . date('d/m/Y', strtotime($amon['fecha_falta'])) . '</td>';
                $contenido .= '<td>' . htmlspecialchars($amon['nombre_completo']) . ' (' . htmlspecialchars($amon['cedula']) . ')</td>';
                $contenido .= '<td>' . htmlspecialchars($amon['departamento']) . '</td>';
                $contenido .= '<td>' . htmlspecialchars($amon['tipo_falta']) . '</td>';
                $contenido .= '<td>' . htmlspecialchars($amon['sancion_aplicada'] ?? '-') . '</td>';
                $contenido .= '</tr>';
            }
            
            $contenido .= '</tbody></table>';
            $contenido .= '<p style="margin-top: 20px;"><strong>Total de amonestaciones en ' . date('Y') . ':</strong> ' . count($amonestaciones) . '</p>';
        } else {
            $contenido .= '<p style="text-align: center; padding: 40px; color: #666;">No hay amonestaciones registradas en el año ' . date('Y') . '</p>';
        }
        
        echo generarHTML('Historial de Amonestaciones ' . date('Y'), $contenido);
        break;
        
    case 'antiguedad':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
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
        $funcionarios = $stmt->fetchAll();
        
        $contenido .= '<table>';
        $contenido .= '<thead><tr>';
        $contenido .= '<th>Cédula</th>';
        $contenido .= '<th>Nombre Completo</th>';
        $contenido .= '<th>Cargo</th>';
        $contenido .= '<th>Departamento</th>';
        $contenido .= '<th>Fecha Ingreso</th>';
        $contenido .= '<th>Antigüedad</th>';
        $contenido .= '</tr></thead><tbody>';
        
        foreach ($funcionarios as $func) {
            $antiguedad = $func['antiguedad_anos'] . ' año(s)';
            if ($func['antiguedad_meses'] > 0) {
                $antiguedad .= ', ' . $func['antiguedad_meses'] . ' mes(es)';
            }
            
            $contenido .= '<tr>';
            $contenido .= '<td>' . htmlspecialchars($func['cedula']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['nombre_completo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['nombre_cargo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($func['departamento']) . '</td>';
            $contenido .= '<td>' . date('d/m/Y', strtotime($func['fecha_ingreso'])) . '</td>';
            $contenido .= '<td><strong>' . $antiguedad . '</strong></td>';
            $contenido .= '</tr>';
        }
        
        $contenido .= '</tbody></table>';
        
        echo generarHTML('Antigüedad del Personal', $contenido);
        break;
        
    case 'nombramientos':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
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
        $nombramientos = $stmt->fetchAll();
        
        if (count($nombramientos) > 0) {
            $contenido .= '<table>';
            $contenido .= '<thead><tr>';
            $contenido .= '<th>Funcionario</th>';
            $contenido .= '<th>Departamento</th>';
            $contenido .= '<th>Categoría</th>';
            $contenido .= '<th>Título</th>';
            $contenido .= '<th>Fecha Inicio</th>';
            $contenido .= '<th>Fecha Fin</th>';
            $contenido .= '</tr></thead><tbody>';
            
            foreach ($nombramientos as $nom) {
                $contenido .= '<tr>';
                $contenido .= '<td>' . htmlspecialchars($nom['nombre_completo']) . ' (' . htmlspecialchars($nom['cedula']) . ')</td>';
                $contenido .= '<td>' . htmlspecialchars($nom['departamento']) . '</td>';
                $contenido .= '<td>' . htmlspecialchars($nom['categoria'] ?? '-') . '</td>';
                $contenido .= '<td>' . htmlspecialchars($nom['titulo']) . '</td>';
                $contenido .= '<td>' . date('d/m/Y', strtotime($nom['fecha_inicio'])) . '</td>';
                $contenido .= '<td>' . ($nom['fecha_fin'] ? date('d/m/Y', strtotime($nom['fecha_fin'])) : 'Indefinido') . '</td>';
                $contenido .= '</tr>';
            }
            
            $contenido .= '</tbody></table>';
            $contenido .= '<p style="margin-top: 20px;"><strong>Total de nombramientos activos:</strong> ' . count($nombramientos) . '</p>';
        } else {
            $contenido .= '<p style="text-align: center; padding: 40px; color: #666;">No hay nombramientos activos registrados</p>';
        }
        
        echo generarHTML('Nombramientos Activos', $contenido);
        break;
        
    case 'cumpleanos':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
                c.nombre_cargo,
                d.nombre AS departamento,
                f.fecha_nacimiento,
                DAY(f.fecha_nacimiento) AS dia_cumple,
                TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) AS edad_actual
            FROM funcionarios f
            INNER JOIN cargos c ON f.cargo_id = c.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.estado != 'inactivo'
            AND MONTH(f.fecha_nacimiento) = MONTH(CURDATE())
            ORDER BY DAY(f.fecha_nacimiento) ASC
        ");
        $cumpleanos = $stmt->fetchAll();
        
        if (count($cumpleanos) > 0) {
            $contenido .= '<table>';
            $contenido .= '<thead><tr>';
            $contenido .= '<th>Día</th>';
            $contenido .= '<th>Funcionario</th>';
            $contenido .= '<th>Cargo</th>';
            $contenido .= '<th>Departamento</th>';
            $contenido .= '<th>Edad</th>';
            $contenido .= '</tr></thead><tbody>';
            
            foreach ($cumpleanos as $cump) {
                $contenido .= '<tr>';
                $contenido .= '<td><strong>' . $cump['dia_cumple'] . ' de ' . strftime('%B', mktime(0, 0, 0, date('m'), 1)) . '</strong></td>';
                $contenido .= '<td>' . htmlspecialchars($cump['nombre_completo']) . ' (' . htmlspecialchars($cump['cedula']) . ')</td>';
                $contenido .= '<td>' . htmlspecialchars($cump['nombre_cargo']) . '</td>';
                $contenido .= '<td>' . htmlspecialchars($cump['departamento']) . '</td>';
                $contenido .= '<td>' . ($cump['edad_actual'] + 1) . ' años</td>';
                $contenido .= '</tr>';
            }
            
            $contenido .= '</tbody></table>';
            $contenido .= '<p style="margin-top: 20px;"><strong>Total de cumpleaños este mes:</strong> ' . count($cumpleanos) . '</p>';
        } else {
            $contenido .= '<p style="text-align: center; padding: 40px; color: #666;">No hay cumpleaños registrados este mes</p>';
        }
        
        echo generarHTML('Cumpleaños del Mes - ' . strftime('%B %Y'), $contenido);
        break;
        
    case 'por_vencer':
        $stmt = $db->query("
            SELECT 
                f.cedula,
                CONCAT(f.nombres, ' ', f.apellidos) AS nombre_completo,
                d.nombre AS departamento,
                'vacaciones' as tipo_documento,
                CONCAT('Vacaciones: ', JSON_UNQUOTE(JSON_EXTRACT(ha.detalles, '$.periodo'))) as titulo,
                ha.fecha_fin,
                DATEDIFF(ha.fecha_fin, CURDATE()) AS dias_restantes
            FROM historial_administrativo ha
            INNER JOIN funcionarios f ON ha.funcionario_id = f.id
            INNER JOIN departamentos d ON f.departamento_id = d.id
            WHERE ha.tipo_evento = 'VACACION' AND ha.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ha.fecha_fin ASC
        ");
        $documentos = $stmt->fetchAll();
        
        if (count($documentos) > 0) {
            $contenido .= '<table>';
            $contenido .= '<thead><tr>';
            $contenido .= '<th>Funcionario</th>';
            $contenido .= '<th>Departamento</th>';
            $contenido .= '<th>Tipo</th>';
            $contenido .= '<th>Documento</th>';
            $contenido .= '<th>Fecha Vencimiento</th>';
            $contenido .= '<th>Días Restantes</th>';
            $contenido .= '</tr></thead><tbody>';
            
            foreach ($documentos as $doc) {
                $urgencia_color = $doc['dias_restantes'] <= 7 ? '#ef476f' : ($doc['dias_restantes'] <= 15 ? '#ff9f1c' : '#666');
                $contenido .= '<tr>';
                $contenido .= '<td>' . htmlspecialchars($doc['nombre_completo']) . ' (' . htmlspecialchars($doc['cedula']) . ')</td>';
                $contenido .= '<td>' . htmlspecialchars($doc['departamento']) . '</td>';
                $contenido .= '<td>' . ucfirst($doc['tipo_documento']) . '</td>';
                $contenido .= '<td>' . htmlspecialchars($doc['titulo']) . '</td>';
                $contenido .= '<td>' . date('d/m/Y', strtotime($doc['fecha_fin'])) . '</td>';
                $contenido .= '<td style="font-weight: bold; color: ' . $urgencia_color . ';">' . $doc['dias_restantes'] . ' día(s)</td>';
                $contenido .= '</tr>';
            }
            
            $contenido .= '</tbody></table>';
            $contenido .= '<p style="margin-top: 20px;"><strong>Total de documentos por vencer:</strong> ' . count($documentos) . '</p>';
        } else {
            $contenido .= '<p style="text-align: center; padding: 40px; color: #666;">No hay documentos por vencer en los próximos 30 días</p>';
        }
        
        echo generarHTML('Documentos por Vencer (Próximos 30 Días)', $contenido);
        break;
        
    default:
        die('Tipo de reporte no válido');
}
