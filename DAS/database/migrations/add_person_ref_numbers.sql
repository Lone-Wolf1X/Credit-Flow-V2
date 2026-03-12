-- Add Person Reference Numbers to Existing Tables
-- Run this script to add unique reference numbers to borrowers and guarantors

-- 1. Add columns
ALTER TABLE borrowers
ADD COLUMN person_ref_no VARCHAR(20) UNIQUE AFTER id;

ALTER TABLE guarantors
ADD COLUMN person_ref_no VARCHAR(20) UNIQUE AFTER id;

-- 2. Generate reference numbers for existing borrowers
SET @br_counter = 0;

UPDATE borrowers
SET
    person_ref_no = CONCAT(
        'BR',
        YEAR(CURDATE()),
        LPAD(
            (
                @br_counter := @br_counter + 1
            ),
            4,
            '0'
        )
    )
WHERE
    person_ref_no IS NULL
ORDER BY id;

-- 3. Generate reference numbers for existing guarantors
SET @gr_counter = 0;

UPDATE guarantors
SET
    person_ref_no = CONCAT(
        'GR',
        YEAR(CURDATE()),
        LPAD(
            (
                @gr_counter := @gr_counter + 1
            ),
            4,
            '0'
        )
    )
WHERE
    person_ref_no IS NULL
ORDER BY id;

-- 4. Create sequence tracking table for future auto-generation
CREATE TABLE IF NOT EXISTS person_ref_sequences (
    type VARCHAR(10) PRIMARY KEY,
    year INT NOT NULL,
    last_sequence INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Initialize sequences with current counts
INSERT INTO
    person_ref_sequences (type, year, last_sequence)
SELECT 'BR', YEAR(CURDATE()), COUNT(*)
FROM borrowers
ON DUPLICATE KEY UPDATE
    last_sequence = (
        SELECT COUNT(*)
        FROM borrowers
    );

INSERT INTO
    person_ref_sequences (type, year, last_sequence)
SELECT 'GR', YEAR(CURDATE()), COUNT(*)
FROM guarantors
ON DUPLICATE KEY UPDATE
    last_sequence = (
        SELECT COUNT(*)
        FROM guarantors
    );

-- 6. Create function to generate next reference number
DELIMITER $$

CREATE FUNCTION IF NOT EXISTS get_next_person_ref(person_type VARCHAR(10))
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE current_year INT;
    DECLARE next_seq INT;
    DECLARE ref_no VARCHAR(20);
    
    SET current_year = YEAR(CURDATE());
    
    -- Get or create sequence for this type and year
    INSERT INTO person_ref_sequences (type, year, last_sequence)
    VALUES (person_type, current_year, 0)
    ON DUPLICATE KEY UPDATE 
        last_sequence = IF(year = current_year, last_sequence + 1, 1),
        year = current_year;
    
    -- Get the sequence
    SELECT last_sequence INTO next_seq
    FROM person_ref_sequences
    WHERE type = person_type AND year = current_year;
    
    -- Generate reference number
    SET ref_no = CONCAT(person_type, current_year, LPAD(next_seq, 4, '0'));
    
    RETURN ref_no;
END$$

DELIMITER;

-- Verification queries
SELECT 'Borrowers with ref numbers:' as info, COUNT(*) as count
FROM borrowers
WHERE
    person_ref_no IS NOT NULL;

SELECT 'Guarantors with ref numbers:' as info, COUNT(*) as count
FROM guarantors
WHERE
    person_ref_no IS NOT NULL;

SELECT * FROM person_ref_sequences;