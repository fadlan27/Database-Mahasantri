<?php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Aggregation Query
    // Group by Mustawa (Class)
    // Count Ikhwan (Active)
    // Count Akhowat (Active)
    
    $sql = "SELECT 
                mustawa,
                SUM(CASE WHEN gender = 'Ikhwan' THEN 1 ELSE 0 END) as count_ikhwan,
                SUM(CASE WHEN gender = 'Akhowat' THEN 1 ELSE 0 END) as count_akhowat,
                COUNT(*) as total
            FROM mahasantri
            WHERE status = 'Aktif'
            GROUP BY mustawa
            ORDER BY FIELD(mustawa, 'Awwal', 'Tsani')";

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
