<?php
require_once 'functions.php';
requireLogin();

// REUSE FILTER LOGIC from master_data.php
$where_clauses = [];
$params = [];
$title_filter = "Semua Mahasantri";

if (!empty($_GET['status'])) {
    $where_clauses[] = "status = ?";
    $params[] = clean($_GET['status']);
    $title_filter = "Status: " . htmlspecialchars($_GET['status']);
}
if (!empty($_GET['gender'])) {
    $where_clauses[] = "gender = ?";
    $params[] = clean($_GET['gender']);
    $title_filter = "Gender: " . htmlspecialchars($_GET['gender']);
}
if (!empty($_GET['angkatan']) && $_GET['angkatan'] !== 'all') {
    $where_clauses[] = "angkatan = ?";
    $params[] = clean($_GET['angkatan']);
    $title_filter = "Angkatan " . htmlspecialchars($_GET['angkatan']);
}
if (!empty($_GET['mustawa']) && $_GET['mustawa'] !== 'all') {
    $where_clauses[] = "mustawa = ?";
    $params[] = clean($_GET['mustawa']);
    $title_filter = "Kelas " . htmlspecialchars($_GET['mustawa']);
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$query = "SELECT * FROM mahasantri $where_sql ORDER BY angkatan DESC, nama ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Mahasantri</title>
    <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; margin: 0; padding: 20px; color: #000; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18pt; text-transform: uppercase; }
        .header h2 { margin: 5px 0; font-size: 14pt; }
        .header p { margin: 0; font-size: 10pt; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; vertical-align: top; font-size: 10pt; }
        th { background-color: #f0f0f0; text-align: center; }
        
        .signature { margin-top: 50px; float: right; width: 250px; text-align: center; }
        .signature p { margin-bottom: 60px; }
        
        @media print {
            @page { size: A4 landscape; margin: 1cm; }
            .no-print { display: none; }
        }
        
        .meta-info { margin-bottom: 10px; font-weight: bold; }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #000; color: #fff; cursor: pointer;">Cetak Laporan</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #ccc; cursor: pointer;">Tutup</button>
    </div>

    <div class="header">
        <h1>MA'HAD ALY JAMIAH ABAT</h1>
        <h2>Laporan Data Mahasantri</h2>
        <p>Jl. Contoh Alamat No. 123, Kota Hufaz, Indonesia 12345</p>
    </div>

    <div class="meta-info">
        Laporan: <?php echo $title_filter; ?><br>
        Dicetak Tanggal: <?php echo date('d F Y'); ?> (Oleh: <?php echo $_SESSION['full_name']; ?>)
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">NIM</th>
                <th width="20%">Nama Lengkap</th>
                <th width="5%">L/P</th>
                <th width="15%">TTL</th>
                <th width="15%">Asal & Alamat</th>
                <th width="5%">Angk.</th>
                <th width="5%">Kelas</th>
                <th width="10%">No. Wali</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($students as $idx => $s): ?>
            <tr>
                <td style="text-align: center;"><?php echo $idx + 1; ?></td>
                <td style="text-align: center;"><?php echo $s['nim']; ?></td>
                <td><?php echo $s['nama']; ?></td>
                <td style="text-align: center;"><?php echo $s['gender'] == 'Ikhwan' ? 'L' : 'P'; ?></td>
                <td><?php echo $s['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($s['tanggal_lahir'])); ?></td>
                <td>
                    <b><?php echo $s['asal']; ?></b><br>
                    <?php echo $s['alamat_lengkap']; ?>
                </td>
                <td style="text-align: center;"><?php echo $s['angkatan']; ?></td>
                <td style="text-align: center;"><?php echo $s['mustawa']; ?></td>
                <td><?php echo $s['wa_wali']; ?></td>
                <td style="text-align: center;"><?php echo $s['status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signature">
        <p>Diketahui Oleh,<br>Mudir Ma'had</p>
        <p style="text-decoration: underline; font-weight: bold;">Ustadz Fulan, Lc., M.Ag.</p>
        <span>NIY. 123456789</span>
    </div>

</body>
</html>
