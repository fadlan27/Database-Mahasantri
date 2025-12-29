<?php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Calculate Stats BEFORE Update
    // Count 'Awwal' -> 'Tsani'
    $stmtTsani = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE mustawa = 'Awwal' AND status = 'Aktif'");
    $countTsani = $stmtTsani->fetchColumn();

    // Count 'Tsani' -> 'Lulus'
    $stmtLulus = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE mustawa = 'Tsani' AND status = 'Aktif'");
    $countLulus = $stmtLulus->fetchColumn();

    // 2. Execute Update (Simple Logic)
    $academicYear = getAcademicYear();
    $hijriYear = getHijriYear();

    $sql = "UPDATE mahasantri 
            SET 
                tahun_lulus_masehi = CASE 
                    WHEN mustawa = 'Tsani' THEN '$academicYear'
                    ELSE tahun_lulus_masehi 
                END,
                tahun_lulus_hijriah = CASE 
                    WHEN mustawa = 'Tsani' THEN '$hijriYear'
                    ELSE tahun_lulus_hijriah 
                END,
                status = CASE 
                    WHEN mustawa = 'Tsani' THEN 'Lulus' 
                    ELSE status 
                END,
                mustawa = CASE 
                    WHEN mustawa = 'Tsani' THEN 'Lulus'
                    WHEN mustawa = 'Awwal' THEN 'Tsani'
                    ELSE mustawa
                END
            WHERE status = 'Aktif'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $total = $stmt->rowCount();

    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "Proses Kenaikan Kelas Selesai.",
        'stats' => [
            'total' => $total,
            'tsani' => $countTsani,
            'lulus' => $countLulus
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
