<?php
/**
 * API: Contar solicitudes pendientes
 * Devuelve JSON: {"count": N}
 * Solo para administradores / RRHH (nivel 1-2)
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/seguridad.php';

// Verificar sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado', 'count' => 0]);
    exit;
}

// Solo nivel 1 y 2 gestionan la bandeja
$nivel = $_SESSION['nivel_acceso'] ?? 3;
if ($nivel > 2) {
    // Nivel 3 no gestiona solicitudes ajenas — devolver 0 silenciosamente
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM   solicitudes_empleados
        WHERE  estado = 'pendiente'
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'count'   => (int) ($row['total'] ?? 0),
        'success' => true
    ]);

} catch (PDOException $e) {
    // No exponer detalles del error en producción
    http_response_code(500);
    echo json_encode(['count' => 0, 'error' => 'DB Error']);
}
