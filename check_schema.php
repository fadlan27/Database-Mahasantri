<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("DESCRIBE mahasantri");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'tanggal_lahir') {
            echo "Column: " . $col['Field'] . "\n";
            echo "Type: " . $col['Type'] . "\n";
            echo "Null: " . $col['Null'] . "\n";
            echo "Default: " . $col['Default'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
