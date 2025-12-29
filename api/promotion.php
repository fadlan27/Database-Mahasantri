<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/api/promotion.php
require_once '../config/database.php';
require_once '../functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Promote Tsani -> Lulus
        $sql1 = "INSERT INTO history_kelas (mahasantri_id, old_mustawa, new_mustawa, keterangan) 
                 SELECT id, 'Tsani', 'Lulus', 'Kenaikan Kelas Otomatis' FROM mahasantri WHERE mustawa = 'Tsani' AND status = 'Aktif'";
        $pdo->exec($sql1);

        $pdo->exec("UPDATE mahasantri SET mustawa = 'Lulus', status = 'Lulus' WHERE mustawa = 'Tsani' AND status = 'Aktif'");
        
        // 2. Promote Awwal -> Tsani
        $sql2 = "INSERT INTO history_kelas (mahasantri_id, old_mustawa, new_mustawa, keterangan) 
                 SELECT id, 'Awwal', 'Tsani', 'Kenaikan Kelas Otomatis' FROM mahasantri WHERE mustawa = 'Awwal' AND status = 'Aktif'";
        $pdo->exec($sql2);

        $pdo->exec("UPDATE mahasantri SET mustawa = 'Tsani' WHERE mustawa = 'Awwal' AND status = 'Aktif'");

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Kenaikan kelas berhasil diproses!']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}
?>
