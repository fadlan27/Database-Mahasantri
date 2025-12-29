<?php
$mysqlPath = "C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe";
$db = "jamiah_abat_db";
$user = "root";
$pass = "";
$file = __DIR__ . "/backup_mahasantri_sql_2025-12-21_18-20-51.sql";

$cmd = "\"$mysqlPath\" -u \"$user\" \"$db\" < \"$file\" 2>&1";
echo "Command: $cmd\n\n";

if (function_exists('shell_exec')) {
    echo "shell_exec is AVAILABLE.\n";
    $output = shell_exec($cmd);
    echo "Output:\n" . ($output ? $output : "[No Output - Success?]");
} else {
    echo "shell_exec is DISABLED.\n";
}
?>
