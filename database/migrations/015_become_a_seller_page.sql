-- ============================================================
-- Migration 015: Become a Seller page content
--
-- The seller page was 288 lines of hardcoded JSX with no API calls, so
-- none of its copy, benefit cards, steps or FAQs could be edited.
--
-- Reuses the page_sections table from migration 014 under the
-- 'become-a-seller' key, adding the section types the layout needs:
--
--   hero     headline + sub-line, image_path for the banner
--   stat     figure in title, label in content  (the strip under the hero)
--   benefit  "why sell with us" cards
--   story    seller testimonial: name in title, quote in content,
--            company in extra
--   journey  numbered journey step
--   tool     growth tool card, "Learn More" href in extra
--   platform platform highlight slide
--
-- Safe to re-run: each block only seeds when that page/type has no rows.
-- ============================================================

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
  SELECT 'become-a-seller' AS a, 'hero' AS b, 'Sell Online on Printmont' AS c,
         'Grow your business with India''s trusted custom print and corporate gifting marketplace.' AS d,
         'Start Selling' AS e, 1 AS f
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'hero');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'stat' AS b, '10,000+' AS c, 'Seller community' AS d, 10 AS e
  UNION ALL SELECT 'become-a-seller', 'stat', '24×7',    'Online business',  11
  UNION ALL SELECT 'become-a-seller', 'stat', '7',       'days* payment',    12
  UNION ALL SELECT 'become-a-seller', 'stat', '19000+',  'Pincodes served',  13
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'stat');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'benefit' AS b, 'Opportunity' AS c, 'Reach customers across 19000+ pincodes and get access to festive campaigns and corporate bulk demand.' AS d, 20 AS e
  UNION ALL SELECT 'become-a-seller', 'benefit', 'Ease of Doing Business', 'Create your seller account in under 10 minutes with one product and a valid GSTIN.', 21
  UNION ALL SELECT 'become-a-seller', 'benefit', 'Growth', 'Sellers see higher visibility during campaign periods, with dedicated promotion slots.', 22
  UNION ALL SELECT 'become-a-seller', 'benefit', 'Additional Support', 'Account management, training, catalogue and photoshoot support whenever you need it.', 23
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'benefit');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'story' AS b, 'Ekta Shah' AS c, 'From 5 to 40+ brands, seamless registration and account manager guidance fuelled our growth in beauty and grooming.' AS d, 'Glide Route Ventures' AS e, 30 AS f
  UNION ALL SELECT 'become-a-seller', 'story', 'Rahul Sharma', 'Corporate bulk orders now make up most of our revenue. Onboarding took a single afternoon.', 'Sharma Prints', 31
  UNION ALL SELECT 'become-a-seller', 'story', 'Neha Kapoor', 'The catalogue support team helped us shoot and list 200 products in our first month.', 'Kapoor Merchandise', 32
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'story');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'journey' AS b, 'Create' AS c, 'Register in 10 minutes with a valid GST, address and bank details.' AS d, 40 AS e
  UNION ALL SELECT 'become-a-seller', 'journey', 'List',     'List the products (minimum 1) you want to sell on Printmont.', 41
  UNION ALL SELECT 'become-a-seller', 'journey', 'Orders',   'Receive orders from customers and corporate buyers across India.', 42
  UNION ALL SELECT 'become-a-seller', 'journey', 'Shipment', 'We handle stress-free delivery of your products.', 43
  UNION ALL SELECT 'become-a-seller', 'journey', 'Payment',  'Receive payment 7 days* from the date of dispatch of your order.', 44
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'journey');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'tool' AS b, 'Fulfilment by Printmont' AS c, 'Worried about storing, packing, shipping and delivering? Let us do it all for you.' AS d, '/contact' AS e, 50 AS f
  UNION ALL SELECT 'become-a-seller', 'tool', 'Printmont Ads',      'Want your products to stand out and gain maximum visibility?', '/contact', 51
  UNION ALL SELECT 'become-a-seller', 'tool', 'Shopping Festivals', 'Get access to our biggest campaigns and festive sale events.', '/contact', 52
  UNION ALL SELECT 'become-a-seller', 'tool', 'Learning Centre',    'Personalised learning modules, webinars and tutorials to help you sell better.', '/help-center', 53
  UNION ALL SELECT 'become-a-seller', 'tool', 'Account Management', 'Improve product selection, pricing and business insights with in-house managers.', '/contact', 54
  UNION ALL SELECT 'become-a-seller', 'tool', 'Seller Dashboard',   'Manage your seller account any time, from desktop or mobile.', '/login', 55
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'tool');

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `content`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'platform' AS b, 'Selection Insights' AS c, 'Uncover best-selling products, identify growth opportunities, and understand customer preferences to stay ahead of the competition.' AS d, 'Explore All Features' AS e, 60 AS f
  UNION ALL SELECT 'become-a-seller', 'platform', 'Pricing Tools', 'See how your pricing compares and get recommendations that keep your listings competitive.', 'Explore All Features', 61
  UNION ALL SELECT 'become-a-seller', 'platform', 'Order Management', 'Track every order from placement to delivery in a single dashboard.', 'Explore All Features', 62
) AS s WHERE NOT EXISTS (SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'platform');
