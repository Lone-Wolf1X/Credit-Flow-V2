<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);
$application = getApplication($id);

if (!$application) {
    die('Application not found');
}

// Access control: Allow if initiator, reviewer, approver, or admin
$is_involved = false;
if ($_SESSION['role'] === 'Admin') {
    $is_involved = true;
} elseif ($application['initiator_id'] == $_SESSION['user_id'] || $application['approver_id'] == $_SESSION['user_id']) {
    $is_involved = true;
} else {
    // Check reviewers
    $stmt = $conn->prepare("SELECT 1 FROM application_reviewers WHERE application_id = ? AND reviewer_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $is_involved = true;
    }
}

if (!$is_involved) {
    die('Unauthorized access');
}

$comments = getApplicationComments($id);
$files = getApplicationFiles($id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Log - <?php echo htmlspecialchars($application['cap_id']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a4f8b;
            --secondary: #6c757d;
            --border: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: #f5f5f5;
            margin: 0;
            padding: 40px;
            font-size: 14px;
        }

        .paper {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 50px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .paper {
                box-shadow: none;
                padding: 30px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-inside: avoid;
            }
        }

        /* Header */
        .doc-header {
            border-bottom: 3px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .bank-brand h1 {
            color: var(--primary);
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .bank-brand p {
            margin: 5px 0 0;
            color: var(--secondary);
            font-size: 12px;
        }

        .doc-meta {
            text-align: right;
        }

        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .doc-ref {
            font-family: 'Consolas', monospace;
            color: #555;
            background: #eee;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Sections */
        .section {
            margin-bottom: 35px;
        }

        .section-header {
            background: #f8f9fa;
            border-left: 4px solid var(--primary);
            padding: 8px 15px;
            margin-bottom: 15px;
            font-weight: bold;
            color: #444;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        th {
            color: var(--secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            width: 25%;
            background: #fff;
        }

        td {
            color: #222;
        }

        /* Timeline (Audit Table) */
        .audit-table th {
            background: #f1f3f5;
            color: #495057;
        }
        
        .audit-table tr:nth-child(even) {
            background: #fcfcfc;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success { background: #d1e7dd; color: #0f5132; }
        .badge-danger { background: #f8d7da; color: #842029; }
        .badge-warning { background: #fff3cd; color: #664d03; }
        .badge-info { background: #cff4fc; color: #055160; }
        .badge-secondary { background: #e2e3e5; color: #41464b; }

        .comment-box {
            background: #fffff0;
            border: 1px solid #e0e0e0;
            padding: 8px;
            margin-top: 5px;
            font-style: italic;
            color: #555;
            border-radius: 4px;
        }

        /* Footer */
        .doc-footer {
            margin-top: 50px;
            border-top: 1px solid var(--border);
            padding-top: 15px;
            text-align: center;
            font-size: 11px;
            color: #999;
        }

        /* Controls */
        .controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-print {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            background: #154378;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="controls no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <div class="paper">
        <!-- Header -->
        <header class="doc-header">
            <div class="bank-brand">
                <h1><?php echo defined('APP_NAME') ? APP_NAME : 'Bank System'; ?></h1>
                <p>Credit Flow Management System</p>
            </div>
            <div class="doc-meta">
                <div class="doc-title">Application Audit Log</div>
                <div class="doc-ref">REF: <?php echo htmlspecialchars($application['cap_id']); ?></div>
            </div>
        </header>

        <!-- Application Details -->
        <section class="section">
            <div class="section-header">Executive Summary</div>
            <table>
                <tr>
                    <th>Applicant Name</th>
                    <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                    <th>Application Date</th>
                    <td><?php echo date('d M Y, h:i A', strtotime($application['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Loan Type</th>
                    <td><?php echo htmlspecialchars($application['loan_type']); ?></td>
                    <th>Current Status</th>
                    <td>
                        <span class="badge badge-info"><?php echo $application['status']; ?></span>
                    </td>
                </tr>
                <tr>
                    <th>Proposed Amount</th>
                    <td colspan="3" style="font-weight: bold; font-size: 1.1em;">
                        <?php echo formatCurrency($application['proposed_limit']); ?>
                    </td>
                </tr>
            </table>
        </section>

        <!-- Workflow Participants -->
        <section class="section">
            <div class="section-header">Workflow Authorization</div>
            <table>
                <tr>
                    <th>Initiated By</th>
                    <td><?php echo htmlspecialchars($application['initiator_name']); ?></td>
                    <td><small class="text-secondary">Staff ID: <?php echo htmlspecialchars($application['initiator_staff_id']); ?></small></td>
                </tr>
                <?php if ($application['reviewer_name']): ?>
                <tr>
                    <th>Reviewer</th>
                    <td><?php echo htmlspecialchars($application['reviewer_name']); ?></td>
                    <td><small class="text-secondary">Staff ID: <?php echo htmlspecialchars($application['reviewer_staff_id']); ?></small></td>
                </tr>
                <?php endif; ?>
                <?php if ($application['approver_name']): ?>
                <tr>
                    <th>Final Authority</th>
                    <td><?php echo htmlspecialchars($application['approver_name']); ?></td>
                    <td><small class="text-secondary">Staff ID: <?php echo htmlspecialchars($application['approver_staff_id']); ?></small></td>
                </tr>
                <?php endif; ?>
            </table>
        </section>

        <!-- Audit Trail -->
        <section class="section">
            <div class="section-header">Processing History & Comments</div>
            <table class="audit-table">
                <thead>
                    <tr>
                        <th style="width: 20%">Date & Time</th>
                        <th style="width: 25%">User / Role</th>
                        <th style="width: 15%">Action</th>
                        <th style="width: 40%">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comments)): ?>
                        <tr><td colspan="4" style="text-align: center; color: #999;">No processing history available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($comments as $log): 
                            $badge_class = 'badge-secondary';
                            $act = strtolower($log['action']);
                            if(strpos($act, 'approv') !== false) $badge_class = 'badge-success';
                            if(strpos($act, 'return') !== false) $badge_class = 'badge-danger';
                            if(strpos($act, 'review') !== false) $badge_class = 'badge-warning';
                            if(strpos($act, 'submit') !== false) $badge_class = 'badge-info';
                        ?>
                        <tr class="page-break">
                            <td><?php echo date('d-M-Y H:i', strtotime($log['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                <small style="color: #777"><?php echo htmlspecialchars($log['designation']); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                            </td>
                            <td>
                                <?php if ($log['comment']): ?>
                                    <div class="comment-box">
                                        &ldquo;<?php echo nl2br(htmlspecialchars($log['comment'])); ?>&rdquo;
                                    </div>
                                <?php else: ?>
                                    <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Attachments -->
        <section class="section">
            <div class="section-header">Attached Documentation</div>
             <?php if (empty($files)): ?>
                <div style="padding: 10px; color: #777; font-style: italic;">No documents attached to this application.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Offered By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f['original_filename']); ?></td>
                            <td><?php echo htmlspecialchars($f['uploaded_by_name']); ?></td>
                            <td><?php echo date('d-M-Y', strtotime($f['uploaded_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Footer -->
        <footer class="doc-footer">
            <p><strong>CONFIDENTIAL</strong> - This document contains sensitive financial information. Unauthorized distribution is prohibited.</p>
            <p>Generated by: <?php echo htmlspecialchars($_SESSION['full_name']); ?> on <?php echo date('d M Y, h:i A'); ?></p>
        </footer>
    </div>

</body>
</html>