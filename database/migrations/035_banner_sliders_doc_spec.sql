-- Migration 035: Document 6 - Home Pages Banner Slider Settings Specification
-- Adds Document 6 fields for Banner Sliders / Carousels:
-- - slider_subtype: top_slider, small_slider, offer_slider, service_slider, product_detail_slider, dashboard_slider
-- - page_target: home_page, other_pages, both
-- - text_tag_name: tag text name
-- - extra_product_id: Home page extra featured product reference
-- - desktop/mobile relative positioning anchors: anchor_type, anchor_id, anchor_position, decimal sort order
-- - mobile_same_design: yes/no toggle
-- - service_slider dual types: with_printing and without_printing images (desktop & mobile)
-- - product_detail_slider placement flags (home category, carousel, product toggles)

ALTER TABLE `banner_carousels`
  ADD COLUMN IF NOT EXISTS `slider_subtype` ENUM('top_slider','small_slider','offer_slider','service_slider','product_detail_slider','dashboard_slider') DEFAULT 'top_slider' AFTER `title`,
  ADD COLUMN IF NOT EXISTS `page_target` ENUM('home_page','other_pages','both') DEFAULT 'both' AFTER `slider_subtype`,
  ADD COLUMN IF NOT EXISTS `text_tag_name` VARCHAR(150) NULL AFTER `page_target`,
  ADD COLUMN IF NOT EXISTS `extra_product_id` INT NULL AFTER `text_tag_name`,
  ADD COLUMN IF NOT EXISTS `desktop_anchor_type` VARCHAR(30) NULL AFTER `extra_product_id`,
  ADD COLUMN IF NOT EXISTS `desktop_anchor_id` INT NULL AFTER `desktop_anchor_type`,
  ADD COLUMN IF NOT EXISTS `desktop_anchor_position` ENUM('before','after') DEFAULT 'after' AFTER `desktop_anchor_id`,
  ADD COLUMN IF NOT EXISTS `mobile_anchor_type` VARCHAR(30) NULL AFTER `desktop_anchor_position`,
  ADD COLUMN IF NOT EXISTS `mobile_anchor_id` INT NULL AFTER `mobile_anchor_type`,
  ADD COLUMN IF NOT EXISTS `mobile_anchor_position` ENUM('before','after') DEFAULT 'after' AFTER `mobile_anchor_id`,
  ADD COLUMN IF NOT EXISTS `mobile_same_design` ENUM('yes','no') DEFAULT 'no' AFTER `mobile_anchor_position`,
  ADD COLUMN IF NOT EXISTS `desktop_with_printing_image` VARCHAR(500) NULL AFTER `desktop_images`,
  ADD COLUMN IF NOT EXISTS `desktop_without_printing_image` VARCHAR(500) NULL AFTER `desktop_with_printing_image`,
  ADD COLUMN IF NOT EXISTS `mobile_with_printing_image` VARCHAR(500) NULL AFTER `mobile_images`,
  ADD COLUMN IF NOT EXISTS `mobile_without_printing_image` VARCHAR(500) NULL AFTER `mobile_with_printing_image`,
  ADD COLUMN IF NOT EXISTS `show_product_category_carousel` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `show_home_category` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `show_category_carousel` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `show_home_product` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `mobile_show_product_category_carousel` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `mobile_show_home_category` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `mobile_show_category_carousel` ENUM('yes','no') DEFAULT 'yes',
  ADD COLUMN IF NOT EXISTS `mobile_show_home_product` ENUM('yes','no') DEFAULT 'yes',
  MODIFY COLUMN `desktop_sort_order` DECIMAL(10,2) DEFAULT 0.00,
  MODIFY COLUMN `mobile_sort_order` DECIMAL(10,2) DEFAULT 0.00;
