<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/config/database.php

// ==========================================
// KONFIGURASI DATABASE 2-TIER (ONLINE -> LOCAL AUTO-CREATE)
// ==========================================

// 1. ONLINE (InfinityFree)
$online_host     = 'sql100.infinityfree.com';
$online_db       = 'if0_40595177_jamiah_abat_db';
$online_user     = 'if0_40595177';
$online_pass     = 'Bungganteng21';

// 2. LOCAL (Laragon) - Auto-Create Capable
$local_host      = 'localhost';
$local_db        = 'jamiah_abat_db';
$local_user      = 'root';
$local_pass      = '';

// ==========================================

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // TIER 1: ONLINE
    $dsn = "mysql:host=$online_host;dbname=$online_db;charset=utf8mb4";
    $pdo = new PDO($dsn, $online_user, $online_pass, $options);
    define('DB_MODE', 'ONLINE');

} catch (PDOException $e_online) {
    try {
        // TIER 2: LOCAL
        // First try standard connection
        $dsn = "mysql:host=$local_host;dbname=$local_db;charset=utf8mb4";
        $pdo = new PDO($dsn, $local_user, $local_pass, $options);
        define('DB_MODE', 'LOCAL');

    } catch (PDOException $e_local) {
        // Handle "Unknown Database" (Code 1049) -> Auto Create
        if ($e_local->getCode() == 1049) {
            try {
                // Connect without DB
                $pdo_root = new PDO("mysql:host=$local_host;charset=utf8mb4", $local_user, $local_pass, $options);
                $pdo_root->exec("CREATE DATABASE `$local_db`");
                
                // Reconnect with new DB
                $dsn = "mysql:host=$local_host;dbname=$local_db;charset=utf8mb4";
                $pdo = new PDO($dsn, $local_user, $local_pass, $options);
                
                // Run Setup automatically
                define('DB_MODE', 'LOCAL_CREATED');
                
            } catch (PDOException $e_create) {
                 handleDbError("Gagal Membuat Database Lokal: " . $e_create->getMessage());
            }
        } else {
             handleDbError("Koneksi Gagal. Online: " . $e_online->getMessage() . " | Local: " . $e_local->getMessage());
        }
    }
}

function handleDbError($msg) {
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
        strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => $msg]));
    } else {
        die("<h3>Sistem Error</h3><p>$msg</p><hr><p>Pastikan Laragon (MySQL) sudah berjalan.</p>");
    }
}

// Auto-Run Setup if Database was just created
if (defined('DB_MODE') && DB_MODE === 'LOCAL_CREATED') {
    define('SETUP_NEEDED', true);
    
    // Auto-Redirect to Setup if not already there (and not in a CLI/API context)
    $currentAction = basename($_SERVER['PHP_SELF']);
    if ($currentAction !== 'setup_database.php' && $currentAction !== 'check_db.php') {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             // If AJAX, return Error JSON instead of Redirect
             header('Content-Type: application/json');
             echo json_encode(['status' => 'error', 'message' => 'Database baru dibuat. Silakan refresh halaman untuk setup.']);
             exit;
        } else {
             header("Location: setup_database.php");
             exit;
        }
    }
    define('SETUP_NEEDED', false);
}
