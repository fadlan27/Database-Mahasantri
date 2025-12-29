<?php
require_once 'config/database.php';
try {
    // Get columns
    $stmt = $pdo->query("DESCRIBE mahasantri");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n\n";

    // Get sample data for 'asal'
    $stmt = $pdo->query("SELECT id, nama, asal FROM mahasantri LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample 'asal' Data:\n";
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
