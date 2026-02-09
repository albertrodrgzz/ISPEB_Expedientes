<?php
/**
 * Contador de Amonestaciones
 * Migrado a historial_administrativo
 * 
 * Cuenta y clasifica amonestaciones por gravedad
 */

require_once '../../../config/sesiones.php';
require_once '../../../config/database.php';

verificarSesion();

header('Content-Type: application/json; charset=utf-8');

try {
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido', 400);
    }
    
    $pdo = getDB();
    
    // Obtener datos del funcionario
    $stmt = $pdo->prepare("
        SELECT id, nombres, apellidos, estado
        FROM funcionarios
        WHERE id = ?
    ");
    $stmt->execute([$funcionario_id]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$funcionario) {
        throw new Exception('Funcionario no encontrado', 404);
    }
    
    // Contar total de amonestaciones
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM historial_administrativo
        WHERE funcionario_id = ?
        AND tipo_evento = 'AMONESTACION'
    ");
    $stmt->execute([$funcionario_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_amonestaciones = (int) $resultado['total'];
    
    // Contar por tipo de falta
    $stmt = $pdo->prepare("
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.tipo_falta')) as tipo_falta,
            COUNT(*) as cantidad
        FROM historial_administrativo
        WHERE funcionario_id = ?
        AND tipo_evento = 'AMONESTACION'
        GROUP BY tipo_falta
    ");
    $stmt->execute([$funcionario_id]);
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $por_tipo = [
        'leve' => 0,
        'grave' => 0,
        'muy_grave' => 0
    ];
    
    foreach ($tipos as $tipo) {
        $tipo_falta = $tipo['tipo_falta'] ?? 'leve';
        $por_tipo[$tipo_falta] = (int) $tipo['cantidad'];
    }
    
    // Calcular nivel de riesgo
    // Criterio: leve = 1 punto, grave = 3 puntos, muy_grave = 5 puntos
    $puntos_riesgo = ($por_tipo['leve'] * 1) + ($por_tipo['grave'] * 3) + ($por_tipo['muy_grave'] * 5);
    
    $nivel_riesgo = 'sin_riesgo';
    $porcentaje_riesgo = 0;
    $mensaje_riesgo = 'Sin amonestaciones registradas';
    $color_riesgo = '#10b981'; // verde
    
    if ($puntos_riesgo >= 15) {
        $nivel_riesgo = 'critico';
        $porcentaje_riesgo = 100;
        $mensaje_riesgo = '⚠️ NIVEL CRÍTICO - Considerar acciones disciplinarias mayores';
        $color_riesgo = '#dc2626'; // rojo
    } elseif ($puntos_riesgo >= 10) {
        $nivel_riesgo = 'alto';
        $porcentaje_riesgo = 75;
        $mensaje_riesgo = 'Nivel de riesgo ALTO - Requiere atención inmediata';
        $color_riesgo = '#f97316'; // naranja
    } elseif ($puntos_riesgo >= 5) {
        $nivel_riesgo = 'medio';
        $porcentaje_riesgo = 50;
        $mensaje_riesgo = 'Nivel de riesgo MEDIO - Monitorear de cerca';
        $color_riesgo = '#eab308'; // amarillo
    } elseif ($puntos_riesgo > 0) {
        $nivel_riesgo = 'bajo';
        $porcentaje_riesgo = 25;
        $mensaje_riesgo = 'Nivel de riesgo BAJO - Sin preocupación mayor';
        $color_riesgo = '#84cc16'; // verde-lima
    }
    
    // Obtener las 3 amonestaciones más recientes
    $stmt = $pdo->prepare("
        SELECT 
            id,
            fecha_evento,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.tipo_falta')) as tipo_falta,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.motivo')) as motivo,
            JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.sancion')) as sancion,
            DATE_FORMAT(fecha_evento, '%d/%m/%Y') as fecha_formateada
        FROM historial_administrativo
        WHERE funcionario_id = ?
        AND tipo_evento = 'AMONESTACION'
        ORDER BY fecha_evento DESC
        LIMIT 3
    ");
    $stmt->execute([$funcionario_id]);
    $recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'funcionario' => [
                'id' => $funcionario['id'],
                'nombre_completo' => $funcionario['nombres'] . ' ' . $funcionario['apellidos'],
                'estado' => $funcionario['estado']
            ],
            'conteo' => [
                'total' => $total_amonestaciones,
                'por_tipo' => $por_tipo
            ],
            'nivel_riesgo' => [
                'nivel' => $nivel_riesgo,
                'puntos' => $puntos_riesgo,
                'porcentaje' => $porcentaje_riesgo,
                'mensaje' => $mensaje_riesgo,
                'color' => $color_riesgo
            ],
            'recientes' => $recientes
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
