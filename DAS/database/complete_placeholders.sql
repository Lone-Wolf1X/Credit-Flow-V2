-- =====================================================
-- COMPLETE PLACEHOLDER LIBRARY FOR DAS
-- Format: ${PLACEHOLDER_KEY} (PHPWord TemplateProcessor)
-- =====================================================

-- Clear existing placeholders
TRUNCATE TABLE template_placeholders;

-- =====================================================
-- BORROWER PLACEHOLDERS
-- =====================================================
INSERT INTO template_placeholders (placeholder_key, placeholder_name, category, description, example_value, data_type, is_system) VALUES
-- Basic Info
('BORROWER_NAME', 'Borrower Full Name', 'Borrower', 'Full name of main borrower', 'Rajesh Kumar Sharma', 'text', 1),
('BORROWER_FIRST_NAME', 'Borrower First Name', 'Borrower', 'First name only', 'Rajesh', 'text', 1),
('BORROWER_LAST_NAME', 'Borrower Last Name', 'Borrower', 'Last name only', 'Sharma', 'text', 1),
('BORROWER_GENDER', 'Borrower Gender', 'Borrower', 'Gender', 'Male', 'text', 1),
('BORROWER_DOB', 'Borrower Date of Birth', 'Borrower', 'Date of birth', '1990-01-15', 'date', 1),
('BORROWER_AGE', 'Borrower Age', 'Borrower', 'Age in years', '33', 'number', 1),
('BORROWER_MARITAL_STATUS', 'Borrower Marital Status', 'Borrower', 'Marital status', 'Married', 'text', 1),

-- ID Documents
('BORROWER_CIT_NUMBER', 'Borrower Citizenship Number', 'Borrower', 'Citizenship number', '12345-6789-0123', 'text', 1),
('BORROWER_CIT_ISSUE_DATE', 'Citizenship Issue Date', 'Borrower', 'Issue date', '2010-05-20', 'date', 1),
('BORROWER_CIT_ISSUE_DISTRICT', 'Citizenship Issue District', 'Borrower', 'Issue district', 'Kathmandu', 'text', 1),
('BORROWER_PAN', 'Borrower PAN Number', 'Borrower', 'PAN number', '123456789', 'text', 1),

-- Family Details
('BORROWER_FATHER_NAME', 'Father Name', 'Borrower', 'Father''s full name', 'Ram Kumar Sharma', 'text', 1),
('BORROWER_MOTHER_NAME', 'Mother Name', 'Borrower', 'Mother''s full name', 'Sita Devi Sharma', 'text', 1),
('BORROWER_GRANDFATHER_NAME', 'Grandfather Name', 'Borrower', 'Grandfather''s name', 'Hari Prasad Sharma', 'text', 1),
('BORROWER_SPOUSE_NAME', 'Spouse Name', 'Borrower', 'Spouse''s name', 'Sunita Sharma', 'text', 1),

-- Contact
('BORROWER_MOBILE', 'Borrower Mobile', 'Borrower', 'Mobile number', '9841234567', 'text', 1),
('BORROWER_EMAIL', 'Borrower Email', 'Borrower', 'Email address', 'rajesh@example.com', 'text', 1),
('BORROWER_PHONE', 'Borrower Phone', 'Borrower', 'Landline number', '01-4567890', 'text', 1),

-- Permanent Address
('BORROWER_PERM_PROVINCE', 'Permanent Province', 'Borrower', 'Province', 'Bagmati', 'text', 1),
('BORROWER_PERM_DISTRICT', 'Permanent District', 'Borrower', 'District', 'Kathmandu', 'text', 1),
('BORROWER_PERM_MUNICIPALITY', 'Permanent Municipality', 'Borrower', 'Municipality/VDC', 'Kathmandu Metropolitan', 'text', 1),
('BORROWER_PERM_WARD', 'Permanent Ward', 'Borrower', 'Ward number', '10', 'number', 1),
('BORROWER_PERM_TOLE', 'Permanent Tole/Village', 'Borrower', 'Tole/Village', 'Baneshwor', 'text', 1),
('BORROWER_PERM_FULL_ADDRESS', 'Permanent Full Address', 'Borrower', 'Complete address', 'Baneshwor, Ward 10, Kathmandu', 'text', 1),

-- Temporary Address
('BORROWER_TEMP_PROVINCE', 'Temporary Province', 'Borrower', 'Province', 'Bagmati', 'text', 1),
('BORROWER_TEMP_DISTRICT', 'Temporary District', 'Borrower', 'District', 'Kathmandu', 'text', 1),
('BORROWER_TEMP_MUNICIPALITY', 'Temporary Municipality', 'Borrower', 'Municipality/VDC', 'Kathmandu Metropolitan', 'text', 1),
('BORROWER_TEMP_WARD', 'Temporary Ward', 'Borrower', 'Ward number', '10', 'number', 1),
('BORROWER_TEMP_TOLE', 'Temporary Tole/Village', 'Borrower', 'Tole/Village', 'Baneshwor', 'text', 1),
('BORROWER_TEMP_FULL_ADDRESS', 'Temporary Full Address', 'Borrower', 'Complete address', 'Baneshwor, Ward 10, Kathmandu', 'text', 1),

