<?php
// Script to download a blank CSV template
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="template_import_mahasantri.csv"');

// Headers matching DB columns for easier mapping
$headers = [
    'NIM', 
    'Nama Lengkap', 
    'Gender', 
    'Tempat Lahir', 
    'Tanggal Lahir', 
    'Angkatan', 
    'Mustawa', 
    'Status', 
    'Asal PPUI', 
    'Alamat Lengkap', 
    'Provinsi',
    'Kabupaten',
    'Kecamatan',
    'Kelurahan',
    'Nama Ayah', 
    'Nama Ibu', 
    'Nomor Wali'
];

$output = fopen('php://output', 'w');
fputcsv($output, $headers);

// Add one example row
fputcsv($output, [
    '2023001', 
    'Contoh Nama Santri', 
    'Ikhwan', 
    'Jakarta', 
    '2005-01-31', 
    '2023', 
    'Awwal', 
    'Aktif', 
    'PPUI Jakarta', 
    'Jl. Contoh No. 123', 
    'DKI Jakarta',
    'Jakarta Selatan',
    'Cilandak',
    'Cilandak Barat',
    'Nama Ayah', 
    'Nama Ibu', 
    '08123456789'
]);

fclose($output);
exit;
