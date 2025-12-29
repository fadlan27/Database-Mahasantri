<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../functions.php';

header('Content-Type: application/json');

// Security Check
if (!isLoggedIn() || !hasRole('superadmin')) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

try {
    // START SESSION FOR UNDO STACK
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['migration_undo_stack'] = []; // Reset undo stack

    $executed_logs = [];
    $undo_commands = [];

    // --- HELPER ---
    if (!function_exists('columnExists')) {
        function columnExists($pdo, $table, $column) {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
            return $stmt->fetch() !== false;
        }
    }
    if (!function_exists('indexExists')) {
        function indexExists($pdo, $table, $indexName) {
            $stmt = $pdo->query("SHOW INDEX FROM $table WHERE Key_name = '$indexName'");
            return $stmt->fetch() !== false;
        }
    }

    // --- MIGRATION DEFINITIONS ---
    // 'check': logic to run this migration (return true to run)
    // 'up': SQL to execute
    // 'down': SQL to undo (reversed)
    // 'desc': Description for user

    $migrations = [
        // 1. Create Tables
        [
            'check' => function($pdo) { 
                $stmt = $pdo->query("SHOW TABLES LIKE 'pelanggaran'");
                return $stmt->fetch() === false; 
            },
            'up' => "CREATE TABLE pelanggaran (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mahasantri_id INT NOT NULL,
                tanggal DATE NOT NULL,
                jenis ENUM('Ringan', 'Sedang', 'Berat') NOT NULL,
                deskripsi TEXT,
                sanksi VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (mahasantri_id) REFERENCES mahasantri(id) ON DELETE CASCADE
            )",
            'down' => "DROP TABLE IF EXISTS pelanggaran",
            'desc' => "Membuat Tabel 'pelanggaran'"
        ],
        [
            'check' => function($pdo) { 
                $stmt = $pdo->query("SHOW TABLES LIKE 'history_kelas'");
                return $stmt->fetch() === false; 
            },
            'up' => "CREATE TABLE history_kelas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mahasantri_id INT NOT NULL,
                old_mustawa VARCHAR(50),
                new_mustawa VARCHAR(50),
                tanggal_promosi DATETIME DEFAULT CURRENT_TIMESTAMP,
                keterangan VARCHAR(255),
                FOREIGN KEY (mahasantri_id) REFERENCES mahasantri(id) ON DELETE CASCADE
            )",
            'down' => "DROP TABLE IF EXISTS history_kelas",
            'desc' => "Membuat Tabel 'history_kelas'"
        ],
        
        // 2. Add Missing Columns
        [
            'check' => function($pdo) { return !columnExists($pdo, 'mahasantri', 'tempat_lahir'); },
            'up' => "ALTER TABLE mahasantri ADD COLUMN tempat_lahir VARCHAR(100) AFTER asal",
            'down' => "ALTER TABLE mahasantri DROP COLUMN tempat_lahir",
            'desc' => "Menambah Kolom 'tempat_lahir'"
        ],
        [
            'check' => function($pdo) { return !columnExists($pdo, 'mahasantri', 'tanggal_lahir'); },
            'up' => "ALTER TABLE mahasantri ADD COLUMN tanggal_lahir DATE AFTER tempat_lahir",
            'down' => "ALTER TABLE mahasantri DROP COLUMN tanggal_lahir",
            'desc' => "Menambah Kolom 'tanggal_lahir'"
        ],
        [
            'check' => function($pdo) { return !columnExists($pdo, 'mahasantri', 'wa_wali'); },
            'up' => "ALTER TABLE mahasantri ADD COLUMN wa_wali VARCHAR(20) AFTER nama_ibu",
            'down' => "ALTER TABLE mahasantri DROP COLUMN wa_wali",
            'desc' => "Menambah Kolom 'wa_wali'"
        ],
        [
            'check' => function($pdo) { return !columnExists($pdo, 'mahasantri', 'provinsi'); },
            'up' => "ALTER TABLE mahasantri ADD COLUMN provinsi VARCHAR(100) AFTER alamat_lengkap",
            'down' => "ALTER TABLE mahasantri DROP COLUMN provinsi",
            'desc' => "Menambah Kolom 'provinsi'"
        ],
        [
            'check' => function($pdo) { return !columnExists($pdo, 'mahasantri', 'kabupaten'); },
            'up' => "ALTER TABLE mahasantri ADD COLUMN kabupaten VARCHAR(100) AFTER alamat_lengkap",
            'down' => "ALTER TABLE mahasantri DROP COLUMN kabupaten",
            'desc' => "Menambah Kolom 'kabupaten'"
        ],
        [
            'check' => function($pdo) { return !columnExists($pdo, 'mahasantri', 'nama_ayah') && columnExists($pdo, 'mahasantri', 'ayah'); },
            'up' => "ALTER TABLE mahasantri CHANGE ayah nama_ayah VARCHAR(150)",
            'down' => "ALTER TABLE mahasantri CHANGE nama_ayah ayah VARCHAR(150)", // Assuming previous was VARCHAR
            'desc' => "Rename kolom 'ayah' -> 'nama_ayah'"
        ],

        // 3. Indexes (Only add, removing index on undo is tricky if duplicate exists but usually safe to Drop)
        [
            'check' => function($pdo) { return !indexExists($pdo, 'mahasantri', 'idx_nama'); },
            'up' => "CREATE INDEX idx_nama ON mahasantri(nama)",
            'down' => "DROP INDEX idx_nama ON mahasantri",
            'desc' => "Menambah Index Performance 'nama'"
        ]
    ];

    // --- EXECUTE ---
    foreach ($migrations as $mig) {
        if ($mig['check']($pdo)) {
            $pdo->exec($mig['up']);
            $executed_logs[] = $mig['desc'];
            
            // Add to Undo Stack (LIFO: Last In First Out)
            array_unshift($undo_commands, $mig['down']);
        }
    }

    // --- RESULT ---
    if (empty($executed_logs)) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Database sudah optimal. Tidak ada perubahan.',
            'changes' => []
        ]);
    } else {
        // Save Undo Stack to Session
        $_SESSION['migration_undo_stack'] = $undo_commands;

        echo json_encode([
            'status' => 'success', 
            'message' => 'Update Berhasil!',
            'changes' => $executed_logs,
            'can_undo' => true
        ]);
    }

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "SQL Error: " . $e->getMessage()]);
}
?>
