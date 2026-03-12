-- =====================================================
-- COMPREHENSIVE PLACEHOLDER LIBRARY FOR DAS
-- Updated version - Handles duplicate entries gracefully
-- =====================================================

USE das_db;

-- Option 1: Clear all existing placeholders first (UNCOMMENT if you want fresh start)
-- TRUNCATE TABLE template_placeholders;

-- Option 2: Use INSERT IGNORE to skip duplicates (DEFAULT - SAFE)
-- This will only insert NEW placeholders and skip existing ones

-- =====================================================
-- CUSTOMER PROFILE PLACEHOLDERS
-- =====================================================
INSERT IGNORE INTO template_placeholders (placeholder_key, placeholder_name, category, description, example_value, data_type, is_system) VALUES
('customer.id', 'Customer ID', 'Customer Profile', 'Auto-generated customer ID', '2025001', 'text', 1),
('customer.type', 'Customer Type', 'Customer Profile', 'Individual or Corporate', 'Individual', 'text', 1),
('customer.full_name', 'Customer Full Name', 'Customer Profile', 'Full name from profile', 'Rajesh Kumar Sharma', 'text', 1),
('customer.email', 'Customer Email', 'Customer Profile', 'Email address', 'rajesh@example.com', 'text', 1),
('customer.contact', 'Customer Contact', 'Customer Profile', 'Contact number', '9841234567', 'text', 1),
('customer.province', 'Customer Province', 'Customer Profile', 'Province location', 'Bagmati', 'text', 1),
('customer.sol', 'Service Outlet Location', 'Customer Profile', 'SOL code', 'KTM-001', 'text', 1),
('customer.status', 'Customer Status', 'Customer Profile', 'Profile status', 'Approved', 'text', 1),
('customer.created_date', 'Profile Created Date', 'Customer Profile', 'Profile creation date', '2025-12-20', 'date', 1),

-- =====================================================
-- BORROWER - INDIVIDUAL PLACEHOLDERS
-- =====================================================
('borrower.full_name', 'Borrower Full Name', 'Borrower', 'Full name of borrower', 'Rajesh Kumar Sharma', 'text', 1),
('borrower.type', 'Borrower Type', 'Borrower', 'Individual or Corporate', 'Individual', 'text', 1),
('borrower.is_co_borrower', 'Is Co-Borrower', 'Borrower', 'Yes/No for joint loans', 'No', 'text', 1),

-- Individual Borrower Fields
('borrower.dob', 'Date of Birth', 'Borrower', 'Borrower date of birth', '1990-01-15', 'date', 1),
('borrower.dob_nepali', 'Date of Birth (Nepali)', 'Borrower', 'DOB in Nepali calendar', '2046-10-01', 'date', 1),
('borrower.age', 'Age', 'Borrower', 'Calculated age in years', '35', 'number', 1),
('borrower.gender', 'Gender', 'Borrower', 'Male/Female/Other', 'Male', 'text', 1),
('borrower.relationship_status', 'Relationship Status', 'Borrower', 'Single/Married/Divorced/Widowed', 'Married', 'text', 1),
('borrower.citizenship_number', 'Citizenship Number', 'Borrower', 'Citizenship number', '12-01-75-12345', 'text', 1),
('borrower.id_issue_date', 'ID Issue Date', 'Borrower', 'Citizenship issue date', '2010-05-15', 'date', 1),
('borrower.id_issue_district', 'ID Issue District', 'Borrower', 'District of issue', 'Kathmandu', 'text', 1),
('borrower.id_issue_authority', 'ID Issue Authority', 'Borrower', 'Issuing authority', 'District Administration Office, Kathmandu', 'text', 1),
('borrower.id_reissue_date', 'ID Reissue Date', 'Borrower', 'Reissue date if applicable', '2020-06-10', 'date', 1),
('borrower.reissue_count', 'Reissue Count', 'Borrower', 'Number of times reissued', 'First', 'text', 1),
('borrower.father_name', 'Father Name', 'Borrower', 'Father\'s full name', 'Krishna Prasad Sharma', 'text', 1),

