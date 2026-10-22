<?php
require_once(__DIR__ . '/../config/database.php');

class ApiMenuModel {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = new Database();
    }

    // Categories flagged to show in the top menu/bar for one device.
    public function getHomeMenu(string $device): array {
        $statusCol = $device === 'mobile' ? 'mobile_topbar_status' : 'desktop_menu_status';
        $menuOrderCol = $device === 'mobile' ? 'mobile_topbar_order' : 'desktop_menu_order';

        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status = 'active' AND {$statusCol} = 'show'
             ORDER BY 
                CASE 
                    WHEN {$menuOrderCol} > 0 THEN {$menuOrderCol}
                    WHEN display_order > 0 THEN display_order 
                    ELSE 999999 
                END ASC, 
                display_order ASC, 
                name ASC 
             LIMIT 25"
        );
    }

    // All active categories in ONE query, used to build the inner-page menu tree in-memory.
    public function getAllActiveFlat(): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status = 'active'
             ORDER BY 
                CASE 
                    WHEN desktop_menu_order > 0 THEN desktop_menu_order
                    WHEN display_order > 0 THEN display_order 
                    ELSE 999999 
                END ASC, 
                display_order ASC, 
                name ASC"
        );
    }
}  
?>
