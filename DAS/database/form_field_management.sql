-- =====================================================
-- Dynamic Form Field Management System
-- =====================================================

-- Table: form_section_config
-- Stores configuration for form sections (steps)
CREATE TABLE IF NOT EXISTS form_section_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    form_type ENUM('borrower', 'guarantor', 'collateral', 'loan', 'limit') NOT NULL,
    person_type ENUM('Individual', 'Corporate', 'Both') DEFAULT 'Both',
    section_name VARCHAR(100) NOT NULL,
    section_label_en VARCHAR(200) NOT NULL,
    section_label_np VARCHAR(200),
    section_icon VARCHAR(50) DEFAULT 'bi-file-text',
    step_number INT NOT NULL,
    display_order INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section (form_type, person_type, section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: form_field_config
-- Stores configuration for individual form fields
CREATE TABLE IF NOT EXISTS form_field_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label_en VARCHAR(200) NOT NULL,
    field_label_np VARCHAR(200),
    field_type ENUM('text', 'number', 'email', 'tel', 'date', 'nepali_date', 'select', 'textarea', 'checkbox', 'radio') NOT NULL,
    field_options JSON COMMENT 'For select/radio: {"options": [{"value": "val1", "label_en": "Label 1", "label_np": "लेबल १"}]}',
    placeholder_en VARCHAR(200),
    placeholder_np VARCHAR(200),
    default_value VARCHAR(200),
    validation_rules JSON COMMENT '{"required": true, "min": 0, "max": 100, "pattern": "regex"}',
    is_required BOOLEAN DEFAULT FALSE,
    column_width ENUM('col-md-3', 'col-md-4', 'col-md-6', 'col-md-12') DEFAULT 'col-md-4',
    display_order INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    help_text_en TEXT,
    help_text_np TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES form_section_config(id) ON DELETE CASCADE,
    UNIQUE KEY unique_field (section_id, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: form_field_values
-- Stores actual field values (for dynamic fields not in main tables)
CREATE TABLE IF NOT EXISTS form_field_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('borrower', 'guarantor', 'collateral', 'loan', 'limit') NOT NULL,
    entity_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES form_field_config(id) ON DELETE CASCADE,
    UNIQUE KEY unique_value (entity_type, entity_id, field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Default Form Sections
-- =====================================================

-- Individual Borrower Sections
INSERT INTO form_section_config (form_type, person_type, section_name, section_label_en, section_label_np, section_icon, step_number, display_order) VALUES
('borrower', 'Individual', 'personal_details', 'Personal Details', 'व्यक्तिगत विवरण', 'bi-person', 1, 1),
('borrower', 'Individual', 'address_details', 'Address Details', 'ठेगाना विवरण', 'bi-geo-alt', 2, 2),
('borrower', 'Individual', 'family_details', 'Family Details', 'परिवार विवरण', 'bi-people', 3, 3);

-- Corporate Borrower Sections
INSERT INTO form_section_config (form_type, person_type, section_name, section_label_en, section_label_np, section_icon, step_number, display_order) VALUES
('borrower', 'Corporate', 'corporate_details', 'Corporate Details', 'संस्थागत विवरण', 'bi-building', 1, 1),
('borrower', 'Corporate', 'address_details', 'Address Details', 'ठेगाना विवरण', 'bi-geo-alt', 2, 2),
('borrower', 'Corporate', 'authorized_person', 'Authorized Person', 'अधिकृत व्यक्ति', 'bi-person-badge', 3, 3);

-- Individual Guarantor Sections
INSERT INTO form_section_config (form_type, person_type, section_name, section_label_en, section_label_np, section_icon, step_number, display_order) VALUES
('guarantor', 'Individual', 'personal_details', 'Personal Details', 'व्यक्तिगत विवरण', 'bi-person', 1, 1),
('guarantor', 'Individual', 'address_details', 'Address Details', 'ठेगाना विवरण', 'bi-geo-alt', 2, 2),
('guarantor', 'Individual', 'family_details', 'Family Details', 'परिवार विवरण', 'bi-people', 3, 3);

-- Corporate Guarantor Sections
INSERT INTO form_section_config (form_type, person_type, section_name, section_label_en, section_label_np, section_icon, step_number, display_order) VALUES
('guarantor', 'Corporate', 'corporate_details', 'Corporate Details', 'संस्थागत विवरण', 'bi-building', 1, 1),
('guarantor', 'Corporate', 'address_details', 'Address Details', 'ठेगाना विवरण', 'bi-geo-alt', 2, 2),
('guarantor', 'Corporate', 'authorized_person', 'Authorized Person', 'अधिकृत व्यक्ति', 'bi-person-badge', 3, 3);

-- =====================================================
-- Insert Sample Fields for Individual Borrower
-- =====================================================

-- Personal Details Fields
INSERT INTO form_field_config (section_id, field_name, field_label_en, field_label_np, field_type, is_required, column_width, display_order) VALUES
((SELECT id FROM form_section_config WHERE form_type='borrower' AND person_type='Individual' AND section_name='personal_details'), 
 'full_name_en', 'Full Name (English)', 'पूरा नाम (अंग्रेजी)', 'text', TRUE, 'col-md-4', 1),
((SELECT id FROM form_section_config WHERE form_type='borrower' AND person_type='Individual' AND section_name='personal_details'), 
 'full_name_np', 'Full Name (Nepali)', 'पूरा नाम (नेपाली)', 'text', TRUE, 'col-md-4', 2),
((SELECT id FROM form_section_config WHERE form_type='borrower' AND person_type='Individual' AND section_name='personal_details'), 
 'date_of_birth', 'Date of Birth', 'जन्म मिति', 'nepali_date', TRUE, 'col-md-4', 3),
((SELECT id FROM form_section_config WHERE form_type='borrower' AND person_type='Individual' AND section_name='personal_details'), 
 'gender', 'Gender', 'लिङ्ग', 'select', TRUE, 'col-md-4', 4);

-- Update gender field with options
UPDATE form_field_config 
SET field_options = JSON_OBJECT(
    'options', JSON_ARRAY(
        JSON_OBJECT('value', 'Male', 'label_en', 'Male', 'label_np', 'पुरुष'),
        JSON_OBJECT('value', 'Female', 'label_en', 'Female', 'label_np', 'महिला'),
        JSON_OBJECT('value', 'Other', 'label_en', 'Other', 'label_np', 'अन्य')
    )
)
WHERE field_name = 'gender';

-- =====================================================
-- Create Indexes for Performance
-- =====================================================

CREATE INDEX idx_section_form_type ON form_section_config(form_type, person_type, is_active);
CREATE INDEX idx_field_section ON form_field_config(section_id, is_active, display_order);
CREATE INDEX idx_value_entity ON form_field_values(entity_type, entity_id);
