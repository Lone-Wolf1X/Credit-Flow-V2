<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Element\Table;

// 1. Create a dummy template
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
$section->addText("Header");
$section->addText('${html_content}');
$section->addText("Footer");
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$templatePath = 'test_template.docx';
$objWriter->save($templatePath);

echo "Template created at $templatePath\n";

// 2. Prepare HTML
$html = '
<p>This is a paragraph from HTML.</p>
<table border="1" style="width:100%">
    <tr>
        <td style="background-color: #cccccc;"><b>Header 1</b></td>
        <td>Header 2</td>
    </tr>
    <tr>
        <td>Row 1 Col 1</td>
        <td>Row 1 Col 2</td>
    </tr>
</table>
<p>Another paragraph.</p>
';

// 3. Try injection
try {
    echo "Attempting injection...\n";
    $template = new TemplateProcessor($templatePath);
    
    // Check if Html::addHtml equivalent exists for conversion
    if (method_exists('PhpOffice\PhpWord\Shared\Html', 'addHtml')) {
         // This is for Section, not TemplateProcessor.
         // We need parseToElements
    }

    // Try parsing
    // valid method is likely: Html::parseNode or similar, but let's try to find a way to get elements
    // In strict PHPWord, Html::addHtml adds to a container.
    // We might need a temporary container?
    
    // Recent PHPWord allows setComplexBlock with array of elements?
    // Or we must implement a custom parser?
    
    // Let's try the common workaround:
    // Create a temporary section
    $tempWord = new \PhpOffice\PhpWord\PhpWord();
    $tempSection = $tempWord->addSection();
    \PhpOffice\PhpWord\Shared\Html::addHtml($tempSection, $html);
    
    // Get elements from section
    $elements = $tempSection->getElements();
    
    echo "Parsed " . count($elements) . " elements from HTML.\n";
    
    // Inject
    // setComplexBlock expects a block element or array of block elements?
    // It usually expects a single ComplexType (like Table or TextRun). 
    // If we have multiple, we might need to loop placeholders or wrap them?
    // But TemplateProcessor usually only replaces one placeholder with one block.
    // A Container (like Section) isn't a Block.
    // Maybe we replace with a Table?
    
    // Let's try injecting just the Table if found
    $foundTable = false;
    foreach($elements as $el) {
        if ($el instanceof \PhpOffice\PhpWord\Element\Table) {
            echo "Found Table element!\n";
            $template->setComplexBlock('html_content', $el);
            $foundTable = true;
            break; 
            // Note: This only replaces with the FIRST table. 
            // Real solution needs to replace with ALL elements.
            // Reference: TemplateProcessor::cloneBlock or similar might be needed for multiple.
        }
    }
    
    if (!$foundTable && count($elements) > 0) {
        // Just try injecting the first element
         $template->setComplexBlock('html_content', $elements[0]);
    }

    $outputPath = 'test_output.docx';
    $template->saveAs($outputPath);
    echo "Saved to $outputPath\n";
    echo "SUCCESS\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