-- Corporate Borrower Fields
('borrower.company_name', 'Company Name', 'Borrower', 'Registered company name', 'ABC Pvt. Ltd.', 'text', 1),
('borrower.registration_no', 'Registration Number', 'Borrower', 'Company registration number', '123456/078/079', 'text', 1),
('borrower.registration_date', 'Registration Date', 'Borrower', 'Company registration date', '2020-01-15', 'date', 1),
('borrower.registration_type', 'Registration Type', 'Borrower', 'Proprietorship/Partnership/Pvt Ltd', 'Private Limited', 'text', 1),
('borrower.pan_number', 'PAN Number', 'Borrower', 'Permanent Account Number', '123456789', 'text', 1),
('borrower.pan_issue_date', 'PAN Issue Date', 'Borrower', 'PAN issue date', '2020-02-01', 'date', 1),
('borrower.pan_issue_authority', 'PAN Issue Authority', 'Borrower', 'PAN issuing authority', 'Inland Revenue Department', 'text', 1),
('borrower.firm_registration_authority', 'Firm Registration Authority', 'Borrower', 'Registration authority', 'Office of Company Registrar', 'text', 1),

-- Borrower Permanent Address
('borrower.perm_country', 'Permanent Country', 'Borrower Address', 'Country', 'Nepal', 'text', 1),
('borrower.perm_province', 'Permanent Province', 'Borrower Address', 'Province', 'Bagmati', 'text', 1),
('borrower.perm_district', 'Permanent District', 'Borrower Address', 'District', 'Kathmandu', 'text', 1),
('borrower.perm_municipality', 'Permanent Municipality/VDC', 'Borrower Address', 'Municipality or VDC', 'Kathmandu Metropolitan City', 'text', 1),
('borrower.perm_ward', 'Permanent Ward No', 'Borrower Address', 'Ward number', '10', 'text', 1),
('borrower.perm_town', 'Permanent Town/Village', 'Borrower Address', 'Town or village name', 'Baneshwor', 'text', 1),
('borrower.perm_street_name', 'Permanent Street Name', 'Borrower Address', 'Street name', 'Madan Bhandari Path', 'text', 1),
('borrower.perm_street_number', 'Permanent Street Number', 'Borrower Address', 'House/building number', '123', 'text', 1),
('borrower.perm_address_full', 'Permanent Full Address', 'Borrower Address', 'Complete permanent address', 'Baneshwor, Ward 10, Kathmandu', 'text', 1),

-- Borrower Temporary Address
('borrower.temp_country', 'Temporary Country', 'Borrower Address', 'Country', 'Nepal', 'text', 1),
('borrower.temp_province', 'Temporary Province', 'Borrower Address', 'Province', 'Bagmati', 'text', 1),
('borrower.temp_district', 'Temporary District', 'Borrower Address', 'District', 'Lalitpur', 'text', 1),
('borrower.temp_municipality', 'Temporary Municipality/VDC', 'Borrower Address', 'Municipality or VDC', 'Lalitpur Sub-Metropolitan', 'text', 1),
('borrower.temp_ward', 'Temporary Ward No', 'Borrower Address', 'Ward number', '5', 'text', 1),
('borrower.temp_town', 'Temporary Town/Village', 'Borrower Address', 'Town or village name', 'Jawalakhel', 'text', 1),
('borrower.temp_street_name', 'Temporary Street Name', 'Borrower Address', 'Street name', 'Pulchowk Road', 'text', 1),
('borrower.temp_street_number', 'Temporary Street Number', 'Borrower Address', 'House/building number', '456', 'text', 1),
('borrower.temp_address_full', 'Temporary Full Address', 'Borrower Address', 'Complete temporary address', 'Jawalakhel, Ward 5, Lalitpur', 'text', 1),

