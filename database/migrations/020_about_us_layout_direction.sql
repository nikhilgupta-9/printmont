-- ============================================================
-- Migration 020: Add layout direction (extra column) to about_us table
--
-- Allows admin to toggle and control image position:
-- 'right' = Text on Left, Image/Feature on Right
-- 'left'  = Image/Feature on Left, Text on Right (Cross)
-- ============================================================

ALTER TABLE `about_us`
  ADD COLUMN `extra` VARCHAR(255) DEFAULT 'right' AFTER `image_path`;

UPDATE `about_us` SET `extra` = 'right' WHERE `extra` IS NULL OR `extra` = '';
