<?php
require_once '../functions.php';
requireLogin();

// Start output buffering to catch any PHP warnings
ob_start();
error_reporting(0); // Suppress Warnings/Notices preventing JSON corruption

header('Content-Type: application/json');

// --- 1. CONFIGURATION & DICTIONARIES ---

// List of Indonesian Provinces for Smart Detection (Sorted by length DESC to match longest first)
$PROVINCES = [
    "Nanggroe Aceh Darussalam", "Aceh", "Sumatera Utara", "Sumatera Barat", "Riau", "Jambi", 
    "Sumatera Selatan", "Bengkulu", "Lampung", "Kepulauan Bangka Belitung", "Kepulauan Riau",
    "DKI Jakarta", "Jakarta", "Jawa Barat", "Jawa Tengah", "DI Yogyakarta", "Yogyakarta", "Jawa Timur", "Banten",
    "Bali", "Nusa Tenggara Barat", "Nusa Tenggara Timur",
    "Kalimantan Barat", "Kalimantan Tengah", "Kalimantan Selatan", "Kalimantan Timur", "Kalimantan Utara",
    "Sulawesi Utara", "Sulawesi Tengah", "Sulawesi Selatan", "Sulawesi Tenggara", "Gorontalo", "Sulawesi Barat",
    "Maluku", "Maluku Utara", "Papua Barat", "Papua", "Papua Selatan", "Papua Tengah", "Papua Pegunungan", "Papua Barat Daya"
];

// Column Synonyms Mapping (LowerCase)
$COLUMN_MAP = [
    'nim' => ['nim', 'nomor induk', 'no induk', 'stambuk', 'nis'],
    'nama' => ['nama', 'nama lengkap', 'nama santri', 'fullname', 'full name'],
    'gender' => ['gender', 'jenis kelamin', 'jk', 'sex', 'l/p'],
    'tempat_lahir' => ['tempat lahir', 'tmp lahir', 'tpt lahir', 'pob', 'kota lahir'],
    'tanggal_lahir' => ['tanggal lahir', 'tgl lahir', 'dob'],
    'angkatan' => ['angkatan', 'tahun masuk', 'tahun'],
    'mustawa' => ['mustawa', 'tingkat', 'kelas', 'id_kelas'],
    'status' => ['status', 'status santri', 'keadaan', 'ket'],
    'asal' => ['asal', 'asal daerah', 'kota asal', 'kabupaten/kota asal'], // Legacy fallback
    'asal_ppui' => ['asal ppui', 'ppui', 'cabang ppui', 'dari ppui'],
    'alamat_lengkap' => ['alamat', 'alamat lengkap', 'alamat rumah', 'domisili'],
    'provinsi' => ['provinsi', 'prov', 'propinsi'],
    'kabupaten' => ['kabupaten', 'kota', 'kab', 'kab/kota'],
    'kecamatan' => ['kecamatan', 'kec'],
    'kelurahan' => ['kelurahan', 'desa', 'kel', 'ds'],
    'nama_ayah' => ['nama ayah', 'ayah', 'wali'],
    'nama_ibu' => ['nama ibu', 'ibu'],
    'wa_wali' => ['wa', 'no wa', 'whatsapp', 'wa wali', 'nomor wali', 'hp wali', 'nomor hp', 'hp ortu', 'no. hp', 'hp', 'telepon', 'telp', 'link wa', 'kontak', 'handphone']
];

// --- 2. HELPER FUNCTIONS ---

