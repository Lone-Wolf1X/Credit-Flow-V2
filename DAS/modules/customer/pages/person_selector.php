<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Get parameters
$target_form = $_GET['form'] ?? 'borrower'; // borrower or guarantor
$profile_id = $_GET['profile_id'] ?? '';
$customer_type = $_GET['customer_type'] ?? 'Individual'; // Individual or Corporate

// Page variables for layout
$pageTitle = 'Select Existing Person';
$activeMenu = 'customer_profile';
$userName = $_SESSION['full_name'] ?? 'User';
$assetPath = '../../../asstes'; // Correct path to assets folder
$userAvatar = $assetPath . '/images/img_avatar.png';
$badgeText = $_SESSION['role_name'] ?? 'Maker';

// Start output buffering for main content
ob_start();
?>

<style>
    /* Premium Page Styling */
    .page-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
        margin-bottom: 40px;
        color: white;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .search-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        background: white;
        margin-top: -60px; /* Overlap effect */
        z-index: 10;
        position: relative;
    }

    .form-control-lg {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 15px 20px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .form-control-lg:focus {
        border-color: #764ba2;
        box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
    }

    /* Table Styling */
    .table-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        background: white;
    }

    .table thead th {
        background-color: #2d3748;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px;
        border: none;
    }

    .table tbody tr {
        transition: all 0.2s;
        border-bottom: 1px solid #edf2f7;
    }

    .table tbody tr:hover {
        background-color: #f7fafc;
        transform: scale(1.002);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        z-index: 5;
        position: relative;
        cursor: pointer;
    }

    .table td {
        vertical-align: middle;
        padding: 15px;
        color: #4a5568 !important; /* Ensure high contrast */
        font-size: 0.95rem;
        border-top: none;
    }
    
    .person-name {
        font-weight: 700;
        color: #2d3748 !important;
        font-size: 1.05rem;
    }
    
    .person-name-en {
        color: #718096 !important;
        font-size: 0.85rem;
        font-weight: 500;
        margin-top: 4px;
    }

    .ref-no {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #764ba2;
        background: #f3ebfa;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid rgba(118, 75, 162, 0.1);
    }

    /* Badges */
    .badge-custom-borrower {
        background-color: #e3f2fd;
        color: #1976d2;
        padding: 8px 12px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.75rem;
        border: 1px solid rgba(25, 118, 210, 0.1);
    }

    .badge-custom-guarantor {
        background-color: #e8f5e9;
        color: #2e7d32;
        padding: 8px 12px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.75rem;
        border: 1px solid rgba(46, 125, 50, 0.1);
    }

    .btn-select {
        border-radius: 50px;
        padding: 6px 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        font-size: 0.85rem;
    }
    
    .btn-select:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.2);
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.9);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(2px);
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="h5 fw-bold text-muted">Processing...</p>
    </div>
</div>

<!-- Premium Page Header -->
<div class="page-header-custom">
    <div class="container-fluid px-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2 fw-bold"><i class="fas fa-users-viewfinder me-3"></i>Find Existing Person</h2>
                <p class="mb-0 opacity-75 fs-5">Select a person from the database to reuse their details</p>
            </div>
            <a href="../customer_profile.php?id=<?php echo $profile_id; ?>" class="btn btn-light text-primary fw-bold shadow-lg rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i> Back to Profile
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-5 pb-5">
    
    <!-- Search Section -->
    <div class="card search-card mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-9">
                    <label class="form-label text-muted fw-bold ms-1 text-uppercase small">Search Operator</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 ps-3">
                            <i class="fas fa-search text-secondary fa-lg"></i>
                        </span>
                        <input type="text" id="personSearchInput" class="form-control form-control-lg border-start-0 ps-2" 
                               placeholder="Start typing name, citizenship number, or reference no..." 
                               autocomplete="off" autofocus>
                    </div>
                </div>
                <div class="col-md-3">
                     <label class="form-label text-muted fw-bold ms-1 text-uppercase small">Target Action</label>
                     <div class="d-grid">
                        <div class="btn btn-outline-primary fw-bold py-2 disabled" style="opacity: 1; border-width: 2px;">
                            <small class="d-block text-muted text-uppercase" style="font-size: 0.65rem;">Adding As</small>
                            <?php echo ucfirst($target_form); ?>
                        </div>
                     </div>
                </div>
                <div class="col-12 mt-2">
                    <button class="btn btn-primary fw-bold px-4 rounded-pill d-none" onclick="searchPersonsTable()">
                        Search Now
                    </button>
                    <small class="text-muted ms-2"><i class="fas fa-info-circle me-1"></i> Use search filters inside the table for more precision.</small>
                </div>
            </div>
        </div>
    </div>

