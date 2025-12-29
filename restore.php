<?php
// c:/laragon/www/Database Mahasantri/restore.php
require_once 'config/database.php';
require_once 'functions.php';
requireLogin();

// Increase limits for large restores
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutes

$page = 'restore.php';
$message = '';
$message_type = ''; // success, error, warning

function showMsg($msg, $type='success') {
    global $message, $message_type;
    $message = $msg;
    $message_type = $type;
}

// --- RESTORE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        showMsg("Upload error code: " . $file['error'], 'error');
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            showMsg("Hanya file .sql yang diperbolehkan.", 'error');
        } else {
            // PROSES RESTORE
            $tmpPath = $file['tmp_name'];
            $destPath = __DIR__ . '/uploads/temp_restore_' . time() . '.sql';
            
            if (move_uploaded_file($tmpPath, $destPath)) {
                $restoreResult = processRestore($destPath, $pdo);
                
                if ($restoreResult['status']) {
                    showMsg($restoreResult['msg'], 'success');
                    logActivity('Restore Database', "Restored from file: " . $file['name']);
                } else {
                    $errorDetails = $restoreResult['msg'];
                    if (!empty($errors)) {
                        $errorDetails .= " <br><hr><br><strong>Detail Shell Error:</strong><br>" . implode("<br>", $errors);
                    }
                    showMsg($errorDetails, 'error');
                }
                
                // Cleanup
                if (file_exists($destPath)) unlink($destPath);
                
            } else {
                showMsg("Gagal memindahkan file upload.", 'error');
            }
        }
    }
}

/**
 * Main Restore Processor
 */
