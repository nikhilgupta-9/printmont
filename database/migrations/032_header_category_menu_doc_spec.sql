-- Migration: 032_header_category_menu_doc_spec.sql
-- Restores/adds fields specified in Google Doc for Header Category Menu

SET @dbname = DATABASE();

-- 1. Desktop & Mobile Menu Description Fields (for Item 4: Category Text Description show inside)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'desktop_menu_desc');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `desktop_menu_desc` TEXT NULL AFTER `description`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'desktop_menu_desc_status');
SET @sql = IF(@col = 0, "ALTER TABLE `categories` ADD COLUMN `desktop_menu_desc_status` ENUM('show','hide') DEFAULT 'show' AFTER `desktop_menu_desc`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'mobile_menu_desc');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `mobile_menu_desc` TEXT NULL AFTER `desktop_menu_desc_status`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'mobile_menu_desc_status');
SET @sql = IF(@col = 0, "ALTER TABLE `categories` ADD COLUMN `mobile_menu_desc_status` ENUM('show','hide') DEFAULT 'show' AFTER `mobile_menu_desc`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Banner/Showcase Orders for Inside Pages Design 1/2/3
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'desktop_menu_order_1');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `desktop_menu_order_1` INT(11) DEFAULT 1 AFTER `desktop_menu_title_1`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'desktop_menu_order_2');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `desktop_menu_order_2` INT(11) DEFAULT 2 AFTER `desktop_menu_title_2`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'desktop_menu_order_3');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `desktop_menu_order_3` INT(11) DEFAULT 3 AFTER `desktop_menu_title_3`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Mobile Sidebar Custom Title & Image
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'mobile_sidebar_title');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `mobile_sidebar_title` VARCHAR(255) NULL AFTER `mobile_sidebar_order`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'mobile_sidebar_image');
SET @sql = IF(@col = 0, 'ALTER TABLE `categories` ADD COLUMN `mobile_sidebar_image` VARCHAR(255) NULL AFTER `mobile_sidebar_title`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
