<?php
// Shared by product.php (Add/Edit Product, section 1 point 13) and api/product-info-banner-api.php.
// Each row is an image + text banner tied to one product, shown on the product page above Ratings & Reviews.
// Replaces the old category-level "category_banner_texts" table (moved from category_id to product_id).

if (!function_exists('ensureProductInfoBannersTable')) {
    function ensureProductInfoBannersTable($db) {
        $db->query("CREATE TABLE IF NOT EXISTS product_info_banners (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT NULL,
            button_text VARCHAR(100) NULL,
            button_link VARCHAR(500) NULL,
            image VARCHAR(500) NULL,
            display_order INT(11) NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_pib_product (product_id, status, display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
