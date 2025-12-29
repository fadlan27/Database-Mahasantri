<?php
require_once 'config/database.php';

try {
    // Add column if not exists
    $pdo->exec("ALTER TABLE pelanggaran ADD COLUMN tingkat_sanksi ENUM('Normal', 'SP1', 'SP2', 'SP3', 'DO') DEFAULT 'Normal' AFTER jenis");
    echo "Column 'tingkat_sanksi' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
