-- ============================================================
-- Migration 017: Side panels on Become a Seller
--
-- Two panels on the seller page were drawn from hardcoded icons and
-- had no row behind them: the tinted block beside the benefit cards,
-- and the one beside the enquiry form. Neither could be given a real
-- photograph without editing the component.
--
-- Section type added:
--
--   aside    one per panel. The band it sits in goes in `extra`
--            (why, help), the accessible description in `title`, and
--            the photograph in `image_path`.
--
-- Both rows are seeded without an image on purpose: the storefront
-- keeps showing its icon until someone uploads one, so running this
-- changes nothing visible on the live site.
--
-- Safe to re-run: seeds only when no aside row exists for the page.
-- ============================================================

INSERT INTO `page_sections` (`page_key`, `section_type`, `title`, `extra`, `display_order`)
SELECT * FROM (
            SELECT 'become-a-seller' AS a, 'aside' AS b, 'A seller packing an order' AS c, 'why' AS d, 100 AS e
  UNION ALL SELECT 'become-a-seller', 'aside', 'Our seller support team', 'help', 101
) AS s WHERE NOT EXISTS (
    SELECT 1 FROM `page_sections` WHERE `page_key` = 'become-a-seller' AND `section_type` = 'aside'
);
