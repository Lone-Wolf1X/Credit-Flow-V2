<?php
// DAS/cleanup_documents.php
require_once 'config/config.php';

// Simple CLI protection or Admin check (if web run)
if (php_sapi_name() !== 'cli' && !isset($_GET['confirm'])) {
    die("This script performs DESTRUCTIVE actions. Run from CLI or append ?confirm=1");
}

echo "Starting Document Cleanup...\n";

// 1. Clear Database Table (Use DELETE to avoid FK constraint issues)
$tableName = 'generated_documents';
$sql = "DELETE FROM $tableName";

if ($conn->query($sql) === TRUE) {
    echo "[SUCCESS] Table '$tableName' cleared.\n";
    $conn->query("ALTER TABLE $tableName AUTO_INCREMENT = 1"); // Reset ID if possible
} else {
    echo "[ERROR] Truncating table: " . $conn->error . "\n";
}

// 2. Delete Files
$targetDir = __DIR__ . '/generated_documents';

if (!is_dir($targetDir)) {
    die("[ERROR] Directory not found: $targetDir\n");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

$fileCount = 0;
$dirCount = 0;

foreach ($iterator as $fileinfo) {
    $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
    $path = $fileinfo->getRealPath();
    
    // Skip skipping the log file if we want to keep history
    if ($fileinfo->getFilename() === 'last_generation_log.txt') {
        echo "[SKIP] Log file preserved.\n";
        continue;
    }
    
    if ($todo($path)) {
        if ($fileinfo->isFile()) $fileCount++;
        else $dirCount++;
    } else {
        echo "[WARN] Failed to delete: $path\n";
    }
}

echo "[SUCCESS] Deleted $fileCount files and $dirCount folders.\n";
echo "Cleanup Complete.\n";
?>
