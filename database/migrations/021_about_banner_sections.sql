-- Migration 021: Add banner sections for About Us page carousels
INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'about', 'about_hero', 'About – Top Hero Banner & Carousel Slider', 1, 1, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'about_hero');

INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'about', 'about_mid_1', 'About – Mid Banner Carousel Slider 1', 1, 1, 'active', 2
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'about_mid_1');

INSERT INTO banner_sections (page, section_key, label, columns_per_row, display_order, is_slider, status)
SELECT 'about', 'about_mid_2', 'About – Mid Banner Carousel Slider 2', 1, 3, 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'about_mid_2');
