<?php
require_once 'config/database.php';
require_once 'functions.php';
requireLogin();
requireRole('superadmin');

$page = 'db_manager.php';
$message = '';
$message_type = ''; // success, error
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// --- HELPER FUNCTIONS ---
function showMsg($msg, $type='success') {
    global $message, $message_type;
    $message = $msg;
    $message_type = $type;
}

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 0. CSRF CHECK
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        showMsg("Invalid CSRF Token!", "error");
        // Stop execution of actions
        $action = ''; 
    } else {
        $action = $_POST['action'] ?? '';
    }

    // 1. UPDATE SINGLE ROW
    if ($action === 'update_row') {
        try {
            $table = clean($_POST['table']);
            $id_column = clean($_POST['id_column']);
            $id_value = $_POST['id_value']; // Can be string or int
            
            $updates = [];
            $params = [];
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'col_') === 0) {
                    $col_name = substr($key, 4);
                    $updates[] = "`$col_name` = ?";
                    $params[] = $value === '' ? null : $value;
                }
            }
            
            if (!empty($updates)) {
                $params[] = $id_value; // For WHERE clause
                $sql = "UPDATE `$table` SET " . implode(', ', $updates) . " WHERE `$id_column` = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                showMsg("Data successfully updated!", "success");
            }
        } catch (PDOException $e) {
            showMsg("Update Failed: " . $e->getMessage(), "error");
        }
    }

    // 2. MASS REPLACE
    if ($action === 'mass_replace') {
        try {
            $table = clean($_POST['table']);
            $column = clean($_POST['column']);
            $find = $_POST['find_str'];
            $replace = $_POST['replace_str'];

            // Safe Replace
            $sql = "UPDATE `$table` SET `$column` = REPLACE(`$column`, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$find, $replace]);
            
            $count = $stmt->rowCount();
            showMsg("Mass replace executed. Affected rows: $count", "success");
        } catch (PDOException $e) {
            showMsg("Replace Failed: " . $e->getMessage(), "error");
        }
    }

    // 3. RAW SQL (Secured)
    if ($action === 'run_sql') {
        $sql = $_POST['sql_query'];
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        
        // 1. Verify Password
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $real_hash = $stmt->fetchColumn();
        
        if (!password_verify($password_confirmation, $real_hash)) {
            showMsg("Password Salah! Akses Ditolak.", "error");
        } else {
            try {
                if (stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0) {
                   // Read-only logic is handled in view...
                } else {
                    $rows = $pdo->exec($sql);
                    showMsg("SQL executed. Affected rows: $rows", "success");
                }
            } catch (PDOException $e) {
                showMsg("SQL Error: " . $e->getMessage(), "error");
            }
        }
    }
}

// --- DATA FETCHING ---
$active_table = isset($_GET['table']) ? clean($_GET['table']) : ($tables[0] ?? '');
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$columns = [];
$rows = [];
$pk = 'id'; // Default PK assumption

