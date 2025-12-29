<?php
require_once 'functions.php';
requireLogin();
$page_title = 'Database Pusat Mahasantri';

// --- MAIN QUERY: FETCH ALL DATA ---
// Fetch ALL students (Aktif, Lulus, DO, Cuti)
$query = "SELECT * FROM mahasantri ORDER BY nim ASC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll();

// Get Unique Lists for Filters
$angkatan_list = array_unique(array_column($students, 'angkatan'));
rsort($angkatan_list);

$ppui_list = array_unique(array_filter(array_column($students, 'asal_ppui')));
sort($ppui_list);

$prop_list = array_unique(array_filter(array_column($students, 'provinsi')));
sort($prop_list);

$kab_list = array_unique(array_filter(array_column($students, 'kabupaten')));
sort($kab_list);

$mustawa_list = array_unique(array_filter(array_column($students, 'mustawa')));
sort($mustawa_list);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    
    <!-- Scripts (jQuery & DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0f172a', 
                        accent: '#3b82f6',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
        
        /* DataTables Customization */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 30px;
            border-radius: 8px;
            border-color: #e2e8f0;
        }
        .dataTables_wrapper .dataTables_filter { display: none; } /* Hide Default Search */
        table.dataTable thead th { border-bottom: 2px solid #e2e8f0 !important; }
        table.dataTable.no-footer { border-bottom: 1px solid #e2e8f0 !important; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        /* Filter Select Style */
        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-600 antialiased selection:bg-blue-100 selection:text-blue-700">

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
            <?php include 'includes/header.php'; ?>
            <main class="flex-1 p-2 lg:p-4 w-full">
            <div class="w-full">
                
                <!-- MOBILE: STICKY SEARCH & FILTER HEADER -->
                <div class="md:hidden sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200 p-4 -mx-4 -mt-4 mb-6 shadow-sm flex gap-3 transition-all duration-300" id="mobileStickyHeader">
                    <div class="relative flex-1 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                        </div>
                        <input type="text" id="globalSearchMobile" class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" placeholder="Cari nama, nim, kota...">
                    </div>
                    <button class="bg-white border border-slate-200 text-slate-600 px-4 rounded-xl flex items-center justify-center shadow-sm active:scale-95 transition-all hover:bg-slate-50 relative" onclick="toggleFilterSheet()">
                        <i class="fa-solid fa-sliders text-lg"></i>
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white hidden" id="filterBadge"></span>
                    </button>
                    <!-- Add Button Mobile -->
                    <button onclick="openModal('create')" class="bg-blue-600 text-white px-4 rounded-xl flex items-center justify-center shadow-md shadow-blue-200 active:scale-95 transition-all">
                        <i class="fa-solid fa-plus text-lg"></i>
                    </button>
                </div>

                <!-- DESKTOP: TOOLS BAR (Hidden on Mobile) -->
                <div class="md:bg-white md:rounded-xl md:shadow-sm md:border md:border-slate-200 md:overflow-hidden">
                    <div class="hidden md:block p-4 border-b border-slate-200 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-500/5 to-purple-500/5 rounded-full blur-2xl -mr-16 -mt-16 pointer-events-none"></div>
                    
                    <div class="flex flex-col md:flex-row gap-4 justify-between items-center relative z-10">
                        <!-- Search & Filter Toggle -->
                         <div class="flex items-center gap-3 w-full md:w-auto">
                            <div class="relative flex-1 md:w-72 group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-magnifying-glass text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                                </div>
                                <input type="text" id="globalSearch" class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" placeholder="Cari data mahasantri...">
                            </div>
                            
                            <button onclick="toggleFilter()" id="filterToggleBtn" class="bg-white border border-slate-200 text-slate-600 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm flex items-center gap-2">
                                <i class="fa-solid fa-filter"></i>
                                <span>Filter</span>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300"></i>
                            </button>
                         </div>

                         <!-- Action Buttons -->
                         <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                            <button onclick="openImportModal()" class="bg-indigo-50 text-indigo-600 border border-indigo-100 hover:bg-indigo-100 px-4 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                <i class="fa-solid fa-file-import"></i> <span class="hidden sm:inline">Import</span>
                            </button>
                            <button onclick="exportData()" class="bg-emerald-50 text-emerald-600 border border-emerald-100 hover:bg-emerald-100 px-4 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                <i class="fa-solid fa-file-excel"></i> <span class="hidden sm:inline">Export</span>
                            </button>
                            <button onclick="openModal('create')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-md shadow-blue-200 transition-all flex items-center gap-2">
                                <i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Tambah Data</span>
                            </button>
                         </div>
                    </div>
                </div>

                <!-- FILTER OVERLAY BACKDROP (Mobile Only) -->
                <div id="filterBackdrop" class="fixed inset-0 bg-black/40 z-[45] hidden transition-opacity duration-300 backdrop-blur-sm md:hidden" onclick="closeFilterSheet()"></div>

                <!-- FILTER SECTION (Responsive: Bottom Sheet on Mobile / Collapsible on Desktop) -->
                <div id="filterSection" class="
                    fixed inset-x-0 bottom-0 z-[50] bg-white rounded-t-3xl shadow-[0_-8px_30px_rgba(0,0,0,0.12)] p-6 transform translate-y-full transition-transform duration-300 max-h-[85vh] overflow-y-auto
                    md:translate-y-0 md:static md:z-auto md:p-6 md:max-h-0 md:overflow-hidden md:opacity-0 md:transition-all md:bg-slate-50/50 md:border-b md:border-slate-200
                ">
                    <!-- Mobile Sheet Header -->
                    <div class="flex justify-between items-center mb-6 md:hidden">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-blue-600"></i>
                            <h3 class="font-bold text-lg text-slate-800">Filter Data</h3>
                        </div>
                        <button onclick="closeFilterSheet()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>

                    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6">
                        <!-- 1. Gender -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Gender</label>
                            <div class="relative">
                                <select id="filterGender" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua</option>
                                    <option value="Ikhwan">Ikhwan</option>
                                    <option value="Akhowat">Akhowat</option>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- 2. Mustawa -->
                         <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Mustawa</label>
                            <div class="relative">
                                <select id="filterMustawa" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua</option>
                                    <?php foreach($mustawa_list as $mst): ?>
                                        <option value="<?php echo $mst; ?>"><?php echo $mst; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- 3. Angkatan -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Angkatan</label>
                            <div class="relative">
                                <select id="filterAngkatan" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua</option>
                                    <?php foreach($angkatan_list as $ank): ?>
                                        <option value="<?php echo $ank; ?>"><?php echo $ank; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- 4. Status -->
                         <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Status</label>
                            <div class="relative">
                                <select id="filterStatus" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua Status</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Cuti">Cuti</option>
                                    <option value="Lulus">Lulus</option>
                                    <option value="Dikeluarkan">Dikeluarkan</option>
                                    <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                                    <option value="Meninggal Dunia">Meninggal Dunia</option>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Advanced Filters Row -->
                        <!-- 5. Asal PPUI -->
                         <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Asal PPUI</label>
                            <div class="relative">
                                <select id="filterPPUI" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua</option>
                                    <?php foreach($ppui_list as $pp): ?>
                                        <option value="<?php echo $pp; ?>"><?php echo $pp; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                         <!-- 6. Provinsi -->
                         <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Provinsi</label>
                             <div class="relative">
                                <select id="filterProvinsi" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua</option>
                                    <?php foreach($prop_list as $pr): ?>
                                        <option value="<?php echo $pr; ?>"><?php echo $pr; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- 7. Kabupaten -->
                         <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide ml-1">Kabupaten/Kota</label>
                             <div class="relative">
                                <select id="filterKabupaten" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 appearance-none">
                                    <option value="">Semua</option>
                                    <?php foreach($kab_list as $kb): ?>
                                        <option value="<?php echo $kb; ?>"><?php echo $kb; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Reset Button -->
                        <div class="flex items-end md:col-start-4">
                            <button type="button" onclick="resetFilters()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-rotate-right"></i> Reset Filter
                            </button>
                        </div>
                    </form>
                    
                    <!-- Mobile Apply Button (Now just Close, since Filter is Live) -->
                    <div class="mt-6 pt-4 border-t border-slate-100 md:hidden">
                        <button onclick="closeFilterSheet()" class="btn-primary w-full py-3 rounded-xl shadow-lg shadow-blue-200 font-bold text-base">
                            Tutup
                        </button>
                    </div>
                </div>

                <!-- MAIN TABLE: CENTRAL DATABASE -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 md:rounded-none md:shadow-none md:border-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center relative min-h-[60px]">
                        <!-- Centered Aesthetic Title -->
                        <div class="absolute left-1/2 -translate-x-1/2 flex items-center gap-2 group cursor-default">
                            <i class="fa-solid fa-layer-group text-lg text-blue-500 group-hover:rotate-12 transition-transform duration-300"></i>
                            <h3 class="text-xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 uppercase drop-shadow-sm">
                                Data Master
                            </h3>
                            <i class="fa-solid fa-layer-group text-lg text-purple-500 group-hover:-rotate-12 transition-transform duration-300"></i>
                        </div>
                        
                        <!-- Right Side (Spacer to push content but keep Total visible) -->
                        <div class="ml-auto flex items-center gap-2 z-10">
                            <span id="totalRecords" class="text-[10px] font-bold uppercase tracking-wider bg-white text-slate-600 px-3 py-1.5 rounded-full border border-slate-200 shadow-sm">
                                Total: <?php echo count($students); ?>
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="mainTable" class="w-full text-left border-collapse table-fixed text-[11px]">
                            <thead>
                                <tr>
                                    <th class="w-[3%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">No</th>
                                    <th class="w-[27%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">Mahasantri</th>
                                    <th class="hidden md:table-cell w-[3%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">L/P</th>
                                    <th class="hidden md:table-cell w-[12%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">TTL & Usia</th>
                                    <th class="hidden md:table-cell w-[10%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">Akd</th>
                                    <th class="hidden md:table-cell w-[20%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">Asal & Alamat</th>
                                    <th class="hidden md:table-cell w-[15%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">Orang Tua</th>
                                    <th class="hidden md:table-cell w-[4%] text-center uppercase tracking-wider px-2 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">Status</th>
                                    <th class="w-[10%] text-center uppercase tracking-wider pl-2 pr-6 py-2 text-slate-500 font-bold bg-slate-50 border-b border-slate-200">Aksi</th>
                                    <!-- Hidden Columns for Filtering -->
                                    <th class="hidden">Provinsi</th> <!-- Index 9 -->
                                    <th class="hidden">Kabupaten</th> <!-- Index 10 -->
                                    <th class="hidden">Asal PPUI</th> <!-- Index 11 -->
                                    <th class="hidden">NIM_Sort</th>  <!-- Index 12 -->
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php 
                                $no = 1;
                                foreach ($students as $row): 
                                    // Helper Variables
                                    $statusClass = match($row['status']) {
                                        'Aktif' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
                                        'Mutasi' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20',
                                        'Lulus' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20',
                                        'Dikeluarkan' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
                                        default => 'bg-slate-50 text-slate-600 ring-1 ring-slate-600/20'
                                    };
                                    $isIkhwan = $row['gender'] == 'Ikhwan';
                                    $genderBadge = $isIkhwan ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-pink-50 text-pink-600 border border-pink-100';
                                    $avatarFallback = $isIkhwan ? 'bg-blue-100 text-blue-500' : 'bg-pink-100 text-pink-500';
                                    $nameColor = $isIkhwan ? 'text-blue-700' : 'text-rose-600';
                                ?>
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="text-center text-slate-400 text-xs font-medium p-3"><?php echo $no++; ?></td>
                                    
                                    <!-- 1. Mahasantri -->
                                    <td class="p-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full <?php echo $avatarFallback; ?> flex items-center justify-center text-sm overflow-hidden shrink-0 border border-white shadow-sm ring-1 ring-slate-100">
                                                <?php if(!empty($row['photo_path']) && file_exists($row['photo_path'])): ?>
                                                    <img loading="lazy" src="<?php echo $row['photo_path']; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <i class="fa-solid <?php echo $isIkhwan ? 'fa-user' : 'fa-user-hijab'; ?>"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="font-semibold <?php echo $nameColor; ?> text-sm leading-tight">
                                                    <?php echo formatNameBin($row['nama'], $row['gender'], $row['nama_ayah']); ?>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-0.5 font-mono flex items-center">
                                                    <span class="bg-slate-100 px-1 rounded text-slate-600"><?php echo $row['nim']; ?></span>
                                                    <span class="text-slate-300 mx-1">•</span>
                                                    <span class="text-slate-500 font-medium"><?php echo $row['mustawa']; ?></span>
                                                    <span class="text-slate-300 mx-1">•</span>
                                                    <span class="text-slate-500"><?php echo $row['gender']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. Gender -->
                                    <td class="hidden md:table-cell text-center p-3">
                                        <span class="hidden"><?php echo $row['gender']; ?></span> 
                                        <span class="w-8 h-8 inline-flex items-center justify-center rounded-full text-xs font-bold <?php echo $genderBadge; ?>" title="<?php echo $row['gender']; ?>">
                                            <?php echo $isIkhwan ? 'L' : 'P'; ?>
                                        </span>
                                    </td>

                                    <!-- 3. TTL -->
                                    <td class="hidden md:table-cell p-3">
                                        <div class="text-sm text-slate-700 font-medium"><?php echo $row['tempat_lahir']; ?></div>
                                        <div class="flex items-center gap-1.5 mt-1">
                                            <i class="fa-regular fa-calendar text-[10px] text-slate-400"></i>
                                            <span class="text-xs text-slate-500">
                                                <?php 
                                                    if($row['tanggal_lahir'] && $row['tanggal_lahir'] != '0000-00-00') {
                                                        echo formatDateMasehi($row['tanggal_lahir']);
                                                        echo " <span class='text-slate-400'>(" . getAge($row['tanggal_lahir']) . " thn)</span>";
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 4. Akademik -->
                                    <td class="hidden md:table-cell text-center p-3">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="text-sm font-semibold text-slate-700"><?php echo $row['mustawa']; ?></span>
                                            <span class="text-[10px] uppercase tracking-wide text-slate-400 mt-0.5 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                                                Mask. <?php 
                                                    $ang = $row['angkatan'];
                                                    if (is_numeric($ang)) {
                                                        $hijriYear = HijriDate::getYear("$ang-07-15");
                                                        echo "$ang <span class='text-slate-300 mx-1'>|</span> $hijriYear H"; 
                                                    } else {
                                                        echo $ang;
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 5. Asal -->
                                    <td class="hidden md:table-cell p-3">
                                        <?php
                                            // 1. Region
                                            $region = implode(', ', array_filter([$row['kabupaten'], $row['provinsi']]));
                                            if(empty($region)) $region = $row['asal']; // Fallback

                                            // 2. Address Logic
                                            $address_parts = array_filter([
                                                $row['alamat_lengkap'], 
                                                $row['kecamatan'] ? "Kec. ".$row['kecamatan'] : '',
                                                $row['kelurahan'] ? "Kel. ".$row['kelurahan'] : ''
                                            ]);
                                            $full_address = implode(', ', $address_parts);
                                            if(empty($full_address)) $full_address = '-';
                                        ?>
                                        
                                        <!-- Line 1: Region -->
                                        <div class="text-sm font-bold text-slate-700 leading-tight mb-1">
                                            <?php echo $region; ?>
                                        </div>

                                        <!-- Line 2: PPUI -->
                                        <?php if($row['asal_ppui']): ?>
                                            <div class="text-[11px] font-semibold text-blue-600 mb-1">
                                                PPUI <?php echo $row['asal_ppui']; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Line 3: Address (Clickable) -->
                                        <button type="button" class="group text-left w-full focus:outline-none" onclick='showAddress("<?php echo htmlspecialchars(addslashes($full_address), ENT_QUOTES); ?>")'>
                                            <div class="flex items-start gap-1.5 opacity-80 group-hover:opacity-100 transition-opacity">
                                                <i class="fa-solid fa-map-location-dot text-[10px] text-slate-400 mt-0.5 group-hover:text-blue-500"></i>
                                                <span class="text-xs text-slate-500 truncate max-w-[180px] group-hover:text-blue-600 border-b border-transparent group-hover:border-blue-300 pb-0.5 transition-all">
                                                    <?php echo $full_address; ?>
                                                </span>
                                            </div>
                                        </button>
                                    </td>

                                    <!-- 6. Ortu -->
                                    <td class="hidden md:table-cell p-3">
                                        <div class="flex flex-col gap-1">
                                            <div class="text-xs flex items-center justify-between">
                                                <span class="text-slate-400 w-8">Ayah</span>
                                                <span class="font-medium text-slate-700 truncate max-w-[100px]" title="<?php echo $row['nama_ayah'] ?? $row['ayah'] ?? ''; ?>"><?php echo $row['nama_ayah'] ?? $row['ayah'] ?? '-'; ?></span>
                                            </div>
                                            <div class="text-xs flex items-center justify-between">
                                                <span class="text-slate-400 w-8">Ibu</span>
                                                <span class="font-medium text-slate-700 truncate max-w-[100px]" title="<?php echo $row['nama_ibu'] ?? $row['ibu'] ?? ''; ?>"><?php echo $row['nama_ibu'] ?? $row['ibu'] ?? '-'; ?></span>
                                            </div>
                                            <?php if($row['wa_wali']): ?>
                                                <a href="https://wa.me/<?php echo formatWA($row['wa_wali']); ?>" target="_blank" class="inline-flex items-center gap-1.5 text-[10px] text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md mt-1 hover:bg-emerald-100 transition-colors w-fit self-center">
                                                    <i class="fa-brands fa-whatsapp"></i> 
                                                    <span class="font-medium font-mono"><?php echo formatPhoneNumberDisplay($row['wa_wali']); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- 7. Status -->
                                    <td class="hidden md:table-cell text-center p-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide <?php echo $statusClass; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>

                                    <!-- 8. Aksi -->
                                    <td class="text-center pl-3 pr-8 py-3">
                                        <div class="flex items-center justify-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                            <button onclick='viewStudent(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all shadow-sm cursor-pointer" title="Lihat Detail">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </button>
                                            
                                            <button onclick='editStudent(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-amber-600 hover:border-amber-200 hover:bg-amber-50 transition-all shadow-sm cursor-pointer" title="Edit Data">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </button>
                                            
                                            <button onclick="deleteStudent(<?php echo $row['id']; ?>)" 
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all shadow-sm cursor-pointer" title="Hapus">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <!-- Hidden Data for Filtering -->
                                    <td class="hidden"><?php echo $row['provinsi']; ?></td>
                                    <td class="hidden"><?php echo $row['kabupaten']; ?></td>
                                    <td class="hidden"><?php echo $row['asal_ppui']; ?></td>
                                    <td class="hidden"><?php echo $row['nim']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================ -->
                <!-- End of Main Table -->
                </div>
              </div> <!-- End Desktop Wrapper -->

            </div>
        </main>
    </div>

    <?php include 'includes/student_modals.php'; ?>

    <!-- Scroll to Top Button (Left Side) -->
    <button id="scrollToTopBtn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
        class="fixed bottom-6 left-6 w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg shadow-blue-500/30 flex items-center justify-center transform translate-y-20 opacity-0 transition-all duration-300 z-50 hover:bg-blue-700 hover:scale-110">
        <i class="fa-solid fa-arrow-up text-lg"></i>
    </button>
    
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/student_crud.js?v=<?php echo time(); ?>"></script>
    
    <script>
        var table; 
        
        // Toggle Logic for Drawer
        function toggleDrawer(button) {
            const container = button.parentElement;
            container.classList.toggle('drawer-open');
        }

        // Global Functions for UI Interaction
        window.toggleFilterSheet = function() {
            const sheet = document.getElementById('filterSection');
            const backdrop = document.getElementById('filterBackdrop');
            if (!sheet || !backdrop) return;
            
            const isHidden = sheet.classList.contains('translate-y-full');
            
            if (isHidden) {
                sheet.classList.remove('translate-y-full');
                backdrop.classList.remove('hidden');
            } else {
                sheet.classList.add('translate-y-full');
                backdrop.classList.add('hidden');
            }
        };

        window.closeFilterSheet = function() {
            const sheet = document.getElementById('filterSection');
            const backdrop = document.getElementById('filterBackdrop');
            if (sheet) sheet.classList.add('translate-y-full');
            if (backdrop) backdrop.classList.add('hidden');
        };

        window.toggleFilter = function() {
            const section = $('#filterSection');
            const btn = $('#filterToggleBtn');
            const icon = btn.find('.fa-chevron-down');
            
            const isExpanded = section.attr('data-expanded') === 'true';
            
            if (!isExpanded) {
                // OPEN
                section.addClass('!max-h-[800px] !opacity-100');
                section.attr('data-expanded', 'true');
                icon.addClass('rotate-180');
                btn.addClass('ring-2 ring-blue-500/20 border-blue-500 text-blue-600');
            } else {
                // CLOSE
                section.removeClass('!max-h-[800px] !opacity-100');
                section.attr('data-expanded', 'false');
                icon.removeClass('rotate-180');
                btn.removeClass('ring-2 ring-blue-500/20 border-blue-500 text-blue-600');
            }
        };

        // Fungsi Import Modal (Global)
        window.openImportModal = function() {
            Swal.fire({
                title: 'Smart Import Data',
                html: `
                    <div class="text-left text-sm text-slate-600 mb-4">
                        <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-2">
                            <p class="mb-0 font-semibold text-slate-700">Fitur Cerdas:</p>
                            <a href="api/download_template.php" class="text-[11px] font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded border border-emerald-200 transition-colors flex items-center gap-1">
                                <i class="fa-solid fa-download"></i> Template CSV
                            </a>
                        </div>
                        <ul class="list-disc pl-5 space-y-1 text-xs mb-3">
                            <li><strong>Auto-Detect Column:</strong> Urutan kolom bisa acak.</li>
                            <li><strong>Smart Address:</strong> Alamat panjang dipisah otomatis.</li>
                        </ul>
                    </div>
                    <input type="file" id="csv_file" class="w-full text-sm text-slate-500 border border-slate-300 rounded-lg cursor-pointer bg-slate-50" accept=".csv">
                `,
                showCancelButton: true,
                confirmButtonText: 'Mulai Import',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3b82f6',
                preConfirm: () => {
                    const fileInput = Swal.getPopup().querySelector('#csv_file');
                    if (!fileInput.files.length) {
                        Swal.showValidationMessage('Pilih file CSV dulu!');
                    }
                    return fileInput.files[0];
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('csv_file', result.value);
                    
                    Swal.fire({
                        title: 'Memproses...', 
                        text: 'Mohon tunggu...', 
                        didOpen: () => Swal.showLoading()
                    });

                    fetch('api/import_handler.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire('Selesai!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(e => Swal.fire('Error', e.toString(), 'error'));
                }
            });
        };

        $(document).ready(function () {
            // DataTables Initialization
            table = $('#mainTable').DataTable({
                responsive: false, 
                pageLength: 50,
                dom: 'rtip',    
                language: {
                    emptyTable: "Tidak ada data yang tersedia pada tabel ini",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                    infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
                    infoPostFix: "",
                    thousands: ".",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    loadingRecords: "Sedang memuat...",
                    processing: "Sedang memproses...",
                    search: "Cari:",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                ordering: true,
                order: [[12, 'asc']], // Default sort by Hidden NIM
                columnDefs: [
                   { orderable: false, targets: [8] }, // Aksi column
                   { className: "dt-head-center", targets: "_all" }
                ]
            });
            
            // Dynamic Row Numbering
            table.on('order.dt search.dt', function () {
                table.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
                    cell.innerHTML = i+1;
                });
            }).draw();
            window.table = table;

            // Sync Search Inputs
            $('#globalSearch').on('keyup', function() {
                $('#globalSearchMobile').val(this.value);
                filterTable();
            });
            $('#globalSearchMobile').on('keyup', function() {
                $('#globalSearch').val(this.value);
                filterTable();
            });

            // Smart Filter Listeners
            $('#filterGender, #filterMustawa, #filterAngkatan, #filterStatus, #filterPPUI, #filterProvinsi, #filterKabupaten').on('change', function() {
                filterTable();
            });

            // Filter Logic
            window.filterTable = function() {
               const gender = $('#filterGender').val();
               const mustawa = $('#filterMustawa').val();
               const angkatan = $('#filterAngkatan').val();
               const status = $('#filterStatus').val();
               const ppui = $('#filterPPUI').val();
               const provinsi = $('#filterProvinsi').val();
               const kabupaten = $('#filterKabupaten').val();
               const globalSearch = $('#globalSearch').val().toLowerCase();

               // Badge Update
               let active = 0;
               if(gender) active++; if(mustawa) active++; if(angkatan) active++; if(status) active++;
               if(ppui) active++; if(provinsi) active++; if(kabupaten) active++;

               if(active > 0) {
                   $('#filterBadge').text(active).removeClass('hidden');
                   $('#filterToggleBtn span').text(`Filter (${active})`);
               } else {
                   $('#filterBadge').addClass('hidden');
                   $('#filterToggleBtn span').text('Filter');
               }

               // 1. DataTables Search
               table.search(globalSearch);
               table.column(2).search(gender || '', true, false);
               
               // Mustawa/Angkatan
               let col4 = '';
               if (mustawa && angkatan) col4 = `(?=.*${mustawa})(?=.*${angkatan})`; 
               else if (mustawa) col4 = mustawa;
               else if (angkatan) col4 = angkatan;
               table.column(4).search(col4, true, false);

               table.column(7).search(status || '', true, false);
               table.column(9).search(provinsi ? `^${provinsi}$` : '', true, false);
               table.column(10).search(kabupaten ? `^${kabupaten}$` : '', true, false);
               table.column(11).search(ppui ? `^${ppui}$` : '', true, false);
               table.draw();
               
               // 2. Mobile Cards Filter
               const cards = document.querySelectorAll('.student-card-mobile');
               let visible = 0;
               cards.forEach(card => {
                   const cName = card.dataset.name.toLowerCase();
                   const cNim = card.dataset.nim;
                   const d = card.dataset;
                   
                   const matchSearch = !globalSearch || cName.includes(globalSearch) || cNim.includes(globalSearch);
                   const matchGen = !gender || d.gender === gender;
                   const matchMus = !mustawa || d.mustawa === mustawa;
                   const matchAng = !angkatan || d.angkatan == angkatan;
                   const matchStat = !status || d.status === status;
                   const matchPpui = !ppui || d.ppui === ppui.toLowerCase();
                   const matchProv = !provinsi || d.prov === provinsi.toLowerCase();
                   const matchKab = !kabupaten || d.kab === kabupaten.toLowerCase();

                   if(matchSearch && matchGen && matchMus && matchAng && matchStat && matchPpui && matchProv && matchKab) {
                       card.style.display = 'flex';
                       visible++;
                   } else {
                       card.style.display = 'none';
                   }
               });

               // Update Count
               const info = table.page.info();
               if(info) {
                   $('#totalRecords').text('Total: ' + info.recordsDisplay);
               }
            };
            
            // Init Filter
            window.filterTable();

            // Reset
            window.resetFilters = function() {
                $('#filterForm select').val(''); // Correct selector
                $('#globalSearch, #globalSearchMobile').val('');
                filterTable();
            };
            
            // Export Data
            window.exportData = function() {
                 const params = new URLSearchParams({
                    gender: $('#filterGender').val(),
                    status: $('#filterStatus').val(),
                    mustawa: $('#filterMustawa').val(),
                    angkatan: $('#filterAngkatan').val(),
                    ppui: $('#filterPPUI').val(),
                    provinsi: $('#filterProvinsi').val(),
                    kabupaten: $('#filterKabupaten').val()
                });
                
                Swal.fire({
                    title: 'Export Data',
                    html: `
                        <div class="flex gap-4 justify-center mt-2">
                             <a href="api/export_handler.php?format=csv&${params}" class="py-3 px-6 border rounded-xl hover:bg-emerald-50 border-emerald-200 text-emerald-600 font-bold block text-center">
                                <i class="fa-solid fa-file-csv text-2xl mb-1"></i><br>CSV
                             </a>
                             <a href="api/export_handler.php?format=xlsx&${params}" class="py-3 px-6 border rounded-xl hover:bg-green-50 border-green-200 text-green-600 font-bold block text-center">
                                <i class="fa-solid fa-file-excel text-2xl mb-1"></i><br>Excel
                             </a>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Tutup'
                });
            };
        });

        // View Address
        window.showAddress = function(val) {
             Swal.fire('Alamat', val, 'info');
        };

        // Scroll Top
        const scrollBtn = document.getElementById('scrollToTopBtn');
        if(scrollBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) scrollBtn.classList.remove('translate-y-20', 'opacity-0');
                else scrollBtn.classList.add('translate-y-20', 'opacity-0');
            });
        }
        
        // Orientation Refresh
        let lastOri = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
        window.addEventListener('resize', function() {
            const curOri = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
            if (curOri !== lastOri) {
                 location.reload();
            }
        });
    </script>
</body>
</html>