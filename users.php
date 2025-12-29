<?php
require_once 'functions.php';
requireLogin();
requireRole('superadmin');
$page_title = 'Manajemen Admin';

// Fetch Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Jamiah Abat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>if(localStorage.getItem('sidebarCollapsed')==='true'){document.write('<style>#sidebar{display:none!important}</style>');}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { primary: '#064E3B', secondary: '#059669', accent: '#D97706' },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased h-screen overflow-hidden flex dark:bg-slate-900 dark:text-slate-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden relative">
        <?php include 'includes/header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 dark:bg-slate-900">
            
            <div class="flex justify-between items-center mb-6">

                <button onclick="openModal('create')" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-emerald-800 shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Tambah Admin
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:bg-slate-800 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Username</th>
                                <th class="px-6 py-3">Nama Lengkap</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <?php foreach($users as $u): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 font-mono text-xs"><?php echo $u['id']; ?></td>
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-bold uppercase tracking-wider dark:bg-blue-900/50 dark:text-blue-300"><?php echo htmlspecialchars($u['role']); ?></span>
                                    <?php if($u['linked_id']): ?>
                                        <span class="block text-[10px] text-gray-500 mt-1">ID: <?php echo htmlspecialchars($u['linked_id']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    <button onclick='editUser(<?php echo json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="text-amber-500 hover:text-amber-700" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                    <button onclick="resetPassword(<?php echo $u['id']; ?>)" class="text-blue-500 hover:text-blue-700" title="Reset Password ke '123'"><i class="fa-solid fa-key"></i></button>
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                    <?php else: ?>
                                        <span class="text-gray-300 cursor-not-allowed"><i class="fa-solid fa-trash"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <i class="fa-solid fa-circle-info mr-2"></i> Password default untuk admin baru atau reset adalah: <strong>123</strong>. Harap segera diganti setelah login.
                </p>
            </div>

        </main>
    </div>

    <!-- Modal -->
    <div id="userModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md dark:bg-slate-800 dark:border dark:border-slate-700">
            <div class="p-6 border-b dark:border-slate-700">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-800 dark:text-white">Tambah Admin</h3>
            </div>
            <form id="userForm" class="p-6 space-y-4">
                <input type="hidden" name="action" id="inp_action" value="create">
                <input type="hidden" name="id" id="inp_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" name="username" id="inp_username" class="w-full border p-2 rounded mt-1 bg-gray-50 focus:ring-primary focus:border-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
                </div>
                <div>
                    <input type="text" name="full_name" id="inp_full_name" class="w-full border p-2 rounded mt-1 bg-gray-50 focus:ring-primary focus:border-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
                </div>
                
                <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hak Akses / Role</label>
                     <select name="role" id="inp_role" class="w-full border p-2 rounded mt-1 bg-gray-50 focus:ring-primary focus:border-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                         <option value="superadmin">Superadmin (Full Access)</option>
                         <option value="guru">Guru (Akademik & Disiplin)</option>
                         <option value="wali">Wali Santri (View Only)</option>
                     </select>
                </div>

                <div id="div_linked_id" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hubungkan Data (Opsional)</label>
                    
                    <!-- Search Input -->
                    <div class="relative">
                         <input type="text" id="inp_search_link" placeholder="Ketik nama santri..." class="w-full border p-2 pl-10 rounded mt-1 bg-gray-50 focus:ring-primary focus:border-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white" autocomplete="off">
                         <i class="fa-solid fa-magnifying-glass absolute left-3 top-4 text-gray-400"></i>
                         <!-- Hidden ID Field -->
                         <input type="hidden" name="linked_id" id="inp_linked_id">
                         <!-- Clear Button (Hidden by default) -->
                         <button type="button" id="btn_clear_link" onclick="clearLinkedId()" class="absolute right-2 top-3 text-slate-400 hover:text-red-500 hidden"><i class="fa-solid fa-times-circle"></i></button>
                    </div>

                    <!-- Autocomplete Dropdown -->
                    <div id="search_results" class="hidden absolute z-50 w-full bg-white dark:bg-slate-800 border dark:border-slate-600 rounded-b shadow-lg max-h-48 overflow-y-auto mt-1"></div>

                    <!-- Selected Indicator -->
                    <div id="selected_link_info" class="hidden mt-2 p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm rounded border border-emerald-100 dark:border-emerald-800 flex items-center gap-2">
                        <i class="fa-solid fa-link"></i>
                        <span id="selected_link_text"></span>
                    </div>

                    <p class="text-[10px] text-gray-500 mt-1">Wajib diisi jika Role adalah <b>Wali Santri</b>.</p>
                </div>
                
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded dark:text-gray-300 dark:hover:bg-slate-700">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded hover:bg-emerald-800">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');

        function openModal(mode, data = null) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            if (mode === 'create') {
                document.getElementById('modalTitle').innerText = 'Tambah Admin Baru';
                document.getElementById('inp_action').value = 'create';
                document.getElementById('inp_id').value = '';
                document.getElementById('inp_username').value = '';
                document.getElementById('inp_username').removeAttribute('readonly');
                document.getElementById('inp_full_name').value = '';
                document.getElementById('inp_role').value = 'guru'; // Default
                document.getElementById('inp_linked_id').value = '';
            } else {
                document.getElementById('modalTitle').innerText = 'Edit Admin';
                document.getElementById('inp_action').value = 'update';
                document.getElementById('inp_id').value = data.id;
                document.getElementById('inp_username').value = data.username;
                document.getElementById('inp_username').setAttribute('readonly', true); // Cannot change username
                document.getElementById('inp_full_name').value = data.full_name;
                document.getElementById('inp_role').value = 'guru'; // Default
                clearLinkedId();
            } else {
                document.getElementById('modalTitle').innerText = 'Edit Admin';
                document.getElementById('inp_action').value = 'update';
                document.getElementById('inp_id').value = data.id;
                document.getElementById('inp_username').value = data.username;
                document.getElementById('inp_username').setAttribute('readonly', true); // Cannot change username
                document.getElementById('inp_full_name').value = data.full_name;
                document.getElementById('inp_role').value = data.role || 'superadmin';
                
                // Set linked ID logic
                if (data.linked_id) {
                     document.getElementById('inp_linked_id').value = data.linked_id;
                     document.getElementById('inp_search_link').value = ''; // Reset search
                     document.getElementById('selected_link_text').innerText = "ID Terhubung: " + data.linked_id;
                     document.getElementById('selected_link_info').classList.remove('hidden');
                     document.getElementById('btn_clear_link').classList.remove('hidden');
                } else {
                     clearLinkedId();
                }
            }
        }
        
        // Autocomplete Logic
        const searchInput = document.getElementById('inp_search_link');
        const searchResults = document.getElementById('search_results');

        searchInput.addEventListener('input', function() {
            const query = this.value;
            const role = document.getElementById('inp_role').value;
            // Only search if role is Wali (for now) or if we want generic search
            // Assuming type 'santri' for Wali
            
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            fetch(`api/search_ui.php?type=santri&q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.length > 0) {
                        searchResults.classList.remove('hidden');
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'p-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer text-sm text-slate-700 dark:text-slate-200';
                            div.innerHTML = `<i class="fa-solid fa-user mr-2 text-slate-400"></i> ${item.text}`;
                            div.onclick = () => selectItem(item.id, item.text);
                            searchResults.appendChild(div);
                        });
                    } else {
                        searchResults.classList.add('hidden');
                    }
                });
        });

        function selectItem(id, text) {
            document.getElementById('inp_linked_id').value = id;
            document.getElementById('selected_link_text').innerText = text;
            document.getElementById('selected_link_info').classList.remove('hidden');
            document.getElementById('inp_search_link').value = ''; // Clear search
            searchResults.classList.add('hidden');
            document.getElementById('btn_clear_link').classList.remove('hidden');
        }

        function clearLinkedId() {
            document.getElementById('inp_linked_id').value = '';
            document.getElementById('selected_link_text').innerText = '';
            document.getElementById('selected_link_info').classList.add('hidden');
            document.getElementById('inp_search_link').value = '';
            document.getElementById('btn_clear_link').classList.add('hidden');
        }

        // Close search if clicked outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function editUser(data) {
            openModal('edit', data);
        }

        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('api/user_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.status === 'success') location.reload();
            });
        };

        function resetPassword(id) {
            if(confirm('Reset password user ini menjadi "123"?')) {
                const formData = new FormData();
                formData.append('action', 'reset');
                formData.append('id', id);
                
                fetch('api/user_handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => alert(data.message));
            }
        }

        function deleteUser(id) {
            if(confirm('Hapus admin ini? Tindakan tidak bisa dibatalkan.')) {
                fetch(`api/user_handler.php?id=${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if(data.status === 'success') location.reload();
                });
            }
        }
    </script>
    <!-- Global Sidebar Scripts -->
    <?php include 'includes/global_scripts.php'; ?>
</body>
</html>
