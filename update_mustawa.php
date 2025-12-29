<?php
require_once 'config/database.php';

try {
    // Check if column is large enough (VARCHAR 50 is likely, but let's assume it fits or ALTER it first to be safe)
    // Actually, "Mustawa Awwal (Ikhwan)" is ~25 chars. 
    // Let's just run update.
    
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
