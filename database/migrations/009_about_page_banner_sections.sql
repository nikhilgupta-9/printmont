-- ============================================================
-- Migration 009: Banner sections for the About page
--
-- The About page's "Accolades & Milestones" grid and the
-- "Rooted in Love, Growing With You" timeline were both hardcoded in
-- AboutPage.jsx — the accolades in particular were 43 hand-drawn inline
-- <path> elements building laurel wreaths in code, with the award names
-- in a JS array. Nothing could be changed without a developer.
--
-- These two sections register the About page with the existing banner
-- system, so both areas become ordinary uploadable banners managed from
-- the admin panel like every other banner on the site.
--
-- Safe to re-run: INSERT IGNORE, and (page, section_key) is UNIQUE.
-- ============================================================

INSERT IGNORE INTO `banner_sections`
  (`page`, `section_key`, `label`, `columns_per_row`, `is_slider`, `display_order`, `status`)
VALUES
  ('about', 'about_accolades', 'About – Accolades & Milestones (3 Columns)', 3, 0, 1, 'active'),
  ('about', 'about_timeline',  'About – Our Journey Timeline (Full Width)',  1, 0, 2, 'active');
