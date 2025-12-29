<?php
require_once 'functions.php';
requireLogin();
requireRole('superadmin');

$page_title = 'Log Aktivitas';

// Pagination Setup
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    // Count Total
    $totalLogs = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
    $totalPages = ceil($totalLogs / $limit);

    // Fetch Logs
    $stmt = $pdo->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $logs = [];
    $totalLogs = 0;
    $totalPages = 1;
}

function getActionBadge($action) {
    if (stripos($action, 'Tambah') !== false) return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (stripos($action, 'Hapus') !== false) return 'bg-red-100 text-red-700 border-red-200';
    if (stripos($action, 'Update') !== false) return 'bg-blue-100 text-blue-700 border-blue-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        body { background-color: #f8fafc; }
        .dark body { background-color: #0f172a; }
    </style>
</head>
<body class="text-slate-800 dark:text-slate-200 font-sans antialiased h-screen overflow-hidden flex">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex-1 overflow-hidden p-6 flex flex-col">
            
            <div class="flex justify-between items-center mb-6 shrink-0">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-slate-800 dark:text-white flex items-center gap-3">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i> Log Aktivitas
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Total: <b><?php echo number_format($totalLogs); ?></b> aktivitas tercatat.
                    </p>
                </div>
                <div class="flex gap-2">
                    <button onclick="location.reload()" class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition">
                        <i class="fa-solid fa-rotate-right"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="flex-1 overflow-auto bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm custom-scrollbar">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-700/50 sticky top-0 z-10 backdrop-blur-sm">
                        <tr>
                            <th class="px-6 py-3 border-b dark:border-slate-700 w-40">Waktu</th>
                            <th class="px-6 py-3 border-b dark:border-slate-700 w-48">User</th>
                            <th class="px-6 py-3 border-b dark:border-slate-700 w-auto">Aksi</th>
                            <th class="px-6 py-3 border-b dark:border-slate-700">Detail Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                    <div class="mb-3"><i class="fa-regular fa-clipboard text-4xl opacity-50"></i></div>
                                    Belum ada data aktivitas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-3 font-mono text-xs text-slate-500 whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($log['username']); ?></div>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold border <?php echo getActionBadge($log['action']); ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="mt-4 flex justify-between items-center shrink-0 p-1">
                <span class="text-xs text-slate-500">
                    Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
                </span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">Prev</a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
    
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
