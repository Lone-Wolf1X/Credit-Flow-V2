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

    // Function to disable all inputs in a container
    function disableInputs(container) {
        // Disable all standard inputs
        const inputs = container.querySelectorAll('input, select, textarea, button[type="button"]:not(.view-mode-back)');
        inputs.forEach(input => {
            input.disabled = true;
            if (input.tagName !== 'BUTTON') {
                input.style.backgroundColor = '#f8f9fa';
                input.style.cursor = 'not-allowed';
            }
        });

        // Disable contenteditable elements
        const editableElements = container.querySelectorAll('[contenteditable="true"]');
        editableElements.forEach(el => {
            el.contentEditable = 'false';
            el.style.backgroundColor = '#f8f9fa';
            el.style.cursor = 'not-allowed';
        });

        // Disable file upload inputs specifically
        const fileInputs = container.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.disabled = true;
            input.style.display = 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        console.log('View-only mode activated');

        // 1. Disable all form inputs (comprehensive)
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            disableInputs(form);
        });

        // 2. Hide all action buttons except back buttons
        const actionButtons = document.querySelectorAll('button[type="submit"], button.btn-next, button.btn-prev, button.btn-success, button.btn-danger:not(.view-mode-back), a.btn-danger, button.btn-primary:not(.view-mode-back)');
        actionButtons.forEach(btn => {
            if (!btn.classList.contains('view-mode-back')) {
                btn.style.display = 'none';
            }
        });

        // 3. Hide "Add" buttons (for dynamic rows like family members, authorized persons, etc.)
        const addButtons = document.querySelectorAll('button:not(.view-mode-back)');
        addButtons.forEach(btn => {
            const btnText = btn.textContent.toLowerCase();
            if (btnText.includes('add') || btnText.includes('थप') || btn.classList.contains('btn-success')) {
                btn.style.display = 'none';
            }
        });

        // 4. Hide remove/delete buttons in dynamic rows
        const removeButtons = document.querySelectorAll('button.btn-danger:not(.view-mode-back), button[onclick*="remove"], button[onclick*="delete"]');
        removeButtons.forEach(btn => {
            if (!btn.classList.contains('view-mode-back')) {
                btn.style.display = 'none';
            }
        });

        // 5. Replace action buttons with Back button
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

        // 6. Update page header to indicate view-only mode
        const headers = document.querySelectorAll('h2, h5');
        headers.forEach(header => {
            if (header.textContent.includes('Add') || header.textContent.includes('Edit')) {
                const entityName = header.textContent.split(/Add|Edit/)[1].trim();
                header.innerHTML = `<i class="bi bi-eye me-2"></i>View ${entityName} (Read-Only)`;
            }
        });

        // 7. Hide progress bars
        const progressCards = document.querySelectorAll('.progress');
        progressCards.forEach(progress => {
            const card = progress.closest('.card');
            if (card) {
                card.style.display = 'none';
            }
        });

        // 8. Show all form steps at once (for multi-step forms)
        const formSteps = document.querySelectorAll('.form-step');
        formSteps.forEach(step => {
            step.style.display = 'block';
        });

        // 9. Add visual indicator
        const container = document.querySelector('.container-fluid');
        if (container) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-info mb-3';
            alert.innerHTML = '<i class="bi bi-info-circle me-2"></i><strong>View-Only Mode:</strong> This form is in read-only mode. You cannot make changes.';
            container.insertBefore(alert, container.firstChild);
        }

        // 10. Prevent form submission via Enter key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                return false;
            }
        });

        // 11. Disable all onclick handlers on buttons
        const allButtons = document.querySelectorAll('button:not(.view-mode-back)');
        allButtons.forEach(btn => {
            btn.onclick = function (e) {
                e.preventDefault();
                return false;
            };
        });

        // 12. Watch for dynamically added content (like family details rows)
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // Element node
                        // Disable inputs in newly added nodes
                        disableInputs(node);

                        // Hide any new buttons
                        const newButtons = node.querySelectorAll('button:not(.view-mode-back)');
                        newButtons.forEach(btn => {
                            const btnText = btn.textContent.toLowerCase();
                            if (btnText.includes('add') || btnText.includes('थप') ||
                                btnText.includes('remove') || btnText.includes('delete') ||
                                btn.classList.contains('btn-success') || btn.classList.contains('btn-danger')) {
                                btn.style.display = 'none';
                            }
                        });
                    }
                });
            });
        });

        // Observe the entire document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})();
