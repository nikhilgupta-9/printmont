-- Migration 023: Banner section for the Bulk Orders page top banner (desktop + mobile images per banner)
INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'bulk-orders', 'bulk_orders_top_banner', 'Bulk Orders – Top Banner & Carousel (Desktop + Mobile)', 1, 1, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'bulk_orders_top_banner');
