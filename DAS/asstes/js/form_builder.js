/**
 * Form Builder JavaScript
 * Handles dynamic form field management
 */

// Load sections on page load
document.addEventListener('DOMContentLoaded', function () {
    loadSections();
    loadFields();

    // Show/hide options container based on field type
    document.getElementById('fieldType').addEventListener('change', function () {
        const optionsContainer = document.getElementById('optionsContainer');
        if (this.value === 'select' || this.value === 'radio') {
            optionsContainer.style.display = 'block';
        } else {
            optionsContainer.style.display = 'none';
        }
    });
});

// Load sections for dropdown
function loadSections() {
    fetch('../../api/form_config_api.php?action=get_sections')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sectionSelect = document.getElementById('sectionId');
                const filterSection = document.getElementById('filterSection');

                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                filterSection.innerHTML = '<option value="">All Sections</option>';

                data.data.forEach(section => {
                    const option = `<option value="${section.id}">${section.section_label_en} (${section.form_type} - ${section.person_type})</option>`;
                    sectionSelect.innerHTML += option;
                    filterSection.innerHTML += option;
                });
            }
        })
        .catch(error => console.error('Error loading sections:', error));
}

// Load fields
function loadFields() {
    const formType = document.getElementById('filterFormType').value;
    const personType = document.getElementById('filterPersonType').value;
    const sectionId = document.getElementById('filterSection').value;

    let url = '../../api/form_config_api.php?action=get_fields';
    if (formType) url += `&form_type=${formType}`;
    if (personType) url += `&person_type=${personType}`;
    if (sectionId) url += `&section_id=${sectionId}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('fieldsTableBody');

            if (data.success && data.data.length > 0) {
                tbody.innerHTML = '';
                data.data.forEach(field => {
                    const row = `
                        <tr>
                            <td>${field.display_order}</td>
                            <td><code>${field.field_name}</code></td>
                            <td>${field.field_label_en}</td>
                            <td>${field.field_label_np || '-'}</td>
                            <td><span class="badge bg-info">${field.field_type}</span></td>
                            <td>${field.is_required ? '<i class="bi bi-check-circle text-success"></i>' : '-'}</td>
                            <td>${field.section_label_en}</td>
                            <td>${field.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editField(${field.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteField(${field.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No fields found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading fields:', error);
            document.getElementById('fieldsTableBody').innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading fields</td></tr>';
        });
}

// Save field
function saveField() {
    const form = document.getElementById('fieldForm');
    const formData = new FormData(form);
    formData.append('action', 'save_field');

    fetch('../../api/form_config_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Field saved successfully!');
                bootstrap.Modal.getInstance(document.getElementById('addFieldModal')).hide();
                form.reset();
                loadFields();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error saving field:', error);
            alert('Error saving field');
        });
}

// Edit field
function editField(fieldId) {
    fetch(`../../api/form_config_api.php?action=get_field&id=${fieldId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const field = data.data;

                document.getElementById('fieldId').value = field.id;
                document.getElementById('sectionId').value = field.section_id;
                document.getElementById('fieldName').value = field.field_name;
                document.getElementById('labelEn').value = field.field_label_en;
                document.getElementById('labelNp').value = field.field_label_np || '';
                document.getElementById('fieldType').value = field.field_type;
                document.getElementById('columnWidth').value = field.column_width;
                document.getElementById('displayOrder').value = field.display_order;
                document.getElementById('isRequired').checked = field.is_required;

                if (field.field_options) {
                    document.getElementById('fieldOptions').value = JSON.stringify(field.field_options, null, 2);
                }

                // Show modal
                new bootstrap.Modal(document.getElementById('addFieldModal')).show();
            }
        })
        .catch(error => console.error('Error loading field:', error));
}

// Delete field
function deleteField(fieldId) {
    if (!confirm('Are you sure you want to delete this field?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_field');
    formData.append('field_id', fieldId);

    fetch('../../api/form_config_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Field deleted successfully!');
                loadFields();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting field:', error);
            alert('Error deleting field');
        });
}
