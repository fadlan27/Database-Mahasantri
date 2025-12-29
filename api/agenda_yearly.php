<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/class_agenda.php';

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    // Default to current year if not specified
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Calculate Start and End of the Year
    $start = "$year-01-01 00:00:00";
    $end   = "$year-12-31 23:59:59";

    // Use existing logic
    $events = AgendaLogic::processEvents($pdo, $start, $end);
    
    echo json_encode([
        'status' => 'success',
        'year' => $year,
        'count' => count($events),
        'data' => $events
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
