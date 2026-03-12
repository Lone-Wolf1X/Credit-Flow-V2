/**
 * Enhanced Person Selector JavaScript with Table View and Pagination
 * Handles person search, selection, and form auto-fill
 */

let currentTargetForm = null;
let currentPersonType = null;
let allPersons = []; // Store all search results
let currentPage = 1;
let recordsPerPage = 20;

/**
 * Open person selector modal
 */
function openPersonSelector(formId, personType) {
    currentTargetForm = formId;
    currentPersonType = personType;

    // Reset state
    currentPage = 1;
    allPersons = [];

    // Clear previous search
    document.getElementById('personSearchInput').value = '';
    document.getElementById('personTableContainer').style.display = 'none';
    document.getElementById('personSearchInitial').style.display = 'block';
    document.getElementById('personSearchNoResults').style.display = 'none';

    // Open modal
    $('#personSelectorModal').modal('show');

    // Focus on search input
    setTimeout(() => {
        document.getElementById('personSearchInput').focus();
    }, 500);
}

/**
 * Search for persons and display in table
 */
function searchPersonsTable() {
    const query = document.getElementById('personSearchInput').value.trim();

    if (query.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }

    // Show loading
    document.getElementById('personSearchLoading').style.display = 'block';
    document.getElementById('personTableContainer').style.display = 'none';
    document.getElementById('personSearchInitial').style.display = 'none';
    document.getElementById('personSearchNoResults').style.display = 'none';

    // Make API call
    fetch(`../api/person_search_api.php?action=search&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('personSearchLoading').style.display = 'none';

            if (data.success) {
                allPersons = data.persons;
                currentPage = 1;

                if (allPersons.length === 0) {
                    document.getElementById('personSearchNoResults').style.display = 'block';
                } else {
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

/**
 * Display persons in table with pagination
 */
function displayPersonTable() {
    const tableBody = document.getElementById('personTableBody');
    const startIndex = (currentPage - 1) * recordsPerPage;
    const endIndex = Math.min(startIndex + recordsPerPage, allPersons.length);
    const pagePersons = allPersons.slice(startIndex, endIndex);

    // Build table rows
    let html = '';
    pagePersons.forEach((person, index) => {
        const globalIndex = startIndex + index + 1;
        const typeBadgeClass = person.type === 'borrower' ? 'badge-primary' : 'badge-success';

        html += `
            <tr onclick="selectPersonFromTable(${person.id}, '${person.type}')">
                <td class="text-center">${globalIndex}</td>
                <td>
                    <div class="person-name">${person.full_name || ''}</div>
                    <span class="person-name-en">${person.full_name_en || ''}</span>
                </td>
                <td>${person.citizenship_number || 'N/A'}</td>
                <td>${person.date_of_birth || 'N/A'}</td>
                <td>${person.father_name || 'N/A'}</td>
                <td>
                    <span class="badge badge-person-type ${typeBadgeClass}">
                        ${person.type}
                    </span>
                </td>
                <td>
                    <small>${person.customer_id || 'N/A'}</small>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); selectPersonFromTable(${person.id}, '${person.type}')">
                        <i class="fas fa-check"></i> Select
                    </button>
                </td>
            </tr>
        `;
    });

    tableBody.innerHTML = html;

    // Update pagination info
    document.getElementById('showingFrom').textContent = startIndex + 1;
    document.getElementById('showingTo').textContent = endIndex;
    document.getElementById('totalRecords').textContent = allPersons.length;

    // Build pagination controls
    buildPagination();

    // Show table
    document.getElementById('personTableContainer').style.display = 'block';
}

/**
 * Build pagination controls
 */
function buildPagination() {
    const totalPages = Math.ceil(allPersons.length / recordsPerPage);
    const paginationControls = document.getElementById('paginationControls');

    let html = '';

    // Previous button
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;

    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    // Adjust start if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page
    if (startPage > 1) {
        html += `
            <li class="page-item">
                <a class="page-link" onclick="changePage(1)">1</a>
            </li>
        `;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" onclick="changePage(${i})">${i}</a>
            </li>
        `;
    }

    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `
            <li class="page-item">
                <a class="page-link" onclick="changePage(${totalPages})">${totalPages}</a>
            </li>
        `;
    }

    // Next button
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;

    paginationControls.innerHTML = html;
}

