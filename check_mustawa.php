<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT DISTINCT mustawa FROM mahasantri");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current Mustawa Values:\n";
    print_r($rows);
    
    $stmt = $pdo->query("SELECT DISTINCT gender FROM mahasantri");
    $genders = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nCurrent Genders:\n";
    print_r($genders);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
