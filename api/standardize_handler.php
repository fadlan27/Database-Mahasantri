<?php
require_once '../functions.php';
requireLogin();

header('Content-Type: application/json');

try {
    // 1. Fetch data to check
    $stmt = $pdo->query("SELECT id, nama, asal, tempat_lahir, alamat_lengkap, nama_ayah, nama_ibu, asal_ppui, kelurahan, kecamatan, kabupaten, provinsi FROM mahasantri");
    $count = 0;
    
    // 2. Prepare Update Statement
    $updateStmt = $pdo->prepare("UPDATE mahasantri SET nama = ?, asal = ?, tempat_lahir = ?, alamat_lengkap = ?, nama_ayah = ?, nama_ibu = ?, asal_ppui = ?, kelurahan = ?, kecamatan = ?, kabupaten = ?, provinsi = ? WHERE id = ?");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // 3. Convert relevant fields
        $newName = standardizeText($row['nama']);
        $newAsal = standardizeText($row['asal']);
        $newTempat = standardizeText($row['tempat_lahir']);
        $newAlamat = standardizeAddress($row['alamat_lengkap']); // Use specialized address function
        $newAyah = standardizeText($row['nama_ayah']);
        $newIbu = standardizeText($row['nama_ibu']);
        $newAsalPpui = standardizeText($row['asal_ppui'] ?? '');
        $newKel = standardizeText($row['kelurahan'] ?? '');
        $newKec = standardizeText($row['kecamatan'] ?? '');
        $newKab = standardizeText($row['kabupaten'] ?? '');
        $newProv = standardizeText($row['provinsi'] ?? '');

        // 4. Update ONLY if changes detected
        if (
            $newName !== $row['nama'] || 
            $newAsal !== $row['asal'] || 
            $newTempat !== $row['tempat_lahir'] || 
            $newAlamat !== $row['alamat_lengkap'] || 
            $newAyah !== $row['nama_ayah'] || 
            $newIbu !== $row['nama_ibu'] ||
            $newAsalPpui !== ($row['asal_ppui'] ?? '') ||
            $newKel !== ($row['kelurahan'] ?? '') ||
            $newKec !== ($row['kecamatan'] ?? '') ||
            $newKab !== ($row['kabupaten'] ?? '') ||
            $newProv !== ($row['provinsi'] ?? '')
        ) {
             $updateStmt->execute([
                 $newName, $newAsal, $newTempat, $newAlamat, $newAyah, $newIbu, $newAsalPpui, 
                 $newKel, $newKec, $newKab, $newProv, 
                 $row['id']
             ]);
             $count++;
        }
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => "Berhasil memperbaiki ejaan pada $count data mahasantri.",
        'count' => $count
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => "Database Error: " . $e->getMessage()
    ]);
}
