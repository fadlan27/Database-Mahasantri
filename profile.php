<?php
require_once 'functions.php';
requireLogin();
$page_title = 'Profil Akun';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Jamiah Abat</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
                    <div class="p-6 bg-gradient-to-r from-emerald-800 to-emerald-600 text-white">
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold">
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold"><?php echo $_SESSION['full_name']; ?></h1>
                                <p class="opacity-90">@<?php echo $_SESSION['username']; ?> (<?php echo ucfirst($_SESSION['role']); ?>)</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b dark:text-white dark:border-slate-700"><i class="fa-solid fa-lock mr-2"></i> Ganti Password</h3>
                        
                        <form id="passwordForm" class="space-y-4">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Lama</label>
                                <input type="password" name="old_password" class="w-full border p-2 rounded mt-1 dark:bg-slate-700 dark:border-slate-600 dark:text-white" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                                <input type="password" name="new_password" class="w-full border p-2 rounded mt-1 dark:bg-slate-700 dark:border-slate-600 dark:text-white" required minlength="6">
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-emerald-800 shadow-lg transition w-full md:w-auto">
                                    Simpan Password Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('passwordForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/user_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.status === 'success') location.reload();
            })
            .catch(err => alert('Error System'));
        };
    </script>
</body>
</html>
