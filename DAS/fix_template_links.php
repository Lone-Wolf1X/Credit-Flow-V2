<?php
// Fix template-scheme linking
$conn = new mysqli('localhost', 'root', '', 'das_db');

echo "<h2>Template-Scheme Linking Fix</h2>";

// 1. Check if loan_scheme_templates table exists
$result = $conn->query("SHOW TABLES LIKE 'loan_scheme_templates'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Table 'loan_scheme_templates' does NOT exist!</p>";
    echo "<p><strong>Run this SQL:</strong></p>";
    echo "<pre>
CREATE TABLE loan_scheme_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_id INT NOT NULL,
    template_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scheme_id) REFERENCES loan_schemes(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_scheme_template (scheme_id, template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
</pre>";
} else {
    echo "<p style='color: green;'>✅ Table 'loan_scheme_templates' exists</p>";
}

// 2. Show available schemes
echo "<h3>Available Loan Schemes:</h3>";
$schemes = $conn->query("SELECT id, scheme_name, scheme_code FROM loan_schemes");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Scheme Name</th><th>Scheme Code</th></tr>";
while ($row = $schemes->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['scheme_name']}</td><td>{$row['scheme_code']}</td></tr>";
}
echo "</table>";

// 3. Show available templates
echo "<h3>Available Templates:</h3>";
$templates = $conn->query("SELECT id, template_name, file_path, scheme_id FROM templates");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Template Name</th><th>File Path</th><th>Old Scheme ID</th></tr>";
while ($row = $templates->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['template_name']}</td><td>{$row['file_path']}</td><td>{$row['scheme_id']}</td></tr>";
}
echo "</table>";

// 4. Show current links
echo "<h3>Current Template-Scheme Links:</h3>";
$links = $conn->query("
    SELECT lst.*, ls.scheme_name, t.template_name
    FROM loan_scheme_templates lst
    JOIN loan_schemes ls ON lst.scheme_id = ls.id
    JOIN templates t ON lst.template_id = t.id
");

if ($links && $links->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Link ID</th><th>Scheme</th><th>Template</th></tr>";
    while ($row = $links->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['scheme_name']}</td><td>{$row['template_name']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No template-scheme links found!</p>";
    echo "<p><strong>You need to link templates to schemes. Run this SQL:</strong></p>";
    echo "<pre>
-- Example: Link template ID 20 to scheme ID 1
INSERT INTO loan_scheme_templates (scheme_id, template_id)
VALUES (1, 20)
ON DUPLICATE KEY UPDATE scheme_id = scheme_id;
</pre>";
}

// 5. Check customer profiles with loan details
echo "<h3>Customer Profiles with Loan Details:</h3>";
$profiles = $conn->query("
    SELECT cp.id, cp.customer_id, cp.status, ld.scheme_id, ls.scheme_name
    FROM customer_profiles cp
    LEFT JOIN loan_details ld ON cp.id = ld.customer_profile_id
    LEFT JOIN loan_schemes ls ON ld.scheme_id = ls.id
    LIMIT 5
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Profile ID</th><th>Customer ID</th><th>Status</th><th>Scheme ID</th><th>Scheme Name</th></tr>";
while ($row = $profiles->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['customer_id']}</td><td>{$row['status']}</td><td>{$row['scheme_id']}</td><td>{$row['scheme_name']}</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Quick Fix SQL:</h3>";
echo "<p>Copy and run this in phpMyAdmin (das_db):</p>";
echo "<pre>
-- Link template ID 20 to scheme ID 1 (Home Loan)
INSERT INTO loan_scheme_templates (scheme_id, template_id)
VALUES (1, 20)
ON DUPLICATE KEY UPDATE scheme_id = scheme_id;

-- If you have more templates, add more links:
-- INSERT INTO loan_scheme_templates (scheme_id, template_id) VALUES (2, 21);
-- INSERT INTO loan_scheme_templates (scheme_id, template_id) VALUES (3, 22);
</pre>";
?>
