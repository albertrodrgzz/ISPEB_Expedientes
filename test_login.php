<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE usuarios 
        SET 
            password_hash = NULL,
            registro_completado = 0,
            pregunta_seguridad_1 = NULL,
            respuesta_seguridad_1 = NULL,
            pregunta_seguridad_2 = NULL,
            respuesta_seguridad_2 = NULL,
            pregunta_seguridad_3 = NULL,
            respuesta_seguridad_3 = NULL
        WHERE id = 1
    ");
    $stmt->execute();
    echo "User 1 reset successful.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
