<?php
/**
 * Document Generation Test Script
 * Tests document generation and updates last_generation_log.txt
 */

session_start();

// Simulate logged-in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/document_generation.php';

// Create alias for consistency
$das_conn = $conn;

// Get parameters
$profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : null;
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Generation Test - DAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-entry { font-family: monospace; font-size: 12px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2><i class="bi bi-bug"></i> Document Generation Test & Debug Tool</h2>
        <p class="text-muted">Test document generation and view detailed logs</p>
        <hr>

        <?php if ($action === 'list'): ?>
            <!-- List Profiles and Templates -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Approved Customer Profiles</h5>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php
                            $stmt = $das_conn->prepare("SELECT id, customer_id, status FROM customer_profiles WHERE status = 'Approved' ORDER BY id DESC");
                            $stmt->execute();
                            $profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            if (empty($profiles)) {
                                echo '<div class="alert alert-warning">No approved profiles found</div>';
                            } else {
                                echo '<div class="list-group">';
                                foreach ($profiles as $p) {
                                    echo '<a href="?action=select_template&profile_id=' . $p['id'] . '" class="list-group-item list-group-item-action">';
                                    echo '<strong>Profile ID:</strong> ' . $p['id'] . ' | ';
                                    echo '<strong>Customer ID:</strong> ' . htmlspecialchars($p['customer_id']) . ' | ';
                                    echo '<span class="badge bg-success">' . $p['status'] . '</span>';
                                    echo '</a>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Database:</strong> <?php echo $das_conn->connect_error ? '<span class="error">Error</span>' : '<span class="success">Connected</span>'; ?></p>
                            <p><strong>PHPWord:</strong> <?php echo class_exists('PhpOffice\PhpWord\TemplateProcessor') ? '<span class="success">Loaded</span>' : '<span class="error">Not Found</span>'; ?></p>
                            <p><strong>Debug Log:</strong> <?php echo file_exists(__DIR__ . '/debug_doc_gen.log') ? '<span class="success">Exists</span>' : '<span class="error">Not Found</span>'; ?></p>
                            <p><strong>Generation Log:</strong> <?php echo file_exists(__DIR__ . '/generated_documents/last_generation_log.txt') ? '<span class="success">Exists</span>' : '<span class="error">Not Found</span>'; ?></p>
                            
                            <hr>
                            <h6>Recent Debug Log (Last 10 lines):</h6>
                            <pre class="log-entry"><?php
                            $log_file = __DIR__ . '/debug_doc_gen.log';
                            if (file_exists($log_file)) {
                                $lines = file($log_file);
                                echo htmlspecialchars(implode('', array_slice($lines, -10)));
                            } else {
                                echo 'No log file found';
                            }
                            ?></pre>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'select_template' && $profile_id): ?>
            <!-- Select Template for Profile -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Select Template for Profile #<?php echo $profile_id; ?></h5>
                </div>
                <div class="card-body">
                    <a href="?" class="btn btn-secondary mb-3">← Back to Profiles</a>
                    
                    <?php
                    $stmt = $das_conn->prepare("SELECT * FROM templates WHERE is_active = TRUE ORDER BY template_name");
                    $stmt->execute();
                    $templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($templates)) {
                        echo '<div class="alert alert-warning">No active templates found</div>';
                    } else {
                        echo '<div class="list-group">';
                        foreach ($templates as $t) {
                            echo '<a href="?action=generate&profile_id=' . $profile_id . '&template_id=' . $t['id'] . '" class="list-group-item list-group-item-action">';
                            echo '<h6>' . htmlspecialchars($t['template_name']) . '</h6>';
                            echo '<small>Code: ' . htmlspecialchars($t['template_code']) . ' | ';
                            echo 'Path: ' . htmlspecialchars($t['template_folder_path'] ?: $t['file_path']) . '</small>';
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

        <?php elseif ($action === 'generate' && $profile_id && $template_id): ?>
            <!-- Generate Document -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Generating Document...</h5>
                </div>
                <div class="card-body">
                    <p><strong>Profile ID:</strong> <?php echo $profile_id; ?></p>
                    <p><strong>Template ID:</strong> <?php echo $template_id; ?></p>
                    
                    <hr>
                    <h6>Generation Process:</h6>
                    
                    <?php
                    // Clear previous generation log
                    $gen_log_file = __DIR__ . '/generated_documents/last_generation_log.txt';
                    
                    // Call generation function
                    echo '<div class="alert alert-info">Starting document generation...</div>';
                    
                    $result = generateDocument($profile_id, $template_id);
                    
                    if ($result['success']) {
                        echo '<div class="alert alert-success">';
                        echo '<h5>✓ Success!</h5>';
                        echo '<p><strong>Message:</strong> ' . htmlspecialchars($result['message']) . '</p>';
                        echo '<p><strong>File Path:</strong> ' . htmlspecialchars($result['file_path']) . '</p>';
                        echo '<p><strong>Filename:</strong> ' . htmlspecialchars($result['filename']) . '</p>';
                        echo '<p><strong>Document ID:</strong> ' . $result['document_id'] . '</p>';
                        echo '<p><a href="' . htmlspecialchars($result['relative_path']) . '" class="btn btn-primary" download>Download Document</a></p>';
                        echo '</div>';
                        
                        // Now generate detailed log
                        generateDetailedLog($profile_id, $template_id, $gen_log_file);
                        
                    } else {
                        echo '<div class="alert alert-danger">';
                        echo '<h5>✗ Error!</h5>';
                        echo '<p><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';
                        echo '</div>';
                    }
                    ?>
                    
                    <hr>
                    <h6>Debug Log (Last 20 lines):</h6>
                    <pre class="log-entry"><?php
                    $log_file = __DIR__ . '/debug_doc_gen.log';
                    if (file_exists($log_file)) {
                        $lines = file($log_file);
                        echo htmlspecialchars(implode('', array_slice($lines, -20)));
                    }
                    ?></pre>
                    
                    <hr>
                    <h6>Generation Log (last_generation_log.txt):</h6>
                    <pre class="log-entry"><?php
                    if (file_exists($gen_log_file)) {
                        echo htmlspecialchars(file_get_contents($gen_log_file));
                    } else {
                        echo 'No generation log found';
                    }
                    ?></pre>
                    
                    <div class="mt-3">
                        <a href="?" class="btn btn-secondary">← Back to Start</a>
                        <a href="?action=select_template&profile_id=<?php echo $profile_id; ?>" class="btn btn-primary">Generate Another</a>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Generate detailed log similar to last_generation_log.txt format
 */
function generateDetailedLog($profile_id, $template_id, $log_file) {
    global $das_conn;
    
    // Get template info
    $template = getTemplate($template_id);
    if (!$template) return;
    
    $log = "Generation Log - " . date('Y-m-d H:i:s') . "\n";
    $log .= "Template: " . ($template['template_name'] ?? 'Unknown') . "\n";
    $log .= str_repeat('-', 40) . "\n";
    
    // Get all data
    $borrowers = getAllBorrowers($profile_id);
    $guarantors = getAllGuarantors($profile_id);
    $collateral = getAllCollateral($profile_id);
    
    // Get loan details
    $stmt = $das_conn->prepare("SELECT * FROM loan_details WHERE customer_profile_id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $loan = $stmt->get_result()->fetch_assoc();
    
    // Count variables
    $var_count = 0;
    
    // Log borrower data
    if (!empty($borrowers)) {
        foreach ($borrowers as $idx => $b) {
            $num = $idx + 1;
            $prefix = $idx === 0 ? 'BR' : 'CO';
            
            $log .= logVariable("\${$prefix}{$num}_NM_NP", $b['full_name'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_NM_EN", $b['full_name_en'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_DOB", $b['date_of_birth'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_AGE", calculateAge($b['date_of_birth'] ?? null));
            $log .= logVariable("\${$prefix}{$num}_P_DIST", $b['permanent_district'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_P_MUN", $b['permanent_municipality'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_P_WARD", $b['permanent_ward_no'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_T_DIST", $b['temporary_district'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_T_MUN", $b['temporary_municipality'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_T_WARD", $b['temporary_ward_no'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_CIT_NO", $b['citizenship_number'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_ISS_DT", $b['citizenship_issued_date'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_REISS_DT", $b['citizenship_reissue_date'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_REISS_TIMES", $b['citizenship_reissue_times'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_ID_AUTH", $b['citizenship_issued_authority'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_ID_DIST", $b['citizenship_issued_district'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_SPOUSE", $b['spouse_name'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_FATHER", $b['father_name'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_MOTHER", $b['mother_name'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_GF", $b['grandfather_name'] ?? null);
            $log .= logVariable("\${$prefix}{$num}_GM", $b['grandmother_name'] ?? null);
        }
    }
    
    // Log loan data
    if ($loan) {
        $log .= logVariable('$LN_LSL_DT', $loan['loan_approved_date'] ?? null);
        $log .= logVariable('$LN_SCHEME', $loan['loan_scheme'] ?? null);
        $log .= logVariable('$LN_AMT', formatNepaliCurrency($loan['sanctioned_amount'] ?? 0));
        $log .= logVariable('$LN_PURPOSE', $loan['purpose'] ?? null);
    }
    
    // Log collateral data
    if (!empty($collateral)) {
        foreach ($collateral as $idx => $c) {
            $num = $idx + 1;
            $log .= logVariable("\$COL{$num}_PROV", $c['land_province'] ?? null);
            $log .= logVariable("\$COL{$num}_DIST", $c['land_district'] ?? null);
            $log .= logVariable("\$COL{$num}_MUN", $c['land_municipality'] ?? null);
            $log .= logVariable("\$COL{$num}_WARD", $c['land_ward_no'] ?? null);
            $log .= logVariable("\$COL{$num}_SHEET_NO", $c['land_sheet_no'] ?? null);
            $log .= logVariable("\$COL{$num}_KITTA_NO", $c['land_kitta_no'] ?? null);
            $log .= logVariable("\$COL{$num}_AREA", $c['land_area'] ?? null);
        }
    }
    
    $log .= "\n" . str_repeat('-', 40) . "\n";
    
    // Write to file
    file_put_contents($log_file, $log);
}

function logVariable($name, $value) {
    if ($value !== null && $value !== '') {
        return "[MATCH] $name = '" . $value . "'\n";
    } else {
        return "[MISSING] $name - No data found (replaced with space)\n";
    }
}

function calculateAge($dob) {
    if (!$dob) return null;
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    return $today->diff($birthDate)->y;
}

function formatNepaliCurrency($amount) {
    if (!$amount) return null;
    return 'रू ' . number_format($amount, 2);
}
?>
