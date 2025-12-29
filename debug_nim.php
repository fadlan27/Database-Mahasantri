<?php
require_once 'functions.php';

try {
    $stmt = $pdo->query("DESCRIBE mahasantri");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in 'mahasantri' table:\n";
    foreach ($columns as $col) {
        if ($col['Field'] == 'nim') {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
