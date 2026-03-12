<?php
require_once 'C:/xampp/htdocs/Credit/DAS/config/config.php';

echo "=== CHECKING TEMPLATE FILES ===\n\n";

// Check templates directory
$template_dir = "C:/xampp/htdocs/Credit/DAS/templates";
echo "1. FILES IN templates/ DIRECTORY:\n";
if (is_dir($template_dir)) {
    $files = scandir($template_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $full_path = $template_dir . '/' . $file;
            if (is_file($full_path)) {
                echo "  ✓ $file (" . filesize($full_path) . " bytes)\n";
            } elseif (is_dir($full_path)) {
                echo "  📁 $file/\n";
                // List files in subdirectory
                $subfiles = scandir($full_path);
                foreach ($subfiles as $subfile) {
                    if ($subfile != '.' && $subfile != '..') {
                        $sub_full_path = $full_path . '/' . $subfile;
                        if (is_file($sub_full_path)) {
                            echo "     ✓ $subfile (" . filesize($sub_full_path) . " bytes)\n";
                        }
                    }
                }
            }
        }
    }
} else {
    echo "  ✗ Directory not found\n";
}

// Now check what the database has
echo "\n2. TEMPLATE RECORDS IN DATABASE:\n";
$result = $conn->query("SELECT id, template_name, template_code, scheme_id FROM templates ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "  - ID {$row['id']}: {$row['template_name']} (Scheme: {$row['scheme_id']})\n";
}

echo "\n=== NEED TO ADD FILE PATH COLUMN ===\n";
echo "The templates table needs a column to store the file path.\n";
echo "Suggested column name: 'template_folder_path' or 'file_path'\n";
?>
