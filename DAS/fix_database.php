<?php
/**
 * Fix DAS 1 Form Save Issue
 * Add missing 'ctz_reissued' column to borrowers and guarantors tables
 */

require_once 'config/config.php';

echo "<h1>DAS 1 Database Fix</h1>";
echo "<p>Adding missing 'ctz_reissued' column...</p>";
echo "<hr>";

// Add column to borrowers table
echo "<h2>1. Borrowers Table</h2>";
$sql = "ALTER TABLE borrowers ADD COLUMN IF NOT EXISTS ctz_reissued TINYINT(1) DEFAULT 0 COMMENT 'Citizenship reissued flag'";
if ($conn->query($sql)) {
    echo "<p style='color: green;'>✅ Successfully added 'ctz_reissued' column to borrowers table</p>";
} else {
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM borrowers LIKE 'ctz_reissued'");
    if ($check->num_rows > 0) {
        echo "<p style='color: blue;'>ℹ️ Column 'ctz_reissued' already exists in borrowers table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

// Add column to guarantors table
echo "<h2>2. Guarantors Table</h2>";
$sql = "ALTER TABLE guarantors ADD COLUMN IF NOT EXISTS ctz_reissued TINYINT(1) DEFAULT 0 COMMENT 'Citizenship reissued flag'";
if ($conn->query($sql)) {
    echo "<p style='color: green;'>✅ Successfully added 'ctz_reissued' column to guarantors table</p>";
} else {
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM guarantors LIKE 'ctz_reissued'");
    if ($check->num_rows > 0) {
        echo "<p style='color: blue;'>ℹ️ Column 'ctz_reissued' already exists in guarantors table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

// Verify the fix
echo "<h2>3. Verification</h2>";
$result = $conn->query("SHOW COLUMNS FROM borrowers LIKE 'ctz_reissued'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Column 'ctz_reissued' is now present in borrowers table</p>";
    $row = $result->fetch_assoc();
    echo "<pre>" . print_r($row, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Column 'ctz_reissued' is still missing from borrowers table</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p>1. Try saving the borrower form again</p>";
echo "<p>2. If you still get errors, check the browser console and share the error message</p>";
echo "<p>3. <a href='debug_form_save.php'>Run Debug Script</a> to verify everything is working</p>";
?>
