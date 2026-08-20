-- ============================================================
-- Migration 014: Editable sections for the Affiliate Program and
-- Business Solutions pages
--
-- Both pages were entirely hardcoded in JSX: the affiliate page held four
-- inline arrays (highlights, steps, commission rates, FAQs) and Business
-- Solutions had its copy written straight into the markup. Nothing on
-- either page could be changed without a developer.
--
-- website_pages already exists but stores a single content blob per page,
-- which cannot express a commission table or a numbered step list. This
-- table keeps the same generic idea but one row per section, mirroring the
-- shape about_us already uses successfully.
--
--   page_key     'affiliate' | 'business-solutions'
--   section_type hero | highlight | step | rate | faq | feature | cta
--   title        heading, question, category name, or step title
--   content      body copy, answer, or commission rate
--   extra        secondary value (e.g. the rate against a category)
--
-- Safe to re-run: CREATE TABLE IF NOT EXISTS, and the seeds only fire when
-- that page has no rows.
-- ============================================================

CREATE TABLE IF NOT EXISTS `page_sections` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `page_key`      VARCHAR(64)  NOT NULL,
  `section_type`  VARCHAR(32)  NOT NULL,
  `title`         VARCHAR(255) NOT NULL DEFAULT '',
  `content`       TEXT         NULL,
  `extra`         VARCHAR(255) NULL,
  `image_path`    VARCHAR(500) NULL,
  `display_order` INT(11)      NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_sections_page` (`page_key`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Affiliate Program ----------
INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
  SELECT 'affiliate' AS a, 'hero' AS b, 'Printmont Affiliate Program' AS c,
         'Partner with Printmont and earn commission on every custom print and corporate gift order you refer.' AS d,
         'Join for free' AS e, 1 AS f
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'affiliate');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'affiliate' AS a, 'highlight' AS b, 'Up to 15% Commission' AS c, 'Earn high competitive commissions on every completed purchase generated through your referral links.' AS d, 10 AS e
  UNION ALL SELECT 'affiliate', 'highlight', '30-Day Cookie Window', 'Long 30-day tracking cookie ensures you earn credit even if a customer buys weeks after clicking your link.', 11
  UNION ALL SELECT 'affiliate', 'highlight', 'Monthly Timely Payouts', 'Hassle-free monthly commission disbursements directly to your verified Indian bank account or UPI.', 12
  UNION ALL SELECT 'affiliate', 'highlight', 'Free Banners & Coupons', 'Access exclusive high-converting promotional banners, text links, and custom discount coupon codes.', 13
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'affiliate' AND `section_type` = 'highlight');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'affiliate' AS a, 'step' AS b, 'Sign Up for Free' AS c, 'Register in minutes and get your unique referral link once approved.' AS d, 20 AS e
  UNION ALL SELECT 'affiliate', 'step', 'Promote & Share', 'Share your link on your site, blog or social channels using our banners.', 21
  UNION ALL SELECT 'affiliate', 'step', 'Earn & Get Paid', 'Track conversions and receive monthly payouts to your bank or UPI.', 22
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'affiliate' AND `section_type` = 'step');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'affiliate' AS a, 'rate' AS b, 'Custom Apparel & T-Shirts' AS c, '12% - 15%' AS d, 30 AS e
  UNION ALL SELECT 'affiliate', 'rate', 'Corporate Gift Sets & Executive Diaries', '10% - 12%', 31
  UNION ALL SELECT 'affiliate', 'rate', 'Drinkware, Mugs & Steel Bottles', '10%', 32
  UNION ALL SELECT 'affiliate', 'rate', 'Marketing Print & Business Cards', '8% - 10%', 33
  UNION ALL SELECT 'affiliate', 'rate', 'Other Personalized Accessories', '8%', 34
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'affiliate' AND `section_type` = 'rate');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'affiliate' AS a, 'faq' AS b, 'Is there any cost to join the Printmont Affiliate Program?' AS c, 'No. Joining is completely free and there are no hidden charges at any stage.' AS d, 40 AS e
  UNION ALL SELECT 'affiliate', 'faq', 'How are my sales and referral conversions tracked?', 'Every affiliate gets a unique referral link. Clicks and completed orders are tracked against it for 30 days.', 41
  UNION ALL SELECT 'affiliate', 'faq', 'When and how do I get paid my commissions?', 'Commissions are paid monthly to your verified bank account or UPI once the minimum payout is reached.', 42
  UNION ALL SELECT 'affiliate', 'faq', 'Can I promote Printmont on social media platforms?', 'Yes. You may promote on Instagram, YouTube, blogs and other channels using our approved banners and links.', 43
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'affiliate' AND `section_type` = 'faq');

-- ---------- Business Solutions ----------
INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
  SELECT 'business-solutions' AS a, 'hero' AS b, 'Printmont Business Solutions' AS c,
         'Corporate gifting, branded merchandise and bulk print, delivered end to end for teams of any size.' AS d,
         'Talk to our team' AS e, 1 AS f
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'business-solutions');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'business-solutions' AS a, 'feature' AS b, 'Corporate Gifting' AS c, 'Curated hampers and executive gift sets for clients, partners and employees.' AS d, 10 AS e
  UNION ALL SELECT 'business-solutions', 'feature', 'Branded Merchandise', 'Custom apparel, drinkware and accessories carrying your logo and brand colours.', 11
  UNION ALL SELECT 'business-solutions', 'feature', 'Employee Onboarding Kits', 'Welcome kits assembled, packed and shipped to each new joiner directly.', 12
  UNION ALL SELECT 'business-solutions', 'feature', 'Bulk Print & Stationery', 'Business cards, brochures and marketing collateral at volume pricing.', 13
  UNION ALL SELECT 'business-solutions', 'feature', 'Event & Trade Show Kits', 'Giveaways, signage and promotional packs prepared for your event dates.', 14
  UNION ALL SELECT 'business-solutions', 'feature', 'Dedicated Account Manager', 'A single point of contact handling quotes, artwork approval and delivery.', 15
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'business-solutions' AND `section_type` = 'feature');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'business-solutions' AS a, 'step' AS b, 'Share your requirement' AS c, 'Tell us quantities, budget and timelines and we prepare a quote.' AS d, 20 AS e
  UNION ALL SELECT 'business-solutions', 'step', 'Approve the sample', 'We share artwork mockups and a physical sample where needed.', 21
  UNION ALL SELECT 'business-solutions', 'step', 'Bulk production', 'Your order is produced with quality checks at every stage.', 22
  UNION ALL SELECT 'business-solutions', 'step', 'Delivered to your door', 'Single or multi-location dispatch, tracked all the way.', 23
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'business-solutions' AND `section_type` = 'step');
