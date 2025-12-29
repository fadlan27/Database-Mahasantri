<?php
/**
 * Smart Export Handler with CSV and XLSX Support
 * Uses native PHP for robust export without heavy dependencies
 */
require_once '../functions.php';
requireLogin();

$format = $_GET['format'] ?? 'csv';

try {
    // Build Query (with filters)
    $sql = "SELECT * FROM mahasantri WHERE 1=1";
    $params = [];

    if (!empty($_GET['gender'])) {
        $sql .= " AND gender = :gender";
        $params[':gender'] = $_GET['gender'];
    }
    if (!empty($_GET['mustawa'])) {
        $sql .= " AND mustawa LIKE :mustawa";
        $params[':mustawa'] = "%" . $_GET['mustawa'] . "%";
    }
    if (!empty($_GET['status'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }
    if (!empty($_GET['angkatan'])) {
         $sql .= " AND angkatan = :angkatan";
         $params[':angkatan'] = $_GET['angkatan'];
    }
    if (!empty($_GET['ppui'])) {
        $sql .= " AND asal_ppui = :ppui";
        $params[':ppui'] = $_GET['ppui'];
    }
    if (!empty($_GET['provinsi'])) {
        $sql .= " AND provinsi = :provinsi";
        $params[':provinsi'] = $_GET['provinsi'];
    }
    if (!empty($_GET['kabupaten'])) {
        $sql .= " AND kabupaten = :kabupaten";
        $params[':kabupaten'] = $_GET['kabupaten'];
    }

    $sql .= " ORDER BY nim ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Define Headers
    $headers = [
        'No', 'NIM', 'Nama Lengkap', 'Gender', 'Tempat Lahir', 'Tanggal Lahir',
        'Angkatan', 'Mustawa', 'Status', 'Provinsi', 'Kabupaten', 'Kecamatan',
        'Kelurahan', 'Asal PPUI', 'Alamat Lengkap', 'Nama Ayah', 'Nama Ibu', 'WA Wali'
    ];

    // Prepare Rows
    $rows = [];
    foreach ($data as $i => $row) {
        $rows[] = [
            $i + 1,
            $row['nim'] ?? '',
            $row['nama'] ?? '',
            $row['gender'] ?? '',
            $row['tempat_lahir'] ?? '',
            $row['tanggal_lahir'] ?? '',
            $row['angkatan'] ?? '',
            $row['mustawa'] ?? '',
            $row['status'] ?? '',
            $row['provinsi'] ?? '',
            $row['kabupaten'] ?? '',
            $row['kecamatan'] ?? '',
            $row['kelurahan'] ?? '',
            $row['asal_ppui'] ?? '',
            $row['alamat_lengkap'] ?? '',
            $row['nama_ayah'] ?? '',
            $row['nama_ibu'] ?? '',
            $row['wa_wali'] ?? ''
        ];
    }

    $filename = 'Data_Mahasantri_' . date('Y-m-d_H-i');

    if ($format === 'xlsx') {
        // --- XLSX Export using SimpleXLSXGen ---
        require_once 'SimpleXLSXGen.php';
        
        $exportData = array_merge([$headers], $rows);
        $xlsx = Shuchkin\SimpleXLSXGen::fromArray($exportData);
        $xlsx->downloadAs($filename . '.xlsx');
        
    } else {
        // --- CSV Export ---
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        // UTF-8 BOM for Excel compatibility
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $r) {
            fputcsv($output, $r);
        }
        fclose($output);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
