<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT id, nama, asal, asal_ppui FROM mahasantri LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample Data:\n";
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
