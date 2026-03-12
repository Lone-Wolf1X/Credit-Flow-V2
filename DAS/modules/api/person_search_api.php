<?php
/**
 * Person Search API
 * Search for existing borrowers and guarantors across all branches
 */

header('Content-Type: application/json');
require_once '../../config/config.php';

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'search':
        searchPersons($conn);
        break;
    case 'get_person':
        getPersonDetails($conn);
        break;
    case 'copy_person':
        copyPersonToProfile($conn);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Search for persons by name or citizenship number
 */
function searchPersons($conn) {
    try {
        $query = $_GET['query'] ?? '';
        
        // Search pattern - if empty, get all persons
        $searchPattern = empty($query) ? '%' : '%' . $query . '%';
        
        // Search Master Entities (New Logic)
        $masterSql = "
            SELECT 
                m.id,
                'master' as type,
                CONCAT('ME-', m.id) as person_ref_no,
                m.full_name,
                m.full_name_en,
                m.citizenship_number,
                m.date_of_birth,
                m.id_issue_district as perm_district, -- Map fields
                '' as perm_municipality_vdc,
                0 as customer_profile_id,
                'N/A' as customer_id,
                'Global Repository' as profile_name,
                m.father_name
            FROM master_entities m
            WHERE m.full_name LIKE ? 
               OR m.full_name_en LIKE ? 
               OR m.citizenship_number LIKE ?
            ORDER BY m.id DESC
            LIMIT 20
        ";

        // Search borrowers (with fallback if person_ref_no doesn't exist)
        $borrowerSql = "
            SELECT 
                b.id,
                'borrower' as type,
                IFNULL(b.person_ref_no, CONCAT('BR-', b.id)) as person_ref_no,
                b.full_name,
                b.full_name_en,
                b.citizenship_number,
                b.date_of_birth,
                b.perm_district,
                b.perm_municipality_vdc,
                b.customer_profile_id,
                cp.customer_id,
                cp.full_name as profile_name,
                (SELECT name FROM family_details 
                 WHERE person_id = b.id 
                 AND person_type = 'Borrower' 
                 AND (relation = 'बुबा' OR relation = 'Father' OR relation = 'father')
                 LIMIT 1) as father_name
            FROM borrowers b
            LEFT JOIN customer_profiles cp ON b.customer_profile_id = cp.id
            WHERE b.full_name LIKE ? 
               OR b.full_name_en LIKE ? 
               OR b.citizenship_number LIKE ?
            ORDER BY b.id DESC
            LIMIT 50
        ";
        
        // Search guarantors (with fallback if person_ref_no doesn't exist)
        $guarantorSql = "
            SELECT 
                g.id,
                'guarantor' as type,
                IFNULL(g.person_ref_no, CONCAT('GR-', g.id)) as person_ref_no,
                g.full_name,
                g.full_name_en,
                g.citizenship_number,
                g.date_of_birth,
                g.perm_district,
                g.perm_municipality_vdc,
                g.customer_profile_id,
                cp.customer_id,
                cp.full_name as profile_name,
                (SELECT name FROM family_details 
                 WHERE person_id = g.id 
                 AND person_type = 'Guarantor' 
                 AND (relation = 'बुबा' OR relation = 'Father' OR relation = 'father')
                 LIMIT 1) as father_name
            FROM guarantors g
            LEFT JOIN customer_profiles cp ON g.customer_profile_id = cp.id
            WHERE g.full_name LIKE ? 
               OR g.full_name_en LIKE ? 
               OR g.citizenship_number LIKE ?
            ORDER BY g.id DESC
            LIMIT 50
        ";
        
        $persons = [];
        
        // Execute borrower search FIRST (highest priority)
        $stmt = $conn->prepare($borrowerSql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $persons[] = $row;
        }
        
        // Execute guarantor search SECOND
        $stmt = $conn->prepare($guarantorSql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $persons[] = $row;
        }

        // Execute Master Entity search LAST (lowest priority for deduplication)
        $stmt = $conn->prepare($masterSql);
        if ($stmt) {
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $persons[] = $row;
            }
        }
        
        // Remove duplicates based on citizenship number
        $uniquePersons = [];
        $seen = [];
        foreach ($persons as $person) {
            // Deduplication by Citizenship Number
            // Priority: Borrower > Guarantor > Master Entity
            // The loop order is now Borrower, then Guarantor, then Master.
            // So if we dedupe by Citizenship, Borrower/Guarantor will be kept (shown to user)
            // and Master Entity will be hidden if same citizenship exists.
            
            $key = $person['citizenship_number'];
            if (empty($key)) {
                $key = $person['full_name'] . '_' . $person['type']; // Fallback for no citizenship
            }
            
            if (!isset($seen[$key])) {
                $uniquePersons[] = $person;
                $seen[$key] = true;
            }
        }
        
        echo json_encode([
            'success' => true,
            'persons' => $uniquePersons,
            'count' => count($uniquePersons)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Get complete person details by ID and type
 */
function getPersonDetails($conn) {
    $id = $_GET['id'] ?? 0;
    $type = $_GET['type'] ?? '';
    
    if (!$id || !in_array($type, ['borrower', 'guarantor', 'master'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        return;
    }
    
    if ($type === 'master') {
        $stmt = $conn->prepare("SELECT * FROM master_entities WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($person = $result->fetch_assoc()) {
            // Map keys if needed (e.g., id_issue_district might be named differently? No, schema is consistent)
            // Master Entities might not have family details in separate table yet?
            // "father_name" is a column in master_entities.
            // But frontend expects `family_details` array for the dynamic rows.
            
            $family = [];
            if (!empty($person['father_name'])) $family[] = ['relation' => 'Father', 'name' => $person['father_name']];
            if (!empty($person['grandfather_name'])) $family[] = ['relation' => 'Grandfather', 'name' => $person['grandfather_name']];
            // ...
            
            $person['family_details'] = $family;
             // Map address fields if they differ
            $person['perm_municipality_vdc'] = ''; // master_entities doesn't have this yet in my schema
            // ...
            
            // Add Master Entity ID
            $person['master_entity_id'] = $id;

            echo json_encode(['success' => true, 'person' => $person]);
        } else {
             echo json_encode(['success' => false, 'error' => 'Master Entity not found']);
        }
        return;
    }

    $table = $type === 'borrower' ? 'borrowers' : 'guarantors';
    
    $sql = "SELECT * FROM $table WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($person = $result->fetch_assoc()) {
        // Also fetch family details
        $familySql = "SELECT * FROM family_details WHERE person_id = ? AND person_type = ?";
        $familyStmt = $conn->prepare($familySql);
        $personType = ucfirst($type);
        $familyStmt->bind_param("is", $id, $personType);
        $familyStmt->execute();
        $familyResult = $familyStmt->get_result();
        
        $family = [];
        while ($row = $familyResult->fetch_assoc()) {
            $family[] = $row;
        }
        
        $person['family_details'] = $family;
        
        echo json_encode([
            'success' => true,
            'person' => $person
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Person not found']);
    }
}

/**
 * Copy person entry to another role (borrower to guarantor or vice versa)
 */
function copyPersonToProfile($conn) {
    try {
        $sourceId = $_POST['source_id'] ?? 0;
        $sourceType = $_POST['source_type'] ?? ''; // borrower or guarantor
        $targetType = $_POST['target_type'] ?? ''; // borrower or guarantor
        $profileId = $_POST['profile_id'] ?? 0;
        
        if (!$sourceId || !$sourceType || !$targetType || !$profileId) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }
        
        // Get source person data
        $sourceTable = $sourceType === 'borrower' ? 'borrowers' : 'guarantors';
        $targetTable = $targetType === 'borrower' ? 'borrowers' : 'guarantors';
        
        // DEBUG: Log the parameters
        error_log("=== COPY PERSON DEBUG ===");
        error_log("Source ID: $sourceId");
        error_log("Source Type: $sourceType");
        error_log("Source Table: $sourceTable");
        error_log("Target Type: $targetType");
        error_log("Target Table: $targetTable");
        error_log("Profile ID: $profileId");
        
        $sql = "SELECT * FROM $sourceTable WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
            return;
        }
        $stmt->bind_param("i", $sourceId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("Query executed. Rows found: " . $result->num_rows);
        
        if ($person = $result->fetch_assoc()) {
            error_log("Person found! ID: $sourceId, Type: $sourceType");
            // Get columns that exist in target table
            $targetColumnsSql = "SHOW COLUMNS FROM $targetTable";
            $targetColumnsResult = $conn->query($targetColumnsSql);
            $targetColumns = [];
            while ($col = $targetColumnsResult->fetch_assoc()) {
                $targetColumns[] = $col['Field'];
            }
            
            // Always exclude these columns (only auto-generated or system fields)
            $alwaysExclude = ['id', 'created_at', 'updated_at', 'person_ref_no'];
            
            // Filter person data to only include columns that exist in target table
            $filteredPerson = [];
            foreach ($person as $key => $value) {
                // Skip if column doesn't exist in target table or is in always exclude list
                if (in_array($key, $targetColumns) && !in_array($key, $alwaysExclude)) {
                    $filteredPerson[$key] = $value;
                }
            }
            
            // Set new profile_id
            $filteredPerson['customer_profile_id'] = $profileId;
            
            // Generate new reference number
            $year = date('Y');
            $prefix = $targetType === 'borrower' ? 'BR' : 'GR';
            
            // Get next sequence number
            $countSql = "SELECT COUNT(*) as count FROM $targetTable WHERE person_ref_no LIKE ?";
            $countStmt = $conn->prepare($countSql);
            $pattern = $prefix . $year . '%';
            $countStmt->bind_param("s", $pattern);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $count = $countResult->fetch_assoc()['count'];
            $filteredPerson['person_ref_no'] = sprintf('%s%d%04d', $prefix, $year, $count + 1);
            
            // Build INSERT query
            $columns = array_keys($filteredPerson);
            $placeholders = array_fill(0, count($columns), '?');
            $types = str_repeat('s', count($columns));
            
            $insertSql = "INSERT INTO $targetTable (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param($types, ...array_values($filteredPerson));
            $insertStmt->execute();
            
            $newPersonId = $conn->insert_id;
            
            // Copy family details
            $familySql = "SELECT * FROM family_details WHERE person_id = ? AND person_type = ?";
            $familyStmt = $conn->prepare($familySql);
            $sourcePersonType = ucfirst($sourceType);
            $familyStmt->bind_param("is", $sourceId, $sourcePersonType);
            $familyStmt->execute();
            $familyResult = $familyStmt->get_result();
            
            $targetPersonType = ucfirst($targetType);
            while ($family = $familyResult->fetch_assoc()) {
                $familyInsertSql = "INSERT INTO family_details (person_id, person_type, relation, name) VALUES (?, ?, ?, ?)";
                $familyInsertStmt = $conn->prepare($familyInsertSql);
                $familyInsertStmt->bind_param(
                    "isss",
                    $newPersonId,
                    $targetPersonType,
                    $family['relation'],
                    $family['name']
                );
                $familyInsertStmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Person copied successfully',
                'new_id' => $newPersonId
            ]);
        } else {
            error_log("Person NOT found! ID: $sourceId, Type: $sourceType, Table: $sourceTable");
            echo json_encode([
                'success' => false, 
                'error' => 'Source person not found',
                'debug' => [
                    'source_id' => $sourceId,
                    'source_type' => $sourceType,
                    'source_table' => $sourceTable,
                    'sql' => $sql
                ]
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