function getColumnIndex($headers, $key) {
    global $COLUMN_MAP;
    $possibleNames = $COLUMN_MAP[$key] ?? [];
    
    // Pass 1: Exact Matches (Priority)
    foreach ($headers as $index => $header) {
        $cleanHeader = strtolower(trim($header));
        $cleanHeader = str_replace(['.', '_', '-'], ' ', $cleanHeader);
        $cleanHeader = preg_replace('/\s+/', ' ', trim($cleanHeader)); 
        
        // Direct match
        if ($cleanHeader === $key) return $index;
        // Synonym match
        if (in_array($cleanHeader, $possibleNames)) return $index;
    }

    // Pass 2: Partial Matches (Fallback)
    foreach ($headers as $index => $header) {
        $cleanHeader = strtolower(trim($header));
        $cleanHeader = str_replace(['.', '_', '-'], ' ', $cleanHeader);
        $cleanHeader = preg_replace('/\s+/', ' ', trim($cleanHeader)); 
        
        foreach ($possibleNames as $synonym) {
            // Skip very short synonyms for partial matching to avoid false positives (like 'wa' in 'mustawa')
            if (strlen($synonym) <= 2) continue; 
            
            if (strpos($cleanHeader, $synonym) !== false) return $index;
        }
    }
    
    return -1;
}

function parseSmartAddress($fullAddress, $existingData = []) {
    global $PROVINCES;
    
    $result = [
        'provinsi' => $existingData['provinsi'] ?? '',
        'kabupaten' => $existingData['kabupaten'] ?? '',
        'kecamatan' => $existingData['kecamatan'] ?? '',
        'kelurahan' => $existingData['kelurahan'] ?? '',
        'alamat_jalan' => $fullAddress // Start with full, remove parts as we find them
    ];

    // Normalize for search
    $searchStr = $fullAddress;

    // 1. Detect Province (Largest Scale First)
    if (empty($result['provinsi'])) {
        foreach ($PROVINCES as $prov) {
            // Case-insensitive check
            if (stripos($searchStr, $prov) !== false) {
                $result['provinsi'] = $prov;
                // Remove from search string to avoid re-detection in address
                $searchStr = str_ireplace($prov, '', $searchStr);
                break; 
            }
        }
    }

    // Custom Regex Logic for loose parsing (no comma required)
    
    // 2. Detect Kabupaten/Kota
    // Patterns: "Kab. Bogor", "Kota Surabaya", "Kabupaten Bandung"
    if (empty($result['kabupaten'])) {
        if (preg_match('/(Kabupaten|Kab\.?|Kota)\s+([a-z\s]+?)(?=\s+(?:Kec|Prov|Jalan|Jl|Kel|Desa|Ds)|$|\.|,)/iu', $searchStr, $matches)) {
            $prefix = $matches[1]; // Kab/Kota
            $name = trim($matches[2]);
            $result['kabupaten'] = "$prefix $name";
            $searchStr = str_replace($matches[0], '', $searchStr);
        }
    }

    // 3. Detect Kecamatan
    // Patterns: "Kec. Cibinong", "Kecamatan Beji"
    if (empty($result['kecamatan'])) {
        if (preg_match('/(Kecamatan|Kec\.?)\s+([a-z\s]+?)(?=\s+(?:Kab|Kota|Prov|Jalan|Jl|Kel|Desa|Ds)|$|\.|,)/iu', $searchStr, $matches)) {
            $result['kecamatan'] = trim($matches[2]);
            $searchStr = str_replace($matches[0], '', $searchStr);
        }
    }

    // 4. Detect Kelurahan/Desa
    // Patterns: "Kel. X", "Desa Y", "Ds. Z"
    if (empty($result['kelurahan'])) {
        if (preg_match('/(Kelurahan|Kel\.?|Desa|Ds\.?)\s+([a-z\s]+?)(?=\s+(?:Kec|Kab|Kota|Prov|Jalan|Jl)|$|\.|,)/iu', $searchStr, $matches)) {
            $result['kelurahan'] = trim($matches[2]);
            $searchStr = str_replace($matches[0], '', $searchStr);
        }
    }

    // Cleanup Alamat Jalan (Remaining string)
    // Remove punctuation leftovers from removal
    $cleanAddr = preg_replace('/[,.]+/', ',', $result['alamat_jalan']);
    // Option: If we want to strictly remove the detected parts from the visible address:
    // $cleanAddr = $searchStr; 
    
    // But usually user wants the full original address in 'alamat_lengkap' and just parsed data in columns.
    // So we'll keep the full address in 'alamat_lengkap' (handled in main loop) and just return components.
    
    return $result;
}

