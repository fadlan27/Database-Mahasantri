<?php
// generate_importer.php (JALANKAN DI LOCALHOST)
// Script ini akan membuat file 'hosting_importer.php' yang berisi DATA LENGKAP + CARA INSTALLNYA.

require_once 'config/database.php';

// 1. Konfigurasi Hosting (Target)
$target_host = 'sql100.infinityfree.com';
$target_db   = 'if0_40595177_jamiah_abat_db';
$target_user = 'if0_40595177';
$target_pass = 'Bungganteng21';

echo "<h1>Sedang Membungkus Database...</h1>";

// 2. Mulai Bikin Konten File Installer
$phpHeader = <<<EOT
<?php
// hosting_importer.php
// FILE INI DIBUAT OTOMATIS OLEH 'generate_importer.php'
// UNTUK DIUPLOAD DAN DIJALANKAN DI HOSTING (InfinityFree)

ini_set('memory_limit', '512M');
set_time_limit(300);

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f0f2f5;'>";
echo "<div style='background:white; padding:30px; border-radius:15px; max-width:800px; margin:0 auto; box-shadow:0 4px 6px rgba(0,0,0,0.1);'>";
echo "<h2 style='color:#2563eb;'>🚀 Installer Database Otomatis</h2>";
echo "<p>Sedang menghubungkan ke Database Hosting...</p>";

\$host = '$target_host';
\$db   = '$target_db';
\$user = '$target_user';
\$pass = '$target_pass';

try {
    \$pdo = new PDO("mysql:host=\$host;dbname=\$db;charset=utf8mb4", \$user, \$pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green;'>✅ Koneksi Sukses!</p>";
} catch (PDOException \$e) {
    die("<h3 style='color:red;'>❌ Koneksi Gagal: " . \$e->getMessage() . "</h3><p>Pastikan file ini dijalankan di hosting yang benar.</p></div></body></html>");
}

echo "<h3>Mulai Proses Import Data...</h3>";
echo "<div style='background:#1e293b; color:#aaa; padding:15px; border-radius:8px; height:300px; overflow-y:auto; font-family:monospace; font-size:12px;'>";

\$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

// --- DATA DUMP START ---
\$sql_commands = [
EOT;

// 3. Ambil Data dari Local Database
$tables = [];
$result = $pdo->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];

$dumpContent = "";

foreach ($tables as $table) {
    // Skip logs biar file gak kegedean (optional)
    if ($table == 'system_logs') continue;

    // Drop & Create
    $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $createSql = $row2[1];
    
    // Escape quote untuk PHP String array
    $createSql = str_replace("'", "\'", $createSql); 
    
    $dumpContent .= "\n'DROP TABLE IF EXISTS `$table`',";
    $dumpContent .= "\n'$createSql',";

    // Insert Data
    $result3 = $pdo->query("SELECT * FROM `$table`");
    $numFields = $result3->columnCount();
    
    if ($result3->rowCount() > 0) {
        $rows = $result3->fetchAll(PDO::FETCH_NUM);
        
        // Batch Inserts (biar gak timeout) - per 50 rows
        $batchSize = 50;
        $totalRows = count($rows);
        
        for ($i = 0; $i < $totalRows; $i += $batchSize) {
            $batch = array_slice($rows, $i, $batchSize);
            
            $values = [];
            foreach ($batch as $row) {
                $rowVals = [];
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $rowVals[] = '"' . $row[$j] . '"';
                    } else {
                        $rowVals[] = 'NULL';
                    }
                }
                $values[] = "(" . implode(',', $rowVals) . ")";
            }
            
            $insertSql = "INSERT INTO `$table` VALUES " . implode(',', $values);
            // Escape single quotes for PHP string
            $insertSql = str_replace("'", "\'", $insertSql);
            
            $dumpContent .= "\n'$insertSql',";
        }
    }
}

// 4. Tutup File
$phpFooter = <<<EOT
];
// --- DATA DUMP END ---

\$count = 0;
foreach (\$sql_commands as \$cmd) {
    \$cmd = stripslashes(\$cmd); // Balikin single quotes
    try {
        if(trim(\$cmd) == '') continue;
        \$pdo->exec(\$cmd);
        \$count++;
        // Feedback per 5 commands biar gak berat render
        if(\$count % 5 == 0) echo "<div>Running command #\$count... OK</div>";
    } catch (PDOException \$e) {
        // Tampilkan error tapi lanjut
        echo "<div style='color:#f87171;'>ERROR at cmd #\$count: " . substr(\$cmd, 0, 100) . "... (" . \$e->getMessage() . ")</div>";
    }
    flush(); 
}

\$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

echo "</div>";
echo "<h2 style='color:green;'>🎉 Alhamdulillah! Import Selesai.</h2>";
echo "<p>Total perintah dieksekusi: <strong>\$count</strong></p>";
echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-top:20px;'>";
echo "<strong>PENTING:</strong><br>Silakan hapus file <code>hosting_importer.php</code> ini dari hosting Anda sekarang agar tidak disalahgunakan orang lain.";
echo "</div>";
echo "<br><a href='index.php' style='display:inline-block; padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:8px;'>Masuk ke Dashboard</a>";
echo "</div></body></html>";
?>
EOT;

// 5. Write File
$fullContent = $phpHeader . $dumpContent . "\n" . $phpFooter;
file_put_contents('hosting_importer.php', $fullContent);

echo "<h3>✅ File 'hosting_importer.php' Berhasil Dibuat!</h3>";
echo "<p>Lokasi: <code>" . __DIR__ . "/hosting_importer.php</code></p>";
echo "<h3>Instruksi Selanjutnya:</h3>";
echo "<ol>
        <li>Buka folder project ini.</li>
        <li>Cari file bernama <strong>hosting_importer.php</strong>.</li>
        <li><strong>Upload</strong> file tersebut ke Hosting Anda.</li>
        <li>Buka file tersebut di browser (contoh: <em>fadlan.xo.je/.../hosting_importer.php</em>).</li>
        <li>Tunggu sampai proses selesai.</li>
      </ol>";
?>
