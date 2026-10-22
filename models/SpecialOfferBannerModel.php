<?php
require_once __DIR__ . '/../config/database.php';

class SpecialOfferBannerModel {
    private $db;
    private $table = 'special_offer_banners';

    public function __construct() {
        $this->db = new Database();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NULL,
            subtitle VARCHAR(255) NULL,
            offer_badge VARCHAR(100) NULL,
            badge_color VARCHAR(50) DEFAULT '#ef4444',
            display_type ENUM('banner', 'carousel') NOT NULL DEFAULT 'banner',
            layout_format VARCHAR(50) NOT NULL DEFAULT '1',
            device_target ENUM('all', 'desktop', 'mobile') NOT NULL DEFAULT 'all',
            image_desktop VARCHAR(500) NULL,
            image_mobile VARCHAR(500) NULL,
            link_url VARCHAR(500) NULL,
            open_in_new_tab TINYINT(1) NOT NULL DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        try {
            $this->db->execute($sql);
        } catch (Exception $e) {
            // Ignored if already exists
        }
    }

    public function getAll($target = 'all', $type = 'all', $status = 'all') {
        $conditions = [];
        $params = [];

        if ($target !== 'all') {
            $conditions[] = "(device_target = ? OR device_target = 'all')";
            $params[] = $target;
        }

        if ($type !== 'all') {
            $conditions[] = "display_type = ?";
            $params[] = $type;
        }

        if ($status !== 'all') {
            $conditions[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $query = "SELECT * FROM {$this->table} {$whereClause} ORDER BY sort_order ASC, id DESC";

        return $this->db->fetchAll($query, $params);
    }

    public function getActiveForApi($target = 'all', $type = 'all') {
        $conditions = ["status = 'active'"];
        $params = [];

        if ($target !== 'all') {
            $conditions[] = "(device_target = ? OR device_target = 'all')";
            $params[] = $target;
        }

        if ($type !== 'all') {
            $conditions[] = "display_type = ?";
            $params[] = $type;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        $query = "SELECT * FROM {$this->table} {$whereClause} ORDER BY sort_order ASC, id DESC";

        return $this->db->fetchAll($query, $params);
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (
            title, subtitle, offer_badge, badge_color, display_type, layout_format, 
            device_target, image_desktop, image_mobile, link_url, open_in_new_tab, 
            start_date, end_date, sort_order, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $data['title'] ?? '',
            $data['subtitle'] ?? '',
            $data['offer_badge'] ?? '',
            $data['badge_color'] ?? '#ef4444',
            $data['display_type'] ?? 'banner',
            $data['layout_format'] ?? '1',
            $data['device_target'] ?? 'all',
            $data['image_desktop'] ?? '',
            $data['image_mobile'] ?? '',
            $data['link_url'] ?? '',
            !empty($data['open_in_new_tab']) ? 1 : 0,
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            (int)($data['sort_order'] ?? 0),
            $data['status'] ?? 'active'
        ];

        return $this->db->insert($query, $params);
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET 
            title = ?, subtitle = ?, offer_badge = ?, badge_color = ?, display_type = ?, 
            layout_format = ?, device_target = ?, image_desktop = ?, image_mobile = ?, 
            link_url = ?, open_in_new_tab = ?, start_date = ?, end_date = ?, 
            sort_order = ?, status = ? 
            WHERE id = ?";

        $params = [
            $data['title'] ?? '',
            $data['subtitle'] ?? '',
            $data['offer_badge'] ?? '',
            $data['badge_color'] ?? '#ef4444',
            $data['display_type'] ?? 'banner',
            $data['layout_format'] ?? '1',
            $data['device_target'] ?? 'all',
            $data['image_desktop'] ?? '',
            $data['image_mobile'] ?? '',
            $data['link_url'] ?? '',
            !empty($data['open_in_new_tab']) ? 1 : 0,
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            (int)($data['sort_order'] ?? 0),
            $data['status'] ?? 'active',
            $id
        ];

        return $this->db->execute($query, $params);
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->execute($query, [$id]);
    }

    public function toggleStatus($id) {
        $current = $this->getById($id);
        if (!$current) return false;
        $newStatus = ($current['status'] === 'active') ? 'inactive' : 'active';
        $query = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        return $this->db->execute($query, [$newStatus, $id]);
    }

    public function updateSortOrder($id, $order) {
        $query = "UPDATE {$this->table} SET sort_order = ? WHERE id = ?";
        return $this->db->execute($query, [(int)$order, $id]);
    }
}
