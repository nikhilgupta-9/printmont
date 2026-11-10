-- Migration 033: Categories Management Doc Spec Enhancements
-- Adds page_target, home_featured_product_ids, and additional_parent_ids to categories table

ALTER TABLE categories 
    ADD COLUMN IF NOT EXISTS page_target ENUM('home_page', 'other_pages', 'both') NOT NULL DEFAULT 'both' AFTER level;

ALTER TABLE categories 
    ADD COLUMN IF NOT EXISTS home_featured_product_ids TEXT NULL DEFAULT NULL AFTER desktop_home_tag;

ALTER TABLE categories 
    ADD COLUMN IF NOT EXISTS additional_parent_ids VARCHAR(255) NULL DEFAULT NULL AFTER parent_id;
