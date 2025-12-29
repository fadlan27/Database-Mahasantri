<?php
// c:/laragon/www/Database Mahasantri/includes/bottom_nav.php
// Mobile Bottom Navigation Bar
?>
<nav class="md:hidden fixed bottom-4 left-4 right-4 bg-white/90 backdrop-blur-xl border border-white/50 shadow-2xl rounded-2xl z-50 flex justify-around items-center h-16 dark:bg-slate-900/90 dark:border-slate-700 animate-slide-up no-print">
    
    <!-- Dashboard -->
    <a href="index.php" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-emerald-600 active:text-emerald-700 transition-colors group <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-emerald-600' : ''; ?>">
        <i class="fa-solid fa-house text-xl mb-0.5 group-hover:scale-110 transition-transform"></i>
        <span class="text-[10px] font-medium">Home</span>
    </a>

    <!-- Data Santri (Master Aktif) -->
    <a href="master_aktif.php" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-emerald-600 active:text-emerald-700 transition-colors group <?php echo basename($_SERVER['PHP_SELF']) == 'master_aktif.php' ? 'text-emerald-600' : ''; ?>">
        <i class="fa-solid fa-users text-xl mb-0.5 group-hover:scale-110 transition-transform"></i>
        <span class="text-[10px] font-medium">Santri</span>
    </a>

    <!-- CENTER ADD/SEARCH BUTTON (Optional - can be something else) -->
    <a href="master_data.php" class="relative -top-5 bg-emerald-600 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/40 hover:scale-105 active:scale-95 transition-all ring-4 ring-white dark:ring-slate-900">
        <i class="fa-solid fa-database text-lg"></i>
    </a>

    <!-- Pelanggaran -->
    <a href="violations.php" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-red-600 active:text-red-700 transition-colors group <?php echo basename($_SERVER['PHP_SELF']) == 'violations.php' ? 'text-red-600' : ''; ?>">
        <i class="fa-solid fa-triangle-exclamation text-xl mb-0.5 group-hover:scale-110 transition-transform"></i>
        <span class="text-[10px] font-medium">Sanksi</span>
    </a>

    <!-- Menu (Sidebar Toggle) -->
    <button id="bottom-nav-menu-btn" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-emerald-600 active:text-emerald-700 transition-colors group">
        <i class="fa-solid fa-bars text-xl mb-0.5 group-hover:scale-110 transition-transform"></i>
        <span class="text-[10px] font-medium">Menu</span>
    </button>

</nav>

<style>
    @keyframes slide-up {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .animate-slide-up {
        animation: slide-up 0.3s ease-out forwards;
    }
</style>

<script>
    // Logic to toggle Sidebar from Bottom Nav
    document.getElementById('bottom-nav-menu-btn').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        // Use the same classes as sidebar.php logic
        sidebar.classList.toggle('-translate-x-full');
        
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    });

    // Auto-Add Padding to Body on Mobile to prevent content being hidden behind nav
    if (window.innerWidth < 768) {
        document.body.classList.add('pb-24'); // Add bottom padding
    }
</script>
