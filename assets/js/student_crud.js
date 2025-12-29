// Global variables
let crudModal, viewModal, form;
let currentViewData = null; // Store data for Edit from View
window.isQuickFixMode = false; // Flag for batch edit
window.quickFixRowId = null; // Store current row ID being edited

// Init on load
document.addEventListener('DOMContentLoaded', () => {
    crudModal = document.getElementById('crudModal');
    viewModal = document.getElementById('viewModal');
    form = document.getElementById('studentForm');

    // Listener for Edit Button in View Modal
    const btnEditFromView = document.getElementById('btnEditFromView');
    if (btnEditFromView) {
        btnEditFromView.onclick = function () {
            if (currentViewData) {
                closeModal('viewModal');
                setTimeout(() => {
                    editStudent(currentViewData);
                }, 100);
            }
        };
    }

    // Attach Submit Listener Here (Safe Zone)
    if (form) {
        form.onsubmit = function (e) {
            e.preventDefault();
            const formData = new FormData(form);

            // Debugging Alert (Optional, can remove later)
            // console.log("Form Submitting...");

            fetch('api/student_handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (window.isQuickFixMode && window.quickFixRowId) {
                            // Silent Success for Quick Fix
                            closeModal('crudModal');
                            markQuickFixComplete(window.quickFixRowId);

                            // Check if all done (optional immediate feedback)
                            const remaining = document.querySelectorAll('.quick-fix-pending').length;
                            if (remaining === 0) {
                                Swal.fire({
                                    title: 'Selesai!',
                                    text: 'Semua data telah diperbaiki.',
                                    icon: 'success',
                                    confirmButtonText: 'Refresh Halaman'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    title: 'Tersimpan',
                                    text: 'Lanjutkan ke data berikutnya.',
                                    icon: 'success',
                                    timer: 1000,
                                    showConfirmButton: false
                                });
                            }
                        } else {
                            // Standard Reload
                            Swal.fire({
                                title: 'Berhasil!',
                                text: data.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        }
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal menghubungi server', 'error');
                });
        };
    }
});

// Helper Function
window.getEl = function (id) {
    return document.getElementById(id);
}

// Helper to show modal
window.openModal = function (mode, data = null) {
    // Ensure elements are grabbed if DOMContentLoaded misfired or loaded dynamically
    if (!crudModal) crudModal = document.getElementById('crudModal');
    if (!form) form = document.getElementById('studentForm');

    if (crudModal) {
        crudModal.classList.remove('hidden');
        crudModal.classList.add('flex');
    } else {
        console.error("CRUD Modal element not found!");
        return;
    }

    // Reset Image Preview
    const imgPreview = document.getElementById('imgPreview');
    const iconPreview = document.getElementById('iconPreview');
    if (imgPreview && iconPreview) {
        imgPreview.src = '';
        imgPreview.classList.add('hidden');
        iconPreview.classList.remove('hidden');
    }

    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Input Mahasantri Baru';
        form.reset();
        document.getElementById('inp_id').value = '';
        document.getElementById('inp_action').value = 'create';

        document.getElementById('inp_mustawa').onchange = function () {
            const val = this.value;
            const statusSelect = document.getElementById('inp_status');
            const activeLevels = ['Awwal', 'Tsani'];

            if (activeLevels.includes(val)) {
                statusSelect.value = 'Aktif';
            } else if (val === 'Lulus') {
                statusSelect.value = 'Lulus';
            }
        };
    } else {
        document.getElementById('modalTitle').innerText = 'Edit Data Mahasantri';
        document.getElementById('inp_action').value = 'update';
        document.getElementById('inp_id').value = data.id;

        for (const key in data) {
            const el = document.getElementById('inp_' + key);
            if (el) el.value = data[key];
        }


        // Manual mapping for mismatched keys (DB vs Input ID)
        if (data.nama_ayah && document.getElementById('inp_ayah')) {
            document.getElementById('inp_ayah').value = data.nama_ayah;
        }
        if (data.nama_ibu && document.getElementById('inp_ibu')) {
            document.getElementById('inp_ibu').value = data.nama_ibu;
        }

        // Show existing photo if any
        if (data.photo_path && data.photo_path.trim() !== '') {
            if (imgPreview && iconPreview) {
                imgPreview.src = data.photo_path;
                imgPreview.classList.remove('hidden');
                iconPreview.classList.add('hidden');
            }
        }

        document.getElementById('inp_mustawa').onchange = function () {
            const val = this.value;
            const statusSelect = document.getElementById('inp_status');
            const activeLevels = ['Awwal', 'Tsani'];

            if (activeLevels.includes(val)) {
                statusSelect.value = 'Aktif';
            } else if (val === 'Lulus') {
                statusSelect.value = 'Lulus';
            }
        };
    }
}

