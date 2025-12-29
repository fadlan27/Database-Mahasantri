<?php
require_once 'config/database.php';

echo "Checking for duplicate NIMs...\n";
$sql = "SELECT nim, COUNT(*) as count FROM mahasantri GROUP BY nim HAVING count > 1";
$stmt = $pdo->query($sql);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) > 0) {
    echo "CRITICAL: Duplicate NIMs found!\n";
    foreach ($duplicates as $d) {
        echo "NIM: " . $d['nim'] . " (Count: " . $d['count'] . ")\n";
    }
} else {
    echo "No duplicate NIMs found.\n";
}

echo "Checking for duplicate Names (just in case)...\n";
$sql2 = "SELECT nama, COUNT(*) as count FROM mahasantri GROUP BY nama HAVING count > 1";
$stmt2 = $pdo->query($sql2);
$dupNames = $stmt2->fetchAll(PDO::FETCH_ASSOC);
if(count($dupNames) > 0){
    foreach ($dupNames as $d) {
        echo "Possibly Duplicate Name: " . $d['nama'] . " (Count: " . $d['count'] . ")\n";
    }
}

?>
