<?php
require_once '../config/database.php';

try {
    // 1. Add 'nim' column if it doesn't exist
    // Use a safe approach: Check existence first or suppress error
    // SHOW COLUMNS logic already showed it doesn't exist.
    
    echo "Adding 'nim' column to 'pelanggaran' table...\n";
    $pdo->exec("ALTER TABLE pelanggaran ADD COLUMN nim VARCHAR(50) AFTER mahasantri_id");
    
    // 2. Index ID and NIM for performance
    echo "Adding index to 'nim'...\n";
    $pdo->exec("ALTER TABLE pelanggaran ADD INDEX idx_violation_nim (nim)");
    
    // 3. Migrate Data: Populate nim from mahasantri table based on existing mahasantri_id
    echo "Migrating existing data (Populating NIM)...\n";
    $stmt = $pdo->prepare("
        UPDATE pelanggaran p
        JOIN mahasantri m ON p.mahasantri_id = m.id
        SET p.nim = m.nim
        WHERE p.nim IS NULL OR p.nim = ''
    ");
    $stmt->execute();
    
    echo "Migration completed successfully. Rows updated: " . $stmt->rowCount();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
