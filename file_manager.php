<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/file_manager.php
require_once 'functions.php';
requireLogin();

$root_dir = __DIR__; // Lock to project root
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';

// Security: Prevent going above root
if (strpos(realpath($root_dir . '/' . $current_dir), realpath($root_dir)) !== 0) {
    $current_dir = '';
}

$full_path = $root_dir . '/' . $current_dir;
$full_path = str_replace('//', '/', $full_path);

// --- ACTIONS ---

// 1. Upload File
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $target = $full_path . '/' . basename($_FILES['file_upload']['name']);
    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target)) {
        echo "<script>alert('File Berhasil Diupload!'); window.location.href='?dir=$current_dir';</script>";
    } else {
        echo "<script>alert('Gagal Upload!');</script>";
    }
}

// 2. Create Folder
if (isset($_POST['new_folder'])) {
    $folder_name = clean($_POST['folder_name']);
    mkdir($full_path . '/' . $folder_name);
    echo "<script>window.location.href='?dir=$current_dir';</script>";
}

// 3. Save File Content (Edit)
if (isset($_POST['save_file'])) {
    $file_path = $_POST['file_path'];
    $content = $_POST['content'];
    file_put_contents($file_path, $content);
    echo "<script>alert('File Tersimpan!'); window.location.href='?dir=$current_dir';</script>";
}

// 4. Delete Item
if (isset($_GET['delete'])) {
    $item = $full_path . '/' . $_GET['delete'];
    if (is_dir($item)) {
        rmdir($item); // Note: rmdir only works on empty dirs usually
    } else {
        unlink($item);
    }
    echo "<script>window.location.href='?dir=$current_dir';</script>";
}

// --- READ DIRECTORY ---
$items = scandir($full_path);
$folders = [];
$files = [];

