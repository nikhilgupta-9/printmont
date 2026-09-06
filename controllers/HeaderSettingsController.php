<?php
require_once __DIR__ . '/../config/database.php';

class HeaderSettingsController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTableExists();
    }

    /**
     * Create the header_page_settings table if not exists and seed defaults.
     */
    public function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `header_page_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `page_key` VARCHAR(50) NOT NULL UNIQUE,
            `page_name` VARCHAR(100) NOT NULL,
            `page_group` VARCHAR(50) DEFAULT 'commerce',
            `route_patterns` TEXT NOT NULL,
            `header_type` ENUM('full', 'inner', 'minimal', 'none') DEFAULT 'inner',
            `show_category_bar` TINYINT(1) DEFAULT 1,
            `category_bar_mode` ENUM('with_images', 'text_only') DEFAULT 'text_only',
            `is_sticky` TINYINT(1) DEFAULT 1,
            `custom_bg` VARCHAR(50) DEFAULT 'rgb(11, 83, 161)',
            `custom_text_color` VARCHAR(50) DEFAULT 'white',
            `sort_order` INT DEFAULT 0,
            `is_custom` TINYINT(1) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->conn->query($sql);

        // Check if page_group or is_custom column exists
        $res = $this->conn->query("SHOW COLUMNS FROM `header_page_settings` LIKE 'page_group'");
        if ($res && $res->num_rows == 0) {
            $this->conn->query("ALTER TABLE `header_page_settings` ADD COLUMN `page_group` VARCHAR(50) DEFAULT 'commerce' AFTER `page_name`");
        }

        $res2 = $this->conn->query("SHOW COLUMNS FROM `header_page_settings` LIKE 'is_custom'");
        if ($res2 && $res2->num_rows == 0) {
            $this->conn->query("ALTER TABLE `header_page_settings` ADD COLUMN `is_custom` TINYINT(1) DEFAULT 0 AFTER `sort_order`");
        }

        // Check count; only seed if table is completely empty
        $resCount = $this->conn->query("SELECT COUNT(*) AS total FROM `header_page_settings`");
        $rowCount = $resCount ? $resCount->fetch_assoc() : null;
        if (!$rowCount || (int)$rowCount['total'] === 0) {
            $this->seedDefaultSettings();
        }
    }

    /**
     * Seed initial sensible defaults matching ALL individual Printmont pages.
     */
    public function seedDefaultSettings() {
        $defaults = [
            // --- COMMERCE & SHOP ---
            [
                'page_key' => 'home',
                'page_name' => 'Home Page',
                'page_group' => 'commerce',
                'route_patterns' => '^/$',
                'header_type' => 'full',
                'show_category_bar' => 0,
                'category_bar_mode' => 'with_images',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 1
            ],
            [
                'page_key' => 'shop_all',
                'page_name' => 'All Products / Shop',
                'page_group' => 'commerce',
                'route_patterns' => '/allproducts',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 2
            ],
            [
                'page_key' => 'search_page',
                'page_name' => 'Search Results Page',
                'page_group' => 'commerce',
                'route_patterns' => '/search',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 3
            ],
            [
                'page_key' => 'category_list',
                'page_name' => 'Categories Browser',
                'page_group' => 'commerce',
                'route_patterns' => '/category,/category/.*',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 4
            ],
            [
                'page_key' => 'product_details',
                'page_name' => 'Product Details Page',
                'page_group' => 'commerce',
                'route_patterns' => '/product/.*,/[a-z0-9\-]+-p[0-9]+',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 5
            ],
            [
                'page_key' => 'cart_page',
                'page_name' => 'Shopping Cart',
                'page_group' => 'commerce',
                'route_patterns' => '/cart',
                'header_type' => 'full',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 6
            ],
            [
                'page_key' => 'wishlist_page',
                'page_name' => 'Wishlist Page',
                'page_group' => 'commerce',
                'route_patterns' => '/wishlist',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 7
            ],
            [
                'page_key' => 'orders_page',
                'page_name' => 'My Orders',
                'page_group' => 'commerce',
                'route_patterns' => '/orders',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 8
            ],
            [
                'page_key' => 'giftcard_page',
                'page_name' => 'Gift Cards',
                'page_group' => 'commerce',
                'route_patterns' => '/user/giftcard',
                'header_type' => 'inner',
                'show_category_bar' => 1,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 9
            ],

            // --- USER & ACCOUNT ---
            [
                'page_key' => 'my_account',
                'page_name' => 'My Account Dashboard',
                'page_group' => 'account',
                'route_patterns' => '/my-account,/account-setting',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 10
            ],
            [
                'page_key' => 'user_profile',
                'page_name' => 'Profile Settings',
                'page_group' => 'account',
                'route_patterns' => '/user/profile,/[a-zA-Z0-9_\-]+/profile',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 11
            ],
            [
                'page_key' => 'manage_address',
                'page_name' => 'Manage Addresses',
                'page_group' => 'account',
                'route_patterns' => '/manage-address,/user/manage-address',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 12
            ],
            [
                'page_key' => 'notification_preferences',
                'page_name' => 'Notification Preferences',
                'page_group' => 'account',
                'route_patterns' => '/notification-preference',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 13
            ],
            [
                'page_key' => 'change_password',
                'page_name' => 'Change Password',
                'page_group' => 'account',
                'route_patterns' => '/user/change-password',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 14
            ],
            [
                'page_key' => 'wallet_coins',
                'page_name' => 'Printmont Coins & Wallet',
                'page_group' => 'account',
                'route_patterns' => '/printmont-coin,/wallet',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 15
            ],
            [
                'page_key' => 'track_order',
                'page_name' => 'Track Order',
                'page_group' => 'account',
                'route_patterns' => '/track-order',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 16
            ],

            // --- CORPORATE & DISCOVERY ---
            [
                'page_key' => 'about_us',
                'page_name' => 'About Us',
                'page_group' => 'corporate',
                'route_patterns' => '/about',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 20
            ],
            [
                'page_key' => 'careers',
                'page_name' => 'Careers & Vacancies',
                'page_group' => 'corporate',
                'route_patterns' => '/careers,/careers/.*',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 21
            ],
            [
                'page_key' => 'faqs',
                'page_name' => 'FAQs Page',
                'page_group' => 'corporate',
                'route_patterns' => '/faq',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 22
            ],
            [
                'page_key' => 'security_info',
                'page_name' => 'Security & Trust',
                'page_group' => 'corporate',
                'route_patterns' => '/security',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 23
            ],
            [
                'page_key' => 'blog',
                'page_name' => 'Blog & Articles',
                'page_group' => 'corporate',
                'route_patterns' => '/blog,/blog/.*',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 24
            ],
            [
                'page_key' => 'sitemap',
                'page_name' => 'Site Map',
                'page_group' => 'corporate',
                'route_patterns' => '/sitemap',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 25
            ],
            [
                'page_key' => 'business_solutions',
                'page_name' => 'Business Solutions',
                'page_group' => 'corporate',
                'route_patterns' => '/business-solutions',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 26
            ],
            [
                'page_key' => 'become_seller',
                'page_name' => 'Become a Seller',
                'page_group' => 'corporate',
                'route_patterns' => '/become-a-seller',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 27
            ],
            [
                'page_key' => 'bulk_orders',
                'page_name' => 'Bulk Orders',
                'page_group' => 'corporate',
                'route_patterns' => '/bulk-orders,/bulk-order',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 28
            ],
            [
                'page_key' => 'franchise',
                'page_name' => 'Franchise Partner',
                'page_group' => 'corporate',
                'route_patterns' => '/franchise,/franchises',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 29
            ],
            [
                'page_key' => 'affiliate_program',
                'page_name' => 'Affiliate Program',
                'page_group' => 'corporate',
                'route_patterns' => '/affiliate-program',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 30
            ],
            [
                'page_key' => 'app_download',
                'page_name' => 'App Download Page',
                'page_group' => 'corporate',
                'route_patterns' => '/app-download',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 31
            ],

            // --- SUPPORT & HELP ---
            [
                'page_key' => 'help_center',
                'page_name' => 'Help Center',
                'page_group' => 'support',
                'route_patterns' => '/help-center',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 40
            ],
            [
                'page_key' => 'contact_us',
                'page_name' => 'Contact Us',
                'page_group' => 'support',
                'route_patterns' => '/contact',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 41
            ],
            [
                'page_key' => 'support_page',
                'page_name' => 'Support Desk',
                'page_group' => 'support',
                'route_patterns' => '/support',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 42
            ],
            [
                'page_key' => 'quick_links',
                'page_name' => 'Quick Links',
                'page_group' => 'support',
                'route_patterns' => '/quick-links',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 43
            ],

            // --- LEGAL & POLICIES ---
            [
                'page_key' => 'privacy_policy',
                'page_name' => 'Privacy Policy',
                'page_group' => 'legal',
                'route_patterns' => '/privacy-policy',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 50
            ],
            [
                'page_key' => 'terms_of_use',
                'page_name' => 'Terms of Use',
                'page_group' => 'legal',
                'route_patterns' => '/terms-of-use',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 51
            ],
            [
                'page_key' => 'terms_conditions',
                'page_name' => 'Terms & Conditions',
                'page_group' => 'legal',
                'route_patterns' => '/terms-and-conditions',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 52
            ],
            [
                'page_key' => 'shipping_policy',
                'page_name' => 'Shipping Policy',
                'page_group' => 'legal',
                'route_patterns' => '/shipping-policy',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 53
            ],
            [
                'page_key' => 'refund_policy',
                'page_name' => 'Refund Policy',
                'page_group' => 'legal',
                'route_patterns' => '/refund-policy',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 54
            ],
            [
                'page_key' => 'return_policy',
                'page_name' => 'Return Policy',
                'page_group' => 'legal',
                'route_patterns' => '/return-policy',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 55
            ],
            [
                'page_key' => 'general_policy',
                'page_name' => 'General Policies Catch-all',
                'page_group' => 'legal',
                'route_patterns' => '/policy/.*,/policy',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 0,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 56
            ],

            // --- DEFAULT / FALLBACK ---
            [
                'page_key' => 'default_fallback',
                'page_name' => 'All Other / Default Pages',
                'page_group' => 'fallback',
                'route_patterns' => '.*',
                'header_type' => 'inner',
                'show_category_bar' => 0,
                'category_bar_mode' => 'text_only',
                'is_sticky' => 1,
                'custom_bg' => 'rgb(11, 83, 161)',
                'custom_text_color' => 'white',
                'sort_order' => 99
            ]
        ];

        // Insert or update defaults
        $stmt = $this->conn->prepare("INSERT INTO `header_page_settings` 
            (`page_key`, `page_name`, `page_group`, `route_patterns`, `header_type`, `show_category_bar`, `category_bar_mode`, `is_sticky`, `custom_bg`, `custom_text_color`, `sort_order`, `is_active`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                `page_name` = VALUES(`page_name`),
                `page_group` = VALUES(`page_group`),
                `route_patterns` = VALUES(`route_patterns`)");

        foreach ($defaults as $d) {
            $stmt->bind_param("sssssisissi", 
                $d['page_key'], 
                $d['page_name'], 
                $d['page_group'], 
                $d['route_patterns'], 
                $d['header_type'], 
                $d['show_category_bar'], 
                $d['category_bar_mode'], 
                $d['is_sticky'], 
                $d['custom_bg'], 
                $d['custom_text_color'], 
                $d['sort_order']
            );
            $stmt->execute();
        }
    }

    /**
     * Reseed all pages cleanly.
     */
    public function reseedAllPages() {
        $this->conn->query("DELETE FROM `header_page_settings` WHERE `is_custom` = 0");
        $this->seedDefaultSettings();
        return true;
    }

    /**
     * Get all header page rules ordered by sort_order.
     */
    public function getAllSettings() {
        $res = $this->conn->query("SELECT * FROM `header_page_settings` ORDER BY `sort_order` ASC, `id` ASC");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Create a new custom page rule.
     */
    public function createPageRule($data) {
        $page_name = trim($data['page_name'] ?? '');
        if (empty($page_name)) return false;

        $page_key = trim($data['page_key'] ?? '');
        if (empty($page_key)) {
            $page_key = preg_replace('/[^a-z0-9_]/', '_', strtolower($page_name)) . '_' . time();
        }

        $page_group = trim($data['page_group'] ?? 'custom');
        $route_patterns = trim($data['route_patterns'] ?? '');
        $header_type = in_array($data['header_type'] ?? '', ['full', 'inner', 'minimal', 'none']) ? $data['header_type'] : 'inner';
        $show_category_bar = !empty($data['show_category_bar']) ? 1 : 0;
        $category_bar_mode = in_array($data['category_bar_mode'] ?? '', ['with_images', 'text_only']) ? $data['category_bar_mode'] : 'text_only';
        $is_sticky = !empty($data['is_sticky']) ? 1 : 0;
        $custom_bg = trim($data['custom_bg'] ?? 'rgb(11, 83, 161)');
        $custom_text_color = trim($data['custom_text_color'] ?? 'white');
        $sort_order = isset($data['sort_order']) ? (int)$data['sort_order'] : 50;
        $is_custom = 1;
        $is_active = 1;

        $stmt = $this->conn->prepare("INSERT INTO `header_page_settings` 
            (`page_key`, `page_name`, `page_group`, `route_patterns`, `header_type`, `show_category_bar`, `category_bar_mode`, `is_sticky`, `custom_bg`, `custom_text_color`, `sort_order`, `is_custom`, `is_active`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssisisssii", 
            $page_key, $page_name, $page_group, $route_patterns, $header_type,
            $show_category_bar, $category_bar_mode, $is_sticky, $custom_bg,
            $custom_text_color, $sort_order, $is_custom, $is_active
        );

        return $stmt->execute();
    }

    /**
     * Delete a custom page rule.
     */
    public function deletePageRule($id) {
        $id = (int)$id;
        $stmt = $this->conn->prepare("DELETE FROM `header_page_settings` WHERE `id` = ? AND `page_key` != 'default_fallback'");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Update a single page rule.
     */
    public function updatePageSetting($id, $data) {
        $id = (int)$id;
        $page_name = isset($data['page_name']) ? trim($data['page_name']) : null;
        $route_patterns = isset($data['route_patterns']) ? trim($data['route_patterns']) : null;
        $page_group = isset($data['page_group']) ? trim($data['page_group']) : null;
        $header_type = in_array($data['header_type'] ?? '', ['full', 'inner', 'minimal', 'none']) ? $data['header_type'] : 'inner';
        $show_category_bar = !empty($data['show_category_bar']) ? 1 : 0;
        $category_bar_mode = in_array($data['category_bar_mode'] ?? '', ['with_images', 'text_only']) ? $data['category_bar_mode'] : 'text_only';
        $is_sticky = !empty($data['is_sticky']) ? 1 : 0;
        $custom_bg = trim($data['custom_bg'] ?? 'rgb(11, 83, 161)');
        $custom_text_color = trim($data['custom_text_color'] ?? 'white');
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if ($page_name !== null && $route_patterns !== null) {
            $stmt = $this->conn->prepare("UPDATE `header_page_settings` SET 
                `page_name` = ?,
                `route_patterns` = ?,
                `page_group` = COALESCE(?, `page_group`),
                `header_type` = ?,
                `show_category_bar` = ?,
                `category_bar_mode` = ?,
                `is_sticky` = ?,
                `custom_bg` = ?,
                `custom_text_color` = ?,
                `is_active` = ?
                WHERE `id` = ?");

            $stmt->bind_param("ssssisisssi", $page_name, $route_patterns, $page_group, $header_type, $show_category_bar, $category_bar_mode, $is_sticky, $custom_bg, $custom_text_color, $is_active, $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE `header_page_settings` SET 
                `header_type` = ?,
                `show_category_bar` = ?,
                `category_bar_mode` = ?,
                `is_sticky` = ?,
                `custom_bg` = ?,
                `custom_text_color` = ?,
                `is_active` = ?
                WHERE `id` = ?");

            $stmt->bind_param("sisisssi", $header_type, $show_category_bar, $category_bar_mode, $is_sticky, $custom_bg, $custom_text_color, $is_active, $id);
        }

        return $stmt->execute();
    }

    /**
     * Toggle status or specific field via AJAX.
     */
    public function toggleField($id, $field, $value) {
        $id = (int)$id;
        $allowedFields = ['show_category_bar', 'is_sticky', 'is_active', 'header_type', 'category_bar_mode'];
        if (!in_array($field, $allowedFields)) {
            return false;
        }

        if (in_array($field, ['show_category_bar', 'is_sticky', 'is_active'])) {
            $val = $value ? 1 : 0;
            $stmt = $this->conn->prepare("UPDATE `header_page_settings` SET `{$field}` = ? WHERE `id` = ?");
            $stmt->bind_param("ii", $val, $id);
        } else {
            $val = trim($value);
            $stmt = $this->conn->prepare("UPDATE `header_page_settings` SET `{$field}` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $val, $id);
        }

        return $stmt->execute();
    }

    /**
     * Get consolidated config payload for Frontend React app.
     */
    public function getFrontendConfig() {
        $rules = $this->getAllSettings();
        
        // Read global layout design if exists
        $configFile = __DIR__ . '/../config/header_menu_design.json';
        $menuDesign = 'design1';
        if (file_exists($configFile)) {
            $data = json_decode(file_get_contents($configFile), true);
            $menuDesign = $data['design'] ?? 'design1';
        }

        return [
            'menu_design' => $menuDesign,
            'rules' => $rules,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
