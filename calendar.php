<?php
require_once 'functions.php';
requireLogin();

$page_title = 'EduCal Pro';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .animate-in { animation: fadeIn 0.3s ease-out; }
        .slide-in { animation: slideDown 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
        /* @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); max-height: 0; } to { opacity: 1; transform: translateY(0); max-height: 200px; } } */
        
        @media print {
            body { background: white; color: black; }
            .no-print, header, aside, .sidebar-overlay, #eventModal { display: none !important; }
            main { padding: 0 !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
            .shadow-sm, .shadow-md, .shadow-lg { box-shadow: none !important; }
            .border { border-color: #e2e8f0 !important; }
            .print\:block { display: block !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans selection:bg-blue-100 selection:text-blue-900 h-screen overflow-hidden flex dark:bg-slate-900 dark:text-slate-100 dark:selection:bg-blue-900 dark:selection:text-blue-100">

    <input type="file" id="importInput" accept=".csv" class="hidden">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden relative transition-all duration-300">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8 print:w-full print:max-w-none print:px-0 print:py-0">
            
            <div class="max-w-7xl mx-auto">
                <!-- Header Actions -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 print:hidden">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-600 p-2.5 rounded-xl text-white shadow-lg shadow-blue-500/30">
                            <i data-lucide="book-open" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-none">EduCal Pro</h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Sistem Kalender Akademik</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center bg-white dark:bg-slate-800 rounded-lg p-1 border border-slate-200 dark:border-slate-700 shadow-sm">
                            <button onclick="changeYear(-1)" class="p-1 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-md text-slate-600 dark:text-slate-300 transition-colors"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                            <span id="currentYearDisplay" class="px-3 font-bold text-slate-700 dark:text-white tabular-nums"></span>
                            <button onclick="changeYear(1)" class="p-1 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-md text-slate-600 dark:text-slate-300 transition-colors"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
                        </div>
                        
                        <div class="flex gap-2">
                            <button onclick="document.getElementById('importInput').click()" title="Import CSV" class="p-2 text-slate-600 hover:bg-white hover:shadow-md rounded-lg border border-slate-200 hidden md:flex transition-all dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800 bg-white dark:bg-slate-800">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                            </button>
                            <button onclick="exportCSV()" title="Export CSV" class="p-2 text-slate-600 hover:bg-white hover:shadow-md rounded-lg border border-slate-200 hidden md:flex transition-all dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800 bg-white dark:bg-slate-800">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                            <button onclick="window.print()" class="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-slate-900/20 transition-all dark:bg-slate-700 dark:hover:bg-slate-600">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                                <span class="hidden md:inline">Cetak</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Print Header -->
                <div class="hidden print:block mb-6 text-center border-b pb-4">
                    <h1 class="text-3xl font-bold uppercase tracking-wider text-slate-900">Kalender Akademik <span id="printYear"></span></h1>
                    <p class="text-slate-500 mt-1">Nahwu &bull; Ilmu Pendidikan &bull; Hadits</p>
                </div>

                <!-- Stats Bar -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 print:hidden" id="statsContainer">
                    <!-- Stat Cards rendered by JS -->
                </div>

                <!-- Calendar Grid -->
                <div id="calendarGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 print:grid-cols-3 print:gap-4">
                    <!-- Months rendered by JS -->
                </div>

                <!-- Legend -->
                <div class="hidden print:flex mt-8 justify-center gap-6 text-xs text-slate-600 border-t pt-4">
                    <div id="legendContainer" class="flex items-center gap-6"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div id="eventModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center shrink-0">
                <div>
                    <h3 id="modalTitle" class="text-lg font-bold text-slate-800 dark:text-white"></h3>
                    <p id="modalSubtitle" class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-1 font-medium">
                        <i data-lucide="moon" class="w-3 h-3"></i> <span id="hijriDateDisplay"></span>
                    </p>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body Scrollable -->
            <div class="p-6 overflow-y-auto">
                
                <!-- Existing Events List -->
                <div id="existingEventsContainer" class="hidden mb-6 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-3 flex items-center gap-2">
                        <i data-lucide="list" class="w-3 h-3"></i> Agenda Terjadwal
                    </h4>
                    <div id="eventList" class="space-y-2">
                        <!-- Items rendered by JS -->
                    </div>
                </div>

                <!-- Form -->
                <form id="eventForm" class="space-y-4">
                    
                    <div class="flex items-center gap-2 mb-4">
                         <div class="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                         <span class="text-[10px] font-bold text-slate-400 uppercase">Tambah Baru</span>
                         <div class="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                    </div>

                    <!-- Mode Toggle -->
                    <div class="flex bg-slate-100 dark:bg-slate-700 p-1 rounded-lg">
                        <button type="button" onclick="setRangeMode(false)" id="btnModeSingle" class="flex-1 text-xs font-medium py-1.5 rounded-md transition-all shadow-sm bg-white text-blue-700 dark:bg-slate-600 dark:text-blue-300">Satu Hari</button>
                        <button type="button" onclick="setRangeMode(true)" id="btnModeRange" class="flex-1 text-xs font-medium py-1.5 rounded-md transition-all text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Rentang Tanggal</button>
                    </div>

                    <!-- Range Inputs -->
                    <div id="rangeInputs" class="hidden grid grid-cols-2 gap-3 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                        <div>
                            <label class="block text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">Dari</label>
                            <div id="rangeStartDisplay" class="text-sm font-medium text-slate-700 dark:text-white bg-white dark:bg-slate-800 px-2 py-1 rounded border border-blue-200 dark:border-blue-700"></div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">Sampai</label>
                            <input type="date" id="rangeEndDate" class="w-full text-sm font-medium text-slate-700 dark:text-white bg-white dark:bg-slate-800 px-2 py-1 rounded border border-blue-300 dark:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Judul Kegiatan</label>
                        <input type="text" id="eventTitle" placeholder="Contoh: UAS Nahwu, Rapat Dosen" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all font-sans text-slate-800 dark:text-white placeholder:text-slate-400">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Kategori</label>
                        <div class="grid grid-cols-2 gap-2" id="categoryGrid">
                            <!-- JS Rendered -->
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Catatan</label>
                        <textarea id="eventDesc" rows="2" placeholder="Detail ruangan, jam, dsb..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-sm text-slate-800 dark:text-white placeholder:text-slate-400"></textarea>
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white hover:bg-blue-700 rounded-lg font-medium shadow-md shadow-blue-600/20 transition-all hover:shadow-lg hover:shadow-blue-600/30 mt-2">
                        <i data-lucide="save" class="w-4 h-4"></i> <span id="btnSaveText">Simpan Agenda</span>
                    </button>
                    
                </form>
            </div>
        </div>
    </div>

    <!-- MAIN SCRIPT -->
    <script>
        const MONTHS = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        const DAYS = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
        
        // Priority: Higher number = render color takes precedence
        const CATEGORIES = {
            exam:     { id: 4, label: "Ujian (UTS/UAS)", color: "bg-purple-100 text-purple-700 border-purple-200", icon: "graduation-cap", priority: 5 },
            holiday:  { id: 2, label: "Libur Nasional / Cuti", color: "bg-red-100 text-red-700 border-red-200", icon: "coffee", priority: 4 },
            urgent:   { id: 5, label: "Penting / Deadline", color: "bg-amber-100 text-amber-700 border-amber-200", icon: "alert-circle", priority: 3 },
            academic: { id: 1, label: "Akademik / Kuliah", color: "bg-blue-100 text-blue-700 border-blue-200", icon: "book-open", priority: 2 },
            activity: { id: 3, label: "Kegiatan / Rapat", color: "bg-emerald-100 text-emerald-700 border-emerald-200", icon: "check-circle-2", priority: 1 }
        };

        // --- STATE ---
        let currentYear = new Date().getFullYear();
        let events = {}; // Key: "YYYY-M-D" -> Array of Objects [{id, title, category, desc, date}]
        let selectedDate = null;
        let isRangeMode = false;
        let selectedCategory = 'academic';

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            document.getElementById('currentYearDisplay').innerText = currentYear;
            document.getElementById('printYear').innerText = currentYear;
            
            renderCategoryButtons();
            renderLegend();
            renderStatsPlaceholder();
            
            fetchYearlyData();
            document.getElementById('importInput').addEventListener('change', handleFileImport);
        });

        // --- API & DATA HANDLING ---
        
        async function fetchYearlyData() {
            try {
                const res = await fetch(`api/agenda_yearly.php?year=${currentYear}`);
                if (!res.ok) throw new Error('Network error');
                const json = await res.json();
                
                events = {}; // Reset

                if (json.data && Array.isArray(json.data)) {
                    json.data.forEach(evt => {
                        const d = new Date(evt.start);
                        const key = formatDateKey(d);
                        const catKey = mapCategory(evt.extendedProps?.kategori);

                        if(!events[key]) events[key] = [];
                        
                        events[key].push({
                            id: evt.id,
                            title: evt.title,
                            description: evt.description,
                            category: catKey,
                            date: d
                        });
                    });
                }
                renderCalendar();
                updateStats();

            } catch (err) {
                console.error("Fetch Data Error:", err);
            }
        }

        function mapCategory(catName) {
            if (!catName) return 'activity';
            const low = catName.toLowerCase();
            if (low.includes('akademik') || low.includes('kuliah')) return 'academic';
            if (low.includes('libur') || low.includes('cuti')) return 'holiday';
            if (low.includes('ujian') || low.includes('uts') || low.includes('uas')) return 'exam';
            if (low.includes('rapat') || low.includes('kegiatan')) return 'activity';
            if (low.includes('penting') || low.includes('deadline')) return 'urgent';
            return 'activity';
        }

        async function saveEventToDb(payload) {
            const catId = CATEGORIES[payload.category]?.id || 3;
            const apiPayload = {
                judul: payload.title,
                deskripsi: payload.description,
                kategori_id: catId,
                tgl_mulai: formatDateKey(payload.date) + ' 08:00:00',
                tgl_selesai: formatDateKey(payload.date) + ' 09:00:00',
                is_full_day: 1,
                is_recurring: 0,
                tipe_kalender: 'masehi'
            };

            await fetch('api/agenda_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(apiPayload)
            });
        }

        async function deleteEventFromDb(id) {
            await fetch(`api/agenda_handler.php?id=${id}`, { method: 'DELETE' });
        }


        // --- LOGIC ---

        function changeYear(delta) {
            currentYear += delta;
            document.getElementById('currentYearDisplay').innerText = currentYear;
            document.getElementById('printYear').innerText = currentYear;
            fetchYearlyData();
        }

        function formatDateKey(date) {
            return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
        }

        function getHijriInfo(date) {
            try {
                const day = new Intl.DateTimeFormat('id-ID-u-ca-islamic', { day: 'numeric' }).format(date);
                const full = new Intl.DateTimeFormat('id-ID-u-ca-islamic', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
                return { day, full };
            } catch (e) {
                return { day: '', full: '' };
            }
        }

        function getHijriMonthName(date) {
             try {
                return new Intl.DateTimeFormat('id-ID-u-ca-islamic', { month: 'long' }).format(date);
            } catch (e) { return ''; }
        }

        function getDayColor(dayEvents, isToday, isWeekend) {
            if (dayEvents && dayEvents.length > 0) {
                // Find event with highest priority
                const topEvent = dayEvents.reduce((prev, current) => {
                    const prevPrio = CATEGORIES[prev.category]?.priority || 0;
                    const currPrio = CATEGORIES[current.category]?.priority || 0;
                    return (currPrio > prevPrio) ? current : prev;
                });
                return CATEGORIES[topEvent.category].color;
            }
            if (isToday) return "bg-slate-800 text-white shadow-lg"; 
            if (isWeekend) return "text-red-500 hover:bg-slate-50 dark:hover:bg-slate-700/50";
            return "text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700";
        }


        // --- RENDERING ---

        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            MONTHS.forEach((monthName, monthIdx) => {
                const daysInMonth = new Date(currentYear, monthIdx + 1, 0).getDate();
                const firstDay = new Date(currentYear, monthIdx, 1).getDay();

                // Hijri Header calculation
                const startM = new Date(currentYear, monthIdx, 1);
                const endM = new Date(currentYear, monthIdx, daysInMonth);
                const hStart = getHijriMonthName(startM);
                const hEnd = getHijriMonthName(endM);
                const hijriString = (hStart === hEnd) ? hStart : `${hStart} - ${hEnd}`;

                // Month Events for Summary
                let monthEvents = [];
                for(let d=1; d<=daysInMonth; d++) {
                    const k = formatDateKey(new Date(currentYear, monthIdx, d));
                    if(events[k]) monthEvents.push(...events[k]);
                }
                monthEvents.sort((a,b) => a.date - b.date);

                const card = document.createElement('div');
                card.className = 'bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow break-inside-avoid print:shadow-none print:border-slate-300 flex flex-col';
                
                // Month Header
                card.innerHTML = `
                    <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-3 border-b border-slate-100 dark:border-slate-700 flex flex-col justify-center items-center print:bg-slate-100 print:border-slate-300">
                        <div class="flex items-center gap-2">
                             <span class="font-bold text-slate-700 dark:text-white text-md">${monthName}</span>
                             <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">${currentYear}</span>
                        </div>
                        <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-600 dark:text-emerald-400 mt-0.5">
                            ${hijriString}
                        </div>
                    </div>
                `;

                // Days Header
                const daysHeader = document.createElement('div');
                daysHeader.className = 'grid grid-cols-7 px-2 py-2 bg-white dark:bg-slate-800';
                DAYS.forEach(day => {
                   const el = document.createElement('div');
                   el.className = `text-center text-[10px] font-bold uppercase tracking-wider ${day === 'Jum' ? 'text-red-500' : 'text-slate-400 dark:text-slate-500'}`;
                   el.innerText = day;
                   daysHeader.appendChild(el);
                });
                card.appendChild(daysHeader);

                // Days Grid
                const daysGrid = document.createElement('div');
                daysGrid.className = 'grid grid-cols-7 px-2 pb-4 gap-y-1';

                for(let i=0; i<firstDay; i++) daysGrid.appendChild(document.createElement('div'));

                for(let day=1; day <= daysInMonth; day++) {
                    const dateCtx = new Date(currentYear, monthIdx, day);
                    const key = formatDateKey(dateCtx);
                    const dayEvents = events[key] || [];
                    const hasEvent = dayEvents.length > 0;
                    const hijri = getHijriInfo(dateCtx);
                    const isToday = new Date().toDateString() === dateCtx.toDateString();
                    const isWeekend = dateCtx.getDay() === 5; // Jumat

                    const cellWrapper = document.createElement('div');
                    cellWrapper.className = 'relative flex justify-center p-[1px]';
                    
                    const btn = document.createElement('button');
                    let baseClass = "h-9 w-full flex flex-col items-center justify-center text-sm rounded-lg cursor-pointer transition-all relative group border border-transparent ";
                    let colorClass = getDayColor(dayEvents, isToday, isWeekend);
                    if (hasEvent) baseClass += "shadow-sm font-bold ";

                    btn.className = baseClass + colorClass;
                    btn.onclick = () => openModal(dateCtx);
                    
                    const countBadge = dayEvents.length > 1 
                        ? `<span class="absolute -top-1 -right-1 bg-amber-500 text-white text-[8px] w-3.5 h-3.5 flex items-center justify-center rounded-full shadow-sm z-10 font-bold border border-white dark:border-slate-800">${dayEvents.length}</span>` 
                        : '';

                    const hijriColor = (hasEvent || isToday) ? "text-white/70" : "text-emerald-500/80";

                    btn.innerHTML = `
                        ${countBadge}
                        <span class="absolute top-[2px] left-[3px] text-[7px] leading-none ${hijriColor} font-normal">${hijri.day}</span>
                        <span class="mt-1 leading-none">${day}</span>
                    `;

                    cellWrapper.appendChild(btn);
                    daysGrid.appendChild(cellWrapper);
                }
                card.appendChild(daysGrid);

                // Summary Footer
                const footer = document.createElement('div');
                footer.className = "mt-auto border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 print:block";
                const summaryId = `summary-${monthIdx}`;
                
                if (monthEvents.length > 0) {
                    footer.innerHTML = `
                        <button onclick="toggleSummary('${summaryId}')" class="w-full flex items-center justify-between px-4 py-2 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <span>Agenda (${monthEvents.length})</span>
                            <i data-lucide="chevron-down" class="w-3 h-3 transform transition-transform" id="icon-${summaryId}"></i>
                        </button>
                        <div id="${summaryId}" class="hidden px-4 pb-3">
                            <ul class="space-y-1.5">
                                ${monthEvents.map(evt => {
                                    const cat = CATEGORIES[evt.category];
                                    const bg = cat.color.split(' ')[0].replace('text-','bg-');
                                    return `
                                    <li class="flex gap-2 text-[10px] items-start dark:text-slate-300">
                                        <span class="shrink-0 w-1.5 h-1.5 rounded-full mt-1 ${bg}"></span>
                                        <div class="flex-1 min-w-0">
                                            <span class="font-bold text-slate-700 dark:text-white mr-1">${evt.date.getDate()}:</span>
                                            <span class="truncate">${evt.title}</span>
                                        </div>
                                    </li>`;
                                }).join('')}
                            </ul>
                        </div>
                    `;
                } else {
                     footer.innerHTML = `
                        <div class="px-4 py-2 text-[10px] text-slate-400 italic text-center">
                            Tidak ada agenda bulan ini.
                        </div>
                     `;
                }

                card.appendChild(footer);
                grid.appendChild(card);
            });
            lucide.createIcons();
        }

        function toggleSummary(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(`icon-${id}`);
            el.classList.toggle('hidden');
            if(el.classList.contains('hidden')) {
                icon.style.transform = 'rotate(0deg)';
            } else {
                icon.style.transform = 'rotate(180deg)';
                el.classList.remove('hidden');
                el.classList.add('animate-in', 'slide-in');
            }
        }

        function renderStatsPlaceholder() {
            const colors = { academic: 'blue', exam: 'purple', holiday: 'red', activity: 'emerald' };
            const container = document.getElementById('statsContainer');
            container.innerHTML = Object.entries(CATEGORIES).filter(([k]) => k!=='urgent').map(([key, cat]) => `
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center gap-4">
                    <div class="p-3 rounded-lg ${cat.color.split(' ')[0]} ${cat.color.split(' ')[1]}">
                        <i data-lucide="${cat.icon}" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p id="stat-${key}" class="text-2xl font-bold text-slate-800 dark:text-white">0</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wide">${cat.label.split('/')[0]}</p>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }

        function updateStats() {
            let counts = { academic: 0, holiday: 0, exam: 0, activity: 0, urgent: 0 };
            
            Object.values(events).flat().forEach(e => {
                if(e.date.getFullYear() === currentYear) {
                     if(counts[e.category] !== undefined) counts[e.category]++;
                }
            });

            document.getElementById('stat-academic').innerText = counts.academic;
            document.getElementById('stat-exam').innerText = counts.exam;
            document.getElementById('stat-holiday').innerText = counts.holiday;
            document.getElementById('stat-activity').innerText = counts.activity + counts.urgent;
        }

        function renderCategoryButtons() {
            const container = document.getElementById('categoryGrid');
            container.innerHTML = Object.entries(CATEGORIES).map(([key, cat]) => `
                <button type="button" 
                    onclick="selectCategory('${key}')" 
                    data-key="${key}"
                    class="cat-btn flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium border transition-all bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50">
                    <i data-lucide="${cat.icon}" class="w-3.5 h-3.5"></i> ${cat.label}
                </button>
            `).join('');
            lucide.createIcons();
        }

        function renderLegend() {
             const container = document.getElementById('legendContainer');
             container.innerHTML = Object.values(CATEGORIES).map(cat => {
                 const bgClass = cat.color.split(' ')[0].replace('text-', 'bg-');
                 return `<div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full border ${bgClass}"></div> 
                    <span>${cat.label.split(' /')[0]}</span>
                 </div>`;
             }).join('');
        }

        function selectCategory(key) {
            selectedCategory = key;
             document.querySelectorAll('.cat-btn').forEach(btn => {
                const k = btn.dataset.key;
                const style = CATEGORIES[k];
                if(k === key) {
                    btn.className = `cat-btn flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium border transition-all ${style.color} ring-1 ring-offset-1 ring-blue-500/20`;
                } else {
                    btn.className = `cat-btn flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium border transition-all bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50`;
                }
            });
        }

        // --- MODAL ---

        function openModal(date) {
            selectedDate = date;
            setRangeMode(false);
            
            const key = formatDateKey(date);
            const dayEvents = events[key] || [];
            const hijri = getHijriInfo(date);

            document.getElementById('modalTitle').innerText = date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            document.getElementById('hijriDateDisplay').innerText = hijri.full;

            // Render List Existing
            const listContainer = document.getElementById('existingEventsContainer');
            const listEl = document.getElementById('eventList');
            listEl.innerHTML = '';
            
            if (dayEvents.length > 0) {
                listContainer.classList.remove('hidden');
                dayEvents.forEach(evt => {
                    const cat = CATEGORIES[evt.category];
                    const div = document.createElement('div');
                    div.className = "flex items-start gap-3 p-2 bg-white dark:bg-slate-700 rounded-lg border border-slate-100 dark:border-slate-600 shadow-sm";
                    div.innerHTML = `
                        <div class="p-1.5 rounded-md ${cat.color}">
                            <i data-lucide="${cat.icon}" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">${evt.title}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">${cat.label}</p>
                            ${evt.description ? `<p class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">${evt.description}</p>` : ''}
                        </div>
                        <button type="button" onclick="deleteEvent('${evt.id}')" class="text-slate-300 hover:text-red-500 transition-colors p-1" title="Hapus">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    `;
                    listEl.appendChild(div);
                });
                lucide.createIcons();
            } else {
                listContainer.classList.add('hidden');
            }

            // Reset Form
            document.getElementById('eventTitle').value = '';
            document.getElementById('eventDesc').value = '';
            selectCategory('academic');
            
            document.getElementById('rangeStartDisplay').innerText = date.toLocaleDateString('id-ID');
            document.getElementById('rangeEndDate').min = date.toISOString().split('T')[0];
            document.getElementById('rangeEndDate').value = '';

            document.getElementById('eventModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('eventTitle').focus(), 100);
        }

        function closeModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }

        function setRangeMode(active) {
            isRangeMode = active;
            const btnSingle = document.getElementById('btnModeSingle');
            const btnRange = document.getElementById('btnModeRange');
            const box = document.getElementById('rangeInputs');
            const saveBtn = document.getElementById('btnSaveText');
            
            if(active) {
                btnSingle.className = "flex-1 text-xs font-medium py-1.5 rounded-md transition-all text-slate-500 hover:text-slate-700 dark:text-slate-400";
                btnRange.className = "flex-1 text-xs font-medium py-1.5 rounded-md transition-all shadow-sm bg-white text-blue-700 dark:bg-slate-600 dark:text-blue-300";
                box.classList.remove('hidden');
                saveBtn.innerText = "Simpan Agenda Massal";
                document.getElementById('modalTitle').innerText = "Tambah Agenda Massal";
                document.getElementById('existingEventsContainer').classList.add('hidden'); // Hide list in mass mode
            } else {
                btnSingle.className = "flex-1 text-xs font-medium py-1.5 rounded-md transition-all shadow-sm bg-white text-blue-700 dark:bg-slate-600 dark:text-blue-300";
                btnRange.className = "flex-1 text-xs font-medium py-1.5 rounded-md transition-all text-slate-500 hover:text-slate-700 dark:text-slate-400";
                box.classList.add('hidden');
                saveBtn.innerText = "Simpan Agenda";
                if(selectedDate) openModal(selectedDate); // Re-open to show list
            }
        }

        // --- FORM ACTION ---
        document.getElementById('eventForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = document.getElementById('eventTitle').value;
            const desc = document.getElementById('eventDesc').value;
            if(!title.trim()) return;

            // Loading state
            document.querySelector('#eventForm button[type="submit"]').innerHTML = 'Menyimpan...';

            if(isRangeMode) {
                const endVal = document.getElementById('rangeEndDate').value;
                if(!endVal) return alert("Pilih tanggal akhir");
                let cur = new Date(selectedDate);
                const end = new Date(endVal);
                while(cur <= end) {
                    await saveEventToDb({ title, description: desc, category: selectedCategory, date: new Date(cur) });
                    cur.setDate(cur.getDate() + 1);
                }
            } else {
                await saveEventToDb({ title, description: desc, category: selectedCategory, date: selectedDate });
            }

            // Reload
            closeModal();
            fetchYearlyData();
            document.querySelector('#eventForm button[type="submit"]').innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> <span id="btnSaveText">Simpan Agenda</span>';
        });

        async function deleteEvent(id) {
            if(confirm('Hapus agenda ini?')) {
                await deleteEventFromDb(id);
                // Refresh Modal List
                // We need to re-fetch data then re-render modal list. 
                // A bit heavy but safe.
                await fetchYearlyData();
                openModal(selectedDate);
            }
        }

        // --- CSV ---
        function exportCSV() {
            let csv = "data:text/csv;charset=utf-8,Tanggal,Judul,Kategori,Deskripsi\n";
            Object.values(events).flat().forEach(evt => {
                const d = evt.date;
                const isoDate = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                csv += `${isoDate},${evt.title.replace(/,/g," ")},${evt.category},${(evt.description||"").replace(/,/g," ")}\n`;
            });
            const link = document.createElement("a");
            link.setAttribute("href", encodeURI(csv));
            link.setAttribute("download", `EduCal_Data_${currentYear}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function handleFileImport(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async (event) => {
                const lines = event.target.result.split('\n');
                let count = 0;
                for (let i = 1; i < lines.length; i++) {
                    const line = lines[i].trim();
                     if(!line) continue;
                    const cols = line.split(',');
                    if(cols.length < 3) continue;
                    const d = new Date(cols[0]);
                    if(isNaN(d.getTime())) continue;
                    await saveEventToDb({ title: cols[1], category: (cols[2]||'academic').trim(), description: cols[3]||'', date: d });
                    count++;
                }
                alert(`Berhasil mengimpor ${count} agenda.`);
                fetchYearlyData();
                e.target.value = '';
            };
            reader.readAsText(file);
        }

    </script>
</body>
</html>
