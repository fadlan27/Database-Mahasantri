<?php
require_once 'config/database.php';

try {
    echo "Starting Calendar System Schema Update...\n";

    // 1. Create agenda_kategori table
    $sql_kategori = "CREATE TABLE IF NOT EXISTS `agenda_kategori` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nama_kategori` VARCHAR(50) NOT NULL,
      `warna_bg` VARCHAR(7) NOT NULL,
      `warna_teks` VARCHAR(7) DEFAULT '#ffffff',
      `icon_class` VARCHAR(50) DEFAULT 'fas fa-circle'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_kategori);
    echo "[OK] Table 'agenda_kategori' check/create.\n";

    // 2. Seed agenda_kategori
    // Check if empty first to avoid duplicates on re-run
    $stmt = $pdo->query("SELECT COUNT(*) FROM agenda_kategori");
    if ($stmt->fetchColumn() == 0) {
        $data = [
            ['Akademik', '#3b82f6', '#ffffff', 'fas fa-graduation-cap'], // Blue
            ['PHBI', '#10b981', '#ffffff', 'fas fa-mosque'],            // Green
            ['Rapat', '#6b7280', '#ffffff', 'fas fa-users'],            // Gray
            ['Ujian', '#ef4444', '#ffffff', 'fas fa-file-alt'],         // Red
            ['Libur', '#f59e0b', '#ffffff', 'fas fa-calendar-times']    // Orange
        ];

        $insert = $pdo->prepare("INSERT INTO agenda_kategori (nama_kategori, warna_bg, warna_teks, icon_class) VALUES (?, ?, ?, ?)");
        
        foreach ($data as $row) {
            $insert->execute($row);
        }
        echo "[OK] Seeded 'agenda_kategori' with default values.\n";
    } else {
        echo "[SKIP] 'agenda_kategori' already has data.\n";
    }

    // 3. Create agenda_sekolah table
    $sql_agenda = "CREATE TABLE IF NOT EXISTS `agenda_sekolah` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `judul` VARCHAR(255) NOT NULL,
      `deskripsi` TEXT NULL,
      `kategori_id` INT NOT NULL,
      `tgl_mulai` DATETIME NOT NULL,
      `tgl_selesai` DATETIME NOT NULL,
      `is_full_day` TINYINT(1) DEFAULT 1,
      `is_recurring` TINYINT(1) DEFAULT 0,
      `tipe_kalender` ENUM('masehi', 'hijriyah') DEFAULT 'masehi',
      `target_role` ENUM('all','guru','siswa','staff') DEFAULT 'all',
      `created_by` INT NULL, 
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`kategori_id`) REFERENCES `agenda_kategori`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql_agenda);
    echo "[OK] Table 'agenda_sekolah' check/create.\n";

    echo "Schema Update Completed Successfully!\n";

} catch (PDOException $e) {
    die("[ERROR] Database Error: " . $e->getMessage() . "\n");
}
?>
