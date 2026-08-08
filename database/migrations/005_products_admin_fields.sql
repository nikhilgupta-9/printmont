-- ============================================================
-- Migration 005: Align `products` table with the admin edit/add
-- product form (12-tab UI in edit-product.php / add-product.php)
--
-- The admin controller/model (ProductModel::$allowedColumns) was built
-- against a richer schema than what actually exists in `products`.
-- Saving an edit failed with "Unknown column 'product_slug' in field
-- list" -- and would have failed again on the next mismatched field
-- (minimum_quantity, show_customization_label, cancel_available, ...)
-- one save at a time.
--
-- This migration:
--   1. Renames existing columns to the names the admin code expects,
--      where the column already holds the right data.
--   2. Adds new columns for fields the form has that the table never
--      had at all (filters, sub-categories, thumbnail, gallery, ...).
--   3. Converts cancel/cod availability from enum('yes','no') to a
--      real boolean, and normalizes cancel_type / status values.
--
-- Columns that already matched (name, description, category_id, brand,
-- price, regular_price, offer_price, discount_price, sort_order,
-- stock_quantity, out_of_stock_status, sku, status, featured,
-- top_selection, our_bestseller, top_rated, top_deal_by_categories,
-- view_count, meta_title, meta_keywords, meta_description, alt_tag,
-- long_description, instructions, delivery_info, show_help_button,
-- show_bulk_form, addon_product_ids, shipping_method_status,
-- created_at, updated_at) are left untouched.
--
-- Old columns that had no admin-form equivalent (image, quantity's
-- sibling stock_quantity, top_deal, printing_location, cancel_status,
-- cod_status) are left in place rather than dropped, so nothing that
-- still reads them breaks; the app just stops writing to them.
--
-- Not idempotent for the RENAME steps (MariaDB 10.4 has no
-- "CHANGE COLUMN IF EXISTS") -- guarded with dynamic SQL instead so
-- it's still safe to re-run.
-- ============================================================

-- ---- STEP 1: renames (same data, new name) ------------------------

DELIMITER $$
CREATE PROCEDURE _mig005_rename_if_needed()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'slug') THEN
        ALTER TABLE `products` CHANGE COLUMN `slug` `product_slug` VARCHAR(255) NOT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'quantity') THEN
        ALTER TABLE `products` CHANGE COLUMN `quantity` `product_quantity` INT(11) NULL DEFAULT 0;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'min_quantity') THEN
        ALTER TABLE `products` CHANGE COLUMN `min_quantity` `minimum_quantity` INT(11) NULL DEFAULT 1;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'bulk_order_pricing') THEN
        ALTER TABLE `products` CHANGE COLUMN `bulk_order_pricing` `bulk_price_variance` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'show_left_quantity') THEN
        ALTER TABLE `products` CHANGE COLUMN `show_left_quantity` `show_quantity` TINYINT(1) NULL DEFAULT 0;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'customization_label_status') THEN
        ALTER TABLE `products` CHANGE COLUMN `customization_label_status` `show_customization_label` TINYINT(1) NULL DEFAULT 0;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'customization_data') THEN
        ALTER TABLE `products` CHANGE COLUMN `customization_data` `customization_fields` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'size_status') THEN
        ALTER TABLE `products` CHANGE COLUMN `size_status` `show_sizes` TINYINT(1) NULL DEFAULT 0;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'sizes') THEN
        ALTER TABLE `products` CHANGE COLUMN `sizes` `size_attributes` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'colors') THEN
        ALTER TABLE `products` CHANGE COLUMN `colors` `color_attributes` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'materials') THEN
        ALTER TABLE `products` CHANGE COLUMN `materials` `material_attributes` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'lamination') THEN
        ALTER TABLE `products` CHANGE COLUMN `lamination` `lamination_attributes` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'orientation') THEN
        ALTER TABLE `products` CHANGE COLUMN `orientation` `orientation_attributes` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'quantity_pricing') THEN
        ALTER TABLE `products` CHANGE COLUMN `quantity_pricing` `quantity_price_breaks` LONGTEXT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'local_shipping_price') THEN
        ALTER TABLE `products` CHANGE COLUMN `local_shipping_price` `local_shipping_charge` DECIMAL(10,2) NULL DEFAULT 0.00;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'regional_shipping_price') THEN
        ALTER TABLE `products` CHANGE COLUMN `regional_shipping_price` `regional_shipping_charge` DECIMAL(10,2) NULL DEFAULT 0.00;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'national_shipping_price') THEN
        ALTER TABLE `products` CHANGE COLUMN `national_shipping_price` `national_shipping_charge` DECIMAL(10,2) NULL DEFAULT 0.00;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'regional_shipping_msg') THEN
        ALTER TABLE `products` CHANGE COLUMN `regional_shipping_msg` `regional_shipping_message` VARCHAR(255) NULL DEFAULT '';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'national_shipping_msg') THEN
        ALTER TABLE `products` CHANGE COLUMN `national_shipping_msg` `national_shipping_message` VARCHAR(255) NULL DEFAULT '';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'cancel_value') THEN
        ALTER TABLE `products` CHANGE COLUMN `cancel_value` `cancel_time` INT(11) NULL DEFAULT 24;
    END IF;
