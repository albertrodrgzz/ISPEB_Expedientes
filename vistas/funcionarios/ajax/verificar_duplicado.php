<?php
/**
 * AJAX: Verificar si un campo único ya está en uso
 * Campos soportados: cedula | telefono | email
 * 
 * GET/POST params:
 *   campo      => 'cedula' | 'telefono' | 'email'
 *   valor      => valor a verificar
 *   excluir_id => (opcional) ID del funcionario a excluir (para edición)
 */

ob_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/seguridad.php';

header('Content-Type: application/json; charset=utf-8');

try {
    verificarSesion();

    $campo      = trim($_REQUEST['campo']      ?? '');
    $valor      = trim($_REQUEST['valor']      ?? '');
    $excluir_id = (int)($_REQUEST['excluir_id'] ?? 0);

    // Campos permitidos (whitelist — evita inyección de nombre de columna)
    $camposPermitidos = ['cedula', 'telefono', 'email'];
    if (!in_array($campo, $camposPermitidos, true)) {
        throw new Exception('Campo no válido');
    }

    if ($valor === '') {
        // Campo vacío → no hay conflicto (puede ser opcional)
        echo json_encode(['disponible' => true, 'mensaje' => '']);
        exit;
    }

    $db = getDB();

    $sql    = "SELECT id, nombres, apellidos FROM funcionarios WHERE {$campo} = ?";
    $params = [$valor];

    if ($excluir_id > 0) {
        $sql    .= " AND id != ?";
        $params[] = $excluir_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch();

    if ($fila) {
        $nombre = $fila['nombres'] . ' ' . $fila['apellidos'];
        $labels = [
            'cedula'   => 'La cédula ya pertenece',
            'telefono' => 'El teléfono ya está registrado para',
            'email'    => 'El correo ya está registrado para',
        ];
        echo json_encode([
            'disponible' => false,
            'mensaje'    => $labels[$campo] . ': ' . $nombre
        ]);
    } else {
        echo json_encode(['disponible' => true, 'mensaje' => '']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['disponible' => false, 'mensaje' => $e->getMessage()]);
}