function processRestore($filePath, $pdo) {
    global $local_user, $local_pass, $local_db, $local_host;
    
    // 1. SANITIZATION (Read, Clean, Write back)
    try {
        $content = file_get_contents($filePath);
        
        // Remove DEFINER clauses (Security/Permission Fix)
        // Pattern: DEFINER=`root`@`localhost` OR DEFINER=root@localhost
        $content = preg_replace('/DEFINER\s*=\s*[^*]*\*/', '*', $content); // Simple safety check? No, regex needs to be precise
        $content = preg_replace('/DEFINER\s*=\s*`?[^`]+`?@`?[^`]+`?/', '', $content);
        
        // Fix Collation (utf8mb4_0900_ai_ci -> utf8mb4_general_ci) if target DB doesn't support it
        // Ideally we just replace it to be safe for older MariaDB/MySQL versions
        $content = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $content);
        
        file_put_contents($filePath, $content);
        
    } catch (Exception $e) {
        return ['status' => false, 'msg' => "Sanitization Error: " . $e->getMessage()];
    }
    
    // 2. EXECUTION STRATEGY
    $errors = [];
    $methodUsed = 'Unknown';
    
    // A. TRY SHELL EXEC (Fastest)
    // Check if mysql exists
    $mysqlPath = '';
    
    // Auto-detect Laragon MySQL
    $laragonMysqlDir = 'C:/laragon/bin/mysql/';
    // Prioritize the known version if it exists
    if (file_exists('C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe')) {
        $mysqlPath = 'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe';
    } elseif (is_dir($laragonMysqlDir)) {
        $versions = scandir($laragonMysqlDir);
        foreach ($versions as $ver) {
            if ($ver !== '.' && $ver !== '..' && is_dir($laragonMysqlDir . $ver)) {
                $possiblePath = $laragonMysqlDir . $ver . '/bin/mysql.exe';
                if (file_exists($possiblePath)) {
                    $mysqlPath = $possiblePath;
                    break;
                }
            }
        }
    }
    
    // Fallback System PATH
    if (!$mysqlPath) $mysqlPath = 'mysql';

    // Construct Command
    // mysql -u root -pPASS dbname < file.sql
    $cmdParams = "-u\"$local_user\"";
    if (!empty($local_pass)) {
        $cmdParams .= " -p\"$local_pass\"";
    }
    
    // Only try shell if we are essentially local or have a path
    // On shared hosting, shell_exec might be disabled or mysql not in path
    $shellExecAvailable = function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')));
    
    if ($shellExecAvailable && (DB_MODE === 'LOCAL' || DB_MODE === 'LOCAL_CREATED')) {
        // Local strategy
        // Windows cmd.exe supports < redirection nicely for mysql
        $cmd = "\"$mysqlPath\" $cmdParams \"$local_db\" < \"$filePath\" 2>&1";
        
        $output = shell_exec($cmd);
        
        // Simple check if output contains error-like keywords (MySQL CLI usually silent on success)
        if ($output && (stripos($output, 'error') !== false || stripos($output, 'denied') !== false || stripos($output, 'unexpected') !== false)) {
            // Shell failed, fallback to PHP
            $errors[] = "Shell Method Failed. Output: " . substr($output, 0, 200);
            // Check if it's just a warning
            if (stripos($output, 'Using a password') !== false && stripos($output, 'error') === false) {
                 // It might have actually worked? No, we check outcome. But let's log it.
            }
        } else {
            return ['status' => true, 'msg' => "Restore Berhasil (Metode: Shell/Native)"];
        }
    }
    
    // B. PHP LINE-BY-LINE (Fallback / Shared Hosting)
    // This is slower but works everywhere
    try {
        $pdo->beginTransaction();
        
        // Disable foreign keys temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Split by semicolon, but respect quotes...
        // Doing a proper SQL split in PHP is hard. 
        // Strategy: Read file, remove comments, split by ";\n" or ";\r\n" specifically?
        // Better: Use a buffer.
        
        $sql = '';
        $lines = file($filePath);
        $statementCount = 0;
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip comments
            if (substr($trimmed, 0, 2) == '--' || $trimmed == '') {
                continue;
            }
            if (substr($trimmed, 0, 2) == '/*') {
                 continue; // Multi-line comment naive check
            }
            
            $sql .= $line;
            
            // If line ends with semicolon, execute it
            if (substr(rtrim($trimmed), -1) == ';') {
                // Execute
                if (trim($sql) != '') {
                    $pdo->exec($sql);
                    $statementCount++;
                }
                $sql = '';
            }
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        $pdo->commit();
        
        return ['status' => true, 'msg' => "Restore Berhasil (Metode: PHP PDO, $statementCount queries)"];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $failSql = substr($sql, 0, 100);
        return ['status' => false, 'msg' => "PHP Importer Gagal: " . $e->getMessage() . " | Query: " . $failSql . "..."];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body class="text-slate-800 dark:text-slate-200 font-sans antialiased h-screen overflow-hidden flex">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-4 md:p-6 flex flex-col gap-6">
            
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shrink-0 px-2">
                <div>
                    <h1 class="text-3xl font-bold font-heading bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent flex items-center gap-3">
                        <i class="fa-solid fa-cloud-arrow-up text-orange-500"></i> Restore Database
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Pulihkan data dari file backup (.sql)</p>
                </div>
                
                <div class="flex gap-3">
                     <a href="db_manager.php" class="px-5 py-2.5 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-sm font-bold hover:bg-slate-300 dark:hover:bg-slate-600 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="max-w-3xl mx-auto w-full space-y-6">
                
                <!-- PRE-FLIGHT CHECKS / INFO -->
                <div class="p-6 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <h3 class="font-bold text-indigo-700 dark:text-indigo-300 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info"></i> Informasi Penting
                    </h3>
                    <ul class="list-disc list-inside text-sm text-slate-600 dark:text-slate-400 space-y-1">
                        <li>Fitur ini mendukung file backup MySQL <strong>(.sql)</strong>.</li>
                        <li>Sistem akan otomatis membersihkan klausul <code>DEFINER</code> yang sering menyebabkan error permission.</li>
                        <li>Sistem otomatis menyesuaikan enkoding text (Collation) agar kompatibel dengan semua versi.</li>
                        <li>Direkomendasikan melakukan <strong>Backup</strong> terlebih dahulu sebelum melakukan Restore.</li>
                    </ul>
                </div>

                <!-- ALERT MESSAGE -->
                <?php if ($message): ?>
                <div class="p-4 rounded-xl text-sm font-bold flex items-center animate-pulse <?php echo $message_type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 <?php echo $message_type === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-check' : 'fa-triangle-exclamation'; ?>"></i>
                    </div>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- UPLOAD FORM -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden border border-slate-100 dark:border-slate-700">
                    <div class="p-8">
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Pilih File Backup (.sql)</label>
                                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-8 text-center hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer group" id="dropArea">
                                    <input type="file" name="sql_file" id="sqlFile" accept=".sql" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="handleFileSelect(this)">
                                    
                                    <!-- State 1: Placeholder -->
                                    <div id="uploadPlaceholder" class="flex flex-col items-center justify-center gap-3 transition-all duration-300">
                                        <div class="w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <i class="fa-solid fa-file-invoice text-3xl"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-slate-600 dark:text-slate-400 font-medium">Klik atau drag file .sql ke sini</p>
                                            <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-xs text-slate-500 font-mono">Max Upload: <?php echo ini_get('upload_max_filesize'); ?></span>
                                        </div>
                                    </div>

                                    <!-- State 2: File Selected -->
                                    <div id="fileInfo" class="hidden flex-col items-center justify-center gap-3 animate-fade-in-up">
                                        <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center ring-4 ring-emerald-50 dark:ring-emerald-900/20">
                                            <i class="fa-solid fa-check text-2xl"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-bold text-slate-800 dark:text-slate-200 text-lg" id="fileName">filename.sql</p>
                                            <p class="text-sm text-slate-500 dark:text-slate-400 font-mono" id="fileSize">0 KB</p>
                                        </div>
                                        <span class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                                            <i class="fa-solid fa-circle-check"></i> Siap di-Restore
                                        </span>
                                    </div>
                                </div>
                                
                                <script>
                                    function handleFileSelect(input) {
                                        const placeholder = document.getElementById('uploadPlaceholder');
                                        const fileInfo = document.getElementById('fileInfo');
                                        const fileName = document.getElementById('fileName');
                                        const fileSize = document.getElementById('fileSize');
                                        const dropArea = document.getElementById('dropArea');

                                        if (input.files && input.files[0]) {
                                            const file = input.files[0];
                                            
                                            // Validate extension strictly here for UI feedback
                                            if (!file.name.toLowerCase().endsWith('.sql')) {
                                                alert('Harap pilih file berakhiran .sql');
                                                input.value = ''; // Reset
                                                return;
                                            }

                                            // Update Info
                                            fileName.textContent = file.name;
                                            
                                            // Format Size
                                            let size = file.size;
                                            let unit = 'B';
                                            if (size > 1024 * 1024) {
                                                size = (size / (1024 * 1024)).toFixed(2);
                                                unit = 'MB';
                                            } else if (size > 1024) {
                                                size = (size / 1024).toFixed(2);
                                                unit = 'KB';
                                            }
                                            fileSize.textContent = size + ' ' + unit;

                                            // Switch View
                                            placeholder.classList.add('hidden');
                                            fileInfo.classList.remove('hidden');
                                            fileInfo.classList.add('flex');
                                            
                                            // Style Border
                                            dropArea.classList.remove('border-slate-300', 'border-dashed');
                                            dropArea.classList.add('border-emerald-400', 'bg-emerald-50/30');
                                            
                                        } else {
                                            // Reset
                                            placeholder.classList.remove('hidden');
                                            fileInfo.classList.add('hidden');
                                            fileInfo.classList.remove('flex');
                                            
                                            dropArea.classList.add('border-slate-300', 'border-dashed');
                                            dropArea.classList.remove('border-emerald-400', 'bg-emerald-50/30');
                                        }
                                    }
                                </script>
                            </div>
                            
                            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700">
                                <button type="submit" onclick="return confirm('PERINGATAN: Tindakan ini akan menimpa data yang ada.\nPastikan Anda sudah backup data saat ini.\n\nLanjutkan Restore?')" class="px-8 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-bold rounded-xl shadow-lg shadow-orange-500/20 transform hover:-translate-y-0.5 transition-all flex items-center gap-2">
                                    <i class="fa-solid fa-hammer"></i> Eksekusi Restore
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
