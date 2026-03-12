/**
 * View-Only Mode Handler for Forms
 * Include this script in any form that needs view-only mode support
 * Usage: Add ?view_mode=1 to the URL to enable view-only mode
 */

(function () {
    // Check if view mode is enabled via URL parameter or Global Flag
    const urlParams = new URLSearchParams(window.location.search);
    let viewMode = urlParams.get('view_mode') === '1';

    // Check for server-forced view mode (e.g. for Checker role)
    if (typeof window.FORCE_VIEW_MODE !== 'undefined' && window.FORCE_VIEW_MODE === true) {
        viewMode = true;
    }

    if (!viewMode) return; // Exit if not in view mode

    document.addEventListener('DOMContentLoaded', function () {
        console.log('View-only mode activated');

        // 1. Disable all form inputs
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, select, textarea, button[type="button"]:not(.view-mode-back)');
            inputs.forEach(input => {
                input.disabled = true;
                if (input.tagName !== 'BUTTON') {
                    input.style.backgroundColor = '#f8f9fa';
                    input.style.cursor = 'not-allowed';
                }
            });
        });

        // 2. Hide all action buttons except back buttons
        const actionButtons = document.querySelectorAll('button[type="submit"], button.btn-next, button.btn-prev, button.btn-success, button.btn-danger:not(.view-mode-back), a.btn-danger');
        actionButtons.forEach(btn => {
            if (!btn.classList.contains('view-mode-back')) {
                btn.style.display = 'none';
            }
        });

        // 3. Replace action buttons with Back button
        const buttonContainers = document.querySelectorAll('.mt-4');
        if (buttonContainers.length > 0) {
            const lastContainer = buttonContainers[buttonContainers.length - 1];
            const profileId = urlParams.get('profile_id');
            if (profileId) {
                lastContainer.innerHTML = `
                    <a href="../customer_profile.php?id=${profileId}" class="btn btn-primary btn-lg px-5 view-mode-back">
                        <i class="bi bi-arrow-left me-2"></i> Back to Profile
                    </a>
                `;
            }
        }

        // 4. Update page header to indicate view-only mode
        const headers = document.querySelectorAll('h2');
        headers.forEach(header => {
            if (header.textContent.includes('Add') || header.textContent.includes('Edit')) {
                const entityName = header.textContent.split(/Add|Edit/)[1].trim();
                header.innerHTML = `<i class="bi bi-eye me-2"></i>View ${entityName} (Read-Only)`;
            }
        });

        // 5. Hide progress bars
        const progressCards = document.querySelectorAll('.progress');
        progressCards.forEach(progress => {
            const card = progress.closest('.card');
            if (card) {
                card.style.display = 'none';
            }
        });

        // 6. Show all form steps at once (for multi-step forms)
        const formSteps = document.querySelectorAll('.form-step');
        formSteps.forEach(step => {
            step.style.display = 'block';
        });

        // 7. Add visual indicator
        const container = document.querySelector('.container-fluid');
        if (container) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-info mb-3';
            alert.innerHTML = '<i class="bi bi-info-circle me-2"></i><strong>View-Only Mode:</strong> This form is in read-only mode. You cannot make changes.';
            container.insertBefore(alert, container.firstChild);
        }
    });
})();
