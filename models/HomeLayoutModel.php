<?php
require_once(__DIR__ . '/../config/database.php');

class HomeLayoutModel {
    private $db;
    private $table = 'home_sections';

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get all homepage layout sections for a target (desktop or mobile) ordered by display_order.
     */
    public function getAllSections(string $target): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE page_target = ? 
             ORDER BY display_order ASC",
            [$target]
        );
    }

    /**
     * Get all active layout sections for a target (for front-end consumption).
     */
    public function getActiveSections(string $target): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE page_target = ? AND status = 'active' 
             ORDER BY display_order ASC",
            [$target]
        );
    }

    /**
     * Get a single section by its ID.
     */
    public function getSectionById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    /**
     * Get a single section by page target and key.
     */
    public function getSectionByKey(string $target, string $key): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE page_target = ? AND section_key = ?",
            [$target, $key]
        );
        return $row ?: null;
    }

    /**
     * Create a new homepage layout section.
     */
    public function createSection(array $data): int|false {
        return $this->db->insert(
            "INSERT INTO {$this->table}
             (page_target, section_type, section_key, label, columns_per_row, is_slider, 
              api_action, product_limit, badge_text, background_image_url, display_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['page_target'],
                $data['section_type'],
                $data['section_key'],
                $data['label'],
                $data['columns_per_row'] ?? 1,
                $data['is_slider'] ?? 0,
                $data['api_action'] ?? null,
                $data['product_limit'] ?? null,
                $data['badge_text'] ?? null,
                $data['background_image_url'] ?? null,
                $data['display_order'] ?? 0,
                $data['status'] ?? 'active'
            ]
        );
    }

    /**
     * Update an existing homepage layout section.
     */
    public function updateSection(int $id, array $data): bool {
        return (bool)$this->db->execute(
            "UPDATE {$this->table} SET
             label = ?, columns_per_row = ?, is_slider = ?, api_action = ?, 
             product_limit = ?, badge_text = ?, background_image_url = ?, status = ?,
             updated_at = NOW()
             WHERE id = ?",
            [
                $data['label'],
                $data['columns_per_row'] ?? 1,
                $data['is_slider'] ?? 0,
                $data['api_action'] ?? null,
                $data['product_limit'] ?? null,
                $data['badge_text'] ?? null,
                $data['background_image_url'] ?? null,
                $data['status'] ?? 'active',
                $id
            ]
        );
    }

    /**
     * Update the display order of a section.
     */
    public function updateSectionOrder(int $id, int $order): bool {
        return (bool)$this->db->execute(
            "UPDATE {$this->table} SET display_order = ?, updated_at = NOW() WHERE id = ?",
            [$order, $id]
        );
    }

    /**
     * Delete a homepage layout section.
     */
    public function deleteSection(int $id): bool {
        return (bool)$this->db->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Toggle the status (active/inactive) of a layout section.
     */
    public function toggleSectionStatus(int $id, string $status): bool {
        return (bool)$this->db->execute(
            "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $id]
        );
    }

    /**
     * Get maximum display order for a target page layout.
     */
    public function getMaxOrder(string $target): int {
        $row = $this->db->fetch(
            "SELECT MAX(display_order) AS max_order FROM {$this->table} WHERE page_target = ?",
            [$target]
        );
        return (int)($row['max_order'] ?? 0);
    }
}
?>
