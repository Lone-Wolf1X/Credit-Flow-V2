<?php
require_once 'c:/xampp/htdocs/Credit/DAS/config/config.php';

$customer_id = '2025005';
echo "--- DIAGNOSTIC START ---\n";

// 1. Get Profile
$stmt = $conn->prepare("SELECT id, full_name, status FROM customer_profiles WHERE customer_id = ?");
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("CRITICAL: Profile 2025005 not found in customer_profiles!\n");
}
$profile = $res->fetch_assoc();
$profile_id = $profile['id'];
echo "1. Found Profile: ID=$profile_id | Name={$profile['full_name']} | Status={$profile['status']}\n";

// 2. Get Loan Details to find Scheme
$stmt = $conn->prepare("SELECT id, scheme_id FROM loan_details WHERE customer_profile_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("CRITICAL: No loan details found for Profile ID $profile_id. Cannot determine Scheme.\n");
}
$loan = $res->fetch_assoc();
$scheme_id = $loan['scheme_id'];
echo "2. Found Loan: ID={$loan['id']} | Scheme ID=$scheme_id | Amount=(Not Available)\n";

// 3. Check Templates for Scheme
$stmt = $conn->prepare("SELECT id, template_name, file_path, scheme_id FROM templates WHERE scheme_id = ?");
$stmt->bind_param("i", $scheme_id);
$stmt->execute();
$res = $stmt->get_result();
$templates = [];
if ($res->num_rows === 0) {
    echo "CRITICAL: No templates found for Scheme ID $scheme_id!\n";
    // Check if templates exist at all
    $all = $conn->query("SELECT COUNT(*) c FROM templates");
    echo "   (Total templates in DB: " . $all->fetch_assoc()['c'] . ")\n";
} else {
    echo "3. Found " . $res->num_rows . " templates for Scheme $scheme_id:\n";
    while ($row = $res->fetch_assoc()) {
        echo "   - Template ID: {$row['id']} | Name: {$row['template_name']} | Path: {$row['file_path']}\n";
        $templates[] = $row;
    }
}

// 4. Test File Paths
foreach ($templates as $tpl) {
    $full_path = __DIR__ . "/../" . $tpl['file_path'];
    if (file_exists($full_path)) {
        echo "   [OK] File exists: $full_path\n";
    } else {
        echo "   [FAIL] File missing: $full_path\n";
    }
}

echo "--- DIAGNOSTIC END ---\n";
?>