-- Borrower Mailing Address (Corporate)
('borrower.mail_country', 'Mailing Country', 'Borrower Address', 'Mailing country', 'Nepal', 'text', 1),
('borrower.mail_province', 'Mailing Province', 'Borrower Address', 'Mailing province', 'Bagmati', 'text', 1),
('borrower.mail_district', 'Mailing District', 'Borrower Address', 'Mailing district', 'Kathmandu', 'text', 1),
('borrower.mail_municipality', 'Mailing Municipality/VDC', 'Borrower Address', 'Mailing municipality', 'Kathmandu Metropolitan', 'text', 1),
('borrower.mail_ward', 'Mailing Ward No', 'Borrower Address', 'Mailing ward', '10', 'text', 1),
('borrower.mail_town', 'Mailing Town/Village', 'Borrower Address', 'Mailing town', 'New Baneshwor', 'text', 1),
('borrower.mail_street_name', 'Mailing Street Name', 'Borrower Address', 'Mailing street', 'Corporate Office Road', 'text', 1),
('borrower.mail_street_number', 'Mailing Street Number', 'Borrower Address', 'Mailing building number', '789', 'text', 1),
('borrower.mail_address_full', 'Mailing Full Address', 'Borrower Address', 'Complete mailing address', 'New Baneshwor, Ward 10, Kathmandu', 'text', 1),

-- =====================================================
-- GUARANTOR PLACEHOLDERS
-- =====================================================
('guarantor.full_name', 'Guarantor Full Name', 'Guarantor', 'Full name of guarantor', 'Sita Sharma', 'text', 1),
('guarantor.type', 'Guarantor Type', 'Guarantor', 'Individual or Corporate', 'Individual', 'text', 1),

-- Individual Guarantor Fields
('guarantor.dob', 'Guarantor Date of Birth', 'Guarantor', 'Date of birth', '1985-05-20', 'date', 1),
('guarantor.dob_nepali', 'Guarantor DOB (Nepali)', 'Guarantor', 'DOB in Nepali calendar', '2042-02-07', 'date', 1),
('guarantor.age', 'Guarantor Age', 'Guarantor', 'Age in years', '40', 'number', 1),
('guarantor.gender', 'Guarantor Gender', 'Guarantor', 'Male/Female/Other', 'Female', 'text', 1),
('guarantor.relationship_status', 'Guarantor Relationship Status', 'Guarantor', 'Marital status', 'Married', 'text', 1),
('guarantor.citizenship_number', 'Guarantor Citizenship Number', 'Guarantor', 'Citizenship number', '12-01-75-67890', 'text', 1),
('guarantor.id_issue_date', 'Guarantor ID Issue Date', 'Guarantor', 'ID issue date', '2012-03-10', 'date', 1),
('guarantor.id_issue_district', 'Guarantor ID Issue District', 'Guarantor', 'District of issue', 'Lalitpur', 'text', 1),
('guarantor.id_issue_authority', 'Guarantor ID Issue Authority', 'Guarantor', 'Issuing authority', 'DAO Lalitpur', 'text', 1),
('guarantor.father_name', 'Guarantor Father Name', 'Guarantor', 'Father\'s name', 'Ram Prasad Sharma', 'text', 1),

-- Corporate Guarantor Fields
('guarantor.company_name', 'Guarantor Company Name', 'Guarantor', 'Company name', 'XYZ Enterprises', 'text', 1),
('guarantor.registration_no', 'Guarantor Registration No', 'Guarantor', 'Company registration', '987654/078/079', 'text', 1),
('guarantor.pan_number', 'Guarantor PAN', 'Guarantor', 'PAN number', '987654321', 'text', 1),

-- Guarantor Permanent Address
('guarantor.perm_province', 'Guarantor Permanent Province', 'Guarantor Address', 'Province', 'Bagmati', 'text', 1),
('guarantor.perm_district', 'Guarantor Permanent District', 'Guarantor Address', 'District', 'Bhaktapur', 'text', 1),
('guarantor.perm_municipality', 'Guarantor Permanent Municipality', 'Guarantor Address', 'Municipality', 'Bhaktapur Municipality', 'text', 1),
('guarantor.perm_ward', 'Guarantor Permanent Ward', 'Guarantor Address', 'Ward number', '8', 'text', 1),
('guarantor.perm_address_full', 'Guarantor Permanent Address', 'Guarantor Address', 'Full permanent address', 'Ward 8, Bhaktapur', 'text', 1),

