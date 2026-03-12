-- Create Master Entities table
CREATE TABLE IF NOT EXISTS master_entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) NOT NULL,
    full_name_en VARCHAR(255),
    full_name_np VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    citizenship_number VARCHAR(50),
    id_issue_date DATE,
    id_issue_district VARCHAR(100),
    father_name VARCHAR(200),
    grandfather_name VARCHAR(200),
    address_perm VARCHAR(255),
    contact_number VARCHAR(50),
    email VARCHAR(100),
    entity_type ENUM('Individual', 'Corporate', 'Proprietorship') DEFAULT 'Individual',

-- Audit fields
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

-- Unique constraint to prevent duplicate entries if possible
-- (Composite unique key on citizenship for individuals?)
UNIQUE KEY unique_citizen (citizenship_number, id_issue_district) );

-- Add Master Entity ID to Borrowers
ALTER TABLE borrowers
ADD COLUMN master_entity_id INT DEFAULT NULL,
ADD CONSTRAINT fk_borrower_master FOREIGN KEY (master_entity_id) REFERENCES master_entities (id) ON DELETE SET NULL;

-- Add Master Entity ID to Guarantors
ALTER TABLE guarantors
ADD COLUMN master_entity_id INT DEFAULT NULL,
ADD CONSTRAINT fk_guarantor_master FOREIGN KEY (master_entity_id) REFERENCES master_entities (id) ON DELETE SET NULL;

-- Add Master Entity ID to Collateral (Owner)
-- Collateral table likely doesn't have master_entity_id directly if it uses owner_type/owner_id
-- But if we want to support 'Other' owners who are master entities:
-- For now, we will stick to linking Borrowers/Guarantors to Master Entities.