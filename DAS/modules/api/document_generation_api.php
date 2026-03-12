<?php
/**
 * Document Generation API
 * Handles AJAX requests for document generation
 */

require_once '../../config/config.php';
require_once '../../includes/document_generation.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'generate':
        $profile_id = intval($_POST['profile_id']);
        $template_id = intval($_POST['template_id']);
        $batch_id = 'SINGLE_' . date('Ymd_His') . '_' . uniqid();
        
        $result = generateDocument($profile_id, $template_id, $batch_id);
        echo json_encode($result);
        break;
        
    case 'generate_multiple':
        $profile_id = intval($_POST['profile_id']);
        $template_ids = json_decode($_POST['template_ids'], true);
        
        $results = [];
        $success_count = 0;
        $batch_id = 'BATCH_' . date('Ymd_His') . '_' . uniqid();
        
        foreach ($template_ids as $template_id) {
            $result = generateDocument($profile_id, intval($template_id), $batch_id);
            $results[] = $result;
            if ($result['success']) $success_count++;
        }
        
        echo json_encode([
            'success' => $success_count > 0,
            'message' => "Generated $success_count of " . count($template_ids) . " documents",
            'batch_id' => $batch_id,
            'results' => $results
        ]);
        break;
        
    case 'get_available_templates':
        $profile_id = intval($_POST['profile_id']);
        
        $stmt = $das_conn->prepare("CALL sp_get_available_templates(?)");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'templates' => $templates]);
        break;
        
    case 'get_generated_documents':
        $profile_id = intval($_POST['profile_id']);
        $latest_only = isset($_POST['latest_only']) && $_POST['latest_only'] == 'true';
        
        $sql = "
            SELECT 
                gd.*,
                u.full_name as generated_by_name
            FROM generated_documents gd
            LEFT JOIN users u ON gd.generated_by = u.id
            WHERE gd.customer_profile_id = ? AND gd.is_active = TRUE
        ";
        
        if ($latest_only) {
            // Get documents from the most recent batch. 
            // Handle NULL batch_id for legacy records.
            $sql .= " AND (gd.batch_id <=> (
                SELECT batch_id FROM generated_documents 
                WHERE customer_profile_id = ? AND is_active = TRUE 
                ORDER BY generated_at DESC LIMIT 1
            ))";
        }
        
        $sql .= " ORDER BY gd.generated_at DESC";
        
        $stmt = $das_conn->prepare($sql);
        if ($latest_only) {
            $stmt->bind_param("ii", $profile_id, $profile_id);
        } else {
            $stmt->bind_param("i", $profile_id);
        }
        
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Parse template snapshots
        foreach ($documents as &$doc) {
            // Priority 1: template_name column if it has a value
            // Priority 2: template_snapshot JSON
            if (empty($doc['template_name']) && !empty($doc['template_snapshot'])) {
                $snapshot = json_decode($doc['template_snapshot'], true);
                $doc['template_name'] = $snapshot['template_name'] ?? 'Unknown';
            }
            if (empty($doc['template_name'])) {
                $doc['template_name'] = 'Generated Document';
            }
        }
        
        echo json_encode(['success' => true, 'data' => $documents]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
