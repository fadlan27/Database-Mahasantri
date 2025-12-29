<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing SimpleXLSX Load...\n";

require_once 'api/SimpleXLSX.php';

if (class_exists('Shuchkin\SimpleXLSX')) {
    echo "Class Shuchkin\SimpleXLSX exists.\n";
} else {
    echo "Class Shuchkin\SimpleXLSX NOT found.\n";
}

echo "Done.\n";
