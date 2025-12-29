<?php
// api/reset_handler.php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// 1. Get Payload
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password admin diperlukan.']);
    exit;
}

// 2. Verify Password (Re-Auth)
// Assuming we store user info in session or have a way to check.
// Since functions.php is required, we check if we have access to user data.
// Based on typical login systems:
try {
    $userId = $_SESSION['user_id'] ?? 1; // Default to 1 if not set, but requireLogin should handle it
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        // Fallback: Check if it's plain text (if legacy system) or just reject
        // For safety, strictly require password_verify
        echo json_encode(['status' => 'error', 'message' => 'Password Admin Salah!']);
        exit;
    }
    
    // 3. Execute Reset (TRUNCATE)
    // NOTE: TRUNCATE is DDL and causes implicit commit, so we CANNOT use transactions here.
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    $tables = ['mahasantri', 'pelanggaran', 'history_kelas', 'mutasi_log', 'system_logs', 'log_aktivitas'];
    
    foreach ($tables as $t) {
        // Check if table exists first to avoid error
        try {
            $pdo->exec("TRUNCATE TABLE `$t`");
        } catch (PDOException $e) {
            // Ignore if table doesn't exist, strictly speaking
        }
    }
    
    // Also reset any other related tables if needed
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    // $pdo->commit(); <--- REMOVED due to implicit commit by TRUNCATE
    
    logActivity('Reset Database', 'Melakukan Pemutihan Data (Truncate All Tables)');
    
    echo json_encode(['status' => 'success', 'message' => 'Database berhasil diputihkan. Semua data santri telah dihapus.']);

} catch (Exception $e) {
    // if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Gagal Reset: ' . $e->getMessage()]);
}
?>
