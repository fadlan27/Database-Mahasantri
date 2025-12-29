<?php
// Ensure functions are loaded
require_once dirname(__DIR__) . '/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Helper for Active State
function isActive($page) {
    global $current_page;
    return $current_page == $page ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-400 hover:text-white hover:bg-white/5';
}

function isActiveGroup($pages) {
    global $current_page;
    return in_array($current_page, $pages);
}
?>
<!-- Mobile Overlay -->
<div id="sidebarOverlay" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden');" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden backdrop-blur-sm transition-all duration-300"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 bg-slate-900 text-slate-300 transition-all duration-300 transform -translate-x-full md:sticky md:top-0 md:h-screen md:translate-x-0 flex flex-col border-r border-white/5 shadow-2xl no-print font-sans overflow-hidden">
    
    <!-- BRAND HEADER -->
    <div class="h-24 flex items-center px-6 border-b border-white/5 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 relative overflow-hidden shrink-0">
        <!-- Glow Effect -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 via-teal-500 to-blue-500"></div>
        
        <div class="flex items-center gap-4 relative z-10 w-full">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-xl shadow-emerald-900/30 border border-white/10 shrink-0">
                <i class="fa-solid fa-mosque text-lg"></i>
            </div>
            <div class="flex flex-col">
                <h1 class="font-bold text-xl text-white tracking-tight font-heading">Jami'ah Abat</h1>
                <p class="text-[10px] uppercase tracking-widest text-emerald-500/90 font-bold mt-0.5">Database Pusat</p>
            </div>
            <!-- Close Button Mobile -->
            <button onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); document.getElementById('sidebarOverlay').classList.add('hidden');" class="md:hidden absolute right-0 text-slate-500 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
    </div>

    <!-- NAVIGATION SCROLL AREA -->
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
        
        <!-- SECTION: DASHBOARD -->
        <p class="px-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2 mt-1">Halaman Utama</p>
        
        <a href="index" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?php echo isActive('index.php'); ?>">
            <i class="fa-solid fa-chart-pie w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>
        
        <a href="master_aktif" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?php echo isActive('master_aktif.php'); ?>">
            <i class="fa-solid fa-address-book w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-medium text-sm">Direktori Mahasantri</span>
            <?php if(isActive('master_aktif.php') != 'text-slate-400 hover:text-white hover:bg-white/5'): ?>
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
            <?php endif; ?>
        </a>


        <!-- SECTION: MASTER DATA (Superadmin & Guru Only) -->
        <?php if(hasRole(['superadmin', 'guru'])): ?>
        <p class="px-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2 mt-8">Basis Data</p>

        <!-- DROPDOWN GROUP -->
        <div class="rounded-xl overflow-hidden transition-all duration-300 <?php echo isActiveGroup(['master_data.php', 'master_ikhwan.php', 'master_akhowat.php', 'master_lulus.php', 'master_do.php', 'tambah_santri.php']) ? 'bg-white/5' : ''; ?>">
            <button onclick="toggleMenu('masterMenu')" class="w-full flex items-center gap-3 px-4 py-3 text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-left group">
                <div class="w-5 flex justify-center"><i class="fa-solid fa-database group-hover:text-emerald-400 transition-colors"></i></div>
                <span class="flex-1 font-medium text-sm">Master Data</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 text-slate-500" id="chevronMaster"></i>
            </button>
            
            <div id="masterMenu" class="hidden space-y-0.5 pb-2 pt-1">
                <a href="master_data" class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] text-slate-400 hover:text-white transition-colors relative group">
                    <?php if($current_page == 'master_data.php') echo '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-emerald-500 rounded-r-full"></div>'; ?>
                    <span class="group-hover:translate-x-1 transition-transform">Tabel Semua Data</span>
                </a>
                <a href="tambah_santri" class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] text-slate-400 hover:text-emerald-300 transition-colors relative group">
                    <?php if($current_page == 'tambah_santri.php') echo '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-emerald-500 rounded-r-full"></div>'; ?>
                    <span class="group-hover:translate-x-1 transition-transform font-medium text-emerald-400">Operasi Massal</span>
                </a>
                <a href="master_ikhwan" class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] text-slate-400 hover:text-blue-300 transition-colors relative group">
                    <?php if($current_page == 'master_ikhwan.php') echo '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-blue-500 rounded-r-full"></div>'; ?>
                    <span class="group-hover:translate-x-1 transition-transform">Data Ikhwan</span>
                </a>
                <a href="master_akhowat" class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] text-slate-400 hover:text-pink-300 transition-colors relative group">
                    <?php if($current_page == 'master_akhowat.php') echo '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-pink-500 rounded-r-full"></div>'; ?>
                    <span class="group-hover:translate-x-1 transition-transform">Data Akhowat</span>
                </a>
                <a href="master_lulus" class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] text-slate-400 hover:text-amber-300 transition-colors relative group">
                    <?php if($current_page == 'master_lulus.php') echo '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-amber-500 rounded-r-full"></div>'; ?>
                    <span class="group-hover:translate-x-1 transition-transform">Data Alumni</span>
                </a>
                <a href="master_do" class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] text-slate-400 hover:text-red-300 transition-colors relative group">
                    <?php if($current_page == 'master_do.php') echo '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-red-500 rounded-r-full"></div>'; ?>
                    <span class="group-hover:translate-x-1 transition-transform">Arsip Non-Aktif</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- VIOLATIONS -->
        <a href="violations" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 mt-1 hover:bg-white/5 group <?php echo $current_page == 'violations.php' ? 'text-orange-400 bg-orange-500/10 border border-orange-500/20' : 'text-slate-400'; ?>">
            <i class="fa-solid fa-gavel w-5 text-center transition-transform group-hover:rotate-12"></i>
            <span class="font-medium text-sm">Pelanggaran</span>
        </a>

        <!-- AGENDA & CALENDAR -->
        <a href="calendar" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 mt-1 hover:bg-white/5 group <?php echo $current_page == 'calendar.php' ? 'text-indigo-400 bg-indigo-500/10 border border-indigo-500/20' : 'text-slate-400'; ?>">
            <i class="fa-solid fa-calendar-days w-5 text-center transition-transform group-hover:scale-110"></i>
            <span class="font-medium text-sm">Kalender & Agenda</span>
        </a>


        <!-- SECTION: ADMINISTRATION (Superadmin Only) -->
        <?php if(isSuperAdmin()): ?>
        <p class="px-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2 mt-8">Administrasi</p>

        <a href="maintenance" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 group <?php echo isActive('maintenance.php'); ?>">
            <i class="fa-solid fa-screwdriver-wrench w-5 text-center text-slate-500 group-hover:text-emerald-400 transition-colors"></i>
            <span class="font-medium text-sm">Maintenance</span>
        </a>
        
        <a href="users" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 group <?php echo isActive('users.php'); ?>">
            <i class="fa-solid fa-users-gear w-5 text-center text-slate-500 group-hover:text-emerald-400 transition-colors"></i>
            <span class="font-medium text-sm">Pengelola Admin</span>
        </a>

        <a href="db_manager" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 group <?php echo isActive('db_manager.php'); ?>">
            <i class="fa-solid fa-hard-drive w-5 text-center text-slate-500 group-hover:text-emerald-400 transition-colors"></i>
            <span class="font-medium text-sm">Database</span>
        </a>

        </a>
        
        <a href="log" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 group <?php echo isActive('log.php'); ?>">
            <i class="fa-solid fa-clock-rotate-left w-5 text-center text-slate-500 group-hover:text-emerald-400 transition-colors"></i>
            <span class="font-medium text-sm">Log Aktivitas</span>
        </a>

    </nav>
        <?php endif; ?>
    </nav>

    <!-- FOOTER PROFILE -->
    <div class="p-4 border-t border-white/5 bg-slate-950/30 flex items-center justify-between gap-2">
        <a href="profile" class="flex items-center gap-3 group px-2 py-2 rounded-xl hover:bg-white/5 transition-colors flex-1 overflow-hidden">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-slate-700 to-slate-600 flex items-center justify-center text-white shadow-md border border-white/10 group-hover:border-emerald-500/50 transition-colors shrink-0">
                <i class="fa-solid fa-user text-sm"></i>
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="text-sm font-bold text-white truncate group-hover:text-emerald-400 transition-colors"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></p>
                <p class="text-[10px] text-slate-500 truncate"><?php echo ucfirst(getCurrentRole() ?? 'Pengaturan'); ?></p>
            </div>
        </a>
        <a href="logout.php" class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:text-red-500 hover:bg-red-500/10 transition-colors" title="Keluar / Logout">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>

    <script>
        function toggleMenu(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('chevronMaster');
            el.classList.toggle('hidden');
            if(!el.classList.contains('hidden')) {
                icon.style.transform = 'rotate(180deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        // Auto expand logic
        <?php if(isActiveGroup(['master_data.php', 'master_ikhwan.php', 'master_akhowat.php', 'master_lulus.php', 'master_do.php', 'tambah_santri.php'])): ?>
            document.getElementById('masterMenu').classList.remove('hidden');
            document.getElementById('chevronMaster').style.transform = 'rotate(180deg)';
        <?php endif; ?>
    </script>
</aside>

<!-- Include Mobile Bottom Nav -->
<?php include 'bottom_nav.php'; ?>
