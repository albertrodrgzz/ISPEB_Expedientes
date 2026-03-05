<?php
/**
 * Script de actualización: Departamentos y Cargos
 * Acceder una sola vez: http://localhost/APP3/actualizar_bd_cargos.php
 * ELIMINAR después de ejecutar.
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance()->getConnection();
    $resultados = [];

    // 1. Agregar "Dirección de Telemática"
    $stmt = $db->prepare("INSERT INTO departamentos (nombre, descripcion, estado) VALUES (?, ?, 'activo') ON DUPLICATE KEY UPDATE estado='activo'");
    $stmt->execute(['Dirección de Telemática', 'Dirección General de Telemática']);
    $resultados[] = "✅ Departamento 'Dirección de Telemática' agregado/actualizado";

    // 2. Reasignar funcionarios con cargo "Asistente" → Técnico (id=6)
    $stmt = $db->prepare("UPDATE funcionarios SET cargo_id=6 WHERE cargo_id=(SELECT id FROM cargos WHERE nombre_cargo='Asistente' LIMIT 1)");
    $stmt->execute();
    $afectados = $stmt->rowCount();
    $resultados[] = "✅ $afectados funcionario(s) con cargo 'Asistente' reasignados a Técnico";

    // 3. Reasignar funcionarios con cargo "Pasante de Pruebas" → Técnico
    $stmt = $db->prepare("UPDATE funcionarios SET cargo_id=6 WHERE cargo_id=(SELECT id FROM cargos WHERE nombre_cargo='Pasante de Pruebas' LIMIT 1)");
    $stmt->execute();
    $afectados = $stmt->rowCount();
    $resultados[] = "✅ $afectados funcionario(s) con cargo 'Pasante de Pruebas' reasignados a Técnico";

    // 4. Eliminar cargos
    foreach (['Asistente', 'Pasante de Pruebas'] as $cargo) {
        $stmt = $db->prepare("DELETE FROM cargos WHERE nombre_cargo=?");
        $stmt->execute([$cargo]);
        $resultados[] = $stmt->rowCount() > 0
            ? "✅ Cargo '$cargo' eliminado"
            : "⚠️ Cargo '$cargo' no encontrado (ya eliminado)";
    }

    // 5. Agregar cargo "Analista" con nivel 3
    $stmt = $db->prepare("INSERT INTO cargos (nombre_cargo, nivel_acceso, descripcion) VALUES ('Analista', 3, 'Analista de sistemas y procesos') ON DUPLICATE KEY UPDATE nivel_acceso=3");
    $stmt->execute();
    $resultados[] = "✅ Cargo 'Analista' (nivel 3) agregado/verificado";

    // 6. Corregir niveles de acceso
    $niveles = [
        ['Jefe de Dirección', 1],
        ['Jefe de Departamento', 2],
        ['Secretaria', 2],
    ];
    foreach ($niveles as [$nombre, $nivel]) {
        $stmt = $db->prepare("UPDATE cargos SET nivel_acceso=? WHERE nombre_cargo=?");
        $stmt->execute([$nivel, $nombre]);
        $resultados[] = "✅ Cargo '$nombre' → nivel $nivel verificado";
    }

    // 7. Mostrar estado final
    $cargos   = $db->query("SELECT id, nombre_cargo, nivel_acceso FROM cargos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $deptos   = $db->query("SELECT id, nombre, estado FROM departamentos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
    <title>Actualización BD - SIGED</title>
    <style>body{font-family:Inter,sans-serif;max-width:800px;margin:40px auto;background:#f1f5f9;padding:20px}
    .box{background:#fff;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
    h1{color:#0F4C81}h2{color:#1e293b}
    .ok{color:#10B981}.warn{color:#F59E0B}.err{color:#EF4444}
    table{width:100%;border-collapse:collapse}th,td{padding:8px 12px;text-align:left;border:1px solid #e2e8f0}
    th{background:#f8fafc;font-weight:600}
    .badge-1{background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:4px}
    .badge-2{background:#DCFCE7;color:#166534;padding:2px 8px;border-radius:4px}
    .badge-3{background:#DBEAFE;color:#1e40af;padding:2px 8px;border-radius:4px}
    .btn{display:inline-block;background:#0F4C81;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;margin-top:10px}
    </style></head><body>';
    echo '<div class="box"><h1>✅ Actualización de BD completada</h1>';
    foreach ($resultados as $r) echo "<p>$r</p>";
    echo '</div>';

    echo '<div class="box"><h2>📋 Cargos actuales</h2><table><tr><th>ID</th><th>Cargo</th><th>Nivel</th></tr>';
    foreach ($cargos as $c) {
        $badge = "badge-{$c['nivel_acceso']}";
        echo "<tr><td>{$c['id']}</td><td>{$c['nombre_cargo']}</td><td><span class='$badge'>Nivel {$c['nivel_acceso']}</span></td></tr>";
    }
    echo '</table></div>';

    echo '<div class="box"><h2>🏢 Departamentos actuales</h2><table><tr><th>ID</th><th>Nombre</th><th>Estado</th></tr>';
    foreach ($deptos as $d) {
        echo "<tr><td>{$d['id']}</td><td>{$d['nombre']}</td><td>{$d['estado']}</td></tr>";
    }
    echo '</table></div>';

    echo '<div class="box"><p>⚠️ <strong>ELIMINA este archivo después de ejecutarlo por seguridad.</strong></p>
    <a class="btn" href="' . APP_URL . '/vistas/admin/organizacion.php">Ir a Organización →</a></div>';
    echo '</body></html>';

} catch (Exception $e) {
    echo '<div style="color:red;font-family:monospace;padding:20px"><h2>❌ Error</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>';
}
