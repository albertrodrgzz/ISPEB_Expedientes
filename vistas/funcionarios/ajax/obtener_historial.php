<?php
/**
 * Controlador de Lectura - Historial Administrativo
 * 
 * Obtiene y formatea registros del historial administrativo para mostrar en las vistas
 * 
 * @author Sistema ISPEB v3.1
 * @date 2026-02-03
 */

// Seguridad y configuración
require_once '../../../config/seguridad.php';
require_once '../../../config/database.php';

verificarSesion();

// Configuración de respuesta JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Validar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido', 405);
    }

    // Obtener parámetros
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $tipo_evento = $_GET['tipo_evento'] ?? '';

    if (!$funcionario_id) {
        throw new Exception('ID de funcionario no especificado', 400);
    }

    // Validar tipo de evento si se especificó
    $tipos_validos = ['NOMBRAMIENTO', 'VACACION', 'AMONESTACION', 'REMOCION', 'TRASLADO', 'DESPIDO', 'RENUNCIA'];
    if (!empty($tipo_evento) && !in_array($tipo_evento, $tipos_validos)) {
        throw new Exception('Tipo de evento no válido', 400);
    }

    // Construir consulta SQL
    $sql = "
        SELECT 
            h.id,
            h.tipo_evento,
            h.fecha_evento,
            h.fecha_fin,
            h.detalles,
            h.ruta_archivo_pdf,
            h.nombre_archivo_original,
            h.created_at,
            h.updated_at,
            CONCAT(u.nombres, ' ', u.apellidos) as registrado_por_nombre,
            uf.username as registrado_por_usuario
        FROM historial_administrativo h
        LEFT JOIN usuarios uf ON h.registrado_por = uf.id
        LEFT JOIN funcionarios u ON uf.funcionario_id = u.id
        WHERE h.funcionario_id = ?
    ";

    $params = [$funcionario_id];

    // Filtrar por tipo de evento si se especificó
    if (!empty($tipo_evento)) {
        $sql .= " AND h.tipo_evento = ?";
        $params[] = $tipo_evento;
    }

    $sql .= " ORDER BY h.fecha_evento DESC, h.created_at DESC";

    // Obtener conexión a base de datos
    $pdo = getDB();

    // Ejecutar consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos para el frontend
    $datos_formateados = array_map(function($registro) {
        // Decodificar JSON de detalles
        $detalles = null;
        if ($registro['detalles']) {
            $detalles = json_decode($registro['detalles'], true);
        }

        // Formatear fechas
        $fecha_evento_formateada = null;
        if ($registro['fecha_evento']) {
            $fecha = new DateTime($registro['fecha_evento']);
            $fecha_evento_formateada = $fecha->format('d/m/Y');
        }

        $fecha_fin_formateada = null;
        if ($registro['fecha_fin']) {
            $fecha = new DateTime($registro['fecha_fin']);
            $fecha_fin_formateada = $fecha->format('d/m/Y');
        }

        $created_at_formateada = null;
        if ($registro['created_at']) {
            $fecha = new DateTime($registro['created_at']);
            $created_at_formateada = $fecha->format('d/m/Y H:i:s');
        }

        // Construir objeto de respuesta
        return [
            'id' => (int) $registro['id'],
            'tipo_evento' => $registro['tipo_evento'],
            'fecha_evento' => $registro['fecha_evento'],
            'fecha_evento_formateada' => $fecha_evento_formateada,
            'fecha_fin' => $registro['fecha_fin'],
            'fecha_fin_formateada' => $fecha_fin_formateada,
            'detalles' => $detalles,
            'tiene_archivo' => !empty($registro['ruta_archivo_pdf']),
            'ruta_archivo_pdf' => $registro['ruta_archivo_pdf'],
            'nombre_archivo_original' => $registro['nombre_archivo_original'],
            'registrado_por' => $registro['registrado_por_nombre'] ?? 'Sistema',
            'registrado_por_usuario' => $registro['registrado_por_usuario'],
            'created_at' => $registro['created_at'],
            'created_at_formateada' => $created_at_formateada,
            'updated_at' => $registro['updated_at']
        ];
    }, $registros);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'total' => count($datos_formateados),
        'tipo_evento' => $tipo_evento ?: 'TODOS',
        'data' => $datos_formateados
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'ERROR_OBTENER_HISTORIAL'
    ], JSON_UNESCAPED_UNICODE);
}
