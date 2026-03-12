<?php
/**
 * Test Page for Document Generation
 * Preview placeholders and test document generation
 */

session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

$profile_id = $_GET['profile_id'] ?? null;

if (!$profile_id) {
    die('Profile ID is required');
}

$pageTitle = 'Document Generation Test';
$activeMenu = 'customer_profile';
$userName = $_SESSION['full_name'] ?? 'User';
$userAvatar = '../../asstes/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-word me-2"></i>Document Generation Test
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../customer/customer_profile.php?id=<?php echo $profile_id; ?>">Customer Profile</a></li>
                    <li class="breadcrumb-item active">Document Test</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Placeholder Preview</h5>
                </div>
                <div class="card-body">
                    <button id="loadPlaceholders" class="btn btn-primary mb-3">
                        <i class="bi bi-arrow-clockwise me-2"></i>Load Placeholders
                    </button>
                    <div id="placeholderPreview"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-word me-2"></i>Generate Documents</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Loan Scheme</label>
                        <select id="schemeSelect" class="form-select">
                            <option value="">Select Scheme...</option>
                            <?php
                            $conn = new mysqli('localhost', 'root', '', 'das_db');
                            $schemes = $conn->query("SELECT id, scheme_name, scheme_code FROM loan_schemes WHERE is_active = 1 ORDER BY scheme_name");
                            while ($scheme = $schemes->fetch_assoc()) {
                                echo "<option value='{$scheme['id']}'>{$scheme['scheme_name']} ({$scheme['scheme_code']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button id="generateDocs" class="btn btn-success">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Generate Documents
                    </button>
                    <div id="generationResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    const profileId = <?php echo $profile_id; ?>;
    
    // Load placeholders
    $('#loadPlaceholders').click(function() {
        $(this).prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>Loading...');
        
        $.get('../api/document_api.php', {
            action: 'preview_placeholders',
            profile_id: profileId
        })
        .done(function(response) {
            if (response.success) {
                $('#placeholderPreview').html(response.preview_html);
            } else {
                $('#placeholderPreview').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        })
        .fail(function() {
            $('#placeholderPreview').html('<div class="alert alert-danger">Error loading placeholders</div>');
        })
        .always(function() {
            $('#loadPlaceholders').prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-2"></i>Load Placeholders');
        });
    });
    
    // Generate documents
    $('#generateDocs').click(function() {
        const schemeId = $('#schemeSelect').val();
        
        if (!schemeId) {
            alert('Please select a loan scheme');
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>Generating...');
        
        $.post('../api/document_api.php', {
            action: 'generate_documents',
            profile_id: profileId,
            scheme_id: schemeId
        })
        .done(function(response) {
            if (response.success) {
                let html = '<div class="alert alert-success">' + response.message + '</div>';
                html += '<ul class="list-group">';
                response.files.forEach(function(file) {
                    html += '<li class="list-group-item">';
                    html += '<i class="bi bi-file-earmark-word text-primary me-2"></i>';
                    html += file.template_name;
                    html += ' <a href="../../' + file.file_path + '" class="btn btn-sm btn-primary float-end" download>';
                    html += '<i class="bi bi-download me-1"></i>Download</a>';
                    html += '</li>';
                });
                html += '</ul>';
                $('#generationResult').html(html);
            } else {
                $('#generationResult').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        })
        .fail(function() {
            $('#generationResult').html('<div class="alert alert-danger">Error generating documents</div>');
        })
        .always(function() {
            $('#generateDocs').prop('disabled', false).html('<i class="bi bi-file-earmark-arrow-down me-2"></i>Generate Documents');
        });
    });
});
</script>

<?php
$mainContent = ob_get_clean();
$assetPath = '../../asstes';
include '../../Layout/layout_new.php';
?>
