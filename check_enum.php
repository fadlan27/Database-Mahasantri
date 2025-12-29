<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("DESCRIBE mahasantri status");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current Type: " . $row['Type'] . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
