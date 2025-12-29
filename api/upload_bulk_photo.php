<?php
require_once '../functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // ACTION 1: AUTO MATCH & BULK UPLOAD
    if ($action === 'auto_match') {
        $files = $_FILES['files'];
        $count = count($files['name']);
        $successCount = 0;
        $logs = [];

        $overwriteMode = $_POST['overwrite_mode'] ?? 'overwrite';

        for ($i = 0; $i < $count; $i++) {
            $name = $files['name'][$i];
            $tmpName = $files['tmp_name'][$i];
            
            // Parse Filename: "Fulan_bin_Fulan.jpg"
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            $searchName = str_replace(['_', '-'], ' ', $baseName);
            
            // Split by "bin" or "binti"
            $parts = preg_split('/\s+(bin|binti)\s+/i', $searchName);
            
            $santri = null;

            if (count($parts) >= 2) {
                // High Confidence Match: Nama + Ayah
                $namaSantri = trim($parts[0]);
                $namaAyah = trim($parts[1]);
                
                $stmt = $pdo->prepare("SELECT * FROM mahasantri WHERE nama LIKE ? AND (nama_ayah LIKE ? OR nama_ayah IS NULL OR nama_ayah = '') LIMIT 1");
                $stmt->execute([$namaSantri, "%$namaAyah%"]);
                $santri = $stmt->fetch();
            } else {
                // Fallback: Try Exact Name Match
                $stmt = $pdo->prepare("SELECT * FROM mahasantri WHERE nama LIKE ? LIMIT 1");
                $stmt->execute([$searchName]);
                $santri = $stmt->fetch();
            }

            if ($santri) {
                // OVERWRITE CHECK
                if ($overwriteMode === 'skip' && !empty($santri['photo_path'])) {
                    $physicalPath = __DIR__ . '/../' . $santri['photo_path'];
                    if (file_exists($physicalPath)) {
                         $logs[] = "Skipped (Existing): $name -> {$santri['nama']}";
                         continue; 
                    }
                }

                // Match Found! Construct Deterministic Name
                $connector = ($santri['gender'] === 'Akhowat') ? 'binti' : 'bin';
                $ayahSafe = $santri['nama_ayah'] ? $santri['nama_ayah'] : 'fulan';
                
                $finalName = clean($santri['nama']) . '_' . $connector . '_' . clean($ayahSafe);
                
                // MOCK single file structure for uploadPhoto
                $singleFile = [
                    'name' => $name, // Ext is extracted inside
                    'type' => $files['type'][$i],
                    'tmp_name' => $tmpName,
                    'error' => 0,
                    'size' => $files['size'][$i]
                ];

                // Upload with Exact Name = TRUE
                $uploaded = uploadPhoto($singleFile, $finalName, true);
                
                if ($uploaded) {
                    // Update DB
                    $pdo->prepare("UPDATE mahasantri SET photo_path = ? WHERE id = ?")
                        ->execute([$uploaded, $santri['id']]);
                    
                    $successCount++;
                    $logs[] = "Matched: $name -> {$santri['nama']}";
                } else {
                    $logs[] = "Failed to upload: $name";
                }
            } else {
                $logs[] = "No match found for: $name";
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => "Berhasil memproses $successCount dari $count foto.",
            'logs' => $logs
        ]);

    // ACTION 2: SINGLE UPLOAD (From Grid)
    } elseif ($action === 'single_upload') {
        $id = $_POST['id'] ?? 0;
        if (!$id || empty($_FILES['photo'])) throw new Exception("Invalid Data");

        $stmt = $pdo->prepare("SELECT * FROM mahasantri WHERE id = ?");
        $stmt->execute([$id]);
        $santri = $stmt->fetch();

        if (!$santri) throw new Exception("Santri not found");

        // Construct Name
        $connector = ($santri['gender'] === 'Akhowat') ? 'binti' : 'bin';
        $ayahSafe = $santri['nama_ayah'] ? $santri['nama_ayah'] : 'fulan';
        $finalName = clean($santri['nama']) . '_' . $connector . '_' . clean($ayahSafe);

        $uploaded = uploadPhoto($_FILES['photo'], $finalName, true);

        if ($uploaded) {
            $pdo->prepare("UPDATE mahasantri SET photo_path = ? WHERE id = ?")
                ->execute([$uploaded, $id]);
            
            echo json_encode(['status' => 'success', 'path' => $uploaded]);
        } else {
            throw new Exception("Upload failed");
        }

    } else {
        throw new Exception("Unknown Action");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
