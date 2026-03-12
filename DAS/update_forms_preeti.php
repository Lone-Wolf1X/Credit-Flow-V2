<?php
/**
 * Automated Form Updater for Preeti Font
 * Adds 'nepali-input' class to all Nepali text input fields
 */

echo "=== PREETI FONT FORM UPDATER ===\n\n";

// Define Nepali field names
$nepali_fields = [
    // Names
    'father_name',
    'grandfather_name',
    'father_in_law_name',
    'spouse_name',
    'mother_name',
    
    // Permanent Address
    'perm_province',
    'perm_district',
    'perm_municipality_vdc',
    'perm_town_village',
    'perm_street_name',
    'perm_tole',
    
    // Temporary Address
    'temp_province',
    'temp_district',
    'temp_municipality_vdc',
    'temp_town_village',
    'temp_street_name',
    'temp_tole',
    
    // Mailing Address
    'mail_province',
    'mail_district',
    'mail_municipality_vdc',
    'mail_town_village',
    'mail_street_name',
    
    // Collateral Boundaries
    'boundary_north',
    'boundary_south',
    'boundary_east',
    'boundary_west',
    'land_boundary_north',
    'land_boundary_south',
    'land_boundary_east',
    'land_boundary_west',
    
    // Family/Authorized Persons
    'relation',
    'occupation',
    'designation'
];

// Find all PHP files in forms directory
$forms_dir = __DIR__ . '/modules/customer/forms';
$files = [];

// Scan directory recursively
function scanDirectory($dir, &$files) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            scanDirectory($path, $files);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
            $files[] = $path;
        }
    }
}

scanDirectory($forms_dir, $files);

echo "Found " . count($files) . " PHP files to process\n\n";

$total_updates = 0;
$files_updated = 0;

// Process each file
foreach ($files as $file) {
    $basename = basename($file);
    $content = file_get_contents($file);
    $original_content = $content;
    $file_updates = 0;
    
    // Pattern 1: name="field" class="form-control"
    // Pattern 2: name="field" class="form-control some-other-class"
    // Pattern 3: name='field' class='form-control'
    
    foreach ($nepali_fields as $field) {
        // Count occurrences first
        $pattern1 = '/name=["\']' . preg_quote($field, '/') . '["\'][^>]*class=["\']([^"\']*form-control[^"\']*)["\'/';
        
        preg_match_all($pattern1, $content, $matches);
        
        foreach ($matches[0] as $index => $full_match) {
            $classes = $matches[1][$index];
            
            // Check if nepali-input already exists
            if (strpos($classes, 'nepali-input') !== false) {
                continue; // Skip, already has the class
            }
            
            // Add nepali-input class
            $new_classes = trim($classes . ' nepali-input');
            $new_match = str_replace('class="' . $classes . '"', 'class="' . $new_classes . '"', $full_match);
            $new_match = str_replace("class='" . $classes . "'", "class='" . $new_classes . "'", $new_match);
            
            $content = str_replace($full_match, $new_match, $content);
            $file_updates++;
        }
    }
    
    // Also handle fields ending with _np (full_name_np, etc.)
    $pattern_np = '/name=["\']([a-z_]+_np)["\'][^>]*class=["\']([^"\']*form-control[^"\']*)["\'/i';
    preg_match_all($pattern_np, $content, $matches_np);
    
    foreach ($matches_np[0] as $index => $full_match) {
        $classes = $matches_np[2][$index];
        
        // Check if nepali-input already exists
        if (strpos($classes, 'nepali-input') !== false) {
            continue;
        }
        
        // Add nepali-input class
        $new_classes = trim($classes . ' nepali-input');
        $new_match = str_replace('class="' . $classes . '"', 'class="' . $new_classes . '"', $full_match);
        $new_match = str_replace("class='" . $classes . "'", "class='" . $new_classes . "'", $new_match);
        
        $content = str_replace($full_match, $new_match, $content);
        $file_updates++;
    }
    
    // If content changed, save it
    if ($content !== $original_content) {
        // Create backup
        $backup_file = $file . '.backup';
        file_put_contents($backup_file, $original_content);
        
        // Save updated content
        file_put_contents($file, $content);
        
        echo "✅ Updated: $basename ($file_updates changes)\n";
        $files_updated++;
        $total_updates += $file_updates;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: " . count($files) . "\n";
echo "Files updated: $files_updated\n";
echo "Total field updates: $total_updates\n";
echo "\n✅ Done! Backups created with .backup extension\n";
?>
