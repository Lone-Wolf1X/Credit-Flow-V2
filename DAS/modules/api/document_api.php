<?php
/**
 * Document Generation API
 * Handles document generation requests
 */

session_start();
require_once '../../config/config.php';
require_once '../../includes/PlaceholderMapper.php';
require_once '../../includes/DocumentGenerator.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'generate_documents':
        generateDocuments();
        break;
    
    case 'preview_placeholders':
        previewPlaceholders();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Generate all documents for a loan scheme
 */
function generateDocuments() {
    global $conn;
    
    $profile_id = $_POST['profile_id'] ?? null;
    $scheme_id = $_POST['scheme_id'] ?? null;
    
    if (!$profile_id || !$scheme_id) {
        echo json_encode(['success' => false, 'message' => 'Profile ID and Scheme ID are required']);
        return;
    }
    
    try {
        // Get placeholder data
        $mapper = new PlaceholderMapper($conn);
        $placeholders = $mapper->getPlaceholderData($profile_id);
        
        // Generate documents
        $generator = new DocumentGenerator();
        $generated_files = $generator->generateSchemeDocuments($profile_id, $scheme_id, $placeholders, $conn);
        
        if (empty($generated_files)) {
            echo json_encode([
                'success' => false, 
                'message' => 'No templates found for this scheme or generation failed'
            ]);
            return;
        }
        
        // Save generation record to database
        foreach ($generated_files as $file) {
            $stmt = $conn->prepare("
                INSERT INTO generated_documents 
                (customer_profile_id, scheme_id, template_name, file_path, generated_by, generated_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iissi", 
                $profile_id, 
                $scheme_id, 
                $file['template_name'], 
                $file['file_path'],
                $_SESSION['user_id']
            );
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => count($generated_files) . ' document(s) generated successfully',
            'files' => $generated_files
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error generating documents: ' . $e->getMessage()
        ]);
    }
}

/**
 * Preview placeholder values for testing
 */
function previewPlaceholders() {
    global $conn;
    
    $profile_id = $_GET['profile_id'] ?? null;
    
    if (!$profile_id) {
        echo json_encode(['success' => false, 'message' => 'Profile ID is required']);
        return;
    }
    
    try {
        $mapper = new PlaceholderMapper($conn);
        $placeholders = $mapper->getPlaceholderData($profile_id);
        
        $generator = new DocumentGenerator();
        $preview_html = $generator->previewPlaceholders($placeholders);
        
        echo json_encode([
            'success' => true,
            'placeholders' => $placeholders,
            'preview_html' => $preview_html
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
