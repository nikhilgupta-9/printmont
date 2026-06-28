<?php
require_once(__DIR__ . '/../config/database.php');

class ApiBannerModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get active banners for a section by its section_key.
     * Returns banners + section meta (columns_per_row, is_slider, label).
     */
    public function getBySectionKey(string $sectionKey): array {
        $section = $this->db->fetch(
            "SELECT id, label, columns_per_row, is_slider FROM banner_sections
             WHERE section_key = ? AND status = 'active' LIMIT 1",
            [$sectionKey]
        );

        if (!$section) {
            return [];
        }

        $banners = $this->db->fetchAll(
            "SELECT id, title, description,
                    image_url_desktop, image_url_mobile,
                    target_url, display_order
             FROM banners
             WHERE section_id = ? AND status = 'active'
               AND (start_date IS NULL OR start_date <= NOW())
               AND (end_date IS NULL OR end_date >= NOW())
             ORDER BY display_order ASC",
            [$section['id']]
        );

        return [
            'section_key'     => $sectionKey,
            'label'           => $section['label'],
            'columns_per_row' => (int) $section['columns_per_row'],
            'is_slider'       => (bool) $section['is_slider'],
            'banners'         => $banners,
        ];
    }

    /**
     * Get all active sections for a page with their banners.
     * Used by the frontend to fetch everything in one request.
     */
    public function getByPage(string $page): array {
        $sections = $this->db->fetchAll(
            "SELECT id, section_key, label, columns_per_row, is_slider
             FROM banner_sections
             WHERE page = ? AND status = 'active'
             ORDER BY display_order ASC",
            [$page]
        );

        $result = [];
        foreach ($sections as $section) {
            $banners = $this->db->fetchAll(
                "SELECT id, title, description,
                        image_url_desktop, image_url_mobile,
                        target_url, display_order
                 FROM banners
                 WHERE section_id = ? AND status = 'active'
                   AND (start_date IS NULL OR start_date <= NOW())
                   AND (end_date IS NULL OR end_date >= NOW())
                 ORDER BY display_order ASC",
                [$section['id']]
            );

            if (!empty($banners)) {
                $result[$section['section_key']] = [
                    'label'           => $section['label'],
                    'columns_per_row' => (int) $section['columns_per_row'],
                    'is_slider'       => (bool) $section['is_slider'],
                    'banners'         => $banners,
                ];
            }
        }

        return $result;
    }

    /**
     * Get all active sections list (for admin dropdowns, etc.)
     */
    public function getAllSections(): array {
        return $this->db->fetchAll(
            "SELECT id, page, section_key, label, columns_per_row, is_slider, display_order
             FROM banner_sections
             WHERE status = 'active'
             ORDER BY page ASC, display_order ASC"
        );
    }

    public function getBannerById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT id, title, description, image_url_desktop, image_url_mobile,
                    target_url, display_order, section_id, status, start_date, end_date
             FROM banners WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }
}
?>
