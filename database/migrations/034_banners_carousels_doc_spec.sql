-- Migration 034: Align banners and carousels with Document 7 (Home Pages Banners) and Document 8 (Manage Carousels)

-- 1. Upgrade banners table
ALTER TABLE `banners`
  ADD COLUMN IF NOT EXISTS `page_target` ENUM('home_page', 'other_pages', 'both') NOT NULL DEFAULT 'both' AFTER `banner_type`,
  ADD COLUMN IF NOT EXISTS `banner_subtype` ENUM('small', 'normal', 'service', 'product_detail', 'dashboard') NOT NULL DEFAULT 'normal' AFTER `page_target`,
  ADD COLUMN IF NOT EXISTS `slogan` VARCHAR(255) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN IF NOT EXISTS `show_slogan` TINYINT(1) NOT NULL DEFAULT 1 AFTER `slogan`,
  ADD COLUMN IF NOT EXISTS `extra_product_id` INT(11) NULL DEFAULT NULL AFTER `desktop_home_category_id`,
  ADD COLUMN IF NOT EXISTS `desktop_anchor_type` VARCHAR(30) NULL DEFAULT NULL AFTER `desktop_sort_order`,
  ADD COLUMN IF NOT EXISTS `desktop_anchor_id` INT(11) NULL DEFAULT NULL AFTER `desktop_anchor_type`,
  ADD COLUMN IF NOT EXISTS `desktop_anchor_position` ENUM('before', 'after') NOT NULL DEFAULT 'after' AFTER `desktop_anchor_id`,
  ADD COLUMN IF NOT EXISTS `mobile_anchor_type` VARCHAR(30) NULL DEFAULT NULL AFTER `mobile_sort_order`,
  ADD COLUMN IF NOT EXISTS `mobile_anchor_id` INT(11) NULL DEFAULT NULL AFTER `mobile_anchor_type`,
  ADD COLUMN IF NOT EXISTS `mobile_anchor_position` ENUM('before', 'after') NOT NULL DEFAULT 'after' AFTER `mobile_anchor_id`,
  ADD COLUMN IF NOT EXISTS `mobile_same_design` ENUM('yes', 'no') NOT NULL DEFAULT 'no' AFTER `mobile_design`,
  MODIFY COLUMN `desktop_sort_order` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  MODIFY COLUMN `mobile_sort_order` DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- 2. Upgrade carousels table
ALTER TABLE `carousels`
  ADD COLUMN IF NOT EXISTS `page_target` ENUM('home_page', 'other_pages', 'both') NOT NULL DEFAULT 'both' AFTER `carousel_type`,
  ADD COLUMN IF NOT EXISTS `mobile_carousel_format` VARCHAR(50) NULL DEFAULT '2.5' AFTER `mobile_home_design`,
  ADD COLUMN IF NOT EXISTS `extra_product_id` INT(11) NULL DEFAULT NULL AFTER `desktop_home_category_id`;
