<?php
require_once __DIR__ . '/includes/AdvancedDocumentGenerator.php';

// Mock Connection (Not needed for this specific method logic test, but class requires it)
$conn = new mysqli('localhost', 'root', '', 'das_db');
$generator = new AdvancedDocumentGenerator($conn);

echo "--- Testing DETAILED Nepali Identification Logic ---\n\n";

// Case 1: Male Unmarried, No Re-issue
$case1 = [
    'gender' => 'Male', 
    'marital_status' => 'Unmarried',
    'grandfather_name' => 'Grandpa', 
    'father_name' => 'Father',
    'full_name' => 'Ram Bahadur', 
    'age' => 25,
    'permanent_district' => 'Ktm', 'permanent_municipality' => 'Kmc', 'permanent_ward_no' => '1',
    'current_district' => 'Bkt', 'current_municipality' => 'Bmc', 'current_ward_no' => '2',
    'citizenship_no' => '1001', 'citizenship_issue_date' => '2070-01-01', 
    'citizenship_issue_district' => 'Ktm',
    'citizenship_issue_authority' => 'District Administration Office',
    'citizenship_reissue_status' => 'No'
];
echo "CASE 1: Male Unmarried, DAO, No Re-issue:\n";
echo $generator->generateNepaliIdentificationString($case1) . "\n\n";

// Case 2: Female Married, With Re-issue
$case2 = [
    'gender' => 'Female', 
    'marital_status' => 'Married',
    'grandfather_name' => 'Grandpa', 
    'father_name' => 'Father',
    'father_in_law_name' => 'Sasura',
    'spouse_name' => 'Pati',
    'full_name' => 'Sita Devi', 
    'age' => 24,
    'permanent_district' => 'Lalitpur', 'permanent_municipality' => 'Lmc', 'permanent_ward_no' => '5',
    'current_district' => 'Ktm', 'current_municipality' => 'Kmc', 'current_ward_no' => '10',
    'citizenship_no' => '2002', 'citizenship_issue_date' => '2071-01-01', 
    'citizenship_issue_district' => 'Lalitpur',
    'citizenship_issue_authority' => 'Area Administration Office',
    'citizenship_reissue_status' => 'Yes',
    'citizenship_reissue_date' => '2080-01-01',
    'citizenship_copy_type' => 'Second'
];
echo "CASE 2: Female Married, AAO, With Re-issue:\n";
echo $generator->generateNepaliIdentificationString($case2) . "\n\n";

echo "--- Test Complete ---\n";
