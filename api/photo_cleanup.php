<?php
require_once '../functions.php';

// Prevent direct access if needed, or simple protection
// if (!isLoggedIn()) die('Unauthorized');

header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'scanned' => 0,
    'cleaned' => 0,
    'missing_files' => []
];

try {
    // 1. Get all students with photos
    $stmt = $pdo->query("SELECT id, nama, photo_path FROM mahasantri WHERE photo_path IS NOT NULL AND photo_path != ''");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['scanned'] = count($students);

    // 2. Iterate and Check
    $idsToClean = [];
    
    foreach ($students as $s) {
        // Resolve path relative to THIS script (api folder)
        // Expected DB path: "uploads/filename.jpg"
        // Physical path: "../uploads/filename.jpg"
        
        $relativePath = $s['photo_path'];
        $physicalPath = __DIR__ . '/../' . $relativePath;
        
        // Check if file exists
        if (!file_exists($physicalPath)) {
            $idsToClean[] = $s['id'];
            $response['missing_files'][] = [
                'id' => $s['id'],
                'name' => $s['nama'],
                'path' => $relativePath
            ];
        }
    }

    // 3. Batch Update
    if (!empty($idsToClean)) {
        $response['cleaned'] = count($idsToClean);
        $inQuery = implode(',', $idsToClean);
        $pdo->exec("UPDATE mahasantri SET photo_path = NULL WHERE id IN ($inQuery)");
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
