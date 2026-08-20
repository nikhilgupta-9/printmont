-- ============================================================
-- Migration 012: About page — section types for content that was hardcoded
--
-- The About page kept several blocks in JSX with no way to edit them:
-- the origin story, the worldwide-delivery highlight, the three
-- "Perfect Surprise" feature cards and the statistics bar.
--
-- section_type is an enum, so those blocks could not simply be added as
-- rows. This widens it and seeds the existing copy so nothing the visitor
-- reads today is lost, and every one of them becomes editable.
--
--   story     -> "How it all started"
--   highlight -> worldwide delivery panel
--   feature   -> Perfect Surprise cards (one row each)
--   stat      -> statistics bar (one row each, title = number)
--
-- Safe to re-run: the enum change is idempotent, and the seed rows are
-- guarded so they are inserted only when that section_type has no rows.
-- ============================================================

ALTER TABLE `about_us`
  MODIFY COLUMN `section_type`
  ENUM('hero','mission','team','values','history','story','highlight','feature','stat')
  NOT NULL;

INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
  SELECT 'How it all started' AS a,
         'What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.' AS b,
         'story' AS c, 10 AS d, 1 AS e
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'story');

INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
  SELECT 'Delivering Love to 100+ Countries' AS a,
         'From bulk corporate orders to a single personalised gift, we print, pack and deliver worldwide with a zero-defect promise.' AS b,
         'highlight' AS c, 11 AS d, 1 AS e
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'highlight');

INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
            SELECT 'Thoughtful Gifts' AS a, 'Curated products chosen to suit every occasion and recipient.' AS b, 'feature' AS c, 20 AS d, 1 AS e
  UNION ALL SELECT 'Scheduled Delivery',     'Pick the date and we deliver on time, every time.',              'feature',    21,      1
  UNION ALL SELECT 'Personalised for them',  'Add names, logos and artwork to make each piece one of a kind.',  'feature',    22,      1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'feature');

INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
            SELECT '12 M+' AS a, 'deliveries worldwide' AS b, 'stat' AS c, 30 AS d, 1 AS e
  UNION ALL SELECT '400+',        'stores across India',        'stat',     31,      1
  UNION ALL SELECT '100+',        'countries served',           'stat',     32,      1
  UNION ALL SELECT '100%',        'smiles delivered',           'stat',     33,      1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'stat');
