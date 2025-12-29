<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/includes/header.php
?>
<header class="h-16 bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-100 flex items-center justify-between px-6 z-20 no-print dark:bg-slate-900/80 dark:border-slate-800 transition-colors duration-300">
    <div class="flex items-center gap-4">
        <button id="mobile-menu-btn" class="text-gray-500 hover:text-blue-600 focus:outline-none md:hidden dark:text-gray-400 dark:hover:text-white transition-colors">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        <!-- Desktop Sidebar Toggle -->
        <button id="desktop-sidebar-toggle" class="hidden md:block text-slate-500 hover:text-blue-600 focus:outline-none transition-colors mr-3 dark:text-slate-400 dark:hover:text-white" title="Toggle Sidebar">
            <i class="fa-solid fa-bars-staggered text-xl"></i>
        </button>
        <div class="flex flex-col pl-2 md:pl-0">
            <h2 class="text-xl font-bold text-slate-800 font-sans tracking-tight dark:text-white leading-tight">
                Database Pusat Mahasantri <span class="font-light text-slate-500 dark:text-slate-400">Jami'ah ABAT Lampung</span>
            </h2>
            <p class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 tracking-wider mt-0.5">
               Tahun Akademik: <?php echo getAcademicYear(); ?> <span class="text-slate-300 dark:text-slate-600 mx-1">|</span> <?php echo getAcademicHijriRange(); ?>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <!-- Dark Mode Toggle -->
        <button id="theme-toggle" class="p-2 rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-yellow-400 transition-all focus:outline-none" title="Toggle Dark Mode">
            <i id="theme-icon" class="fa-solid fa-moon text-lg"></i>
        </button>

        <form action="master_data.php" method="GET" class="relative hidden md:block group">
            <input 
                type="text" 
                name="q"
                placeholder="Cari global..." 
                class="pl-10 pr-4 py-2 bg-slate-100 border border-transparent rounded-full text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 w-64 transition-all dark:bg-slate-800 dark:text-white dark:focus:bg-slate-900"
                value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
            >
            <i class="fa-solid fa-search absolute left-3 top-2.5 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
        </form>

        <div class="flex items-center gap-3 pl-2 border-l border-slate-200 dark:border-slate-700">
            <div class="text-right hidden md:block leading-tight">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?></p>
                <div class="flex items-center justify-end gap-1 relative group/status cursor-help">
                    <?php 
                        // Determine Status Display
                        $statusColor = 'bg-gray-500';
                        $statusText = 'OFFLINE';
                        $dbName = 'Unknown';
                        
                        if (defined('DB_MODE')) {
                            if (DB_MODE === 'ONLINE') {
                                $statusColor = 'bg-emerald-500';
                                $statusText = 'ONLINE';
                                $dbName = isset($online_db) ? $online_db : 'Cloud DB'; 
                            } else {
                                $statusColor = 'bg-amber-500';
                                $statusText = 'LOCAL';
                                $dbName = isset($local_db) ? $local_db : 'Localhost DB';
                            }
                        }
                    ?>
                    <span class="w-2 h-2 rounded-full <?php echo $statusColor; ?> animate-pulse"></span>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest font-bold"><?php echo $statusText; ?></p>
                    
                    <!-- Tooltip -->
                    <div class="absolute top-full right-0 mt-2 hidden group-hover/status:block px-3 py-2 bg-slate-800 text-white text-xs rounded-lg shadow-xl whitespace-nowrap z-50">
                        <p class="font-semibold text-gray-300">Active Database:</p>
                        <p class="font-mono text-emerald-400"><?php echo $dbName; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="relative">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-blue-700 font-bold border-2 border-white shadow-sm cursor-pointer hover:shadow-md transition-all dark:from-slate-700 dark:to-slate-800 dark:text-slate-300 dark:border-slate-600" onclick="document.getElementById('logout-menu').classList.toggle('hidden')">
                    <?php 
                        $initials = isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 2)) : 'UA';
                        echo $initials;
                    ?>
                </div>

                <!-- Dropdown Logout (Glassmorphism) -->
                <div id="logout-menu" class="hidden absolute top-12 right-0 bg-white/90 backdrop-blur-xl shadow-xl rounded-xl w-48 py-2 border border-slate-100 z-50 transform origin-top-right transition-all dark:bg-slate-900/90 dark:border-slate-700">
                    <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700 md:hidden">
                        <p class="text-sm font-semibold text-slate-800 dark:text-white"><?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?></p>
                    </div>
                    <a href="profile.php" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-400 transition-colors">
                        <i class="fa-solid fa-user-gear mr-2 w-5 text-center"></i> Profile
                    </a>
                    <a href="api/auth.php?logout=true" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                        <i class="fa-solid fa-sign-out-alt mr-2 w-5 text-center"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        sidebar.classList.toggle('-translate-x-full');
        
        if (overlay) {
            overlay.classList.toggle('hidden');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('logout-menu');
        const trigger = event.target.closest('.relative'); // The container of the avatar
        
        if (!trigger && !menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
        }
    });

    // Dark Mode Logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const html = document.documentElement;

    // Load saved theme
    function applyTheme() {
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        } else {
            html.classList.remove('dark');
            themeIcon.classList.replace('fa-sun', 'fa-moon');
        }
    }
    applyTheme();

    themeToggleBtn.addEventListener('click', function() {
        html.classList.toggle('dark');
        if (html.classList.contains('dark')) {
            localStorage.theme = 'dark';
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        } else {
            localStorage.theme = 'light';
            themeIcon.classList.replace('fa-sun', 'fa-moon');
        }
    });

    // Sidebar Toggle Logic (Desktop) - with Blur Refresh
    const desktopToggleBtn = document.getElementById('desktop-sidebar-toggle');

    if (desktopToggleBtn) {
        desktopToggleBtn.addEventListener('click', function() {
            // Toggle state in localStorage
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            localStorage.setItem('sidebarCollapsed', isCollapsed ? 'false' : 'true');
            
            // Blur transition
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                inset: 0;
                background: rgba(255,255,255,0.9);
                backdrop-filter: blur(10px);
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            overlay.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-4xl text-blue-500"></i>';
            document.body.appendChild(overlay);
            
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
            });
            
            setTimeout(() => {
                location.reload();
            }, 300);
        });
    }

</script>