if ($active_table) {
    try {
        // Get Columns & PK
        $stmt = $pdo->query("SHOW COLUMNS FROM `$active_table`");
        $cols_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols_info as $c) {
            $columns[] = $c['Field'];
            if ($c['Key'] === 'PRI') $pk = $c['Field'];
        }

        // Build Query
        $sql = "SELECT * FROM `$active_table`";
        $params = [];

        if ($search_query) {
            $sql .= " WHERE ";
            $conditions = [];
            foreach ($columns as $col) {
                $conditions[] = "`$col` LIKE ?";
                $params[] = "%$search_query%";
            }
            $sql .= implode(' OR ', $conditions);
        }

        $sql .= " LIMIT 200"; // Limit for performance

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        showMsg("Error loading table: " . $e->getMessage(), "error");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    <script>if(localStorage.getItem('sidebarCollapsed')==='true'){document.write('<style>#sidebar{display:none!important}</style>');}</script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }

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
<body class="text-slate-800 dark:text-slate-200 font-sans antialiased h-screen overflow-hidden flex">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex-1 overflow-hidden p-4 md:p-6 flex flex-col gap-6">
            
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shrink-0 px-2">
                <div>
                    <h1 class="text-3xl font-bold font-heading bg-gradient-to-r from-indigo-600 to-violet-600 dark:from-indigo-400 dark:to-violet-400 bg-clip-text text-transparent flex items-center gap-3">
                        <i class="fa-solid fa-server text-indigo-500"></i> Database Manager
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Panel Kontrol Data Terpusat</p>
                </div>
                
                <div class="flex gap-3">
                    <a href="restore.php" class="px-5 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-bold hover:bg-orange-700 transition-all shadow-lg shadow-orange-500/20 hover:shadow-xl hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Restore DB
                    </a>
                     <button onclick="document.getElementById('sqlModal').classList.remove('hidden')" class="px-5 py-2.5 bg-slate-800 dark:bg-slate-700 text-white rounded-xl text-sm font-bold hover:bg-slate-900 dark:hover:bg-slate-600 transition-all shadow-lg shadow-slate-900/10 hover:shadow-xl hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fa-solid fa-terminal text-green-400"></i> SQL Runner
                    </button>
                    <button onclick="document.getElementById('replaceModal').classList.remove('hidden')" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl text-sm font-bold hover:from-indigo-700 hover:to-violet-700 transition-all shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fa-solid fa-right-left"></i> Mass Replace
                    </button>
                </div>
            </div>

            <!-- ALERT MESSAGE -->
            <?php if ($message): ?>
            <div class="mx-2 p-4 rounded-xl text-sm font-bold flex items-center animate-fade-in-up <?php echo $message_type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm shadow-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-red-50 text-red-700 border border-red-200 shadow-sm shadow-red-100 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'; ?>">
                <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 <?php echo $message_type === 'success' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-300' : 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-300'; ?>">
                    <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-check' : 'fa-triangle-exclamation'; ?>"></i>
                </div>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- MAIN WORKSPACE -->
            <div class="flex-1 flex gap-6 overflow-hidden pb-2">
                
                <!-- SIDEBAR: TABLES -->
                <div class="w-72 glass-card rounded-2xl flex flex-col shrink-0 overflow-hidden border border-white/50 dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/80 backdrop-blur-sm">
                        <h3 class="font-bold text-slate-700 dark:text-slate-200 text-xs uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-database text-indigo-500"></i> Tabel Database
                        </h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-3 space-y-1 custom-scrollbar">
                        <?php foreach($tables as $t): ?>
                            <a href="?table=<?php echo $t; ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 border border-transparent <?php echo $active_table === $t ? 'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800/50 shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-white dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200'; ?>">
                                <i class="fa-solid fa-table w-4 opacity-70"></i> <?php echo $t; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CONTENT: TABLE DATA -->
                <div class="flex-1 glass-card rounded-2xl flex flex-col overflow-hidden border border-white/50 dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none">
                    
                    <!-- Search Toolbar -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/30 backdrop-blur-sm">
                        <div class="flex items-center gap-3 text-sm">
                             <span class="px-3 py-1 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 font-bold font-mono">
                                <?php echo $active_table; ?>
                             </span>
                             <span class="text-slate-400 text-xs font-medium uppercase tracking-wider"><?php echo count($rows); ?> Rows</span>
                        </div>
                        <form method="GET" class="flex-1 max-w-md relative group">
                            <input type="hidden" name="table" value="<?php echo $active_table; ?>">
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Cari data di semua kolom..." 
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm group-hover:shadow-md">
                            <i class="fa-solid fa-search absolute left-3.5 top-3 text-slate-400 group-hover:text-indigo-500 transition-colors"></i>
                        </form>
                    </div>

                    <!-- Table Wrapper -->
                    <div class="flex-1 overflow-auto custom-scrollbar bg-white/30 dark:bg-slate-900/30">
                        <table class="w-full text-sm text-left whitespace-nowrap">
                            <thead class="text-xs uppercase bg-slate-50/90 dark:bg-slate-800/90 text-slate-500 dark:text-slate-400 sticky top-0 z-10 backdrop-blur-md shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 text-center w-16 bg-slate-50/95 dark:bg-slate-800/95">Aksi</th>
                                    <?php foreach($columns as $col): ?>
                                        <th class="px-6 py-3 border-b border-slate-200 dark:border-slate-700 font-bold tracking-wider hover:text-indigo-600 transition-colors cursor-default"><?php echo $col; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-slate-700 dark:text-slate-300">
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="<?php echo count($columns) + 1; ?>" class="px-6 py-20 text-center text-slate-400">
                                            <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fa-solid fa-database text-3xl opacity-30"></i>
                                            </div>
                                            <p class="font-medium">Data kosong atau tidak ditemukan.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($rows as $row): ?>
                                    <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors group">
                                        <td class="px-2 py-2 text-center border-r border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/50 sticky left-0 z-10 backdrop-blur-[1px]">
                                            <button onclick='openEditModal(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-white hover:shadow-sm dark:hover:bg-slate-700 transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-600" title="Edit Baris">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        </td>
                                        <?php foreach($row as $key => $val): ?>
                                            <td class="px-6 py-3 border-r border-slate-100/50 dark:border-slate-700/30 max-w-xs truncate font-mono text-xs" title="<?php echo htmlspecialchars($val); ?>">
                                                <?php echo htmlspecialchars(mb_strimwidth($val, 0, 50, "...")); ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 1. EDIT ROW MODAL -->
    <div id="editModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('editModal')"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white dark:bg-slate-800 shadow-2xl transform transition-transform duration-300 translate-x-full flex flex-col" id="editModalPanel">
            
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Edit Data</h3>
                <button onclick="closeModal('editModal')" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <form method="POST" class="flex-1 flex flex-col overflow-hidden">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                <input type="hidden" name="action" value="update_row">
                <input type="hidden" name="table" value="<?php echo $active_table; ?>">
                <input type="hidden" name="id_column" value="<?php echo $pk; ?>">
                <input type="hidden" name="id_value" id="editIdValue">

                <div class="flex-1 overflow-y-auto p-6 space-y-4" id="editFieldsContainer">
                    <!-- Fields injected by JS -->
                </div>

                <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                    <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition-all transform hover:scale-[1.02]">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. MASS REPLACE MODAL -->
    <div id="replaceModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('replaceModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100">
            <div class="p-6 bg-gradient-to-r from-indigo-600 to-violet-600 text-white">
                <h3 class="text-xl font-bold">Mass Find & Replace</h3>
                <p class="text-indigo-100 text-sm mt-1">Hati-hati, aksi ini akan mengubah banyak data sekaligus.</p>
            </div>
            
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                <input type="hidden" name="action" value="mass_replace">
                <input type="hidden" name="table" value="<?php echo $active_table; ?>">
                
                <div>
                     <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Target Kolom</label>
                     <select name="column" class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 custom-select">
                        <?php foreach($columns as $col): ?>
                            <option value="<?php echo $col; ?>"><?php echo $col; ?></option>
                        <?php endforeach; ?>
                     </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari Teks</label>
                        <input type="text" name="find_str" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Jakarta">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ganti Dengan</label>
                        <input type="text" name="replace_str" required class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Jaksel">
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('replaceModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 font-medium transition">Batal</button>
                    <button type="submit" onclick="return confirm('Yakin ingin mengganti data secara massal?')" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-500/20 transition">
                        Eksekusi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. SQL RUNNER MODAL -->
    <div id="sqlModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('sqlModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[80vh]">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 dark:text-white"><i class="fa-solid fa-code mr-2"></i> SQL Query Runner</h3>
                <button type="button" onclick="document.getElementById('sqlModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-times"></i></button>
            </div>
            <form method="POST" class="flex-1 flex flex-col p-0">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                <input type="hidden" name="action" value="run_sql">
                <textarea name="sql_query" class="flex-1 p-4 font-mono text-sm bg-slate-900 text-green-400 focus:outline-none resize-none" placeholder="Isi perintah SQL disini..."></textarea>
                <div class="p-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex flex-col gap-3">
                    <div class="flex justify-between items-center w-full">
                        <span class="text-xs text-red-500 font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Advanced Use Only</span>
                    </div>
                    <div class="flex gap-2 w-full">
                        <input type="password" name="password_confirmation" id="sqlRunnerPass" class="flex-1 px-4 py-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-red-500" placeholder="Verifikasi Password Admin" required>
                        <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold transition">Eksekusi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const editModal = document.getElementById('editModal');
        const editModalPanel = document.getElementById('editModalPanel');
        const editFieldsContainer = document.getElementById('editFieldsContainer');
        const editIdValue = document.getElementById('editIdValue');

        function openEditModal(rowData) {
            // Reset fields
            editFieldsContainer.innerHTML = '';
            editIdValue.value = rowData['<?php echo $pk; ?>'];

            // Populate fields
            for (const key in rowData) {
                // Skip ID column in display if strictly needed, but better to show it as disabled
                const isPk = key === '<?php echo $pk; ?>';
                let val = rowData[key];
                
                // Handle null/undefined/numbers
                if (val === null || val === undefined) {
                    val = '';
                } else {
                    val = String(val); // Convert numbers to string
                }
                
                const div = document.createElement('div');
                div.innerHTML = `
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">${key}</label>
                    <input type="text" name="col_${key}" value="${val.replace(/"/g, '&quot;')}" 
                        class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none text-sm dark:text-white ${isPk ? 'opacity-50 cursor-not-allowed' : ''}" 
                        ${isPk ? 'readonly' : ''}>
                `;
                editFieldsContainer.appendChild(div);
            }

            editModal.classList.remove('hidden');
            setTimeout(() => {
                editModalPanel.classList.remove('translate-x-full');
            }, 10);
        }

        function closeModal(id) {
            if (id === 'editModal') {
                editModalPanel.classList.add('translate-x-full');
                setTimeout(() => {
                    editModal.classList.add('hidden');
                }, 300);
            } else {
                document.getElementById(id).classList.add('hidden');
            }
        }
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
