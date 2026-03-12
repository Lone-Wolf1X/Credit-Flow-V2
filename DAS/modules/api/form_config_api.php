<?php
/**
 * Form Configuration API
 * Handles CRUD operations for form sections and fields
 */

session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_sections':
            getSections($conn);
            break;
        
        case 'get_fields':
            getFields($conn);
            break;
        
        case 'get_field':
            getField($conn);
            break;
        
        case 'save_field':
            saveField($conn);
            break;
        
        case 'delete_field':
            deleteField($conn);
            break;
        
        case 'get_form_config':
            getFormConfig($conn);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get all sections
 */
function getSections($conn) {
    $sql = "SELECT * FROM form_section_config WHERE is_active = 1 ORDER BY form_type, person_type, step_number";
    $result = $conn->query($sql);
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $sections]);
}

/**
 * Get fields with optional filters
 */
function getFields($conn) {
    $formType = $_GET['form_type'] ?? '';
    $personType = $_GET['person_type'] ?? '';
    $sectionId = $_GET['section_id'] ?? '';
    
    $sql = "SELECT f.*, s.section_label_en, s.form_type, s.person_type 
            FROM form_field_config f
            JOIN form_section_config s ON f.section_id = s.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($formType) {
        $sql .= " AND s.form_type = ?";
        $params[] = $formType;
        $types .= 's';
    }
    
    if ($personType) {
        $sql .= " AND s.person_type = ?";
        $params[] = $personType;
        $types .= 's';
    }
    
    if ($sectionId) {
        $sql .= " AND f.section_id = ?";
        $params[] = $sectionId;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY s.step_number, f.display_order";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fields = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        if ($row['field_options']) {
            $row['field_options'] = json_decode($row['field_options'], true);
        }
        if ($row['validation_rules']) {
            $row['validation_rules'] = json_decode($row['validation_rules'], true);
        }
        $fields[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $fields]);
}

/**
 * Get single field
 */
function getField($conn) {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $conn->prepare("SELECT * FROM form_field_config WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        if ($row['field_options']) {
            $row['field_options'] = json_decode($row['field_options'], true);
        }
        if ($row['validation_rules']) {
            $row['validation_rules'] = json_decode($row['validation_rules'], true);
        }
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Field not found']);
    }
}

/**
 * Save field (create or update)
 */
function saveField($conn) {
    // Only Admin can modify
    if ($_SESSION['role_name'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $fieldId = $_POST['field_id'] ?? null;
    $sectionId = $_POST['section_id'] ?? null;
    $fieldName = $_POST['field_name'] ?? '';
    $labelEn = $_POST['field_label_en'] ?? '';
    $labelNp = $_POST['field_label_np'] ?? '';
    $fieldType = $_POST['field_type'] ?? 'text';
    $fieldOptions = $_POST['field_options'] ?? null;
    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $columnWidth = $_POST['column_width'] ?? 'col-md-4';
    $displayOrder = $_POST['display_order'] ?? 1;
    
    // Validate JSON options if provided
    if ($fieldOptions) {
        $decoded = json_decode($fieldOptions);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON in field options']);
            return;
        }
    }
    
    if ($fieldId) {
        // Update existing field
        $sql = "UPDATE form_field_config SET 
                section_id = ?, field_name = ?, field_label_en = ?, field_label_np = ?,
                field_type = ?, field_options = ?, is_required = ?, 
                column_width = ?, display_order = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssssisii', 
            $sectionId, $fieldName, $labelEn, $labelNp, $fieldType, 
            $fieldOptions, $isRequired, $columnWidth, $displayOrder, $fieldId
        );
    } else {
        // Create new field
        $sql = "INSERT INTO form_field_config 
                (section_id, field_name, field_label_en, field_label_np, field_type, 
                 field_options, is_required, column_width, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssssssi', 
            $sectionId, $fieldName, $labelEn, $labelNp, $fieldType, 
            $fieldOptions, $isRequired, $columnWidth, $displayOrder
        );
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Field saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error saving field: ' . $stmt->error]);
    }
}

/**
 * Delete field
 */
function deleteField($conn) {
    // Only Admin can delete
    if ($_SESSION['role_name'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $fieldId = $_POST['field_id'] ?? 0;
    
    $stmt = $conn->prepare("DELETE FROM form_field_config WHERE id = ?");
    $stmt->bind_param('i', $fieldId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Field deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting field']);
    }
}

/**
 * Get complete form configuration for rendering
 */
function getFormConfig($conn) {
    $formType = $_GET['form_type'] ?? '';
    $personType = $_GET['person_type'] ?? '';
    
    if (!$formType || !$personType) {
        echo json_encode(['success' => false, 'message' => 'Form type and person type required']);
        return;
    }
    
    // Get sections
    $sql = "SELECT * FROM form_section_config 
            WHERE form_type = ? AND (person_type = ? OR person_type = 'Both') 
            AND is_active = 1 
            ORDER BY step_number";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $formType, $personType);
    $stmt->execute();
    $sectionsResult = $stmt->get_result();
    
    $config = [];
    while ($section = $sectionsResult->fetch_assoc()) {
        // Get fields for this section
        $fieldsSql = "SELECT * FROM form_field_config 
                      WHERE section_id = ? AND is_active = 1 
                      ORDER BY display_order";
        
        $fieldsStmt = $conn->prepare($fieldsSql);
        $fieldsStmt->bind_param('i', $section['id']);
        $fieldsStmt->execute();
        $fieldsResult = $fieldsStmt->get_result();
        
        $fields = [];
        while ($field = $fieldsResult->fetch_assoc()) {
            // Decode JSON fields
            if ($field['field_options']) {
                $field['field_options'] = json_decode($field['field_options'], true);
            }
            if ($field['validation_rules']) {
                $field['validation_rules'] = json_decode($field['validation_rules'], true);
            }
            $fields[] = $field;
        }
        
        $section['fields'] = $fields;
        $config[] = $section;
    }
    
    echo json_encode(['success' => true, 'data' => $config]);
}
?>
