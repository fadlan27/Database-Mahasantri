<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/functions.php

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    // Prevent Browser Caching (Security: Back Button after logout)
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// --- CSRF PROTECTION ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCsrfToken() {
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- RBAC HELPER FUNCTIONS ---

function getCurrentRole() {
    return $_SESSION['role'] ?? null;
}

function hasRole($roles) {
    // Determine if user has one of the required roles
    // $roles can be string 'admin' or array ['admin', 'guru']
    if (!isLoggedIn()) return false;
    
    $currentRole = getCurrentRole();
    if (is_array($roles)) {
        return in_array($currentRole, $roles);
    }
    return $currentRole === $roles;
}

function requireRole($roles) {
    if (!hasRole($roles)) {
        // Simple error page or redirect
        http_response_code(403);
        die("<h1>403 Forbidden</h1><p>Anda tidak memiliki akses ke halaman ini.</p><a href='index.php'>Kembali ke Dashboard</a>");
    }
}

function isSuperAdmin() {
    return hasRole('superadmin');
}

function isGuru() {
    return hasRole('guru');
}

function isWali() {
    return hasRole('wali');
}

// Sanitize Input (Result: string)
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Standardize Text (Title Case: "budi santoso" -> "Budi Santoso", with Acronym Exceptions)
function standardizeText($string) {
    if (empty($string)) return '';

    // 0. Remove Garbage Characters (Mojibake Fix)
    // Only allow: Letters, Numbers, Space, Dot, Comma, Quote, Dash, Slash, Parentheses
    $string = preg_replace('/[^a-zA-Z0-9\s\.\,\'\-\/\(\)]/', '', $string);

    // 1. Clean extra spaces
    $string = preg_replace('/\s+/', ' ', trim($string));

    // 2. Remove Trailing Punctuation (Budi. -> Budi)
    // Safety check: Remove dots/commas/dashes at the very end of string
    $string = rtrim($string, ".,- ");

    // 3. Basic Title Case
    // Reverted to ucwords for safety against encoding issues (mojibake)
    $string = ucwords(strtolower($string));

    // 2. Acronym Exceptions (Keep or Force Uppercase)
    // Add common Indonesian acronyms and Roman numerals here
    $exceptions = [
        'SD', 'SMP', 'SMA', 'SMK', 'MTS', 'MA', 'TK', 'KB', 'TPQ',
        'RT', 'RW', 'NO', 'JL', 'GANG', 'BLOK',
        'II', 'III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'XI', 'XII', // Roman numerals
        'XX', 'XXI', 'PBB', 'TNI', 'POLRI', 'DPR', 'MPR', 'PPUI', 'Pondok'
    ];

    foreach ($exceptions as $ex) {
        // Use word boundary to match exact words (case insensitive)
        // e.g. "Sd" matches "SD", "sd" matches "SD"
        // But "Tidak" does NOT match "TK" due to letters.
        // Regex: \bkeyword\b with 'i' modifier
        $pattern = '/\b' . preg_quote($ex, '/') . '\b/i';
        $string = preg_replace($pattern, $ex, $string);
    }

    // 3. Specific Fixes (Optional)
    // Fix "bin" and "binti" to allow lowercase if desired, but Title Case usually makes them Bin/Binti.
    // User requested "Title Case", so "Bin" is acceptable.

    return $string;
}

