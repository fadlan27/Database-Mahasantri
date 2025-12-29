<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/import.php
require_once 'config/database.php';
require_once 'functions.php';
requireLogin();

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    $filename = $_FILES['file']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (strtolower($ext) !== 'csv') {
        $message = "Maaf, hanya format .csv yang didukung saat ini.";
        $status = 'error';
    } else {
        try {
            $handle = fopen($file, "r");
            
            // Skip Header
            fgetcsv($handle); 
            
            $pdo->beginTransaction();
            // Updated Query for new fields
            $stmt = $pdo->prepare("INSERT INTO mahasantri (nim, nama, gender, tanggal_lahir, angkatan, mustawa, status, asal, asal_ppui, alamat_lengkap, ayah, ibu, wa_wali) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE nama=VALUES(nama), status=VALUES(status), alamat_lengkap=VALUES(alamat_lengkap), asal_ppui=VALUES(asal_ppui)");
            
            $count = 0;
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Expected CSV: 0=No, 1=NIM, 2=Nama, 3=Gender, 4=TTL, 5=Angkatan, 6=Mustawa, 7=Status, 8=Asal, 9=Asal_PPUI, 10=Alamat, 11=Ayah, 12=Ibu, 13=WA
                
                $nim = str_replace("'", "", $row[1] ?? '');
                $nama = $row[2] ?? '';
                
                // Handle Date (YYYY-MM-DD expected)
                $tgl = $row[4] ?? null;
                if(!strtotime($tgl)) $tgl = null; 

                if ($nim && $nama) {
                     $stmt->execute([
                        $nim, $nama, 
                        $row[3] ?? 'Ikhwan',
                        $tgl,
                        $row[5] ?? date('Y'), 
                        $row[6] ?? 'Awwal', 
                        $row[7] ?? 'Aktif', 
                        $row[8] ?? '', 
                        $row[9] ?? '', // Asal PPUI
                        $row[10] ?? '', // Alamat
                        $row[11] ?? '', 
                        $row[12] ?? '',
                        $row[13] ?? ''
                    ]);
                    $count++;
                }
            }
            
            fclose($handle);
            $pdo->commit();
            $message = "Berhasil mengimport $count data mahasantri!";
            $status = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $status = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data CSV - Jamiah Abat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Import Data CSV (Versi Baru V3)</h2>
        
        <?php if($message): ?>
            <div class="p-4 mb-4 rounded <?php echo $status == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <i class="fa-solid fa-file-csv text-4xl text-green-600 mb-2"></i>
                <p class="text-sm text-gray-500 mb-2">Upload file <b>.CSV</b></p>
                <div class="bg-blue-50 text-blue-700 p-2 rounded text-xs text-left">
                    <b>Format Baru V3:</b><br>
                    No, NIM, Nama, Gender, TTL, Angkatan, Mustawa, Status, Asal, <b>Asal PPUI</b>, Alamat, Ayah, Ibu, WA
                </div>
                <input type="file" name="file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 mt-4">
            </div>
            
            <div class="flex justify-between items-center">
                <a href="master_data.php" class="text-gray-500 hover:text-gray-700">Kembali</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Upload & Import</button>
            </div>
        </form>
    </div>
</body>
</html>
