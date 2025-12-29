<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/export.php
require_once 'config/database.php';
require_once 'functions.php';
requireLogin();

// Filter Logic matches Master Data
$where_clauses = [];
$params = [];

if (!empty($_GET['q'])) {
    $q = "%" . clean($_GET['q']) . "%";
    $where_clauses[] = "(nama LIKE ? OR nim LIKE ? OR asal LIKE ? OR ayah LIKE ?)";
    $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q;
}
if (!empty($_GET['status'])) {
    $where_clauses[] = "status = ?";
    $params[] = clean($_GET['status']);
}
if (!empty($_GET['gender'])) {
    $where_clauses[] = "gender = ?";
    $params[] = clean($_GET['gender']);
}
if (!empty($_GET['angkatan']) && $_GET['angkatan'] !== 'all') {
    $where_clauses[] = "angkatan = ?";
    $params[] = clean($_GET['angkatan']);
}
if (!empty($_GET['mustawa']) && $_GET['mustawa'] !== 'all') {
    $where_clauses[] = "mustawa = ?";
    $params[] = clean($_GET['mustawa']);
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Filename
$filename = "Data_Mahasantri_" . date('Y-m-d') . ".csv";

// Headers match Database Columns (UPDATED for Asal PPUI)
$headers = ['No', 'NIM', 'Nama', 'Gender', 'TTL', 'Angkatan', 'Mustawa', 'Status', 'Asal_Daerah', 'Asal_PPUI', 'Alamat', 'Ayah', 'Ibu', 'WA_Wali'];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
fputcsv($output, $headers);

$stmt = $pdo->prepare("SELECT * FROM mahasantri $where_sql ORDER BY angkatan DESC, nama ASC");
$stmt->execute($params);

$no = 1;
while ($row = $stmt->fetch()) {
    $ttl = ($row['tanggal_lahir'] && $row['tanggal_lahir'] != '0000-00-00') ? $row['tanggal_lahir'] : '';
    
    $data = [
        $no++,
        "'" . $row['nim'],
        $row['nama'],
        $row['gender'],
        $ttl,
        $row['angkatan'],
        $row['mustawa'],
        $row['status'],
        $row['asal'],
        $row['asal_ppui'], // New Field
        $row['alamat_lengkap'],
        $row['ayah'],
        $row['ibu'],
        $row['wa_wali']
    ];
    fputcsv($output, $data);
}

fclose($output);
exit;
?>
