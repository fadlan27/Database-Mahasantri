<?php
// export_manager.php (LOCAL ONLY)
require_once 'config/database.php';
require_once 'functions.php';

// Force Download Header
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="full_backup_local.sql"');

// Initialize
$tables = [];
$sql = "-- FULL DATABASE BACKUP LOCAL \n";
$sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get All Tables
$result = $pdo->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// Process Each Table
foreach ($tables as $table) {
    if ($table == 'system_logs') continue; // Optional: Skip logs to save size

    // 1. Drop & Create
    $sql .= "\n\n-- Table structure for `$table`\n";
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
    
    $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $sql .= $row2[1] . ";\n\n";

    // 2. Insert Data
    $result3 = $pdo->query("SELECT * FROM `$table`");
    $numFields = $result3->columnCount();

    if ($result3->rowCount() > 0) {
        $sql .= "-- Dumping data for `$table`\n";
        $sql .= "INSERT INTO `$table` VALUES \n";
        
        $rows = $result3->fetchAll(PDO::FETCH_NUM);
        $rowCount = count($rows);
        $counter = 0;

        foreach ($rows as $row) {
            $sql .= "(";
            for ($j = 0; $j < $numFields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                if (isset($row[$j])) {
                    $sql .= '"' . $row[$j] . '"';
                } else {
                    $sql .= '""';
                }
                if ($j < ($numFields - 1)) {
                    $sql .= ',';
                }
            }
            $counter++;
            $sql .= ")" . ($counter < $rowCount ? ",\n" : ";\n");
        }
    }
}

echo $sql;
exit;
?>
