<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

echo "Verifying converted template...\n";

$file = __DIR__ . '/templates/dummy_shorthand_template_converted.docx';

if (!file_exists($file)) {
    die("❌ Error: Converted file not found at $file\n");
}

$phpWord = IOFactory::load($file);
$text = '';
foreach ($phpWord->getSections() as $section) {
    foreach ($section->getElements() as $element) {
        if (method_exists($element, 'getText')) {
            $text .= $element->getText() . "\n";
        }
    }
}

echo "Content Preview:\n----------------\n$text----------------\n";

$checks = [
    '${BR1_NM_NP}' => 'Borrower Name',
    '${BR1_CIT_NO}' => 'Borrower Citizenship',
    '${LN_AMT}' => 'Loan Amount',
    '${COL1_KITTA_NO}' => 'Collateral Kitta'
];

$allPassed = true;
foreach ($checks as $placeholder => $desc) {
    if (strpos($text, $placeholder) !== false) {
        echo "✅ Found $placeholder ($desc)\n";
    } else {
        echo "❌ Missing $placeholder ($desc)\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n🎉 Verification SUCCESS! All shorthands converted correctly.\n";
} else {
    echo "\n⚠️ Verification FAILED! Some shorthands were not converted.\n";
}