END$$
DELIMITER ;

CALL _mig005_rename_if_needed();
DROP PROCEDURE _mig005_rename_if_needed;

-- ---- STEP 2: new columns the form has but the table never did -----

ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `sub_category_id`                  INT(11)       NULL                          COMMENT 'FK categories.id, level 2',
  ADD COLUMN IF NOT EXISTS `sub_sub_category_id`               INT(11)       NULL                          COMMENT 'FK categories.id, level 3',
  ADD COLUMN IF NOT EXISTS `homepage_category_id`              INT(11)       NULL                          COMMENT 'FK categories.id, homepage grouping',
  ADD COLUMN IF NOT EXISTS `homepage_carousel_id`               INT(11)       NULL,
  ADD COLUMN IF NOT EXISTS `additional_category_ids`            TEXT          NULL                          COMMENT 'JSON array of extra category ids',
  ADD COLUMN IF NOT EXISTS `additional_sub_category_ids`        TEXT          NULL                          COMMENT 'JSON array of extra sub-category ids',
  ADD COLUMN IF NOT EXISTS `additional_sub_sub_category_ids`    TEXT          NULL                          COMMENT 'JSON array of extra sub-sub-category ids',
  ADD COLUMN IF NOT EXISTS `thumbnail_image`                   VARCHAR(255)  NULL,
  ADD COLUMN IF NOT EXISTS `gallery_images`                     LONGTEXT      NULL                          COMMENT 'JSON array of gallery image paths',
  ADD COLUMN IF NOT EXISTS `color`                              VARCHAR(100)  NULL                          COMMENT 'Filter/tag value, distinct from color_attributes',
  ADD COLUMN IF NOT EXISTS `type`                               VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `material`                           VARCHAR(100)  NULL                          COMMENT 'Filter/tag value, distinct from material_attributes',
  ADD COLUMN IF NOT EXISTS `occasion`                           VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `discount_type`                      VARCHAR(50)   NULL,
  ADD COLUMN IF NOT EXISTS `shape`                               VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `gender`                              VARCHAR(20)   NULL,
  ADD COLUMN IF NOT EXISTS `gift_type`                           VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `ideal_for`                           VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `customization_tech`                  VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `customization_location`              VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `capacity`                            VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `ink_color`                           VARCHAR(100)  NULL,
  ADD COLUMN IF NOT EXISTS `features`                            TEXT          NULL,
  ADD COLUMN IF NOT EXISTS `help_button_text`                    VARCHAR(255)  NULL DEFAULT 'Need Help?',
  ADD COLUMN IF NOT EXISTS `help_button_link`                    VARCHAR(255)  NULL DEFAULT '/contact-us',
  ADD COLUMN IF NOT EXISTS `customization_label`                 VARCHAR(255)  NULL,
  ADD COLUMN IF NOT EXISTS `show_colors`                         TINYINT(1)    NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `local_shipping_message`              VARCHAR(255)  NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `cancel_available`                    TINYINT(1)    NOT NULL DEFAULT 1           COMMENT 'Replaces cancel_status enum',
  ADD COLUMN IF NOT EXISTS `cod_available`                       TINYINT(1)    NOT NULL DEFAULT 1           COMMENT 'Replaces cod_status enum';

-- Backfill the new booleans from the old yes/no enums (old columns kept, unused going forward)
UPDATE `products` SET `cancel_available` = IF(`cancel_status` = 'yes', 1, 0);
UPDATE `products` SET `cod_available`    = IF(`cod_status` = 'yes', 1, 0);

-- ---- STEP 3: normalize enum/value mismatches -----------------------

-- cancel_type was enum('hour','day'); the form submits 'hours'/'days'.
ALTER TABLE `products` MODIFY COLUMN `cancel_type` VARCHAR(10) NOT NULL DEFAULT 'hours';
UPDATE `products` SET `cancel_type` = 'hours' WHERE `cancel_type` = 'hour';
UPDATE `products` SET `cancel_type` = 'days'  WHERE `cancel_type` = 'day';

-- status was enum('active','inactive','out_of_stock'); the form also offers 'draft'.
ALTER TABLE `products` MODIFY COLUMN `status` ENUM('active','inactive','draft','out_of_stock') NOT NULL DEFAULT 'active';

-- VERIFY
-- DESCRIBE products;
