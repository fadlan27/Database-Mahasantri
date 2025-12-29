<?php
require_once 'functions.php';

try {
    echo "Updating database schema...\n";

    // Check existing columns
    $stmt = $pdo->query("DESCRIBE pelanggaran");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 1. Add tingkat_sanksi if not exists
    if (!in_array('tingkat_sanksi', $columns)) {
        echo "Adding column 'tingkat_sanksi'...\n";
        // Default to 'Normal' as per usage in violations.php
        $pdo->exec("ALTER TABLE pelanggaran ADD COLUMN tingkat_sanksi VARCHAR(50) DEFAULT 'Normal' AFTER sanksi");
        echo "Column 'tingkat_sanksi' added.\n";
    } else {
        echo "Column 'tingkat_sanksi' already exists.\n";
    }

    // 2. Add nim if not exists
    if (!in_array('nim', $columns)) {
         echo "Adding column 'nim'...\n";
        $pdo->exec("ALTER TABLE pelanggaran ADD COLUMN nim VARCHAR(20) AFTER mahasantri_id");
        echo "Column 'nim' added.\n";
        
        // Populate nim
        echo "Populating 'nim' from mahasantri table...\n";
        $sql = "UPDATE pelanggaran p 
                JOIN mahasantri m ON p.mahasantri_id = m.id 
                SET p.nim = m.nim 
                WHERE p.nim IS NULL OR p.nim = ''";
        $affected = $pdo->exec($sql);
        echo "Column 'nim' populated. Rows updated: $affected\n";
    } else {
        echo "Column 'nim' already exists.\n";
    }
    
    // 3. Add index on nim if not exists (checking by catching error is simplest here for quick fix)
    try {
        $pdo->exec("CREATE INDEX idx_pelanggaran_nim ON pelanggaran(nim)");
        echo "Index on 'nim' created.\n";
    } catch (PDOException $e) {
        // SQLSTATE[42000]: Syntax error or access violation: 1061 Duplicate key name
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
             echo "Index on 'nim' already exists.\n";
        } else {
             // Other error
             echo "Info: " . $e->getMessage() . "\n";
        }
    }

    echo "Database update completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
