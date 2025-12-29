<?php
// master_do.php
require_once 'functions.php';
requireLogin();

// Fetch Data Keluar/DO
// Note: We use a broad IN clause to catch all non-active variations
$sql = "SELECT * FROM mahasantri WHERE status IN ('Dikeluarkan', 'Drop Out', 'Keluar', 'Mengundurkan Diri')";
$sql .= " ORDER BY nim ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll();

// Fetch All Students for Violation Dropdown
$all_students = $pdo->query("SELECT id, nama, nim FROM mahasantri ORDER BY nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Segoe UI"', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        mica: '#f3f3f3',
                        glass: 'rgba(255, 255, 255, 0.7)',
                        glassBorder: 'rgba(255, 255, 255, 0.5)',
                        accent: '#991b1b', // Red-800
                        accentHover: '#7f1d1d',
                        surface: '#ffffff',
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(153, 27, 27, 0.07)',
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid transparent; background-clip: content-box; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; border: 2px solid transparent; background-clip: content-box; }

        body {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); /* Red Gradient */
            background-attachment: fixed;
        }
        .dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .dark .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen overflow-hidden flex dark:text-slate-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-2">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-red-900 dark:text-white mb-1">Arsip DO / Keluar</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Menampilkan <?php echo count($students); ?> santri Non-Aktif.</p>
                    </div>
                </div>

                <!-- Filter / Search -->
                 <div class="glass-panel rounded-2xl p-4 shadow-glass mb-6 sticky top-0 z-30">
                    <div class="flex w-full items-center">
                        <div class="relative w-full">
                            <input type="text" id="searchInput" onkeyup="filterCards()" placeholder="Cari Nama, NIM..." class="w-full pl-10 pr-4 py-3 bg-white/50 dark:bg-slate-700/50 border border-transparent hover:bg-white hover:border-red-200 hover:shadow-sm focus:bg-white focus:ring-2 focus:ring-accent/20 focus:border-accent rounded-xl outline-none transition-all">
                            <i class="fa-solid fa-search absolute left-3 top-3.5 text-red-400"></i>
                         </div>
                    </div>
                </div>

                <!-- Grid Cards -->
                <?php if(count($students) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-20">
                        <?php foreach($students as $s): 
                            $photo = $s['photo_path'] && file_exists($s['photo_path']) ? $s['photo_path'] : '';
                            $statusBadge = 'bg-red-500/20 text-red-800 border-red-400/30 dark:text-red-200';
                        ?>
                        <!-- Card Item -->
                        <div class="glass-panel rounded-2xl shadow-glass hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group h-full relative border border-white/60 bg-red-50/30 searchable-item"
                             data-search="<?php echo strtolower($s['nama'] . ' ' . $s['nim'] . ' ' . $s['asal']); ?>">
                            
                            <!-- Top Banner / Photo -->
                            <div class="h-28 bg-gradient-to-tr from-red-700 to-red-500 relative">
                                <div class="absolute top-0 left-0 w-full h-full bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20"></div>
                                 <div class="absolute top-3 right-3">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider backdrop-blur-md border <?php echo $statusBadge; ?>">
                                        <?php echo strtoupper($s['status']); ?>
                                    </span>
                                 </div>
                            </div>
                            
                            <!-- Avatar Container -->
                            <div class="px-5 -mt-12 flex justify-between items-end relative z-10">
                                <div class="w-24 h-24 rounded-2xl border-4 border-white/80 shadow-lg overflow-hidden bg-white grayscale dark:border-slate-800 dark:bg-slate-700">
                                    <?php if($photo): ?>
                                        <img loading="lazy" src="<?php echo $photo; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300 dark:bg-slate-600 dark:text-slate-400">
                                            <i class="fa-solid fa-user-xmark text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Quick Actions -->
                                <div class="flex gap-2 mb-2">
                                     <button onclick='viewStudent(<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="w-9 h-9 flex items-center justify-center rounded-xl bg-white/70 hover:bg-white text-blue-500 shadow-sm border border-slate-100 transition-all active:scale-95 tooltip" title="Lihat Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                     <button onclick='editStudent(<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="w-9 h-9 flex items-center justify-center rounded-xl bg-white/70 hover:bg-white text-amber-500 shadow-sm border border-slate-100 transition-all active:scale-95 tooltip" title="Edit Data">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-5 flex-1 flex flex-col gap-3">
                                <div>
                                    <h3 class="font-bold text-lg text-slate-700 leading-tight mb-1 truncate dark:text-white" title="<?php echo $s['nama']; ?>"><?php echo formatNameBin($s['nama'], $s['gender'], $s['nama_ayah']); ?></h3>
                                    <div class="text-[10px] text-slate-500 mt-1">
                                        Nomor Induk Mahasantri: <span class="font-mono font-bold text-red-700 dark:text-red-400"><?php echo $s['nim']; ?></span>
                                    </div>
                                </div>

                                <hr class="border-red-100/50 dark:border-slate-700">

                                <!-- Details Grid -->
                                <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                                    
                                    <!-- Angkatan -->
                                    <div class="flex justify-between items-center bg-red-50/50 dark:bg-slate-700/50 p-2 rounded-lg">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-calendar-xmark text-red-500"></i>
                                            <span class="font-bold text-slate-700 dark:text-white">Non-Aktif</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-400">Angkatan</span>
                                            <span class="font-medium"><?php echo $s['angkatan']; ?></span>
                                        </div>
                                    </div>

                                    <!-- Asal (Region) -->
                                    <?php 
                                        $regionParts = array_filter([$s['kabupaten'], $s['provinsi']]);
                                        $regionDisplay = !empty($regionParts) ? implode(', ', $regionParts) : $s['asal'];
                                        
                                        $streetParts = array_filter([$s['alamat_lengkap'], 
                                            $s['kelurahan'] ? 'Kel. '.$s['kelurahan'] : '', 
                                            $s['kecamatan'] ? 'Kec. '.$s['kecamatan'] : ''
                                        ]);
                                        $streetDisplay = !empty($streetParts) ? implode(', ', $streetParts) : ($s['alamat_lengkap'] ?? '-');
                                    ?>
                                    <div class="flex gap-2 items-start" title="Wilayah Asal">
                                        <i class="fa-solid fa-location-dot w-4 text-center text-red-500 mt-0.5"></i>
                                        <div class="flex flex-col leading-tight overflow-hidden">
                                            <span class="truncate font-medium text-slate-700 dark:text-slate-200"><?php echo $regionDisplay; ?></span>
                                            <span class="text-[10px] text-slate-400 truncate"><?php echo $s['asal_ppui']; ?></span>
                                        </div>
                                    </div>

                                    <!-- Parent & WA -->
                                    <div class="flex flex-col gap-1">
                                        <div class="flex gap-2 items-center" title="Nama Ibu">
                                            <i class="fa-solid fa-person-breastfeeding w-4 text-center text-slate-400"></i>
                                            <span class="truncate">Ibu: <span class="font-medium text-slate-700 dark:text-slate-200"><?php echo $s['nama_ibu'] ?? $s['ibu'] ?? '-'; ?></span></span>
                                        </div>
                                        <?php if($s['wa_wali']): ?>
                                        <div class="flex gap-2 items-center text-[10px] ml-6">
                                            <a href="https://wa.me/<?php echo formatWA($s['wa_wali']); ?>" target="_blank" class="text-emerald-600 hover:text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded flex items-center gap-1 transition-colors">
                                                <i class="fa-brands fa-whatsapp"></i> <?php echo formatPhoneNumberDisplay($s['wa_wali']); ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                     <!-- Detailed Address -->
                                    <div class="flex gap-2 items-start group/addr" title="Alamat Lengkap">
                                        <i class="fa-solid fa-map-pin w-4 text-center text-slate-400 mt-0.5"></i>
                                        <span class="leading-tight text-[11px] text-slate-500 line-clamp-2">
                                            <?php echo $streetDisplay; ?>
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-96 text-slate-400">
                        <div class="bg-white/50 p-6 rounded-full shadow-sm mb-4 backdrop-blur-sm">
                            <i class="fa-solid fa-folder-open text-6xl text-red-200"></i>
                        </div>
                        <p class="font-medium text-lg">Tidak ada data DO/Keluar.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Modals (Shared) -->
    <?php include 'includes/student_modals.php'; ?>

    <!-- Client-side Filter Script -->
    <script>
        function filterCards() {
            let input = document.getElementById('searchInput');
            let filter = input.value.toLowerCase();
            let cards = document.querySelectorAll('.searchable-item');

            cards.forEach(card => {
                let text = card.getAttribute('data-search');
                if (text.includes(filter)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
