-- ============================================================
-- Migration 001: Banner Sections Refactor
-- Run this on your database: u950539402_print_bkend_db
-- Safe to run multiple times (uses IF NOT EXISTS / IF EXISTS)
-- ============================================================

-- STEP 1: Create the new banner_sections table
-- This replaces the free-text `position` field with a proper table.
-- Admin can set columns_per_row (1/2/3/4) and is_slider per section.
-- ============================================================

CREATE TABLE IF NOT EXISTS `banner_sections` (
  `id`              int(11)       NOT NULL AUTO_INCREMENT,
  `page`            varchar(50)   NOT NULL COMMENT 'home | blog | product | cart | checkout',
  `section_key`     varchar(80)   NOT NULL COMMENT 'unique key used by frontend API',
  `label`           varchar(120)  NOT NULL COMMENT 'human-readable label shown in admin',
  `columns_per_row` tinyint(1)    NOT NULL DEFAULT 1 COMMENT '1=full width, 2=2-col, 3=3-col, 4=4-col',
  `is_slider`       tinyint(1)    NOT NULL DEFAULT 0 COMMENT '1=carousel/slider, 0=static grid',
  `display_order`   int(11)       NOT NULL DEFAULT 0,
  `status`          enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`      timestamp     NOT NULL DEFAULT current_timestamp(),
  `updated_at`      timestamp     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- STEP 2: Seed all sections (matching every position currently in banners table)
-- Adjust columns_per_row / is_slider to match what your frontend actually renders.
-- ============================================================

INSERT IGNORE INTO `banner_sections`
  (`page`, `section_key`, `label`, `columns_per_row`, `is_slider`, `display_order`) VALUES

-- HOME PAGE
('home', 'home_hero',                      'Home – Hero Slider (Full Width)',              1, 1,  1),
('home', 'home_above_fold',                'Home – Above Fold (4 Columns)',                4, 0,  2),
('home', 'home_mid_section_1',             'Home – Mid Section 1 (3 Columns)',             3, 0,  3),
('home', 'home_mid_section_2',             'Home – Mid Section 2 (Multi Banners)',         1, 0,  4),
('home', 'home_mid_section_3',             'Home – Mid Section 3 (Single Banner)',         1, 0,  5),
('home', 'home_below_selection',           'Home – Below Selection (2 Columns)',           2, 0,  6),
('home', 'home_after_discount',            'Home – After Discount (3 Columns)',            3, 0,  7),
('home', 'home_after_rated',               'Home – After Top Rated (3 Columns)',           3, 0,  8),
('home', 'home_after_women_cloths',        'Home – After Women Clothes (1 Column)',        1, 0,  9),
('home', 'home_after_top_deal_categories', 'Home – After Top Deals & Categories (3 Col)', 3, 0, 10),
('home', 'home_after_men_cloths',          'Home – Men Clothes Section (1 Column)',        1, 0, 11),
('home', 'home_after_men_cloths2',         'Home – After Men Clothes (1 Column)',          1, 0, 12),
('home', 'home_after_top_selection',       'Home – After Top Selection (1 Column)',        1, 0, 13),
('home', 'home_after_cloths',              'Home – After Clothes (2 Columns)',             2, 0, 14),
('home', 'home_after_electronics_item',    'Home – After Electronics (3 Columns)',         3, 0, 15),
('home', 'home_after_table_dinner_ware1',  'Home – After Table & Dinnerware 1 (1 Col)',    1, 0, 16),
('home', 'home_after_table_dinner_ware2',  'Home – After Table & Dinnerware 2 (2 Col)',    2, 0, 17),
('home', 'home_after_table_dinner_ware3',  'Home – After Table & Dinnerware 3 (1 Col)',    1, 0, 18),
('home', 'home_after_home_decor',          'Home – After Home Decor (2 Columns)',          2, 0, 19),

-- OTHER PAGES
('blog',     'blog_page_banner',     'Blog Page – Top Banner (Full Width)',    1, 0, 1),
('product',  'product_page_middle',  'Product Page – Middle Banner (1 Col)',   1, 0, 1),
('product',  'product_page_top',     'Product Page – Top Banner (Full Width)', 1, 0, 2),
('cart',     'cart_page',            'Cart Page – Banner (Full Width)',        1, 0, 1),
('checkout', 'checkout_top',         'Checkout Page – Top Banner (Full Width)',1, 0, 1);


-- STEP 3: Add section_id column to banners table (if not already there)
-- ============================================================

ALTER TABLE `banners`
  ADD COLUMN IF NOT EXISTS `section_id` int(11) DEFAULT NULL
    COMMENT 'FK to banner_sections.id — replaces the position string',
  ADD KEY IF NOT EXISTS `idx_section_id` (`section_id`);


-- STEP 4: Populate section_id from existing position values
-- Fixes the typo: home_after_home_desocre → home_after_home_decor
-- ============================================================

UPDATE `banners` b
JOIN `banner_sections` s ON s.section_key = b.position
SET b.section_id = s.id
WHERE b.section_id IS NULL;

-- Handle the typo in existing data
UPDATE `banners` b
JOIN `banner_sections` s ON s.section_key = 'home_after_home_decor'
SET b.section_id = s.id, b.position = 'home_after_home_decor'
WHERE b.position = 'home_after_home_desocre';


-- STEP 5: Drop unused tables (empty — never actually used)
-- Comment these out if you want to keep them for now.
-- ============================================================

DROP TABLE IF EXISTS `banner_assignments`;
DROP TABLE IF EXISTS `banner_layouts`;
DROP TABLE IF EXISTS `home_sliders`;


-- STEP 6: Add FK constraint (optional — run after verifying data is clean)
-- ============================================================

-- ALTER TABLE `banners`
--   ADD CONSTRAINT `fk_banners_section`
--   FOREIGN KEY (`section_id`) REFERENCES `banner_sections` (`id`)
--   ON DELETE SET NULL ON UPDATE CASCADE;


-- VERIFY: Check all banners have a section_id
-- SELECT id, title, position, section_id FROM banners WHERE section_id IS NULL;
