<?php
/**
 * Contador de Amonestaciones (CORREGIDO)
 * Solución: Se reemplazó sesiones.php por seguridad.php para cargar verificarSesion()
 */

// CORRECCIÓN AQUÍ: Usar seguridad.php en lugar de sesiones.php
require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/database.php';

// Verificar sesión correctamente
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

try {
    $funcionario_id = filter_var($_GET['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if (!$funcionario_id) {
        throw new Exception('ID de funcionario requerido', 400);
    }
    
    $pdo = getDB();
    
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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'conteo' => [
                'total' => $total_amonestaciones
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>