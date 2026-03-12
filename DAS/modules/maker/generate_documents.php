<?php
/**
 * Generate Documents Page
 * UI for generating documents from approved profiles
 */

require_once '../config/config.php';
requireLogin();

$profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : null;

if (!$profile_id) {
    header('Location: ../modules/maker/dashboard.php');
    exit;
}

// Get profile info
$stmt = $das_conn->prepare("SELECT * FROM vw_profile_complete WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile || $profile['status'] !== 'Approved') {
    $_SESSION['error'] = 'Profile must be approved before generating documents';
    header('Location: ../modules/maker/dashboard.php');
    exit;
}

// Get available templates
$stmt = $das_conn->prepare("CALL sp_get_available_templates(?)");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get generated documents from new table
$stmt = $das_conn->prepare("
    SELECT 
        gd.*,
        u.full_name as generated_by_name,
        gd.template_snapshot
    FROM das_generated_documents gd
    LEFT JOIN users u ON gd.generated_by = u.id
    WHERE gd.customer_profile_id = ? AND gd.is_active = TRUE
    ORDER BY gd.generated_at DESC
");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$generated_docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Parse template snapshot for display
foreach ($generated_docs as &$doc) {
    if (!empty($doc['template_snapshot'])) {
        $snapshot = json_decode($doc['template_snapshot'], true);
        $doc['template_name'] = $snapshot['template_name'] ?? 'Unknown Template';
    } else {
        $doc['template_name'] = 'Unknown Template';
    }
    $doc['document_name'] = $doc['file_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Documents - DAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .template-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .template-card.selected {
            border: 2px solid #0d6efd;
            background: #e7f3ff;
        }
        .doc-item {
            border-left: 4px solid #198754;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="bi bi-file-earmark-text me-2"></i>Generate Documents</h2>
                <p class="text-muted">
                    Customer: <strong><?php echo htmlspecialchars($profile['full_name']); ?></strong> | 
                    ID: <strong><?php echo $profile['customer_id']; ?></strong> |
                    Scheme: <strong><?php echo $profile['scheme_name']; ?></strong>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="../modules/maker/customer_profile.php?id=<?php echo $profile_id; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Profile
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Available Templates -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Available Templates</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($templates)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No templates available for this loan scheme. Please contact administrator.
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Select templates to generate (multiple selection allowed):</p>
                            
                            <div class="row" id="templateSelection">
                                <?php foreach ($templates as $template): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card template-card" onclick="toggleTemplate(<?php echo $template['id']; ?>)">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input template-checkbox" 
                                                           type="checkbox" 
                                                           id="template_<?php echo $template['id']; ?>"
                                                           value="<?php echo $template['id']; ?>">
                                                    <label class="form-check-label w-100" for="template_<?php echo $template['id']; ?>">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($template['template_name']); ?></h6>
                                                        <p class="text-muted small mb-0">
                                                            <?php echo htmlspecialchars($template['description'] ?? 'No description'); ?>
                                                        </p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-success btn-lg" onclick="generateDocuments()">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Generate Selected Documents
                                </button>
                                <button class="btn btn-outline-primary" onclick="selectAll()">
                                    <i class="bi bi-check-all me-2"></i>Select All
                                </button>
                                <button class="btn btn-outline-secondary" onclick="deselectAll()">
                                    <i class="bi bi-x-circle me-2"></i>Deselect All
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Generated Documents -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Generated Documents</h6>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;" id="generatedDocsList">
                        <?php if (empty($generated_docs)): ?>
                            <p class="text-muted small">No documents generated yet</p>
                        <?php else: ?>
                            <?php foreach ($generated_docs as $doc): ?>
                                <div class="doc-item p-3 mb-2 rounded">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($doc['document_name']); ?></h6>
                                    <p class="small text-muted mb-2">
                                        Generated: <?php echo date('M d, Y H:i', strtotime($doc['generated_at'])); ?><br>
                                        By: <?php echo htmlspecialchars($doc['generated_by_name']); ?>
                                    </p>
                                    <a href="../../DAS/<?php echo $doc['file_path']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       download>
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 mb-0">Generating documents...</p>
                    <p class="text-muted small">This may take a few moments</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const profileId = <?php echo $profile_id; ?>;
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

        function toggleTemplate(templateId) {
            const checkbox = document.getElementById('template_' + templateId);
            checkbox.checked = !checkbox.checked;
            updateCardStyle(templateId);
        }

        function updateCardStyle(templateId) {
            const checkbox = document.getElementById('template_' + templateId);
            const card = checkbox.closest('.template-card');
            
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }

        function selectAll() {
            document.querySelectorAll('.template-checkbox').forEach(cb => {
                cb.checked = true;
                updateCardStyle(cb.value);
            });
        }

        function deselectAll() {
            document.querySelectorAll('.template-checkbox').forEach(cb => {
                cb.checked = false;
                updateCardStyle(cb.value);
            });
        }

        function generateDocuments() {
            const selected = Array.from(document.querySelectorAll('.template-checkbox:checked'))
                                 .map(cb => cb.value);
            
            if (selected.length === 0) {
                alert('Please select at least one template');
                return;
            }

            if (!confirm(`Generate ${selected.length} document(s)?`)) return;

            loadingModal.show();

            fetch('../api/document_generation_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=generate_multiple&profile_id=${profileId}&template_ids=${JSON.stringify(selected)}`
            })
            .then(res => res.json())
            .then(data => {
                loadingModal.hide();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                loadingModal.hide();
                alert('Error generating documents: ' + err.message);
            });
        }
    </script>
</body>
</html>