// --- 3. MAIN HANDLER ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => 'Upload File Gagal. Code: ' . ($_FILES['csv_file']['error'] ?? 'Unknown')]);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$origName = $_FILES['csv_file']['name'];
$fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if ($fileExt === 'sql') {
    // Return 200 so the JS reads the JSON message
    http_response_code(200); 
    echo json_encode(['status' => 'error', 'message' => '<b>Salah Fitur!</b><br>File .sql tidak bisa diimport di sini.<br><br>Gunakan menu tombol oranye:<br><b>Database > Restore DB</b>']);
    exit;
}

// Global DB Transaction start
$pdo->beginTransaction();

try {
    $rows = [];
    $headers = [];

    // --- A. XLSX HANDLING ---
    if ($fileExt === 'xlsx' || $fileExt === 'xls') {
        require_once 'SimpleXLSX.php';
        if ($xlsx = Shuchkin\SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            if (!empty($rows)) {
                $headers = array_map('trim', $rows[0]);
                array_shift($rows); // Remove Header
            }
        } else {
             throw new Exception("Error Excel: " . Shuchkin\SimpleXLSX::parseError());
        }
    } 
    // --- B. CSV HANDLING ---
    else {
        $handle = fopen($file, "r");
        if ($handle === FALSE) throw new Exception("Gagal membuka file CSV.");
        
        // Detect Delimiter
        $delimiter = ",";
        $firstLine = fgets($handle);
        if ($firstLine && substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ";";
        }
        rewind($handle);

        // Read and Clean Header
        if (($csvHeaders = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
             // BOM Removal
             $csvHeaders[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csvHeaders[0]);
             $headers = array_map('trim', $csvHeaders);
        }

        // Read All Rows
        while (($r = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
             if (array_filter($r)) $rows[] = $r;
        }
        fclose($handle);
    }

    if (empty($headers)) {
        throw new Exception("File kosong atau format header salah.");
    }

    // Map Headers to DB Columns
    $colIndices = [];
    $logMsg = "--- New Import Debug ---\n";
    $logMsg .= "Detected Headers: " . implode(" | ", $headers) . "\n";
    
    foreach ($COLUMN_MAP as $dbKey => $synonyms) {
        $colIndices[$dbKey] = getColumnIndex($headers, $dbKey);
        $logMsg .= "Mapped '$dbKey' to Index: " . $colIndices[$dbKey] . "\n";
    }
    file_put_contents('debug_log.txt', $logMsg, FILE_APPEND);

    // Validate Essential Columns
    if ($colIndices['nama'] === -1) {
        // Fallback: If no 'nama' found, try column 1 (index 1) if strictly existing, else fail
        // Better to fail and ask user to rename
        throw new Exception("Kolom 'Nama' tidak ditemukan. Pastikan ada header 'Nama' atau 'Nama Lengkap'.");
    }

    $sql = "INSERT INTO mahasantri 
            (nim, nama, gender, tempat_lahir, tanggal_lahir, angkatan, mustawa, status, asal, asal_ppui, alamat_lengkap, provinsi, kabupaten, kecamatan, kelurahan, nama_ayah, nama_ibu, wa_wali) 
            VALUES (:nim, :nama, :gender, :tempat_lahir, :tanggal_lahir, :angkatan, :mustawa, :status, :asal, :asal_ppui, :alamat_lengkap, :provinsi, :kabupaten, :kecamatan, :kelurahan, :nama_ayah, :nama_ibu, :wa_wali)
            ON DUPLICATE KEY UPDATE 
            nama=VALUES(nama), gender=VALUES(gender), tempat_lahir=VALUES(tempat_lahir), tanggal_lahir=VALUES(tanggal_lahir), 
            angkatan=VALUES(angkatan), mustawa=VALUES(mustawa), status=VALUES(status), 
            asal=VALUES(asal), asal_ppui=VALUES(asal_ppui), alamat_lengkap=VALUES(alamat_lengkap),
            provinsi=VALUES(provinsi), kabupaten=VALUES(kabupaten), kecamatan=VALUES(kecamatan), kelurahan=VALUES(kelurahan),
            nama_ayah=VALUES(nama_ayah), nama_ibu=VALUES(nama_ibu), wa_wali=VALUES(wa_wali)";

    $stmt = $pdo->prepare($sql);
    $rowNumber = 1; // Header is 1
    $successCount = 0;
    $errors = [];

    foreach ($rows as $row) {
        $rowNumber++;
        
        // Skip empty rows
        if (empty(implode('', $row))) continue;

        // Extract Data using Map
        $data = [];
        foreach ($COLUMN_MAP as $key => $v) {
            $idx = $colIndices[$key];
            if ($idx > -1 && isset($row[$idx])) {
                $data[$key] = trim($row[$idx]);
            } else {
                $data[$key] = '';
            }
        }

        // --- SMART LOGIC ---

        // 1. Mandatory Fields
        if (empty($data['nama'])) continue; 
        if (empty($data['nim'])) {
            // Generate temporary/auto NIM if missing? No, user usually wants errors or generated.
            // For now, let's auto-generate ONLY if missing ID is acceptable. 
            // Better behavior: Use Name hash or random for unique key if strict schema isn't enforced.
            // Assuming DB has NIM As Primary Key, we ideally need it.
            // Fallback: Generate generic NIM
            $data['nim'] = date('Y') . sprintf('%04d', rand(1, 9999));
        }

        // 1. Formatting & Cleaning
        
        // 1a. PPUI Formatting (Auto-add prefix)
        // e.g. "Borneo" -> "PPUI Borneo"
        // 1a. PPUI Formatting
        // User requested NO auto-prefix "PPUI". Just standardize.
        if (!empty($data['asal_ppui'])) {
            // Clean noise: Remove ?, !, and parenthesis leftovers
            // e.g. "MARGODADI ?" -> "MARGODADI"
            $data['asal_ppui'] = preg_replace('/[?!\[\]\(\)]+/', '', $data['asal_ppui']);
            $data['asal_ppui'] = standardizeText($data['asal_ppui']);
        }
        
        // 2. Name Formatting
        $data['nama'] = standardizeText($data['nama']);
        
        // Remove Bin/Binti and father's name (Keep only student name)
        // Matches " bin " or " binti " followed by anything to the end of the string
        $data['nama'] = preg_replace('/\s+(bin|binti)\s+.*$/i', '', $data['nama']);

        $data['gender'] = clean($data['gender']); 
        
        // --- SMART TTL (TEMPAT TANGGAL LAHIR) MIXED COLUMN HANDLING ---
        $raw_place = $data['tempat_lahir'];
        $raw_date = $data['tanggal_lahir'];
        $detected_date_str = null;

        // Indonesian Month Dictionary
        $months_regex = 'Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|Jan|Feb|Mar|Apr|Jun|Jul|Agu|Sep|Okt|Nov|Des';

        // Regex Patterns
        // 1. Numeric: 12-05-2010 or 12/05/2010 or 12.05.2010
        $pat_numeric = '/(\d{1,2})[\-\/\.\s](\d{1,2})[\-\/\.\s](\d{4})/'; 
        // 2. Text: 12 Mei 2010 or 12 Januari 2010
        $pat_text = '/(\d{1,2})[\s\-]+(' . $months_regex . ')[\s\-]+(\d{4})/i';

        // A. Try finding in 'tanggal_lahir' column first
        if (preg_match($pat_numeric, $raw_date, $m) || preg_match($pat_text, $raw_date, $m)) {
            $detected_date_str = $m[0];
        } 
        // B. If not found, try finding inside 'tempat_lahir' (Mixed "Place, Date")
        elseif (preg_match($pat_numeric, $raw_place, $m) || preg_match($pat_text, $raw_place, $m)) {
            $detected_date_str = $m[0];
            // Remove the date from place string so we just keep the city
            $raw_place = str_ireplace($detected_date_str, '', $raw_place);
        }

        // D. Fallback: Parse Incomplete Dates (Year Only or Month+Year)
        if (!$detected_date_str) {
            // Search in both columns combined to find loose years
            $combined_search = $input_tgl . ' ' . $raw_place;
            
            // Pattern 3: Month Name + Year (e.g. "Agustus 2010" -> "01 Agustus 2010")
            if (preg_match('/(' . $months_regex . ')[\s\-]+(\d{4})/i', $combined_search, $m)) {
                $detected_date_str = "01 " . $m[1] . " " . $m[2];
                // Remove from place if found there
                $raw_place = str_ireplace($m[0], '', $raw_place);
            }
            // Pattern 4: Year Only (e.g. "2010" -> "01 Januari 2010")
            // Strict regex to avoid picking up NIM/Phone (Must be 19xx or 20xx)
            elseif (preg_match('/\b(19|20)(\d{2})\b/', $combined_search, $m)) {
                $detected_date_str = "01 Januari " . $m[0];
                $raw_place = str_ireplace($m[0], '', $raw_place);
            }
        }

        // C. Parse the found date string into YYYY-MM-DD
        $clean_date_db = null;
        if ($detected_date_str) {
            // Convert Month Names to Numbers
            $replacements = [
                'januari'=>'01','jan'=>'01', 'februari'=>'02','feb'=>'02', 'maret'=>'03','mar'=>'03',
                'april'=>'04','apr'=>'04', 'mei'=>'05', 'juni'=>'06','jun'=>'06',
                'juli'=>'07','jul'=>'07', 'agustus'=>'08','agu'=>'08', 'september'=>'09','sep'=>'09',
                'oktober'=>'10','okt'=>'10', 'november'=>'11','nov'=>'11', 'desember'=>'12','des'=>'12',
            ];
            // Replace text month with number
            $temp_d = preg_replace_callback('/[a-zA-Z]+/', function($matches) use ($replacements) {
                $k = strtolower($matches[0]);
                return $replacements[$k] ?? $matches[0];
            }, $detected_date_str);

            // Extract numbers (now it should be like "12 05 2010")
            if (preg_match_all('/\d+/', $temp_d, $nums)) {
                $parts = $nums[0];
                if (count($parts) >= 3) {
                    $p1 = (int)$parts[0]; $p2 = (int)$parts[1]; $p3 = (int)$parts[2];
                    
                    // Guess Year, Month, Day
                    $y=0; $m=0; $d=0;
                    if ($p3 > 1000) { $d=$p1; $m=$p2; $y=$p3; }      // D M Y
                    elseif ($p1 > 1000) { $y=$p1; $m=$p2; $d=$p3; }  // Y M D
                    
                    // Safety Swap if Month > 12 (US Format assumption)
                    if ($m > 12 && $d <= 12) { $tmp=$m; $m=$d; $d=$tmp; }
                    
                    if ($y > 1900 && $m > 0 && $m <= 12 && $d > 0 && $d <= 31) {
                         $clean_date_db = "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                    }
                }
            }
        }

        // Assign to Data
        $data['tanggal_lahir'] = $clean_date_db; // NULL if invalid/empty
        
        // Extra Cleanup: Aggressively remove Month Names from Place field
        // This handles cases where date was NOT detected by main regex but month name exists (e.g. "Jakarta, Agustus 2011")
        $raw_place = preg_replace('/(' . $months_regex . ')/i', '', $raw_place);
        
        // Clean the Place Field (Remove digits/punctuation leftovers)
        $raw_place = preg_replace('/[0-9\[\]\(\)\,\.\-\/]+/', ' ', $raw_place); 
        $data['tempat_lahir'] = standardizeText($raw_place);

        // 4. Smart Address Parsing
        // ...

        // 7. General Encoding Clean (Fix for \xA0 and special chars)
        // Loop through all fields to fix "Incorrect string value" errors
        foreach ($data as $key => $value) {
            // CRITICAL: Skip if value is NULL (especially for tanggal_lahir)
            // Otherwise mb_convert_encoding converts NULL to "" which breaks Strict SQL Date
            if (is_null($value)) continue;

            // Replace NBSP (Non-Breaking Space) with normal space
            $value = str_replace("\xc2\xa0", " ", $value); 
            
            // Remove Double Spaces (New Feature)
            $value = preg_replace('/\s+/', ' ', $value);

            // GLOBAL NOISE CLEANER: Remove question marks (?) and exclamation marks (!) from ALL fields
            // User reported "Margodadi?" appearing in app. This fixes it globally.
            $value = str_replace(['?', '!'], '', $value);

            // Remove other control characters if any (keeping printable)
            // Fix Windows-1252 to UTF-8 issues if possible, or just strip
            $data[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            
            // Final Trim: Aggressive Unicode Trim (removes NBSP, Zero-width, etc from start/end)
            $data[$key] = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $data[$key]);
        }
        // If Prov/Kab/Kec is empty, try to parse from Alamat Lengkap
        if (empty($data['provinsi']) || empty($data['kabupaten'])) {
            $parsed = parseSmartAddress($data['alamat_lengkap'], $data);
            if (empty($data['provinsi'])) $data['provinsi'] = $parsed['provinsi'];
            if (empty($data['kabupaten'])) $data['kabupaten'] = $parsed['kabupaten'];
            if (empty($data['kecamatan'])) $data['kecamatan'] = $parsed['kecamatan'];
            if (empty($data['kelurahan'])) $data['kelurahan'] = $parsed['kelurahan'];
        }
        
        // 5. Phone Number Cleaning (Standardize to 62...)
        $wa = preg_replace('/[^0-9]/', '', $data['wa_wali']);
        
        // If empty, skip
        if (!empty($wa)) {
            // Fix: 08 -> 628
            if (substr($wa, 0, 2) === '08') {
                $wa = '62' . substr($wa, 1);
            }
            // Fix: 8 -> 628 (Missing leading zero/prefix)
            elseif (substr($wa, 0, 1) === '8') {
                $wa = '62' . $wa;
            }
            // If it's already 628..., keep it.
        }
        $data['wa_wali'] = $wa;
        
        // 6. Parent Names
        $data['nama_ayah'] = standardizeText($data['nama_ayah']);
        $data['nama_ibu'] = standardizeText($data['nama_ibu']);

        // Bind & Execute
        try {
            $stmt->execute([
                ':nim' => $data['nim'],
                ':nama' => $data['nama'],
                ':gender' => $data['gender'],
                ':tempat_lahir' => $data['tempat_lahir'],
                ':tanggal_lahir' => $data['tanggal_lahir'],
                ':angkatan' => $data['angkatan'],
                ':mustawa' => $data['mustawa'] ?: 'Awwal',
                ':status' => $data['status'] ?: 'Aktif',
                ':asal' => $data['asal'],
                ':asal_ppui' => $data['asal_ppui'],
                ':alamat_lengkap' => $data['alamat_lengkap'],
                ':provinsi' => $data['provinsi'],
                ':kabupaten' => $data['kabupaten'],
                ':kecamatan' => $data['kecamatan'],
                ':kelurahan' => $data['kelurahan'],
                ':nama_ayah' => $data['nama_ayah'],
                ':nama_ibu' => $data['nama_ibu'],
                ':wa_wali' => $data['wa_wali']
            ]);
            $successCount++;
        } catch (PDOException $e) {
            $errors[] = "Baris $rowNumber: {$data['nama']} - " . $e->getMessage();
        }
    }

    $pdo->commit();
    
    $response = [
        'status' => 'success',
        'message' => "Import Selesai! $successCount data berhasil disimpan.",
        'errors' => $errors
    ];
    
    ob_end_clean();
    echo json_encode($response);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Close handle if it exists (for CSV)
    if (isset($handle) && is_resource($handle)) fclose($handle);
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}
