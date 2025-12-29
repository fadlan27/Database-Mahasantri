<?php
// violations.php
require_once 'functions.php';
requireLogin();

// 1. Fetch Summary Data (Grouped by Student) using Aggregation
// We list students who have violations.
$query = "SELECT 
            m.id as student_id, m.nama, m.nim, m.photo_path, m.gender, m.angkatan, 
            m.tempat_lahir, m.tanggal_lahir, m.kabupaten, m.provinsi, m.asal, m.mustawa, m.asal_ppui,
            m.alamat_lengkap, m.kecamatan, m.kelurahan,
            m.nama_ayah, m.wa_wali,
            COUNT(CASE WHEN p.jenis = 'Ringan' THEN 1 END) as count_ringan,
            COUNT(CASE WHEN p.jenis = 'Sedang' THEN 1 END) as count_sedang,
            COUNT(CASE WHEN p.jenis = 'Berat' THEN 1 END) as count_berat,
            MAX(p.tanggal) as last_violation_date,
            GROUP_CONCAT(p.sanksi SEPARATOR ' | ') as all_sanksi_text,
            GROUP_CONCAT(p.tingkat_sanksi) as all_levels
          FROM mahasantri m
          JOIN pelanggaran p ON m.id = p.mahasantri_id
          WHERE m.status = 'Aktif'
          GROUP BY m.id
          ORDER BY last_violation_date DESC";

$stmt = $pdo->query($query);
$pocket_book = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Active Students for the "Add New" Dropdown
$all_students = $pdo->query("SELECT id, nama, nim FROM mahasantri WHERE status = 'Aktif' ORDER BY nama ASC")->fetchAll();

