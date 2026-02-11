<?php
/**
 * Script para agregar el campo tiene_amonestaciones_graves
 * si no existe en la tabla funcionarios
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDB();
    
    // Verificar si el campo ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM funcionarios LIKE 'tiene_amonestaciones_graves'");
    $existe = $stmt->fetch();
    
    if (!$existe) {
        echo "⚙️  Agregando campo tiene_amonestaciones_graves...\n";
        
        $pdo->exec("
            ALTER TABLE funcionarios 
            ADD COLUMN tiene_amonestaciones_graves TINYINT(1) DEFAULT 0 
            COMMENT 'Flag: 1 si tiene amonestaciones muy graves'
        ");
        
        echo "✅ Campo agregado exitosamente\n";
    } else {
        echo "✅ El campo tiene_amonestaciones_graves ya existe\n";
    }
    
    // Mostrar información del campo
    $stmt = $pdo->query("SHOW COLUMNS FROM funcionarios LIKE 'tiene_amonestaciones_graves'");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n📋 Información del campo:\n";
    echo "   Tipo: " . $info['Type'] . "\n";
    echo "   Null: " . $info['Null'] . "\n";
    echo "   Default: " . ($info['Default'] ?? '(ninguno)') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
