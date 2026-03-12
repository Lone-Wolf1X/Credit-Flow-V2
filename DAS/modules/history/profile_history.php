<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/comment_functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Get profile ID from URL
$profile_id = $_GET['id'] ?? '';

if (empty($profile_id)) {
    header('Location: profile_history_list.php');
    exit;
}

// Fetch customer profile
$stmt = $conn->prepare("SELECT cp.*, u.full_name as created_by_name FROM customer_profiles cp LEFT JOIN users u ON cp.created_by = u.id WHERE cp.id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if (!$profile) {
    header('Location: profile_history_list.php');
    exit;
}

// Page variables for layout
$pageTitle = 'Document Generation History - ' . $profile['full_name'];
$activeMenu = 'profile_history';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

// Fetch all comments/workflow logs
$workflow_logs = getProfileComments($profile_id);

// Fetch all generated documents
$stmt_docs = $conn->prepare("
    SELECT gd.*, u.full_name as generated_by_name 
    FROM generated_documents gd
    LEFT JOIN users u ON gd.generated_by = u.id
    WHERE gd.customer_profile_id = ? AND gd.is_active = TRUE
    ORDER BY gd.generated_at DESC
");
$stmt_docs->bind_param("i", $profile_id);
$stmt_docs->execute();
$all_docs = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);

