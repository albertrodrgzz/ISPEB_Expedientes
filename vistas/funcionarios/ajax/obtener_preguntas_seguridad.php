<?php
/**
 * AJAX: Obtener lista de preguntas de seguridad disponibles
 */

require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

try {
    // Lista predefinida de preguntas de seguridad
    $preguntas = [
        ['id' => 1, 'pregunta' => '¿Cuál es el nombre de tu primera mascota?'],
        ['id' => 2, 'pregunta' => '¿En qué ciudad naciste?'],
        ['id' => 3, 'pregunta' => '¿Cuál es el nombre de tu mejor amigo de la infancia?'],
        ['id' => 4, 'pregunta' => '¿Cuál es tu comida favorita?'],
        ['id' => 5, 'pregunta' => '¿Cuál es el nombre de tu escuela primaria?'],
        ['id' => 6, 'pregunta' => '¿Cuál es el segundo nombre de tu madre?'],
        ['id' => 7, 'pregunta' => '¿Cuál es el segundo nombre de tu padre?'],
        ['id' => 8, 'pregunta' => '¿En qué año te graduaste de bachillerato?'],
        ['id' => 9, 'pregunta' => '¿Cuál es tu color favorito?'],
        ['id' => 10, 'pregunta' => '¿Cuál es el nombre de tu película favorita?'],
        ['id' => 11, 'pregunta' => '¿Cuál es tu equipo deportivo favorito?'],
        ['id' => 12, 'pregunta' => '¿Cuál es el nombre de tu libro favorito?'],
        ['id' => 13, 'pregunta' => '¿Cuál es tu lugar de vacaciones favorito?'],
        ['id' => 14, 'pregunta' => '¿Cuál es el nombre de tu primer trabajo?'],
        ['id' => 15, 'pregunta' => '¿Cuál es tu número de la suerte?']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $preguntas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener preguntas de seguridad'
    ]);
}
