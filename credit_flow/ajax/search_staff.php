<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['query']) || !isset($_GET['role'])) {
    echo json_encode(['success' => false, 'users' => []]);
    exit;
}

$query = sanitize($_GET['query']);
$role = sanitize($_GET['role']);

// Determine which roles to search
$roles = [];
if ($role === 'reviewer') {
    // Return all users except self
    $roles = ['Initiator', 'Reviewer', 'Approver', 'Admin'];
} elseif ($role === 'approver') {
    // Return all users except self
    $roles = ['Initiator', 'Reviewer', 'Approver', 'Admin'];
}

if (empty($roles)) {
    echo json_encode(['success' => false, 'users' => []]);
    exit;
}

// Search by name or staff ID
$placeholders = implode(',', array_fill(0, count($roles), '?'));
$sql = "SELECT id, staff_id, full_name, designation, role 
        FROM users 
        WHERE is_active = 1 
        AND role IN ($placeholders)
        AND (full_name LIKE ? OR staff_id LIKE ?)
        ORDER BY full_name
        LIMIT 10";

$stmt = $conn->prepare($sql);

// Bind role parameters
$types = str_repeat('s', count($roles)) . 'ss';
$searchTerm = "%$query%";
$params = array_merge($roles, [$searchTerm, $searchTerm]);

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'staff_id' => $row['staff_id'],
        'full_name' => $row['full_name'],
        'designation' => $row['designation'],
        'role' => $row['role'],
        'display' => $row['full_name'] . ' - ' . $row['staff_id'] . ' (' . $row['designation'] . ')'
    ];
}

echo json_encode(['success' => true, 'users' => $users]);
?>