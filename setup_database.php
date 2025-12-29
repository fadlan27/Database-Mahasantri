<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/setup_database.php

require_once 'config/database.php';

echo "<h1>Setting up Database...</h1>";

try {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'ustadz') DEFAULT 'ustadz',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'users' checked/created.<br>";

    // Seed Admin User
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$pass', 'Administrator', 'admin')");
        echo "Admin user created (User: admin, Pass: admin123).<br>";
    }

    // 2. Mahasantri Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS mahasantri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nim VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        gender ENUM('Ikhwan', 'Akhowat') NOT NULL,
        tempat_lahir VARCHAR(100),
        tanggal_lahir DATE,
        angkatan YEAR NOT NULL,
        mustawa ENUM('Awwal', 'Tsani', 'Lulus') NOT NULL,

        status ENUM('Aktif', 'Cuti', 'Lulus', 'Dikeluarkan', 'Drop Out') DEFAULT 'Aktif',
        asal VARCHAR(100),
        asal_ppui VARCHAR(100),

        provinsi VARCHAR(100),
        kabupaten VARCHAR(100),
        kecamatan VARCHAR(100),
        kelurahan VARCHAR(100),
        alamat_lengkap TEXT,
        nama_ayah VARCHAR(100),
        nama_ibu VARCHAR(100),
        wa_wali VARCHAR(20),
        photo_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Table 'mahasantri' checked/created.<br>";

    // 3. Pelanggaran Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS pelanggaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasantri_id INT NOT NULL,
        tanggal DATE NOT NULL,
        jenis ENUM('Ringan', 'Sedang', 'Berat') NOT NULL,
        deskripsi TEXT,
        sanksi VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mahasantri_id) REFERENCES mahasantri(id) ON DELETE CASCADE
    )");
    echo "Table 'pelanggaran' checked/created.<br>";

    // 4. History Kelas (Promotion Log) Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS history_kelas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasantri_id INT NOT NULL,
        old_mustawa VARCHAR(50),
        new_mustawa VARCHAR(50),
        tanggal_promosi DATETIME DEFAULT CURRENT_TIMESTAMP,
        keterangan VARCHAR(255),
        FOREIGN KEY (mahasantri_id) REFERENCES mahasantri(id) ON DELETE CASCADE
    )");
    echo "Table 'history_kelas' checked/created.<br>";

    echo "<h3>Setup Finished Successfully!</h3>";
    echo "<p><a href='login.php'>Go to Login</a></p>";

} catch (PDOException $e) {
    die("Setup Failed: " . $e->getMessage());
}
?>