// Group documents by batch_id
$batches = [];
foreach ($all_docs as $doc) {
    $batch_id = $doc['batch_id'] ?? 'UNGROUPED_' . $doc['id'];
    if (!isset($batches[$batch_id])) {
        $batches[$batch_id] = [
            'batch_id' => $batch_id,
            'generated_at' => $doc['generated_at'],
            'generated_by' => $doc['generated_by_name'],
            'documents' => []
        ];
    }
    // Priority 1: template_name column
    // Priority 2: template_snapshot JSON
    if (empty($doc['template_name']) && !empty($doc['template_snapshot'])) {
        $snapshot = json_decode($doc['template_snapshot'], true);
        $doc['template_name'] = $snapshot['template_name'] ?? 'Unknown';
    }
    if (empty($doc['template_name'])) {
        $doc['template_name'] = 'Generated Document';
    }
    $batches[$batch_id]['documents'][] = $doc;
}

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-file-earmark-word text-primary me-2"></i>Document Generation History: <?php echo htmlspecialchars($profile['full_name']); ?>
                    </h2>
                    <p class="text-muted">Full history of generated documents and system batches</p>
                </div>
                <button class="btn btn-outline-secondary" onclick="window.history.back()">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar: Info Summary -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Profile Summary</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Customer ID</small>
                        <strong><?php echo htmlspecialchars($profile['customer_id']); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Current Status</small>
                        <?php
                        $statusColors = [
                            'Draft' => 'warning',
                            'Submitted' => 'info',
                            'Approved' => 'success',
                            'Rejected' => 'danger'
                        ];
                        $statusColor = $statusColors[$profile['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $statusColor; ?>"><?php echo htmlspecialchars($profile['status']); ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Originated By</small>
                        <strong><?php echo htmlspecialchars($profile['created_by_name']); ?></strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Created On</small>
                        <strong><?php echo date('M d, Y', strtotime($profile['created_at'])); ?></strong>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Quick Navigation</h6>
                    <div class="list-group list-group-flush small">
                        <a href="#documentSection" class="list-group-item list-group-item-action py-2 border-0">
                            <i class="bi bi-file-earmark-word me-2"></i>Generation History
                        </a>
                        <a href="#timelineSection" class="list-group-item list-group-item-action py-2 border-0">
                            <i class="bi bi-clock-history me-2"></i>Workflow Timeline
                        </a>
                        <a href="../customer/customer_profile.php?id=<?php echo $profile_id; ?>" class="list-group-item list-group-item-action py-2 border-0 text-primary">
                            <i class="bi bi-person-badge me-2"></i>Go to Active Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Timeline and Batches -->
        <div class="col-md-9">
            <!-- Document Generation Section -->
            <div id="documentSection" class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-dark"><i class="bi bi-file-earmark-word text-info me-2"></i>Document Generation Batches / कागजात उत्पादन इतिहास</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($batches)): ?>
                        <p class="text-muted text-center py-4">No documents have been generated for this profile yet.</p>
                    <?php else: ?>
                        <div class="accordion accordion-flush" id="batchAccordion">
                            <?php $i = 0; foreach ($batches as $batch_id => $batch): $i++; ?>
                                <div class="accordion-item border rounded mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php echo $i === 1 ? '' : 'collapsed'; ?> bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#batch_<?php echo $i; ?>">
                                            <div class="w-100 d-flex justify-content-between align-items-center pe-3">
                                                <div>
                                                    <span class="badge bg-secondary me-2">Batch #<?php echo $i; ?></span>
                                                    <strong><?php echo date('M d, Y H:i', strtotime($batch['generated_at'])); ?></strong>
                                                    <span class="text-muted ms-2 small">by <?php echo htmlspecialchars($batch['generated_by']); ?></span>
                                                </div>
                                                <div class="small">
                                                    <span class="badge bg-info"><?php echo count($batch['documents']); ?> Files</span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="batch_<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i === 1 ? 'show' : ''; ?>" data-bs-parent="#batchAccordion">
                                        <div class="accordion-body p-0">
                                            <table class="table table-hover table-sm mb-0">
                                                <thead class="small bg-white">
                                                    <tr>
                                                        <th class="ps-3 border-0">Template Name</th>
                                                        <th class="border-0">File Type</th>
                                                        <th class="border-0 text-end pe-3">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($batch['documents'] as $doc): ?>
                                                        <tr>
                                                            <td class="ps-3 py-2">
                                                                <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                                                <?php echo htmlspecialchars($doc['template_name']); ?>
                                                            </td>
                                                            <td class="py-2"><span class="badge bg-light text-dark border">Word / PDF</span></td>
                                                            <td class="py-2 text-end pe-3">
                                                                <?php $filePath = str_replace('generated_documents/', '', $doc['file_path']); ?>
                                                                <div class="btn-group">
                                                                    <a href="../../download_document.php?file=<?php echo urlencode($filePath); ?>" class="btn btn-xs btn-outline-primary py-0 px-2" title="Download Word">
                                                                        <i class="bi bi-download"></i>
                                                                    </a>
                                                                    <a href="../../download_pdf.php?file=<?php echo urlencode($filePath); ?>" class="btn btn-xs btn-outline-danger py-0 px-2" title="Download PDF">
                                                                        <i class="bi bi-file-pdf"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <div class="bg-light p-2 border-top text-end">
                                                <a href="../../download_zip.php?profile_id=<?php echo $profile_id; ?>&batch_id=<?php echo urlencode($batch_id); ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-file-zip me-1"></i> Download Batch ZIP
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Timeline Section -->
            <div id="timelineSection" class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-dark"><i class="bi bi-clock-history text-success me-2"></i>Workflow Review Timeline / कार्य प्रवाह समीक्षा</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($workflow_logs)): ?>
                        <p class="text-muted text-center py-4">No workflow logs recorded yet.</p>
                    <?php else: ?>
                        <div class="timeline ps-3 border-start border-2 border-light">
                            <?php foreach ($workflow_logs as $log): ?>
                                <?php
                                $stageColors = [
                                    'Submission' => 'primary',
                                    'Approval' => 'success',
                                    'Return' => 'warning',
                                    'Rejection' => 'danger'
                                ];
                                $color = $stageColors[$log['comment_type']] ?? 'secondary';
                                ?>
                                <div class="timeline-item position-relative mb-4">
                                    <div class="position-absolute translate-middle-x bg-<?php echo $color; ?> rounded-circle" 
                                         style="width: 12px; height: 12px; left: -19px; top: 10px;"></div>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-1">
                                            <span class="text-<?php echo $color; ?>"><?php echo htmlspecialchars($log['comment_type']); ?></span>
                                            <small class="text-muted ms-2">by <?php echo htmlspecialchars($log['commenter_name']); ?></small>
                                        </h6>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($log['commented_at'])); ?></small>
                                    </div>
                                    <div class="bg-light p-3 rounded mt-2">
                                        <p class="mb-0 small text-dark"><?php echo nl2br(htmlspecialchars($log['comment_text'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-xs {
    padding: 0.1rem 0.3rem;
    font-size: 0.75rem;
}
.timeline-item {
    padding-left: 20px;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}
.accordion-button:not(.collapsed) {
    color: #000;
    box-shadow: none;
}
</style>

<?php
$mainContent = ob_get_clean();
include '../../Layout/layout_new.php';
?>