<!-- Loading Message -->
<div id="personSearchLoading" class="card shadow-sm" style="display: none;">
    <div class="card-body text-center py-5">
        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
        <h5>Searching...</h5>
    </div>
</div>

<!-- No Results Message -->
<div id="personSearchNoResults" class="card shadow-sm" style="display: none;">
    <div class="card-body text-center py-5">
        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No persons found matching your search</h5>
        <p class="text-muted">Try a different search term</p>
    </div>
</div>

<!-- Results Table -->
    <!-- Results Table -->
    <div id="personTableContainer" class="card table-card" style="display: none;">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-list-ul me-2"></i> Search Results
                <span class="badge bg-primary rounded-pill ms-2" id="totalRecordsBadge">0</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0" id="personTable">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="12%">Ref No.</th>
                            <th width="25%">Full Name / Details</th>
                            <th width="15%">Citizenship No.</th>
                            <th width="18%">Father's Name</th>
                            <th width="10%" class="text-center">Type</th>
                            <th width="15%" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="personTableBody">
                        <!-- Results will be inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted">
                    Showing <strong id="showingFrom">0</strong> to <strong id="showingTo">0</strong> of <strong id="totalRecords">0</strong> records
                </span>
            </div>
            <nav>
                <ul class="pagination mb-0" id="paginationControls">
                    <!-- Pagination buttons will be inserted here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Hidden form data -->
<input type="hidden" id="targetForm" value="<?php echo htmlspecialchars($target_form); ?>">
<input type="hidden" id="profileId" value="<?php echo htmlspecialchars($profile_id); ?>">
<input type="hidden" id="customerType" value="<?php echo htmlspecialchars($customer_type); ?>">

</div>