-- Guarantor Temporary Address
('guarantor.temp_province', 'Guarantor Temporary Province', 'Guarantor Address', 'Province', 'Bagmati', 'text', 1),
('guarantor.temp_district', 'Guarantor Temporary District', 'Guarantor Address', 'District', 'Kathmandu', 'text', 1),
('guarantor.temp_municipality', 'Guarantor Temporary Municipality', 'Guarantor Address', 'Municipality', 'Kathmandu Metro', 'text', 1),
('guarantor.temp_ward', 'Guarantor Temporary Ward', 'Guarantor Address', 'Ward number', '15', 'text', 1),
('guarantor.temp_address_full', 'Guarantor Temporary Address', 'Guarantor Address', 'Full temporary address', 'Ward 15, Kathmandu', 'text', 1),

-- =====================================================
-- COLLATERAL PLACEHOLDERS
-- =====================================================
('collateral.type', 'Collateral Type', 'Collateral', 'Land or Vehicle', 'Land', 'text', 1),
('collateral.owner_name', 'Collateral Owner Name', 'Collateral', 'Name of owner', 'Rajesh Kumar Sharma', 'text', 1),
('collateral.owner_type', 'Collateral Owner Type', 'Collateral', 'Borrower or Guarantor', 'Borrower', 'text', 1),

-- Land Collateral
('collateral.land_country', 'Land Country', 'Collateral', 'Country', 'Nepal', 'text', 1),
('collateral.land_province', 'Land Province', 'Collateral', 'Province', 'Bagmati', 'text', 1),
('collateral.land_district', 'Land District', 'Collateral', 'District', 'Kathmandu', 'text', 1),
('collateral.land_municipality', 'Land Municipality/VDC', 'Collateral', 'Municipality', 'Kathmandu Metro', 'text', 1),
('collateral.land_ward', 'Land Ward No', 'Collateral', 'Ward number', '10', 'text', 1),
('collateral.land_sheet_no', 'Land Sheet Number', 'Collateral', 'Sheet number', '123', 'text', 1),
('collateral.land_kitta_no', 'Land Kitta Number', 'Collateral', 'Kitta number', '456', 'text', 1),
('collateral.land_area', 'Land Area', 'Collateral', 'Area with unit', '0-5-2-0 (5 Aana 2 Paisa)', 'text', 1),
('collateral.land_malpot_office', 'Malpot Office', 'Collateral', 'Land revenue office', 'Kathmandu Malpot Office', 'text', 1),
('collateral.land_location_full', 'Land Full Location', 'Collateral', 'Complete land location', 'Sheet 123, Kitta 456, Ward 10, Kathmandu', 'text', 1),

-- Vehicle Collateral
('collateral.vehicle_model', 'Vehicle Model', 'Collateral', 'Vehicle model number', 'Toyota Corolla 2020', 'text', 1),
('collateral.vehicle_engine_no', 'Vehicle Engine Number', 'Collateral', 'Engine number', 'ENG123456789', 'text', 1),
('collateral.vehicle_chassis_no', 'Vehicle Chassis Number', 'Collateral', 'Chassis number', 'CHS987654321', 'text', 1),
('collateral.vehicle_no', 'Vehicle Number', 'Collateral', 'Registration/plate number', 'BA 1 KHA 1234', 'text', 1),

