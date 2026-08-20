-- ============================================================
-- Migration 013: Accolades as editable About sections
--
-- The awards row was previously either hand-drawn inline SVG (43 <path>
-- elements building laurel wreaths, with the award names in a JS array)
-- or an uploaded banner image. Neither let the awards be edited as text.
--
-- This adds an 'accolade' section type so each award is an ordinary row:
-- section_title holds the award name, and the laurel wreaths are drawn
-- once in CSS/SVG around whatever text the row carries.
--
-- Safe to re-run: the enum change is idempotent and the seed only fires
-- when no accolade rows exist yet.
-- ============================================================

ALTER TABLE `about_us`
  MODIFY COLUMN `section_type`
  ENUM('hero','mission','team','values','history','story','highlight','feature','stat','accolade')
  NOT NULL;

INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
            SELECT '2025 Pitch Top 50 Brands India' AS a, '' AS b, 'accolade' AS c, 40 AS d, 1 AS e
  UNION ALL SELECT 'Future of Workplace & Leadership Award', '', 'accolade', 41, 1
  UNION ALL SELECT 'Top 100 Franchise Opportunities',        '', 'accolade', 42, 1
  UNION ALL SELECT 'National Excellence in Digital Printing','', 'accolade', 43, 1
  UNION ALL SELECT 'Best Gifting e-Retailer of the Year',    '', 'accolade', 44, 1
  UNION ALL SELECT 'Corporate Merchandise Leader of the Year','', 'accolade', 45, 1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'accolade');
