<?php
// Shared by category-banner-texts.php (admin) and api/category-banner-text-api.php.
// Banner texts are attached to a category and shown on the product page under "About the product".

if (!function_exists('ensureCategoryBannerTextsTable')) {
    function ensureCategoryBannerTextsTable($db) {
        $db->query("CREATE TABLE IF NOT EXISTS category_banner_texts (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            category_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            button_text VARCHAR(100) NULL,
            button_link VARCHAR(500) NULL,
            image VARCHAR(500) NULL,
            bg_color VARCHAR(20) NOT NULL DEFAULT '#f1f5ff',
            text_color VARCHAR(20) NOT NULL DEFAULT '#1e293b',
            display_order INT(11) NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_cbt_category (category_id, status, display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tables created before banner images were added
        $col = $db->query("SHOW COLUMNS FROM category_banner_texts LIKE 'image'");
        if ($col && $col->num_rows === 0) {
            $db->query("ALTER TABLE category_banner_texts ADD COLUMN image VARCHAR(500) NULL AFTER button_link");
        }
    }
}
