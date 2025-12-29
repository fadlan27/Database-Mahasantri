<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/update_schema_v3.php
require_once 'config/database.php';

echo "<h1>Updating Database Schema V3...</h1>";

try {
    // Add 'asal_ppui' if not exists
    try {
        $pdo->exec("ALTER TABLE mahasantri ADD COLUMN asal_ppui VARCHAR(100) AFTER asal");
        echo "Column 'asal_ppui' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'asal_ppui' likely already exists.<br>";
    }

    echo "<h3>Schema Update V3 Finished!</h3>";
    echo "<a href='master_data.php'>Back to Master Data</a>";

} catch (PDOException $e) {
    die("Update Failed: " . $e->getMessage());
}
?>
