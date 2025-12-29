<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/api/auth.php

// ENABLE DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$logFile = __DIR__ . '/debug_auth.log';
function logStep($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

logStep("Starting auth.php execution...");

try {
    require_once '../config/database.php';
    logStep("Database included successfully. DB_MODE: " . (defined('DB_MODE') ? DB_MODE : 'Not Defined'));
} catch (Exception $e) {
    logStep("Error including database: " . $e->getMessage());
    die("Error Database Config: " . $e->getMessage());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
    logStep("Session Started. ID: " . session_id());
} else {
    logStep("Session already active. ID: " . session_id());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    logStep("POST request received.");
    
    if (isset($_POST['login'])) {
        logStep("Login action detected.");
        $username = trim($_POST['username']);
        // Don't log password!
        logStep("Username: $username");

        if (empty($username) || empty($_POST['password'])) {
            logStep("Empty credentials. Redirecting back.");
            header("Location: ../login.php?error=Username dan Password wajib diisi");
            exit;
        }

        try {
            logStep("Preparing query...");
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user) {
                logStep("User found in DB. Verifying password...");
                if (password_verify($_POST['password'], $user['password'])) {
                    logStep("Password Valid. Setting Session...");
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    logStep("Redirecting to ../index.php");
                    header("Location: ../index.php");
                    exit;
                } else {
                    logStep("Password Invalid.");
                    header("Location: ../login.php?error=Username atau Password salah");
                    exit;
                }
            } else {
                logStep("User NOT found in DB.");
                header("Location: ../login.php?error=Username atau Password salah");
                exit;
            }
        } catch (PDOException $e) {
            logStep("Database Exception: " . $e->getMessage());
            header("Location: ../login.php?error=Database Error: " . urlencode($e->getMessage()));
            exit;
        }
    } else {
        logStep("POST request but 'login' key missing in \$_POST");
        // var_dump($_POST); // Careful with passwords
        header("Location: ../login.php?error=Form Invalid");
        exit;
    }
} elseif (isset($_GET['logout'])) {
    logStep("Logout requested via GET.");
    session_destroy();
    header("Location: ../login.php");
    exit;
} else {
    logStep("Not a POST login nor GET logout. Method: " . $_SERVER["REQUEST_METHOD"]);
    header("Location: ../login.php");
    exit;
}
