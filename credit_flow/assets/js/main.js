
// Multi-user selection (for Reviewers)
let selectedReviewers = [];

function setupMultiUserSelect(inputId, hiddenId, containerId, role) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const container = document.getElementById(containerId);
    const resultsDiv = document.getElementById(inputId + '_results');

    if (!input || !hidden || !container || !resultsDiv) return;

    // Input event handler
    input.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchStaffMulti(query, role, resultsDiv, input, hidden, container);
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });

    // Initial render if any provided (e.g. edit mode)
    renderSelectedUsers(container, hidden);
}

async function searchStaffMulti(query, role, resultsDiv, input, hidden, container) {
    try {
        const response = await fetch(`../../ajax/search_staff.php?query=${encodeURIComponent(query)}&role=${role}`);
        const data = await response.json();

        if (data.success && data.users.length > 0) {
            displayResultsMulti(data.users, resultsDiv, input, hidden, container);
        } else {
            resultsDiv.innerHTML = '<div class="autocomplete-item text-muted">No users found</div>';
            resultsDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error searching staff:', error);
    }
}

function displayResultsMulti(users, resultsDiv, input, hidden, container) {
    resultsDiv.innerHTML = '';

    users.forEach(user => {
        // Skip if already selected
        if (selectedReviewers.some(r => r.id == user.id)) return;

        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.innerHTML = `
            <strong>${user.full_name}</strong><br>
            <small class="text-muted">Staff ID: ${user.staff_id} | ${user.designation}</small>
        `;

        item.addEventListener('click', function () {
            addSelectedUser(user, container, hidden);
            input.value = ''; // Clear input
            resultsDiv.style.display = 'none';
        });

        resultsDiv.appendChild(item);
    });

    if (resultsDiv.children.length === 0) {
        resultsDiv.innerHTML = '<div class="autocomplete-item text-muted">Already selected</div>';
    }

    resultsDiv.style.display = 'block';
}

function addSelectedUser(user, container, hidden) {
    if (selectedReviewers.some(r => r.id == user.id)) return;

    selectedReviewers.push(user);
    updateHiddenInput(hidden);
    renderSelectedUsers(container, hidden);
}

function removeSelectedUser(userId, container, hidden) {
    selectedReviewers = selectedReviewers.filter(u => u.id != userId);
    updateHiddenInput(hidden);
    renderSelectedUsers(container, hidden);
}

function updateHiddenInput(hidden) {
    const ids = selectedReviewers.map(u => u.id);
    hidden.value = JSON.stringify(ids);
}

function renderSelectedUsers(container, hidden) {
    container.innerHTML = '';

    selectedReviewers.forEach(user => {
        const tag = document.createElement('div');
        tag.className = 'user-tag';
        tag.innerHTML = `
            ${user.full_name} (${user.designation})
            <span class="remove-user" onclick="removeSelectedUser(${user.id}, document.getElementById('${container.id}'), document.getElementById('${hidden.id}'))">&times;</span>
        `;
        container.appendChild(tag);
    });
}

// Autocomplete for staff selection
let searchTimeout;
let selectedReviewerId = null;
let selectedApproverId = null;

function setupAutocomplete(inputId, hiddenId, role) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const resultsDiv = document.getElementById(inputId + '_results');

    if (!input || !hidden || !resultsDiv) return;

    input.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            hidden.value = '';
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchStaff(query, role, resultsDiv, input, hidden);
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
}

async function searchStaff(query, role, resultsDiv, input, hidden) {
    try {
        const response = await fetch(`../../ajax/search_staff.php?query=${encodeURIComponent(query)}&role=${role}`);
        const data = await response.json();

        if (data.success && data.users.length > 0) {
            displayResults(data.users, resultsDiv, input, hidden);
        } else {
            resultsDiv.innerHTML = '<div class="autocomplete-item text-muted">No users found</div>';
            resultsDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error searching staff:', error);
    }
}

function displayResults(users, resultsDiv, input, hidden) {
    resultsDiv.innerHTML = '';

    users.forEach(user => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.innerHTML = `
            <strong>${user.full_name}</strong><br>
            <small class="text-muted">Staff ID: ${user.staff_id} | ${user.designation}</small>
        `;

        item.addEventListener('click', function () {
            input.value = user.display;
            hidden.value = user.id;
            resultsDiv.style.display = 'none';

            // Store selection
            if (hidden.id === 'reviewer_id') {
                selectedReviewerId = user.id;
            } else if (hidden.id === 'approver_id') {
                selectedApproverId = user.id;
            }
        });

        resultsDiv.appendChild(item);
    });

    resultsDiv.style.display = 'block';
}

