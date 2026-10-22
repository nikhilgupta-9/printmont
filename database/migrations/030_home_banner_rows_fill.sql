-- Fill home banner rows so each row has as many banners as it has columns.
-- home_above_fold: 4 columns, home_mid_section_1: 3 columns. Safe to run more than once.
UPDATE banner_sections SET columns_per_row = 4 WHERE section_key = 'home_above_fold';
UPDATE banner_sections SET columns_per_row = 3, layout_format = NULL WHERE section_key = 'home_mid_section_1';

SET @above := (SELECT id FROM banner_sections WHERE section_key = 'home_above_fold' LIMIT 1);
SET @mid1  := (SELECT id FROM banner_sections WHERE section_key = 'home_mid_section_1' LIMIT 1);

-- Laptop bags moves into the 4-up row (3rd slot)
UPDATE banners SET section_id = @above, display_order = 3 WHERE image_url_desktop = 'uploads/banners/desktop/banner_row1_laptop_bags.jpg' AND section_id = @mid1;
UPDATE banners SET display_order = 1 WHERE image_url_desktop = 'uploads/banners/desktop/banner_row2_trophies.jpg' AND section_id = @mid1;

INSERT INTO banners (title, image_url_desktop, image_url_mobile, target_url, display_order, status, position, section_id, banner_type)
SELECT 'Personalised Photo Lamps', 'uploads/banners/desktop/banner_row1_photo_lamps.jpg', 'uploads/banners/mobile/banner_row1_photo_lamps.jpg', '/allproducts', 4, 'active', 'homepage', @above, 'single'
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE section_id = @above AND image_url_desktop = 'uploads/banners/desktop/banner_row1_photo_lamps.jpg');

INSERT INTO banners (title, image_url_desktop, image_url_mobile, target_url, display_order, status, position, section_id, banner_type)
SELECT 'Custom Corporate Apparel', 'uploads/banners/desktop/banner_row2_apparel.jpg', 'uploads/banners/mobile/banner_row2_apparel.jpg', '/allproducts', 2, 'active', 'homepage', @mid1, 'single'
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE section_id = @mid1 AND image_url_desktop = 'uploads/banners/desktop/banner_row2_apparel.jpg');

INSERT INTO banners (title, image_url_desktop, image_url_mobile, target_url, display_order, status, position, section_id, banner_type)
SELECT 'Employee Welcome Kits', 'uploads/banners/desktop/banner_row2_welcome_kits.jpg', 'uploads/banners/mobile/banner_row2_welcome_kits.jpg', '/allproducts', 3, 'active', 'homepage', @mid1, 'single'
WHERE NOT EXISTS (SELECT 1 FROM banners WHERE section_id = @mid1 AND image_url_desktop = 'uploads/banners/desktop/banner_row2_welcome_kits.jpg');
