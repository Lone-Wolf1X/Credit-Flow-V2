# Document Generation Issue - Analysis

## Problem

Generated documents won't open in MS Word.

## Root Cause

**PHPWord's `setValue()` only replaces placeholders that exist in the template.**

If you try to set a placeholder that doesn't exist in the template, it's silently ignored. However, if the template has placeholders that are NOT set, they remain as `${...}` in the document, which can cause corruption.

## What's Happening

1. Template has 43 placeholders
2. PlaceholderMapper creates values for all 43
3. **BUT**: Some values might be empty or NULL from database
4. PHPWord leaves empty placeholders unreplaced
5. Document has leftover `${...}` → **Corruption**

## Solution

**Set ALL placeholders to empty string if value is missing:**

```php
// In DocumentGenerator.php
foreach ($placeholders as $key => $value) {
    // Ensure value is never null or empty
    $safeValue = ($value === null || $value === '') ? ' ' : $value;
    $template->setValue($key, $safeValue);
}

// Also set any template variables that weren't in our mapping
$templateVars = $template->getVariables();
foreach ($templateVars as $var) {
    if (!isset($placeholders[$var])) {
        $template->setValue($var, ' '); // Set to space, not empty
    }
}
```

## Test Results

**Simple test with hardcoded values:** ✅ Works  
**Full test with database:** ❌ Fails (some values empty)

## Fix Required

Update `DocumentGenerator.php` to:
1. Replace ALL template placeholders
2. Use space (' ') instead of empty string for missing values
3. Ensure no `${...}` remains in final document