// Helper to calculate Age
function calculateAge($dob) {
    if (!$dob || $dob == '0000-00-00') return '-';
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age . ' Thn';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    
    <!-- DataTables & jQuery -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        body { background-color: #f1f5f9; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="text-slate-600 font-sans antialiased h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                    <div>
                         <h1 class="text-2xl font-bold text-slate-800">Buku Saku Pelanggaran</h1>
                         <p class="text-slate-500 text-sm mt-1">Rekapitulasi detail kasus santri aktif.</p>
                    </div>
                    
                    <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                        <!-- Search Box -->
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fa-solid fa-search text-slate-400"></i>
                            </span>
                            <input type="text" id="searchInput" onkeyup="filterCards()" placeholder="Cari Nama / NIM..." class="pl-10 pr-4 py-2.5 rounded-xl border-slate-200 focus:ring-red-500 focus:border-red-500 w-full md:w-64 text-sm shadow-sm transition-all hover:bg-white hover:border-slate-300">
                        </div>

                        <!-- Class Filter -->
                        <select id="classFilter" onchange="filterCards()" class="py-2.5 rounded-xl border-slate-200 focus:ring-red-500 focus:border-red-500 text-sm shadow-sm w-full md:w-48 text-slate-600 font-medium cursor-pointer hover:bg-white hover:border-slate-300">
                            <option value="">Semua Kelas</option>
                            <?php
                            // Get Distinct Classes for Filter
                            $classes = $pdo->query("SELECT DISTINCT mustawa FROM mahasantri WHERE status='Aktif' AND mustawa IS NOT NULL ORDER BY mustawa ASC")->fetchAll(PDO::FETCH_COLUMN);
                            foreach($classes as $cls) {
                                echo "<option value='".htmlspecialchars($cls)."'>".htmlspecialchars($cls)."</option>";
                            }
                            ?>
                        </select>

                        <button onclick="openAddModal()" class="px-5 py-2.5 bg-red-600 text-white text-sm font-bold rounded-xl hover:bg-red-700 shadow-lg shadow-red-200 transition-all flex items-center justify-center transform hover:scale-105">
                            <i class="fa-solid fa-plus mr-2"></i> Input
                        </button>
                    </div>
                </div>

                <!-- Grid / List Layout for Pocket Book -->
                <div id="cardGrid" class="grid grid-cols-1 gap-4 pb-20">
                    <?php if(empty($pocket_book)): ?>
                        <div class="text-center py-20 bg-white rounded-xl shadow-sm">
                            <i class="fa-solid fa-check-circle text-6xl text-emerald-100 mb-4"></i>
                            <h3 class="text-lg font-bold text-slate-600">Alhamdulillah!</h3>
                            <p class="text-slate-400">Belum ada data pelanggaran tercatat.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($pocket_book as $row): 
                            $age = calculateAge($row['tanggal_lahir']);
                            
                            // Address Logic
                            $ttl = ($row['tempat_lahir'] ?: 'Unknown') . ', ' . ($row['tanggal_lahir'] ? date('d M Y', strtotime($row['tanggal_lahir'])) : '-');
                            $addressParts = [];
                            if ($row['alamat_lengkap']) $addressParts[] = $row['alamat_lengkap'];
                            if ($row['kelurahan']) $addressParts[] = "Kel. " . $row['kelurahan'];
                            if ($row['kecamatan']) $addressParts[] = "Kec. " . $row['kecamatan'];
                            if ($row['kabupaten']) $addressParts[] = $row['kabupaten'];
                            if ($row['provinsi']) $addressParts[] = $row['provinsi'];
                            $fullAddress = implode(', ', $addressParts) ?: 'Alamat belum lengkap';

                            $photo = !empty($row['photo_path']) && file_exists($row['photo_path']) ? $row['photo_path'] : '';
                            $bgColor = ($row['gender'] == 'Ikhwan') ? 'bg-blue-50' : 'bg-pink-50';
                            $borderColor = ($row['gender'] == 'Ikhwan') ? 'border-blue-100' : 'border-pink-100';
                            
                            // Smart Status Badge Logic (Based on Structured Level)
                            $statusBadge = '';
                            $levels = $row['all_levels'] ?? '';
                            
                            if (strpos($levels, 'DO') !== false) {
                                $statusBadge = '<span class="px-3 py-1 bg-slate-900 text-white text-xs font-bold rounded-full shadow-lg shadow-slate-300 animate-pulse border-2 border-slate-700">⛔ STATUS: DO / KELUAR</span>';
                            } elseif (strpos($levels, 'SP3') !== false) {
                                $statusBadge = '<span class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-full shadow-md shadow-red-200 border border-white">⚠️ PERINGATAN KERAS (SP3)</span>';
                            } elseif (strpos($levels, 'SP2') !== false) {
                                $statusBadge = '<span class="px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full shadow-sm border border-white">⚠️ PERINGATAN 2 (SP2)</span>';
                            } elseif (strpos($levels, 'SP1') !== false) {
                                $statusBadge = '<span class="px-3 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full shadow-sm border border-yellow-200">⚠️ PERINGATAN 1 (SP1)</span>';
                            }
                        ?>
                        <!-- Card Item -->
                        <div class="check-item bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-all flex flex-col md:flex-row" 
                             data-name="<?php echo strtolower($row['nama']); ?>" 
                             data-nim="<?php echo $row['nim']; ?>"
                             data-class="<?php echo $row['mustawa']; ?>">
                            
                            <!-- Left: Identity -->
                            <div class="p-5 md:w-5/12 flex items-start gap-4 <?php echo $bgColor; ?> border-r <?php echo $borderColor; ?>">
                                <div class="w-20 h-20 rounded-xl bg-white border-2 border-white shadow-sm overflow-hidden shrink-0">
                                    <?php if($photo): ?>
                                        <img src="<?php echo $photo; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-3xl"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0 space-y-1">
                                    <h3 class="font-bold text-slate-800 text-lg leading-tight truncate"><?php echo $row['nama']; ?></h3>
                                    <p class="text-xs text-slate-500">
                                        <?php echo $row['gender'] == 'Ikhwan' ? 'Bin' : 'Binti'; ?> <span class="font-bold text-slate-700"><?php echo $row['nama_ayah'] ?: '-'; ?></span> | <span class="font-mono text-slate-600"><?php echo $age; ?></span>
                                    </p>
                                    
                                    <div class="text-xs text-slate-600 mt-2 space-y-1">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-cake-candles text-pink-400 w-4 text-center"></i> 
                                            <span class="truncate"><?php echo $ttl; ?></span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <i class="fa-solid fa-location-dot text-blue-400 w-4 text-center mt-0.5"></i>
                                            <span class="leading-relaxed line-clamp-2" title="<?php echo htmlspecialchars($fullAddress); ?>"><?php echo $fullAddress; ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-school text-indigo-400 w-4 text-center"></i>
                                            <span class="font-medium text-slate-700">Asal PPUI: <?php echo $row['asal_ppui'] ?: '-'; ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-graduation-cap text-emerald-400 w-4 text-center"></i>
                                            <span class="font-medium text-slate-700"><?php echo $row['mustawa'] ?: 'Belum diatur'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle: Stats & Action Taken -->
                            <div class="p-5 md:w-4/12 border-r border-slate-100 flex flex-col justify-center">
                                <div class="flex justify-between items-center mb-3">
                                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Statistik Kasus</div>
                                    <?php echo $statusBadge; ?>
                                </div>
                                
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div onclick="openHistoryModal(<?php echo $row['student_id']; ?>, 'Ringan')" class="bg-emerald-50 rounded-lg p-2 border border-emerald-100 cursor-pointer hover:bg-emerald-100 transition-colors group">
                                        <div class="text-xl font-bold text-emerald-600 group-hover:scale-110 transition-transform"><?php echo $row['count_ringan']; ?></div>
                                        <div class="text-[10px] text-emerald-800 font-medium">RINGAN</div>
                                    </div>
                                    <div onclick="openHistoryModal(<?php echo $row['student_id']; ?>, 'Sedang')" class="bg-orange-50 rounded-lg p-2 border border-orange-100 cursor-pointer hover:bg-orange-100 transition-colors group">
                                        <div class="text-xl font-bold text-orange-600 group-hover:scale-110 transition-transform"><?php echo $row['count_sedang']; ?></div>
                                        <div class="text-[10px] text-orange-800 font-medium">SEDANG</div>
                                    </div>
                                    <div onclick="openHistoryModal(<?php echo $row['student_id']; ?>, 'Berat')" class="bg-red-50 rounded-lg p-2 border border-red-100 cursor-pointer hover:bg-red-100 transition-colors group">
                                        <div class="text-xl font-bold text-red-600 group-hover:scale-110 transition-transform"><?php echo $row['count_berat']; ?></div>
                                        <div class="text-[10px] text-red-800 font-medium">BERAT</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Contact & Action -->
                            <div class="p-5 md:w-3/12 flex flex-col justify-center gap-3 bg-slate-50/30">
                                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                                    <i class="fa-solid fa-user-shield text-slate-400"></i> Wali Mahasantri
                                </div>
                                
                                <?php if($row['wa_wali']): ?>
                                    <a href="https://wa.me/<?php echo formatWA($row['wa_wali']); ?>" target="_blank" class="flex items-center justify-center w-full py-2 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition-colors text-sm font-medium border border-emerald-200">
                                        <i class="fa-brands fa-whatsapp mr-2 text-lg"></i> Hubungi Wali
                                    </a>
                                <?php else: ?>
                                    <button disabled class="w-full py-2 bg-slate-100 text-slate-400 rounded-lg text-sm border border-slate-200 cursor-not-allowed">
                                        <i class="fa-solid fa-phone-slash mr-2"></i> No WA Tidak Ada
                                    </button>
                                <?php endif; ?>

                                <button onclick="openHistoryModal(<?php echo $row['student_id']; ?>)" class="flex items-center justify-center w-full py-2 bg-white text-slate-600 rounded-lg hover:bg-white hover:text-blue-600 hover:border-blue-200 transition-all text-sm font-medium border border-slate-200 shadow-sm relative group overflow-hidden">
                                     <span class="relative z-10 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-book-open"></i> Lihat Rincian Kasus
                                     </span>
                                </button>
                            </div>

                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL 1: ADD NEW VIOLATION -->
    <div id="addModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform transition-all scale-95 opacity-0" id="addModalContent">
            <div class="bg-slate-50 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">Catat Pelanggaran Baru</h3>
                <button onclick="closeModal('addModal')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form id="formAddViolation" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Mahasantri</label>
                    <select name="mahasantri_id" required class="w-full rounded-lg border-slate-300 bg-slate-50 focus:ring-red-500 focus:border-red-500 py-2.5 px-3 text-sm">
                        <option value="">-- Pilih Mahasantri --</option>
                        <?php foreach($all_students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['nama']; ?> - <?php echo $s['nim']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-red-500 focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jenis</label>
                        <select name="jenis" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-red-500 focus:border-red-500">
                            <option value="Ringan">Ringan (Teguran)</option>
                            <option value="Sedang">Sedang (Takzir)</option>
                            <option value="Berat">Berat (SP)</option>
                        </select>
                    </div>
                </div>

                 <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status Sanksi (Level)</label>
                    <select name="tingkat_sanksi" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-red-500 focus:border-red-500 font-bold text-slate-600">
                        <option value="Normal">Normal (Tanpa SP)</option>
                        <option value="SP1" class="text-yellow-600">SP 1 (Peringatan 1)</option>
                        <option value="SP2" class="text-orange-600">SP 2 (Peringatan 2)</option>
                        <option value="SP3" class="text-red-600">SP 3 (Peringatan Keras)</option>
                        <option value="DO" class="bg-red-50 text-red-700 font-black">DO / Dikeluarkan</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">*Pilih jika pelanggaran ini menyebabkan kenaikan status SP/DO.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keterangan / Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-red-500 focus:border-red-500" placeholder="Kronologi singkat..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sanksi / Hukuman</label>
                    <input type="text" name="sanksi" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-red-500 focus:border-red-500" placeholder="Contoh: SP1, Takzir Kebersihan...">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-lg shadow-lg shadow-red-200 transition-all">Simpan Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: HISTORY & DETAIL -->
    <div id="historyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-4xl h-[90vh] flex flex-col rounded-2xl shadow-2xl overflow-hidden transform transition-all scale-95 opacity-0" id="historyModalContent">
            <!-- Header with Dynamic Student Info -->
            <div class="bg-slate-50 px-6 py-4 border-b flex justify-between items-center shrink-0">
                <div class="flex items-center gap-4">
                    <div id="histPhoto" class="w-12 h-12 rounded-full bg-slate-200 border border-white shadow overflow-hidden"></div>
                    <div>
                        <h3 id="histName" class="font-bold text-lg text-slate-800 leading-tight">Loading...</h3>
                        <p id="histNIM" class="text-sm text-slate-500 font-mono"></p>
                    </div>
                </div>
                <button onclick="closeModal('historyModal')" class="w-8 h-8 rounded-full bg-white border hover:bg-slate-100 flex items-center justify-center text-slate-400 transition-colors"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-0 bg-slate-50/50">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-bold text-slate-600 uppercase tracking-wider">Riwayat Pelanggaran</h4>
                        <button onclick="openAddModal(currentStudentId)" class="text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg font-medium transition-colors border border-red-100 flex items-center">
                            <i class="fa-solid fa-plus mr-1"></i> Tambah Baru
                        </button>
                    </div>
                    
                    <div id="historyTimeline" class="space-y-4">
                        <!-- Content injected via JS -->
                        <div class="text-center py-10 text-slate-400"><i class="fa-solid fa-circle-notch fa-spin text-2xl"></i><br>Mengambil data...</div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t bg-white shrink-0 text-right">
                <button onclick="closeModal('historyModal')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition-colors">Tutup</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: EDIT VIOLATION -->
    <div id="editModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl transform transition-all">
             <div class="bg-amber-50 px-6 py-4 border-b flex justify-between items-center border-amber-100">
                <h3 class="font-bold text-lg text-amber-800">Edit Pelanggaran</h3>
                <button onclick="closeModal('editModal')" class="text-amber-800/50 hover:text-amber-800"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="formEditViolation" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" id="editTanggal" required class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jenis</label>
                        <select name="jenis" id="editJenis" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-amber-500 focus:border-amber-500">
                            <option value="Ringan">Ringan (Teguran)</option>
                            <option value="Sedang">Sedang (Takzir)</option>
                            <option value="Berat">Berat (SP)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status Sanksi (Level)</label>
                    <select name="tingkat_sanksi" id="editTingkat" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-amber-500 focus:border-amber-500 font-bold text-slate-600">
                        <option value="Normal">Normal (Tanpa SP)</option>
                        <option value="SP1" class="text-yellow-600">SP 1 (Peringatan 1)</option>
                        <option value="SP2" class="text-orange-600">SP 2 (Peringatan 2)</option>
                        <option value="SP3" class="text-red-600">SP 3 (Peringatan Keras)</option>
                        <option value="DO" class="bg-red-50 text-red-700 font-black">DO / Dikeluarkan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keterangan / Deskripsi</label>
                    <textarea name="deskripsi" id="editDeskripsi" rows="3" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sanksi / Hukuman</label>
                    <input type="text" name="sanksi" id="editSanksi" class="w-full rounded-lg border-slate-300 bg-slate-50 py-2 px-3 text-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 rounded-lg shadow-lg shadow-amber-200 transition-all">Update Data</button>
                </div>
            </form>
        </div>
    </div>


    <!-- JS LOGIC -->
    <script>
        $(document).ready(function() {
            // Handle Add Form
            $('#formAddViolation').on('submit', function(e){
                e.preventDefault();
                $.post('api/violation_handler.php', $(this).serialize(), function(res){
                    if(res.status === 'success') {
                        Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                });
            });

            // Handle Edit Form
            $('#formEditViolation').on('submit', function(e){
                e.preventDefault();
                $.post('api/violation_handler.php', $(this).serialize(), function(res){
                    if(res.status === 'success') {
                        // Reload page is safer to sync totals
                        Swal.fire('Updated', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });
        });

        // Modal Controls
        function openAddModal(preselectedId = null) {
            $('#addModal').removeClass('hidden');
            setTimeout(() => {
                $('#addModalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);
            
            // Handle Pre-selection
            let select = $('select[name="mahasantri_id"]');
            if (preselectedId) {
                select.val(preselectedId);
            } else {
                select.val('');
            }
        }

        function closeModal(id) {
            let content = $('#' + id + 'Content');
            if(id === 'editModal') content = $('#' + id).find('> div'); 

             $('#' + id + 'Content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            setTimeout(() => {
                $('#' + id).addClass('hidden');
            }, 200);
        }

        // HISTORY LOGIC
        let currentStudentId = 0;

        function openHistoryModal(studentId, filterType = null) {
            currentStudentId = studentId;
            $('#historyModal').removeClass('hidden');
            setTimeout(() => {
                $('#historyModalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);

            // Fetch Data
            $.get('api/get_violations.php?student_id=' + studentId, function(res) {
                if(res.status === 'success') {
                    // ... Populate Header ...
                    let s = res.student;
                    $('#histName').text(s.nama);
                    $('#histNIM').text(s.nim);
                    let imgHtml = s.photo_path ? `<img src="${s.photo_path}" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center bg-slate-200 text-slate-400"><i class="fa-solid fa-user"></i></div>';
                    $('#histPhoto').html(imgHtml);

                    // Populate Timeline with optional Highlight/Filter
                    let html = '';
                    if(res.violations.length === 0) {
                        html = '<p class="text-center text-slate-400 italic">Belum ada data pelanggaran.</p>';
                    } else {
                        res.violations.forEach(v => {
                            // If filter exists, maybe hide others? Or just highlight?
                            // Let's hide others for "Precision" view if user clicked specific box.
                            if (filterType && v.jenis !== filterType) return; 

                            let badge = '';
                            if(v.jenis === 'Berat') badge = 'bg-red-100 text-red-700 border-red-200';
                            else if(v.jenis === 'Sedang') badge = 'bg-orange-100 text-orange-700 border-orange-200';
                            else badge = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            
                            // Status specific badge
                            let spBadge = '';
                             if(v.tingkat_sanksi === 'DO') spBadge = '<span class="ml-2 text-[10px] bg-slate-800 text-white px-1.5 py-0.5 rounded">DO</span>';
                            else if(v.tingkat_sanksi === 'SP3') spBadge = '<span class="ml-2 text-[10px] bg-red-600 text-white px-1.5 py-0.5 rounded">SP3</span>';
                            else if(v.tingkat_sanksi === 'SP2') spBadge = '<span class="ml-2 text-[10px] bg-orange-500 text-white px-1.5 py-0.5 rounded">SP2</span>';
                             else if(v.tingkat_sanksi === 'SP1') spBadge = '<span class="ml-2 text-[10px] bg-yellow-400 text-white px-1.5 py-0.5 rounded">SP1</span>';

                            html += `
                            <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm relative group hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded border uppercase ${badge}">${v.jenis}</span>
                                        ${spBadge}
                                        <span class="text-xs text-slate-400 font-mono ml-2"><i class="fa-regular fa-calendar mr-1"></i>${v.tanggal}</span>
                                    </div>
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button onclick='openEdit(${JSON.stringify(v)})' class="w-7 h-7 flex items-center justify-center rounded bg-amber-50 text-amber-600 hover:bg-amber-100"><i class="fa-solid fa-pen text-xs"></i></button>
                                        <button onclick="deleteViolation(${v.id})" class="w-7 h-7 flex items-center justify-center rounded bg-red-50 text-red-600 hover:bg-red-100"><i class="fa-solid fa-trash text-xs"></i></button>
                                    </div>
                                </div>
                                <p class="text-slate-700 text-sm font-medium mb-1">${v.deskripsi || '-'}</p>
                                <div class="text-xs text-slate-500 bg-slate-50 p-2 rounded inline-block border border-slate-100">
                                    <strong class="text-slate-600">Sanksi:</strong> ${v.sanksi || '-'}
                                </div>
                            </div>`;
                        });
                        
                        // Empty state if filtered results are 0
                        if (html === '') {
                             html = `<p class="text-center text-slate-400 italic">Tidak ada pelanggaran kategori ${filterType}. <br><span class="text-xs text-blue-500 cursor-pointer" onclick="openHistoryModal(${studentId})">Lihat Semua</span></p>`;
                        }
                    }
                    $('#historyTimeline').html(html);
                    
                    // Update Title to reflect filter
                    let title = filterType ? `Riwayat: ${filterType}` : 'Riwayat Pelanggaran';
                    $('h4:contains("Riwayat")').text(title);

                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }

        // EDIT LOGIC
        function openEdit(v) {
            $('#editId').val(v.id);
            $('#editTanggal').val(v.tanggal);
            $('#editJenis').val(v.jenis);
            $('#editTingkat').val(v.tingkat_sanksi || 'Normal');
            $('#editDeskripsi').val(v.deskripsi);
            $('#editSanksi').val(v.sanksi);
            
            $('#editModal').removeClass('hidden');
        }

        // DELETE LOGIC
        function deleteViolation(id) {
            Swal.fire({
                title: 'Hapus data ini?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/violation_handler.php?id=' + id,
                        type: 'DELETE',
                        success: function(res) {
                            Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                            // Refresh modal data
                            openHistoryModal(currentStudentId); 
                        }
                    });
                }
            })
        }
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
