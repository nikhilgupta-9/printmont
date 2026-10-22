<?php
require_once(__DIR__ . '/../models/ProductLayoutModel.php');
require_once(__DIR__ . '/../models/BannerModel.php');

class ProductLayoutController {
    private ProductLayoutModel $layoutModel;
    private BannerModel $bannerModel;
    private Database $db;

    public function __construct() {
        $this->layoutModel = new ProductLayoutModel();
        $this->bannerModel = new BannerModel();
        $this->db = new Database();
    }

    /**
     * Get sections for layout manager (all sections ordered).
     */
    public function getSections(string $target = 'mobile'): array {
        $sections = $this->layoutModel->getAllSections($target);

        // Fetch associated banners if section is a banner/slider type
        foreach ($sections as &$section) {
            $sType = $section['section_type'];
            if (in_array($sType, ['small_banner_carousel', 'small_banner_slider'])) {
                $section['banners'] = $this->db->fetchAll(
                    "SELECT id, title, desktop_image, mobile_image, desktop_url, mobile_url, 
                            desktop_sort_order, mobile_sort_order, desktop_status, mobile_status, auto_play_speed
                     FROM sliders
                     WHERE slider_type = 'small' AND status = 'active'
                       AND (target_pages IS NULL OR target_pages = '' OR target_pages = 'all' OR FIND_IN_SET('product', target_pages) OR target_pages LIKE '%product%')
                     ORDER BY mobile_sort_order ASC, desktop_sort_order ASC, id ASC"
                );
            } elseif (in_array($sType, ['slider', 'banner', 'one_and_half_carousel', 'marketing_banners'])) {
                $sectionDbRow = $this->db->fetch(
                    "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                    [$section['section_key']]
                );
                if ($sectionDbRow) {
                    $section['banners'] = $this->bannerModel->getBannersBySection((int)$sectionDbRow['id']);
                } else {
                    $section['banners'] = [];
                }
            } else {
                $section['banners'] = [];
            }
        }
        return $sections;
    }

    /**
     * Get active sections for frontend consumption.
     */
    public function getActiveSections(string $target = 'mobile'): array {
        $sections = $this->layoutModel->getActiveSections($target);

        foreach ($sections as &$section) {
            $sType = $section['section_type'];
            if (in_array($sType, ['small_banner_carousel', 'small_banner_slider'])) {
                $section['banners'] = $this->db->fetchAll(
                    "SELECT id, title, desktop_image, mobile_image, desktop_url, mobile_url, 
                            desktop_sort_order, mobile_sort_order, desktop_status, mobile_status, auto_play_speed
                     FROM sliders
                     WHERE slider_type = 'small' AND status = 'active'
                       AND (target_pages IS NULL OR target_pages = '' OR target_pages = 'all' OR FIND_IN_SET('product', target_pages) OR target_pages LIKE '%product%')
                     ORDER BY mobile_sort_order ASC, desktop_sort_order ASC, id ASC"
                );
            } elseif (in_array($sType, ['slider', 'banner', 'one_and_half_carousel', 'marketing_banners'])) {
                $sectionDbRow = $this->db->fetch(
                    "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                    [$section['section_key']]
                );
                if ($sectionDbRow) {
                    $section['banners'] = $this->bannerModel->getBannersBySection((int)$sectionDbRow['id']);
                } else {
                    $section['banners'] = [];
                }
            } else {
                $section['banners'] = [];
            }
        }
        return $sections;
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleSection(int $id, string $status): array {
        if (!in_array($status, ['active', 'inactive'])) {
            return ['success' => false, 'error' => 'Invalid status.'];
        }
        $success = $this->layoutModel->toggleSectionStatus($id, $status);
        if ($success) {
            return ['success' => true, 'message' => 'Section status updated successfully.'];
        }
        return ['success' => false, 'error' => 'Failed to update section status.'];
    }

    /**
     * Update order of multiple sections.
     */
    public function reorderSections(array $sectionIds): array {
        foreach ($sectionIds as $index => $id) {
            $this->layoutModel->updateSectionOrder((int)$id, $index + 1);
        }
        return ['success' => true, 'message' => 'Sections reordered successfully.'];
    }

    /**
     * Create section.
     */
    public function createSection(array $data): array {
        if (empty($data['label'])) {
            return ['success' => false, 'error' => 'Section Title / Label is required.'];
        }
        if (empty($data['section_type'])) {
            return ['success' => false, 'error' => 'Section Type is required.'];
        }
        if (empty($data['section_key'])) {
            $data['section_key'] = 'pdp_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($data['label']))) . '_' . time();
        }

        $id = $this->layoutModel->createSection($data);
        if ($id) {
            return ['success' => true, 'message' => 'Section created successfully.', 'id' => $id];
        }
        return ['success' => false, 'error' => 'Failed to create section.'];
    }

    /**
     * Update section.
     */
    public function updateSection(int $id, array $data): array {
        $section = $this->layoutModel->getSectionById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found.'];
        }

        $success = $this->layoutModel->updateSection($id, $data);
        if ($success) {
            return ['success' => true, 'message' => 'Section updated successfully.'];
        }
        return ['success' => false, 'error' => 'Failed to update section or no changes made.'];
    }

    /**
     * Delete section.
     */
    public function deleteSection(int $id): array {
        $section = $this->layoutModel->getSectionById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found.'];
        }
        $success = $this->layoutModel->deleteSection($id);
        if ($success) {
            return ['success' => true, 'message' => 'Section deleted successfully.'];
        }
        return ['success' => false, 'error' => 'Failed to delete section.'];
    }
}
