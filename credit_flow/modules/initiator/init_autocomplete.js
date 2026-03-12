// Initialize autocomplete for reviewer and approver
document.addEventListener('DOMContentLoaded', function () {
    setupMultiUserSelect('reviewer_input', 'reviewer_ids', 'selected_reviewers', 'reviewer');
    setupAutocomplete('approver_input', 'approver_id', 'approver');
});
