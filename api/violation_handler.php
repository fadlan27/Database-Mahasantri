<?php
// api/violation_handler.php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

// --- DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Determine ID from URL: api/violation_handler.php?id=123
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Support parsed body for DELETE if transmitted that way (rare in simple fetch/ajax)
    // But usually DELETE is passed via URL query parameter or a POST with _method=DELETE
    
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM pelanggaran WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Data pelanggaran berhasil dihapus']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    }
    exit;
}

// --- CREATE & UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? 'create'; // create | update
    
    // Common Fields
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $jenis = $_POST['jenis'] ?? 'Ringan';
    $tingkat_sanksi = $_POST['tingkat_sanksi'] ?? 'Normal';
    $deskripsi = clean($_POST['deskripsi'] ?? '');
    $sanksi = clean($_POST['sanksi'] ?? '');
    
    try {
        if ($action === 'create') {
            $mahasantri_id = $_POST['mahasantri_id'] ?? 0;
            if (!$mahasantri_id) throw new Exception("Mahasantri ID diperlukan");

            // Look up NIM from ID
            $stmtM = $pdo->prepare("SELECT nim FROM mahasantri WHERE id = ?");
            $stmtM->execute([$mahasantri_id]);
            $sData = $stmtM->fetch(PDO::FETCH_ASSOC);
            $nim = $sData ? $sData['nim'] : '';

            $stmt = $pdo->prepare("INSERT INTO pelanggaran (mahasantri_id, nim, tanggal, jenis, tingkat_sanksi, deskripsi, sanksi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$mahasantri_id, $nim, $tanggal, $jenis, $tingkat_sanksi, $deskripsi, $sanksi]);
            
            // Auto-update student status ONLY if explicitly DO
            if ($tingkat_sanksi == 'DO') {
                $upd = $pdo->prepare("UPDATE mahasantri SET status = 'Dikeluarkan' WHERE id = ?");
                $upd->execute([$mahasantri_id]);
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Pelanggaran berhasil dicatat']);

        } elseif ($action === 'update') {
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Pelanggaran diperlukan untuk edit");

            $stmt = $pdo->prepare("UPDATE pelanggaran SET tanggal = ?, jenis = ?, tingkat_sanksi = ?, deskripsi = ?, sanksi = ? WHERE id = ?");
            $stmt->execute([$tanggal, $jenis, $tingkat_sanksi, $deskripsi, $sanksi, $id]);
            
            // If updated to DO, enforce status change
            if ($tingkat_sanksi == 'DO') {
                $upd = $pdo->prepare("UPDATE mahasantri SET status = 'Dikeluarkan' WHERE id = (SELECT mahasantri_id FROM pelanggaran WHERE id = ?)");
                $upd->execute([$id]);
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Data pelanggaran diperbarui']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
