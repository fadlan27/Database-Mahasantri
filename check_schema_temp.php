<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("DESCRIBE pelanggaran");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in pelanggaran: " . implode(", ", $columns) . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
