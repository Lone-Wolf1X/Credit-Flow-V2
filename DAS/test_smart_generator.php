<?php
/**
 * Test Smart Document Generator
 * Tests the new smart generation system
 */

session_start();
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/SmartDocumentGenerator.php';
require_once __DIR__ . '/includes/PlaceholderExtractor.php';

$action = $_GET['action'] ?? 'menu';
$profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Document Generator Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .placeholder-match { color: green; font-weight: bold; }
        .placeholder-missing { color: red; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 500px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>🚀 Smart Document Generator Test</h2>
        <p class="text-muted">Testing automatic template selection and placeholder filling</p>
        <hr>

        <?php if ($action === 'menu'): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Test Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="?action=extract_placeholders" class="list-group-item list-group-item-action">
                                    📋 Extract Placeholders from Templates
                                </a>
                                <a href="?action=list_profiles" class="list-group-item list-group-item-action">
                                    👥 Generate Document (Select Profile)
                                </a>
                                <a href="?action=view_logs" class="list-group-item list-group-item-action">
                                    📊 View Generation Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5>System Info</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Templates Found:</strong> 
                                <?php
                                $templates = glob(__DIR__ . '/templates/ptl/*.docx');
                                echo count($templates);
                                ?>
                            </p>
                            <p><strong>Approved Profiles:</strong>
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_profiles WHERE status = 'Approved'");
                                $stmt->execute();
                                $result = $stmt->get_result()->fetch_assoc();
                                echo $result['count'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'extract_placeholders'): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>📋 Placeholder Extraction Results</h5>
                </div>
                <div class="card-body">
                    <a href="?" class="btn btn-secondary mb-3">← Back</a>
                    
                    <?php
                    $template_dir = __DIR__ . '/templates/ptl';
                    $results = PlaceholderExtractor::extractFromDirectory($template_dir);
                    
                    foreach ($results as $template_name => $placeholders) {
                        echo "<h6 class='mt-3'>$template_name</h6>";
                        
                        if (isset($placeholders['error'])) {
                            echo "<div class='alert alert-danger'>{$placeholders['error']}</div>";
                            continue;
                        }
                        
                        $categories = PlaceholderExtractor::categorize($placeholders);
                        $borrower_count = PlaceholderExtractor::getBorrowerCount($placeholders);
                        
                        echo "<p><strong>Total Placeholders:</strong> " . count($placeholders) . "</p>";
                        echo "<p><strong>Required Borrowers:</strong> $borrower_count</p>";
                        
                        echo "<div class='row'>";
                        foreach ($categories as $category => $items) {
                            if (!empty($items)) {
                                echo "<div class='col-md-4'>";
                                echo "<strong>" . ucwords(str_replace('_', ' ', $category)) . ":</strong> " . count($items);
                                echo "<br><small>" . implode(', ', array_slice($items, 0, 5));
                                if (count($items) > 5) echo "...";
                                echo "</small></div>";
                            }
                        }
                        echo "</div>";
                        
                        echo "<details class='mt-2'>";
                        echo "<summary>View All Placeholders</summary>";
                        echo "<pre>" . implode("\n", $placeholders) . "</pre>";
                        echo "</details>";
                    }
                    ?>
                </div>
            </div>

        <?php elseif ($action === 'list_profiles'): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Select Profile for Document Generation</h5>
                </div>
                <div class="card-body">
                    <a href="?" class="btn btn-secondary mb-3">← Back</a>
                    
                    <?php
                    $stmt = $conn->prepare("
                        SELECT cp.*, 
                               (SELECT COUNT(*) FROM borrowers WHERE customer_profile_id = cp.id) as borrower_count
                        FROM customer_profiles cp
                        WHERE cp.status = 'Approved'
                        ORDER BY cp.id DESC
                    ");
                    $stmt->execute();
                    $profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($profiles)) {
                        echo "<div class='alert alert-warning'>No approved profiles found</div>";
                    } else {
                        echo "<div class='list-group'>";
                        foreach ($profiles as $p) {
                            echo "<a href='?action=generate&profile_id={$p['id']}' class='list-group-item list-group-item-action'>";
                            echo "<strong>Profile #{$p['id']}</strong> | ";
                            echo "Customer ID: {$p['customer_id']} | ";
                            echo "Borrowers: {$p['borrower_count']} | ";
                            echo "<span class='badge bg-success'>{$p['status']}</span>";
                            echo "</a>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

        <?php elseif ($action === 'generate' && $profile_id): ?>
            <div class="card">
                <div class="card-header bg-warning">
                    <h5>🚀 Generating Document for Profile #<?php echo $profile_id; ?></h5>
                </div>
                <div class="card-body">
                    <?php
                    $generator = new SmartDocumentGenerator($conn);
                    $result = $generator->generate($profile_id, 'mortgage_deed');
                    
                    if ($result['success']) {
                        echo "<div class='alert alert-success'>";
                        echo "<h5>✓ Success!</h5>";
                        echo "<p><strong>Message:</strong> {$result['message']}</p>";
                        echo "<p><strong>File:</strong> {$result['filename']}</p>";
                        echo "<p><strong>Document ID:</strong> {$result['document_id']}</p>";
                        echo "<p><a href='{$result['relative_path']}' class='btn btn-primary' download>Download Document</a></p>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-danger'>";
                        echo "<h5>✗ Error!</h5>";
                        echo "<p>{$result['error']}</p>";
                        echo "</div>";
                    }
                    ?>
                    
                    <hr>
                    <h6>Debug Log (Last 20 lines):</h6>
                    <pre><?php
                    $log_file = __DIR__ . '/debug_doc_gen.log';
                    if (file_exists($log_file)) {
                        $lines = file($log_file);
                        echo htmlspecialchars(implode('', array_slice($lines, -20)));
                    }
                    ?></pre>
                    
                    <hr>
                    <h6>Generation Log:</h6>
                    <pre><?php
                    $gen_log = __DIR__ . '/generated_documents/last_generation_log.txt';
                    if (file_exists($gen_log)) {
                        $content = file_get_contents($gen_log);
                        // Highlight matches and missing
                        $content = preg_replace('/\[MATCH\]/', '<span class="placeholder-match">[MATCH]</span>', $content);
                        $content = preg_replace('/\[MISSING\]/', '<span class="placeholder-missing">[MISSING]</span>', $content);
                        echo $content;
                    }
                    ?></pre>
                    
                    <div class="mt-3">
                        <a href="?" class="btn btn-secondary">← Back to Menu</a>
                        <a href="?action=list_profiles" class="btn btn-primary">Generate Another</a>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'view_logs'): ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5>📊 Generation Logs</h5>
                </div>
                <div class="card-body">
                    <a href="?" class="btn btn-secondary mb-3">← Back</a>
                    
                    <h6>Last Generation Log:</h6>
                    <pre><?php
                    $gen_log = __DIR__ . '/generated_documents/last_generation_log.txt';
                    if (file_exists($gen_log)) {
                        echo htmlspecialchars(file_get_contents($gen_log));
                    } else {
                        echo "No generation log found";
                    }
                    ?></pre>
                    
                    <hr>
                    <h6>Debug Log (Last 30 lines):</h6>
                    <pre><?php
                    $debug_log = __DIR__ . '/debug_doc_gen.log';
                    if (file_exists($debug_log)) {
                        $lines = file($debug_log);
                        echo htmlspecialchars(implode('', array_slice($lines, -30)));
                    }
                    ?></pre>
                </div>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
