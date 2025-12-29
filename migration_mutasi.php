<?php
require_once 'config/database.php';

try {
    echo "Starting Migration...<br>";

    // 1. Add 'Mutasi' to ENUM
    // Note: We keep 'Cuti' for now to ensure UPDATE works safely before removal, or just leave it.
    // We also match the existing list exactly: 'Aktif','Cuti','Lulus','Dikeluarkan','Drop Out'
    $sqlAlter = "ALTER TABLE mahasantri MODIFY COLUMN status ENUM('Aktif','Cuti','Lulus','Dikeluarkan','Drop Out', 'Mutasi')";
    $pdo->exec($sqlAlter);
    echo "1. ENUM updated to include 'Mutasi'.<br>";

    // 2. Update Data
    $sqlUpdate = "UPDATE mahasantri SET status = 'Mutasi' WHERE status = 'Cuti'";
    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "2. Updated $count records from 'Cuti' to 'Mutasi'.<br>";

    echo "Migration Completed Successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
