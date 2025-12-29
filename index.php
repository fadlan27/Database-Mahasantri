<?php
require_once 'functions.php';
require_once 'includes/class_agenda.php';
requireLogin();

// Redirect Wali to their specific dashboard
if (isWali()) {
    header("Location: profile_santri.php");
    exit;
}

$page_title = 'Dashboard';

// Fetch Statistics
try {
    // Total Active
    $stmt = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE status = 'Aktif'");
    $total_aktif = $stmt->fetchColumn();

    // Total Ever (All Data)
    $stmt = $pdo->query("SELECT COUNT(*) FROM mahasantri");
    $total_ever = $stmt->fetchColumn();

    // Total Lulus
    $stmt = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE status = 'Lulus'");
    $total_lulus = $stmt->fetchColumn();

    // Total Ikhwan
    $stmt = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE gender = 'Ikhwan' AND status='Aktif'");
    $total_ikhwan = $stmt->fetchColumn();

    // Total Akhowat
    $stmt = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE gender = 'Akhowat' AND status='Aktif'");
    $total_akhowat = $stmt->fetchColumn();

    // Total Keluar/DO
    $stmt = $pdo->query("SELECT COUNT(*) FROM mahasantri WHERE status IN ('Dikeluarkan', 'Drop Out', 'Keluar', 'Mengundurkan Diri')");
    $total_keluar = $stmt->fetchColumn();

    // Total Pelanggaran (Count of Violation Records)
    $stmt = $pdo->query("SELECT COUNT(*) FROM pelanggaran");
    $total_pelanggaran = $stmt->fetchColumn();

    // Chart Data: Angkatan
    $stmt = $pdo->query("SELECT angkatan, COUNT(*) as count FROM mahasantri GROUP BY angkatan ORDER BY angkatan DESC");
    $angkatan_data = $stmt->fetchAll();
    
    $angkatan_labels = []; $angkatan_counts = [];
    foreach ($angkatan_data as $row) {
        $angkatan_labels[] = $row['angkatan'];
        $angkatan_counts[] = $row['count'];
    }

    // Chart Data: Status (Active vs Others)
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM mahasantri GROUP BY status");
    $status_raw = $stmt->fetchAll();
    $status_labels = []; $status_counts = [];
    foreach($status_raw as $row) {
        $status_labels[] = $row['status'];
        $status_counts[] = $row['count'];
    }

    // Chart Data: Top 5 Asal (Cities)
    $stmt = $pdo->query("SELECT asal, COUNT(*) as count FROM mahasantri WHERE asal != '' GROUP BY asal ORDER BY count DESC LIMIT 5");
    $asal_raw = $stmt->fetchAll();
    $asal_labels = []; $asal_counts = [];
    foreach($asal_raw as $row) {
        $asal_labels[] = $row['asal'];
        $asal_counts[] = $row['count'];
    }



} catch (PDOException $e) {
    if ($e->getCode() == '42S02' || strpos($e->getMessage(), '1146') !== false) {
        die("Table missing. Run setup.");
    }
    die("Error Fetching Stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    
    <!-- Chart.js (Dashboard Only) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Glass Card Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            background: rgba(255, 255, 255, 0.85);
        }
        .dark .glass-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .dark .glass-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen overflow-hidden flex dark:text-slate-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-8">

            <?php
            // FETCH AGENDA WIDGET DATA
            $next_events = AgendaLogic::getNextEvents($pdo, 5);
            $main_event = !empty($next_events) ? $next_events[0] : null;
            ?>

            <!-- AGENDA HERO SECTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <!-- 1. COUNTDOWN / MAIN EVENT -->
                <div class="lg:col-span-2 glass-card rounded-2xl p-6 relative overflow-hidden flex flex-col justify-center text-white bg-gradient-to-br from-indigo-600 to-blue-700 shadow-lg shadow-indigo-500/30">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                    <div class="absolute right-0 bottom-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mb-16"></div>

                    <?php if ($main_event): ?>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2 py-1 rounded-lg bg-white/20 text-xs font-bold uppercase tracking-wider backdrop-blur-sm">
                                    <i class="<?php echo $main_event['extendedProps']['icon']; ?> mr-1"></i> <?php echo $main_event['extendedProps']['kategori']; ?>
                                </span>
                                <?php if($main_event['extendedProps']['is_recurring']): ?>
                                    <span class="px-2 py-1 rounded-lg bg-orange-500/80 text-xs font-bold uppercase tracking-wider">Tahunan</span>
                                <?php endif; ?>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($main_event['title']); ?></h2>
                            <p class="text-indigo-100 text-lg mb-6 line-clamp-2"><?php echo htmlspecialchars($main_event['description'] ?? 'Tidak ada deskripsi tambahan.'); ?></p>
                            
                            <!-- Countdown Timer -->
                            <div class="grid grid-cols-4 gap-2 md:gap-4 max-w-lg mb-4">
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center">
                                    <span class="block text-2xl md:text-3xl font-mono font-bold" id="cd-days">00</span>
                                    <span class="text-[10px] uppercase opacity-70">Hari</span>
                                </div>
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center">
                                    <span class="block text-2xl md:text-3xl font-mono font-bold" id="cd-hours">00</span>
                                    <span class="text-[10px] uppercase opacity-70">Jam</span>
                                </div>
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center">
                                    <span class="block text-2xl md:text-3xl font-mono font-bold" id="cd-minutes">00</span>
                                    <span class="text-[10px] uppercase opacity-70">Menit</span>
                                </div>
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center">
                                    <span class="block text-2xl md:text-3xl font-mono font-bold" id="cd-seconds">00</span>
                                    <span class="text-[10px] uppercase opacity-70">Detik</span>
                                </div>
                            </div>
                            
                            <div class="text-sm text-indigo-200">
                                <i class="fa-solid fa-calendar mr-2"></i> <?php echo date('d F Y', strtotime($main_event['start'])); ?> 
                                <span class="mx-2">•</span> 
                                <i class="fa-solid fa-clock mr-2"></i> <?php echo date('H:i', strtotime($main_event['start'])); ?> WIB
                            </div>
                        </div>

                        <!-- Script for specific countdown -->
                        <script>
                            const targetDate = new Date("<?php echo $main_event['start']; ?>").getTime();
                            
                            const timer = setInterval(function() {
                                const now = new Date().getTime();
                                const distance = targetDate - now;
                                
                                if (distance < 0) {
                                    clearInterval(timer);
                                    document.getElementById("cd-days").innerHTML = "00";
                                    return;
                                }

                                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                                document.getElementById("cd-days").innerHTML = days;
                                document.getElementById("cd-hours").innerHTML = hours < 10 ? "0" + hours : hours;
                                document.getElementById("cd-minutes").innerHTML = minutes < 10 ? "0" + minutes : minutes;
                                document.getElementById("cd-seconds").innerHTML = seconds < 10 ? "0" + seconds : seconds;
                            }, 1000);
                        </script>

                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center h-full text-center relative z-10">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-4 text-3xl">
                                <i class="fa-solid fa-mug-hot"></i>
                            </div>
                            <h2 class="text-2xl font-bold mb-1">Tidak Ada Agenda Dekat</h2>
                            <p class="text-indigo-200 text-sm">Santai sejenak, belum ada kegiatan mendesak.</p>
                            <a href="calendar.php" class="mt-4 px-4 py-2 bg-white text-indigo-600 rounded-lg font-bold text-sm hover:bg-indigo-50 transition-colors">
                                + Buat Agenda
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. UPCOMING LIST -->
                <div class="glass-card rounded-2xl p-6 flex flex-col h-full">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-list-ul text-emerald-500"></i> Segera Datang
                        </h3>
                        <a href="calendar" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg transition-colors">
                            Lihat Kalender
                        </a>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3 pr-1">
                        <?php if (!empty($next_events)): ?>
                            <?php foreach ($next_events as $idx => $evt): 
                                // Skip first if shown in main card? OR show all? Let's show all but highlight differently.
                            ?>
                                <div class="flex gap-3 group p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-xl transition-colors cursor-pointer border border-transparent hover:border-slate-100 dark:hover:border-slate-700">
                                    <!-- Date Box -->
                                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 shrink-0 font-bold border border-slate-200 dark:border-slate-600">
                                        <span class="text-xs uppercase text-red-500"><?php echo date('M', strtotime($evt['start'])); ?></span>
                                        <span class="text-lg leading-none"><?php echo date('d', strtotime($evt['start'])); ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">
                                            <span style="color: <?php echo $evt['backgroundColor']; ?>">●</span> <?php echo $evt['extendedProps']['kategori']; ?>
                                        </p>
                                        <h4 class="font-bold text-slate-800 dark:text-white truncate group-hover:text-indigo-600 transition-colors">
                                            <?php echo htmlspecialchars($evt['title']); ?>
                                        </h4>
                                        <p class="text-xs text-slate-500 truncate">
                                            <?php echo $evt['allDay'] ? 'Sepanjang Hari' : date('H:i', strtotime($evt['start'])) . ' WIB'; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-slate-400">
                                <p class="text-sm">Belum ada agenda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- STATISTICS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <!-- Card 1: Total Aktif -->
                <div onclick="showActiveStats()" class="glass-card rounded-2xl p-6 group relative overflow-hidden cursor-pointer transition-all hover:scale-[1.02]">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-emerald-500/20"></div>
                    <div class="flex justify-between items-start relative">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Total Aktif</p>
                            <h3 class="text-3xl font-bold text-emerald-600 mt-2 group-hover:scale-110 origin-left transition-transform dark:text-emerald-400 flex items-center gap-2">
                                <?php echo $total_aktif; ?> <span class="text-sm font-normal text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">(Lihat Rincian)</span>
                            </h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300 shadow-sm"><i class="fa-solid fa-users text-xl"></i></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 flex items-center gap-1"><i class="fa-solid fa-hand-pointer text-emerald-500"></i> Klik untuk rincian per kelas</p>
                </div>

                <!-- Card 2: Ikhwan -->
                <a href="master_ikhwan" class="glass-card rounded-2xl p-6 group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-blue-500/20"></div>
                     <div class="flex justify-between items-start relative">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Total Ikhwan</p>
                            <h3 class="text-3xl font-bold text-blue-600 mt-2 group-hover:scale-110 origin-left transition-transform dark:text-blue-400"><?php echo $total_ikhwan; ?></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 shadow-sm"><i class="fa-solid fa-user-tie text-xl"></i></div>
                    </div>
                </a>

                <!-- Card 3: Akhowat -->
                <a href="master_akhowat" class="glass-card rounded-2xl p-6 group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-pink-500/10 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-pink-500/20"></div>
                     <div class="flex justify-between items-start relative">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Total Akhowat</p>
                            <h3 class="text-3xl font-bold text-pink-600 mt-2 group-hover:scale-110 origin-left transition-transform dark:text-pink-400"><?php echo $total_akhowat; ?></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-pink-100 text-pink-600 dark:bg-pink-900/40 dark:text-pink-300 shadow-sm"><i class="fa-solid fa-user-hijab text-xl"></i></div>
                    </div>
                </a>

                <!-- Card 4: Alumni/Lulus -->
                <a href="master_lulus.php" class="glass-card rounded-2xl p-6 group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-amber-500/20"></div>
                     <div class="flex justify-between items-start relative">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Total Alumni</p>
                            <h3 class="text-3xl font-bold text-amber-600 mt-2 group-hover:scale-110 origin-left transition-transform dark:text-amber-400"><?php echo $total_lulus; ?></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300 shadow-sm"><i class="fa-solid fa-graduation-cap text-xl"></i></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 flex items-center gap-1"><i class="fa-solid fa-arrow-right text-amber-500"></i> Lihat Database Alumni</p>
                </a>
                
                <!-- Card 5: Keluar/DO -->
                <a href="master_do.php" class="glass-card rounded-2xl p-6 group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-red-500/10 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-red-500/20"></div>
                     <div class="flex justify-between items-start relative">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Keluar / DO</p>
                            <h3 class="text-3xl font-bold text-red-600 mt-2 group-hover:scale-110 origin-left transition-transform dark:text-red-400"><?php echo $total_keluar; ?></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300 shadow-sm"><i class="fa-solid fa-user-xmark text-xl"></i></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-4 flex items-center gap-1"><i class="fa-solid fa-arrow-right text-red-500"></i> Lihat Arsip Non-Aktif</p>
                </a>

                <!-- Card 6: Pelanggaran (Interactive) -->
                <div onclick="showViolationStats()" class="glass-card rounded-2xl p-6 group relative overflow-hidden border-orange-200/50 cursor-pointer transition-all hover:scale-[1.02]">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-orange-500/10 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-orange-500/20"></div>
                     <div class="flex justify-between items-start relative">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Kasus Pelanggaran</p>
                            <h3 class="text-3xl font-bold text-orange-600 mt-2 group-hover:scale-110 origin-left transition-transform dark:text-orange-400 flex items-center gap-2">
                                <i class="fa-solid fa-eye text-2xl opacity-50"></i> <span class="text-lg underline decoration-dashed underline-offset-4">Lihat Detail</span>
                            </h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-300 shadow-sm"><i class="fa-solid fa-triangle-exclamation text-xl"></i></div>
                    </div>
                     <p class="text-xs text-slate-400 mt-4 flex items-center gap-1"><i class="fa-solid fa-hand-pointer text-orange-500"></i> Klik untuk rincian data</p>
                </div>

                <!-- Card 7: Total Database -->
                <a href="master_data.php" class="glass-card rounded-2xl p-6 group relative overflow-hidden lg:col-span-2 xl:col-span-2">
                    <div class="absolute right-0 top-0 w-64 h-64 bg-slate-500/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
                     <div class="flex justify-between items-center relative h-full">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide dark:text-slate-400">Total Database</p>
                            <h3 class="text-4xl font-bold text-slate-700 mt-1 dark:text-white"><?php echo $total_ever; ?> <span class="text-lg font-medium text-slate-400">Records</span></h3>
                            <p class="text-xs text-slate-400 mt-2">Seluruh data yang tersimpan dalam sistem.</p>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 shadow-inner"><i class="fa-solid fa-server text-3xl"></i></div>
                    </div>
                </a>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Angkatan Chart -->
                <div class="glass-card p-6 rounded-2xl col-span-1 lg:col-span-2">
                    <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2 dark:text-white">
                        <i class="fa-solid fa-chart-column text-emerald-500"></i> Statistik per Angkatan
                    </h3>
                    <div class="h-64 relative w-full">
                        <canvas id="angkatanChart"></canvas>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2 dark:text-white">
                        <i class="fa-solid fa-chart-pie text-blue-500"></i> Distribusi Status
                    </h3>
                    <div class="h-64 relative w-full flex justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Students & Map -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Students -->
                <!-- Statistik PPUI (Replacing Recent Input) -->
                <div class="glass-card p-6 rounded-2xl col-span-1 lg:col-span-2 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-building-columns text-indigo-500"></i> Statistik Asal PPUI
                        </h3>
                        <a href="master_aktif.php" class="text-xs text-indigo-500 hover:text-indigo-600 font-medium bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 rounded-lg transition-colors">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4 max-h-[300px]">
                        <?php 
                        // Fetch Distinct PPUI Count (Including Empty)
                        $ppui_query = $pdo->query("SELECT asal_ppui, COUNT(*) as count FROM mahasantri WHERE status='Aktif' GROUP BY asal_ppui ORDER BY count DESC");
                        $ppui_raw = $ppui_query->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Process Data: Handle Empty Asal
                        $ppui_stats = [];
                        foreach ($ppui_raw as $row) {
                            $key = empty(trim($row['asal_ppui'] ?? '')) ? 'Belum Terdata' : $row['asal_ppui'];
                            if (isset($ppui_stats[$key])) {
                                $ppui_stats[$key]['count'] += $row['count'];
                            } else {
                                $ppui_stats[$key] = [
                                    'asal_ppui' => $key,
                                    'count' => $row['count'],
                                    'original_key' => $row['asal_ppui'] // Keep original for search link
                                ];
                            }
                        }
                        
                        // Sort by Count DESC
                        usort($ppui_stats, function($a, $b) {
                            return $b['count'] - $a['count'];
                        });

                        // Calculate Statistics
                        $total_students = array_sum(array_column($ppui_stats, 'count'));
                        
                        if(count($ppui_stats) > 0):
                            foreach($ppui_stats as $ppui):
                                $percentage = $total_students > 0 ? round(($ppui['count'] / $total_students) * 100, 1) : 0;
                                
                                // Logic for "Belum Terdata"
                                if ($ppui['asal_ppui'] === 'Belum Terdata') {
                                    // Fetch specific students for this group to enable Quick Fix
                                    $missing_query = $pdo->query("SELECT * FROM mahasantri WHERE status='Aktif' AND (asal_ppui IS NULL OR asal_ppui = '')");
                                    $missing_students = $missing_query->fetchAll(PDO::FETCH_ASSOC);
                                    $missing_json = htmlspecialchars(json_encode($missing_students, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                    
                                    $onclick = "showMissingDataPopup($missing_json)";
                                    $href = "javascript:void(0);";
                                    $icon_class = "text-red-500 fa-circle-exclamation";
                                    $text_class = "text-red-600 dark:text-red-400 font-bold";
                                } else {
                                    $onclick = "";
                                    $href = "master_aktif.php?search=" . urlencode($ppui['original_key']);
                                    $icon_class = "hidden"; // No icon for normal rows
                                    $text_class = "text-slate-600 dark:text-slate-300";
                                }
                        ?>
                            <a href="<?php echo $href; ?>" onclick="<?php echo $onclick; ?>" class="group block p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="font-medium <?php echo $text_class; ?> group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors flex items-center gap-2">
                                        <?php if($ppui['asal_ppui'] === 'Belum Terdata'): ?>
                                            <i class="fa-solid fa-triangle-exclamation text-orange-500 animate-pulse"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($ppui['asal_ppui']); ?>
                                    </span>
                                    <span class="font-bold text-slate-800 dark:text-white"><?php echo $ppui['count']; ?> <span class="text-xs text-slate-400 font-normal">(<?php echo $percentage; ?>%)</span></span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                    <div class="<?php echo $ppui['asal_ppui'] === 'Belum Terdata' ? 'bg-orange-500' : 'bg-indigo-500'; ?> h-2 rounded-full transition-all duration-500 group-hover:bg-indigo-400" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </a>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <div class="text-center py-8 text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-50"></i>
                                <p class="text-sm">Belum ada data PPUI</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistik Kota (Replacing Top 5 Chart) -->
                <div class="glass-card p-6 rounded-2xl h-full flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-map-location text-purple-500"></i> Statistik Kota Asal
                        </h3>
                        <a href="master_aktif.php" class="text-xs text-purple-500 hover:text-purple-600 font-medium bg-purple-50 dark:bg-purple-900/30 px-3 py-1.5 rounded-lg transition-colors">
                            Lihat Semua
                        </a>
                    </div>
                     <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4 max-h-[300px]">
                        <?php 
                        // Fetch Distinct City Count
                        $city_query = $pdo->query("SELECT kabupaten, COUNT(*) as count FROM mahasantri WHERE status='Aktif' GROUP BY kabupaten ORDER BY count DESC");
                        $city_raw = $city_query->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Process Data
                        $city_stats = [];
                        foreach ($city_raw as $row) {
                            $key = empty(trim($row['kabupaten'] ?? '')) ? 'Belum Terdata' : $row['kabupaten'];
                            if (isset($city_stats[$key])) {
                                $city_stats[$key]['count'] += $row['count'];
                            } else {
                                $city_stats[$key] = [
                                    'kabupaten' => $key,
                                    'count' => $row['count'],
                                    'original_key' => $row['kabupaten']
                                ];
                            }
                        }
                        
                         // Sort
                        usort($city_stats, function($a, $b) {
                            return $b['count'] - $a['count'];
                        });

                        // Calculate Statistics
                        $total_students_city = array_sum(array_column($city_stats, 'count'));
                        
                        if(count($city_stats) > 0):
                            foreach($city_stats as $city):
                                $percentage = $total_students_city > 0 ? round(($city['count'] / $total_students_city) * 100, 1) : 0;
                                
                                // Logic for "Belum Terdata"
                                if ($city['kabupaten'] === 'Belum Terdata') {
                                    $missing_query = $pdo->query("SELECT * FROM mahasantri WHERE status='Aktif' AND (kabupaten IS NULL OR kabupaten = '')");
                                    $missing_students = $missing_query->fetchAll(PDO::FETCH_ASSOC);
                                    $missing_json = htmlspecialchars(json_encode($missing_students, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                    
                                    $onclick = "showMissingDataPopup($missing_json, 'Kabupaten')";
                                    $href = "javascript:void(0);";
                                    $icon_class = "text-red-500 fa-circle-exclamation";
                                    $text_class = "text-red-600 dark:text-red-400 font-bold";
                                } else {
                                    $onclick = "";
                                    $href = "master_aktif.php?search=" . urlencode($city['original_key']);
                                    $icon_class = "hidden";
                                    $text_class = "text-slate-600 dark:text-slate-300";
                                }
                        ?>
                            <a href="<?php echo $href; ?>" onclick="<?php echo $onclick; ?>" class="group block p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="font-medium <?php echo $text_class; ?> group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors flex items-center gap-2">
                                        <?php if($city['kabupaten'] === 'Belum Terdata'): ?>
                                            <i class="fa-solid fa-triangle-exclamation text-orange-500 animate-pulse"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($city['kabupaten']); ?>
                                    </span>
                                    <span class="font-bold text-slate-800 dark:text-white"><?php echo $city['count']; ?> <span class="text-xs text-slate-400 font-normal">(<?php echo $percentage; ?>%)</span></span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                    <div class="<?php echo $city['kabupaten'] === 'Belum Terdata' ? 'bg-orange-500' : 'bg-purple-500'; ?> h-2 rounded-full transition-all duration-500 group-hover:bg-purple-400" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </a>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <div class="text-center py-8 text-slate-400">
                                <i class="fa-solid fa-map-location-dot text-3xl mb-2 opacity-50"></i>
                                <p class="text-sm">Belum ada data Kota</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('angkatanChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($angkatan_labels); ?>,
                datasets: [{
                    label: 'Jumlah Mahasantri',
                    data: <?php echo json_encode($angkatan_counts); ?>,
                    backgroundColor: '#10B981',
                    borderRadius: 6,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Status Chart
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6366F1'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });



        // VIOLATION STATS POPUP logic
        function showViolationStats() {
            Swal.fire({
                title: 'Mengambil Data...',
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('api/get_violation_stats.php')
                .then(res => res.json())
                .then(res => {
                    if(res.status === 'success') {
                        let html = `
                            <div class="overflow-x-auto mt-4 rounded-lg border border-slate-200 dark:border-slate-700">
                                <table class="w-full text-sm text-center">
                                    <thead class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 font-bold uppercase text-xs">
                                        <tr>
                                            <th class="p-3 text-left">Kelas</th>
                                            <th class="p-3">Santri<br>Terlibat</th>
                                            <th class="p-3 bg-red-50 dark:bg-red-900/20 text-red-600">Total<br>Pelanggaran</th>
                                            <th class="p-3 bg-yellow-50 dark:bg-yellow-900/20">R / S / B</th>
                                            <th class="p-3 bg-orange-50 dark:bg-orange-900/20">SP1 / SP2 / SP3</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                        `;

                        if(res.data.length === 0) {
                            html += `<tr><td colspan="5" class="p-4 text-slate-400 italic">Belum ada data pelanggaran</td></tr>`;
                        } else {
                            res.data.forEach(row => {
                                html += `
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <td class="p-3 text-left font-bold">${row.mustawa}</td>
                                        <td class="p-3">${row.student_count}</td>
                                        <td class="p-3 font-bold text-red-600 bg-red-50/50">${row.total_violations}</td>
                                        <td class="p-3 text-xs bg-yellow-50/50">
                                            <span class="text-emerald-600 font-bold" title="Ringan">${row.count_ringan}</span> / 
                                            <span class="text-orange-500 font-bold" title="Sedang">${row.count_sedang}</span> / 
                                            <span class="text-red-600 font-bold" title="Berat">${row.count_berat}</span>
                                        </td>
                                        <td class="p-3 text-xs bg-orange-50/50">
                                            <span class="px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 font-bold">${row.count_sp1}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 font-bold">${row.count_sp2}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-bold">${row.count_sp3}</span>
                                        </td>
                                    </tr>
                                `;
                            });
                        }

                        html += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-xs text-slate-400 text-right">
                                * R: Ringan, S: Sedang, B: Berat
                            </div>
                        `;

                        Swal.fire({
                            title: '<strong>Statistik Pelanggaran</strong>',
                            icon: 'info',
                            html: html,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fa-solid fa-book-open"></i> Buka Buku Pelanggaran',
                            cancelButtonText: 'Tutup',
                            confirmButtonColor: '#ea580c', // Orange
                            cancelButtonColor: '#64748b',
                            width: '700px',
                            customClass: {
                                popup: 'rounded-2xl'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'violations.php';
                            }
                        });
                    } else {
                        Swal.fire('Error', 'Gagal memuat data', 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Kesalahan koneksi', 'error'));
        }

        // ACTIVE STATS POPUP logic
        function showActiveStats() {
            Swal.fire({
                title: 'Mengambil Data...',
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('api/get_active_stats.php')
                .then(res => res.json())
                .then(res => {
                    if(res.status === 'success') {
                        let html = `
                            <div class="overflow-x-auto mt-4 rounded-lg border border-slate-200 dark:border-slate-700">
                                <table class="w-full text-sm text-center">
                                    <thead class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 font-bold uppercase text-xs">
                                        <tr>
                                            <th class="p-3 text-left">Kelas (Mustawa)</th>
                                            <th class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600">Ikhwan</th>
                                            <th class="p-3 bg-pink-50 dark:bg-pink-900/20 text-pink-600">Akhowat</th>
                                            <th class="p-3 font-bold bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                        `;

                        if(res.data.length === 0) {
                            html += `<tr><td colspan="4" class="p-4 text-slate-400 italic">Belum ada data aktif</td></tr>`;
                        } else {
                            res.data.forEach(row => {
                                html += `
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <td class="p-3 text-left font-bold border-r border-slate-100 dark:border-slate-700">${row.mustawa}</td>
                                        <td class="p-3 text-blue-600 font-medium">${row.count_ikhwan}</td>
                                        <td class="p-3 text-pink-600 font-medium">${row.count_akhowat}</td>
                                        <td class="p-3 font-bold text-emerald-600 bg-emerald-50/30">${row.total}</td>
                                    </tr>
                                `;
                            });
                        }

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        Swal.fire({
                            title: '<strong>Rincian Santri Aktif</strong>',
                            icon: 'info',
                            html: html,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fa-solid fa-users-viewfinder"></i> Lihat Data Lengkap',
                            cancelButtonText: 'Tutup',
                            confirmButtonColor: '#10b981', 
                            cancelButtonColor: '#64748b',
                            width: '600px',
                            customClass: {
                                popup: 'rounded-2xl'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'master_aktif.php';
                            }
                        });
                    } else {
                        Swal.fire('Error', 'Gagal memuat data', 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Kesalahan koneksi', 'error'));
        }
        // MISSING DATA POPUP logic
        // Region API Logic (Localized for Quick Fix)
        const regionApiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        async function getProvinces() {
            // Check cache or global first if available, else fetch
            // Simple fetch for now
            const res = await fetch(`${regionApiBase}/provinces.json`);
            return await res.json();
        }

        async function getRegencies(provId) {
            const res = await fetch(`${regionApiBase}/regencies/${provId}.json`);
            return await res.json();
        }

        // MISSING DATA POPUP logic
        function showMissingDataPopup(students, fieldNameLabel = 'Asal PPUI') {
            const isCityFix = (fieldNameLabel === 'Kabupaten');
            const dbField = isCityFix ? 'kabupaten' : 'asal_ppui';
            
            // Width adjustment
            const modalWidth = isCityFix ? '900px' : '700px';

            let tableHead = '';
            if (isCityFix) {
                tableHead = `
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3 w-[200px]">Pilih Provinsi</th>
                    <th class="px-4 py-3 w-[200px]">Pilih Kabupaten</th>
                    <th class="px-4 py-3 w-[50px]"></th>
                `;
            } else {
                tableHead = `
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3 w-[250px]">Input ${fieldNameLabel}</th>
                    <th class="px-4 py-3 w-[50px]"></th>
                `;
            }

            let html = `
                <div class="flex justify-between items-center mb-4">
                    <div class="text-left text-sm text-slate-600 dark:text-slate-300">
                        Input manual <b>${fieldNameLabel}</b> untuk data berikut:
                    </div>
                    <div id="quickFixInfo" class="text-xs font-mono bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">
                        Sisa: <b>${students.length}</b>
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase text-slate-500 sticky top-0 backdrop-blur-md z-10">
                            <tr>${tableHead}</tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
            `;

            students.forEach(s => {
                let inputCells = '';
                
                if (isCityFix) {
                    // Two Dropdowns
                    inputCells = `
                        <td class="px-4 py-3">
                            <select id="prov-${s.id}" onchange="loadCityForRow(${s.id}, this.value)" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Loading...</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <select id="kab-${s.id}" disabled class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none disabled:opacity-50">
                                <option value="">Pilih Prov Dulu</option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button id="btn-save-${s.id}" onclick="saveInlineRegion(${s.id})" 
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <i class="fa-solid fa-save text-xs"></i>
                            </button>
                        </td>
                    `;
                } else {
                    // Standard Text Input
                    inputCells = `
                        <td class="px-4 py-3">
                            <input type="text" id="input-${s.id}" 
                                class="w-full px-3 py-1.5 text-sm rounded-lg border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-400"
                                placeholder="Ketik ${fieldNameLabel}..."
                                onkeydown="if(event.key === 'Enter') saveInline(${s.id}, '${dbField}')">
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button id="btn-save-${s.id}" onclick="saveInline(${s.id}, '${dbField}')" 
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                            </button>
                        </td>
                    `;
                }

                html += `
                    <tr id="row-fix-${s.id}" class="group hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-700 dark:text-slate-200">${s.nama}</div>
                            <div class="text-xs text-slate-400 font-mono">${s.nim}</div>
                        </td>
                        ${inputCells}
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end">
                    <button onclick="location.reload()" class="bg-slate-800 text-white hover:bg-slate-900 px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg transition-all active:scale-95 flex items-center gap-2">
                        <i class="fa-solid fa-rotate-right"></i> Selesai & Refresh
                    </button>
                </div>
            `;

            Swal.fire({
                title: 'Quick Fix Data',
                html: html,
                width: modalWidth,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    popup: 'rounded-2xl dark:bg-slate-800 dark:border dark:border-slate-700'
                },
                didOpen: async () => {
                   if (isCityFix) {
                       // Load Provinces for ALL dropdowns
                       const provs = await getProvinces();
                       const selects = document.querySelectorAll('select[id^="prov-"]');
                       
                       const optionsHtml = '<option value="">Pilih Provinsi</option>' + 
                           provs.map(p => `<option value="${p.id}" data-name="${p.name}">${p.name}</option>`).join('');
                       
                       selects.forEach(s => s.innerHTML = optionsHtml);
                   } else {
                       const firstInput = document.querySelector('input[id^="input-"]');
                       if(firstInput) firstInput.focus();
                   }
                }
            });
        }

        async function loadCityForRow(id, provId) {
            const kabSelect = document.getElementById(`kab-${id}`);
            if (!provId) {
                kabSelect.innerHTML = '<option value="">Pilih Prov Dulu</option>';
                kabSelect.disabled = true;
                return;
            }

            kabSelect.innerHTML = '<option value="">Loading...</option>';
            kabSelect.disabled = true;

            const cities = await getRegencies(provId);
            kabSelect.innerHTML = '<option value="">Pilih Kota/Kab</option>' + 
                cities.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
            kabSelect.disabled = false;
        }

        function saveInlineRegion(id) {
            const provSelect = document.getElementById(`prov-${id}`);
            const kabSelect = document.getElementById(`kab-${id}`);
            const btn = document.getElementById(`btn-save-${id}`);
            
            const provName = provSelect.options[provSelect.selectedIndex].getAttribute('data-name');
            const kabName = kabSelect.value;

            if (!provName || !kabName) {
                // Shake validation
                 if(!provName) provSelect.classList.add('ring-2', 'ring-red-400');
                 if(!kabName) kabSelect.classList.add('ring-2', 'ring-red-400');
                 setTimeout(() => {
                     provSelect.classList.remove('ring-2', 'ring-red-400');
                     kabSelect.classList.remove('ring-2', 'ring-red-400');
                 }, 1000);
                 return;
            }

            // Loading
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btn.disabled = true;
            provSelect.disabled = true;
            kabSelect.disabled = true;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('mode', 'multiple');
            formData.append('data', JSON.stringify({
                provinsi: provName,
                kabupaten: kabName
            }));

            fetch('api/quick_update', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                         const row = document.getElementById(`row-fix-${id}`);
                         // Remove input cells and replace with success message
                         // Simple approach: Replace the 3 cells with 1 colspan cell or just update content
                         // Structure is: Name | Prov | Kab | Action
                         // Target parent of btn is last cell.
                         
                         // Clean up cells
                         btn.parentElement.innerHTML = ''; // Remove save button
                         
                         // Replace dropdowns with text
                         provSelect.parentElement.innerHTML = `<span class="text-xs text-slate-500">${provName}</span>`;
                         kabSelect.parentElement.innerHTML = `<span class="font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded border border-emerald-100"><i class="fa-solid fa-check"></i> ${kabName}</span>`;
                         
                         row.classList.add('bg-emerald-50/50', 'dark:bg-emerald-900/10');
                         updateCounter();

                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerHTML = '<i class="fa-solid fa-save"></i>';
                    btn.disabled = false;
                    provSelect.disabled = false;
                    kabSelect.disabled = false;
                    Swal.showValidationMessage(`Gagal: ${err.message}`);
                });
        }
        
        function updateCounter() {
            const info = document.getElementById('quickFixInfo');
            if(!info) return;
            let currentCount = parseInt(info.querySelector('b').innerText);
            if (currentCount > 0) info.innerHTML = `Sisa: <b>${currentCount - 1}</b>`;
            if (currentCount - 1 === 0) {
                    info.innerHTML = `<span class="text-emerald-500 font-bold"><i class="fa-solid fa-check-double"></i> Selesai!</span>`;
                    Swal.showValidationMessage('Semua data beres!');
            }
        }

        function saveInline(id, field) {
            const input = document.getElementById(`input-${id}`);
            const btn = document.getElementById(`btn-save-${id}`);
            const val = input.value.trim();

            if (!val) {
                input.focus();
                input.classList.add('ring-2', 'ring-red-400');
                setTimeout(() => input.classList.remove('ring-2', 'ring-red-400'), 1000);
                return;
            }

            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btn.disabled = true;
            input.disabled = true;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('field', field);
            formData.append('value', val);

            fetch('api/quick_update.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.getElementById(`row-fix-${id}`);
                        input.parentElement.innerHTML = `<span class="font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1 rounded-lg border border-emerald-100 dark:border-emerald-800 flex items-center gap-2"><i class="fa-solid fa-check-circle"></i> ${val}</span>`;
                        btn.parentElement.innerHTML = '';
                        row.classList.add('bg-emerald-50/50', 'dark:bg-emerald-900/10');
                        
                        updateCounter();

                        const nextInput = document.querySelector('input[id^="input-"]:not([disabled])');
                        if(nextInput) nextInput.focus();
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    btn.disabled = false;
                    input.disabled = false;
                    Swal.showValidationMessage(`Gagal: ${err.message}`);
                });
        }
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
