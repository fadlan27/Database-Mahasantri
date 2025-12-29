<?php
require_once 'config/database.php';

echo "Testing Insert...\n";

try {
    $nim = 'TEST_' . time();
    $sql = "INSERT INTO mahasantri (nim, nama, gender, angkatan, mustawa, status, tempat_lahir, tanggal_lahir) 
            VALUES (:nim, 'Test User', 'Ikhwan', '2024', 'Awwal', 'Aktif', 'Jakarta', '2000-01-01')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nim' => $nim]);
    
    echo "Insert Success! ID: " . $pdo->lastInsertId() . "\n";
    
    // Cleanup
    $pdo->exec("DELETE FROM mahasantri WHERE nim = '$nim'");
    echo "Cleanup Success.\n";
    
} catch (PDOException $e) {
    echo "Insert Failed: " . $e->getMessage() . "\n";
}
?>
