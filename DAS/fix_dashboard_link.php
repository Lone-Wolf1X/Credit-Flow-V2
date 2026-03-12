<?php
// Update the admin dashboard link to point to the correct file

$file = 'C:/xampp/htdocs/Credit/Admin/dashboard.php';
$content = file_get_contents($file);

// Replace the incorrect DAS path with the correct Admin path
$content = str_replace(
    '../DAS/modules/admin/reset_profile_status.php',
    'das_reset_profile.php',
    $content
);

file_put_contents($file, $content);

echo "✅ Updated dashboard link to point to das_reset_profile.php\n";
?>
