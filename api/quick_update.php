<?php
// api/quick_update.php

require_once '../functions.php';

// Prevent unwanted output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

// Get Input
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'single'; // 'single' or 'multiple'

if ($id <= 0) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Allowed Fields (Allowlist for security)
$allowedFields = ['kabupaten', 'asal_ppui', 'provinsi', 'kecamatan', 'kelurahan'];

try {
    if ($mode === 'multiple') {
        $updates = isset($_POST['data']) ? json_decode($_POST['data'], true) : [];
        if (empty($updates)) {
            throw new Exception("No data to update");
        }

        $setClause = [];
        $params = [];
        
        foreach ($updates as $field => $val) {
            if (in_array($field, $allowedFields)) {
                $setClause[] = "$field = ?";
                $params[] = clean($val);
            }
        }

        if (empty($setClause)) {
            throw new Exception("No valid fields");
        }

        $sql = "UPDATE mahasantri SET " . implode(', ', $setClause) . " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        logActivity('Quick Update', "Updated fields for ID $id");

    } else {
        // Legacy Single Field Mode
        $field = isset($_POST['field']) ? clean($_POST['field']) : '';
        $value = isset($_POST['value']) ? clean($_POST['value']) : '';

        if (!in_array($field, $allowedFields)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Field not allowed']);
            exit;
        }

        $sql = "UPDATE mahasantri SET $field = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $id]);
        
        logActivity('Quick Update', "Updated $field for ID $id to '$value'");
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Data saved']);

} catch (Exception $e) {
    ob_clean();
    error_log("Quick Update Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
