<?php
/**
 * Preeti Font Form Updater V3
 * Handles class BEFORE name attribute ordering
 */

echo "=== PREETI FONT FORM UPDATER V3 ===\n\n";

$files_to_update = [
    __DIR__ . '/modules/customer/forms/individual_borrower.php',
    __DIR__ . '/modules/customer/forms/corporate_borrower.php',
    __DIR__ . '/modules/customer/forms/guarantor_form.php',
    __DIR__ . '/modules/customer/forms/collateral_form.php',
];

// Correct pattern: class="form-control" name="field"
$replacements = [
    // Full name Nepali
    'class="form-control" name="full_name_np"' => 'class="form-control nepali-input" name="full_name_np"',
    
    // Father/grandfather names  
    'class="form-control" name="father_name"' => 'class="form-control nepali-input" name="father_name"',
    'class="form-control" name="grandfather_name"' => 'class="form-control nepali-input" name="grandfather_name"',
    'class="form-control" name="mother_name"' => 'class="form-control nepali-input" name="mother_name"',
    'class="form-control" name="father_in_law_name"' => 'class="form-control nepali-input" name="father_in_law_name"',
    'class="form-control" name="spouse_name"' => 'class="form-control nepali-input" name="spouse_name"',
    
    // Address fields - Permanent
    'class="form-control" name="perm_province"' => 'class="form-control nepali-input" name="perm_province"',
    'class="form-control" name="perm_district"' => 'class="form-control nepali-input" name="perm_district"',
    'class="form-control" name="perm_municipality_vdc"' => 'class="form-control nepali-input" name="perm_municipality_vdc"',
    'class="form-control" name="perm_town_village"' => 'class="form-control nepali-input" name="perm_town_village"',
    'class="form-control" name="perm_street_name"' => 'class="form-control nepali-input" name="perm_street_name"',
    'class="form-control" name="perm_tole"' => 'class="form-control nepali-input" name="perm_tole"',
    
    // Address fields - Temporary
    'class="form-control" name="temp_province"' => 'class="form-control nepali-input" name="temp_province"',
    'class="form-control" name="temp_district"' => 'class="form-control nepali-input" name="temp_district"',
    'class="form-control" name="temp_municipality_vdc"' => 'class="form-control nepali-input" name="temp_municipality_vdc"',
    'class="form-control" name="temp_town_village"' => 'class="form-control nepali-input" name="temp_town_village"',
    'class="form-control" name="temp_street_name"' => 'class="form-control nepali-input" name="temp_street_name"',
    'class="form-control" name="temp_tole"' => 'class="form-control nepali-input" name="temp_tole"',
    
    // Collateral boundaries
    'class="form-control" name="boundary_north"' => 'class="form-control nepali-input" name="boundary_north"',
    'class="form-control" name="boundary_south"' => 'class="form-control nepali-input" name="boundary_south"',
    'class="form-control" name="boundary_east"' => 'class="form-control nepali-input" name="boundary_east"',
    'class="form-control" name="boundary_west"' => 'class="form-control nepali-input" name="boundary_west"',
    'class="form-control" name="land_boundary_north"' => 'class="form-control nepali-input" name="land_boundary_north"',
    'class="form-control" name="land_boundary_south"' => 'class="form-control nepali-input" name="land_boundary_south"',
    'class="form-control" name="land_boundary_east"' => 'class="form-control nepali-input" name="land_boundary_east"',
    'class="form-control" name="land_boundary_west"' => 'class="form-control nepali-input" name="land_boundary_west"',
    
    // Family members
    'class="form-control" name="family_name[]"' => 'class="form-control nepali-input" name="family_name[]"',
    'class="form-control" name="relation"' => 'class="form-control nepali-input" name="relation"',
    'class="form-control" name="occupation"' => 'class="form-control nepali-input" name="occupation"',
];

$total_updates = 0;
$files_updated = 0;

foreach ($files_to_update as $file) {
    if (!file_exists($file)) {
        echo "⚠️  Skipped: " . basename($file) . " (not found)\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $original_content = $content;
    $file_updates = 0;
    
    foreach ($replacements as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        if ($count > 0) {
            $file_updates += $count;
        }
    }
    
    if ($content !== $original_content) {
        $backup_file = $file . '.backup_' . date('YmdHis');
        file_put_contents($backup_file, $original_content);
        file_put_contents($file, $content);
        
        echo "✅ Updated: " . basename($file) . " ($file_updates changes)\n";
        $files_updated++;
        $total_updates += $file_updates;
    } else {
        echo "ℹ️  No changes: " . basename($file) . "\n";
    }
}

// Address section
$address_file = __DIR__ . '/modules/customer/forms/shared/address_section.php';
if (file_exists($address_file)) {
    $content = file_get_contents($address_file);
    $original_content = $content;
    $file_updates = 0;
    
    foreach ($replacements as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        if ($count > 0) {
            $file_updates += $count;
        }
    }
    
    if ($content !== $original_content) {
        $backup_file = $address_file . '.backup_' . date('YmdHis');
        file_put_contents($backup_file, $original_content);
        file_put_contents($address_file, $content);
        
        echo "✅ Updated: address_section.php ($file_updates changes)\n";
        $files_updated++;
        $total_updates += $file_updates;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: " . (count($files_to_update) + 1) . "\n";
echo "Files updated: $files_updated\n";
echo "Total replacements: $total_updates\n";
echo "\n✅ Done! Backups saved.\n";
?>
