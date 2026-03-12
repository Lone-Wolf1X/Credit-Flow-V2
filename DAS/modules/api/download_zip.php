<?php
/**
 * Download All Documents as ZIP
 * 
 * Fetches all generated documents for a profile and zips them up.
 * The zip contains a folder named "{CustomerID}_{CustomerName}".
 */

require_once '../../config/config.php';

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Unauthorized access");
}

$profile_id = $_GET['profile_id'] ?? '';

if (empty($profile_id)) {
    die("Profile ID is required");
}

// 1. Get Customer Profile Info
$stmt = $conn->prepare("SELECT customer_id, full_name, customer_type FROM customer_profiles WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Profile not found");
}

$profile = $result->fetch_assoc();
$customer_id = $profile['customer_id'];
$full_name = $profile['full_name'];

// Construct Folder Name for inside ZIP (Sanitized)
// e.g., 2026001_Rameshwar_Prasad_Sah
$safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $full_name); // Replace non-alphanumeric with underscore
$folder_name = $customer_id . '_' . $safe_name;
$zip_filename = $folder_name . '_Documents.zip';

// 2. Fetch Generated Documents
// We use generated_documents table to find paths
// Assuming file_path is stored relative to DAS root (e.g., 'generated_documents/folder/file.docx')
$stmt = $conn->prepare("SELECT file_path, document_name FROM generated_documents WHERE customer_profile_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$docs_result = $stmt->get_result();

if ($docs_result->num_rows === 0) {
    die("<script>alert('No generated documents found for this profile.'); window.close();</script>");
}

// 3. Create ZIP
$zip = new ZipArchive();
$temp_zip = tempnam(sys_get_temp_dir(), 'DAS_ZIP_');

if ($zip->open($temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Cannot initiate zip creation");
}

// Create the root directory inside ZIP
$zip->addEmptyDir($folder_name);

$base_dir = dirname(__DIR__, 2); // C:/xampp/htdocs/Credit/DAS (Assuming we are in modules/api)

$files_added = 0;
while ($row = $docs_result->fetch_assoc()) {
    $relative_path = $row['file_path'];
    $absolute_path = $base_dir . '/' . $relative_path;
    
    // Safety check just in case path logic differs
    if (!file_exists($absolute_path)) {
        // Try alternate path if starting with ../ or similar
        $alternate_path = __DIR__ . '/../../' . $relative_path; 
        if (file_exists($alternate_path)) {
            $absolute_path = $alternate_path;
        }
    }

    if (file_exists($absolute_path)) {
        $file_name = basename($absolute_path);
        // Add file to the specific folder inside zip
        $zip->addFile($absolute_path, $folder_name . '/' . $file_name);
        $files_added++;
    }
}

$zip->close();

if ($files_added === 0) {
    unlink($temp_zip);
    die("<script>alert('Files listed in database were not found on server.'); window.close();</script>");
}

// 4. Stream Download
if (file_exists($temp_zip)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($temp_zip));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($temp_zip);
    
    // Delete temp file after download
    unlink($temp_zip);
    exit;
} else {
    die("Error creating zip file");
}
?>
