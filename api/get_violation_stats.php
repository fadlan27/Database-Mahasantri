<?php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Aggregation Query
    // We Group by Mustawa (Class)
    // We count:
    // - Total Students Involved (Distinct ID)
    // - Total Violation Records
    // - Count of each Category (Ringan, Sedang, Berat)
    // - Count of specific Sanctions (SP1, SP2, SP3)
    
    $sql = "SELECT 
                m.mustawa,
                COUNT(DISTINCT p.mahasantri_id) as student_count,
                COUNT(p.id) as total_violations,
                SUM(CASE WHEN p.jenis = 'Ringan' THEN 1 ELSE 0 END) as count_ringan,
                SUM(CASE WHEN p.jenis = 'Sedang' THEN 1 ELSE 0 END) as count_sedang,
                SUM(CASE WHEN p.jenis = 'Berat' THEN 1 ELSE 0 END) as count_berat,
                SUM(CASE WHEN p.tingkat_sanksi = 'SP1' THEN 1 ELSE 0 END) as count_sp1,
                SUM(CASE WHEN p.tingkat_sanksi = 'SP2' THEN 1 ELSE 0 END) as count_sp2,
                SUM(CASE WHEN p.tingkat_sanksi = 'SP3' THEN 1 ELSE 0 END) as count_sp3
            FROM mahasantri m
            JOIN pelanggaran p ON m.id = p.mahasantri_id
            WHERE m.status = 'Aktif'
            GROUP BY m.mustawa
            ORDER BY FIELD(m.mustawa, 'Awwal', 'Tsani')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