-- Occupation
('BORROWER_OCCUPATION', 'Borrower Occupation', 'Borrower', 'Occupation', 'Business', 'text', 1),
('BORROWER_EMPLOYER', 'Employer Name', 'Borrower', 'Employer/Business name', 'ABC Pvt. Ltd.', 'text', 1),
('BORROWER_MONTHLY_INCOME', 'Monthly Income', 'Borrower', 'Monthly income', '50000', 'currency', 1),
('BORROWER_ANNUAL_INCOME', 'Annual Income', 'Borrower', 'Annual income', '600000', 'currency', 1),

-- =====================================================
-- GUARANTOR PLACEHOLDERS
-- =====================================================
('GUARANTOR_NAME', 'Guarantor Full Name', 'Guarantor', 'Full name of guarantor', 'Mohan Bahadur Thapa', 'text', 1),
('GUARANTOR_GENDER', 'Guarantor Gender', 'Guarantor', 'Gender', 'Male', 'text', 1),
('GUARANTOR_DOB', 'Guarantor Date of Birth', 'Guarantor', 'Date of birth', '1985-03-20', 'date', 1),
('GUARANTOR_CIT_NUMBER', 'Guarantor Citizenship', 'Guarantor', 'Citizenship number', '98765-4321-0987', 'text', 1),
('GUARANTOR_FATHER_NAME', 'Guarantor Father Name', 'Guarantor', 'Father''s name', 'Bir Bahadur Thapa', 'text', 1),
('GUARANTOR_MOBILE', 'Guarantor Mobile', 'Guarantor', 'Mobile number', '9851234567', 'text', 1),
('GUARANTOR_PERM_FULL_ADDRESS', 'Guarantor Permanent Address', 'Guarantor', 'Complete address', 'Lalitpur, Ward 5', 'text', 1),
('GUARANTOR_OCCUPATION', 'Guarantor Occupation', 'Guarantor', 'Occupation', 'Government Service', 'text', 1),
('GUARANTOR_MONTHLY_INCOME', 'Guarantor Monthly Income', 'Guarantor', 'Monthly income', '45000', 'currency', 1),
('GUARANTOR_RELATION_TO_BORROWER', 'Relation to Borrower', 'Guarantor', 'Relationship', 'Friend', 'text', 1),

-- =====================================================
-- COLLATERAL PLACEHOLDERS
-- =====================================================
-- Land Collateral
('COLLATERAL_TYPE', 'Collateral Type', 'Collateral', 'Type of collateral', 'Land', 'text', 1),
('COLLATERAL_OWNER_NAME', 'Collateral Owner Name', 'Collateral', 'Owner name', 'Rajesh Kumar Sharma', 'text', 1),
('COLLATERAL_OWNER_GENDER', 'Collateral Owner Gender', 'Collateral', 'Owner gender', 'Male', 'text', 1),
('COLLATERAL_OWNER_TYPE', 'Owner Type', 'Collateral', 'Borrower/Guarantor/Third Party', 'Borrower', 'text', 1),

('LAND_PROVINCE', 'Land Province', 'Collateral', 'Province', 'Bagmati', 'text', 1),
('LAND_DISTRICT', 'Land District', 'Collateral', 'District', 'Kathmandu', 'text', 1),
('LAND_MUNICIPALITY', 'Land Municipality', 'Collateral', 'Municipality/VDC', 'Kathmandu Metropolitan', 'text', 1),
('LAND_WARD', 'Land Ward', 'Collateral', 'Ward number', '15', 'number', 1),
('LAND_SHEET_NO', 'Land Sheet Number', 'Collateral', 'Sheet number', '123', 'text', 1),
('LAND_KITTA_NO', 'Land Kitta Number', 'Collateral', 'Kitta number', '456', 'text', 1),
('LAND_AREA', 'Land Area', 'Collateral', 'Area in sq ft/aana/ropani', '5-0-0-0 (Ropani-Aana-Paisa-Daam)', 'text', 1),
('LAND_MALPOT_OFFICE', 'Malpot Office', 'Collateral', 'Land revenue office', 'Kathmandu Malpot', 'text', 1),
('LAND_ESTIMATED_VALUE', 'Land Estimated Value', 'Collateral', 'Market value', '5000000', 'currency', 1),

