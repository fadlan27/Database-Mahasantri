<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/check_db.php

require_once 'config/database.php';

echo "<h1>Database Connection Diagnostic</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<div style='padding: 20px; background: #f0f9ff; border: 2px solid #00aaff; border-radius: 10px;'>";
echo "<h2>Current Connection Status</h2>";
echo "<h3>Mode: <span style='background: black; color: white; padding: 5px 10px; border-radius: 5px;'>" . (defined('DB_MODE') ? DB_MODE : 'UNKNOWN') . "</span></h3>";

if (defined('DB_MODE')) {
    echo "<ul>";
    if (DB_MODE === 'ONLINE') echo "<li>✅ Connected to Online Database (InfinityFree)</li>";
    if (DB_MODE === 'LOCAL')  echo "<li>✅ Connected to Local Database (Laragon MySQL)</li>";
    if (DB_MODE === 'LOCAL_CREATED') echo "<li>⚠️ Local Database Created (Need Setup)</li>";
    echo "</ul>";
    
    // Check for Auto-Setup Requirement
    if (defined('SETUP_NEEDED') && SETUP_NEEDED === true) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; border-radius: 5px; margin-top: 10px;'>";
        echo "<strong>Database Lokal Baru Dibuat!</strong><br>";
        echo "Database `jamiah_abat_db` baru saja dibuat otomatis di Laragon.<br>";
        echo "Silakan jalankan setup untuk membuat tabel.";
        echo "<br><br><a href='setup_database.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Jalankan Setup Database</a>";
        echo "</div>";
    } else {
        // Test Query
        try {
            $stmt = $pdo->query("SELECT count(*) FROM users");
            $count = $stmt->fetchColumn();
            echo "<p>Test Query (Users count): <b>$count</b></p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>Database Connected but Tables Missing? Error: " . $e->getMessage() . "</p>";
            echo "<a href='setup_database.php'>Run Setup Database</a>";
        }
    }
}
echo "</div>";

echo "<hr><h3>Fallback Logic Explanation:</h3>";
echo "<ol>";
echo "<li><b>Tier 1 (Online):</b> Mencoba koneksi ke hosting (<code>sql100.infinityfree.com</code>).</li>";
echo "<li><b>Tier 2 (Local):</b> Jika gagal, mencoba koneksi ke <code>localhost</code> (Laragon).</li>";
echo "<li><b>Auto-Create:</b> Jika database lokal belum ada, sistem akan membuatnya otomatis.</li>";
echo "</ol>";
?>
