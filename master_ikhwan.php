<?php
require_once 'functions.php';
requireLogin();

// Fetch Data KHUSUS IKHWAN with Violation Stats
try {
    $sql = "SELECT m.*, 
        COUNT(p.id) as violation_count,
        MAX(CASE WHEN p.tingkat_sanksi = 'SP3' THEN 1 ELSE 0 END) as has_sp3,
        MAX(CASE WHEN p.tingkat_sanksi = 'SP2' THEN 1 ELSE 0 END) as has_sp2,
        MAX(CASE WHEN p.tingkat_sanksi = 'SP1' THEN 1 ELSE 0 END) as has_sp1
    FROM mahasantri m
    LEFT JOIN pelanggaran p ON m.id = p.mahasantri_id
    WHERE (m.gender LIKE 'Ikhwan%' OR m.gender = 'Laki-laki' OR m.gender = 'L') AND m.status = 'Aktif'
    GROUP BY m.id
    ORDER BY m.nim ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("<h3>Terjadi Kesalahan Database:</h3><p>Gagal mengambil data santri. Detail: " . $e->getMessage() . "</p>");
}
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
                        sans: ['"Inter"', 'sans-serif'],
                        heading: ['"Outfit"', 'sans-serif'],
                    },
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981', // Prime
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        blue: {
                            50: '#eff6ff', 
                            100: '#dbeafe', 
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        body {
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .dark body {
            background-color: #0f172a;
            background-image: radial-gradient(#1e293b 1px, transparent 1px);
        }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen overflow-hidden flex dark:text-slate-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- HEADER & ACTIONS -->
                <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                    <div>
                        <h1 class="text-3xl font-bold font-heading text-blue-800 dark:text-blue-400">Direktori Ikhwan</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Data lengkap mahasantri Ikhwan aktif tahun ajaran ini.</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="window.location.reload()" class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 shadow-sm flex items-center justify-center transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <a href="master_data.php" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white hover:bg-slate-900 font-medium transition-all shadow-lg shadow-slate-200/50 dark:shadow-none flex items-center gap-2">
                            <i class="fa-solid fa-database"></i> Database Master
                        </a>
                    </div>
                </div>

                <!-- SMART SEARCH & FILTERS -->
                <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl p-5 rounded-2xl border border-white/50 dark:border-slate-700 shadow-lg shadow-blue-500/5 sticky top-0 z-20">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search Box -->
                        <div class="md:col-span-3 relative">
                            <input type="text" id="searchInput" placeholder="Cari Nama, NIM, Asal, atau Walisantri..." 
                                class="w-full pl-12 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400">
                            <i class="fa-solid fa-search absolute left-4 top-3.5 text-slate-400 pointer-events-none"></i>
                        </div>

                        <!-- Filter Mustawa -->
                        <div class="relative">
                            <select id="filterMustawa" class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all appearance-none cursor-pointer text-slate-600 dark:text-slate-300">
                                <option value="">Semua Kelas</option>
                                <option value="Awwal">Mustawa Awwal</option>
                                <option value="Tsani">Mustawa Tsani</option>
                            </select>
                            <i class="fa-solid fa-layer-group absolute left-4 top-3.5 text-slate-400 pointer-events-none"></i>
                            <i class="fa-solid fa-chevron-down absolute right-4 top-4 text-xs text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <!-- Result Count -->
                    <div class="mt-3 flex justify-between items-center text-xs px-1">
                        <span class="text-slate-500 font-medium"><i class="fa-solid fa-list-ul mr-1"></i> Menampilkan <span id="visibleCount" class="text-slate-800 dark:text-white font-bold"><?php echo count($students); ?></span> santri</span>
                        <button onclick="resetFilters()" class="text-blue-600 hover:text-blue-700 font-medium hover:underline hidden" id="resetBtn">Reset Filter</button>
                    </div>
                </div>

                <!-- CARDS GRID -->
                <div id="studentGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6 pb-20">
                    <?php if(count($students) > 0): ?>
                        <?php foreach($students as $s): 
                            $photo = $s['photo_path'] && file_exists($s['photo_path']) ? $s['photo_path'] : '';
                            $full_name = formatNameBin($s['nama'], $s['gender'], $s['nama_ayah']);
                        ?>
                        <div onclick='viewStudent(<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="student-card group bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative flex flex-col cursor-pointer"
                             data-name="<?php echo strtolower($s['nama']); ?>"
                             data-nim="<?php echo $s['nim']; ?>"
                             data-mustawa="<?php echo $s['mustawa']; ?>"
                             data-asal="<?php echo strtolower($s['asal']); ?>"
                             data-wali="<?php echo strtolower($s['nama_ayah']); ?>">
                            
                            <!-- Violation Badge -->
                            <?php if ($s['violation_count'] > 0): ?>
                                <div class="absolute top-0 right-0 z-10">
                                    <?php if ($s['has_sp3']): ?>
                                        <span class="bg-red-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm">SP3</span>
                                    <?php elseif ($s['has_sp2']): ?>
                                        <span class="bg-orange-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm">SP2</span>
                                    <?php elseif ($s['has_sp1']): ?>
                                        <span class="bg-yellow-400 text-yellow-900 text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm">SP1</span>
                                    <?php else: ?>
                                        <span class="bg-slate-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col md:flex-row h-full">
                                <!-- Left: Photo -->
                                <div class="w-full md:w-32 bg-slate-50 dark:bg-slate-700/30 flex flex-col items-center justify-center p-4 border-b md:border-b-0 md:border-r border-slate-100 dark:border-slate-700 relative overflow-hidden">
                                     <div class="w-20 h-20 rounded-full border-2 border-white dark:border-slate-600 shadow-md overflow-hidden relative z-10">
                                        <?php if($photo): ?>
                                            <img src="<?php echo $photo; ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-600 text-slate-300">
                                                <i class="fa-solid fa-user text-2xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2 text-center">
                                         <span class="block text-xs font-bold text-slate-400 font-mono tracking-tighter"><?php echo $s['nim']; ?></span>
                                          <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase mt-1 bg-blue-50 text-blue-600">
                                            <?php echo $s['gender']; ?>
                                        </span>
                                    </div>
                                    <!-- Background Pattern -->
                                    <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                                </div>

                                <!-- Right: Details -->
                                <div class="p-4 flex-1 flex flex-col justify-between">
                                    <div>
                                        <!-- Header Info -->
                                        <div class="mb-3">
                                            <h3 class="font-bold text-lg text-slate-800 dark:text-white leading-tight font-heading group-hover:text-blue-600 transition-colors">
                                                <?php echo $s['nama']; ?>
                                            </h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                                                <?php echo $s['nama_ayah'] ? 'Bin ' . $s['nama_ayah'] : ''; ?>
                                            </p>
                                        </div>

                                        <!-- Meta Info Pills -->
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-[11px] font-semibold border border-blue-100 dark:border-blue-800">
                                                <i class="fa-solid fa-layer-group"></i> Mustawa <?php echo $s['mustawa']; ?>
                                            </span>
                                            <?php if($s['asal_ppui']): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-[11px] font-semibold border border-indigo-100 dark:border-indigo-800">
                                                <i class="fa-solid fa-building-columns"></i> <?php echo $s['asal_ppui']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Detailed Info List -->
                                        <div class="grid grid-cols-1 gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                            <div class="flex items-center gap-2">
                                                 <i class="fa-solid fa-location-dot w-4 text-center text-red-400"></i>
                                                 <span class="truncate"><?php echo $s['kabupaten'] ? $s['kabupaten'] . ', ' : ''; ?><?php echo $s['provinsi']; ?></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                 <i class="fa-solid fa-cake-candles w-4 text-center text-pink-400"></i>
                                                 <span><?php echo formatDateId($s['tanggal_lahir']); ?> (<?php echo getAge($s['tanggal_lahir']); ?> Thn)</span>
                                            </div>
                                             <div class="flex items-center gap-2">
                                                 <i class="fa-solid fa-person-breastfeeding w-4 text-center text-orange-400"></i>
                                                 <span>Ibu: <span class="font-medium"><?php echo $s['nama_ibu'] ?: '-'; ?></span></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Footer: Phone & Joined Year -->
                                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                                         <?php if($s['wa_wali']): ?>
                                            <a href="https://wa.me/<?php echo formatWA($s['wa_wali']); ?>" target="_blank" onclick="event.stopPropagation()" class="flex items-center gap-1.5 text-emerald-600 hover:text-emerald-700 font-medium text-xs bg-emerald-50 px-2 py-1 rounded-full transition-colors">
                                                <i class="fa-brands fa-whatsapp"></i> <?php echo formatPhoneNumberDisplay($s['wa_wali']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">-</span>
                                        <?php endif; ?>
                                        <span class="text-[10px] text-slate-400 font-medium bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-md">
                                            Th. Masuk: <?php echo $s['angkatan']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full py-12 text-center text-slate-400">
                             <div class="bg-white/50 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4 text-3xl shadow-sm">
                                <i class="fa-solid fa-user-slash"></i>
                             </div>
                            <p>Belum ada data Ikhwan aktif.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Empty State for Filter -->
                    <div id="noResults" class="hidden col-span-full py-20 text-center">
                        <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-500 text-4xl shadow-inner">
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-700 dark:text-white mb-2">Tidak ditemukan</h3>
                        <p class="text-slate-500 max-w-md mx-auto">Tidak ada santri yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau reset filter.</p>
                        <button onclick="resetFilters()" class="mt-6 px-6 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium transition-colors dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200">
                            Reset Filter
                        </button>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Include Modals for "Detail" View -->
    <?php include 'includes/student_modals.php'; ?>

    <!-- JS Logic -->
    <script src="assets/js/student_crud.js"></script> 
    <!-- Note: student_crud.js handles openViewModal logic -->

    <script>
        // SMART SEARCH & FILTER Logic
        const searchInput = document.getElementById('searchInput');
        const filterMustawa = document.getElementById('filterMustawa');
        const cards = document.querySelectorAll('.student-card');
        const noResults = document.getElementById('noResults');
        const visibleCount = document.getElementById('visibleCount');
        const resetBtn = document.getElementById('resetBtn');

        function filterStudents() {
            const query = searchInput.value.toLowerCase();
            const mustawa = filterMustawa.value;
            let count = 0;

            cards.forEach(card => {
                const name = card.dataset.name;
                const nim = card.dataset.nim;
                const cardMustawa = card.dataset.mustawa;
                const asal = card.dataset.asal;
                const wali = card.dataset.wali;

                // Logic matches
                const matchesSearch = name.includes(query) || nim.includes(query) || asal.includes(query) || wali.includes(query);
                const matchesMustawa = mustawa === '' || cardMustawa === mustawa;

                if(matchesSearch && matchesMustawa) {
                    card.classList.remove('hidden');
                    card.classList.add('flex'); // Ensure flex is restored
                    count++;
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('flex');
                }
            });

            // Update UI
            visibleCount.textContent = count;
            if(count === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }

            // Show/Hide Reset Button
            if(query || mustawa) {
                resetBtn.classList.remove('hidden');
            } else {
                resetBtn.classList.add('hidden');
            }
        }

        // Attach Events
        searchInput.addEventListener('input', filterStudents);
        filterMustawa.addEventListener('change', filterStudents);

        function resetFilters() {
            searchInput.value = '';
            filterMustawa.value = '';
            filterStudents();
        }
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