// Standardize Address (Jl, Jln -> Jl. ; No -> No. ; RT/RW handling)
function standardizeAddress($string) {
    if (empty($string)) return '';

    // 1. Uniform Abbreviations (Pre-processing)
    // We run this BEFORE Title Case to ensure we catch "jln.nama" (no space)
    // \b... matches start/end of word. \.? matches optional dot. \s* matches optional space.
    $replacements = [
        '/\b(jl|jln|jalan)\b\.?\s*/i' => 'Jl. ',
        '/\b(gg|gang)\b\.?\s*/i' => 'Gg. ',
        '/\b(ds|desa)\b\.?\s*/i' => 'Ds. ',
        '/\b(dk|dusun)\b\.?\s*/i' => 'Dsn. ',
        '/\b(blok)\b\.?\s*/i' => 'Blok ',
        '/\b(no|nomer|nomor)\b\.?\s*/i' => 'No. ',
        '/\b(rt)\b\.?\s*(\d+)/i' => 'RT $2', 
        '/\b(rw)\b\.?\s*(\d+)/i' => 'RW $2',
        '/\b(kel|kelurahan)\b\.?\s*/i' => 'Kel. ',
        '/\b(kec|kecamatan)\b\.?\s*/i' => 'Kec. ',
        '/\b(kab|kabupaten)\b\.?\s*/i' => 'Kab. ',
        '/\b(prop|prov|provinsi)\b\.?\s*/i' => 'Prov. ',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $string = preg_replace($pattern, $replacement, $string);
    }

    // 2. Punctuation Cleanup
    $string = preg_replace('/,([^\s])/', ', $1', $string); // "Bogor,Jawa" -> "Bogor, Jawa"
    $string = preg_replace('/\s+/', ' ', $string); // Remove double spaces

    // 3. Title Case & Final Cleanup
    // standardizationText will now capitalize words that were separated by the regexes
    // e.g. "Jl. habibah" -> "Jl. Habibah"
    return standardizeText($string);
}

// JSON Response Helper
function jsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Handle File Upload with Compression
function uploadPhoto($file, $customName = '', $useExactName = false) {
    // __DIR__ is Root (because functions.php is in root)
    $targetDir = __DIR__ . "/uploads/"; 
    $webPathDir = "uploads/"; 
    
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            return false; // Failed to create directory
        }
    }
    
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($extension, $allowed)) {
        return false;
    }

    // Generate Filename: Nama_Ayah.jpg (if Exact) or Nama_Ayah_Time.jpg
    if ($customName) {
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($customName));
        // Remove repetitive underscores
        $safeName = preg_replace('/_+/', '_', $safeName);
        
        if ($useExactName) {
            $fileName = $safeName . '.jpg';
        } else {
            $fileName = $safeName . '_' . time() . '.jpg';
        }
    } else {
        $fileName = time() . '_' . uniqid() . '.jpg';
    }

    $targetFilePath = $targetDir . $fileName;
    $dbFilePath = $webPathDir . $fileName;
    
    $tmpName = $file['tmp_name'];
    $quality = 60; // 0 - 100 (60 is good balance)

    list($width, $height, $type) = getimagesize($tmpName);
    
    $image = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($tmpName); break;
        case IMAGETYPE_PNG:  $image = imagecreatefrompng($tmpName); break;
        case IMAGETYPE_WEBP: $image = imagecreatefromwebp($tmpName); break;
    }

    if ($image) {
        // Resize if too big (max width 800px)
        if ($width > 800) {
            $newWidth = 800;
            $newHeight = ($height / $width) * 800;
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        // Save as JPG compressed
        if (imagejpeg($image, $targetFilePath, $quality)) {
            imagedestroy($image);
            return $dbFilePath;
        }
        imagedestroy($image);
    }
    
    // Fallback if GD fails or invalid image
    return false;
}

// Date Formatter (Indonesia: Hari, dd MMMM yyyy)
function formatDateId($date) {
    if (!$date || $date == '0000-00-00') return '';
    
    // PHP date('N') -> 1 (Senin) - 7 (Ahad)
    $days = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Ahad'];
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $dayNum = date('N', $timestamp); // 1-7
    $dayName = isset($days[$dayNum]) ? $days[$dayNum] : '';
    $dayDate = date('d', $timestamp);
    $monthName = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$dayName, $dayDate $monthName $year"; 
}

// Format Date Masehi (08 Juli 2000 M)
function formatDateMasehi($date) {
    if (!$date || $date == '0000-00-00') return '-';
    
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $dayDate = date('d', $timestamp);
    $monthName = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$dayDate $monthName $year M"; 
}

