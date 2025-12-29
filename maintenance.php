<?php
require_once 'functions.php';
requireLogin();
requireRole('superadmin');

$message = '';
// Logic for handlers is in api/ folder
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo getCsrfToken(); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Maintenance & Backup - Jamiah Abat</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>if(localStorage.getItem('sidebarCollapsed')==='true'){document.write('<style>#sidebar{display:none!important}</style>');}</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Segoe UI"', 'Inter', 'sans-serif'],
                    },
                    colors: { mica: '#f3f3f3', glass: 'rgba(255, 255, 255, 0.7)' }
                }
            }
        }
    </script>
    <style>
        body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); background-attachment: fixed; }
        .dark body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .glass-panel { background: rgba(255, 255, 255, 0.65); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5); }
        .dark .glass-panel { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen overflow-hidden flex dark:text-slate-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 flex flex-col items-center">
            
            <div class="max-w-4xl w-full space-y-8">
                <!-- Grid Card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- 1. Backup Card (Split Options) -->
                    <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center hover:shadow-xl transition-shadow">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mb-6 dark:bg-blue-900/40 dark:text-blue-400">
                            <i class="fa-solid fa-cloud-arrow-down text-4xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Backup Database</h2>
                        <p class="text-slate-500 text-sm mb-6 dark:text-slate-400">Pilih jenis backup yang Anda butuhkan:</p>
                        
                        <div class="flex flex-col w-full gap-3">
                            <a href="api/backup_handler?mode=sql" target="_blank" class="px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium transition-colors w-full shadow-lg shadow-amber-500/30 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-code"></i> Backup SQL Saja (Cepat)
                            </a>
                            <a href="api/backup_handler?mode=full" target="_blank" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors w-full shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-zipper"></i> Backup Full + Foto (ZIP)
                            </a>
                        </div>
                    </div>

                    <!-- 2. Restore Card (Split Options) -->
                    <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center hover:shadow-xl transition-shadow">
                        <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 mb-6 dark:bg-orange-900/40 dark:text-orange-400">
                            <i class="fa-solid fa-rotate-left text-4xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Restore Database</h2>
                        <p class="text-slate-500 text-sm mb-6 dark:text-slate-400">Kembalikan data dari file backup. <br><span class="text-red-500 font-semibold">PERINGATAN: Data saat ini akan ditimpa!</span></p>
                        
                        <div class="flex flex-col w-full gap-3">
                            <!-- Input SQL Only -->
                            <input type="file" id="restoreFileSQL" accept=".sql" class="hidden" onchange="performRestore(this)">
                            <button type="button" onclick="confirmRestore('sql')" class="px-4 py-3 bg-white border border-slate-200 hover:bg-amber-50 text-slate-700 hover:text-amber-700 rounded-xl font-medium transition-colors w-full dark:bg-slate-700 dark:border-slate-600 dark:text-white dark:hover:bg-slate-600 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-code"></i> Restore File .SQL
                            </button>

                            <!-- Input ZIP Full -->
                            <input type="file" id="restoreFileZIP" accept=".zip" class="hidden" onchange="performRestore(this)">
                            <button type="button" onclick="confirmRestore('zip')" class="px-4 py-3 bg-white border border-slate-200 hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded-xl font-medium transition-colors w-full dark:bg-slate-700 dark:border-slate-600 dark:text-white dark:hover:bg-slate-600 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-zipper"></i> Restore File .ZIP
                            </button>
                        </div>
                    </div>

                    <!-- 3. Fix Capitalization Card -->
                    <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center hover:shadow-xl transition-shadow">
                        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 mb-6 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <i class="fa-solid fa-spell-check text-4xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Perbaikan Ejaan</h2>
                        <p class="text-slate-500 text-sm mb-6 dark:text-slate-400">Otomatis ubah nama menjadi <strong>Title Case</strong>.<br>Contoh: <em>budi</em> &rarr; <em>Budi</em></p>
                        
                        <button onclick="confirmFix()" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors w-full shadow-lg shadow-emerald-500/30">
                            <i class="fa-solid fa-wand-magic-sparkles mr-2"></i> Perbaiki Data
                        </button>
                    </div>

                    <!-- 4. Update Database Structure Card -->
                    <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center hover:shadow-xl transition-shadow">
                        <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 mb-6 dark:bg-purple-900/40 dark:text-purple-400">
                            <i class="fa-solid fa-server text-4xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Update Struktur Database</h2>
                        <p class="text-slate-500 text-sm mb-6 dark:text-slate-400">Cek & tambahkan tabel/kolom yang belum ada di hosting agar sesuai dengan versi lokal. <strong class="text-emerald-600">Aman & Tidak Menghapus Data.</strong></p>
                        
                        <button onclick="confirmDbFix()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition-colors w-full shadow-lg shadow-purple-500/30">
                            <i class="fa-solid fa-rotate mr-2"></i> Cek & Update Database
                        </button>
                    </div>

                    <!-- 5. Promotion Card -->
                    <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center hover:shadow-xl transition-shadow">
                        <div class="w-20 h-20 bg-rose-100 rounded-full flex items-center justify-center text-rose-600 mb-6 dark:bg-rose-900/40 dark:text-rose-400">
                            <i class="fa-solid fa-angles-up text-4xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Kenaikan Kelas Massal</h2>
                        <p class="text-slate-500 text-sm mb-6 dark:text-slate-400">Otomatis naikkan Mustawa 1 tingkat untuk <strong>semua santri aktif</strong>.<br>(Awwal &rarr; Tsani &rarr; Lulus)</p>
                        
                        <button onclick="confirmPromotion()" class="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-medium transition-colors w-full shadow-lg shadow-rose-500/30">
                            <i class="fa-solid fa-graduation-cap mr-2"></i> Proses Kenaikan
                        </button>
                    </div>

                    <!-- 6. DANGER ZONE: Reset Database -->
                    <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center hover:shadow-xl transition-shadow border-2 border-red-100 dark:border-red-900/30">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center text-red-600 mb-6 dark:bg-red-900/40 dark:text-red-400">
                            <i class="fa-solid fa-biohazard text-4xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2 text-red-600 dark:text-red-400">Pemutihan Database</h2>
                        <p class="text-slate-500 text-sm mb-6 dark:text-slate-400">
                            Menghapus <strong>SEMUA DATA SANTRI</strong>, pelanggaran, dan riwayat.<br>
                            Database akan kembali bersih (0 Data).<br>
                            <span class="font-bold text-red-600">Admin Tidak Terhapus.</span>
                        </p>
                        
                        <button onclick="confirmDatabaseReset()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors w-full shadow-lg shadow-red-500/30">
                            <i class="fa-solid fa-trash-can mr-2"></i> Reset Total
                        </button>
                    </div>

                </div>

                <!-- Petunjuk -->
                <div class="glass-panel p-6 rounded-xl text-sm text-slate-600 dark:text-slate-400">
                    <h3 class="font-bold mb-2 flex items-center gap-2"><i class="fa-regular fa-lightbulb text-yellow-500"></i> Petunjuk:</h3>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Lakukan Backup secara berkala (misal: seminggu sekali) untuk mencegah kehilangan data.</li>
                        <li>Pastikan nama file backup berakhiran <code>.sql</code>.</li>
                        <li>Proses Restore akan menghapus semua data yang ada saat ini dan menggantinya dengan data dari file backup.</li>
                    </ul>
                </div>

            </div>
        </main>
    </div>

    <script>
        // 1. Restore Confirmation
        // 1. Restore Confirmation
        function confirmRestore(type) {
            let titleText = type === 'zip' ? 'Restore Full (ZIP)?' : 'Restore SQL Only?';
            let descText = type === 'zip' 
                ? "Sistem akan mengembalikan Database DAN Foto. Data lama akan ditimpa." 
                : "Hanya Database yang dikembalikan. Foto tidak berubah.";

            Swal.fire({
                title: titleText,
                text: descText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f97316',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Ya, Pilih File',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'zip') {
                        document.getElementById('restoreFileZIP').click();
                    } else {
                        document.getElementById('restoreFileSQL').click();
                    }
                }
            })
        }

        // 2. Perform Restore (File Input Change)
        function performRestore(input) {
            if (input.files.length > 0) {
                const file = input.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                
                // Smart Confirmation Modal
                Swal.fire({
                    title: '<strong>Konfirmasi Restore</strong>',
                    html: `
                        <div class="bg-slate-50 dark:bg-slate-700/50 p-4 rounded-xl border border-slate-200 dark:border-slate-600 mb-4 text-left">
                            <p class="text-xs text-slate-500 uppercase tracking-widest font-bold mb-1">File Terpilih:</p>
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-file-code text-2xl text-orange-500"></i>
                                <div>
                                    <p class="font-bold text-slate-700 dark:text-slate-200 text-sm truncate max-w-[200px]">${file.name}</p>
                                    <p class="text-xs text-slate-500 font-mono">${fileSize}</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Sistem akan menggunakan <strong>Metode Hybrid (Shell)</strong> yang cepat & stabil.
                            <br><span class="text-red-500 font-bold">Data lama akan ditimpa!</span> yakiin?
                        </p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ea580c',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '<i class="fa-solid fa-bolt"></i> Eksekusi Sekarang',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const formData = new FormData();
                        formData.append('backup_file', file);
                        
                        return fetch('api/restore_handler', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'error') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Error: ${error}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: result.value.message,
                            icon: 'success'
                        }).then(() => location.reload());
                    }
                    // Reset input
                     input.value = '';
                })
            }
        }

        // 3. Fix Capitalization
        function confirmFix() {
            Swal.fire({
                title: 'Perbaiki Ejaan?',
                text: "Sistem akan mengubah format penulisan Nama, Alamat, dll menjadi Huruf Besar di Awal Kata.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Ya, Proses',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('api/standardize_handler')
                        .then(response => {
                            if (!response.ok) { throw new Error(response.statusText) }
                            return response.json()
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`)
                        })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.status === 'success' ? 'Berhasil!' : 'Gagal',
                        text: result.value.message,
                        icon: result.value.status === 'success' ? 'success' : 'error'
                    });
                }
            })
        }

        // 4. Check & Update Database (Enhanced with Undo)
        function confirmDbFix() {
            Swal.fire({
                title: 'Update Struktur Database?',
                text: "Sistem akan mengecek database hosting dan menambahkan kolom/tabel yang kurang sesuai versi terbaru. Data lama tetap AMAN (Tidak Dihapus).",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#9333ea',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Ya, Cek & Update',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('api/db_migration.php')
                        .then(response => {
                             // Handle raw HTML error gracefully
                            const contentType = response.headers.get("content-type");
                            if (!contentType || !contentType.includes("application/json")) {
                                throw new Error("Server Error: Respon bukan JSON. Cek log server.");
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'error') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value;
                    
                    if (data.changes && data.changes.length > 0) {
                        // Show Report with Undo Option
                        let listHtml = '<ul class="text-left text-sm list-disc pl-5 space-y-1 mb-4 text-slate-600 dark:text-slate-300">';
                        data.changes.forEach(change => {
                            listHtml += `<li>${change}</li>`;
                        });
                        listHtml += '</ul>';

                        Swal.fire({
                            title: '<strong>Update Berhasil!</strong>',
                            html: `
                                <div class="bg-indigo-50 dark:bg-indigo-900/30 p-4 rounded-xl border border-indigo-100 dark:border-indigo-800 mb-4">
                                     <h4 class="font-bold text-indigo-700 dark:text-indigo-300 text-sm mb-2 uppercase tracking-wide">Rincian Perubahan:</h4>
                                     ${listHtml}
                                </div>
                                <p class="text-sm text-slate-500">Jika ada kesalahan, anda bisa membatalkannya sekarang.</p>
                            `,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonColor: '#cbd5e1',
                            cancelButtonColor: '#e11d48',
                            confirmButtonText: 'Tutup',
                            cancelButtonText: '<i class="fa-solid fa-rotate-left"></i> Undo / Batalkan',
                            reverseButtons: true
                        }).then((res) => {
                            // If Cancel (Undo) is clicked
                            if (res.dismiss === Swal.DismissReason.cancel) {
                                // Trigger Undo
                                Swal.fire({
                                    title: 'Membatalkan Perubahan...',
                                    text: 'Mengembalikan struktur database ke kondisi sebelumnya.',
                                    didOpen: () => {
                                        Swal.showLoading();
                                        fetch('api/db_migration_undo.php', {
                                            method: 'POST' // Needs CSRF usually handled globally
                                        })
                                        .then(r => r.json())
                                        .then(d => {
                                            if(d.status === 'success') {
                                                Swal.fire('Sukses', d.message, 'success');
                                            } else {
                                                Swal.fire('Gagal', d.message, 'error');
                                            }
                                        })
                                        .catch(e => Swal.fire('Error', 'Gagal menghubungi server', 'error'));
                                    }
                                });
                            }
                        });
                        
                    } else {
                        // No changes
                        Swal.fire('Sudah Optimal', data.message, 'info');
                    }
                }
            })
        }

        // 5. Mass Promotion (Enhanced with Stats)
        function confirmPromotion() {
            Swal.fire({
                title: 'Naik Kelas Massal?',
                text: "PERINGATAN: Semua santri aktif akan naik 1 tingkat. Santri Tsani akan menjadi Lulus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Ya, Naikkan Semua',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('api/promotion_handler', { method: 'POST' })
                        .then(response => {
                            if (!response.ok) { throw new Error(response.statusText) }
                            return response.json()
                        })
                        .then(data => {
                            if (data.status === 'error') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`)
                        })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    const stats = result.value.stats;
                    
                    // Fancy Stats Popup
                    Swal.fire({
                        title: '<strong>Kenaikan Kelas Selesai!</strong>',
                        icon: 'success',
                        html: `
                            <div class="mt-4 flex flex-col gap-3">
                                <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-xl border border-blue-100 dark:border-blue-800 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                            <i class="fa-solid fa-arrow-up"></i>
                                        </div>
                                        <div class="text-left">
                                            <h4 class="font-bold text-slate-700 dark:text-slate-200">Naik ke Tsani</h4>
                                            <p class="text-xs text-slate-500">Santri Awwal</p>
                                        </div>
                                    </div>
                                    <span class="text-2xl font-bold text-blue-600">${stats.tsani}</span>
                                </div>

                                <div class="bg-amber-50 dark:bg-amber-900/30 p-4 rounded-xl border border-amber-100 dark:border-amber-800 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </div>
                                        <div class="text-left">
                                            <h4 class="font-bold text-slate-700 dark:text-slate-200">Lulus (Alumni)</h4>
                                            <p class="text-xs text-slate-500">Santri Tsani</p>
                                        </div>
                                    </div>
                                    <span class="text-2xl font-bold text-amber-600">${stats.lulus}</span>
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-slate-400">Total data diproses: <b>${stats.total}</b> santri</p>
                        `,
                        confirmButtonText: 'Mantap!',
                        confirmButtonColor: '#10b981',
                        customClass: {
                            popup: 'rounded-2xl'
                        }
                    });
                }
            })
        }

        // 6. Database Reset (Pemutihan)
        function confirmDatabaseReset() {
            Swal.fire({
                title: 'PERINGATAN KERAS!',
                html: `
                    <div class="text-left bg-red-50 p-4 rounded-lg border border-red-200 text-red-800 text-sm mb-4">
                        <p class="font-bold mb-2"><i class="fa-solid fa-triangle-exclamation"></i> BAHAYA:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Semua Data Santri akan <strong>DIHAPUS PERMANEN</strong>.</li>
                            <li>Semua Data Pelanggaran akan hilang.</li>
                            <li>Tindakan ini <strong>TIDAK BISA DIBATALKAN</strong>.</li>
                        </ul>
                    </div>
                    <p class="mb-2 font-bold">Masukkan Password Admin untuk konfirmasi:</p>
                `,
                icon: 'warning',
                input: 'password',
                inputPlaceholder: 'Password Admin...',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off'
                },
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'YA, HAPUS SEMUANYA',
                cancelButtonText: 'Batal, Saya Takut',
                showLoaderOnConfirm: true,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Password harus diisi!');
                        return false;
                    }
                    
                    return fetch(`api/reset_handler?nocache=${Date.now()}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ password: password })
                    })
                    .then(response => {
                        if (response.status === 405) {
                            throw new Error('405 Method Not Allowed - Server rejected output method. Check server logs.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'error') throw new Error(data.message);
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Gagal: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Database Bersih!',
                        text: result.value.message,
                        icon: 'success'
                    }).then(() => location.reload());
                }
            });
        }

        <?php if($message): ?>
            Swal.fire({
                icon: '<?php echo strpos($message, "Error") !== false ? "error" : "success"; ?>',
                title: 'Status',
                text: '<?php echo $message; ?>'
            });
        <?php endif; ?>
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
