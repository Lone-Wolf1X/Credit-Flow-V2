<?php
// Script to download all generated documents for a profile as ZIP
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

$profile_id = $_GET['profile_id'] ?? 0;
$batch_id = $_GET['batch_id'] ?? '';

if (empty($profile_id)) {
    die('Profile ID required');
}

// 1. Fetch generated documents from DB
$sql = "
    SELECT gd.file_path, gd.batch_id, cp.customer_id, gd.template_snapshot, gd.template_name
    FROM generated_documents gd
    INNER JOIN customer_profiles cp ON gd.customer_profile_id = cp.id
    WHERE gd.customer_profile_id = ? AND gd.is_active = TRUE
";

if (!empty($batch_id)) {
    $sql .= " AND gd.batch_id = ?";
} else {
    // Default to latest batch
    $sql .= " AND gd.batch_id <=> (
        SELECT batch_id FROM generated_documents 
        WHERE customer_profile_id = ? AND is_active = TRUE 
        ORDER BY generated_at DESC LIMIT 1
    )";
}

$stmt = $conn->prepare($sql);
if (!empty($batch_id)) {
    $stmt->bind_param("is", $profile_id, $batch_id);
} else {
    $stmt->bind_param("ii", $profile_id, $profile_id);
}

$stmt->execute();
$result = $stmt->get_result();
$files = $result->fetch_all(MYSQLI_ASSOC);

if (count($files) === 0) {
    die('No documents found for this profile' . (!empty($batch_id) ? " and batch $batch_id" : "") . '.');
}

// 2. Prepare Zip
$zip = new ZipArchive();
$zipName = 'documents_' . $files[0]['customer_id'] . '_' . date('Ymd_His') . '.zip';
$tempZipPath = sys_get_temp_dir() . '/' . $zipName;

if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Cannot create zip file");
}

$baseDir = __DIR__ . '/';
$addedCount = 0;

foreach ($files as $file) {
    $relativePath = $file['file_path'];
    $absolutePath = $baseDir . $relativePath;
    
    // Security check: ensure path is within generated_documents
    if (strpos(realpath($absolutePath), realpath($baseDir . 'generated_documents')) !== 0) {
        continue; 
    }

    if (file_exists($absolutePath)) {
        // Add file to zip
        if (!empty($file['template_name'])) {
            $templateName = $file['template_name'];
        } elseif (!empty($file['template_snapshot'])) {
            $snapshot = json_decode($file['template_snapshot'], true);
            $templateName = $snapshot['template_name'] ?? 'Document';
        }
        
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $templateName);
        if (empty($cleanName)) $cleanName = 'document_' . uniqid();
        
        // Ensure unique names in zip
        $zipFileName = $cleanName . '.' . $extension;
        $counter = 1;
        while ($zip->locateName($zipFileName) !== false) {
             $zipFileName = $cleanName . '_' . $counter . '.' . $extension;
             $counter++;
        }
        
        $zip->addFile($absolutePath, $zipFileName);
        $addedCount++;
    }
}

$zip->close();

if ($addedCount === 0) {
    die('No valid files found on server to zip.');
}

// 3. Output Zip
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tempZipPath));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tempZipPath);

// 4. Cleanup
unlink($tempZipPath);
exit;