-- =====================================================
-- LOAN/LIMIT DETAILS PLACEHOLDERS
-- =====================================================
('loan.type', 'Loan Type', 'Loan', 'Type of loan product', 'Personal Term Loan', 'text', 1),
('loan.scheme', 'Loan Scheme', 'Loan', 'Loan scheme name', 'Regular Scheme', 'text', 1),
('loan.amount', 'Loan Amount', 'Loan', 'Sanctioned amount', '500000.00', 'currency', 1),
('loan.amount_words', 'Loan Amount in Words', 'Loan', 'Amount in words', 'Five Lakh Only', 'text', 1),
('loan.amount_words_nepali', 'Loan Amount (Nepali Words)', 'Loan', 'Amount in Nepali words', 'पाँच लाख मात्र', 'text', 1),
('loan.tenure', 'Loan Tenure', 'Loan', 'Tenure in months', '60', 'number', 1),
('loan.tenure_years', 'Loan Tenure (Years)', 'Loan', 'Tenure in years', '5', 'number', 1),
('loan.interest_rate', 'Interest Rate', 'Loan', 'Annual interest rate %', '12.50', 'number', 1),
('loan.base_rate', 'Base Rate', 'Loan', 'Base rate %', '10.00', 'number', 1),
('loan.premium', 'Premium Rate', 'Loan', 'Premium %', '2.50', 'number', 1),
('loan.approved_date', 'Loan Approved Date', 'Loan', 'Approval date', '2025-12-20', 'date', 1),
('loan.approved_date_nepali', 'Loan Approved Date (Nepali)', 'Loan', 'Approval date in BS', '2081-09-06', 'date', 1),
('loan.disbursement_date', 'Disbursement Date', 'Loan', 'Expected disbursement date', '2025-12-25', 'date', 1),
('loan.maturity_date', 'Maturity Date', 'Loan', 'Loan maturity date', '2030-12-25', 'date', 1),
('loan.emi_amount', 'EMI Amount', 'Loan', 'Monthly EMI', '11122.22', 'currency', 1),
('loan.emi_amount_words', 'EMI Amount in Words', 'Loan', 'EMI in words', 'Eleven Thousand One Hundred Twenty Two Rupees Twenty Two Paisa Only', 'text', 1),
('loan.total_interest', 'Total Interest', 'Loan', 'Total interest payable', '167333.20', 'currency', 1),
('loan.total_repayment', 'Total Repayment Amount', 'Loan', 'Principal + Interest', '667333.20', 'currency', 1),

-- =====================================================
-- SYSTEM/BANK PLACEHOLDERS
-- =====================================================
('date.today', 'Today\'s Date', 'System', 'Current date (AD)', '2025-12-20', 'date', 1),
('date.today_nepali', 'Today\'s Date (Nepali)', 'System', 'Current date (BS)', '2081-09-06', 'date', 1),
('date.today_words', 'Today\'s Date in Words', 'System', 'Date in words', 'Twentieth December Two Thousand Twenty Five', 'text', 1),
('date.year', 'Current Year', 'System', 'Current year', '2025', 'text', 1),
('date.month', 'Current Month', 'System', 'Current month', 'December', 'text', 1),
('date.day', 'Current Day', 'System', 'Current day', '20', 'text', 1),

('bank.name', 'Bank Name', 'System', 'Name of the bank', 'State Bank of India', 'text', 1),
('bank.address', 'Bank Address', 'System', 'Bank head office address', 'Kathmandu, Nepal', 'text', 1),
('bank.branch', 'Branch Name', 'System', 'Branch name', 'Kathmandu Branch', 'text', 1),
('bank.branch_code', 'Branch Code', 'System', 'Branch code', 'KTM-001', 'text', 1),
('bank.contact', 'Bank Contact', 'System', 'Bank contact number', '01-4123456', 'text', 1),
('bank.email', 'Bank Email', 'System', 'Bank email', 'info@bank.com.np', 'text', 1),
('bank.website', 'Bank Website', 'System', 'Bank website', 'www.bank.com.np', 'text', 1),

-- Document specific
('document.reference_no', 'Document Reference Number', 'System', 'Unique document reference', 'DOC-2025-001', 'text', 1),
('document.generated_date', 'Document Generated Date', 'System', 'When document was generated', '2025-12-20', 'date', 1),
('document.generated_by', 'Generated By', 'System', 'User who generated document', 'Maker Name', 'text', 1);

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT 'Comprehensive placeholder library updated successfully!' AS Status;
SELECT COUNT(*) AS 'Total Placeholders' FROM template_placeholders;
SELECT category, COUNT(*) AS 'Count' FROM template_placeholders GROUP BY category ORDER BY category;
