/**
 * Nepali Date Picker (BS only)
 * Simple Nepali date input
 */

// Initialize Nepali date picker
function initNepaliDatePicker(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    // Create wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'nepali-date-picker';

    // Create BS date input
    const bsInput = document.createElement('input');
    bsInput.type = 'text';
    bsInput.className = 'form-control';
    bsInput.name = input.name;
    bsInput.placeholder = 'YYYY-MM-DD (BS)';
    bsInput.value = input.value ? convertADtoBS(input.value) : '';
    bsInput.required = input.required;

    // Add small helper text
    const helpText = document.createElement('small');
    helpText.className = 'text-muted';
    helpText.textContent = 'Nepali Date (BS)';

    wrapper.appendChild(bsInput);
    wrapper.appendChild(helpText);

    // Replace original input
    input.parentNode.insertBefore(wrapper, input);
    input.style.display = 'none';

    // Convert BS to AD on change and update hidden input
    bsInput.addEventListener('change', function () {
        if (this.value) {
            // For now, just store the BS date
            // In production, you'd convert BS to AD here
            input.value = convertBStoAD(this.value);
        }
    });
}

// Simple BS to AD conversion (approximation)
function convertBStoAD(bsDate) {
    if (!bsDate) return '';

    const parts = bsDate.split('-');
    if (parts.length !== 3) return '';

    const bsYear = parseInt(parts[0]);
    const bsMonth = parseInt(parts[1]);
    const bsDay = parseInt(parts[2]);

    // Simple approximation: AD year ≈ BS year - 56/57
    const adYear = bsYear - 56;
    const adMonth = bsMonth - 8;
    const adDay = bsDay - 15;

    // Adjust for underflow
    let finalMonth = adMonth < 1 ? adMonth + 12 : adMonth;
    let finalDay = adDay < 1 ? adDay + 30 : adDay;

    return `${adYear}-${String(finalMonth).padStart(2, '0')}-${String(finalDay).padStart(2, '0')}`;
}

// Simple AD to BS conversion (approximation)
function convertADtoBS(adDate) {
    if (!adDate) return '';

    const date = new Date(adDate);
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    const day = date.getDate();

    // Simple approximation: BS year ≈ AD year + 56/57
    const bsYear = year + 56;
    const bsMonth = month + 8;
    const bsDay = day + 15;

    // Adjust for overflow
    let finalMonth = bsMonth > 12 ? bsMonth - 12 : bsMonth;
    let finalDay = bsDay > 30 ? bsDay - 30 : bsDay;

    return `${bsYear}-${String(finalMonth).padStart(2, '0')}-${String(finalDay).padStart(2, '0')}`;
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    const nepaliDateInputs = document.querySelectorAll('[data-nepali-date="true"]');
    nepaliDateInputs.forEach(input => {
        initNepaliDatePicker(input.id);
    });
});
