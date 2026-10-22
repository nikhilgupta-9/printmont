<?php
/**
 * Dynamic Sitemap Controller — Printmont
 * 
 * Automatically generates live sitemap entries directly from database:
 * - Categories & Subcategories tree (Auto-sync)
 * - Active Products catalog (Auto-sync)
 * - Published Blog Posts & Categories (Auto-sync)
 * - Core Store, Corporate & Policy pages
 * - Custom Admin overrides & XML generator
 */

require_once(__DIR__ . '/../config/database.php');

class SitemapController {
    private $conn;
    private $table_name = "sitemap_entries";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTableExists();
    }

    public function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `url` VARCHAR(500) NOT NULL,
            `category` VARCHAR(50) NOT NULL DEFAULT 'main_pages',
            `changefreq` VARCHAR(20) NOT NULL DEFAULT 'weekly',
            `priority` DECIMAL(2,1) NOT NULL DEFAULT 0.8,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_url` (`url`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->conn->query($query);
    }

    /**
     * Get Complete Live Dynamic Sections (Auto-aggregating all DB tables)
     */
    public function getGroupedEntries($active_only = true) {
        $sections = [];

        // 1. Core Shopping & Account Pages
        $sections[] = [
            'category_key' => 'main_pages',
            'category_name' => 'Main Navigation & Shopping',
            'icon' => 'ri-compass-3-line',
            'description' => 'Core shopping destinations, search, cart, and account management.',
            'links' => [
                ['id' => 'm1', 'title' => 'Home Page', 'url' => '/', 'changefreq' => 'daily', 'priority' => 1.0],
                ['id' => 'm2', 'title' => 'All Products & Collections', 'url' => '/allproducts', 'changefreq' => 'daily', 'priority' => 0.9],
                ['id' => 'm3', 'title' => 'Shopping Cart', 'url' => '/cart', 'changefreq' => 'daily', 'priority' => 0.8],
                ['id' => 'm4', 'title' => 'My Wishlist', 'url' => '/wishlist', 'changefreq' => 'daily', 'priority' => 0.7],
                ['id' => 'm5', 'title' => 'Track Your Order', 'url' => '/track-order', 'changefreq' => 'daily', 'priority' => 0.8],
                ['id' => 'm6', 'title' => 'Printmont Coins & Wallet', 'url' => '/wallet', 'changefreq' => 'weekly', 'priority' => 0.7],
                ['id' => 'm7', 'title' => 'Customer Reviews Directory', 'url' => '/reviews', 'changefreq' => 'daily', 'priority' => 0.8],
            ]
        ];

        // 2. Live Categories & Subcategories (Tree Structure)
        $categoriesLinks = [];
        $categoriesTree = $this->getLiveCategoriesTree(100);
        foreach ($categoriesTree as $cat) {
            $categoriesLinks[] = [
                'id' => 'cat_' . $cat['id'],
                'title' => $cat['name'],
                'url' => '/category/' . $cat['slug'],
                'subcategories' => $cat['subcategories'] ?? [],
                'changefreq' => 'daily',
                'priority' => 0.9
            ];
        }

        $sections[] = [
            'category_key' => 'products_categories',
            'category_name' => 'Product Categories & Departments',
            'icon' => 'ri-store-2-line',
            'description' => 'Browse custom apparel, corporate gifting, stationery, drinkware, and packaging.',
            'links' => $categoriesLinks
        ];

        // 3. Corporate & B2B Solutions
        $sections[] = [
            'category_key' => 'company_info',
            'category_name' => 'B2B, Enterprise & Corporate Programs',
            'icon' => 'ri-building-line',
            'description' => 'Wholesale orders, corporate welcome kits, franchise partnerships, and brand solutions.',
            'links' => [
                ['id' => 'c1', 'title' => 'Business Solutions & Enterprise Hub', 'url' => '/business-solutions', 'changefreq' => 'weekly', 'priority' => 0.9],
                ['id' => 'c2', 'title' => 'Bulk Orders & Wholesale Printing', 'url' => '/bulk-orders', 'changefreq' => 'weekly', 'priority' => 0.9],
                ['id' => 'c3', 'title' => 'Become a Seller / Supplier', 'url' => '/become-a-seller', 'changefreq' => 'monthly', 'priority' => 0.8],
                ['id' => 'c4', 'title' => 'Franchise Partner Program', 'url' => '/franchise', 'changefreq' => 'monthly', 'priority' => 0.8],
                ['id' => 'c5', 'title' => 'Affiliate Program', 'url' => '/affiliate-program', 'changefreq' => 'monthly', 'priority' => 0.8],
                ['id' => 'c6', 'title' => 'About Printmont & Brand Story', 'url' => '/about', 'changefreq' => 'monthly', 'priority' => 0.8],
                ['id' => 'c7', 'title' => 'Careers & Open Vacancies', 'url' => '/careers', 'changefreq' => 'weekly', 'priority' => 0.8],
                ['id' => 'c8', 'title' => 'Download Mobile App', 'url' => '/app-download', 'changefreq' => 'monthly', 'priority' => 0.7],
            ]
        ];

        // 4. Live Published Blog Posts & Stories
        $blogLinks = [
            ['id' => 'b_hub', 'title' => 'Printmont Blog Hub & Articles', 'url' => '/blog', 'changefreq' => 'daily', 'priority' => 0.8]
        ];
        $blogs = $this->getLiveBlogPosts(25);
        foreach ($blogs as $b) {
            $blogLinks[] = [
                'id' => 'blog_' . $b['id'],
                'title' => $b['title'],
                'url' => '/blog/' . $b['slug'],
                'changefreq' => 'weekly',
                'priority' => 0.7
            ];
        }

        $sections[] = [
            'category_key' => 'blog_articles',
            'category_name' => 'Official Blog, News & Gifting Guides',
            'icon' => 'ri-article-line',
            'description' => 'Printing guides, corporate gifting trends, and design inspiration.',
            'links' => $blogLinks
        ];

        // 5. Help & Customer Support
        $sections[] = [
            'category_key' => 'help_support',
            'category_name' => 'Help Center & Customer Support',
            'icon' => 'ri-customer-service-2-line',
            'description' => '24x7 support desk, FAQs, contact information, and order assistance.',
            'links' => [
                ['id' => 'h1', 'title' => 'Help Center & Knowledge Base', 'url' => '/help-center', 'changefreq' => 'weekly', 'priority' => 0.8],
                ['id' => 'h2', 'title' => 'Frequently Asked Questions (FAQ)', 'url' => '/faq', 'changefreq' => 'weekly', 'priority' => 0.8],
                ['id' => 'h3', 'title' => 'Contact Us & Corporate Desk', 'url' => '/contact', 'changefreq' => 'monthly', 'priority' => 0.8],
                ['id' => 'h4', 'title' => 'Customer Support & Tickets', 'url' => '/support', 'changefreq' => 'weekly', 'priority' => 0.7],
                ['id' => 'h5', 'title' => 'Quick Navigation Links', 'url' => '/quick-links', 'changefreq' => 'weekly', 'priority' => 0.7],
                ['id' => 'h6', 'title' => 'Notification Preferences', 'url' => '/notification-preference', 'changefreq' => 'monthly', 'priority' => 0.6],
                ['id' => 'h7', 'title' => 'Manage Delivery Addresses', 'url' => '/manage-address', 'changefreq' => 'monthly', 'priority' => 0.6],
            ]
        ];

        // 6. Legal, Trust & Security Policies
        $sections[] = [
            'category_key' => 'legal_policies',
            'category_name' => 'Legal, Security & Policies',
            'icon' => 'ri-shield-check-line',
            'description' => 'Terms of use, privacy policies, secure payments, and shipping guidelines.',
            'links' => [
                ['id' => 'l1', 'title' => 'Safe & Secure Shopping Trust', 'url' => '/security', 'changefreq' => 'monthly', 'priority' => 0.7],
                ['id' => 'l2', 'title' => 'Privacy Policy', 'url' => '/privacy-policy', 'changefreq' => 'monthly', 'priority' => 0.6],
                ['id' => 'l3', 'title' => 'Terms of Use', 'url' => '/terms-of-use', 'changefreq' => 'monthly', 'priority' => 0.6],
                ['id' => 'l4', 'title' => 'Terms & Conditions', 'url' => '/terms-and-conditions', 'changefreq' => 'monthly', 'priority' => 0.6],
                ['id' => 'l5', 'title' => 'Shipping & Delivery Policy', 'url' => '/shipping-policy', 'changefreq' => 'monthly', 'priority' => 0.6],
                ['id' => 'l6', 'title' => 'Return & Refund Policy', 'url' => '/refund-policy', 'changefreq' => 'monthly', 'priority' => 0.6],
                ['id' => 'l7', 'title' => 'XML Sitemap (Search Engines)', 'url' => '/sitemap.xml', 'changefreq' => 'daily', 'priority' => 0.9],
            ]
        ];

        // 7. Custom Manual Overrides / Entries from Table
        $customEntries = $this->getCustomEntries();
        if (!empty($customEntries)) {
            $sections[] = [
                'category_key' => 'custom_entries',
                'category_name' => 'Special Featured & Custom Links',
                'icon' => 'ri-links-line',
                'description' => 'Custom pages and seasonal campaign links configured via admin.',
                'links' => $customEntries
            ];
        }

        return $sections;
    }

    /**
     * Get Live Category Tree (Parent Categories + Subcategories + Sub-subcategories)
     */
    public function getLiveCategoriesTree($limit = 100) {
        $tree = [];
        $pRes = $this->conn->query("SELECT id, name, slug FROM categories WHERE (parent_id IS NULL OR parent_id = 0 OR level = 1) AND status = 'active' ORDER BY display_order ASC, name ASC LIMIT $limit");
        
        if ($pRes) {
            while ($parent = $pRes->fetch_assoc()) {
                $parentId = (int)$parent['id'];
                $subRes = $this->conn->query("SELECT id, name, slug FROM categories WHERE parent_id = $parentId AND status = 'active' ORDER BY display_order ASC, name ASC LIMIT 50");
                $subcategories = [];
                if ($subRes) {
                    while ($sub = $subRes->fetch_assoc()) {
                        $subId = (int)$sub['id'];
                        // Fetch Level 3 (Sub-sub categories)
                        $subSubRes = $this->conn->query("SELECT id, name, slug FROM categories WHERE parent_id = $subId AND status = 'active' ORDER BY display_order ASC, name ASC LIMIT 50");
                        $childCategories = [];
                        if ($subSubRes) {
                            while ($child = $subSubRes->fetch_assoc()) {
                                $childCategories[] = [
                                    'id' => (int)$child['id'],
                                    'name' => $child['name'],
                                    'slug' => $child['slug'],
                                    'url' => '/category/' . $child['slug']
                                ];
                            }
                        }

                        $subcategories[] = [
                            'id' => $subId,
                            'name' => $sub['name'],
                            'slug' => $sub['slug'],
                            'url' => '/category/' . $sub['slug'],
                            'subcategories' => $childCategories
                        ];
                    }
                }
                $parent['subcategories'] = $subcategories;
                $tree[] = $parent;
            }
        }
        return $tree;
    }

    /**
     * Get Live Published Blog Posts
     */
    public function getLiveBlogPosts($limit = 30) {
        $blogs = [];
        $res = $this->conn->query("SELECT id, title, slug, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT $limit");
        if ($res) {
            while ($b = $res->fetch_assoc()) {
                $blogs[] = $b;
            }
        }
        return $blogs;
    }

    /**
     * Get Live Active Products (for Search Directory / XML)
     */
    public function getLiveProducts($limit = 100) {
        $products = [];
        $res = $this->conn->query("SELECT id, name, slug, updated_at FROM products WHERE status = 'active' ORDER BY id DESC LIMIT $limit");
        if ($res) {
            while ($p = $res->fetch_assoc()) {
                $products[] = $p;
            }
        }
        return $products;
    }

    /**
     * Get Custom Entries from sitemap_entries table
     */
    public function getCustomEntries() {
        $entries = [];
        $res = $this->conn->query("SELECT id, title, url, changefreq, priority FROM `{$this->table_name}` WHERE is_active = 1 ORDER BY sort_order ASC, title ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $entries[] = $r;
            }
        }
        return $entries;
    }

    /**
     * Real-time Live Stats
     */
    public function getSitemapStats() {
        $catCount = 0;
        $prodCount = 0;
        $blogCount = 0;
        $customCount = 0;

        $cRes = $this->conn->query("SELECT COUNT(*) as cnt FROM categories WHERE status = 'active'");
        if ($cRes) $catCount = (int)$cRes->fetch_assoc()['cnt'];

        $pRes = $this->conn->query("SELECT COUNT(*) as cnt FROM products WHERE status = 'active'");
        if ($pRes) $prodCount = (int)$pRes->fetch_assoc()['cnt'];

        $bRes = $this->conn->query("SELECT COUNT(*) as cnt FROM blog_posts WHERE status = 'published'");
        if ($bRes) $blogCount = (int)$bRes->fetch_assoc()['cnt'];

        $custRes = $this->conn->query("SELECT COUNT(*) as cnt FROM `{$this->table_name}` WHERE is_active = 1");
        if ($custRes) $customCount = (int)$custRes->fetch_assoc()['cnt'];

        $staticPages = 24; // Core shopping, corporate, help, and legal pages
        $totalUrls = $staticPages + $catCount + $prodCount + $blogCount + $customCount;

        return [
            'total_urls' => $totalUrls,
            'categories_count' => $catCount,
            'products_count' => $prodCount,
            'blogs_count' => $blogCount,
            'static_pages_count' => $staticPages,
            'custom_entries_count' => $customCount,
            'last_generated' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate / Update physical XML Sitemap file (sitemap.xml)
     */
    public function generateXmlSitemap($baseUrl = 'http://localhost:5173') {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $today = date('Y-m-d');
        $baseUrl = rtrim($baseUrl, '/');

        // Helper to add URL node
        $addUrl = function($loc, $changefreq = 'weekly', $priority = '0.8', $lastmod = null) use (&$xml, $baseUrl, $today) {
            $fullUrl = (strpos($loc, 'http://') === 0 || strpos($loc, 'https://') === 0) ? $loc : $baseUrl . $loc;
            $mod = $lastmod ?: $today;
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($fullUrl) . "</loc>\n";
            $xml .= "    <lastmod>" . $mod . "</lastmod>\n";
            $xml .= "    <changefreq>" . $changefreq . "</changefreq>\n";
            $xml .= "    <priority>" . $priority . "</priority>\n";
            $xml .= "  </url>\n";
        };

        // 1. Core Pages
        $corePages = [
            ['/', 'daily', '1.0'],
            ['/allproducts', 'daily', '0.9'],
            ['/cart', 'daily', '0.8'],
            ['/wishlist', 'daily', '0.7'],
            ['/track-order', 'daily', '0.8'],
            ['/wallet', 'weekly', '0.7'],
            ['/reviews', 'daily', '0.8'],
            ['/business-solutions', 'weekly', '0.9'],
            ['/bulk-orders', 'weekly', '0.9'],
            ['/become-a-seller', 'monthly', '0.8'],
            ['/franchise', 'monthly', '0.8'],
            ['/affiliate-program', 'monthly', '0.8'],
            ['/about', 'monthly', '0.8'],
            ['/careers', 'weekly', '0.8'],
            ['/app-download', 'monthly', '0.7'],
            ['/help-center', 'weekly', '0.8'],
            ['/faq', 'weekly', '0.8'],
            ['/contact', 'monthly', '0.8'],
            ['/support', 'weekly', '0.7'],
            ['/security', 'monthly', '0.7'],
            ['/privacy-policy', 'monthly', '0.6'],
            ['/terms-of-use', 'monthly', '0.6'],
            ['/terms-and-conditions', 'monthly', '0.6'],
            ['/shipping-policy', 'monthly', '0.6'],
            ['/refund-policy', 'monthly', '0.6'],
            ['/sitemap', 'weekly', '0.8'],
            ['/blog', 'daily', '0.8']
        ];

        foreach ($corePages as $p) {
            $addUrl($p[0], $p[1], $p[2]);
        }

        // 2. Categories
        $cRes = $this->conn->query("SELECT slug, updated_at FROM categories WHERE status = 'active'");
        if ($cRes) {
            while ($c = $cRes->fetch_assoc()) {
                $mod = !empty($c['updated_at']) ? date('Y-m-d', strtotime($c['updated_at'])) : $today;
                $addUrl('/category/' . $c['slug'], 'daily', '0.85', $mod);
            }
        }

        // 3. Products
        $pRes = $this->conn->query("SELECT slug, updated_at FROM products WHERE status = 'active'");
        if ($pRes) {
            while ($p = $pRes->fetch_assoc()) {
                $mod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
                $addUrl('/product/' . ($p['slug'] ?: $p['id']), 'weekly', '0.8', $mod);
            }
        }

        // 4. Blogs
        $bRes = $this->conn->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published'");
        if ($bRes) {
            while ($b = $bRes->fetch_assoc()) {
                $mod = !empty($b['updated_at']) ? date('Y-m-d', strtotime($b['updated_at'])) : $today;
                $addUrl('/blog/' . $b['slug'], 'weekly', '0.75', $mod);
            }
        }

        // 5. Custom Entries
        $custRes = $this->conn->query("SELECT url, changefreq, priority, updated_at FROM `{$this->table_name}` WHERE is_active = 1");
        if ($custRes) {
            while ($cust = $custRes->fetch_assoc()) {
                $mod = !empty($cust['updated_at']) ? date('Y-m-d', strtotime($cust['updated_at'])) : $today;
                $addUrl($cust['url'], $cust['changefreq'] ?: 'weekly', (string)$cust['priority'] ?: '0.7', $mod);
            }
        }

        $xml .= '</urlset>';

        // Write to root sitemap.xml and backend sitemap.xml
        $backendPath = __DIR__ . '/../sitemap.xml';
        file_put_contents($backendPath, $xml);

        $frontendPublicPath = __DIR__ . '/../../frontend/public/sitemap.xml';
        if (is_dir(dirname($frontendPublicPath))) {
            file_put_contents($frontendPublicPath, $xml);
        }

        return ['success' => true, 'message' => 'Sitemap XML generated successfully! File updated with latest database records.'];
    }

    /**
     * CRUD: Get All Entries (with optional category filter)
     */
    public function getAllEntries($category = null, $active_only = false) {
        $sql = "SELECT * FROM `{$this->table_name}` WHERE 1=1";
        if ($category && $category !== 'all') {
            $catSafe = $this->conn->real_escape_string($category);
            $sql .= " AND category = '$catSafe'";
        }
        if ($active_only) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, id DESC";

        $result = $this->conn->query($sql);
        $entries = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
        }
        return $entries;
    }

    /**
     * CRUD: Get single entry by ID
     */
    public function getEntryById($id) {
        $id = (int)$id;
        $stmt = $this->conn->prepare("SELECT * FROM `{$this->table_name}` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    /**
     * CRUD: Create custom entry
     */
    public function createEntry($data) {
        $title = trim($data['title'] ?? '');
        $url = trim($data['url'] ?? '');
        $category = trim($data['category'] ?? 'main_pages');
        $changefreq = trim($data['changefreq'] ?? 'weekly');
        $priority = floatval($data['priority'] ?? 0.8);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $sort_order = (int)($data['sort_order'] ?? 0);

        if (empty($title) || empty($url)) {
            return ['success' => false, 'message' => 'Title and URL are required.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO `{$this->table_name}` (title, url, category, changefreq, priority, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdii", $title, $url, $category, $changefreq, $priority, $is_active, $sort_order);

        if ($stmt->execute()) {
            // Auto regenerate XML
            $this->generateXmlSitemap();
            return ['success' => true, 'message' => 'Sitemap entry created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error saving entry: ' . $stmt->error];
        }
    }

    /**
     * CRUD: Update custom entry
     */
    public function updateEntry($id, $data) {
        $id = (int)$id;
        $title = trim($data['title'] ?? '');
        $url = trim($data['url'] ?? '');
        $category = trim($data['category'] ?? 'main_pages');
        $changefreq = trim($data['changefreq'] ?? 'weekly');
        $priority = floatval($data['priority'] ?? 0.8);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $sort_order = (int)($data['sort_order'] ?? 0);

        if (empty($title) || empty($url)) {
            return ['success' => false, 'message' => 'Title and URL are required.'];
        }

        $stmt = $this->conn->prepare("UPDATE `{$this->table_name}` SET title = ?, url = ?, category = ?, changefreq = ?, priority = ?, is_active = ?, sort_order = ? WHERE id = ?");
        $stmt->bind_param("ssssdiii", $title, $url, $category, $changefreq, $priority, $is_active, $sort_order, $id);

        if ($stmt->execute()) {
            // Auto regenerate XML
            $this->generateXmlSitemap();
            return ['success' => true, 'message' => 'Sitemap entry updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error updating entry: ' . $stmt->error];
        }
    }

    /**
     * CRUD: Delete custom entry
     */
    public function deleteEntry($id) {
        $id = (int)$id;
        $stmt = $this->conn->prepare("DELETE FROM `{$this->table_name}` WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $this->generateXmlSitemap();
            return ['success' => true, 'message' => 'Sitemap entry deleted.'];
        } else {
            return ['success' => false, 'message' => 'Error deleting entry: ' . $stmt->error];
        }
    }

    /**
     * Toggle status
     */
    public function toggleStatus($id) {
        $id = (int)$id;
        $this->conn->query("UPDATE `{$this->table_name}` SET is_active = IF(is_active=1, 0, 1) WHERE id = $id");
        $this->generateXmlSitemap();
        return ['success' => true];
    }
}

