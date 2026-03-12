<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

$file = __DIR__ . '/templates/Individual/test_md.docx';
echo "Inspecting: $file\n";

$templateProcessor = new TemplateProcessor($file);
$vars = $templateProcessor->getVariables();

echo "Variables found (" . count($vars) . "):\n";
print_r($vars);
