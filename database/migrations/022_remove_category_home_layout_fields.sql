-- ============================================================
-- Migration 022: Remove Home Layout Fields from Categories Table
-- Removes desktop & mobile home page layout fields from categories
-- because home page layout is managed separately in home-layout-manager.php / home_sections
-- ============================================================

ALTER TABLE `categories`
  DROP COLUMN IF EXISTS `desktop_home_show`,
  DROP COLUMN IF EXISTS `desktop_home_design`,
  DROP COLUMN IF EXISTS `desktop_home_order`,
  DROP COLUMN IF EXISTS `desktop_bg_color`,
  DROP COLUMN IF EXISTS `desktop_bg_image`,
  DROP COLUMN IF EXISTS `desktop_image`,
  DROP COLUMN IF EXISTS `mobile_home_show`,
  DROP COLUMN IF EXISTS `mobile_home_design`,
  DROP COLUMN IF EXISTS `mobile_home_format`,
  DROP COLUMN IF EXISTS `mobile_home_order`,
  DROP COLUMN IF EXISTS `mobile_bg_color`,
  DROP COLUMN IF EXISTS `mobile_bg_image`,
  DROP COLUMN IF EXISTS `mobile_image`;
