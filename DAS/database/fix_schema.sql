CREATE TABLE IF NOT EXISTS family_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    person_type ENUM('Borrower', 'Guarantor') NOT NULL,
    name VARCHAR(255),
    relation VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (person_id, person_type)
);

-- Add English and Nepali name columns if they don't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'borrowers' AND COLUMN_NAME = 'full_name_en') THEN
        ALTER TABLE borrowers ADD COLUMN full_name_en VARCHAR(255) AFTER full_name;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'borrowers' AND COLUMN_NAME = 'full_name_np') THEN
        ALTER TABLE borrowers ADD COLUMN full_name_np VARCHAR(255) AFTER full_name_en;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'guarantors' AND COLUMN_NAME = 'full_name_en') THEN
        ALTER TABLE guarantors ADD COLUMN full_name_en VARCHAR(255) AFTER full_name;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'das_db' AND TABLE_NAME = 'guarantors' AND COLUMN_NAME = 'full_name_np') THEN
        ALTER TABLE guarantors ADD COLUMN full_name_np VARCHAR(255) AFTER full_name_en;
    END IF;
END$$;