foreach ($items as $item) {
    if ($item === '.' || ($item === '..' && $current_dir === '')) continue;
    
    $path = $full_path . '/' . $item;
    if (is_dir($path)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

// --- MODE: EDIT FILE ---
$edit_mode = false;
$file_content = '';
$edit_file = '';
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_file = $full_path . '/' . $_GET['edit'];
    $file_content = file_get_contents($edit_file);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager Pro - Jamiah Abat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CodeMirror (Code Editor) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>

    <script>
        tailwind.config = {
            theme: { extend: { colors: { orange: '#F97316', dark: '#1F2937' } } }
        }
    </script>
</head>
<body class="bg-gray-100 font-[Inter] h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full relative">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-4">
            
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 h-full flex flex-col">
                <!-- Toolbar -->
                <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                    <div class="flex items-center gap-2 overflow-x-auto text-sm">
                        <a href="?dir=" class="text-blue-600 hover:underline font-bold"><i class="fa-solid fa-home"></i> Root</a>
                        <?php 
                        $parts = array_filter(explode('/', $current_dir));
                        $path_acc = '';
                        foreach($parts as $part): 
                            $path_acc .= ($path_acc ? '/' : '') . $part;
                        ?>
                            <span class="text-gray-400">/</span>
                            <a href="?dir=<?php echo $path_acc; ?>" class="text-blue-600 hover:underline"><?php echo $part; ?></a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <form method="POST" class="flex gap-2">
                             <input type="text" name="folder_name" placeholder="Folder Baru..." class="border rounded px-2 py-1 text-sm w-32 focus:w-48 transition-all" required>
                             <button type="submit" name="new_folder" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-sm"><i class="fa-solid fa-folder-plus"></i></button>
                        </form>
                        <form method="POST" enctype="multipart/form-data" class="flex gap-2 items-center">
                            <label class="cursor-pointer bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-sm flex items-center gap-1">
                                <i class="fa-solid fa-cloud-upload"></i> Upload
                                <input type="file" name="file_upload" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                </div>

                <!-- Editor Mode -->
                <?php if($edit_mode): ?>
                    <form method="POST" class="flex-1 flex flex-col">
                        <input type="hidden" name="file_path" value="<?php echo $edit_file; ?>">
                        <input type="hidden" name="save_file" value="1">
                        <div class="p-2 bg-gray-800 text-white flex justify-between items-center text-sm">
                            <span>Editing: <b><?php echo basename($edit_file); ?></b></span>
                            <div>
                                <a href="?dir=<?php echo $current_dir; ?>" class="px-3 py-1 bg-gray-600 rounded hover:bg-gray-500 mr-2">Batal</a>
                                <button type="submit" class="px-3 py-1 bg-green-600 rounded hover:bg-green-500"><i class="fa-solid fa-save"></i> Simpan</button>
                            </div>
                        </div>
                        <textarea id="codeEditor" name="content" class="flex-1"><?php echo htmlspecialchars($file_content); ?></textarea>
                    </form>
                    <script>
                        var editor = CodeMirror.fromTextArea(document.getElementById("codeEditor"), {
                            lineNumbers: true,
                            mode: "application/x-httpd-php",
                            theme: "dracula",
                            indentUnit: 4,
                            lineWrapping: true
                        });
                        editor.setSize("100%", "100%");
                    </script>
                
                <!-- Browser Mode -->
                <?php else: ?>
                    <div class="flex-1 overflow-y-auto p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <!-- Folders -->
                            <?php if($current_dir !== ''): ?>
                                <a href="?dir=<?php echo dirname($current_dir) == '.' ? '' : dirname($current_dir); ?>" class="bg-yellow-50 hover:bg-yellow-100 p-4 rounded-lg flex flex-col items-center justify-center cursor-pointer border border-yellow-200 transition">
                                    <i class="fa-solid fa-turn-up text-3xl text-yellow-600 mb-2"></i>
                                    <span class="text-xs font-bold text-gray-700 text-center">.. (Kembali)</span>
                                </a>
                            <?php endif; ?>

                            <?php foreach($folders as $f): ?>
                                <div class="group relative bg-blue-50 hover:bg-blue-100 p-4 rounded-lg flex flex-col items-center justify-center border border-blue-200 transition">
                                    <a href="?dir=<?php echo ($current_dir ? $current_dir . '/' : '') . $f; ?>" class="absolute inset-0"></a>
                                    <i class="fa-solid fa-folder text-4xl text-blue-500 mb-2"></i>
                                    <span class="text-xs font-bold text-gray-700 text-center truncate w-full"><?php echo $f; ?></span>
                                    
                                    <a href="?dir=<?php echo $current_dir; ?>&delete=<?php echo $f; ?>" onclick="return confirm('Hapus Folder <?php echo $f; ?>?')" class="absolute top-1 right-1 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 p-1 z-10"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            <?php endforeach; ?>

                            <!-- Files -->
                            <?php 
                            // DAFTAR FILE "HARAM" (Critical System Files)
                            $critical_items = ['config', 'api', 'includes', 'functions.php', 'index.php', 'login.php', 'setup_database.php', 'db_manager.php', 'file_manager.php'];
                            
                            foreach($files as $f): 
                                $ext = pathinfo($f, PATHINFO_EXTENSION);
                                $icon = 'fa-file';
                                $color = 'text-gray-400';
                                
                                $is_critical = in_array($f, $critical_items);
                                $bg_class = $is_critical ? 'bg-red-50 border-red-300' : 'bg-white border-gray-200';
                                $border_class = $is_critical ? 'border-red-500 border-2' : '';
                                
                                if(in_array($ext, ['php','js','css','html'])) { $icon = 'fa-file-code'; $color = $is_critical ? 'text-red-600' : 'text-purple-500'; }
                                elseif(in_array($ext, ['jpg','png','jpeg','gif'])) { $icon = 'fa-file-image'; $color = 'text-green-500'; }
                                elseif(in_array($ext, ['zip','rar'])) { $icon = 'fa-file-zipper'; $color = 'text-yellow-600'; }
                                elseif($ext == 'pdf') { $icon = 'fa-file-pdf'; $color = 'text-red-500'; }
                            ?>
                                <div class="group relative <?php echo $bg_class; ?> hover:shadow-md p-4 rounded-lg flex flex-col items-center justify-center border <?php echo $border_class; ?> shadow-sm transition">
                                    <i class="fa-solid <?php echo $icon . ' ' . $color; ?> text-4xl mb-2"></i>
                                    
                                    <span class="text-xs text-gray-600 text-center truncate w-full font-bold" title="<?php echo $f; ?>">
                                        <?php echo $f; ?>
                                    </span>
                                    
                                    <?php if($is_critical): ?>
                                        <span class="absolute top-0 right-0 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded-bl-lg font-bold">DANGER</span>
                                    <?php endif; ?>
                                    
                                    <div class="absolute bottom-2 flex gap-2 opacity-0 group-hover:opacity-100 transition z-10 bg-white/80 p-1 rounded shadow-sm">
                                        <?php if(in_array($ext, ['php','js','css','html','txt','json', 'xml', 'sql'])): ?>
                                            <a href="?dir=<?php echo $current_dir; ?>&edit=<?php echo $f; ?>" 
                                               onclick="return <?php echo $is_critical ? "confirm('⚠️ PERINGATAN KERAS! ⚠️\\n\\nIni adalah FILE SISTEM KRITIS: $f\\n\\nSalah edit bisa menyebabkan WEBSITE MATI TOTAL.\\nApakah Anda yakin 100% tahu apa yang Anda lakukan?')" : "true"; ?>"
                                               class="text-blue-600 hover:text-blue-800 p-1"><i class="fa-solid fa-pen"></i></a>
                                        <?php endif; ?>
                                        
                                        <a href="?dir=<?php echo $current_dir; ?>&delete=<?php echo $f; ?>" 
                                           onclick="return confirm('<?php echo $is_critical ? "⛔ BAHAYA! JANGAN DIHAPUS! ⛔\\n\\nMenghapus $f akan MERUSAK SISTEM.\\n\\nApakah Anda BENAR-BENAR ingin menghancurkan aplikasi ini?" : "Hapus File $f?"; ?>')" 
                                           class="text-red-600 hover:text-red-800 p-1"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </main>
    </div>
</body>
</html>
