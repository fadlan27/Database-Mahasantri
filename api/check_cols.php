<?php
require_once '../config/database.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM pelanggaran");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "COLUMNS: " . implode(", ", $cols);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
