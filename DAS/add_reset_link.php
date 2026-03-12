<?php
// Quick script to add the Reset Profile Status link to admin dashboard

$file = 'C:/xampp/htdocs/Credit/Admin/dashboard.php';
$content = file_get_contents($file);

// Find the line with "Placeholder Library" and add our link after it
$search = '<a href="admin_messages.php" class="quick-action-btn btn btn-outline-warning">';
$newLink = '<a href="../DAS/modules/admin/reset_profile_status.php" class="quick-action-btn btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Profile Status
                        </a>
                        <a href="admin_messages.php" class="quick-action-btn btn btn-outline-warning">';

$content = str_replace($search, $newLink, $content);

file_put_contents($file, $content);

echo "✅ Successfully added Reset Profile Status link to admin dashboard!\n";
echo "Location: Admin Panel > DAS Management > Reset Profile Status\n";
?>
