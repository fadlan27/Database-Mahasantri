<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/class_agenda.php';

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Fetch Events
        if (!isset($_GET['start']) || !isset($_GET['end'])) {
            throw new Exception("Missing start/end parameters");
        }
        
        $events = AgendaLogic::processEvents($pdo, $_GET['start'], $_GET['end']);
        echo json_encode($events);

    } elseif ($method === 'POST') {
        // Create Agenda
        $input = json_decode(file_get_contents('php://input'), true);

        // Validation (basic)
        if (empty($input['judul']) || empty($input['tgl_mulai'])) {
            throw new Exception("Judul dan Tanggal Mulai wajib diisi");
        }

        $sql = "INSERT INTO agenda_sekolah 
                (judul, deskripsi, kategori_id, tgl_mulai, tgl_selesai, is_full_day, is_recurring, tipe_kalender, target_role, created_by) 
                VALUES 
                (:judul, :deskripsi, :kategori_id, :tgl_mulai, :tgl_selesai, :is_full_day, :is_recurring, :tipe_kalender, :target_role, :created_by)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':judul' => $input['judul'],
            ':deskripsi' => $input['deskripsi'] ?? null,
            ':kategori_id' => $input['kategori_id'],
            ':tgl_mulai' => $input['tgl_mulai'],
            ':tgl_selesai' => $input['tgl_selesai'],
            ':is_full_day' => $input['is_full_day'] ?? 1,
            ':is_recurring' => $input['is_recurring'] ?? 0,
            ':tipe_kalender' => $input['tipe_kalender'] ?? 'masehi',
            ':target_role' => $input['target_role'] ?? 'all',
            ':created_by' => $_SESSION['user_id'] ?? 1 // Default to 1 if no session for now
        ]);

        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);

    } elseif ($method === 'DELETE') {
        // Delete Agenda
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $stmt = $pdo->prepare("DELETE FROM agenda_sekolah WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
