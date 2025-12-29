<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostics Start</h1>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";

echo "Attempting to include functions.php...<br>";
try {
    require_once 'functions.php';
    echo "<b>SUCCESS:</b> functions.php loaded.<br>";
} catch (Throwable $e) {
    echo "<b>CRITICAL ERROR loading functions.php:</b> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    exit;
}

echo "Testing DB Connection...<br>";
global $pdo;
if ($pdo) {
    echo "<b>SUCCESS:</b> PDO Object exists.<br>";
} else {
    echo "<b>ERROR:</b> PDO Object is missing.<br>";
}

echo "Testing Session...<br>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<b>SUCCESS:</b> Session is active.<br>";
} else {
    echo "<b>INFO:</b> Session is not active (this is okay if functions.php didn't start it).<br>";
}

echo "<h1>Diagnostics End - Everything looks valid.</h1>";
?>
