<?php
require_once __DIR__ . '/vendor/autoload.php';

// Database Connection
$das_conn = new mysqli('localhost', 'root', '', 'das_db');
$das_conn->set_charset("utf8mb4");

// Fetch the specific component
$code = '${MD_BRTBL}';
$stmt = $das_conn->prepare("SELECT html_content FROM document_components WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Component $code not found");
}

$html = $row['html_content'];
echo "HTML Length: " . strlen($html) . "\n";
// echo "HTML Preview: " . substr($html, 0, 200) . "\n\n";

// Parse
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
\PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);

$elements = $section->getElements();
echo "Top Level Elements: " . count($elements) . "\n";

function inspectElement($element, $depth = 0) {
    $indent = str_repeat("  ", $depth);
    $type = get_class($element);
    echo "{$indent}[{$depth}] Type: $type";
    
    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        echo " (TextRun)\n";
        foreach ($element->getElements() as $child) {
            inspectElement($child, $depth + 1);
        }
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
        echo " [FOUND TABLE!]\n";
        // Check rows
        $rows = $element->getRows();
        echo "{$indent}  Rows: " . count($rows) . "\n";
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Container || method_exists($element, 'getElements')) {
        echo " (Container)\n";
         foreach ($element->getElements() as $child) {
            inspectElement($child, $depth + 1);
        }
    } else {
        echo "\n";
    }
}

foreach ($elements as $el) {
    inspectElement($el);
}
?>
