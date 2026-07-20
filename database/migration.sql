-- PrintMont Database Schema - Missing Tables
-- Run this SQL in phpMyAdmin or MySQL CLI

USE printmont_db;

-- =====================================================
-- 1. Sliders (Home Page Sliders - Main/Small/Offer)
-- =====================================================
CREATE TABLE IF NOT EXISTS `sliders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slider_type` ENUM('main','small','offer') NOT NULL DEFAULT 'main',
  `sponsored_tag` VARCHAR(100) DEFAULT NULL,
  `desktop_design` VARCHAR(50) DEFAULT NULL,
  `desktop_image` VARCHAR(500) DEFAULT NULL,
  `desktop_url` VARCHAR(500) DEFAULT NULL,
  `desktop_sort_order` INT DEFAULT 0,
  `desktop_status` ENUM('active','inactive') DEFAULT 'active',
  `desktop_other_pages` ENUM('yes','no') DEFAULT 'no',
  `desktop_category_id` INT DEFAULT NULL,
  `desktop_carousel_id` INT DEFAULT NULL,
  `mobile_design` VARCHAR(50) DEFAULT NULL,
  `mobile_image` VARCHAR(500) DEFAULT NULL,
  `mobile_url` VARCHAR(500) DEFAULT NULL,
  `mobile_sort_order` INT DEFAULT 0,
  `mobile_status` ENUM('active','inactive') DEFAULT 'active',
  `mobile_other_pages` ENUM('yes','no') DEFAULT 'no',
  `mobile_category_id` INT DEFAULT NULL,
  `mobile_carousel_id` INT DEFAULT NULL,
  `mobile_sponsored_tag` VARCHAR(100) DEFAULT NULL,
  `auto_run_time` INT DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. Carousels (Product Carousels)
-- =====================================================
CREATE TABLE IF NOT EXISTS `carousels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) DEFAULT NULL,
  `desktop_other_pages` ENUM('yes','no') DEFAULT 'no',
  `desktop_other_category_id` INT DEFAULT NULL,
  `desktop_other_banner_id` INT DEFAULT NULL,
  `desktop_home_category_id` INT DEFAULT NULL,
  `desktop_home_design` VARCHAR(50) DEFAULT NULL,
  `desktop_bg_color` VARCHAR(20) DEFAULT NULL,
  `desktop_bg_image` VARCHAR(500) DEFAULT NULL,
  `desktop_sort_order` INT DEFAULT 0,
  `desktop_status` ENUM('active','inactive') DEFAULT 'active',
  `mobile_other_pages` ENUM('yes','no') DEFAULT 'no',
  `mobile_category_id` INT DEFAULT NULL,
  `mobile_banner_id` INT DEFAULT NULL,
  `mobile_home_show` ENUM('yes','no') DEFAULT 'no',
  `mobile_home_category_id` INT DEFAULT NULL,
  `mobile_home_design` VARCHAR(50) DEFAULT NULL,
  `mobile_other_design` VARCHAR(50) DEFAULT NULL,
  `mobile_bg_color` VARCHAR(20) DEFAULT NULL,
  `mobile_bg_image` VARCHAR(500) DEFAULT NULL,
  `mobile_sort_order` INT DEFAULT 0,
  `mobile_status` ENUM('active','inactive') DEFAULT 'active',
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_keywords` TEXT DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. Home Products (Home Page Product Sections)
-- =====================================================
CREATE TABLE IF NOT EXISTS `home_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT DEFAULT NULL,
  `desktop_home_design` VARCHAR(50) DEFAULT NULL,
  `desktop_home_carousel_id` INT DEFAULT NULL,
  `desktop_other_design` VARCHAR(50) DEFAULT NULL,
  `desktop_other_carousel_id` INT DEFAULT NULL,
  `desktop_home_show` ENUM('yes','no') DEFAULT 'yes',
  `desktop_other_show` ENUM('yes','no') DEFAULT 'no',
  `mobile_home_design` VARCHAR(50) DEFAULT NULL,
  `mobile_home_carousel_id` INT DEFAULT NULL,
  `mobile_other_design` VARCHAR(50) DEFAULT NULL,
  `mobile_other_carousel_id` INT DEFAULT NULL,
  `mobile_home_show` ENUM('yes','no') DEFAULT 'yes',
  `mobile_other_show` ENUM('yes','no') DEFAULT 'no',
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. Coupons
-- =====================================================
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `coupon_code` VARCHAR(100) NOT NULL UNIQUE,
  `category_id` INT DEFAULT NULL,
  `sub_category_id` INT DEFAULT NULL,
  `sub_sub_category_id` INT DEFAULT NULL,
  `carousel_type` VARCHAR(50) DEFAULT NULL,
  `discount_format` ENUM('percentage','fixed') DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) DEFAULT 0.00,
  `min_order_value` DECIMAL(10,2) DEFAULT 0.00,
  `quantity` INT DEFAULT 0,
  `used_count` INT DEFAULT 0,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('active','inactive','expired') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. Payment Settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gateway_name` VARCHAR(100) NOT NULL,
  `gateway_key` VARCHAR(255) DEFAULT NULL,
  `gateway_secret` VARCHAR(255) DEFAULT NULL,
  `mode` ENUM('live','test') DEFAULT 'test',
  `status` ENUM('active','inactive') DEFAULT 'inactive',
  `settings_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. COD Settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `cod_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cod_enabled` ENUM('yes','no') DEFAULT 'yes',
  `min_order_value` DECIMAL(10,2) DEFAULT 0.00,
  `max_order_value` DECIMAL(10,2) DEFAULT NULL,
  `cod_charges` DECIMAL(10,2) DEFAULT 0.00,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. Bulk Inquiries
-- =====================================================
CREATE TABLE IF NOT EXISTS `bulk_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `customer_phone` VARCHAR(20) DEFAULT NULL,
  `product_id` INT DEFAULT NULL,
  `quantity` INT DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('new','pending','completed','sold') DEFAULT 'new',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. Roles & Permissions
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL,
  `permission_key` VARCHAR(100) NOT NULL,
  `can_view` TINYINT(1) DEFAULT 0,
  `can_create` TINYINT(1) DEFAULT 0,
  `can_edit` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `role_perm` (`role_id`, `permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT IGNORE INTO `roles` (`role_name`, `description`) VALUES
('admin', 'Full access to all features'),
('manager', 'Can manage products, orders, and content'),
('staff', 'Limited access for order processing');

-- =====================================================
-- 9. Product Filters
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_filters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `filter_name` VARCHAR(100) NOT NULL,
  `filter_key` VARCHAR(100) NOT NULL UNIQUE,
  `filter_type` ENUM('single','multiple') DEFAULT 'multiple',
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_filter_values` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `filter_id` INT NOT NULL,
  `value_name` VARCHAR(255) NOT NULL,
  `value_slug` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active','inactive') DEFAULT 'active',
  FOREIGN KEY (`filter_id`) REFERENCES `product_filters`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default filter types from DOCX
INSERT IGNORE INTO `product_filters` (`filter_name`, `filter_key`, `sort_order`) VALUES
('Color', 'color', 1),
('Type', 'type', 2),
('Material', 'material', 3),
('Occasion', 'occasion', 4),
('Discount', 'discount', 5),
('Brand', 'brand', 6),
('Shape', 'shape', 7),
('Gender', 'gender', 8),
('Gift Type', 'gift_type', 9),
('Ideal For', 'ideal_for', 10),
('Customization Technology', 'customization_tech', 11),
('Customization Location', 'customization_location', 12),
('Capacity', 'capacity', 13),
('Ink Color', 'ink_color', 14),
('Features', 'features', 15);

-- =====================================================
-- 10. Gift Finder Settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `gift_finder_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` VARCHAR(50) DEFAULT 'text',
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 11. Master Modules
-- =====================================================
CREATE TABLE IF NOT EXISTS `master_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `module_type` ENUM('category','carousel','slider','banner','banner_carousel') NOT NULL,
  `module_name` VARCHAR(255) NOT NULL,
  `design_data` JSON DEFAULT NULL,
  `is_sponsored` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 12. Contact Inquiries
-- =====================================================
CREATE TABLE IF NOT EXISTS `contact_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('new','read','replied','closed') DEFAULT 'new',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 13. Site Settings (General/Favicon/Maintenance)
-- =====================================================
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_group` VARCHAR(50) NOT NULL,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('text','image','boolean','json') DEFAULT 'text',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `group_key` (`setting_group`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default site settings
INSERT IGNORE INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`, `setting_type`) VALUES
('favicon', 'favicon_image', NULL, 'image'),
('maintenance', 'is_enabled', '0', 'boolean'),
('maintenance', 'message', 'Website is under maintenance. Please check back later.', 'text'),
('error', 'error_bg_image', NULL, 'image'),
('content', 'website_name', 'PrintMont', 'text'),
('content', 'tagline', 'Your Custom Printing Partner', 'text');

