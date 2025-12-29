<?php
require_once 'functions.php';
requireLogin();
requireRole('wali');

$santri_id = $_SESSION['linked_id'] ?? null;

$santri_id = $_SESSION['linked_id'] ?? null;
$santri = null;
$error_message = "";

if (!$santri_id) {
    $error_message = "Akun ini belum terhubung dengan data santri manapun.";
} else {
    // Fetch Santri Data
    $stmt = $pdo->prepare("SELECT * FROM mahasantri WHERE id = ? OR nim = ?");
    $stmt->execute([$santri_id, $santri_id]);
    $santri = $stmt->fetch();

    if (!$santri) {
        $error_message = "Data santri tidak ditemukan (ID: " . htmlspecialchars($santri_id) . "). Hubungi Admin.";
    }
}

if ($error_message) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Jamiah Abat</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md text-center">
            <div class="w-20 h-20 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-link-slash text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Akses Terbatas</h2>
            <p class="text-gray-600 mb-8"><?php echo $error_message; ?></p>
            <div class="flex gap-4 justify-center">
                <a href="logout.php" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-xl font-bold hover:bg-gray-300 transition">Logout</a>
                <a href="https://wa.me/62812345678" target="_blank" class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-emerald-700 transition"><i class="fa-brands fa-whatsapp"></i> Hubungi Admin</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Fetch Violations
$stmt = $pdo->prepare("SELECT * FROM pelanggaran WHERE student_id = ? ORDER BY violation_date DESC");
$stmt->execute([$santri['id']]); // Use actual ID from fetched santri
$violations = $stmt->fetchAll();

