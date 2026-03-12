<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $client_id = sanitize($_POST['client_id'] ?? '');
    $cap_id = sanitize($_POST['cap_id'] ?? '');

    // Validate all mandatory fields
    if (empty($customer_name) || empty($client_id) || empty($cap_id)) {
        $error = 'All fields are mandatory: Full Name, Client ID, and CAP ID';
    } else {
        // Validate CAP ID from Credit Flow
        $validation = validateCapId($cap_id);

        if (!$validation['valid']) {
            $error = $validation['message'];
        } else {
            // Check if client_id already exists
            $check = $conn->prepare("SELECT id FROM customers WHERE client_id = ?");
            $check->bind_param("s", $client_id);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $error = 'Client ID already exists. Please use a unique Client ID.';
            } else {
                // Create customer
                $stmt = $conn->prepare("INSERT INTO customers (customer_name, client_id, created_by) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $customer_name, $client_id, $_SESSION['user_id']);

                if ($stmt->execute()) {
                    $customer_id = $conn->insert_id;

                    // Create folder structure
                    $folder_result = createCustomerFolders($customer_name, $client_id, $cap_id);

                    if (!$folder_result['success']) {
                        $error = 'Customer created but folder creation failed: ' . $folder_result['message'];
                    } else {
                        // Link CAP ID
                        $cap_stmt = $conn->prepare("INSERT INTO cap_documents (customer_id, cap_id, folder_path, submitted_by) VALUES (?, ?, ?, ?)");
                        $cap_stmt->bind_param("issi", $customer_id, $cap_id, $folder_result['path'], $_SESSION['user_id']);

                        if ($cap_stmt->execute()) {
                            $cap_doc_id = $conn->insert_id;

                            // Add initial comment
                            $comment_stmt = $conn->prepare("INSERT INTO legal_comments (cap_document_id, user_id, action, comment) VALUES (?, ?, 'Submitted', 'Customer profile created with CAP ID')");
                            $comment_stmt->bind_param("ii", $cap_doc_id, $_SESSION['user_id']);
                            $comment_stmt->execute();

                            // Notify legal team
                            notifyLegalTeam($customer_id, $cap_id, $customer_name);

                            // Add audit log
                            addAuditLog($_SESSION['user_id'], 'Customer Created', 'customer', $customer_id, "Customer: $customer_name, Client ID: $client_id, CAP ID: $cap_id");

                            $success = 'Customer profile created successfully!';
                            header("refresh:2;url=customer_profile.php?id=$customer_id");
                        } else {
                            $error = 'Failed to link CAP ID';
                        }
                    }
                } else {
                    $error = 'Failed to create customer profile';
                }
            }
        }
    }
}

include '../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="form-card">
            <h4 class="mb-4"><i class="fas fa-user-plus"></i> Create Customer Profile</h4>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>All fields are mandatory.</strong> Ensure the CAP ID is
                approved in Credit Flow system.
            </div>

            <form method="POST" action="" id="createCustomerForm">
                <div class="mb-3">
                    <label for="customer_name" class="form-label">
                        Full Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name"
                        placeholder="Enter customer's full name" required>
                    <small class="text-muted">This will be used for folder naming</small>
                </div>

                <div class="mb-3">
                    <label for="client_id" class="form-label">
                        Client ID <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="client_id" name="client_id"
                        placeholder="Enter unique client ID (e.g., CLI-2024-001)" required>
                    <small class="text-muted">Must be unique across the system</small>
                </div>

                <div class="mb-3">
                    <label for="cap_id" class="form-label">
                        CAP ID <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="cap_id" name="cap_id"
                        placeholder="Enter approved CAP ID from Credit Flow" required>
                    <small class="text-muted">Must be an approved CAP ID from Credit Flow system</small>
                </div>

                <div class="alert alert-warning">
                    <strong><i class="fas fa-folder"></i> Folder Structure:</strong><br>
                    System will auto-create: <code>FullName_ClientID_CAPID/</code> with subfolders for documents.
                </div>

                <div class="text-end">
                    <a href="../../dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Customer Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('createCustomerForm').addEventListener('submit', function (e) {
        const customerName = document.getElementById('customer_name').value.trim();
        const clientId = document.getElementById('client_id').value.trim();
        const capId = document.getElementById('cap_id').value.trim();

        if (!customerName || !clientId || !capId) {
            e.preventDefault();
            alert('All fields are mandatory!');
            return false;
        }

        return confirm('Create customer profile with:\n\nName: ' + customerName + '\nClient ID: ' + clientId + '\nCAP ID: ' + capId + '\n\nProceed?');
    });
</script>

<?php include '../../includes/footer.php'; ?>