<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DocumentGenerator.php';

use PhpOffice\PhpWord\TemplateProcessor;

echo "Testing Test MD Clone...\n";

// 1. Mock Data for 2 Owners
// Even though variables are CO1_..., we pass them for each clone.
// The system maps CO1_... -> CO1_...#1 (Clone 1)
// The system maps CO1_... -> CO1_...#2 (Clone 2)

$data = [
    'BLOCK_DETAILS' => [
        // Owner 1
        [
            'CO1_NM_EN' => 'Ram Bahadur (Owner 1)',
            'CO1_NM_NP' => 'राम बहादुर',
            'CO1_CIT_NO' => '1001',
            'CO1_FATHER' => 'Hari Bahadur',
            'CO1_GF' => 'Shyam Bahadur',
            'CO1_P_DIST' => 'Kathmandu'
        ],
        // Owner 2
        [
            'CO1_NM_EN' => 'Sita Kumari (Owner 2)',
            'CO1_NM_NP' => 'सीता कुमारी',
            'CO1_CIT_NO' => '2002',
            'CO1_FATHER' => 'Gopal Prasad',
            'CO1_GF' => 'Krishna Prasad',
            'CO1_P_DIST' => 'Lalitpur'
        ],
        // Owner 3
        [
            'CO1_NM_EN' => 'Hari Prasad (Owner 3)',
            'CO1_NM_NP' => 'हरि प्रसाद',
            'CO1_CIT_NO' => '3003',
            'CO1_FATHER' => 'Shiva Prasad',
            'CO1_GF' => 'Ram Prasad',
            'CO1_P_DIST' => 'Bhaktapur'
        ],
        // Owner 4
        [
            'CO1_NM_EN' => 'Gita Devi (Owner 4)',
            'CO1_NM_NP' => 'गीता देवी',
            'CO1_CIT_NO' => '4004',
            'CO1_FATHER' => 'Bishnu Prasad',
            'CO1_GF' => 'Mahesh Prasad',
            'CO1_P_DIST' => 'Kavre'
        ]
    ]
];

// 2. Load Template
$templatePath = __DIR__ . '/templates/Individual/test_md.docx';
echo "Using template: $templatePath\n";

$templateProcessor = new TemplateProcessor($templatePath);
$generator = new DocumentGenerator($conn);

// 3. Process Blocks
echo "Processing blocks...\n";
$generator->processBlocks($templateProcessor, $data);

// 4. Save
$outputPath = __DIR__ . '/generated_documents/test_md_output.docx';
$templateProcessor->saveAs($outputPath);

echo "Generated: $outputPath\n";

// INSPECT OUTPUT
$outProcessor = new TemplateProcessor($outputPath);
echo "Output Variables (Snippet):\n";
print_r(array_slice($outProcessor->getVariables(), 0, 20)); // First 20