-- =====================================================
-- 14. Banner Carousels
-- =====================================================
CREATE TABLE IF NOT EXISTS `banner_carousels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `desktop_home_category_id` INT DEFAULT NULL,
  `desktop_home_carousel_id` INT DEFAULT NULL,
  `desktop_design` VARCHAR(50) DEFAULT NULL,
  `desktop_image` VARCHAR(500) DEFAULT NULL,
  `desktop_url` VARCHAR(500) DEFAULT NULL,
  `desktop_status` ENUM('active','inactive') DEFAULT 'active',
  `desktop_sort_order` INT DEFAULT 0,
  `desktop_start_date` DATE DEFAULT NULL,
  `desktop_end_date` DATE DEFAULT NULL,
  `mobile_design` VARCHAR(50) DEFAULT NULL,
  `mobile_carousel_id` INT DEFAULT NULL,
  `mobile_other_pages` ENUM('yes','no') DEFAULT 'no',
  `mobile_image` VARCHAR(500) DEFAULT NULL,
  `mobile_url` VARCHAR(500) DEFAULT NULL,
  `mobile_status` ENUM('active','inactive') DEFAULT 'active',
  `mobile_sort_order` INT DEFAULT 0,
  `mobile_other_sort_order` INT DEFAULT 0,
  `mobile_start_date` DATE DEFAULT NULL,
  `mobile_end_date` DATE DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 15. Watermarks
-- =====================================================
CREATE TABLE IF NOT EXISTS `watermarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 16. Home Sliders (alternative table name)
-- =====================================================
CREATE TABLE IF NOT EXISTS `home_sliders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slider_id` INT DEFAULT NULL,
  `section_type` VARCHAR(50) DEFAULT 'main',
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active','inactive') DEFAULT 'active',
  FOREIGN KEY (`slider_id`) REFERENCES `sliders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
