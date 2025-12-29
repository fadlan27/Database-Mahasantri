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

// 0. CSRF CHECK
$headers = getallheaders();
$csrf_token = $headers['X-CSRF-TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token!']);
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['migration_undo_stack'])) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada perubahan yang bisa dibatalkan.']);
        exit;
    }

    $undo_commands = $_SESSION['migration_undo_stack'];
    $rollback_count = 0;

    foreach ($undo_commands as $sql) {
        $pdo->exec($sql);
        $rollback_count++;
    }

    // Clear stack after undo
    $_SESSION['migration_undo_stack'] = [];

    echo json_encode([
        'status' => 'success', 
        'message' => "Berhasil membatalkan $rollback_count perubahan struktur database."
    ]);

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Undo Error: " . $e->getMessage()]);
}
?>
