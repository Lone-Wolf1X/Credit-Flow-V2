<?php
// Quick script to create a download endpoint
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

$file = $_GET['file'] ?? '';

if (empty($file)) {
    die('File parameter required');
}

// Security: Only allow downloading from generated_documents folder
$basePath = __DIR__ . '/generated_documents/';

// Handle nested folders: file can be "FolderName/filename.docx"
// Remove any ../ or absolute paths for security
$file = str_replace(['../', '..\\'], '', $file); 
// We generally allow \ as separator on windows but keeping it standard / is safer, 
// though str_replace inside previous logic replaced \ with / which is fine.
$file = str_replace('\\', '/', $file);

// Build full path
$filePath = $basePath . $file;

// Verify the resolved path is still within generated_documents
$realBasePath = realpath($basePath);
$realFilePath = realpath($filePath);

if (!$realFilePath || strpos($realFilePath, $realBasePath) !== 0) {
    die('Invalid file path');
}

if (!file_exists($filePath)) {
    die('File not found: ' . htmlspecialchars(basename($file)));
}

// Determine content type
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = 'application/octet-stream';
if ($ext === 'docx') {
    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
} elseif ($ext === 'pdf') {
    $contentType = 'application/pdf';
}

// Set headers for download
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($filePath));

// Output file
readfile($filePath);
exit;
