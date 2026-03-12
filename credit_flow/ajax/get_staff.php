<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Staff ID required']);
    exit;
}

$staff_id = sanitize($_GET['staff_id']);

$stmt = $conn->prepare("SELECT staff_id, full_name, designation, role FROM users WHERE staff_id = ? AND is_active = 1");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $staff = $result->fetch_assoc();
    echo json_encode(['success' => true, 'staff' => $staff]);
} else {
    echo json_encode(['success' => false, 'message' => 'Staff not found']);
}
?>