<?php
// Check generated_documents table structure
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "<h2>Generated Documents Table Check</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'generated_documents'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Table 'generated_documents' does NOT exist!</p>";
    echo "<p>Run the migration: document_generation_setup.sql</p>";
} else {
    echo "<p style='color: green;'>✅ Table 'generated_documents' exists</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $columns = $conn->query("DESCRIBE generated_documents");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for customer_profile_id column
    $check = $conn->query("SHOW COLUMNS FROM generated_documents LIKE 'customer_profile_id'");
    if ($check->num_rows == 0) {
        echo "<p style='color: red;'>❌ Column 'customer_profile_id' is MISSING!</p>";
        echo "<p><strong>Fix SQL:</strong></p>";
        echo "<pre>
ALTER TABLE generated_documents 
ADD COLUMN customer_profile_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE;
</pre>";
    } else {
        echo "<p style='color: green;'>✅ Column 'customer_profile_id' exists</p>";
    }
}

// Check existing generated documents
echo "<h3>Existing Generated Documents:</h3>";
$docs = $conn->query("SELECT * FROM generated_documents LIMIT 10");
if ($docs && $docs->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Profile ID</th><th>Scheme ID</th><th>Template Name</th><th>File Path</th><th>Generated At</th></tr>";
    while ($row = $docs->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . ($row['customer_profile_id'] ?? 'N/A') . "</td>";
        echo "<td>{$row['scheme_id']}</td>";
        echo "<td>{$row['template_name']}</td>";
        echo "<td>{$row['file_path']}</td>";
        echo "<td>{$row['generated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No documents found yet</p>";
}

// Check generated_documents folder
echo "<h3>Generated Documents Folder:</h3>";
$folder = __DIR__ . '/generated_documents';
if (is_dir($folder)) {
    echo "<p style='color: green;'>✅ Folder exists</p>";
    $files = scandir($folder);
    $docx_files = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'docx'; });
    if (count($docx_files) > 0) {
        echo "<p><strong>Files found:</strong></p><ul>";
        foreach ($docx_files as $file) {
            $size = filesize($folder . '/' . $file);
            echo "<li>$file (" . number_format($size) . " bytes)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No .docx files found</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Folder does not exist</p>";
}
?>
