-- Migration 026: show the inner-page category navbar on the Support page (desktop)
UPDATE header_page_settings SET show_category_bar = 1 WHERE page_key = 'support_page';
