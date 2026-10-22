<?php
require_once(__DIR__ . '/../config/database.php');

class ProductLayoutModel {
    private $db;
    private $table = 'product_page_sections';

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get all product detail layout sections for a target (mobile/desktop) ordered by display_order.
     */
    public function getAllSections(string $target = 'mobile'): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE page_target = ? 
             ORDER BY display_order ASC, id ASC",
            [$target]
        );
    }

    /**
     * Get all active layout sections for a target.
     */
    public function getActiveSections(string $target = 'mobile'): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE page_target = ? AND status = 'active' 
             ORDER BY display_order ASC, id ASC",
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
     * Create a new product layout section.
     */
    public function createSection(array $data): int {
        // Auto calculate next display order if not specified
        if (!isset($data['display_order']) || (int)$data['display_order'] === 0) {
            $maxRow = $this->db->fetch(
                "SELECT MAX(display_order) as max_order FROM {$this->table} WHERE page_target = ?",
                [$data['page_target'] ?? 'mobile']
            );
            $data['display_order'] = ((int)($maxRow['max_order'] ?? 0)) + 1;
        }

        return $this->db->insert($this->table, [
            'page_target'          => $data['page_target'] ?? 'mobile',
            'section_type'         => $data['section_type'],
            'section_key'          => $data['section_key'],
            'label'                => $data['label'],
            'columns_per_row'      => (int)($data['columns_per_row'] ?? 2),
            'is_slider'            => (int)($data['is_slider'] ?? 0),
            'api_action'           => $data['api_action'] ?? null,
            'category_ids'         => $data['category_ids'] ?? null,
            'sub_category_ids'     => $data['sub_category_ids'] ?? null,
            'product_ids'          => $data['product_ids'] ?? null,
            'product_limit'        => (int)($data['product_limit'] ?? 8),
            'badge_text'           => $data['badge_text'] ?? null,
            'background_image_url' => $data['background_image_url'] ?? null,
            'banner_target_url'    => $data['banner_target_url'] ?? null,
            'custom_html'          => $data['custom_html'] ?? null,
            'display_order'        => (int)$data['display_order'],
            'status'               => $data['status'] ?? 'active'
        ]);
    }

    /**
     * Update an existing product layout section.
     */
    public function updateSection(int $id, array $data): bool {
        $allowedFields = [
            'section_type', 'section_key', 'label', 'columns_per_row',
            'is_slider', 'api_action', 'category_ids', 'sub_category_ids',
            'product_ids', 'product_limit', 'badge_text', 'background_image_url',
            'banner_target_url', 'custom_html', 'display_order', 'status'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update($this->table, $updateData, 'id = ?', [$id]);
    }

    /**
     * Toggle status (active/inactive).
     */
    public function toggleSectionStatus(int $id, string $status): bool {
        return $this->db->update($this->table, ['status' => $status], 'id = ?', [$id]);
    }

    /**
     * Update display order.
     */
    public function updateSectionOrder(int $id, int $order): bool {
        return $this->db->update($this->table, ['display_order' => $order], 'id = ?', [$id]);
    }

    /**
     * Delete section.
     */
    public function deleteSection(int $id): bool {
        return $this->db->delete($this->table, 'id = ?', [$id]);
    }
}
