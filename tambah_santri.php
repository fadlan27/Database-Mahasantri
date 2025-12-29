<?php
require_once 'functions.php';
requireLogin();
$page_title = 'Operasi Massal Data Santri';

// Fetch necessary data for dropdowns
$stmt = $pdo->query("SELECT DISTINCT mustawa FROM mahasantri WHERE mustawa != '' ORDER BY mustawa ASC");
$mustawa_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fallback if empty (e.g. init state)
if(empty($mustawa_list)) {
    $mustawa_list = ['Awwal', 'Tsani', 'Tsalits', 'Rabiah', 'Khamis', 'Sadis', 'Lulus', 'Pengabdian'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include 'includes/head.php'; ?>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { primary: '#0f172a', accent: '#3b82f6' }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-600 antialiased">

<div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col min-w-0">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 p-4 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Operasi Massal</h1>
                        <p class="text-slate-500 text-sm">Tambah atau edit banyak data sekaligus (Spreadsheet Mode).</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="window.history.back()" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-all font-medium text-sm">
                            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-slate-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="switchTab('add')" id="tab-add" class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-plus-circle"></i> Tambah Massal
                        </button>
                        <button onclick="switchTab('edit')" id="tab-edit" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Massal
                        </button>
                        <button onclick="switchTab('photos')" id="tab-photos" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-images"></i> Edit Foto Massal
                        </button>
                    </nav>
                </div>

                <!-- CONTENT: MASS ADD -->
                <div id="content-add" class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
                            <h3 class="font-bold text-blue-800"><i class="fa-solid fa-table-list mr-2"></i>Input Data Santri Baru</h3>
                            <button onclick="addRows(5)" class="text-xs bg-white text-blue-600 px-3 py-1.5 rounded-lg border border-blue-200 hover:bg-blue-50 font-semibold shadow-sm">
                                + Tambah 5 Baris
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left" id="bulkAddTable">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 w-12 text-center">#</th>
                                        <th class="px-4 py-3 w-32">NIM (Opsional)</th>
                                        <th class="px-4 py-3 min-w-[200px]">Nama Lengkap <span class="text-red-500">*</span></th>
                                        <th class="px-4 py-3 w-32">Gender</th>
                                        <th class="px-4 py-3 w-40">Mustawa <span class="text-red-500">*</span></th>
                                        <th class="px-4 py-3 min-w-[200px]">Alamat / Asal</th>
                                        <th class="px-4 py-3 w-12 text-center">Hapus</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <!-- Rows generated by JS -->
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                            <button onclick="saveBulkData()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                                <i class="fa-solid fa-save"></i> Simpan Semua Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CONTENT: MASS EDIT -->
                <div id="content-edit" class="hidden space-y-4">
                    <!-- Filter Bar -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-1 w-full space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Cari Nama / NIM</label>
                            <input type="text" id="editSearch" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Ketik nama atau NIM...">
                        </div>
                        <div class="w-full md:w-48 space-y-1">
                             <label class="text-xs font-bold text-slate-500 uppercase ml-1">Mustawa</label>
                             <select id="editFilterMustawa" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <option value="">Semua</option>
                                <?php foreach($mustawa_list as $mst): ?>
                                    <option value="<?php echo $mst; ?>"><?php echo $mst; ?></option>
                                <?php endforeach; ?>
                             </select>
                        </div>
                        <button onclick="loadMassEditData()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md shadow-blue-200 transition-all active:scale-95 h-[42px]">
                            <i class="fa-solid fa-magnifying-glass mr-2"></i> Cari
                        </button>
                    </div>

                    <!-- Instruction Initial -->
                    <div id="editInstruction" class="text-center py-12 md:py-20 text-slate-400">
                        <i class="fa-solid fa-table-cells text-6xl mb-4 opacity-50"></i>
                         <p class="font-medium">Gunakan filter di atas untuk memuat data.</p>
                    </div>

                    <!-- Results Table -->
                    <div id="massEditContainer" class="hidden bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-250px)]">
                        <!-- Toolbar -->
                         <div class="p-3 bg-blue-50/50 border-b border-slate-100 flex justify-between items-center h-14 shrink-0">
                            <div class="text-sm font-semibold text-slate-600 ml-2" id="selectionCount">0 data dipilih</div>
                            <button onclick="deleteSelected()" id="btnDeleteSelected" class="hidden text-xs bg-white text-red-600 border border-red-200 hover:bg-red-50 hover:border-red-300 px-4 py-2 rounded-lg shadow-sm font-bold transition-all flex items-center gap-2">
                                <i class="fa-solid fa-trash-can"></i> Hapus Terpilih
                            </button>
                        </div>
                        
                        <div class="overflow-auto min-h-0 flex-1">
                            <table class="w-full text-sm text-left relative border-collapse" id="massEditTable">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200 sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="w-10 px-4 py-3 text-center bg-slate-50"><input type="checkbox" id="selectAll" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"></th>
                                        <th class="px-4 py-3 bg-slate-50 min-w-[200px]">Nama Lengkap</th>
                                        <th class="px-4 py-3 w-32 bg-slate-50">NIM</th>
                                        <th class="px-4 py-3 w-32 bg-slate-50">Gender</th>
                                        <th class="px-4 py-3 w-40 bg-slate-50">Mustawa</th>
                                        <th class="px-4 py-3 w-40 bg-slate-50">Status</th>
                                        <th class="px-4 py-3 w-10 bg-slate-50">ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <!-- Loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="p-4 bg-slate-50 border-t border-slate-200 shrink-0 flex justify-end items-center gap-4">
                             <div id="hasChangesIndicator" class="hidden text-amber-600 text-xs font-semibold animate-pulse">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i> Ada perubahan belum disimpan
                             </div>
                             <button onclick="saveMassEditChanges()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold shadow-lg shadow-emerald-200 transition-all flex items-center gap-2 active:scale-95">
                                 <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                             </button>
                        </div>
                    </div>
                </div>

                <!-- CONTENT: MASS PHOTOS -->
                <div id="content-photos" class="hidden space-y-6">
                    
                    <!-- 1. BULK UPLOAD ZONE -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-2 gap-2">
                         <label class="text-sm font-bold text-slate-700">Area Upload</label>
                         <div class="flex items-center gap-4 text-sm bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                            <span class="text-slate-500 text-xs font-semibold uppercase">Jika foto ada:</span>
                            <label class="flex items-center gap-1.5 cursor-pointer hover:text-blue-600 transition-colors">
                                <input type="radio" name="overwrite_mode" value="overwrite" checked class="text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span>Timpa</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer hover:text-blue-600 transition-colors">
                                <input type="radio" name="overwrite_mode" value="skip" class="text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span>Lewati</span>
                            </label>
                        </div>
                    </div>
                    <div id="dropZone" class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer group relative overflow-hidden h-64 flex flex-col items-center justify-center text-center p-8">
                        <input type="file" id="bulkPhotoInput" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <div class="pointer-events-none group-hover:scale-105 transition-transform duration-300">
                             <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                             </div>
                             <h3 class="text-lg font-bold text-slate-700">Drag & Drop Foto Santri Disini</h3>
                             <p class="text-sm text-slate-400 mt-2">Atau klik untuk memilih file</p>
                             <p class="text-xs text-slate-400 mt-4 border-t border-slate-200 pt-4 max-w-md mx-auto">
                                <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i>
                                Sistem otomatis mencocokkan nama file: <br>
                                <code class="bg-slate-200 px-1 rounded text-slate-600">Nama_bin_Ayah.jpg</code>
                             </p>
                        </div>
                    </div>

                    <!-- 2. FILTER & GRID -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-1 w-full space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Cari Nama / NIM</label>
                            <input type="text" id="photoSearch" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Cari santri..." onkeypress="if(event.key === 'Enter') loadPhotoGrid()">
                        </div>
                        <div class="w-full md:w-48 space-y-1">
                             <label class="text-xs font-bold text-slate-500 uppercase ml-1">Mustawa</label>
                             <select id="photoFilterMustawa" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <option value="">Semua</option>
                                <?php foreach($mustawa_list as $mst): ?>
                                    <option value="<?php echo $mst; ?>"><?php echo $mst; ?></option>
                                <?php endforeach; ?>
                             </select>
                        </div>
                        <button onclick="loadPhotoGrid()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md shadow-blue-200 transition-all active:scale-95 h-[42px]">
                            <i class="fa-solid fa-magnifying-glass mr-2"></i> Cari
                        </button>
                    </div>

                    <!-- 3. RESULTS GRID -->
                    <div id="photoGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <!-- Loaded via AJAX -->
                        <div class="col-span-full text-center py-20 text-slate-300">
                             <i class="fa-solid fa-images text-6xl mb-4 opacity-50"></i>
                             <p>Gunakan pencarian untuk menampilkan data.</p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    // Initialize dynamic Mustawa options from PHP to JS
    const mustawaList = <?php echo json_encode($mustawa_list); ?>;
    const mustawaOptions = mustawaList.map(m => `<option value="${m}">${m}</option>`).join('');

    // --- TABS LOGIC ---
    function switchTab(tab) {
        // Reset classes
        $('#tab-add, #tab-edit, #tab-photos').removeClass('border-blue-500 text-blue-600').addClass('border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300');
        $('#content-add, #content-edit, #content-photos').addClass('hidden');

        // Activate selected
        $(`#tab-${tab}`).removeClass('border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300').addClass('border-blue-500 text-blue-600');
        $(`#content-${tab}`).removeClass('hidden');
    }

    // --- MASS ADD LOGIC ---
    function createRow(index) {
        return `
            <tr class="group hover:bg-slate-50 transition-colors">
                <td class="px-4 py-2 text-center text-slate-400 font-mono text-xs">${index}</td>
                <td class="px-2 py-2">
                    <input type="text" name="nim[]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all placeholder-slate-300" placeholder="Auto">
                </td>
                <td class="px-2 py-2">
                    <input type="text" name="nama[]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all font-medium" placeholder="Nama Santri...">
                </td>
                <td class="px-2 py-2">
                    <select name="gender[]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm cursor-pointer">
                        <option value="Ikhwan">Ikhwan</option>
                        <option value="Akhowat">Akhowat</option>
                    </select>
                </td>
                <td class="px-2 py-2">
                     <select name="mustawa[]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm cursor-pointer">
                        ${mustawaOptions}
                    </select>
                </td>
                <td class="px-2 py-2">
                     <input type="text" name="alamat[]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-all" placeholder="Kota/Kabupaten...">
                </td>
                <td class="px-2 py-2 text-center">
                    <button onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-50">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function addRows(count = 1) {
        const tbody = $('#bulkAddTable tbody');
        const startIdx = tbody.children().length + 1;
        for(let i=0; i<count; i++) {
            tbody.append(createRow(startIdx + i));
        }
    }

    function removeRow(btn) {
        $(btn).closest('tr').remove();
        $('#bulkAddTable tbody tr').each(function(i) {
            $(this).find('td:first').text(i+1);
        });
    }

    function saveBulkData() {
        let students = [];
        $('#bulkAddTable tbody tr').each(function() {
            const row = $(this);
            const nama = row.find('input[name="nama[]"]').val().trim();
            const nim = row.find('input[name="nim[]"]').val().trim();
            const gender = row.find('select[name="gender[]"]').val();
            const mustawa = row.find('select[name="mustawa[]"]').val();
            const alamat = row.find('input[name="alamat[]"]').val().trim();

            if(nama) {
                students.push({
                    nim: nim, nama: nama, gender: gender, mustawa: mustawa, alamat_kabupaten: alamat 
                });
            }
        });

        if(students.length === 0) {
            Swal.fire('Data Kosong', 'Harap isi minimal satu nama santri.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Menyimpan...', text: `Memproses ${students.length} data santri...`, allowOutsideClick: false, didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'api/student_bulk.php?action=mass_add', // Modified endpoint
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ data: students }),
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!', text: `${response.inserted} santri berhasil ditambahkan.`, icon: 'success'
                    }).then(() => window.location.href = 'master_data.php');
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Terjadi kesalahan server.', 'error'); }
        });
    }

    // --- MASS EDIT LOGIC ---
    function loadMassEditData() {
        const search = $('#editSearch').val();
        const mustawa = $('#editFilterMustawa').val();

        if(!search && !mustawa) {
            Swal.fire('Filter Minim', 'Mohon isi pencarian atau pilih mustawa.', 'warning');
            return;
        }

        $('#editInstruction').addClass('hidden');
        $('#massEditContainer').removeClass('hidden');
        
        // Show loading in table
        const tbody = $('#massEditTable tbody');
        tbody.html('<tr><td colspan="7" class="text-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i><br>Memuat data...</td></tr>');

        $.ajax({
            url: 'api/student_bulk.php',
            method: 'GET',
            data: { action: 'search', q: search, mustawa: mustawa },
            success: function(response) {
                if(response.status === 'success') {
                    renderEditTable(response.data);
                } else {
                    tbody.html(`<tr><td colspan="7" class="text-center py-8 text-red-400">${response.message}</td></tr>`);
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="7" class="text-center py-8 text-red-400">Gagal memuat data.</td></tr>');
            }
        });
    }

    function renderEditTable(data) {
        const tbody = $('#massEditTable tbody');
        tbody.empty();
        
        if(data.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center py-8 text-slate-400">Tidak ada data ditemukan.</td></tr>');
            return;
        }

        data.forEach(item => {
            const row = `
                <tr class="hover:bg-blue-50/50 transition-colors" data-id="${item.id}">
                    <td class="text-center p-0">
                        <input type="checkbox" class="row-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4" value="${item.id}">
                    </td>
                    <td class="p-2">
                        <input type="text" class="edit-nama w-full px-3 py-1.5 border border-transparent hover:border-slate-300 focus:border-blue-500 bg-transparent focus:bg-white rounded text-sm font-medium outline-none transition-all" value="${item.nama}">
                    </td>
                    <td class="p-2">
                        <input type="text" class="edit-nim w-full px-3 py-1.5 border border-transparent hover:border-slate-300 focus:border-blue-500 bg-transparent focus:bg-white rounded text-sm outline-none transition-all font-mono text-slate-500 focus:text-slate-800" value="${item.nim}">
                    </td>
                    <td class="p-2">
                        <select class="edit-gender w-full px-2 py-1.5 border border-transparent hover:border-slate-300 focus:border-blue-500 bg-transparent focus:bg-white rounded text-sm outline-none cursor-pointer">
                            <option value="Ikhwan" ${item.gender === 'Ikhwan' ? 'selected' : ''}>Ikhwan</option>
                            <option value="Akhowat" ${item.gender === 'Akhowat' ? 'selected' : ''}>Akhowat</option>
                        </select>
                    </td>
                    <td class="p-2">
                         <select class="edit-mustawa w-full px-2 py-1.5 border border-transparent hover:border-slate-300 focus:border-blue-500 bg-transparent focus:bg-white rounded text-sm outline-none cursor-pointer">
                            ${mustawaList.map(m => `<option value="${m}" ${item.mustawa === m ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>

                    </td>
                    <td class="p-2">
                         <select class="edit-status w-full px-2 py-1.5 border border-transparent hover:border-slate-300 focus:border-blue-500 bg-transparent focus:bg-white rounded text-sm outline-none cursor-pointer ${getStatusColor(item.status)}">
                            <option value="Aktif" ${item.status === 'Aktif' ? 'selected' : ''}>Aktif</option>
                            <option value="Cuti" ${item.status === 'Cuti' ? 'selected' : ''}>Cuti</option>
                            <option value="Lulus" ${item.status === 'Lulus' ? 'selected' : ''}>Lulus</option>
                            <option value="Dikeluarkan" ${item.status === 'Dikeluarkan' ? 'selected' : ''}>Dikeluarkan</option>
                            <option value="Mengundurkan Diri" ${item.status === 'Mengundurkan Diri' ? 'selected' : ''}>Mengundurkan Diri</option>
                        </select>
                    </td>
                    <td class="p-2 text-xs text-slate-300 text-center select-none">${item.id}</td>
                </tr>
            `;
            tbody.append(row);
        });

        updateSelectionUI();
    }

    function getStatusColor(status) {
        if(status === 'Aktif') return 'text-emerald-600 font-semibold';
        if(status === 'Lulus') return 'text-blue-600 font-semibold';
        if(status === 'Dikeluarkan') return 'text-red-600';
        return 'text-slate-600';
    }

    // -- Selection Logic --
    $(document).on('change', '#selectAll', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateSelectionUI();
    });

    $(document).on('change', '.row-checkbox', function() {
        const allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', allChecked);
        updateSelectionUI();
    });

    function updateSelectionUI() {
        const count = $('.row-checkbox:checked').length;
        $('#selectionCount').text(`${count} data dipilih`);
        if(count > 0) {
            $('#btnDeleteSelected').removeClass('hidden').addClass('flex');
        } else {
            $('#btnDeleteSelected').addClass('hidden').removeClass('flex');
        }
    }

    // -- Change Detection --
    $(document).on('change input', '#massEditTable input, #massEditTable select', function() {
        $('#hasChangesIndicator').removeClass('hidden');
    });

    // -- Save Actions --
    function saveMassEditChanges() {
        let updates = [];
        
        $('#massEditTable tbody tr').each(function() {
            const row = $(this);
            const id = row.data('id');
            updates.push({
                id: id,
                nama: row.find('.edit-nama').val(),
                nim: row.find('.edit-nim').val(),
                gender: row.find('.edit-gender').val(),
                mustawa: row.find('.edit-mustawa').val(),
                status: row.find('.edit-status').val(),
            });
        });
        
        Swal.fire({
            title: 'Simpan Perubahan?',
            text: `Akan memperbarui ${updates.length} data yang tampil.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if(result.isConfirmed) {
                 Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });
                 
                 $.ajax({
                    url: 'api/student_bulk.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'bulk_update', data: updates }),
                    success: function(res) {
                        if(res.status === 'success') {
                            Swal.fire('Berhasil', 'Data berhasil diperbarui', 'success');
                            $('#hasChangesIndicator').addClass('hidden');
                            loadMassEditData(); // Refresh
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    }
                 });
            }
        });
    }

    function deleteSelected() {
        const ids = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
        
        Swal.fire({
            title: 'Hapus Data?',
            text: `Yakin ingin menghapus ${ids.length} data terpilih? TINDAKAN INI PERMANEN.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus Permanen'
        }).then((result) => {
            if(result.isConfirmed) {
                Swal.fire({ title: 'Menghapus...', didOpen: () => Swal.showLoading() });
                
                $.ajax({
                    url: 'api/student_bulk.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'bulk_delete', ids: ids }),
                    success: function(res) {
                        if(res.status === 'success') {
                            Swal.fire('Berhasil', 'Data berhasil dihapus', 'success');
                            loadMassEditData(); // Refresh
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    }
                 });
            }
        });
    }

    $(document).ready(function() {
        addRows(5);
        
        // Enter key on search
        $('#editSearch').on('keypress', function(e) {
            if(e.which == 13) loadMassEditData();
        });
    });
    // --- PHOTO LOGIC ---
    
    // 1. Bulk Upload
    $('#bulkPhotoInput').on('change', function(e) {
        handleFiles(e.target.files);
    });

    const dropZone = document.getElementById('dropZone');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) { dropZone.classList.add('bg-blue-50', 'border-blue-400'); }
    function unhighlight(e) { dropZone.classList.remove('bg-blue-50', 'border-blue-400'); }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        if(files.length === 0) return;

        const formData = new FormData();
        formData.append('action', 'auto_match');
        
        // Get overwrite mode
        const mode = document.querySelector('input[name="overwrite_mode"]:checked').value;
        formData.append('overwrite_mode', mode); // Send mode to API
        
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        Swal.fire({
            title: 'Mengupload...',
            text: `Memproses ${files.length} foto...`,
            didOpen: () => Swal.showLoading(),
            allowOutsideClick: false
        });

        $.ajax({
            url: 'api/upload_bulk_photo',
            method: 'POST',
            body: formData, // Wrong in JQuery
            data: formData, // Correct
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    // Show Logs
                    const logHtml = res.logs.map(l => `<li class="text-xs text-left">${l}</li>`).join('');
                    
                    Swal.fire({
                        title: 'Upload Selesai',
                        html: `<div class="max-h-60 overflow-y-auto bg-slate-50 p-2 border rounded">${res.message}<ul class="mt-2 space-y-1 list-disc pl-4">${logHtml}</ul></div>`,
                        icon: 'info'
                    }).then(() => loadPhotoGrid()); // Refresh grid if active
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseText || 'Upload failed', 'error');
            }
        });
    }

    // 2. Load Grid
    function loadPhotoGrid() {
        const search = $('#photoSearch').val();
        const mustawa = $('#photoFilterMustawa').val();

        if(!search && !mustawa) {
             Swal.fire('Filter Minim', 'Mohon isi filter pencarian.', 'warning');
             return;
        }

        $('#photoGrid').html('<div class="col-span-full text-center py-20"><i class="fa-solid fa-spinner fa-spin text-4xl text-blue-500"></i></div>');

         $.ajax({
            url: 'api/student_bulk.php?action=search',
            method: 'GET',
            data: { q: search, mustawa: mustawa },
            success: function(res) {
                if(res.status === 'success') {
                    renderPhotoGrid(res.data);
                } else {
                    $('#photoGrid').html(`<div class="col-span-full text-center py-20 text-red-400">${res.message}</div>`);
                }
            }
        });
    }

    function renderPhotoGrid(data) {
        const grid = $('#photoGrid');
        grid.empty();
        
        if(data.length === 0) {
            grid.html('<div class="col-span-full text-center py-20 text-slate-400">Tidak ada data matched.</div>');
            return;
        }

        data.forEach(item => {
            // Determine default image based on gender
            const defaultImg = item.gender === 'Akhowat' ? 'assets/img/profile_akhowat.svg' : 'assets/img/profile_ikhwan.svg';
            const photoUrl = item.photo_path ? item.photo_path : defaultImg; 
            
            const card = `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col items-center relative group hover:shadow-md transition-shadow">
                    <div class="w-24 h-24 rounded-full bg-slate-100 mb-3 overflow-hidden border-2 border-slate-100 relative">
                        <img src="${photoUrl}" id="img-${item.id}" class="w-full h-full object-cover" onerror="this.src='${defaultImg}'">
                        
                        <label class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer text-white">
                            <i class="fa-solid fa-camera"></i>
                            <input type="file" class="hidden" onchange="uploadSinglePhoto(this, ${item.id})" accept="image/*">
                        </label>
                    </div>
                    <h4 class="font-bold text-slate-700 text-center text-sm leading-tight mb-1 truncate w-full" title="${item.nama}">${item.nama}</h4>
                    <p class="text-xs text-slate-400 font-mono">${item.nim}</p>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 mt-2">${item.mustawa}</span>
                </div>
            `;
            grid.append(card);
        });
    }

    function uploadSinglePhoto(input, id) {
        if(input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('action', 'single_upload');
            formData.append('id', id);
            formData.append('photo', input.files[0]);

            // Show mini loading?
            const img = document.getElementById(`img-${id}`);
            img.style.opacity = '0.5';

            $.ajax({
                url: 'api/upload_bulk_photo',
                method: 'POST',
                data: formData,
                 processData: false,
                contentType: false,
                success: function(res) {
                    if(res.status === 'success') {
                        // Update IMG src with cache bust
                        img.src = res.path;
                        img.style.opacity = '1';
                        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                        Toast.fire({ icon: 'success', title: 'Foto updated' });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                        img.style.opacity = '1';
                    }
                },
                error: function() {
                     Swal.fire('Error', 'Upload failed', 'error');
                     img.style.opacity = '1';
                }
            });
        }
    }

    $(document).ready(function() {
        // ... existing
    });
</script>

    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
