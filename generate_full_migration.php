<?php
// generate_full_migration.php (RUN ON LOCAL)
// Generates 'install_db_hosting.php' for full deployment.

require_once 'config/database.php';

// --- HOSTING CREDENTIALS (FROM USER) ---
$target_host = 'sql100.infinityfree.com';
$target_db   = 'if0_40595177_jamiah_abat_db';
$target_user = 'if0_40595177';
$target_pass = 'Bungganteng21';

// --- LOCAL CREDENTIALS (RE-VERIFY) ---
$local_host  = 'localhost';
$local_db    = 'jamiah_abat_db';
$local_user  = 'root';
$local_pass  = '';

echo "<h1>🚀 Generating Deployment Script...</h1>";

try {
    $pdo_local = new PDO("mysql:host=$local_host;dbname=$local_db;charset=utf8mb4", $local_user, $local_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Local Connection Failed: " . $e->getMessage());
}

// 1. HEADER
$content = <<<PHP
<?php
// install_db_hosting.php
// GENERATED INSTALLER FOR: $target_db
ini_set('display_errors', 1);
ini_set('max_execution_time', 600);
ini_set('memory_limit', '256M');

echo "<html><head><title>Database Installer</title></head><body style='background:#f3f4f6; padding:20px; font-family:sans-serif;'>";
echo "<div style='max-width:800px; margin:0 auto; background:white; padding:30px; border-radius:10px; shadow:0 4px 6px rgba(0,0,0,0.1);'>";
echo "<h1 style='color:#2563eb; border-bottom:2px solid #e5e7eb; padding-bottom:10px;'>📦 Install Database Hosting</h1>";

\$host = '$target_host';
\$db   = '$target_db';
\$user = '$target_user';
\$pass = '$target_pass';

try {
    \$pdo = new PDO("mysql:host=\$host;dbname=\$db;charset=utf8mb4", \$user, \$pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<p style='color:green; font-weight:bold;'>✅ Connected to Hosting Database!</p>";
} catch (PDOException \$e) {
    die("<h3 style='color:red;'>❌ Connection Failed: " . \$e->getMessage() . "</h3></div></body></html>");
}

echo "<div style='background:#1e293b; color:#cbd5e1; padding:15px; border-radius:8px; height:400px; overflow-y:auto; font-family:monospace; font-size:12px;'>";

\$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

\$start = microtime(true);
\$errors = 0;
PHP;

// 2. DUMP CONTENT
$tables = [];
$r = $pdo_local->query("SHOW TABLES");
while ($row = $r->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];

$dumpOps = "";
foreach ($tables as $table) {
    // Schema
    $row2 = $pdo_local->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $createSQL = $row2[1];
    
    // --- FIX: Compatibility for Older MySQL (Hosting) ---
    // Replace 'utf8mb4_0900_ai_ci' with 'utf8mb4_general_ci'
    $createSQL = str_replace("utf8mb4_0900_ai_ci", "utf8mb4_general_ci", $createSQL);
    $createSQL = str_replace("utf8mb4_unicode_520_ci", "utf8mb4_general_ci", $createSQL); // Extra safety
    
    // Add logic to PHP output
    $dumpOps .= "\n// --- TABLE: $table ---\n";
    $dumpOps .= "echo '> Dropping $table... ';\n";
    $dumpOps .= "try { \$pdo->exec('DROP TABLE IF EXISTS `$table`'); echo 'OK<br>'; } catch(Exception \$e) { echo '<span style=\"color:#f87171\">ERR: '.\$e->getMessage().'</span><br>'; }\n";
    
    $safeCreate = addslashes($createSQL);
    $dumpOps .= "echo '> Creating $table... ';\n";
    $dumpOps .= "try { \$pdo->exec('$safeCreate'); echo 'OK<br>'; } catch(Exception \$e) { echo '<span style=\"color:#f87171\">ERR: '.\$e->getMessage().'</span><br>'; \$errors++; }\n";

    // Data
    $rows = $pdo_local->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
    if (count($rows) > 0) {
        $dumpOps .= "echo '> Inserting " . count($rows) . " rows into $table... ';\n";
        
        // Batch
        $batchSize = 50;
        $total = count($rows);
        $curr = 0;
        
        // Get column types to handle specialized formatting if needed (simplified here)
        
        for ($i = 0; $i < $total; $i += $batchSize) {
            $batch = array_slice($rows, $i, $batchSize);
            $values = [];
            foreach ($batch as $row) {
                $rowVals = [];
                foreach ($row as $val) {
                    if ($val === null) $rowVals[] = "NULL";
                    else $rowVals[] = $pdo_local->quote($val);
                }
                $values[] = "(" . implode(',', $rowVals) . ")";
            }
            
            $insertSQL = "INSERT INTO `$table` VALUES " . implode(',', $values);
            // $safeInsert = addslashes($insertSQL); // Not needed if using quote() on values, but quote() adds surrounding quotes.
            // Wait, $pdo_local->quote() adds quotes. So we don't need confusing addslashes for the SQL string itself IF we put it in double quotes in PHP? No.
            // Best way: HEREDOC for the SQL string in generated file? No.
            // We need to escape single quotes because we will wrap this query in '$pdo->exec(\' ... \')'
            
            $safeInsert = str_replace("'", "\'", $insertSQL);
            $safeInsert = str_replace("\\", "\\\\", $safeInsert); // Escape backslashes first!
            
            // Actually, simplest is to use Nowdoc/Heredoc in generated file, BUT we are in a generator.
            // Let's use stripslashes/addslashes correctly.
            
            // Re-strategy: We are writing PHP code.
            // $pdo->exec("INSERT ...");
            // The string inside exec must be valid SQL. 
            // We just need to escape the double quotes if we use double quotes for exec("...").
            $safeInsert = addslashes($insertSQL); // Escapes ', ", \, NULL byte
            
            $dumpOps .= "\$pdo->exec('$safeInsert');\n";
        }
        $dumpOps .= "echo 'Done.<br>';\n";
    }
    $dumpOps .= "echo '<br>';\n";
}

$content .= $dumpOps;

// 3. FOOTER
$content .= <<<PHP

\$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
\$end = microtime(true);
\$time = round(\$end - \$start, 2);

echo "</div>";
echo "<h3 style='color:green;'>🎉 DONE! Processed in \$time sec. Errors: \$errors</h3>";
echo "<p style='background:#fef2f2; color:#b91c1c; padding:10px; border:1px solid #fca5a5; border-radius:5px;'>⚠️ <strong>IMPORTANT:</strong> Please DELETE this file (<code>install_db_hosting.php</code>) from your hosting file manager immediately for security!</p>";
echo "<a href='index.php' style='display:inline-block; background:#2563eb; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Go to Dashboard</a>";
echo "</div></body></html>";
PHP;

file_put_contents('install_db_hosting.php', $content);
echo "<h3>✅ 'install_db_hosting.php' Created!</h3>";
echo "Size: " . round(filesize('install_db_hosting.php') / 1024, 2) . " KB<br>";
echo "<p>Next Step: Upload this file to hosting and open it in browser.</p>";
?>
