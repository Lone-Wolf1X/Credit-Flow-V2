-- Clear existing placeholders to avoid duplicates/confusion
TRUNCATE TABLE template_placeholders;

-- Insert Comprehensive Placeholders
INSERT INTO template_placeholders (placeholder_key, placeholder_name, category, description, example_value, data_type) VALUES
-- System
('SYSTEM_DATE', 'Current Date', 'System', 'Date when document is generated', '2025-01-01', 'Date'),
('CUSTOMER_ID', 'Customer ID', 'System', 'Unique ID of the customer', '2025001', 'Text'),
('APPROVED_BY_NAME', 'Approved By', 'System', 'Name of the checker', 'John Doe', 'Text'),
('APPROVED_AT', 'Approval Date', 'System', 'Date of approval', '2025-01-01', 'Date'),
('GENERATED_AT', 'Generation Time', 'System', 'Timestamp of generation', '2025-01-01 10:00:00', 'DateTime'),
('BANK_NAME', 'Bank Name', 'System', 'Name of the bank', 'State Bank of India', 'Text'),
('BANK_ADDRESS', 'Bank Address', 'System', 'Address of the bank', 'Kathmandu, Nepal', 'Text'),
('BANK_BRANCH', 'Bank Branch', 'System', 'Branch name', 'Main Branch', 'Text'),

-- Borrower (Main)
('BORROWER_NAME', 'Borrower Name', 'Borrower', 'Full name of main borrower', 'Ram Sharma', 'Text'),
('BORROWER_CIT_NUMBER', 'Citizenship No', 'Borrower', 'Citizenship number', '12-34-56/789', 'Text'),
('BORROWER_CIT_ISSUE_DATE', 'Citizenship Date', 'Borrower', 'Date of issue', '2070-01-01', 'Date'),
('BORROWER_CIT_ISSUE_DISTRICT', 'Citizenship District', 'Borrower', 'District of issue', 'Kathmandu', 'Text'),
('BORROWER_DOB', 'Date of Birth', 'Borrower', 'Date of birth', '2050-01-01', 'Date'),
('BORROWER_FATHER_NAME', 'Father Name', 'Borrower', 'Father''s name', 'Hari Sharma', 'Text'),
('BORROWER_GRANDFATHER_NAME', 'Grandfather Name', 'Borrower', 'Grandfather''s name', 'Gopal Sharma', 'Text'),
('BORROWER_SPOUSE_NAME', 'Spouse Name', 'Borrower', 'Spouse''s name', 'Sita Sharma', 'Text'),
('BORROWER_EMAIL', 'Email', 'Borrower', 'Email address', 'ram@example.com', 'Text'),
('BORROWER_PHONE', 'Phone', 'Borrower', 'Phone number', '9800000000', 'Text'),
('BORROWER_MARITAL_STATUS', 'Marital Status', 'Borrower', 'Marital Status', 'Married', 'Text'),
('BORROWER_GENDER', 'Gender', 'Borrower', 'Gender', 'Male', 'Text'),

-- Borrower Address
('BORROWER_PERM_PROVINCE', 'Province (Perm)', 'Borrower', 'Permanent Province', 'Bagmati', 'Text'),
('BORROWER_PERM_DISTRICT', 'District (Perm)', 'Borrower', 'Permanent District', 'Kathmandu', 'Text'),
('BORROWER_PERM_MUNICIPALITY', 'Municipality (Perm)', 'Borrower', 'Permanent Muni/VDC', 'Kathmandu Metro', 'Text'),
('BORROWER_PERM_WARD', 'Ward (Perm)', 'Borrower', 'Permanent Ward No', '10', 'Number'),
('BORROWER_PERM_TOLE', 'Tole (Perm)', 'Borrower', 'Permanent Tole/Street', 'New Road', 'Text'),
('BORROWER_PERM_FULL_ADDRESS', 'Full Address (Perm)', 'Borrower', 'Complete formatted address', 'Kathmandu-10, New Road', 'Text'),

('BORROWER_TEMP_PROVINCE', 'Province (Temp)', 'Borrower', 'Temporary Province', 'Bagmati', 'Text'),
('BORROWER_TEMP_DISTRICT', 'District (Temp)', 'Borrower', 'Temporary District', 'Lalitpur', 'Text'),
('BORROWER_TEMP_MUNICIPALITY', 'Municipality (Temp)', 'Borrower', 'Temporary Muni/VDC', 'Lalitpur Metro', 'Text'),
('BORROWER_TEMP_WARD', 'Ward (Temp)', 'Borrower', 'Temporary Ward No', '3', 'Number'),
('BORROWER_TEMP_TOLE', 'Tole (Temp)', 'Borrower', 'Temporary Tole/Street', 'Pulchowk', 'Text'),

