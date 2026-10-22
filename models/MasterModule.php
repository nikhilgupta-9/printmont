<?php
class MasterModule {
    private $conn;
    private $table = "master_modules";

    public function __construct($db) {
        $this->conn = $db;
        $this->ensureTable();
        $this->ensureDefaultModules();
    }

    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `module_type` enum('category','carousel','slider','banner','banner_carousel') NOT NULL,
            `module_name` varchar(255) NOT NULL,
            `design_data` longtext DEFAULT NULL,
            `is_sponsored` tinyint(1) DEFAULT 0,
            `sort_order` int(11) DEFAULT 0,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }

    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['type'])) {
            $sql .= " AND module_type = ?";
            $params[] = $filters['type'];
            $types .= "s";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND module_name LIKE ?";
            $params[] = "%" . $filters['search'] . "%";
            $types .= "s";
        }

        $sql .= " ORDER BY sort_order ASC, id DESC";

        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($sql);
        }

        $modules = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $modules[] = $row;
            }
        }
        return $modules;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function create($data) {
        $type = in_array($data['module_type'] ?? '', ['category','carousel','slider','banner','banner_carousel']) ? $data['module_type'] : 'banner';
        $name = trim($data['module_name'] ?? '');
        $design = !empty($data['design_data']) ? (is_array($data['design_data']) ? json_encode($data['design_data'], JSON_UNESCAPED_UNICODE) : $data['design_data']) : '{}';
        $isSponsored = !empty($data['is_sponsored']) ? 1 : 0;
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $status = ($data['status'] ?? '') === 'inactive' ? 'inactive' : 'active';

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (module_type, module_name, design_data, is_sponsored, sort_order, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt) return false;

        $stmt->bind_param("sssiss", $type, $name, $design, $isSponsored, $sortOrder, $status);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update($id, $data) {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $type = $data['module_type'] ?? $existing['module_type'];
        $name = trim($data['module_name'] ?? $existing['module_name']);
        $design = isset($data['design_data']) ? (is_array($data['design_data']) ? json_encode($data['design_data'], JSON_UNESCAPED_UNICODE) : $data['design_data']) : $existing['design_data'];
        $isSponsored = isset($data['is_sponsored']) ? (!empty($data['is_sponsored']) ? 1 : 0) : $existing['is_sponsored'];
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : (int)$existing['sort_order'];
        $status = $data['status'] ?? $existing['status'];

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET module_type = ?, module_name = ?, design_data = ?, is_sponsored = ?, sort_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("sssissi", $type, $name, $design, $isSponsored, $sortOrder, $status, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function toggleStatus($id) {
        $mod = $this->getById($id);
        if (!$mod) return false;
        $newStatus = ($mod['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("si", $newStatus, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? $newStatus : false;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function duplicate($id) {
        $original = $this->getById($id);
        if (!$original) return false;

        $copyName = $original['module_name'] . ' (Copy)';
        $data = [
            'module_type' => $original['module_type'],
            'module_name' => $copyName,
            'design_data' => $original['design_data'],
            'is_sponsored' => $original['is_sponsored'],
            'sort_order' => (int)$original['sort_order'] + 1,
            'status' => 'inactive'
        ];
        return $this->create($data);
    }

    public function ensureDefaultModules() {
        $check = $this->conn->query("SELECT COUNT(*) as cnt FROM {$this->table}");
        if ($check) {
            $row = $check->fetch_assoc();
            if ((int)$row['cnt'] > 0) {
                return;
            }
        }

        $defaults = [
            [
                'module_type' => 'slider',
                'module_name' => 'Main Hero Promotional Slider',
                'is_sponsored' => 0,
                'sort_order' => 1,
                'status' => 'active',
                'design_data' => json_encode([
                    'autoplay' => true,
                    'autoplay_speed' => 5000,
                    'effect' => 'fade',
                    'navigation' => true,
                    'pagination' => true,
                    'slides' => [
                        [
                            'title' => 'Personalized Corporate Gifts & Swag',
                            'subtitle' => 'Premium branding with bulk discounts',
                            'image' => '/uploads/hero_banner_1.jpg',
                            'link' => '/categories/corporate-gifts',
                            'button_text' => 'Shop Now'
                        ],
                        [
                            'title' => 'Custom Apparel & Custom T-Shirts',
                            'subtitle' => 'High definition print quality that lasts',
                            'image' => '/uploads/hero_banner_2.jpg',
                            'link' => '/categories/custom-apparel',
                            'button_text' => 'Explore Collection'
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'module_type' => 'category',
                'module_name' => 'Popular Category Quick Grid',
                'is_sponsored' => 0,
                'sort_order' => 2,
                'status' => 'active',
                'design_data' => json_encode([
                    'layout' => 'circle_grid',
                    'columns' => 6,
                    'show_count' => true,
                    'categories' => ['Drinkware', 'Stationery', 'Apparel', 'Tech Accessories', 'Stickers & Labels', 'Packaging Boxes']
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'module_type' => 'banner_carousel',
                'module_name' => 'Trending Offers & Seasonal Discounts',
                'is_sponsored' => 1,
                'sort_order' => 3,
                'status' => 'active',
                'design_data' => json_encode([
                    'items_per_view' => 3,
                    'gap' => 20,
                    'badge' => 'SPECIAL OFFER',
                    'badge_color' => '#f8991d',
                    'cards' => [
                        ['title' => 'Flat 20% Off on Mugs & Bottles', 'code' => 'DRINK20', 'bg' => '#f0fdf4'],
                        ['title' => 'Buy 50 Custom Stickers, Get 10 Free', 'code' => 'STICKER50', 'bg' => '#eff6ff'],
                        ['title' => 'Free Delivery on Orders Above ₹999', 'code' => 'FREESHIP', 'bg' => '#faf5ff']
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'module_type' => 'carousel',
                'module_name' => 'Bestselling Custom Merchandise Swiper',
                'is_sponsored' => 0,
                'sort_order' => 4,
                'status' => 'active',
                'design_data' => json_encode([
                    'display_source' => 'bestseller_products',
                    'limit' => 10,
                    'show_rating' => true,
                    'show_quick_view' => true
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'module_type' => 'banner',
                'module_name' => 'Bulk Corporate & Institutional Inquiries Callout',
                'is_sponsored' => 0,
                'sort_order' => 5,
                'status' => 'active',
                'design_data' => json_encode([
                    'headline' => 'Need Large Scale Bulk Custom Merchandise?',
                    'description' => 'Get dedicated account management, doorstep sampling, and wholesale enterprise tier rates.',
                    'cta_text' => 'Request Bulk Quote',
                    'cta_link' => '/bulk-inquiry',
                    'bg_gradient' => 'linear-gradient(135deg, #0B53A1 0%, #032b57 100%)'
                ], JSON_UNESCAPED_UNICODE)
            ]
        ];

        foreach ($defaults as $d) {
            $this->create($d);
        }
    }
}
?>
