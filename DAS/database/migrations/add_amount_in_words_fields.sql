-- Migration script to add amount_in_words field to limit_details only
-- Run this in phpMyAdmin or MySQL command line

USE das_db;

-- Add to limit_details table only
ALTER TABLE limit_details
ADD COLUMN amount_in_words VARCHAR(500) DEFAULT NULL COMMENT 'Loan amount in words (Nepali)';