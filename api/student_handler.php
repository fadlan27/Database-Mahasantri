<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/api/student_handler.php




// Prevent any unexpected output
ob_start();
error_reporting(E_ALL); // Temporarily enable all for log capture (will use try-catch for output)
ini_set('display_errors', 0); // Keep 0 for JSON safety

require_once '../functions.php';

// Check Login (If fails, it redirects, which we need to catch)
if (!isLoggedIn()) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir. Silakan login kembali.']);
    exit;
}

// SET HEADERS
header('Content-Type: application/json');

// --- CSRF VALIDATION ---
$headers = getallheaders();
$csrf_token = $headers['X-CSRF-TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token! Refresh halaman.']);
    exit;
}

// --- HANDLE DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    file_put_contents('debug_log.txt', "DELETE Request received\n", FILE_APPEND);
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id) {
        try {
            // ... (rest of logic)
            // Just quick fix to ensure it doesn't break syntax
            $stmt = $pdo->prepare("SELECT photo_path FROM mahasantri WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetchColumn();
            
            if ($photo && file_exists("../" . $photo)) {
                unlink("../" . $photo);
            }
            
            $stmt = $pdo->prepare("DELETE FROM mahasantri WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('Hapus Data', "Menghapus data ID: $id" . ($photo ? " (Foto dihapus)" : ""));

            ob_clean(); // Clean buffer before output
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
        } catch (PDOException $e) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
    } else {
        ob_clean();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
    exit;
}

// --- HANDLE POST (CREATE & UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $action = isset($_POST['action']) ? $_POST['action'] : 'create';
    
    try {
        $nim = clean($_POST['nim']);
        $nama = clean($_POST['nama']);

        // Construct filename base: Nama + Connector + Ayah
        $connector = (isset($_POST['gender']) && $_POST['gender'] === 'Akhowat') ? 'binti' : 'bin';
        $rawAyah = $_POST['nama_ayah'] ?? $_POST['ayah'] ?? 'fulan';
        $filenameBase = clean($nama) . '_' . $connector . '_' . clean($rawAyah);

        // PHOTO UPLOAD (Use Exact Name = true)
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            file_put_contents('debug_log.txt', "Attempting upload for $filenameBase...\n", FILE_APPEND);
            // Pass true for useExactName
            $uploaded = uploadPhoto($_FILES['photo'], $filenameBase, true);
            if ($uploaded) {
                $photo_path = $uploaded; 
                file_put_contents('debug_log.txt', "Upload success: $uploaded\n", FILE_APPEND);
            } else {
                file_put_contents('debug_log.txt', "Upload failed inside function.\n", FILE_APPEND);
            }
        }

        if ($action === 'create') {
            // Check Duplicate NIM
            $stmt = $pdo->prepare("SELECT id FROM mahasantri WHERE nim = ?");
            $stmt->execute([$nim]);
            if ($stmt->fetch()) {

                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'NIM sudah terdaftar!']);
                exit;
            }

            // Ensure columns exist (Auto-Migration should have run, but handle gracefully)
            $sql = "INSERT INTO mahasantri (nim, nama, gender, angkatan, mustawa, status, asal, asal_ppui, alamat_lengkap, provinsi, kabupaten, kecamatan, kelurahan, tanggal_lahir, nama_ayah, nama_ibu, wa_wali, photo_path, tempat_lahir) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                clean($_POST['nim']), 
                standardizeText($_POST['nama']), 
                clean($_POST['gender']), 
                (isset($_POST['angkatan']) && $_POST['angkatan'] !== '') ? clean($_POST['angkatan']) : '0000', 
                clean($_POST['mustawa']), 
                clean($_POST['status']) === 'Mengundurkan Diri' ? 'Drop Out' : clean($_POST['status']), // Map incompatible value
                clean($_POST['asal']), 
                clean($_POST['asal_ppui']), 
                standardizeText($_POST['alamat_lengkap']), 
                standardizeText($_POST['provinsi']),
                standardizeText($_POST['kabupaten']),
                standardizeText($_POST['kecamatan']),
                standardizeText($_POST['kelurahan']),
                !empty($_POST['tanggal_lahir']) ? clean($_POST['tanggal_lahir']) : NULL, // Fix Date
                standardizeText($_POST['nama_ayah'] ?? $_POST['ayah'] ?? ''), 
                standardizeText($_POST['nama_ibu'] ?? $_POST['ibu'] ?? ''), 
                clean($_POST['wa_wali']), 
                $photo_path,
                standardizeText($_POST['tempat_lahir'] ?? '')
            ];
            


            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute($params)) {
                $err = $stmt->errorInfo();

                throw new Exception("Insert Failed: " . $err[2]);
            }
            
            logActivity('Tambah Data', "Menambahkan santri baru: $nama ($nim)");

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil ditambahkan']);
            
        } elseif ($action === 'update') {
            $id = intval($_POST['id']);
            
            // Build Update Query
            $sql = "UPDATE mahasantri SET nim=?, nama=?, gender=?, angkatan=?, mustawa=?, status=?, asal=?, asal_ppui=?, alamat_lengkap=?, provinsi=?, kabupaten=?, kecamatan=?, kelurahan=?, tanggal_lahir=?, nama_ayah=?, nama_ibu=?, wa_wali=?, tempat_lahir=?";
            $params = [
                clean($_POST['nim']), 
                standardizeText($_POST['nama']), 
                clean($_POST['gender']), 
                (isset($_POST['angkatan']) && $_POST['angkatan'] !== '') ? clean($_POST['angkatan']) : '0000', 
                clean($_POST['mustawa']), 
                clean($_POST['status']) === 'Mengundurkan Diri' ? 'Drop Out' : clean($_POST['status']),
                clean($_POST['asal']), 
                $_POST['asal_ppui'], 
                standardizeText($_POST['alamat_lengkap']), 
                standardizeText($_POST['provinsi']), 
                standardizeText($_POST['kabupaten']), 
                standardizeText($_POST['kecamatan']), 
                standardizeText($_POST['kelurahan']),
                !empty($_POST['tanggal_lahir']) ? clean($_POST['tanggal_lahir']) : NULL, // Fix Date
                standardizeText($_POST['nama_ayah'] ?? $_POST['ayah'] ?? ''), 
                standardizeText($_POST['nama_ibu'] ?? $_POST['ibu'] ?? ''), 
                clean($_POST['wa_wali']), 
                standardizeText($_POST['tempat_lahir'] ?? '')
            ];
            
            if ($photo_path) {
                $sql .= ", photo_path=?";
                $params[] = $photo_path;
            }
            
            $sql .= " WHERE id=?";
            $params[] = $id;
            


            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute($params)) {
                 $err = $stmt->errorInfo();

                 throw new Exception("Update Failed: " . $err[2]);
            }
            
            logActivity('Update Data', "Mengubah data santri: $nama ($nim)");

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui']);
        }
        
    } catch (PDOException $e) {

        ob_clean();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    } catch (Throwable $ex) {

        ob_clean();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine()]);
    }
    exit;
}
// End of file (no closing tag)

