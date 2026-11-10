-- Migration 036: Master Banner & Carousel Spec
-- Adds alt_text, SEO fields (meta_title, meta_keywords, meta_description), target_page, and other_pages_display flags

-- 1. Table: banners
ALTER TABLE `banners`
  ADD COLUMN `alt_text` VARCHAR(255) NULL AFTER `title`,
  ADD COLUMN `meta_title` VARCHAR(255) NULL,
  ADD COLUMN `meta_keywords` TEXT NULL,
  ADD COLUMN `meta_description` TEXT NULL,
  ADD COLUMN `target_page` VARCHAR(50) DEFAULT 'home',
  ADD COLUMN `other_pages_display` TEXT NULL,
  ADD COLUMN `show_on_category` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_product` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_product_detail` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_franchise` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_affiliate` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_career` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_business_solutions` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_advertising` ENUM('yes','no') DEFAULT 'no';

-- 2. Table: carousels
ALTER TABLE `carousels`
  ADD COLUMN `alt_text` VARCHAR(255) NULL AFTER `title`,
  ADD COLUMN `description` TEXT NULL AFTER `alt_text`,
  ADD COLUMN `target_page` VARCHAR(50) DEFAULT 'home',
  ADD COLUMN `other_pages_display` TEXT NULL,
  ADD COLUMN `show_on_category` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_product` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_product_detail` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_franchise` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_affiliate` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_career` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_business_solutions` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_advertising` ENUM('yes','no') DEFAULT 'no';

-- 3. Table: banner_carousels
ALTER TABLE `banner_carousels`
  ADD COLUMN `alt_text` VARCHAR(255) NULL AFTER `title`,
  ADD COLUMN `description` TEXT NULL AFTER `alt_text`,
  ADD COLUMN `meta_title` VARCHAR(255) NULL,
  ADD COLUMN `meta_keywords` TEXT NULL,
  ADD COLUMN `meta_description` TEXT NULL,
  ADD COLUMN `target_page` VARCHAR(50) DEFAULT 'home',
  ADD COLUMN `other_pages_display` TEXT NULL,
  ADD COLUMN `show_on_category` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_product` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_product_detail` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_franchise` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_affiliate` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_career` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_business_solutions` ENUM('yes','no') DEFAULT 'no',
  ADD COLUMN `show_on_advertising` ENUM('yes','no') DEFAULT 'no';
