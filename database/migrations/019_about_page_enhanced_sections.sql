-- ============================================================
-- Migration 019: About Page — Flexible Section Types & Full Layout Support
--
-- Widens section_type from strict ENUM to VARCHAR(64) so admins can add
-- custom rows, carousels, split sections, banners, slogans, and rich text blocks.
-- ============================================================

ALTER TABLE `about_us`
  MODIFY COLUMN `section_type` VARCHAR(64) NOT NULL DEFAULT 'story';

-- Ensure 'story' section has the exact heading & copy from wireframe
UPDATE `about_us`
SET `section_title` = 'How it all started',
    `section_content` = 'What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.'
WHERE `section_type` = 'story' AND `id` = 6;

-- Ensure 'highlight' section matches wireframe
UPDATE `about_us`
SET `section_title` = 'Delivering Love to 100+ Countries',
    `section_content` = 'From bulk corporate orders to a single personalised gift, we print, pack and deliver worldwide with a zero-defect promise.'
WHERE `section_type` = 'highlight' AND `id` = 7;

-- Ensure Vision & Mission text matches wireframe
UPDATE `about_us`
SET `section_title` = 'Our Vision',
    `section_content` = 'Founded in 2008 by printing industry veterans, Printmont started as a small local print shop with a big vision. What began with a single printing press and a commitment to quality has grown into a full-service printing company serving clients nationwide. Our journey has been marked by continuous innovation, expanding our services to include digital printing, large format printing, and custom packaging solutions.'
WHERE `section_type` = 'history';

UPDATE `about_us`
SET `section_title` = 'Our Mission',
    `section_content` = 'Our mission is to empower businesses and individuals with high-quality printing services that exceed expectations. We believe that great printing can transform ideas into powerful communications. Through cutting-edge technology, sustainable practices, and unparalleled customer service, we strive to be the preferred printing partner for clients across all industries.'
WHERE `section_type` = 'mission' AND `id` = 2;

-- Insert Normal Text block if not present
INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
  SELECT 'Highlight Text (Normal Text Pages.)' AS a,
         'Founded in 2008 by printing industry veterans, Printmont started as a small local print shop with a big vision. What began with a single printing press and a commitment to quality has grown into a full-service printing company serving clients nationwide. Our journey has been marked by continuous innovation, expanding our services to include digital printing, large format printing, and custom packaging solutions.<br><br>Over the years, Printmont has invested heavily in state-of-the-art technology and infrastructure, including high-speed industrial presses, specialized garment printing stations, laser engraving, UV flatbeds, and automated quality assurance pipelines.<br><br>Today, we proudly serve thousands of startups, growing enterprises, leading multinational corporations, educational institutions, and creative retail brands across all pincodes in India and internationally.' AS b,
         'normal_text' AS c, 25 AS d, 1 AS e
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'normal_text');

-- Insert slogan highlights if not present
INSERT INTO `about_us` (`section_title`, `section_content`, `section_type`, `display_order`, `is_active`)
SELECT * FROM (
  SELECT 'Highlights Slogn Text.' AS a,
         'What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.' AS b,
         'slogan' AS c, 12 AS d, 1 AS e
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `about_us` WHERE `section_type` = 'slogan');

-- Update stats to match 1M+, 20,000+, 7 Years as shown in proposition footer
UPDATE `about_us` SET `section_title` = '1 Million+', `section_content` = 'Custom Gifts & Orders Delivered' WHERE `section_type` = 'stat' AND `id` = 11;
UPDATE `about_us` SET `section_title` = '20,000+', `section_content` = 'Happy Clients & Printing Partners' WHERE `section_type` = 'stat' AND `id` = 12;
UPDATE `about_us` SET `section_title` = '7 Years', `section_content` = 'Of Excellence & Innovation' WHERE `section_type` = 'stat' AND `id` = 13;
