<!-- Search Borrower Modal -->
<div class="modal fade" id="searchBorrowerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-search me-2"></i>Search Existing Borrower
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search by Name, Citizenship, or PAN</label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="borrowerSearchInput"
                           placeholder="Type at least 2 characters..."
                           onkeyup="searchBorrowers(this.value)">
                </div>
                
                <div id="borrowerSearchResults">
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-search"></i>
                        Start typing to search...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Guarantor Modal -->
<div class="modal fade" id="searchGuarantorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-search me-2"></i>Search Existing Guarantor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search by Name, Citizenship, or PAN</label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="guarantorSearchInput"
                           placeholder="Type at least 2 characters..."
                           onkeyup="searchGuarantors(this.value)">
                </div>
                
                <div id="guarantorSearchResults">
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-search"></i>
                        Start typing to search...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout;

function searchBorrowers(term) {
    clearTimeout(searchTimeout);
    
    if (term.length < 2) {
        document.getElementById('borrowerSearchResults').innerHTML = 
            '<p class="text-muted text-center py-4"><i class="bi bi-search"></i> Start typing to search...</p>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch('../api/search_borrower_api.php?search=' + encodeURIComponent(term))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    displayBorrowerResults(data.data);
                } else {
                    showError('borrowerSearchResults', data.error);
                }
            });
    }, 300);
}

function displayBorrowerResults(results) {
    const container = document.getElementById('borrowerSearchResults');
    
    if (results.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No matching borrowers found</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    results.forEach(borrower => {
        html += `
            <div class="list-group-item list-group-item-action" style="cursor:pointer" 
                 onclick="selectBorrower(${borrower.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${escapeHtml(borrower.full_name)}</h6>
                        <p class="mb-1 small text-muted">
                            ${borrower.citizenship_number ? 'Citizenship: ' + borrower.citizenship_number : ''}
                            ${borrower.pan_number ? ' | PAN: ' + borrower.pan_number : ''}
                        </p>
                        <small class="text-muted">
                            ${borrower.perm_district ? borrower.perm_district + ', ' : ''}
                            ${borrower.perm_province || ''}
                        </small>
                    </div>
                    <span class="badge bg-primary">${borrower.borrower_type}</span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function selectBorrower(masterId) {
    fetch('../api/get_master_borrower.php?id=' + masterId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fillBorrowerForm(data.data);
                bootstrap.Modal.getInstance(document.getElementById('searchBorrowerModal')).hide();
                alert('Borrower details loaded successfully!');
            }
        });
}

function fillBorrowerForm(borrower) {
    // Auto-fill form fields
    const fields = {
        'full_name': borrower.full_name,
        'borrower_type': borrower.borrower_type,
        'citizenship_number': borrower.citizenship_number,
        'father_name': borrower.father_name,
        'date_of_birth': borrower.date_of_birth,
        'gender': borrower.gender,
        'perm_province': borrower.perm_province,
        'perm_district': borrower.perm_district,
        'perm_municipality_vdc': borrower.perm_municipality_vdc,
        'perm_ward_no': borrower.perm_ward_no,
        'temp_province': borrower.temp_province,
        'temp_district': borrower.temp_district
    };
    
    Object.keys(fields).forEach(key => {
        const element = document.getElementById(key);
        if (element && fields[key]) {
            element.value = fields[key];
        }
    });
    
    // Store master ID for linking
    const masterIdField = document.getElementById('master_borrower_id');
    if (masterIdField) {
        masterIdField.value = borrower.id;
    } else {
        // Create hidden field if doesn't exist
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'master_borrower_id';
        hidden.name = 'master_borrower_id';
        hidden.value = borrower.id;
        document.querySelector('form').appendChild(hidden);
    }
}

// Similar functions for guarantor
function searchGuarantors(term) {
    clearTimeout(searchTimeout);
    
    if (term.length < 2) {
        document.getElementById('guarantorSearchResults').innerHTML = 
            '<p class="text-muted text-center py-4"><i class="bi bi-search"></i> Start typing to search...</p>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch('../api/search_guarantor_api.php?search=' + encodeURIComponent(term))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    displayGuarantorResults(data.data);
                }
            });
    }, 300);
}

function displayGuarantorResults(results) {
    const container = document.getElementById('guarantorSearchResults');
    
    if (results.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No matching guarantors found</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    results.forEach(guarantor => {
        html += `
            <div class="list-group-item list-group-item-action" style="cursor:pointer" 
                 onclick="selectGuarantor(${guarantor.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${escapeHtml(guarantor.full_name)}</h6>
                        <p class="mb-1 small text-muted">
                            ${guarantor.citizenship_number ? 'Citizenship: ' + guarantor.citizenship_number : ''}
                        </p>
                    </div>
                    <span class="badge bg-success">${guarantor.guarantor_type}</span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function selectGuarantor(masterId) {
    fetch('../api/get_master_guarantor.php?id=' + masterId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fillGuarantorForm(data.data);
                bootstrap.Modal.getInstance(document.getElementById('searchGuarantorModal')).hide();
                alert('Guarantor details loaded successfully!');
            }
        });
}

function fillGuarantorForm(guarantor) {
    // Similar to fillBorrowerForm
    const fields = {
        'guarantor_name': guarantor.full_name,
        'guarantor_type': guarantor.guarantor_type,
        'guarantor_citizenship': guarantor.citizenship_number,
        'guarantor_father_name': guarantor.father_name,
        // Add all other fields
    };
    
    Object.keys(fields).forEach(key => {
        const element = document.getElementById(key);
        if (element && fields[key]) {
            element.value = fields[key];
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(containerId, message) {
    document.getElementById(containerId).innerHTML = 
        `<div class="alert alert-danger">${escapeHtml(message)}</div>`;
}
</script>