-- Vehicle Collateral
('VEHICLE_TYPE', 'Vehicle Type', 'Collateral', 'Type of vehicle', 'Car', 'text', 1),
('VEHICLE_MODEL', 'Vehicle Model', 'Collateral', 'Model name', 'Toyota Corolla', 'text', 1),
('VEHICLE_NUMBER', 'Vehicle Number', 'Collateral', 'Registration number', 'BA 1 PA 1234', 'text', 1),
('VEHICLE_ENGINE_NO', 'Engine Number', 'Collateral', 'Engine number', 'ABC123456', 'text', 1),
('VEHICLE_CHASSIS_NO', 'Chassis Number', 'Collateral', 'Chassis number', 'XYZ789012', 'text', 1),
('VEHICLE_YEAR', 'Manufacturing Year', 'Collateral', 'Year', '2020', 'number', 1),
('VEHICLE_COLOR', 'Vehicle Color', 'Collateral', 'Color', 'White', 'text', 1),
('VEHICLE_ESTIMATED_VALUE', 'Vehicle Estimated Value', 'Collateral', 'Market value', '2500000', 'currency', 1),

-- =====================================================
-- LOAN PLACEHOLDERS
-- =====================================================
('LOAN_AMOUNT', 'Loan Amount', 'Loan', 'Sanctioned amount', '500000', 'currency', 1),
('LOAN_AMOUNT_WORDS', 'Loan Amount in Words', 'Loan', 'Amount in words', 'Five Lakh Only', 'text', 1),
('LOAN_TYPE', 'Loan Type', 'Loan', 'Type of loan', 'Personal Term Loan', 'text', 1),
('LOAN_SCHEME', 'Loan Scheme', 'Loan', 'Scheme name', 'Regular Scheme', 'text', 1),
('LOAN_PURPOSE', 'Loan Purpose', 'Loan', 'Purpose', 'Business Expansion', 'text', 1),

-- Interest & Tenure
('INTEREST_RATE', 'Interest Rate', 'Loan', 'Annual interest rate', '12.5', 'number', 1),
('BASE_RATE', 'Base Rate', 'Loan', 'Base rate', '10.0', 'number', 1),
('PREMIUM', 'Premium', 'Loan', 'Premium rate', '2.5', 'number', 1),
('TENURE_MONTHS', 'Tenure (Months)', 'Loan', 'Tenure in months', '60', 'number', 1),
('TENURE_YEARS', 'Tenure (Years)', 'Loan', 'Tenure in years', '5', 'number', 1),
('EMI_AMOUNT', 'EMI Amount', 'Loan', 'Monthly EMI', '11122', 'currency', 1),

-- Dates
('LOAN_APPLICATION_DATE', 'Application Date', 'Loan', 'Application date', '2025-12-01', 'date', 1),
('LOAN_APPROVAL_DATE', 'Approval Date', 'Loan', 'Approval date', '2025-12-15', 'date', 1),
('LOAN_DISBURSEMENT_DATE', 'Disbursement Date', 'Loan', 'Disbursement date', '2025-12-20', 'date', 1),
('LOAN_MATURITY_DATE', 'Maturity Date', 'Loan', 'Maturity date', '2030-12-20', 'date', 1),

-- Account Details
('LOAN_ACCOUNT_NUMBER', 'Loan Account Number', 'Loan', 'Account number', '1234567890', 'text', 1),
('DISBURSEMENT_ACCOUNT', 'Disbursement Account', 'Loan', 'Account for disbursement', '9876543210', 'text', 1),

-- =====================================================
-- SYSTEM PLACEHOLDERS
-- =====================================================
('CURRENT_DATE', 'Today''s Date', 'System', 'Current date (AD)', '2025-12-20', 'date', 1),
('CURRENT_DATE_NEPALI', 'Today''s Date (Nepali)', 'System', 'Current date (BS)', '2081-09-05', 'date', 1),
('CURRENT_DATE_WORDS', 'Date in Words', 'System', 'Date in words', '20th December, 2025', 'text', 1),
('CURRENT_YEAR', 'Current Year', 'System', 'Year', '2025', 'number', 1),
('CURRENT_MONTH', 'Current Month', 'System', 'Month', 'December', 'text', 1),

('AGREEMENT_NUMBER', 'Agreement Number', 'System', 'Auto-generated agreement number', 'AGR/2025/12/00123', 'text', 1),
('CUSTOMER_ID', 'Customer ID', 'System', 'Customer ID', 'CUST-2025-00123', 'text', 1),

-- Bank Details
('BANK_NAME', 'Bank Name', 'System', 'Bank name', 'State Bank of India', 'text', 1),
('BANK_ADDRESS', 'Bank Address', 'System', 'Bank address', 'Kathmandu, Nepal', 'text', 1),
('BANK_BRANCH', 'Branch Name', 'System', 'Branch name', 'Kathmandu Branch', 'text', 1),
('BANK_BRANCH_CODE', 'Branch Code', 'System', 'Branch code', 'KTM001', 'text', 1),
('BANK_PHONE', 'Bank Phone', 'System', 'Bank phone', '01-4123456', 'text', 1),
('BANK_EMAIL', 'Bank Email', 'System', 'Bank email', 'ktm@sbi.com.np', 'text', 1);

SELECT 'Complete placeholder library installed successfully!' AS Status;
SELECT COUNT(*) AS 'Total Placeholders' FROM template_placeholders;
