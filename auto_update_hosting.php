<?php
// auto_update_hosting.php
// Script Khusus untuk Update Database Hosting (InfinityFree)

// 1. Credentials (HARDCODED for Hosting)
$host = 'sql100.infinityfree.com';
$db   = 'if0_40595177_jamiah_abat_db';
$user = 'if0_40595177';
$pass = 'Bungganteng21';

echo "<h1>Auto-Update Database Hosting</h1>";
echo "Target DB: $db<hr>";

try {
    // 2. Connect
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green'>✅ Koneksi Berhasil!</p>";

} catch (PDOException $e) {
    die("<h3 style='color:red'>❌ Koneksi Gagal: " . $e->getMessage() . "</h3><p>Pastikan file ini dijalankan di Hosting, bukan di Localhost (kecuali IP local diizinkan).</p>");
}

// 3. SQL Commands Array
$commands = [
    // A. Fix Table Pelanggaran (Drop & Recreate)
    "DROP TABLE IF EXISTS pelanggaran" => "Hapus tabel pelanggaran lama",
    
    "CREATE TABLE pelanggaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasantri_id INT NOT NULL,
        nim VARCHAR(50) NOT NULL,
        tanggal DATE NOT NULL,
        jenis VARCHAR(50) NOT NULL,
        tingkat_sanksi VARCHAR(50) DEFAULT 'Normal',
        deskripsi TEXT,
        sanksi VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (nim),
        INDEX (mahasantri_id)
    )" => "Buat tabel pelanggaran BARU (Fixed Schema)",

    // B. Create System Logs
    "CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(100) NOT NULL DEFAULT 'System',
        action VARCHAR(100) NOT NULL,
        details TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )" => "Buat tabel system_logs",

    // C. Add Columns to Mahasantri (Using silent ALTER)
    // We use a trick: duplicate column error is caught in the loop
    "ALTER TABLE mahasantri ADD COLUMN asal_ppui VARCHAR(100) AFTER asal" => "Tambah kol asal_ppui",
    "ALTER TABLE mahasantri ADD COLUMN kabupaten VARCHAR(100) AFTER asal" => "Tambah kol kabupaten",
    "ALTER TABLE mahasantri ADD COLUMN provinsi VARCHAR(100) AFTER kabupaten" => "Tambah kol provinsi",
    "ALTER TABLE mahasantri ADD COLUMN kecamatan VARCHAR(100) AFTER provinsi" => "Tambah kol kecamatan",
    "ALTER TABLE mahasantri ADD COLUMN kelurahan VARCHAR(100) AFTER kecamatan" => "Tambah kol kelurahan",
];

// 4. Execution Loop
echo "<ul>";
foreach ($commands as $sql => $desc) {
    echo "<li><strong>$desc</strong>... ";
    try {
        $pdo->exec($sql);
        echo "<span style='color:green'>BERHASIL ✅</span>";
    } catch (PDOException $e) {
        // Ignore "Duplicate column name" error (Code 42S21)
        if ($e->getCode() == '42S21') {
            echo "<span style='color:orange'>SKIP (Sudah Ada) ⚠️</span>";
        } else {
            echo "<span style='color:red'>ERROR: " . $e->getMessage() . " ❌</span>";
        }
    }
    echo "</li>";
}
echo "</ul>";

echo "<hr><h3>🎉 UPDATE SELESAI!</h3>";
echo "<p>Silakan hapus file <code>auto_update_hosting.php</code> ini demi keamanan setelah dipakai.</p>";
echo "<a href='index.php'>Kembali ke Dashboard</a>";
?>
