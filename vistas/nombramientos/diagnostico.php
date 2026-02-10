<?php
/**
 * Script de Diagnóstico - Nombramientos
 * Verifica datos en historial_administrativo
 */

require_once __DIR__ . '/../../config/database.php';

$pdo = getDB();

echo "<h1>Diagnóstico de Nombramientos</h1>";

// 1. Contar nombramientos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM historial_administrativo WHERE tipo_evento = 'NOMBRAMIENTO'");
$total = $stmt->fetch()['total'];
echo "<p><strong>Total de nombramientos:</strong> $total</p>";

// 2. Mostrar último nombramiento
$stmt = $pdo->query("
    SELECT 
        h.id,
        h.funcionario_id,
        h.tipo_evento,
        h.fecha_evento,
        h.detalles,
        h.ruta_archivo_pdf,
        f.nombres,
        f.apellidos,
        f.cedula
    FROM historial_administrativo h
    INNER JOIN funcionarios f ON h.funcionario_id = f.id
    WHERE h.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY h.created_at DESC
    LIMIT 1
");
$ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ultimo) {
    echo "<h2>Último Nombramiento Registrado:</h2>";
    echo "<pre>";
    echo "ID: " . $ultimo['id'] . "\n";
    echo "Funcionario: " . $ultimo['nombres'] . " " . $ultimo['apellidos'] . "\n";
    echo "Cédula: " . $ultimo['cedula'] . "\n";
    echo "Fecha: " . $ultimo['fecha_evento'] . "\n";
    echo "Detalles (JSON): " . $ultimo['detalles'] . "\n";
    
    $detalles = json_decode($ultimo['detalles'], true);
    echo "\nDetalles decodificados:\n";
    print_r($detalles);
    
    echo "\nArchivo PDF: " . ($ultimo['ruta_archivo_pdf'] ?? 'No tiene') . "\n";
    echo "</pre>";
} else {
    echo "<p style='color: red;'>⚠️ NO HAY NOMBRAMIENTOS EN LA BASE DE DATOS</p>";
}

// 3. Listar todos los nombramientos
$stmt = $pdo->query("
    SELECT 
        h.id,
        h.funcionario_id,
        h.fecha_evento,
        h.detalles,
        f.nombres,
        f.apellidos
    FROM historial_administrativo h
    INNER JOIN funcionarios f ON h.funcionario_id = f.id
    WHERE h.tipo_evento = 'NOMBRAMIENTO'
    ORDER BY h.created_at DESC
");
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Lista de Todos los Nombramientos ($total):</h2>";
if (count($todos) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Funcionario</th><th>Fecha</th><th>Cargo</th><th>Departamento</th></tr>";
    foreach ($todos as $nom) {
        $det = json_decode($nom['detalles'], true);
        echo "<tr>";
        echo "<td>" . $nom['id'] . "</td>";
        echo "<td>" . $nom['nombres'] . " " . $nom['apellidos'] . "</td>";
        echo "<td>" . $nom['fecha_evento'] . "</td>";
        echo "<td>" . ($det['cargo'] ?? 'N/A') . "</td>";
        echo "<td>" . ($det['departamento'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No hay registros que mostrar</p>";
}
?>
