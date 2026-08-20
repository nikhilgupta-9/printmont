-- ============================================================
-- Migration 016: Make every string on Become a Seller editable
--
-- Migration 015 made the cards, stats, stories, steps and tools
-- editable, but each band's own heading and intro sentence were still
-- written into the component, along with two button labels and the
-- enquiry form's topic list. Changing "Why do sellers love selling on
-- Printmont?" still meant editing code.
--
-- Section types added:
--
--   heading  one per band. The band it belongs to goes in `extra` as a
--            slug (why, stories, journey, tools, platform, help); the
--            dark half of the two-tone title in `title`, the coloured
--            half in `subtitle_blue` (stored in `content` after a pipe),
--            and the intro paragraph after a second pipe.
--
--            Format: "<blue half>|<intro paragraph>"
--
--            Splitting on a pipe keeps this to the existing three text
--            columns rather than widening the table for one page.
--
--   label    button and link text. Key in `extra`, caption in `title`.
--   topic    one option in the enquiry form's topic dropdown.
--
-- Safe to re-run: each block only seeds when that type has no rows.
-- ============================================================

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'heading' AS b, 'Why do' AS c,
                   'sellers love selling on Printmont?|Customers across India trust Printmont for custom prints and corporate gifting. Thousands of sellers list with us to reach them.' AS d,
                   'why' AS e, 70 AS f
  UNION ALL SELECT 'become-a-seller', 'heading', 'Seller Success',
                   'Stories|Thousands of sellers trust Printmont for their online business.',
                   'stories', 71
  UNION ALL SELECT 'become-a-seller', 'heading', 'Your Journey',
                   'on Printmont|Starting your online business with Printmont is easy.',
                   'journey', 72
  UNION ALL SELECT 'become-a-seller', 'heading', 'Access our tools to grow faster',
                   'on Printmont|Your business may need extra support from time to time, and we have you covered.',
                   'tools', 73
  UNION ALL SELECT 'become-a-seller', 'heading', 'Take a sneak peek into',
                   'our platform|',
                   'platform', 74
  UNION ALL SELECT 'become-a-seller', 'heading', 'We are happy to',
                   'help you|Still have questions left unanswered? Share your thoughts below and we will get back to you.',
                   'help', 75
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'heading');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'label' AS b, 'See All Stories' AS c, 'stories_cta' AS d, 80 AS e
  UNION ALL SELECT 'become-a-seller', 'label', 'Download Launch Kit', 'journey_cta', 81
  UNION ALL SELECT 'become-a-seller', 'label', 'Send Query',          'form_submit', 82
  UNION ALL SELECT 'become-a-seller', 'label', 'Watermark shown behind the tools grid', 'tools_watermark', 83
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'label');

UPDATE `page_sections`
SET `title` = '5x Growth'
WHERE `page_key` = 'become-a-seller' AND `section_type` = 'label' AND `extra` = 'tools_watermark';

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'topic' AS b, 'Registration' AS c, 90 AS d
  UNION ALL SELECT 'become-a-seller', 'topic', 'Listing & Catalogue', 91
  UNION ALL SELECT 'become-a-seller', 'topic', 'Orders & Shipping',   92
  UNION ALL SELECT 'become-a-seller', 'topic', 'Payments',            93
  UNION ALL SELECT 'become-a-seller', 'topic', 'Something else',      94
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'topic');
