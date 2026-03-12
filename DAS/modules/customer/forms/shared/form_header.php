<?php
/**
 * Shared Form Header Component
 * Adapted for Layout System
 */

// Start output buffering to capture form content
ob_start();

// Ensure page title is set
if (!isset($pageTitle)) {
    $pageTitle = 'Form'; 
}
?>

<!-- Custom Form Styles (if any specific ones are needed that aren't in layout) -->
<!-- Nepali Date Picker CSS is likely needed here or via layout hook -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sajanm/nepali-date-picker@latest/dist/nepali.datepicker.v5.0.6.min.css">
<!-- Preeti Font for Nepali Input -->
<link rel="stylesheet" href="../../../assets/css/preeti-font.css">

<div class="container-fluid">
    <div class="row">
        <main class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../customer_profile.php?id=<?php echo $profile_id ?? ''; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Profile
                    </a>
                </div>
            </div>
