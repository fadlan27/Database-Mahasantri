<?php
require_once '../functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$type = $_GET['type'] ?? 'santri'; // 'santri' or 'guru'
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $results = [];

    if ($type === 'santri') {
        $stmt = $pdo->prepare("SELECT id, nama, nim FROM mahasantri WHERE nama LIKE ? OR nim LIKE ? LIMIT 10");
        $stmt->execute(["%$query%", "%$query%"]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($data as $d) {
            $results[] = [
                'id' => $d['id'],
                'text' => $d['nama'] . ' (' . $d['nim'] . ')'
            ];
        }
    } 
    // Add logic for 'guru' search if a Teachers table exists later
    // For now we only focus on Santri since that's the priority for Wali
    
    echo json_encode($results);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
