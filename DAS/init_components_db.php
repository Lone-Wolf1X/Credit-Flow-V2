<?php
// Script to initialize document_components table
$conn = new mysqli('localhost', 'root', '', 'das_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `document_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL UNIQUE,
  `html_content` longtext,
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Check if component exists
$check = $conn->query("SELECT * FROM document_components WHERE code = 'COLLATERAL_TABLE'");
if ($check->num_rows == 0) {
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
  <thead>
    <tr style="background-color:#f0f0f0;">
      <th><strong>S.N.</strong></th>
      <th><strong>Owner Name</strong></th>
      <th><strong>Plot No</strong></th>
      <th><strong>Area</strong></th>
      <th><strong>Location</strong></th>
    </tr>
  </thead>
  <tbody>
    <!-- Loop Example: {{#collaterals}} -->
    <tr>
      <td>{{sn}}</td>
      <td>{{owner_name}}</td>
      <td>{{plot_no}}</td>
      <td>{{area}}</td>
      <td>{{location}}</td>
    </tr>
    <!-- {{/collaterals}} -->
  </tbody>
</table>';
    
    $stmt = $conn->prepare("INSERT INTO document_components (name, code, html_content, description) VALUES (?, ?, ?, ?)");
    $name = 'Standard Collateral Table';
    $code = 'COLLATERAL_TABLE';
    $desc = 'Standard collateral table with owner, plot, area details.';
    $stmt->bind_param("ssss", $name, $code, $html, $desc);
    
    if ($stmt->execute()) {
        echo "Sample component inserted.\n";
    } else {
        echo "Error inserting sample: " . $stmt->error . "\n";
    }
} else {
    echo "Sample component already exists.\n";
}

$conn->close();
?>
