<?php
/**
 * Manual Test Endpoint for Document Generation
 * Usage: http://localhost/Credit/DAS/generate_test.php?profile_id=1&template_id=1
 */

require_once __DIR__ . '/includes/DocumentGenerator.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Get parameters
$profileId = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
$templateId = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

if (!$profileId || !$templateId) {
    die(json_encode([
        'success' => false, 
        'error' => 'Missing parameters. Usage: ?profile_id=X&template_id=Y'
    ]));
}

// Fetch template details
$stmt = $conn->prepare("SELECT * FROM templates WHERE id = ?");
$stmt->bind_param("i", $templateId);
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();

if (!$template) {
    die(json_encode(['success' => false, 'error' => 'Template not found']));
}

$templatePath = __DIR__ . '/' . $template['file_path'];

if (!file_exists($templatePath)) {
    die(json_encode(['success' => false, 'error' => 'Template file not found at: ' . $templatePath]));
}

// Generate document
echo "<h2>Document Generation Test</h2>";
echo "<hr>";
echo "<pre>";

$generator = new DocumentGenerator($conn);
$result = $generator->generate($templatePath, $profileId, 'mortgage_deed');

echo "</pre>";
echo "<hr>";

if ($result['success']) {
    echo "<h3 style='color: green;'>✅ Success!</h3>";
    echo "<p><strong>Profile ID:</strong> {$result['customer_profile_id']}</p>";
    echo "<p><strong>Placeholders Filled:</strong> {$result['placeholders_count']}</p>";
    echo "<p><strong>Output File:</strong> {$result['output_path']}</p>";
    
    // Provide download link
    $downloadPath = str_replace(__DIR__ . '/', '', $result['output_path']);
    echo "<p><a href='$downloadPath' download style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Download Document</a></p>";
} else {
    echo "<h3 style='color: red;'>❌ Failed!</h3>";
    echo "<p><strong>Error:</strong> {$result['error']}</p>";
    if (isset($result['trace'])) {
        echo "<details><summary>Stack Trace</summary><pre>{$result['trace']}</pre></details>";
    }
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
    }
    pre {
        background: #f4f4f4;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
</style>
