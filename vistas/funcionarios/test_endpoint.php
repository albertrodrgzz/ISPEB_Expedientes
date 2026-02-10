<?php
/**
 * Test directo del endpoint obtener_historial.php
 */

// Simular sesión
session_start();
$_SESSION['usuario_id'] = 1; // Usuario admin
$_SESSION['nivel_acceso'] = 1;

require_once __DIR__ . '/../../config/database.php';

$pdo = getDB();

// Obtener ID del funcionario que tiene nombramiento
$stmt = $pdo->query("
    SELECT DISTINCT funcionario_id 
    FROM historial_administrativo 
    WHERE tipo_evento = 'NOMBRAMIENTO' 
    LIMIT 1
");
$result = $stmt->fetch();

if (!$result) {
    die("<h1>❌ ERROR: No hay ningún nombramiento en la base de datos</h1>");
}

$funcionario_id = $result['funcionario_id'];

echo "<h1>Test de Endpoint obtener_historial.php</h1>";
echo "<p><strong>Funcionario ID:</strong> $funcionario_id</p>";

// Simular la petición GET
$_GET['funcionario_id'] = $funcionario_id;
$_GET['tipo_evento'] = 'NOMBRAMIENTO';

echo "<h2>Parámetros enviados:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>Ejecutando endpoint...</h2>";

// Capturar output
ob_start();
include __DIR__ . '/ajax/obtener_historial.php';
$output = ob_get_clean();

echo "<h2>Respuesta del endpoint:</h2>";
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

echo "<h2>Respuesta decodificada:</h2>";
$data = json_decode($output, true);
if ($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    if (isset($data['success']) && $data['success']) {
        echo "<p style='color: green;'><strong>✅ Endpoint funciona correctamente</strong></p>";
        echo "<p><strong>Total registros:</strong> " . $data['total'] . "</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Error en el endpoint:</strong> " . ($data['error'] ?? 'Desconocido') . "</p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ La respuesta NO es JSON válido</strong></p>";
}
?>