// --- REGION API LOGIC ---
const apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';

// Cache to prevent redundant fetches
const regionCache = {
    provinces: null,
    regencies: {},
    districts: {},
    villages: {}
};

async function loadProvinces() {
    if (regionCache.provinces) return regionCache.provinces;
    const res = await fetch(`${apiBase}/provinces.json`);
    const data = await res.json();
    regionCache.provinces = data;

    const sel = document.getElementById('inp_provinsi');
    sel.innerHTML = '<option value="">Pilih Provinsi</option>';
    data.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.name;
        opt.dataset.id = p.id;
        opt.innerText = p.name;
        sel.appendChild(opt);
    });
    return data;
}

async function loadRegencies(provName, selectedVal = null) {
    const sel = document.getElementById('inp_kabupaten');
    const provOpt = Array.from(document.getElementById('inp_provinsi').options).find(o => o.value.toUpperCase() === provName.toUpperCase());
    if (!provOpt) return;

    if (!regionCache.regencies[provOpt.dataset.id]) {
        sel.disabled = true;
        sel.innerHTML = '<option value="">Loading...</option>';
        const res = await fetch(`${apiBase}/regencies/${provOpt.dataset.id}.json`);
        regionCache.regencies[provOpt.dataset.id] = await res.json();
    }

    const data = regionCache.regencies[provOpt.dataset.id];
    sel.innerHTML = '<option value="">Pilih Kab/Kota</option>';
    data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.name;
        opt.dataset.id = d.id;
        opt.innerText = d.name;
        if (selectedVal && d.name.toUpperCase() === selectedVal.toUpperCase()) opt.selected = true;
        sel.appendChild(opt);
    });
    sel.disabled = false;
    if (selectedVal) {
        const matchingOpt = Array.from(sel.options).find(o => o.value.toUpperCase() === selectedVal.toUpperCase());
        if (matchingOpt) sel.value = matchingOpt.value;
    }
}

async function loadDistricts(kabName, selectedVal = null) {
    const sel = document.getElementById('inp_kecamatan');
    const kabOpt = Array.from(document.getElementById('inp_kabupaten').options).find(o => o.value.toUpperCase() === kabName.toUpperCase());
    if (!kabOpt) return;

    if (!regionCache.districts[kabOpt.dataset.id]) {
        sel.disabled = true;
        sel.innerHTML = '<option value="">Loading...</option>';
        const res = await fetch(`${apiBase}/districts/${kabOpt.dataset.id}.json`);
        regionCache.districts[kabOpt.dataset.id] = await res.json();
    }

    const data = regionCache.districts[kabOpt.dataset.id];
    sel.innerHTML = '<option value="">Pilih Kecamatan</option>';
    data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.name;
        opt.dataset.id = d.id;
        opt.innerText = d.name;
        if (selectedVal && d.name.toUpperCase() === selectedVal.toUpperCase()) opt.selected = true;
        sel.appendChild(opt);
    });
    sel.disabled = false;
    if (selectedVal) {
        const matchingOpt = Array.from(sel.options).find(o => o.value.toUpperCase() === selectedVal.toUpperCase());
        if (matchingOpt) sel.value = matchingOpt.value;
    }
}

