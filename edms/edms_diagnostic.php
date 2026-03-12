<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

echo "EDMS Diagnostic Tool (v2)\n";
echo "====================\n";

// File System Check
echo "\n[File System Check]\n";
echo "Configured Upload Path (Raw): " . UPLOAD_DIR . "\n";
echo "Resolved Upload Path: " . realpath(UPLOAD_DIR) . "\n";

$check_path = UPLOAD_DIR;
if (is_dir($check_path)) {
    echo "✓ Upload directory exists\n";
    if (is_writable($check_path)) {
        echo "✓ Upload directory is writable\n";
    } else {
        echo "✗ Upload directory is NOT writable\n";
    }
} else {
    echo "✗ Upload directory does NOT exist at: $check_path\n";
    // Check parent
    echo "Checking parent directory: " . dirname($check_path) . "\n";
}

echo "\nDiagnostic Complete.\n";
?>