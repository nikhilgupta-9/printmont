<?php
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

        // Seed default entries if empty
        $check = $this->conn->query("SELECT COUNT(*) as cnt FROM `{$this->table_name}`");
        $row = $check ? $check->fetch_assoc() : ['cnt' => 0];
        if ($row['cnt'] == 0) {
            $this->seedDefaultEntries();
        }
    }

    public function seedDefaultEntries() {
        $defaults = [
            // Main Navigation & Shop
            ['title' => 'Home', 'url' => '/', 'category' => 'main_pages', 'changefreq' => 'daily', 'priority' => 1.0, 'sort_order' => 1],
            ['title' => 'All Products', 'url' => '/product', 'category' => 'main_pages', 'changefreq' => 'daily', 'priority' => 0.9, 'sort_order' => 2],
            ['title' => 'Bulk Orders', 'url' => '/bulk-order', 'category' => 'main_pages', 'changefreq' => 'weekly', 'priority' => 0.8, 'sort_order' => 3],
            ['title' => 'Franchise Program', 'url' => '/franchise', 'category' => 'main_pages', 'changefreq' => 'weekly', 'priority' => 0.8, 'sort_order' => 4],
            ['title' => 'Track Your Order', 'url' => '/track-order', 'category' => 'main_pages', 'changefreq' => 'daily', 'priority' => 0.7, 'sort_order' => 5],

            // Company & About
            ['title' => 'About Us', 'url' => '/about', 'category' => 'company_info', 'changefreq' => 'monthly', 'priority' => 0.8, 'sort_order' => 1],
            ['title' => 'Careers & Vacancies', 'url' => '/careers', 'category' => 'company_info', 'changefreq' => 'weekly', 'priority' => 0.8, 'sort_order' => 2],
            ['title' => 'Blog & Stories', 'url' => '/blog', 'category' => 'company_info', 'changefreq' => 'daily', 'priority' => 0.8, 'sort_order' => 3],
            ['title' => 'Affiliate Program', 'url' => '/affiliate-program', 'category' => 'company_info', 'changefreq' => 'monthly', 'priority' => 0.7, 'sort_order' => 4],
            ['title' => 'Business Solutions', 'url' => '/business-solutions', 'category' => 'company_info', 'changefreq' => 'monthly', 'priority' => 0.7, 'sort_order' => 5],

            // Customer Support & Help
            ['title' => 'Help Center', 'url' => '/help-center', 'category' => 'help_support', 'changefreq' => 'weekly', 'priority' => 0.8, 'sort_order' => 1],
            ['title' => 'Frequently Asked Questions (FAQ)', 'url' => '/faq', 'category' => 'help_support', 'changefreq' => 'weekly', 'priority' => 0.7, 'sort_order' => 2],
            ['title' => 'Safe & Secure Shopping', 'url' => '/security', 'category' => 'help_support', 'changefreq' => 'monthly', 'priority' => 0.7, 'sort_order' => 3],
            ['title' => 'Contact Us', 'url' => '/contact', 'category' => 'help_support', 'changefreq' => 'monthly', 'priority' => 0.8, 'sort_order' => 4],

            // Legal & Policies
            ['title' => 'Privacy Policy', 'url' => '/policy/privacy', 'category' => 'legal_policies', 'changefreq' => 'monthly', 'priority' => 0.6, 'sort_order' => 1],
            ['title' => 'Terms of Use', 'url' => '/terms-of-use', 'category' => 'legal_policies', 'changefreq' => 'monthly', 'priority' => 0.6, 'sort_order' => 2],
            ['title' => 'Terms & Conditions', 'url' => '/policy/terms', 'category' => 'legal_policies', 'changefreq' => 'monthly', 'priority' => 0.6, 'sort_order' => 3],
            ['title' => 'Shipping Policy', 'url' => '/policy/shipping', 'category' => 'legal_policies', 'changefreq' => 'monthly', 'priority' => 0.6, 'sort_order' => 4],
            ['title' => 'Return & Refund Policy', 'url' => '/policy/refund', 'category' => 'legal_policies', 'changefreq' => 'monthly', 'priority' => 0.6, 'sort_order' => 5],
        ];

        $stmt = $this->conn->prepare("INSERT IGNORE INTO `{$this->table_name}` (title, url, category, changefreq, priority, is_active, sort_order) VALUES (?, ?, ?, ?, ?, 1, ?)");
        foreach ($defaults as $d) {
            $stmt->bind_param("ssssdi", $d['title'], $d['url'], $d['category'], $d['changefreq'], $d['priority'], $d['sort_order']);
            $stmt->execute();
        }
        $stmt->close();
    }

    public function getAllEntries($category = null, $active_only = false) {
        $query = "SELECT * FROM `{$this->table_name}` WHERE 1=1";
        $params = [];
        $types = "";

        if ($category && $category !== 'all') {
            $query .= " AND category = ?";
            $params[] = $category;
            $types .= "s";
        }
        if ($active_only) {
            $query .= " AND is_active = 1";
        }

        $query .= " ORDER BY category ASC, sort_order ASC, title ASC";

        if (!empty($params)) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        $res = $this->conn->query($query);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getGroupedEntries($active_only = true) {
        $entries = $this->getAllEntries(null, $active_only);

        $categoryLabels = [
            'main_pages' => 'Main Navigation & Shopping',
            'products_categories' => 'Product Categories',
            'company_info' => 'Company & About Us',
            'help_support' => 'Help & Customer Support',
            'legal_policies' => 'Legal & Policies',
            'custom' => 'Other Useful Links'
        ];

        $grouped = [];
        foreach ($categoryLabels as $key => $label) {
            $grouped[$key] = [
                'category_key' => $key,
                'category_name' => $label,
                'links' => []
            ];
        }

        foreach ($entries as $item) {
            $cat = $item['category'] ?? 'custom';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'category_key' => $cat,
                    'category_name' => ucwords(str_replace('_', ' ', $cat)),
                    'links' => []
                ];
            }
            $grouped[$cat]['links'][] = [
                'id' => (int)$item['id'],
                'title' => $item['title'],
                'url' => $item['url'],
                'changefreq' => $item['changefreq'],
                'priority' => (float)$item['priority']
            ];
        }

        // Dynamically fetch active product categories
        $catRes = $this->conn->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC LIMIT 30");
        if ($catRes && $catRes->num_rows > 0) {
            while ($c = $catRes->fetch_assoc()) {
                $cSlug = !empty($c['slug']) ? $c['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $c['name']), '-'));
                $grouped['products_categories']['links'][] = [
                    'id' => 'cat_' . $c['id'],
                    'title' => $c['name'],
                    'url' => '/category/' . $cSlug,
                    'changefreq' => 'weekly',
                    'priority' => 0.8
                ];
            }
        }

        // Filter out categories with 0 links
        return array_values(array_filter($grouped, function($g) {
            return !empty($g['links']);
        }));
    }

    public function createEntry($data) {
        $title = trim($data['title'] ?? '');
        $url = trim($data['url'] ?? '');
        $category = $data['category'] ?? 'main_pages';
        $changefreq = $data['changefreq'] ?? 'weekly';
        $priority = floatval($data['priority'] ?? 0.8);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $sort_order = intval($data['sort_order'] ?? 0);

        if (empty($title) || empty($url)) {
            return ['success' => false, 'message' => 'Title and URL are required.'];
        }

        // Ensure URL starts with / or http
        if (!str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
            $url = '/' . $url;
        }

        $stmt = $this->conn->prepare("INSERT INTO `{$this->table_name}` (title, url, category, changefreq, priority, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdii", $title, $url, $category, $changefreq, $priority, $is_active, $sort_order);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Sitemap entry created successfully!', 'id' => $stmt->insert_id];
        } else {
            return ['success' => false, 'message' => 'Error creating entry: ' . $stmt->error];
        }
    }

    public function updateEntry($id, $data) {
        $id = (int)$id;
        $title = trim($data['title'] ?? '');
        $url = trim($data['url'] ?? '');
        $category = $data['category'] ?? 'main_pages';
        $changefreq = $data['changefreq'] ?? 'weekly';
        $priority = floatval($data['priority'] ?? 0.8);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $sort_order = intval($data['sort_order'] ?? 0);

        if (empty($title) || empty($url)) {
            return ['success' => false, 'message' => 'Title and URL are required.'];
        }

        if (!str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
            $url = '/' . $url;
        }

        $stmt = $this->conn->prepare("UPDATE `{$this->table_name}` SET title = ?, url = ?, category = ?, changefreq = ?, priority = ?, is_active = ?, sort_order = ? WHERE id = ?");
        $stmt->bind_param("ssssdiii", $title, $url, $category, $changefreq, $priority, $is_active, $sort_order, $id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Sitemap entry updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Error updating entry: ' . $stmt->error];
        }
    }

    public function deleteEntry($id) {
        $id = (int)$id;
        $stmt = $this->conn->prepare("DELETE FROM `{$this->table_name}` WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Sitemap entry deleted successfully!'];
        }
        return ['success' => false, 'message' => 'Failed to delete entry.'];
    }

    public function toggleStatus($id) {
        $id = (int)$id;
        $stmt = $this->conn->prepare("UPDATE `{$this->table_name}` SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function generateXmlSitemap($baseUrl = 'http://localhost:5173') {
        $baseUrl = rtrim($baseUrl, '/');
        $entries = $this->getAllEntries(null, true);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

        $today = date('Y-m-d');
        $urlCount = 0;

        // 1. Add static & configured sitemap entries
        foreach ($entries as $e) {
            $loc = str_starts_with($e['url'], 'http') ? $e['url'] : $baseUrl . $e['url'];
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . $today . "</lastmod>\n";
            $xml .= "    <changefreq>" . htmlspecialchars($e['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            $xml .= "    <priority>" . number_format($e['priority'], 1) . "</priority>\n";
            $xml .= "  </url>\n";
            $urlCount++;
        }

        // 2. Add dynamic active Categories
        $catRes = $this->conn->query("SELECT id, name, slug, updated_at FROM categories WHERE is_active = 1");
        if ($catRes && $catRes->num_rows > 0) {
            while ($c = $catRes->fetch_assoc()) {
                $cSlug = !empty($c['slug']) ? $c['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $c['name']), '-'));
                $lastmod = !empty($c['updated_at']) ? date('Y-m-d', strtotime($c['updated_at'])) : $today;
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/category/' . $cSlug, ENT_XML1, 'UTF-8') . "</loc>\n";
                $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "  </url>\n";
                $urlCount++;
            }
        }

        // 3. Add dynamic active Careers
        $careerRes = $this->conn->query("SELECT id, slug, job_title, updated_at FROM careers WHERE is_active = 1");
        if ($careerRes && $careerRes->num_rows > 0) {
            while ($car = $careerRes->fetch_assoc()) {
                $carSlug = !empty($car['slug']) ? $car['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $car['job_title']), '-'));
                $lastmod = !empty($car['updated_at']) ? date('Y-m-d', strtotime($car['updated_at'])) : $today;
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/careers/' . $carSlug, ENT_XML1, 'UTF-8') . "</loc>\n";
                $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "    <priority>0.7</priority>\n";
                $xml .= "  </url>\n";
                $urlCount++;
            }
        }

        // 4. Add dynamic active Products (limit 500)
        $prodRes = $this->conn->query("SELECT id, name, slug, updated_at FROM products WHERE status = 'active' OR is_active = 1 LIMIT 500");
        if ($prodRes && $prodRes->num_rows > 0) {
            while ($p = $prodRes->fetch_assoc()) {
                $pSlug = !empty($p['slug']) ? $p['slug'] : $p['id'];
                $lastmod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/product/' . $pSlug, ENT_XML1, 'UTF-8') . "</loc>\n";
                $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
                $xml .= "    <changefreq>daily</changefreq>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "  </url>\n";
                $urlCount++;
            }
        }

        $xml .= "</urlset>\n";

        // Save to backend root and public
        $targetPaths = [
            __DIR__ . '/../sitemap.xml',
            __DIR__ . '/../api/sitemap.xml',
            __DIR__ . '/../../printmont/public/sitemap.xml'
        ];

        foreach ($targetPaths as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($path, $xml);
        }

        // Save generation metadata
        $metaPath = __DIR__ . '/../sitemap_meta.json';
        file_put_contents($metaPath, json_encode([
            'last_generated' => date('Y-m-d H:i:s'),
            'total_urls' => $urlCount,
            'file_size' => strlen($xml),
            'base_url' => $baseUrl
        ]));

        return [
            'success' => true,
            'message' => "XML Sitemap generated successfully with {$urlCount} URLs!",
            'total_urls' => $urlCount,
            'last_generated' => date('Y-m-d H:i:s'),
            'file_size' => strlen($xml)
        ];
    }

    public function getSitemapStats() {
        $metaPath = __DIR__ . '/../sitemap_meta.json';
        $xmlPath = __DIR__ . '/../sitemap.xml';

        $totalConfigured = 0;
        $cntRes = $this->conn->query("SELECT COUNT(*) as cnt FROM `{$this->table_name}` WHERE is_active = 1");
        if ($cntRes) {
            $r = $cntRes->fetch_assoc();
            $totalConfigured = (int)$r['cnt'];
        }

        if (file_exists($metaPath)) {
            $data = json_decode(file_get_contents($metaPath), true);
            $data['total_configured'] = $totalConfigured;
            $data['xml_exists'] = file_exists($xmlPath);
            return $data;
        }

        return [
            'last_generated' => null,
            'total_urls' => $totalConfigured,
            'total_configured' => $totalConfigured,
            'file_size' => file_exists($xmlPath) ? filesize($xmlPath) : 0,
            'xml_exists' => file_exists($xmlPath)
        ];
    }
}
