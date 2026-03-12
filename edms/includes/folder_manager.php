<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Create customer folder structure
 * Format: FullName_ClientID_CAPID/
 */
function createCustomerFolders($customer_name, $client_id, $cap_id)
{
    // Sanitize folder names
    $safe_name = sanitizeFolderName($customer_name);
    $safe_client_id = sanitizeFolderName($client_id);
    $safe_cap_id = sanitizeFolderName($cap_id);

    // Create base folder path
    $base_folder = UPLOAD_DIR . $safe_name . '_' . $safe_client_id . '_' . $safe_cap_id . '/';

    // Create subfolders
    $folders = [
        $base_folder,
        $base_folder . 'General_Documents/',
        $base_folder . 'Security_Documents/',
        $base_folder . $safe_cap_id . '/',
        $base_folder . $safe_cap_id . '/Legal_Documents/',
        $base_folder . $safe_cap_id . '/Approved_CAP_Copy/'
    ];

    foreach ($folders as $folder) {
        if (!ensureFolderExists($folder)) {
            return ['success' => false, 'message' => 'Failed to create folder: ' . $folder];
        }
    }

    return ['success' => true, 'path' => $base_folder];
}

/**
 * Ensure folder exists, create if not
 */
function ensureFolderExists($path)
{
    if (!is_dir($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

/**
 * Sanitize folder name - remove special characters
 */
function sanitizeFolderName($name)
{
    // Remove special characters, keep alphanumeric, dash, underscore
    $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
    // Remove multiple underscores
    $name = preg_replace('/_+/', '_', $name);
    // Trim underscores from start and end
    return trim($name, '_');
}

/**
 * Get document path for upload
 */
function getDocumentPath($customer_name, $client_id, $cap_id, $category)
{
    $safe_name = sanitizeFolderName($customer_name);
    $safe_client_id = sanitizeFolderName($client_id);
    $safe_cap_id = sanitizeFolderName($cap_id);

    $base_folder = UPLOAD_DIR . $safe_name . '_' . $safe_client_id . '_' . $safe_cap_id . '/';

    switch ($category) {
        case 'General':
            return $base_folder . 'General_Documents/';
        case 'Security':
            return $base_folder . 'Security_Documents/';
        case 'Legal':
            return $base_folder . $safe_cap_id . '/Legal_Documents/';
        case 'Critical':
             // Return the base CAP folder (Name_Client_CAP)
            return $base_folder;
        default:
            return $base_folder;
    }
}

/**
 * Get customer folder path
 */
function getCustomerFolderPath($customer_name, $client_id, $cap_id)
{
    $safe_name = sanitizeFolderName($customer_name);
    $safe_client_id = sanitizeFolderName($client_id);
    $safe_cap_id = sanitizeFolderName($cap_id);

    return UPLOAD_DIR . $safe_name . '_' . $safe_client_id . '_' . $safe_cap_id . '/';
}

/**
 * Check if folder exists for customer
 */
function customerFolderExists($customer_name, $client_id, $cap_id)
{
    $path = getCustomerFolderPath($customer_name, $client_id, $cap_id);
    return is_dir($path);
}
?>