-- Guarantor
('GUARANTOR_NAME', 'Guarantor Name', 'Guarantor', 'Full name of guarantor', 'Shyam Singh', 'Text'),
('GUARANTOR_CIT_NUMBER', 'Citizenship No', 'Guarantor', 'Citizenship number', '99-88-77', 'Text'),
('GUARANTOR_CIT_ISSUE_DATE', 'Citizenship Date', 'Guarantor', 'Date of issue', '2075-01-01', 'Date'),
('GUARANTOR_CIT_ISSUE_DISTRICT', 'Citizenship District', 'Guarantor', 'District of issue', 'Bhaktapur', 'Text'),
('GUARANTOR_RELATION', 'Relation', 'Guarantor', 'Relation to borrower', 'Brother', 'Text'),
('GUARANTOR_FATHER_NAME', 'Father Name', 'Guarantor', 'Father''s name', 'Mohan Singh', 'Text'),
('GUARANTOR_GRANDFATHER_NAME', 'Grandfather Name', 'Guarantor', 'Grandfather''s name', 'Sohan Singh', 'Text'),
('GUARANTOR_SPOUSE_NAME', 'Spouse Name', 'Guarantor', 'Spouse''s name', 'Gita Singh', 'Text'),
('GUARANTOR_PERM_FULL_ADDRESS', 'Full Address', 'Guarantor', 'Complete formatted address', 'Bhaktapur-5, Durbar Sq', 'Text'),

-- Loan
('LOAN_TYPE', 'Loan Type', 'Loan', 'Type of loan', 'Home Loan', 'Text'),
('LOAN_AMOUNT', 'Loan Amount', 'Loan', 'Sanctioned amount (formatted)', '1,000,000.00', 'Currency'),
('LOAN_AMOUNT_WORDS', 'Loan Amount (Words)', 'Loan', 'Amount in words', 'One Million Only', 'Text'),
('LOAN_TENURE', 'Tenure', 'Loan', 'Duration in months', '120', 'Number'),
('LOAN_INTEREST_RATE', 'Interest Rate', 'Loan', 'Interest rate %', '12.5', 'Number'),
('LOAN_PURPOSE', 'Purpose', 'Loan', 'Purpose of loan', 'House Construction', 'Text'),
('BASE_RATE', 'Base Rate', 'Loan', 'Base Rate %', '9.0', 'Number'),
('PREMIUM', 'Premium', 'Loan', 'Premium %', '3.5', 'Number'),
('INSTALLMENT_AMOUNT', 'Installment', 'Loan', 'Monthly installment', '15,000.00', 'Currency'),

-- Collateral
('COLLATERAL_TYPE', 'Type', 'Collateral', 'Land or Vehicle', 'Land', 'Text'),
('COLLATERAL_OWNER', 'Owner Name', 'Collateral', 'Name of owner', 'Ram Sharma', 'Text'),
('COLLATERAL_VALUATION', 'Valuation', 'Collateral', 'Fair Market Value', '5,000,000.00', 'Currency'),

-- Land Specific
('LAND_DISTRICT', 'Land District', 'Collateral', 'Location of land', 'Kathmandu', 'Text'),
('LAND_MUNICIPALITY', 'Land Municipality', 'Collateral', 'Municipality of land', 'Kathmandu Metro', 'Text'),
('LAND_WARD', 'Land Ward', 'Collateral', 'Ward number', '10', 'Number'),
('LAND_KITTA', 'Kitta No', 'Collateral', 'Plot/Kitta number', '1234', 'Text'),
('LAND_AREA', 'Area', 'Collateral', 'Area of land', '4 Aana', 'Text'),
('LAND_SHEET_NO', 'Sheet No', 'Collateral', 'Map sheet number', '102-Ka', 'Text'),

-- Vehicle Specific
('VEHICLE_MODEL', 'Vehicle Model', 'Collateral', 'Model name', 'Hyundai Creta', 'Text'),
('VEHICLE_REG_NO', 'Registration No', 'Collateral', 'Plaate number', 'Ba 1 Ja 1234', 'Text'),
('VEHICLE_ENGINE_NO', 'Engine No', 'Collateral', 'Engine serial', 'ENG123456', 'Text'),
('VEHICLE_CHASSIS_NO', 'Chassis No', 'Collateral', 'Chassis serial', 'CHA123456', 'Text');
