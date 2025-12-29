<?php
require_once 'config/database.php';

try {
    echo "=== TABLE: pelanggaran ===\n";
    $stmt = $pdo->query("DESCRIBE pelanggaran");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $c) {
        echo $c['Field'] . " (" . $c['Type'] . ")\n";
    }

    echo "\n=== TABLE: mahasantri ===\n";
    $stmt = $pdo->query("DESCRIBE mahasantri");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $c) {
        echo $c['Field'] . " (" . $c['Type'] . ")\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
