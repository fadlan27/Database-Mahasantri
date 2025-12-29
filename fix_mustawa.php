<?php
require_once 'config/database.php';

try {
    // 1. ALTER TABLE to VARCHAR(100)
    echo "Altering column 'mustawa' to VARCHAR(100)...\n";
    $pdo->exec("ALTER TABLE mahasantri MODIFY COLUMN mustawa VARCHAR(100)");
    echo "Column altered successfully.\n";
    
    // 2. Run UPDATE
    echo "Updating data...\n";
    $sql = "UPDATE mahasantri 
            SET mustawa = CONCAT('Mustawa ', mustawa, ' (', gender, ')') 
            WHERE mustawa IS NOT NULL 
            AND mustawa != '' 
            AND mustawa NOT LIKE 'Mustawa%'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "Updated " . $stmt->rowCount() . " rows to new Mustawa format.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
