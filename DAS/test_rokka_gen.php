<?php
require_once __DIR__ . '/includes/SmartDocumentGenerator.php';

// Setup DB
$conn = new mysqli('localhost', 'root', '', 'das_db');

// 1. Register Template
$conn->query("DELETE FROM templates WHERE template_code = 'ROKKA_TEST'"); // Cleanup previous
$stmt = $conn->prepare("INSERT INTO templates (template_name, template_code, file_path, is_active) VALUES (?, ?, ?, 1)");
$name = 'Rokka Letter Test';
$code = 'ROKKA_TEST';
$path = 'Templates 2/Rokka letter.docx'; 
$stmt->bind_param("sss", $name, $code, $path);
$stmt->execute();
$template_id = $conn->insert_id;

echo "Registered Template: Rokka Letter (ID: $template_id)\n";

// 2. Mock Session for Branch Data (if needed)
session_start();
$_SESSION['user_id'] = 1; // Assume admin or existing user

// 3. Generate
$profile_id = $argv[1] ?? 1; // Take from arg or default to 1
echo "Generating for Profile ID: $profile_id...\n";

$generator = new SmartDocumentGenerator($conn);
$result = $generator->generate($profile_id, 'test_rokka', $template_id);

if ($result['success']) {
    echo "SUCCESS: Document generated at:\n" . $result['file_path'] . "\n";
    // Verify placeholders
    // We can't easily read back the docx, but success implies it ran.
} else {
    echo "FAILURE: " . $result['error'] . "\n";
}
?>
