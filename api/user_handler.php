<?php
require_once '../config/database.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// --- CSRF VALIDATION ---
$headers = getallheaders();
$csrf_token = $headers['X-CSRF-TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token! Refresh halaman.']);
    exit;
}

// --- HANDLE CREATE USER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        
        // Default password for new users
        $default_password = '123';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'guru'; // Default to guru if not set
        $linked_id = $_POST['linked_id'] ?: null; // Handle empty string as NULL

        // 1. Validation: Wali MUST have linked_id
        if ($role === 'wali' && empty($linked_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Role Wali Santri wajib dihubungkan dengan Data Santri!']);
            exit;
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username sudah digunakan!']);
            exit;
        }

        $sql = "INSERT INTO users (username, password, full_name, role, linked_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $hashed_password, $full_name, $role, $linked_id]);

        echo json_encode(['status' => 'success', 'message' => "User berhasil ditambahkan as $role. Password default: 123"]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}

// --- HANDLE UPDATE USER ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $id = $_POST['id'];
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        $linked_id = $_POST['linked_id'] ?: null;
        
        // 1. Validation: Wali MUST have linked_id
        if ($role === 'wali' && empty($linked_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Role Wali Santri wajib dihubungkan dengan Data Santri!']);
            exit;
        }

        // Prevent editing self username to avoid session issues (simplified logic)
        $sql = "UPDATE users SET full_name = ?, role = ?, linked_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $role, $linked_id, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Data user diperbarui']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}

// --- HANDLE RESET PASSWORD ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    try {
        $id = $_POST['id'];
        $default_password = '123';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hashed_password, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Password di-reset ke: 123']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}

// --- HANDLE DELETE USER ---
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        // Prevent deleting self
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak bisa menghapus akun sendiri!']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Admin berhasil dihapus']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}

// --- CHANGE OWN PASSWORD (PROFILE) ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    try {
        $user_id = $_SESSION['user_id'];
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        // Verify old password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current = $stmt->fetchColumn();

        if (!password_verify($old_pass, $current)) {
            echo json_encode(['status' => 'error', 'message' => 'Password lama salah!']);
            exit;
        }

        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$new_hash, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah!']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
