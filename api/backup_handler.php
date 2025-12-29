<?php
ob_start();
error_reporting(0); 
ini_set('display_errors', 0);

require_once '../functions.php';
requireLogin();

// Increase limits
set_time_limit(600); 
ini_set('memory_limit', '512M');

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'full';

// ---------------------------------------------------------
// SHARED: GENERATE SQL CONTENT
// ---------------------------------------------------------
function generateSqlBackup($pdo) {
    $sql = "-- DATABASE BACKUP: " . date("Y-m-d H:i:s") . "\n";
    $sql .= "-- HOST: " . $_SERVER['HTTP_HOST'] . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    try {
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $table = $row[0];
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $createStmt = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sql .= $createStmt[1] . ";\n\n";
            
            $dataStmt = $pdo->query("SELECT * FROM $table");
            while ($dRow = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                $sql .= "INSERT INTO `$table` VALUES(";
                $vals = [];
                foreach ($dRow as $val) {
                    $vals[] = ($val === null) ? "NULL" : $pdo->quote($val);
                }
                $sql .= implode(", ", $vals) . ");\n";
            }
            $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;";
        return $sql;

    } catch (Exception $e) {
        return "-- ERROR GENERATING BACKUP: " . $e->getMessage();
    }
}

// ---------------------------------------------------------
// MODE 1: SQL ONLY
// ---------------------------------------------------------
if ($mode === 'sql') {
    $filename = 'backup_mahasantri_sql_' . date('Y-m-d_H-i-s') . '.sql';
    $sqlContent = generateSqlBackup($pdo);

    ob_clean();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sqlContent));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $sqlContent;
    exit;
}

// ---------------------------------------------------------
// MODE 2: FULL (ZIP)
// ---------------------------------------------------------
else {
    if (!extension_loaded('zip')) {
        ob_clean();
        die("Error: PHP Zip extension is not enabled.");
    }

    $filename = 'backup_mahasantri_full_' . date('Y-m-d_H-i-s') . '.zip';
    $tempZip  = sys_get_temp_dir() . '/' . uniqid('backup_', true) . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        ob_clean();
        die("Error: Cannot create temporary zip file.");
    }

    // Add SQL
    $zip->addFromString('database.sql', generateSqlBackup($pdo));

    // Add Uploads
    $uploadDir = realpath('../uploads');
    if ($uploadDir && is_dir($uploadDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'uploads/' . substr($filePath, strlen($uploadDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    } else {
        $zip->addFromString('uploads/readme.txt', 'Uploads directory empty or not found.');
    }

    $zip->close();

    if (file_exists($tempZip)) {
        ob_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempZip));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($tempZip);
        unlink($tempZip);
        exit;
    } else {
        ob_clean();
        die("Error: Failed to create zip file.");
    }
}
?>
