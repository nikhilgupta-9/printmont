-- Migration 029: show the inner-page category menu bar on Cart, Checkout and Track Order (desktop)
UPDATE header_page_settings SET show_category_bar = 1 WHERE page_key IN ('cart_page', 'track_order');

INSERT INTO header_page_settings (page_key, page_name, page_group, route_patterns, header_type, show_category_bar, category_bar_mode, sort_order, is_active)
SELECT 'checkout_page', 'Checkout', 'commerce', '/checkout', 'inner', 1, 'text_only', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM header_page_settings WHERE page_key = 'checkout_page');
