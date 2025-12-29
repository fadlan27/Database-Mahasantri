<!-- CRUD Modal -->
<div id="crudModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm modal-animate">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 dark:border-slate-700">
        <form id="studentForm" class="p-0">
            <input type="hidden" name="action" id="inp_action" value="create">
            <input type="hidden" name="id" id="inp_id">
            
            <div class="sticky top-0 bg-white dark:bg-slate-800 p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center z-10">
                <h3 id="modalTitle" class="text-xl font-bold text-slate-800 dark:text-white">Input Mahasantri Baru</h3>
                <button type="button" onclick="closeModal('crudModal')" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Photo Upload -->
                <div class="flex flex-col items-center justify-center mb-6">
                    <div class="w-32 h-32 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center overflow-hidden border-4 border-white dark:border-slate-600 shadow-lg relative group cursor-pointer" onclick="document.getElementById('inp_photo').click()">
                        <img id="imgPreview" class="w-full h-full object-cover hidden">
                        <i id="iconPreview" class="fa-solid fa-camera text-slate-400 text-3xl group-hover:scale-110 transition-transform"></i>
                        <div class="absolute inset-0 bg-black/30 hidden group-hover:flex items-center justify-center text-white text-xs font-medium">Ubah Foto</div>
                    </div>
                    <input type="file" name="photo" id="inp_photo" class="hidden" accept="image/*" onchange="previewImage(this)">
                    <p class="text-xs text-slate-400 mt-2">Klik untuk upload foto (JPG/PNG)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama" id="inp_nama" required class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">NIM (Identitas)</label>
                        <input type="text" name="nim" id="inp_nim" required class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>

                    <div>
                         <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                         <select name="status" id="inp_status" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                             <option value="Aktif">Aktif</option>
                             <option value="Mutasi">Mutasi</option>
                             <option value="Lulus">Lulus</option>
                             <option value="Dikeluarkan">Dikeluarkan</option>
                             <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                         </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin</label>
                        <select name="gender" id="inp_gender" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="Ikhwan">Ikhwan (Laki-laki)</option>
                            <option value="Akhowat">Akhowat (Perempuan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Angkatan (Tahun)</label>
                        <input type="number" name="angkatan" id="inp_angkatan" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mustawa (Tingkat)</label>
                        <select name="mustawa" id="inp_mustawa" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="Awwal">Mustawa Awwal</option>
                            <option value="Tsani">Mustawa Tsani</option>
                            <option value="Lulus">Lulus</option>
                        </select>
                    </div>



                    <div class="col-span-1 md:col-span-2">
                         <div class="border-t border-slate-100 dark:border-slate-700 my-2"></div>
                         <h4 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Data Pribadi & Keluarga</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Domisili (Wilayah)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <select name="provinsi" id="inp_provinsi" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm">
                                <option value="">Pilih Provinsi</option>
                            </select>
                            <select name="kabupaten" id="inp_kabupaten" disabled class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm disabled:opacity-50">
                                <option value="">Pilih Kab/Kota</option>
                            </select>
                            <select name="kecamatan" id="inp_kecamatan" disabled class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm disabled:opacity-50">
                                <option value="">Pilih Kecamatan</option>
                            </select>
                            <select name="kelurahan" id="inp_kelurahan" disabled class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm disabled:opacity-50">
                                <option value="">Pilih Kelurahan</option>
                            </select>
                        </div>
                        <input type="hidden" name="asal" id="inp_asal"> <!-- Hidden field to store legacy/concatenated simplified origin if needed, or remove completely if full switch -->
                    </div>

                     <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Asal PPUI (Cabang)</label>
                        <input type="text" name="asal_ppui" id="inp_asal_ppui" placeholder="Ex: Bogor, Pusat..." class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="inp_tempat_lahir" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="inp_tanggal_lahir" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" id="inp_alamat_lengkap" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Ayah</label>
                        <input type="text" name="nama_ayah" id="inp_nama_ayah" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Ibu</label>
                        <input type="text" name="nama_ibu" id="inp_nama_ibu" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. WA Wali</label>
                         <div class="relative">
                            <span class="absolute left-4 top-2.5 text-slate-400"><i class="fa-brands fa-whatsapp"></i></span>
                            <input type="text" name="wa_wali" id="inp_wa_wali" placeholder="08..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-300 dark:bg-slate-700 dark:border-slate-600 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3 sticky bottom-0 z-10">
                <button type="button" onclick="closeModal('crudModal')" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700 font-medium transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-500/30 font-medium transition-all transform active:scale-95">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm modal-animate">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 dark:border-slate-700 relative">
        
        <button onclick="closeModal('viewModal')" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-red-100 text-slate-500 hover:text-red-500 transition-colors z-20">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="flex flex-col md:flex-row h-full">
            <!-- Sidebar / Photo Side -->
            <div class="md:w-1/3 bg-slate-50 dark:bg-slate-900/50 p-6 flex flex-col items-center justify-center text-center border-r border-slate-100 dark:border-slate-700">
                <div class="w-32 h-32 md:w-48 md:h-48 rounded-full border-4 border-white dark:border-slate-700 shadow-xl overflow-hidden mb-4 bg-white dark:bg-slate-800 flex items-center justify-center">
                    <img id="view_photo" class="w-full h-full object-cover hidden">
                    <i id="view_icon" class="fa-solid fa-user text-6xl text-slate-300"></i>
                </div>
                
                <h2 id="view_nama" class="text-xl font-bold text-slate-800 dark:text-white mb-1">Nama Mahasantri</h2>
                <div class="flex items-center justify-center gap-2 mb-4">
                     <span id="view_nim" class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 text-xs font-mono rounded">NIM</span>
                     <span id="view_gender" class="text-slate-400 text-sm">Gender</span>
                </div>

                <div class="w-full space-y-2">
                     <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm">
                        <span class="block text-xs text-slate-400 uppercase tracking-wider mb-1">Mustawa</span>
                        <span id="view_mustawa" class="font-bold text-lg text-slate-700 dark:text-slate-200">Tsani</span>
                    </div>
                </div>
            </div>

            <!-- Details Side -->
            <div class="md:w-2/3 p-6 md:p-8">
                <div class="space-y-6">
                    <!-- Academic Card -->
                    <div class="bg-slate-50/50 dark:bg-slate-700/30 rounded-xl p-5 border border-slate-100 dark:border-slate-600">
                        <h4 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-graduation-cap"></i> Data Akademik
                        </h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-400">Angkatan</span>
                                <span id="view_angkatan" class="font-medium text-slate-700 dark:text-slate-200">1</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-xs text-slate-400 mb-1">Status</span>
                                <span id="view_status" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">Aktif</span>
                            </div>

                        </div>
                    </div>

                    <!-- Personal Card -->
                    <div class="bg-slate-50/50 dark:bg-slate-700/30 rounded-xl p-5 border border-slate-100 dark:border-slate-600">
                        <h4 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-address-card"></i> Data Pribadi
                        </h4>
                        <div class="space-y-3 text-sm">
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-400">Tempat, Tanggal Lahir</span>
                                <span id="view_ttl" class="font-medium text-slate-700 dark:text-slate-200">-</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-400">Asal Daerah & PPUI</span>
                                    <div class="flex flex-wrap gap-1 items-center" id="view_asal_wrapper">
                                        <!-- Content populated by JS -->
                                    </div>
                            </div>
                             <div class="flex flex-col">
                                <span class="text-xs text-slate-400">Alamat</span>
                                <span id="view_alamat" class="text-slate-600 italic leading-snug">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Family Card (Full Width) -->
                     <div class="md:col-span-2 bg-slate-50/50 dark:bg-slate-700/30 rounded-xl p-5 border border-slate-100 dark:border-slate-600">
                        <h4 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-users"></i> Data Keluarga
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="block text-xs text-slate-400">Nama Ayah</span>
                                <span id="view_ayah" class="font-medium text-slate-700 dark:text-slate-200">-</span>
                            </div>
                             <div>
                                <span class="block text-xs text-slate-400">Nama Ibu</span>
                                <span id="view_ibu" class="font-medium text-slate-700 dark:text-slate-200">-</span>
                            </div>
                             <div>
                                <span class="block text-xs text-slate-400">Kontak Wali</span>
                                <a id="view_wa_link" href="#" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-medium flex items-center gap-1">
                                    <i class="fa-brands fa-whatsapp"></i> <span id="view_wa">-</span>
                                </a>
                            </div>
                        </div>
                     </div>
                </div>
            </div>
        </div>
        
        <!-- View Modal Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3 rounded-b-2xl">
            <button type="button" onclick="closeModal('viewModal')" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700 font-medium transition-colors">
                Tutup
            </button>
             <button type="button" id="btnEditFromView" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-500/30 font-medium transition-all transform active:scale-95 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square"></i> Edit Data
            </button>
        </div>

    </div>
</div>

<!-- History Modal (Shared) -->
<div id="historyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 modal-animate">
    <div class="bg-white dark:bg-slate-800 w-full max-w-4xl h-[90vh] flex flex-col rounded-2xl shadow-2xl overflow-hidden transform transition-all scale-95 opacity-0 border border-slate-200 dark:border-slate-700" id="historyModalContent">
        <!-- Header -->
        <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-4">
                <div id="histPhoto" class="w-12 h-12 rounded-full bg-slate-200 border border-white dark:border-slate-600 shadow overflow-hidden"></div>
                <div>
                    <h3 id="histName" class="font-bold text-lg text-slate-800 dark:text-white leading-tight">Loading...</h3>
                    <p id="histNIM" class="text-sm text-slate-500 font-mono"></p>
                </div>
            </div>
            <button onclick="closeModal('historyModal')" class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 border dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-0 bg-slate-50/50 dark:bg-slate-900/30">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Riwayat Pelanggaran</h4>
                    <!-- Optional: Add New Button can be hidden if strictly for viewing, but allowed here -->
                </div>
                
                <div id="historyTimeline" class="space-y-4">
                    <!-- Content injected via JS -->
                    <div class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i>
                        <p>Mengambil data...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 shrink-0 text-right">
            <button onclick="closeModal('historyModal')" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-sm font-medium transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>
