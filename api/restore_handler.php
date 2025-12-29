<?php
// api/restore_handler.php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(600);

require_once '../functions.php';
requireLogin();

ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendJson(['status' => 'error', 'message' => 'Method Not Allowed']);
}

if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    sendJson(['status' => 'error', 'message' => 'Upload Gagal or File Empty.']);
}

$file = $_FILES['backup_file']['tmp_name'];
$ext  = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));

try {
    $msg = "";
    
    if ($ext === 'sql') {
        // --- STANDARD SQL RESTORE ---
        // Pass File Path, not content, for Memory Efficiency & Shell Support
        $result = restoreDatabase($pdo, $file);
        $msg = "SQL Restore: " . $result;
        
    } elseif ($ext === 'zip') {
        // --- ZIP FULL RESTORE ---
        if (!extension_loaded('zip')) {
            throw new Exception("PHP Zip Extension missing.");
        }

        $zip = new ZipArchive;
        if ($zip->open($file) === TRUE) {
            $extractPath = sys_get_temp_dir() . '/restore_' . uniqid();
            if (!mkdir($extractPath)) {
                 throw new Exception("Gagal membuat temp folder.");
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // 1. Restore Database
            $sqlFile = $extractPath . '/database.sql';
            if (file_exists($sqlFile)) {
                // Pass Path
                $dbResult = restoreDatabase($pdo, $sqlFile); // Changed from content to path
                $msg .= "Database: $dbResult. ";
            } else {
                $msg .= "Database: Skipped (No SQL file). ";
            }

            // 2. Restore Uploads
            $uploadSrc = $extractPath . '/uploads';
            $uploadDst = realpath('../uploads');
            
            $fileCount = 0;
            if (is_dir($uploadSrc) && $uploadDst) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($uploadSrc, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $obj) {
                    $relativePath = substr($name, strlen($uploadSrc) + 1);
                    $targetPath   = $uploadDst . '/' . $relativePath;
                    
                    // Create dir if not exists
                    $targetDir = dirname($targetPath);
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                    
                    // Copy file
                    if (copy($name, $targetPath)) {
                        $fileCount++;
                    }
                }
                $msg .= "Foto: $fileCount file dipulihkan.";
            } else {
                $msg .= "Foto: Folder uploads tidak ditemukan di backup.";
            }

            // Cleanup
            deleteDir($extractPath);

        } else {
            throw new Exception("Gagal membuka file ZIP.");
        }
    } else {
        throw new Exception("Format harus .sql atau .zip");
    }

    sendJson(['status' => 'success', 'message' => $msg]);

} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()]);
}

// ---------------- HELPER FUNCTIONS ----------------

// --- 2. ROBUST RESTORE FUNCTION (Hybrid Shell/PHP) ---
function restoreDatabase($pdo, $filePath) {
    // Check if file exists
    if (!file_exists($filePath)) {
        return "File tidak ditemukan: $filePath";
    }

    // A. SANITIZATION (Read only, don't overwrite if it's too big? No, we likely need to fix Definer)
    // For very large files, file_get_contents might fail.
    // Ideally we stream-edit or skip if too big. 
    // For now, let's keep sanitization but wrap in try-catch for memory issues.
    try {
        $fileSize = filesize($filePath);
        if ($fileSize < 100 * 1024 * 1024) { // Only sanitize if < 100MB to avoid RAM choke
            $content = file_get_contents($filePath);
            $content = preg_replace('/DEFINER\s*=\s*[^*]*\*/', '*', $content);
            $content = preg_replace('/DEFINER\s*=\s*`?[^`]+`?@`?[^`]+`?/', '', $content);
            $content = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $content);
            
            // FIX FOREIGN KEY TYPES (match mahasantri.id which is BIGINT UNSIGNED)
            // Replace `mahasantri_id` int(11) -> bigint unsigned
            $content = preg_replace('/`mahasantri_id`\s+int(?:\(\d+\))?/', '`mahasantri_id` bigint unsigned', $content);
            // Also ensure `id` in mahasantri is created as bigint unsigned if it's being recreated
            $content = preg_replace('/`id`\s+int(?:\(\d+\))?\s+NOT NULL AUTO_INCREMENT/', '`id` bigint unsigned NOT NULL AUTO_INCREMENT', $content);

            file_put_contents($filePath, $content);
        }
    } catch (Exception $e) {
        // Ignore sanitization errors (like memory limit) and proceed
    }
    
    // Global Configs for Shell
    global $local_user, $local_pass, $local_db, $local_host;
    
    // B. TRY SHELL EXEC (Fastest)
    $mysqlPath = '';
    
    // Auto-detect Laragon MySQL
    $laragonMysqlDir = 'C:/laragon/bin/mysql/';
    if (file_exists('C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe')) {
        $mysqlPath = 'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe';
    } elseif (is_dir($laragonMysqlDir)) {
        $versions = scandir($laragonMysqlDir);
        foreach ($versions as $ver) {
            if ($ver !== '.' && $ver !== '..' && is_dir($laragonMysqlDir . $ver)) {
                $possible = $laragonMysqlDir . $ver . '/bin/mysql.exe';
                if (file_exists($possible)) { $mysqlPath = $possible; break; }
            }
        }
    }
    if (!$mysqlPath) $mysqlPath = 'mysql';

    $cmdParams = "-u\"$local_user\"";
    if (!empty($local_pass)) $cmdParams .= " -p\"$local_pass\"";
    
    $shellAvailable = function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')));
    
    if ($shellAvailable && (DB_MODE === 'LOCAL' || DB_MODE === 'LOCAL_CREATED')) {
        $cmd = "\"$mysqlPath\" $cmdParams \"$local_db\" < \"$filePath\" 2>&1";
        $output = shell_exec($cmd);
        
        // Check for specific error keywords
        $isError = false;
        if ($output) {
             $lowerOut = strtolower($output);
             if (strpos($lowerOut, 'error') !== false || strpos($lowerOut, 'denied') !== false) {
                 // Ignore standard warning
                 if (strpos($lowerOut, 'using a password') === false) $isError = true;
             }
        }
        
        if (!$isError) {
            return "Restore Berhasil (Metode: Shell/Native)";
        }
    }
    
    // C. PHP FALLBACK (Line-by-Line)
    try {
        $pdo->beginTransaction();
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Stream file instead of loading all
        $handle = fopen($filePath, "r");
        if ($handle) {
            $sql = '';
            $count = 0;
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if (substr($trimmed, 0, 2) == '--' || $trimmed == '' || substr($trimmed, 0, 2) == '/*') continue;
                
                $sql .= $line;
                if (substr(rtrim($trimmed), -1) == ';') {
                    if (trim($sql) != '') {
                       $pdo->exec($sql);
                       $count++;
                    }
                    $sql = '';
                }
            }
            fclose($handle);
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            
            return "Restore Berhasil (Metode: PHP PDO, $count queries)";
        } else {
             throw new Exception("Gagal membaca file.");
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return "PHP Error: " . $e->getMessage() . " (Query: " . substr($sql ?? '', 0, 50) . "...)";
    }
}

function sendJson($data) {
    ob_clean();
    echo json_encode($data);
    exit;
}

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) deleteDir($file);
        else unlink($file);
    }
    rmdir($dirPath);
}
?>
