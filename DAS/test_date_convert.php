<?php
require_once 'includes/NepaliDateConverter.php';

$converter = new NepaliDateConverter();

$bsDate = '2082-10-04';
echo "BS Date: $bsDate\n";

$adDate = $converter->convertBsToAd($bsDate);
echo "AD Date: $adDate\n";

if (empty($adDate)) {
    echo "ERROR: Conversion returned empty!\n";
    
    // Try alternative
    $parts = explode('-', $bsDate);
    if (count($parts) == 3) {
        echo "Year: {$parts[0]}, Month: {$parts[1]}, Day: {$parts[2]}\n";
    }
}
