<?php
// Check generated_documents table and records
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "<h2>Generated Documents Debug</h2>";

// 1. Check table structure
echo "<h3>Table Structure:</h3>";
$result = $conn->query("DESCRIBE generated_documents");
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

// 2. Check foreign keys
echo "<h3>Foreign Key Constraints:</h3>";
$result = $conn->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'das_db' 
    AND TABLE_NAME = 'generated_documents'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Constraint</th><th>Column</th><th>References Table</th><th>References Column</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['CONSTRAINT_NAME']}</td><td>{$row['COLUMN_NAME']}</td><td>{$row['REFERENCED_TABLE_NAME']}</td><td>{$row['REFERENCED_COLUMN_NAME']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No foreign keys found</p>";
}

// 3. Check records
echo "<h3>Records in generated_documents:</h3>";
$result = $conn->query("SELECT * FROM generated_documents ORDER BY generated_at DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Profile ID</th><th>Scheme ID</th><th>Template Name</th><th>File Path</th><th>Generated At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . ($row['customer_profile_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['scheme_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['template_name'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['file_path'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['generated_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No records found! This is why documents don't show in UI.</p>";
}

// 4. Check actual files
echo "<h3>Files in generated_documents folder:</h3>";
$folder = __DIR__ . '/generated_documents';
if (is_dir($folder)) {
    $files = scandir($folder);
    $docx_files = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'docx'; });
    if (count($docx_files) > 0) {
        echo "<ul>";
        foreach ($docx_files as $file) {
            $size = filesize($folder . '/' . $file);
            $time = filemtime($folder . '/' . $file);
            echo "<li><strong>$file</strong> (" . number_format($size) . " bytes, " . date('Y-m-d H:i:s', $time) . ")</li>";
        }
        echo "</ul>";
    }
}

// 5. SQL to fix
echo "<hr><h3>Fix SQL:</h3>";
echo "<p>If foreign key constraint exists on template_id, run:</p>";
echo "<pre>
ALTER TABLE generated_documents
DROP FOREIGN KEY generated_documents_ibfk_2;
</pre>";

echo "<p>Then manually insert a test record:</p>";
echo "<pre>
INSERT INTO generated_documents 
(customer_profile_id, scheme_id, template_name, file_path, generated_by, generated_at)
VALUES (2, 8, 'Mortgage Deed', 'generated_documents/profile_2_Mortgage Deed_1767708460.docx', 1, NOW());
</pre>";
?>
