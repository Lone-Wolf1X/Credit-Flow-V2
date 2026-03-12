<?php
// Quick fix script - Run the SQL and insert existing documents
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "<h2>Applying Fixes</h2>";

// 1. Fix template_id to allow NULL
echo "<h3>Step 1: Allow NULL for template_id</h3>";
$result = $conn->query("ALTER TABLE generated_documents MODIFY COLUMN template_id INT NULL");
if ($result) {
    echo "<p style='color: green;'>✅ template_id now allows NULL</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
}

// 2. Insert records for existing files
echo "<h3>Step 2: Insert records for existing documents</h3>";
$folder = __DIR__ . '/generated_documents';
$files = scandir($folder);
$docx_files = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'docx'; });

$inserted = 0;
foreach ($docx_files as $file) {
    // Parse filename: profile_{id}_{template}_{timestamp}.docx
    if (preg_match('/profile_(\d+)_(.+)_(\d+)\.docx/', $file, $matches)) {
        $profile_id = $matches[1];
        $template_name = $matches[2];
        $timestamp = $matches[3];
        
        // Get scheme_id from loan_details
        $stmt = $conn->prepare("SELECT scheme_id FROM loan_details WHERE customer_profile_id = ? LIMIT 1");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $scheme_id = 8; // Default
        if ($row = $result->fetch_assoc()) {
            $scheme_id = $row['scheme_id'];
        }
        
        // Insert record
        $stmt = $conn->prepare("
            INSERT INTO generated_documents 
            (customer_profile_id, scheme_id, template_name, file_path, generated_by, generated_at)
            VALUES (?, ?, ?, ?, 1, FROM_UNIXTIME(?))
        ");
        $file_path = 'generated_documents/' . $file;
        $stmt->bind_param("iissi", $profile_id, $scheme_id, $template_name, $file_path, $timestamp);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Inserted: $file</p>";
            $inserted++;
        } else {
            echo "<p style='color: orange;'>⚠️ Skipped $file: " . $stmt->error . "</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>$inserted documents inserted into database</strong></p>";
echo "<p>Now refresh the customer profile page to see the documents!</p>";
echo "<p><a href='modules/customer/customer_profile.php?id=1'>View Profile 1</a> | ";
echo "<a href='modules/customer/customer_profile.php?id=2'>View Profile 2</a></p>";
?>
