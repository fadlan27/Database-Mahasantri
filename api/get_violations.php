<?php
// api/get_violations.php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$studentId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Student ID']);
    exit;
}

try {
    // 1. Get Student Info
    $stmt = $pdo->prepare("SELECT id, nama, nim, photo_path, angkatan, gender, status FROM mahasantri WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        exit;
    }

    // 2. Get Violations History by NIM (to persistent across ID changes)
    $nim = $student['nim'];
    $stmt = $pdo->prepare("SELECT * FROM pelanggaran WHERE nim = ? ORDER BY tanggal DESC, id DESC");
    $stmt->execute([$nim]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'student' => $student,
        'violations' => $violations
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
