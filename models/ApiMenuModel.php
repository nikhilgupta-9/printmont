<?php
require_once(__DIR__ . '/../config/database.php');

class ApiMenuModel {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = new Database();
    }

    // Categories flagged to show ON the home page, for one device.
    public function getHomeMenu(string $device): array {
        $showCol  = $device === 'mobile' ? 'mobile_home_show'  : 'desktop_home_show';
        $orderCol = $device === 'mobile' ? 'mobile_home_order' : 'desktop_home_order';

        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status = 'active' AND {$showCol} = 'yes'
             ORDER BY {$orderCol} ASC, display_order ASC limit 12"
        );
    }

    // All active categories in ONE query, used to build the inner-page menu tree in-memory.
    public function getAllActiveFlat(): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status = 'active'
             ORDER BY display_order ASC, name ASC"
        );
    }
}  
?>
