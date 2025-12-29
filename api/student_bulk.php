<?php
require_once '../functions.php';
header('Content-Type: application/json');

// Ensure only logged-in users can access
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// --- HANDLE GET REQUESTS (SEARCH) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'search') {
        try {
            $q = trim($_GET['q'] ?? '');
            $mustawa = trim($_GET['mustawa'] ?? '');
            
            if (empty($q) && empty($mustawa)) {
                echo json_encode(['status' => 'success', 'data' => []]);
                exit;
            }

            $sql = "SELECT id, nim, nama, gender, mustawa, status, photo_path FROM mahasantri WHERE 1=1";
            $params = [];

            if (!empty($q)) {
                $sql .= " AND (nama LIKE ? OR nim LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }

            if (!empty($mustawa)) {
                $sql .= " AND mustawa = ?";
                $params[] = $mustawa;
            }

            $sql .= " ORDER BY nama ASC LIMIT 100"; // Limit results for performance

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // AUTO-PRUNE: Check if files exist
            foreach ($data as &$row) {
                if (!empty($row['photo_path'])) {
                    // Assuming photo_path is stored as "uploads/filename.jpg"
                    // Physical path is ../uploads/filename.jpg relative to this api file
                    $physicalPath = __DIR__ . '/../' . $row['photo_path'];
                    
                    if (!file_exists($physicalPath)) {
                        // File missing! Prune from DB
                        $pdo->query("UPDATE mahasantri SET photo_path = NULL WHERE id = {$row['id']}");
                        $row['photo_path'] = null; // Update current response
                    }
                }
            }

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    exit; 
}

// --- HANDLE POST REQUESTS (ACTIONS) ---
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];

try {
    // 1. MASS ADD
    if ($action === 'mass_add') {
        $data = $input['data'] ?? [];
        if (empty($data)) throw new Exception("Tidak ada data yang dikirim.");

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO mahasantri (nim, nama, gender, mustawa, alamat_kabupaten, status, created_at) VALUES (?, ?, ?, ?, ?, 'Aktif', NOW())");
        
        $insertedCount = 0;
        foreach ($data as $row) {
            if (empty($row['nama']) || empty($row['mustawa'])) continue;

            $nama = clean($row['nama']);
            $nim = !empty($row['nim']) ? clean($row['nim']) : generateNIM();
            $gender = clean($row['gender']);
            $mustawa = clean($row['mustawa']);
            $alamat = clean($row['alamat_kabupaten']);

            $stmt->execute([$nim, $nama, $gender, $mustawa, $alamat]);
            $insertedCount++;
        }
        $pdo->commit();
        
        logActivity($_SESSION['user_id'], "Mass Add: Added $insertedCount students.");
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan.', 'inserted' => $insertedCount]);

    // 2. BULK UPDATE
    } elseif ($action === 'bulk_update') {
        $data = $input['data'] ?? [];
        if (empty($data)) throw new Exception("Tidak ada perubahan yang dikirim.");

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE mahasantri SET nim=?, nama=?, gender=?, mustawa=?, status=?, updated_at=NOW() WHERE id=?");
        
        $updatedCount = 0;
        foreach ($data as $row) {
            if (empty($row['id']) || empty($row['nama'])) continue;

            $stmt->execute([
                clean($row['nim']),
                clean($row['nama']),
                clean($row['gender']),
                clean($row['mustawa']),
                clean($row['status']),
                $row['id']
            ]);
            $updatedCount++;
        }
        $pdo->commit();
        
        logActivity($_SESSION['user_id'], "Bulk Update: Updated $updatedCount students.");
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui.']);

    // 3. BULK DELETE
    } elseif ($action === 'bulk_delete') {
        $ids = $input['ids'] ?? [];
        if (empty($ids)) throw new Exception("Tidak ada data yang dipilih.");

        $pdo->beginTransaction();
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM mahasantri WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $deletedCount = $stmt->rowCount();
        $pdo->commit();

        logActivity($_SESSION['user_id'], "Bulk Delete: Deleted $deletedCount students.");
        echo json_encode(['status' => 'success', 'message' => "$deletedCount data berhasil dihapus."]);

    } else {
        throw new Exception("Action not supported.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Helper: Custom NIM Generator
function generateNIM() {
    return date('y') . date('m') . rand(10000, 99999);
}
