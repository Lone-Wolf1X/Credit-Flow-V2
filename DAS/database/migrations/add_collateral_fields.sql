-- Migration script to add new collateral fields
-- Run this in phpMyAdmin or MySQL command line

USE das_db;

ALTER TABLE collateral
ADD COLUMN land_kitta_no_words VARCHAR(255) DEFAULT NULL COMMENT 'Kitta number in words (Nepali)',
ADD COLUMN land_khande VARCHAR(255) DEFAULT NULL COMMENT 'Khande',
ADD COLUMN land_dhito_parit_mulya VARCHAR(255) DEFAULT NULL COMMENT 'Dhito Parit Mulya',
ADD COLUMN land_biraha VARCHAR(255) DEFAULT NULL COMMENT 'Biraha',
ADD COLUMN land_kisim VARCHAR(255) DEFAULT NULL COMMENT 'Kisim',
ADD COLUMN land_guthi_mohi_name VARCHAR(255) DEFAULT NULL COMMENT 'Guthi/Mohi name',
ADD COLUMN land_remarks TEXT DEFAULT NULL COMMENT 'Remarks';