/**
 * Change page
 */
function changePage(page) {
    const totalPages = Math.ceil(allPersons.length / recordsPerPage);

    if (page < 1 || page > totalPages) {
        return;
    }

    currentPage = page;
    displayPersonTable();

    // Scroll to top of table
    document.getElementById('personTableContainer').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Select person from table row
 */
function selectPersonFromTable(personId, personType) {
    // Show loading in modal
    document.getElementById('personTableContainer').style.display = 'none';
    document.getElementById('personSearchLoading').style.display = 'block';

    // Fetch complete person details
    fetch(`../api/person_search_api.php?action=get_person&id=${personId}&type=${personType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fillFormWithPersonData(data.person);
                $('#personSelectorModal').modal('hide');

                // Show success message
                showNotification('Person data loaded successfully!', 'success');
            } else {
                document.getElementById('personSearchLoading').style.display = 'none';
                document.getElementById('personTableContainer').style.display = 'block';
                alert('Error loading person details: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            document.getElementById('personSearchLoading').style.display = 'none';
            document.getElementById('personTableContainer').style.display = 'block';
            alert('Error connecting to server');
            console.error('Get person error:', error);
        });
}

/**
 * Fill form with person data
 */
function fillFormWithPersonData(person) {
    const form = document.getElementById(currentTargetForm);
    if (!form) {
        console.error('Target form not found:', currentTargetForm);
        return;
    }

    // Map of database fields to form input names
    const fieldMappings = {
        'full_name': 'full_name',
        'full_name_en': 'full_name_en',
        'date_of_birth': 'date_of_birth',
        'gender': 'gender',
        'relationship_status': 'relationship_status',
        'citizenship_number': 'citizenship_number',
        'id_issue_date': 'id_issue_date',
        'id_issue_district': 'id_issue_district',
        'id_issue_authority': 'id_issue_authority',
        'id_reissue_date': 'id_reissue_date',
        'reissue_count': 'reissue_count',
        'perm_country': 'perm_country',
        'perm_province': 'perm_province',
        'perm_district': 'perm_district',
        'perm_municipality_vdc': 'perm_municipality_vdc',
        'perm_ward_no': 'perm_ward_no',
        'perm_town_village': 'perm_town_village',
        'perm_street_name': 'perm_street_name',
        'temp_country': 'temp_country',
        'temp_province': 'temp_province',
        'temp_district': 'temp_district',
        'temp_municipality_vdc': 'temp_municipality_vdc',
        'temp_ward_no': 'temp_ward_no',
        'temp_town_village': 'temp_town_village',
        'contact_number': 'contact_number',
        'email': 'email'
    };

    // Fill form fields
    Object.keys(fieldMappings).forEach(dbField => {
        const formField = fieldMappings[dbField];
        const input = form.querySelector(`[name="${formField}"]`);

        if (input && person[dbField]) {
            input.value = person[dbField];
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // Fill family details if available
    if (person.family_details && person.family_details.length > 0) {
        person.family_details.forEach(family => {
            const relation = family.relation.toLowerCase();
            const name = family.name;

            const familyFieldMap = {
                'father': 'father_name',
                'बुबा': 'father_name',
                'mother': 'mother_name',
                'आमा': 'mother_name',
                'grandfather': 'grandfather_name',
                'बाजे': 'grandfather_name',
                'grandmother': 'grandmother_name',
                'spouse': 'spouse_name',
                'husband': 'spouse_name',
                'wife': 'spouse_name'
            };

            const formFieldName = familyFieldMap[relation];
            if (formFieldName) {
                const familyInput = form.querySelector(`[name="${formFieldName}"]`);
                if (familyInput) {
                    familyInput.value = name;
                    familyInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    }

    console.log('Form filled with person data:', person);
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-info';
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Allow search on Enter key
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('personSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchPersonsTable();
            }
        });
    }
});
