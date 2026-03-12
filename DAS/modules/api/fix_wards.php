<?php
// fix_wards.php
// Utility script to convert existing English digits in Ward Numbers (Database) to Nepali Unicode.

// Direct connection as per customer_api.php
$conn = new mysqli('localhost', 'root', '', 'das_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function convertEnglishToNepaliDigits($str) {
    if (empty($str)) return $str;
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    $nepali = ['०','१','२','३','४','५','६','७','८','९'];
    return str_replace($english, $nepali, $str);
}

try {
    global $conn;
    echo "Starting Ward Number Update...<br>";

    // 1. Fetch all borrowers
    $sql = "SELECT id, perm_ward_no, temp_ward_no FROM borrowers";
    $result = $conn->query($sql);
    
    $count = 0;

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $perm = $row['perm_ward_no'];
            $temp = $row['temp_ward_no'];
            
            $newPerm = convertEnglishToNepaliDigits($perm);
            $newTemp = convertEnglishToNepaliDigits($temp);
            
            // Only update if changes found
            if ($perm !== $newPerm || $temp !== $newTemp) {
                $updateSql = "UPDATE borrowers SET perm_ward_no = ?, temp_ward_no = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("ssi", $newPerm, $newTemp, $id);
                if($stmt->execute()) {
                    $count++;
                    echo "Updated Borrower ID $id: ($perm -> $newPerm), ($temp -> $newTemp)<br>";
                }
            }
        }
    }
    
    // Also update Guarantors if they have address fields (Checking schema)
    // Assuming guarantors table structure is similar or they are in same table?
    // Based on forms, guarantors have separate table 'guarantors'.
    
    $sqlG = "SELECT id, perm_ward_no, temp_ward_no FROM guarantors";
    // Check if table exists/fields exist before running, or try/catch. Assuming yes.
    $resultG = $conn->query($sqlG);
    if ($resultG && $resultG->num_rows > 0) {
        while($row = $resultG->fetch_assoc()) {
             $id = $row['id'];
            $perm = $row['perm_ward_no'];
            $temp = $row['temp_ward_no'];
            
            $newPerm = convertEnglishToNepaliDigits($perm);
            $newTemp = convertEnglishToNepaliDigits($temp);
            
             if ($perm !== $newPerm || $temp !== $newTemp) {
                $updateSql = "UPDATE guarantors SET perm_ward_no = ?, temp_ward_no = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("ssi", $newPerm, $newTemp, $id);
                if($stmt->execute()) {
                    $count++;
                    echo "Updated Guarantor ID $id: ($perm -> $newPerm), ($temp -> $newTemp)<br>";
                }
            }
        }
    }

    echo "Done. Total rows updated: $count";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
