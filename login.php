<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/login.php
session_start();
require_once 'config/database.php';

// Handle Login Logic Directly Here
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=Username dan Password wajib diisi");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Normalize 'admin' to 'superadmin' (Backward Compatibility)
            $safeRole = $user['role'] === 'admin' ? 'superadmin' : $user['role'];
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $safeRole;
            $_SESSION['linked_id'] = $user['linked_id']; // Store linked ID (Guru/Santri)

            // RBAC Redirection
            if ($safeRole === 'wali') {
                header("Location: profile_santri.php"); // Wali goes to specific view
            } elseif ($user['role'] === 'guru') {
                header("Location: index.php"); // Guru uses dashboard but with limited view
            } else {
                header("Location: index.php"); // Superadmin/Admin
            }
            exit;
        } else {
            header("Location: login.php?error=Username atau Password salah");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: login.php?error=Database Error: " . urlencode($e->getMessage()));
        exit;
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Jamiah Abat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#064E3B',
                        secondary: '#059669',
                        accent: '#D97706',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        arabic: ['Amiri', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md border-t-4 border-primary">
        <div class="text-center mb-6">
            <i class="fa-solid fa-mosque text-4xl text-primary mb-2"></i>
            <h1 class="text-2xl font-bold text-gray-800 font-arabic">Jamiah Abat</h1>
            <p class="text-sm text-gray-500">Sistem Informasi Mahasantri</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" name="username" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Masukkan username" required>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="********" required>
                </div>
            </div>
            <button type="submit" name="login" class="w-full bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-emerald-800 transition duration-300 shadow-md">
                MASUK
            </button>
        </form>
        
        <div class="mt-6 text-center text-xs text-gray-400">
            &copy; 2025 Database Jamiah Abat
        </div>
    </div>
</body>
</html>