$page_title = "Profil Santri: " . $santri['nama'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wali Santri Dashboard - Jamiah Abat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: { colors: { primary: '#064E3B', secondary: '#059669' }, fontFamily: { sans: ['Inter', 'sans-serif'] } }
            }
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased h-screen overflow-hidden flex dark:bg-slate-900 dark:text-slate-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-slate-900 p-6">
            
            <!-- HEADER INFO -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl shadow-lg p-6 mb-6 text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
                
                <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                    <div class="w-24 h-24 rounded-full border-4 border-white/30 overflow-hidden shadow-xl bg-white flex items-center justify-center text-slate-300">
                        <?php if($santri['foto']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($santri['foto']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-solid fa-user text-4xl"></i>
                        <?php endif; ?>
                    </div>
                    <div class="text-center md:text-left flex-1">
                        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($santri['nama']); ?></h1>
                        <p class="opacity-90 mt-1 flex items-center justify-center md:justify-start gap-3">
                            <span class="bg-white/20 px-2 py-0.5 rounded text-sm"><i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($santri['nim']); ?></span>
                            <span class="bg-white/20 px-2 py-0.5 rounded text-sm"><i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($santri['mustawa']); ?></span>
                        </p>
                    </div>
                    <div class="text-right hidden md:block">
                        <span class="block text-4xl font-bold opacity-20">WALI SANTRI</span>
                    </div>
                </div>
            </div>

            <!-- INFO CARDS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                
                <!-- PERSONAL INFO -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6 lg:col-span-2">
                    <h3 class="font-bold text-lg mb-4 text-slate-700 dark:text-white border-b pb-2 dark:border-slate-700">Biodata Santri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        
                        <div>
                            <p class="text-slate-500 text-xs">Tempat, Tanggal Lahir</p>
                            <p class="font-medium"><?php echo htmlspecialchars($santri['tempat_lahir']) . ', ' . formatDateId($santri['tanggal_lahir']); ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs">Jenis Kelamin</p>
                            <p class="font-medium"><?php echo $santri['gender']; ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs">Alamat Domisili</p>
                            <p class="font-medium"><?php echo htmlspecialchars($santri['alamat']); ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500 text-xs">Kota Asal / PPUI</p>
                            <p class="font-medium"><?php echo htmlspecialchars($santri['kabupaten']); ?> / <?php echo htmlspecialchars($santri['asal_ppui']); ?></p>
                        </div>
                         <div>
                            <p class="text-slate-500 text-xs">Nama Ayah</p>
                            <p class="font-medium"><?php echo htmlspecialchars($santri['nama_ayah']); ?></p>
                        </div>
                         <div>
                            <p class="text-slate-500 text-xs">Nama Ibu</p>
                            <p class="font-medium"><?php echo htmlspecialchars($santri['nama_ibu']); ?></p>
                        </div>

                    </div>
                </div>

                <!-- ACADEMIC / STATUS -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-lg mb-4 text-slate-700 dark:text-white border-b pb-2 dark:border-slate-700">Status Akademik</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <span class="text-blue-600 dark:text-blue-300 font-medium">Status</span>
                            <span class="px-2 py-1 bg-blue-200 text-blue-800 text-xs rounded-full font-bold"><?php echo $santri['status']; ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg">
                            <span class="text-emerald-600 dark:text-emerald-300 font-medium">Hafalan Quran</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">5 Juz <span class="text-xs font-normal">(Target: 30)</span></span>
                        </div>
                        
                        <div class="flex justify-between items-center bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg">
                            <span class="text-amber-600 dark:text-amber-300 font-medium">Rata-rata Nilai</span>
                            <span class="font-bold text-amber-700 dark:text-amber-400">85.4 (Jayyid)</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- VIOLATION HISTORY & BILLING -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- VIOLATIONS -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-lg mb-4 text-slate-700 dark:text-white border-b pb-2 dark:border-slate-700 flex items-center justify-between">
                        <span>Catatan Kedisiplinan</span>
                        <?php if(count($violations)==0): ?>
                            <span class="text-xs bg-emerald-100 text-emerald-600 px-2 py-1 rounded">Bersih</span>
                        <?php else: ?>
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded"><?php echo count($violations); ?> Catatan</span>
                        <?php endif; ?>
                    </h3>
                    
                    <div class="max-h-64 overflow-y-auto space-y-3 custom-scrollbar">
                        <?php if(count($violations) > 0): ?>
                            <?php foreach($violations as $v): ?>
                                <div class="p-3 border rounded-lg border-slate-100 dark:border-slate-700 flex gap-3 items-start">
                                    <div class="mt-1 w-2 h-2 rounded-full <?php echo $v['severity'] == 'Berat' ? 'bg-red-500' : ($v['severity'] == 'Sedang' ? 'bg-orange-500' : 'bg-yellow-500'); ?>"></div>
                                    <div>
                                        <p class="font-bold text-sm text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($v['violation_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo formatDateId($v['violation_date']); ?> • Poin: <?php echo $v['points']; ?></p>
                                        <?php if($v['punishment']): ?>
                                            <p class="text-xs text-red-500 mt-1 italic">Hukuman: <?php echo htmlspecialchars($v['punishment']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-slate-400">
                                <i class="fa-solid fa-shield-halved text-4xl mb-2 opacity-30"></i>
                                <p>Alhamdulillah, tidak ada catatan pelanggaran.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BILLING MOCKUP -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/5 rounded-bl-full -mr-4 -mt-4"></div>
                    <h3 class="font-bold text-lg mb-4 text-slate-700 dark:text-white border-b pb-2 dark:border-slate-700">Status Keuangan</h3>
                    
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-xl border border-indigo-100 dark:border-indigo-800 mb-4">
                        <p class="text-xs text-indigo-600 dark:text-indigo-300 font-bold uppercase">Tagihan Bulan Ini</p>
                        <h2 class="text-2xl font-bold text-indigo-700 dark:text-indigo-400 mt-1">Rp 0,-</h2>
                        <p class="text-xs text-indigo-500 mt-1"><i class="fa-solid fa-check-circle"></i> Lunas (Syahriyah)</p>
                    </div>

                    <div class="space-y-2 text-sm">
                        <p class="font-bold text-slate-600 dark:text-slate-300 text-xs uppercase mb-2">Riwayat Pembayaran Terakhir</p>
                        
                        <div class="flex justify-between items-center p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded transition">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">Syahriyah Maret 2025</p>
                                <p class="text-[10px] text-slate-400">01 Mar 2025</p>
                            </div>
                            <span class="text-emerald-600 font-bold text-xs">Lunas</span>
                        </div>
                        <div class="flex justify-between items-center p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded transition">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">Uang Pangkal (Cicilan 3)</p>
                                <p class="text-[10px] text-slate-400">15 Feb 2025</p>
                            </div>
                            <span class="text-emerald-600 font-bold text-xs">Lunas</span>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</body>
</html>