// Fetch staff details by Staff ID
async function fetchStaffDetails(staffId, targetField) {
    if (!staffId || staffId.length < 2) {
        return;
    }

    try {
        const response = await fetch(`ajax/get_staff.php?staff_id=${staffId}`);
        const data = await response.json();

        if (data.success) {
            document.getElementById(targetField).value = `${data.staff.full_name} - ${data.staff.staff_id}`;
        } else {
            document.getElementById(targetField).value = 'Not found';
        }
    } catch (error) {
        console.error('Error fetching staff details:', error);
    }
}

// Validate reviewer and approver selection
function validateStaffSelection() {
    const reviewerIdsInput = document.getElementById('reviewer_ids');
    const approverId = document.getElementById('approver_id').value;

    let reviewerIds = [];
    try {
        reviewerIds = reviewerIdsInput.value ? JSON.parse(reviewerIdsInput.value) : [];
    } catch (e) {
        console.error('Error parsing reviewer IDs', e);
    }

    if (reviewerIds.length === 0 || !approverId) {
        alert('Please select at least one Reviewer and an Approver');
        return false;
    }

    return true;
}

// Form validation
function validateLoanForm() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        alert('Please fill all required fields');
        return false;
    }

    return validateStaffSelection();
}

// File preview and cumulative upload
const fileAccumulators = {};

function previewFile(input) {
    const fileList = document.getElementById('fileList');
    if (!fileList) return;

    // Ensure input has an ID for tracking
    if (!input.id) {
        console.error('File input must have an ID for cumulative uploads');
        return;
    }

    if (!fileAccumulators[input.id]) {
        fileAccumulators[input.id] = new DataTransfer();
    }

    const dt = fileAccumulators[input.id];

    // Add new files to the accumulator
    if (input.files) {
        Array.from(input.files).forEach(file => {
            // Simple duplicate check by name and size
            const exists = Array.from(dt.files).some(f => f.name === file.name && f.size === file.size);
            if (!exists) {
                dt.items.add(file);
            }
        });
    }

    // Update the input's files property
    input.files = dt.files;

    // Render the updated list
    renderFileList(input.id, fileList);
}

function renderFileList(inputId, container) {
    container.innerHTML = '';
    const dt = fileAccumulators[inputId];

    if (dt.files.length === 0) return;

    Array.from(dt.files).forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'alert alert-info mb-2 d-flex justify-content-between align-items-center p-2';
        fileItem.innerHTML = `
            <span>
                <i class="fas fa-file me-2"></i>${file.name} 
                <small class="text-muted ms-2">(${(file.size / 1024).toFixed(2)} KB)</small>
            </span>
            <button type="button" class="btn-close btn-sm" onclick="removeFile('${inputId}', ${index})" aria-label="Remove"></button>
        `;
        container.appendChild(fileItem);
    });
}

function removeFile(inputId, index) {
    const input = document.getElementById(inputId);
    const fileList = document.getElementById('fileList');
    if (!input || !fileAccumulators[inputId]) return;

    const dt = fileAccumulators[inputId];
    const newDt = new DataTransfer();

    // Rebuild DataTransfer skipping the removed index
    Array.from(dt.files).forEach((file, i) => {
        if (i !== index) newDt.items.add(file);
    });

    // Update accumulator and input
    fileAccumulators[inputId] = newDt;
    input.files = newDt.files;

    renderFileList(inputId, fileList);
}

// Update loan types based on segment
function updateLoanTypes(segment) {
    const loanTypeSelect = document.getElementById('loan_type');
    if (!loanTypeSelect) return;

    const loanTypes = {
        'Retail': ['Personal Term Loan', 'Personal OD Loan', 'Professional Loan', 'Home Loan', 'LAP', 'Vehicle Loan', 'Education Loan'],
        'SME/MSME': ['Business Term Loan', 'Working Capital', 'Mudra Loan', 'CGTMSE'],
        'Micro': ['Group Loan', 'Individual Micro Loan'],
        'Agriculture': ['Crop Loan/KCC', 'Tractor Loan']
    };

    loanTypeSelect.innerHTML = '<option value="">Select Loan Type</option>';

    if (loanTypes[segment]) {
        loanTypes[segment].forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type;
            loanTypeSelect.appendChild(option);
        });
    }
}

// Confirm action
function confirmAction(message) {
    return confirm(message);
}

// Show alert
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.main-content');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Format currency input
function formatCurrency(input) {
    let value = input.value.replace(/[^0-9.]/g, '');
    if (value) {
        input.value = parseFloat(value).toLocaleString('en-IN');
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