async function loadVillages(kecName, selectedVal = null) {
    const sel = document.getElementById('inp_kelurahan');
    const kecOpt = Array.from(document.getElementById('inp_kecamatan').options).find(o => o.value.toUpperCase() === kecName.toUpperCase());
    if (!kecOpt) return;

    if (!regionCache.villages[kecOpt.dataset.id]) {
        sel.disabled = true;
        sel.innerHTML = '<option value="">Loading...</option>';
        const res = await fetch(`${apiBase}/villages/${kecOpt.dataset.id}.json`);
        regionCache.villages[kecOpt.dataset.id] = await res.json();
    }

    const data = regionCache.villages[kecOpt.dataset.id];
    sel.innerHTML = '<option value="">Pilih Kelurahan</option>';
    data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.name;
        opt.innerText = d.name;
        if (selectedVal && d.name.toUpperCase() === selectedVal.toUpperCase()) opt.selected = true;
        sel.appendChild(opt);
    });
    sel.disabled = false;
    if (selectedVal) {
        // Try to set value directly if exact match fails but we found a case-insensitive one
        // Actually finding the exact option value is safer
        const matchingOpt = Array.from(sel.options).find(o => o.value.toUpperCase() === selectedVal.toUpperCase());
        if (matchingOpt) sel.value = matchingOpt.value;
    }
}


// Event Listeners for Chained Dropdowns
document.getElementById('inp_provinsi').addEventListener('change', function () {
    loadRegencies(this.value);
    document.getElementById('inp_kabupaten').innerHTML = '<option value="">Pilih Kab/Kota</option>';
    document.getElementById('inp_kecamatan').innerHTML = '<option value="">Pilih Kecamatan</option>';
    document.getElementById('inp_kelurahan').innerHTML = '<option value="">Pilih Kelurahan</option>';
    document.getElementById('inp_kabupaten').disabled = true;
    document.getElementById('inp_kecamatan').disabled = true;
    document.getElementById('inp_kelurahan').disabled = true;
});

document.getElementById('inp_kabupaten').addEventListener('change', function () {
    loadDistricts(this.value);
    document.getElementById('inp_kecamatan').innerHTML = '<option value="">Pilih Kecamatan</option>';
    document.getElementById('inp_kelurahan').innerHTML = '<option value="">Pilih Kelurahan</option>';
    document.getElementById('inp_kecamatan').disabled = true;
    document.getElementById('inp_kelurahan').disabled = true;
});

document.getElementById('inp_kecamatan').addEventListener('change', function () {
    loadVillages(this.value);
    document.getElementById('inp_kelurahan').innerHTML = '<option value="">Pilih Kelurahan</option>';
    document.getElementById('inp_kelurahan').disabled = true;
});


// Init Provinces on Load
document.addEventListener('DOMContentLoaded', loadProvinces);

// Attach to window for global access
window.closeModal = function (modalId) {
    const m = document.getElementById(modalId);
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
}

window.editStudent = async function (data) {
    openModal('edit', data); // Opens modal and sets basic fields

    // Robust Chained Loading for Edit Mode
    if (data.provinsi) {
        const provSelect = document.getElementById('inp_provinsi');
        // Ensure provinces loaded
        if (provSelect.options.length <= 1) {
            await loadProvinces();
        }

        // Case-Insensitive Match for Province
        const provOpt = Array.from(provSelect.options).find(o => o.value.toUpperCase() === data.provinsi.toUpperCase());
        if (provOpt) {
            provSelect.value = provOpt.value;

            // Continue Chain
            if (data.kabupaten) {
                await loadRegencies(provOpt.value, data.kabupaten);
                if (data.kecamatan) {
                    await loadDistricts(data.kabupaten, data.kecamatan);
                    if (data.kelurahan) {
                        await loadVillages(data.kecamatan, data.kelurahan);
                    }
                }
            }
        }
    }
}