// Age Calculator
function getAge($date) {
    if (!$date || $date == '0000-00-00') return '';
    $birthDate = new DateTime($date);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Format WA (08x -> 628x)
function formatWA($number) {
    if(!$number) return '';
    $number = preg_replace('/[^0-9]/', '', $number); // Remove non-numeric
    if (substr($number, 0, 1) === '0') {
        $number = '62' . substr($number, 1);
    }
    return $number;
}

// Format Phone Number Display (0812 3456 7890)
function formatPhoneNumberDisplay($number) {
    if (empty($number)) return '-';
    
    // Clean first
    $clean = preg_replace('/[^0-9]/', '', $number);
    
    // Normalize 628 -> 08
    if (substr($clean, 0, 2) === '62') {
        $clean = '0' . substr($clean, 2);
    } elseif (substr($clean, 0, 1) === '8') {
        $clean = '0' . $clean;
    }
    
    // Format groups
    $len = strlen($clean);
    if ($len <= 10) {
        // 0812-345-678
        return preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $clean);
    } elseif ($len <= 12) {
        // 0812-3456-7890
        return preg_replace('/(\d{4})(\d{4})(\d{4})/', '$1 $2 $3', $clean);
    } else {
        // Longer?
        return preg_replace('/(\d{4})(\d{4})(\d{4,})/', '$1 $2 $3', $clean);
    }
}

// Format Name with Bin/Binti
function formatNameBin($name, $gender, $fatherName) {
    if (empty($fatherName)) return $name;
    
    $connector = ($gender === 'Akhowat') ? 'binti' : 'bin';
    // Prevent double bin if user already typed it (basic check)
    if (stripos($name, " $connector ") !== false) return $name;
    
    return $name . ' ' . $connector . ' ' . $fatherName;
}


// Current Academic Year (e.g., "2024/2025")
function getAcademicYear($offset = 0) {
    $month = date('n');
    $year = date('Y') + $offset;
    if ($month >= 7) {
        return $year . '/' . ($year + 1);
    } else {
        return ($year - 1) . '/' . $year;
    }
}

// Hijri Date Logic
require_once __DIR__ . '/includes/HijriDate.php';

// Current Hijri Year
function getHijriYear($date = 'now') {
    return HijriDate::getYear($date) . ' H';
}

function getDualYear($date = 'now') {
    $masehi = date('Y', strtotime($date));
    $hijri = getHijriYear($date);
    return "$masehi / $hijri";
}

// Activity Logging System (FIFO 100)
function logActivity($action, $details = '') {
    global $pdo;

    try {
        // 1. Insert New Log
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'System'; // Assuming session has username
        
        // Fallback for username if not in session but user_id is there
        if (!$username && $userId) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $username = $stmt->fetchColumn() ?: 'Unknown';
        }

        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, username, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $username, $action, $details]);

        // 2. Prune Old Logs (Keep only latest 100)
        // Using a subquery DELETE approach compatible with MySQL/MariaDB
        // "DELETE FROM table WHERE id <= (SELECT id FROM (SELECT id FROM table ORDER BY id DESC LIMIT 1 OFFSET 100) foo)"
        
        $sqlPrune = "DELETE FROM system_logs WHERE id <= (
            SELECT id FROM (
                SELECT id FROM system_logs ORDER BY id DESC LIMIT 1 OFFSET 100
            ) AS subquery
        )";
        $pdo->exec($sqlPrune);

    } catch (PDOException $e) {
        // Silently fail or log to file if needed, don't break app
        error_log("Log Error: " . $e->getMessage());
    }
}

function getAcademicHijriRange($offset = 0) {
    $month = date('n');
    $year = date('Y') + $offset;
    if ($month >= 7) {
        $startYear = $year;
        $endYear = $year + 1;
    } else {
        $startYear = $year - 1;
        $endYear = $year;
    }
    
    if (!class_exists('HijriDate')) {
        return "1446 - 1447 H"; 
    }

    $startH = HijriDate::getYear("$startYear-07-15");
    $endH = HijriDate::getYear("$endYear-06-15");
    
    if ($startH == $endH) {
         return $startH . " H";
    }
    
    if ($startH > $endH) {
        $temp = $startH; $startH = $endH; $endH = $temp;
    }

    return "$startH - $endH H";
}
