// EDMS JavaScript Utilities

// Add dynamic document rows
// Add dynamic document rows
function addMoreRows(containerId) {
    const container = document.getElementById(containerId);
    const rows = container.getElementsByClassName('upload-row');

    if (rows.length > 0) {
        // Clone the first row to preserve input type (select vs text)
        const newRow = rows[0].cloneNode(true);

        // Clear values in the cloned row
        const inputs = newRow.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.value = '';
        });

        container.appendChild(newRow);
    }
}

// Remove document row
function removeRow(button) {
    // Only remove if there are more than 1 row to prevent empty container (optional, but good UX)
    // But user asked for delete button for "multiple rows added and some are not use", so allowing full delete is fine, 
    // though usually keeping at least one is safer. For now, simple remove.
    const row = button.closest('.upload-row');
    row.remove();
}

// Get document options based on category
function getDocumentOptions(category) {
    const options = {
        'General': [
            'KYC Documents',
            'Application Form',
            'Photographs',
            'Identity Proof',
            'Address Proof',
            'Income Proof',
            'Bank Statements'
        ],
        'Security': [
            'Property Papers',
            'Title Deed',
            'Hypothecation Deed',
            'Valuation Report',
            'Insurance Documents',
            'NOC from Society',
            'Encumbrance Certificate'
        ],
        'Legal': [
            'Loan Agreement',
            'Mortgage Deed',
            'Promissory Note',
            'Guarantee Documents',
            'Legal Opinion',
            'Sanction Letter',
            'Disbursement Letter'
        ]
    };

    const categoryOptions = options[category] || [];
    return categoryOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('');
}

// Switch CAP ID tab
function switchCapTab(capId) {
    // Hide all CAP content
    document.querySelectorAll('.cap-content').forEach(el => {
        el.style.display = 'none';
    });

    // Remove active class from all tabs
    document.querySelectorAll('.cap-tab').forEach(el => {
        el.classList.remove('active');
    });

    // Show selected CAP content
    const content = document.getElementById('cap-content-' + capId.replace(/[^a-zA-Z0-9]/g, '_'));
    if (content) {
        content.style.display = 'block';
    }

    // Add active class to selected tab
    const tab = document.querySelector(`[data-cap-id="${capId}"]`);
    if (tab) {
        tab.classList.add('active');
    }
}

// Confirm action
function confirmAction(message) {
    return confirm(message);
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.main-content');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// File size validation
function validateFileSize(input, maxSizeMB = 10) {
    if (input.files && input.files[0]) {
        const fileSize = input.files[0].size / 1024 / 1024; // in MB
        if (fileSize > maxSizeMB) {
            alert(`File size exceeds ${maxSizeMB}MB limit. Please choose a smaller file.`);
            input.value = '';
            return false;
        }
    }
    return true;
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function () {
    // Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // File input validation
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function () {
            validateFileSize(this);
        });
    });
});

// Upload document via AJAX
async function uploadDocument(formData, category, capId) {
    try {
        const response = await fetch('ajax/upload_document.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Document uploaded successfully!', 'success');
            // Reload document list
            location.reload();
        } else {
            showAlert(result.message || 'Upload failed', 'danger');
        }
    } catch (error) {
        showAlert('An error occurred during upload', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarClose = document.getElementById('sidebarClose');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
            } else {
                sidebar.classList.toggle('show');
            }
        });
    }

    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('show');
        });
    }
});


// View Document Function
function viewDocument(event, url) {
    // Get file extension
    const extension = url.split('.').pop().toLowerCase();
    const viewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    if (viewableExtensions.includes(extension)) {
        event.preventDefault(); // Prevent default download/navigation
        
        // Try to find the modal
        const modalEl = document.getElementById('previewModal');
        const iframe = document.getElementById('previewFrame');
        
        if (modalEl && iframe) {
            iframe.src = url;
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            // Fallback: Open in new tab (let browser handle it)
            window.open(url, '_blank');
        }
    }
    // If not viewable, let default action happen (download)
}