<!-- Person Selector Logic -->
<script>
    let allPersons = [];
    let currentPage = 1;
    let recordsPerPage = 20;

    // Load all persons on page load
    window.addEventListener('DOMContentLoaded', function() {
        loadAllPersons();
    });

    // Search on Enter key
    document.getElementById('personSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchPersonsTable();
        }
    });

    // Load all persons (no search query)
    function loadAllPersons() {
        // Show loading
        document.getElementById('personSearchLoading').style.display = 'block';
        document.getElementById('personTableContainer').style.display = 'none';
        document.getElementById('personSearchNoResults').style.display = 'none';
        
        // Make API call with wildcard to get all
        fetch(`../../api/person_search_api.php?action=search&query=`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('personSearchLoading').style.display = 'none';
                
                if (data.success) {
                    allPersons = data.persons;
                    currentPage = 1;
                    
                    if (allPersons.length === 0) {
                        document.getElementById('personSearchNoResults').style.display = 'block';
                    } else {
                        document.getElementById('totalRecordsBadge').textContent = allPersons.length;
                        displayPersonTable();
                    }
                } else {
                    alert('Error: ' + (data.error || 'Failed to load persons'));
                }
            })
            .catch(error => {
                document.getElementById('personSearchLoading').style.display = 'none';
                alert('Error connecting to server');
                console.error('Load error:', error);
            });
    }

    function searchPersonsTable() {
        const query = document.getElementById('personSearchInput').value.trim();
        
        // If empty, reload all
        if (query.length === 0) {
            loadAllPersons();
            return;
        }
        
        if (query.length < 2) {
            alert('Please enter at least 2 characters to search');
            return;
        }
        
        // Show loading
        document.getElementById('personSearchLoading').style.display = 'block';
        document.getElementById('personTableContainer').style.display = 'none';
        document.getElementById('personSearchNoResults').style.display = 'none';
        
        // Make API call
        fetch(`../../api/person_search_api.php?action=search&query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('personSearchLoading').style.display = 'none';
                
                if (data.success) {
                    allPersons = data.persons;
                    currentPage = 1;
                    
                    if (allPersons.length === 0) {
                        document.getElementById('personSearchNoResults').style.display = 'block';
                    } else {
                        document.getElementById('totalRecordsBadge').textContent = allPersons.length;
                        displayPersonTable();
                    }
                } else {
                    alert('Error: ' + (data.error || 'Search failed'));
                }
            })
            .catch(error => {
                document.getElementById('personSearchLoading').style.display = 'none';
                alert('Error connecting to server');
                console.error('Search error:', error);
            });
    }

    function displayPersonTable() {
        const tableBody = document.getElementById('personTableBody');
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = Math.min(startIndex + recordsPerPage, allPersons.length);
        const pagePersons = allPersons.slice(startIndex, endIndex);
        
        let html = '';
        pagePersons.forEach((person, index) => {
            const globalIndex = startIndex + index + 1;
            const typeBadgeClass = person.type === 'borrower' ? 'badge-primary' : 'badge-success';
            
            html += `
                <tr onclick="selectPerson(${person.id}, '${person.type}')">
                    <td class="text-center text-muted fw-bold">${globalIndex}</td>
                    <td><span class="ref-no">${person.person_ref_no || 'N/A'}</span></td>
                    <td>
                        <div class="person-name">${person.full_name || ''}</div>
                        <div class="person-name-en">${person.full_name_en || ''}</div>
                    </td>
                    <td class="fw-semibold text-secondary">${person.citizenship_number || '<span class="text-muted">-</span>'}</td>
                    <td class="text-secondary">${person.father_name || '<span class="text-muted">-</span>'}</td>
                    <td class="text-center">
                        <span class="${person.type === 'borrower' ? 'badge-custom-borrower' : 'badge-custom-guarantor'}">
                            ${person.type === 'borrower' ? '<i class="fas fa-user-tie me-1"></i>' : '<i class="fas fa-user-shield me-1"></i>'}
                            ${person.type.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary btn-select" onclick="event.stopPropagation(); selectPerson(${person.id}, '${person.type}')">
                            Select <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
        
        document.getElementById('showingFrom').textContent = startIndex + 1;
        document.getElementById('showingTo').textContent = endIndex;
        document.getElementById('totalRecords').textContent = allPersons.length;
        
        buildPagination();
        document.getElementById('personTableContainer').style.display = 'block';
    }

    function buildPagination() {
        const totalPages = Math.ceil(allPersons.length / recordsPerPage);
        const paginationControls = document.getElementById('paginationControls');
        
        let html = '';
        
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" onclick="changePage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
        
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" onclick="changePage(1)">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" onclick="changePage(${i})">${i}</a>
                </li>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" onclick="changePage(${totalPages})">${totalPages}</a></li>`;
        }
        
        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" onclick="changePage(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationControls.innerHTML = html;
    }

    function changePage(page) {
        const totalPages = Math.ceil(allPersons.length / recordsPerPage);
        if (page < 1 || page > totalPages) return;
        
        currentPage = page;
        displayPersonTable();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function selectPerson(personId, personType) {
        const targetForm = document.getElementById('targetForm').value;
        const profileId = document.getElementById('profileId').value;
        
        // Show loading overlay
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        // Prepare form data for copy_person API
        const formData = new FormData();
        formData.append('action', 'copy_person');
        formData.append('source_id', personId);
        formData.append('source_type', personType);
        formData.append('target_type', targetForm); // borrower or guarantor
        formData.append('profile_id', profileId);
        
        // Call copy_person API
        // Call copy_person API
        fetch(`../../api/person_search_api.php`, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error("Server Error: " + text);
                    }
                });
            })
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    alert(`✅ ${personType.charAt(0).toUpperCase() + personType.slice(1)} successfully added as ${targetForm}!`);
                    
                    // Redirect back to customer profile page
                    window.location.href = `../customer_profile.php?id=${profileId}`;
                } else {
                    alert('Error: ' + (data.error || 'Failed to copy person'));
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                console.error('Copy person error:', error);
                // Extract useful message if possible, limit length
                let msg = error.message;
                if (msg.length > 200) msg = msg.substring(0, 200) + '...';
                alert(msg);
            });
    }
</script>

<?php
$mainContent = ob_get_clean();

// Include the layout
include '../../../Layout/layout_new.php';
?>