// Function to preview uploaded image
window.previewImage = function (input) {
    const imgPreview = document.getElementById('imgPreview');
    const iconPreview = document.getElementById('iconPreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            imgPreview.src = e.target.result;
            imgPreview.classList.remove('hidden');
            iconPreview.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

window.viewStudent = function (data) {
    if (!viewModal) viewModal = document.getElementById('viewModal');

    if (!viewModal) {
        console.error("View Modal element not found!");
        return;
    }

    viewModal.classList.remove('hidden');
    viewModal.classList.add('flex');

    // Text Assignments
    // Format Name with Bin/Binti
    let fullName = data.nama;
    if (data.nama_ayah) {
        const connector = (data.gender === 'Akhowat') ? 'binti' : 'bin';
        // Only add if not already present to avoid duplication
        if (!fullName.toLowerCase().includes(` ${connector} `)) {
            fullName += ` ${connector} ${data.nama_ayah}`;
        }
    }
    document.getElementById('view_nama').innerHTML = fullName;
    document.getElementById('view_nim').innerText = data.nim;
    document.getElementById('view_angkatan').innerText = data.angkatan;
    document.getElementById('view_mustawa').innerText = data.mustawa;
    document.getElementById('view_status').innerText = data.status;
    document.getElementById('view_gender').innerText = data.gender;


    // Date Format (Masehi & Hijri + Ages)
    if (data.tanggal_lahir && data.tanggal_lahir !== '0000-00-00') {
        const d = new Date(data.tanggal_lahir);

        // Masehi
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        const masehiStr = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} M`;

        // Age Masehi
        const diff = Date.now() - d.getTime();
        const ageMasehi = Math.abs(new Date(diff).getUTCFullYear() - 1970);

        // Hijri - Using pure JS algorithm (no Intl dependency for compatibility)
        let hijriStr = '';
        let ageHijri = '';
        try {
            // Pure JS Hijri Conversion (Kuwaiti Algorithm)
            function gregorianToHijri(gYear, gMonth, gDay) {
                // Calculate Julian Day Number
                let jd;
                if ((gYear > 1582) || ((gYear == 1582) && (gMonth > 10)) || ((gYear == 1582) && (gMonth == 10) && (gDay > 14))) {
                    jd = Math.floor((1461 * (gYear + 4800 + Math.floor((gMonth - 14) / 12))) / 4) +
                        Math.floor((367 * (gMonth - 2 - 12 * Math.floor((gMonth - 14) / 12))) / 12) -
                        Math.floor((3 * Math.floor((gYear + 4900 + Math.floor((gMonth - 14) / 12)) / 100)) / 4) +
                        gDay - 32075;
                } else {
                    jd = 367 * gYear - Math.floor((7 * (gYear + 5001 + Math.floor((gMonth - 9) / 7))) / 4) +
                        Math.floor((275 * gMonth) / 9) + gDay + 1729777;
                }

                // Convert JD to Hijri
                let l = jd - 1948440 + 10632;
                let n = Math.floor((l - 1) / 10631);
                l = l - 10631 * n + 354;
                let j = (Math.floor((10985 - l) / 5316)) * (Math.floor((50 * l) / 17719)) +
                    (Math.floor(l / 5670)) * (Math.floor((43 * l) / 15238));
                l = l - (Math.floor((30 - j) / 15)) * (Math.floor((17719 * j) / 50)) -
                    (Math.floor(j / 16)) * (Math.floor((15238 * j) / 43)) + 29;

                let hMonth = Math.floor((24 * l) / 709);
                let hDay = l - Math.floor((709 * hMonth) / 24);
                let hYear = 30 * n + j - 30;

                return { day: hDay, month: hMonth, year: hYear };
            }

            const hijriMonths = ['Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir', 'Jumadil Awal', 'Jumadil Akhir', 'Rajab', 'Syaban', 'Ramadhan', 'Syawal', 'Dzulqadah', 'Dzulhijjah'];

            const hijri = gregorianToHijri(d.getFullYear(), d.getMonth() + 1, d.getDate());
            hijriStr = `${hijri.day} ${hijriMonths[hijri.month - 1]} ${hijri.year} H`;

            // Calculate Hijri Age (Diff in days / 354.36708)
            const daysDiff = diff / (1000 * 60 * 60 * 24);
            ageHijri = Math.floor(daysDiff / 354.36708);
        } catch (e) {
            console.error("Hijri conversion failed", e);
            hijriStr = '? H';
            ageHijri = '?';
        }

        let birthPlace = data.tempat_lahir ? data.tempat_lahir + ', ' : '';

        // Final Output: "Tempat, 08 Juli 2000 M (25 Thn) / 14 Rabiul Akhir 1421 H (26 Thn)"
        document.getElementById('view_ttl').innerText = `${birthPlace}${masehiStr} (${ageMasehi} Thn) / ${hijriStr} (${ageHijri} Thn)`;
    } else {
        document.getElementById('view_ttl').innerText = '-';
    }


    // Construct Full Address Display
    let addrParts = [];
    if (data.alamat_lengkap) addrParts.push(data.alamat_lengkap);
    if (data.kelurahan) addrParts.push(`Kel. ${data.kelurahan}`);
    if (data.kecamatan) addrParts.push(`Kec. ${data.kecamatan}`);
    if (data.kabupaten) addrParts.push(data.kabupaten);
    if (data.provinsi) addrParts.push(data.provinsi);

    // Revised Display Logic (Asal & PPUI)
    const asalWrapper = document.getElementById('view_asal_wrapper');
    if (asalWrapper) {
        let region = [data.kabupaten, data.provinsi].filter(Boolean).join(', ');
        if (!region) region = data.asal || '-';

        let html = `<span class="font-medium text-slate-700 dark:text-slate-200">${region}</span>`;

        if (data.asal_ppui && data.asal_ppui.trim() !== '') {
            html += `
                <span class="text-slate-300 mx-1">•</span>
                <span class="text-accent font-semibold">${data.asal_ppui}</span>
            `;
        }
        asalWrapper.innerHTML = html;
    }

    let street = [data.alamat_lengkap, data.kelurahan ? 'Kel. ' + data.kelurahan : '', data.kecamatan ? 'Kec. ' + data.kecamatan : ''].filter(Boolean).join(', ');
    document.getElementById('view_alamat').innerText = street || '-';

    document.getElementById('view_ayah').innerText = data.nama_ayah || data.ayah || '-';
    document.getElementById('view_ibu').innerText = data.nama_ibu || data.ibu || '-';
    document.getElementById('view_wa').innerText = data.wa_wali || '-';

    const waLink = document.getElementById('view_wa_link');

    if (data.wa_wali) {
        // Format WA Number (08 -> 62) for Link
        let waNum = data.wa_wali.replace(/\D/g, ''); // Remove non-digits

        // Display Logic (08xx xxxx xxxx)
        let displayNum = waNum;
        if (displayNum.startsWith('62')) displayNum = '0' + displayNum.substring(2);
        else if (displayNum.startsWith('8')) displayNum = '0' + displayNum;

        // Grouping
        if (displayNum.length <= 10) {
            displayNum = displayNum.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
        } else if (displayNum.length <= 12) {
            displayNum = displayNum.replace(/(\d{4})(\d{4})(\d{4})/, '$1 $2 $3');
        } else {
            displayNum = displayNum.replace(/(\d{4})(\d{4})(\d{4,})/, '$1 $2 $3');
        }
        document.getElementById('view_wa').innerText = displayNum;

        if (waNum.startsWith('0')) {
            waNum = '62' + waNum.substring(1);
        }
        waLink.href = `https://wa.me/${waNum}`;
        waLink.classList.remove('hidden');
        waLink.parentElement.classList.remove('hidden');
    } else {
        waLink.classList.add('hidden');
        waLink.parentElement.classList.add('hidden');
    }

    // Styling Status
    const st = document.getElementById('view_status');
    st.className = `px-2 py-1 rounded text-xs font-bold ${data.status === 'Aktif' ? 'bg-emerald-100 text-emerald-700' :
        (data.status === 'Lulus' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')
        }`;

    // Photo Handling
    const img = document.getElementById('view_photo');
    const icon = document.getElementById('view_icon');

    img.classList.add('hidden');
    icon.classList.remove('hidden');

    if (data.photo_path && data.photo_path.trim() !== '') {
        img.src = data.photo_path;
        img.classList.remove('hidden');
        icon.classList.add('hidden');
        // Error fallback
        img.onerror = function () {
            this.classList.add('hidden');
            icon.classList.remove('hidden');
        }
    } else {
        icon.className = `fa-solid ${data.gender === 'Ikhwan' ? 'fa-user' : 'fa-user-hijab'} text-5xl text-slate-300`;
    }

    // Update global state
    currentViewData = data;
}

window.deleteStudent = function (id) {
    Swal.fire({
        title: 'Hapus Data?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#cbd5e1',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: '#fff',
        customClass: {
            popup: 'rounded-2xl'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`api/student_handler.php?id=${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data telah dihapus.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }
    })
}

// --- HISTORY MODAL LOGIC ---
let currentStudentId = 0;

function openHistoryModal(studentId, filterType = null) {
    currentStudentId = studentId;

    const histModal = document.getElementById('historyModal');
    const histContent = document.getElementById('historyModalContent');

    if (!histModal) return;

    histModal.classList.remove('hidden');
    histModal.classList.add('flex');

    setTimeout(() => {
        histContent.classList.remove('scale-95', 'opacity-0');
        histContent.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Initial Loading State (with Drawers structure placeholder)
    const timeline = document.getElementById('historyTimeline');
    timeline.innerHTML = `
        <div class="text-center py-10 text-slate-400">
            <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i>
            <p>Mengambil data...</p>
        </div>
    `;

    // Fetch Data
    fetch(`api/get_violations.php?student_id=${studentId}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                // Populate Header
                const s = res.student;
                document.getElementById('histName').innerText = s.nama;
                document.getElementById('histNIM').innerText = s.nim;

                const imgContainer = document.getElementById('histPhoto');
                if (s.photo_path) {
                    imgContainer.innerHTML = `<img src="${s.photo_path}" class="w-full h-full object-cover">`;
                } else {
                    const icon = s.gender === 'Akhowat' ? 'fa-user-hijab' : 'fa-user';
                    imgContainer.innerHTML = `<div class="w-full h-full flex items-center justify-center text-slate-400 dark:text-slate-500"><i class="fa-solid ${icon}"></i></div>`;
                }

                // Group Violations
                const ringan = res.violations.filter(v => v.jenis === 'Ringan');
                const sedang = res.violations.filter(v => v.jenis === 'Sedang');
                const berat = res.violations.filter(v => v.jenis === 'Berat');

                if (res.violations.length === 0) {
                    timeline.innerHTML = `
                        <div class="text-center py-12">
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-check text-2xl text-emerald-500"></i>
                            </div>
                            <h3 class="text-slate-800 dark:text-slate-200 font-medium">Bersih!</h3>
                            <p class="text-slate-500 text-sm mt-1">Santri ini belum memiliki catatan pelanggaran.</p>
                        </div>
                    `;
                } else {
                    // Build Drawers
                    let html = '';

                    // BERAT Drawer
                    html += buildDrawer('Berat', berat, 'red', filterType === 'Berat' || berat.length > 0);

                    // SEDANG Drawer
                    html += buildDrawer('Sedang', sedang, 'orange', filterType === 'Sedang');

                    // RINGAN Drawer
                    html += buildDrawer('Ringan', ringan, 'yellow', filterType === 'Ringan');

                    timeline.innerHTML = html;
                }
            } else {
                Swal.fire('Error', res.message || 'Gagal mengambil data', 'error');
                closeModal('historyModal');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            closeModal('historyModal');
        });
}

function buildDrawer(type, items, colorName, isOpen = false) {
    const count = items.length;
    // Map tailwind colors
    const colors = {
        red: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-100', icon: 'text-red-500' },
        orange: { bg: 'bg-orange-50', text: 'text-orange-700', border: 'border-orange-100', icon: 'text-orange-500' },
        yellow: { bg: 'bg-yellow-50', text: 'text-yellow-700', border: 'border-yellow-100', icon: 'text-yellow-600' }
    };
    const c = colors[colorName];
    const drawerId = `drawer-${type}`;
    const arrowId = `arrow-${type}`;
    const displayStyle = isOpen ? 'block' : 'hidden'; // Make sure this logic is sound
    const rotateClass = isOpen ? 'rotate-180' : '';

    let contentHtml = '';
    if (count === 0) {
        contentHtml = `<div class="p-4 text-center text-slate-400 text-sm italic">Tidak ada pelanggaran ${type.toLowerCase()}.</div>`;
    } else {
        items.forEach(v => {
            // SP Badge Logic
            let spBadge = '';
            if (v.tingkat_sanksi === 'DO') spBadge = '<span class="ml-2 text-[10px] bg-slate-900 text-white px-2 py-0.5 rounded font-bold">DO</span>';
            else if (v.tingkat_sanksi === 'SP3') spBadge = '<span class="ml-2 text-[10px] bg-red-600 text-white px-2 py-0.5 rounded font-bold">SP3</span>';
            else if (v.tingkat_sanksi === 'SP2') spBadge = '<span class="ml-2 text-[10px] bg-orange-500 text-white px-2 py-0.5 rounded font-bold">SP2</span>';
            else if (v.tingkat_sanksi === 'SP1') spBadge = '<span class="ml-2 text-[10px] bg-yellow-500 text-white px-2 py-0.5 rounded font-bold">SP1</span>';

            contentHtml += `
            <div class="bg-white dark:bg-slate-800 p-4 border-b border-slate-100 dark:border-slate-700 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-mono"><i class="fa-regular fa-calendar mr-1"></i>${v.tanggal}</span>
                        ${spBadge}
                    </div>
                    <button onclick="deleteViolation(${v.id}, ${currentStudentId})" class="text-slate-300 hover:text-red-500 transition-colors" title="Hapus">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>
                <p class="text-slate-700 dark:text-slate-200 text-sm font-medium mb-1">${v.deskripsi || '-'}</p>
                <div class="text-xs text-slate-500">
                    <strong class="text-slate-600 dark:text-slate-400">Sanksi:</strong> ${v.sanksi || '-'}
                </div>
            </div>`;
        });
    }

    return `
    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden mb-3">
        <button onclick="toggleDrawer('${type}')" class="w-full flex items-center justify-between p-4 ${c.bg} ${c.text} hover:opacity-90 transition-all text-left">
            <div class="flex items-center gap-3">
                <span class="font-bold text-sm tracking-wide uppercase">Pelanggaran ${type}</span>
                <span class="bg-white/60 dark:bg-black/20 px-2 py-0.5 rounded-md text-xs font-bold">${count}</span>
            </div>
            <i id="${arrowId}" class="fa-solid fa-chevron-down transition-transform duration-300 ${rotateClass}"></i>
        </button>
        <div id="${drawerId}" class="${displayStyle} bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700">
            ${contentHtml}
        </div>
    </div>`;
}

function toggleDrawer(type) {
    const drawer = document.getElementById(`drawer-${type}`);
    const arrow = document.getElementById(`arrow-${type}`);

    if (drawer.classList.contains('hidden')) {
        drawer.classList.remove('hidden');
        drawer.classList.add('block');
        arrow.classList.add('rotate-180');
    } else {
        drawer.classList.remove('block');
        drawer.classList.add('hidden');
        arrow.classList.remove('rotate-180');
    }
}

function deleteViolation(id, studentId) {
    Swal.fire({
        title: 'Hapus data ini?',
        text: "Data tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#cbd5e1',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`api/violation_handler.php?id=${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Refresh the modal content without closing
                        openHistoryModal(studentId);

                        // Optional: Show small toast
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({ icon: 'success', title: 'Data terhapus' });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                });
        }
    });
}
