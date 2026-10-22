<?php
require_once(__DIR__ . '/../models/HomeLayoutModel.php');
require_once(__DIR__ . '/../models/BannerModel.php');

class HomeLayoutController {
    private HomeLayoutModel $layoutModel;
    private BannerModel $bannerModel;
    private Database $db;

    public function __construct() {
        $this->layoutModel = new HomeLayoutModel();
        $this->bannerModel = new BannerModel();
        $this->db = new Database();
    }

    /**
     * Get sections for the layout manager (both active and inactive).
     */
    public function getSections(string $target): array {
        $sections = $this->layoutModel->getAllSections($target);
        
        // For banner/slider sections, fetch their associated banner images
        foreach ($sections as &$section) {
            $isBannerType = in_array($section['section_type'], ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']);
            if ($isBannerType) {
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
     * Toggle the status of a section.
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
     * Delete a section.
     */
    public function deleteSection(int $id): array {
        $section = $this->layoutModel->getSectionById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found.'];
        }

        // If it's a banner/slider section, clean up associated banners and files
        $isBannerType = in_array($section['section_type'], ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']);
        if ($isBannerType) {
            $sectionDbRow = $this->db->fetch(
                "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                [$section['section_key']]
            );
            if ($sectionDbRow) {
                $sectionId = (int)$sectionDbRow['id'];
                $banners = $this->bannerModel->getBannersBySection($sectionId);
                foreach ($banners as $b) {
                    // Delete files
                    if (!empty($b['image_url_desktop']) && file_exists(__DIR__ . '/../' . $b['image_url_desktop'])) {
                        @unlink(__DIR__ . '/../' . $b['image_url_desktop']);
                    }
                    if (!empty($b['image_url_mobile']) && file_exists(__DIR__ . '/../' . $b['image_url_mobile'])) {
                        @unlink(__DIR__ . '/../' . $b['image_url_mobile']);
                    }
                    $this->bannerModel->deleteBanner((int)$b['id']);
                }
                // Delete banner section
                $this->db->execute(
                    "DELETE FROM banner_sections WHERE id = ?",
                    [$sectionId]
                );
            }
        }

        $success = $this->layoutModel->deleteSection($id);
        if ($success) {
            return ['success' => true, 'message' => 'Section deleted successfully.'];
        }
        return ['success' => false, 'error' => 'Failed to delete section.'];
    }

    /**
     * Duplicate a section structure.
     */
    public function duplicateSection(int $id): array {
        $section = $this->layoutModel->getSectionById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found.'];
        }

        $newKey = $section['section_key'] . '_copy_' . time();
        $baseLabel = !empty(trim($section['label'] ?? '')) ? trim($section['label']) : ucwords(str_replace(['_', '-'], ' ', $section['section_key']));
        $newLabel = $baseLabel . ' (Copy)';

        // Position directly below the source section
        $targetOrder = (int)$section['display_order'] + 1;
        $this->db->execute(
            "UPDATE home_sections SET display_order = display_order + 1 WHERE page_target = ? AND display_order >= ?",
            [$section['page_target'], $targetOrder]
        );

        $data = [
            'page_target' => $section['page_target'],
            'section_type' => $section['section_type'],
            'section_key' => $newKey,
            'label' => $newLabel,
            'columns_per_row' => $section['columns_per_row'],
            'is_slider' => $section['is_slider'],
            'api_action' => $section['api_action'],
            'category_ids' => $section['category_ids'] ?? null,
            'sub_category_ids' => $section['sub_category_ids'] ?? null,
            'product_ids' => $section['product_ids'] ?? null,
            'product_limit' => $section['product_limit'],
            'badge_text' => $section['badge_text'],
            'background_image_url' => $section['background_image_url'],
            'display_order' => $targetOrder,
            'status' => $section['status'] ?? 'active'
        ];

        $newId = $this->layoutModel->createSection($data);
        if (!$newId) {
            return ['success' => false, 'error' => 'Failed to duplicate section.'];
        }

        // If it's a banner/slider type, duplicate banner_sections and banner images
        $isBannerType = in_array($section['section_type'], ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']);
        if ($isBannerType) {
            $origBannerSec = $this->db->fetch(
                "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                [$section['section_key']]
            );
            if ($origBannerSec) {
                $newBannerSectionId = $this->db->insert(
                    "INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status) 
                     VALUES (?, ?, ?, ?, ?, 'active')",
                    [$section['page_target'], $newKey, $newLabel, $section['columns_per_row'], $section['is_slider']]
                );

                if ($newBannerSectionId) {
                    $originalBanners = $this->bannerModel->getBannersBySection((int)$origBannerSec['id']);
                    foreach ($originalBanners as $b) {
                        $this->bannerModel->createBanner([
                            'title' => $b['title'] ?? 'Banner',
                            'description' => $b['description'] ?? '',
                            'image_url_desktop' => $b['image_url_desktop'] ?? '',
                            'image_url_mobile' => $b['image_url_mobile'] ?? '',
                            'target_url' => $b['target_url'] ?? '',
                            'section_id' => $newBannerSectionId,
                            'display_order' => $b['display_order'] ?? 1,
                            'status' => 'active',
                            'start_date' => $b['start_date'] ?? null,
                            'end_date' => $b['end_date'] ?? null
                        ]);
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Section duplicated successfully!',
            'id' => $newId,
            'display_order' => $targetOrder,
            'page_target' => $section['page_target']
        ];
    }

    /**
     * Create a layout section (Banners, Sliders, or Products).
     */
    public function createSection(array $post, array $files): array {
        $pageTarget = $post['page_target'] ?? 'desktop';
        $sectionType = $post['section_type'] ?? '';
        $label = trim($post['label'] ?? '');

        if (empty($sectionType)) {
            return ['success' => false, 'error' => 'Section type is required.'];
        }

        // Determine section key
        $sectionKey = trim($post['section_key'] ?? '');
        if (empty($sectionKey)) {
            $keyBase = !empty($label) ? $label : $sectionType;
            $sectionKey = 'sec_' . strtolower(preg_replace('/[^A-Za-z0-9]/', '_', $keyBase)) . '_' . time();
        }

        $maxOrder = $this->layoutModel->getMaxOrder($pageTarget);

        $isSlider = in_array($sectionType, ['slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']) ? 1 : 0;
        $isBannerType = in_array($sectionType, ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']);

        if ($isBannerType) {
            $columnsPerRow = (int)($post['columns_per_row'] ?? 1);
            // 1. Create entry in banner_sections
            $bannerSectionId = $this->db->insert(
                "INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status) 
                 VALUES (?, ?, ?, ?, ?, 'active')",
                [$pageTarget, $sectionKey, $label, $columnsPerRow, $isSlider]
            );

            if (!$bannerSectionId) {
                return ['success' => false, 'error' => 'Failed to create banner section database record.'];
            }

            // 2. Upload and create individual banners (if any were provided)
            $this->saveBannersForSection($bannerSectionId, $post, $files);

            // 3. Create entry in home_sections
            $data = [
                'page_target' => $pageTarget,
                'section_type' => $sectionType,
                'section_key' => $sectionKey,
                'label' => $label,
                'columns_per_row' => $columnsPerRow,
                'is_slider' => $isSlider,
                'display_order' => $maxOrder + 1,
                'status' => 'active'
            ];
            
            $id = $this->layoutModel->createSection($data);
            if ($id) {
                return ['success' => true, 'message' => ucfirst($sectionType) . ' section created successfully!', 'id' => $id];
            }
        } else {
            // Product or Custom Section Creation
            $isProductType = in_array($sectionType, ['product_carousel', 'mobile_list', 'compact_grid', 'compact_product_grid', 'featured_grid']);
            $apiAction = !empty($post['api_action']) ? $post['api_action'] : ($isProductType ? 'bestseller' : null);

            $data = [
                'page_target' => $pageTarget,
                'section_type' => $sectionType,
                'section_key' => $sectionKey,
                'label' => $label,
                'columns_per_row' => (int)($post['columns_per_row'] ?? 2),
                'api_action' => $apiAction,
                'category_ids' => !empty($post['category_ids']) ? $post['category_ids'] : null,
                'sub_category_ids' => !empty($post['sub_category_ids']) ? $post['sub_category_ids'] : null,
                'product_ids' => !empty($post['product_ids']) ? $post['product_ids'] : null,
                'product_limit' => !empty($post['product_limit']) ? (int)$post['product_limit'] : 10,
                'badge_text' => !empty($post['badge_text']) ? $post['badge_text'] : null,
                'background_image_url' => !empty($post['background_image_url']) ? $post['background_image_url'] : null,
                'display_order' => $maxOrder + 1,
                'status' => 'active'
            ];

            $id = $this->layoutModel->createSection($data);
            if ($id) {
                return ['success' => true, 'message' => 'Section created successfully!', 'id' => $id];
            }
        }

        return ['success' => false, 'error' => 'Failed to create section.'];
    }

    /**
     * Update layout section configurations or banners.
     */
    public function updateSection(int $id, array $post, array $files): array {
        $section = $this->layoutModel->getSectionById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found.'];
        }

        $post['page_target'] = $post['page_target'] ?? $section['page_target'] ?? 'desktop';

        $label = isset($post['label']) ? trim($post['label']) : ($section['label'] ?? '');

        $sectionType = trim($post['section_type'] ?? $section['section_type']);
        $columnsPerRow = (int)($post['columns_per_row'] ?? $section['columns_per_row']);
        $isSlider = in_array($sectionType, ['slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']) ? 1 : 0;
        $isBannerType = in_array($sectionType, ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel']);

        if ($isBannerType) {
            $sectionDbRow = $this->db->fetch(
                "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                [$section['section_key']]
            );

            if ($sectionDbRow) {
                $bannerSectionId = (int)$sectionDbRow['id'];
                $this->db->execute(
                    "UPDATE banner_sections SET label = ?, columns_per_row = ?, is_slider = ?, updated_at = NOW() WHERE id = ?",
                    [$label, $columnsPerRow, $isSlider, $bannerSectionId]
                );

                // Re-upload or add new banners
                $this->saveBannersForSection($bannerSectionId, $post, $files);
            } else {
                $bannerSectionId = $this->db->insert(
                    "INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status) 
                     VALUES (?, ?, ?, ?, ?, 'active')",
                    [$section['page_target'], $section['section_key'], $label, $columnsPerRow, $isSlider]
                );
                if ($bannerSectionId) {
                    $this->saveBannersForSection($bannerSectionId, $post, $files);
                }
            }
        }

        $isProductType = in_array($sectionType, ['product_carousel', 'mobile_list', 'compact_grid', 'compact_product_grid', 'featured_grid']);
        $apiAction = !empty($post['api_action']) ? $post['api_action'] : ($isProductType ? (!empty($section['api_action']) ? $section['api_action'] : 'bestseller') : null);

        $data = [
            'section_type' => $sectionType,
            'label' => $label,
            'columns_per_row' => $columnsPerRow,
            'is_slider' => $isSlider,
            'api_action' => $apiAction,
            'category_ids' => !empty($post['category_ids']) ? $post['category_ids'] : null,
            'sub_category_ids' => !empty($post['sub_category_ids']) ? $post['sub_category_ids'] : null,
            'product_ids' => !empty($post['product_ids']) ? $post['product_ids'] : null,
            'product_limit' => !empty($post['product_limit']) ? (int)$post['product_limit'] : 10,
            'badge_text' => !empty($post['badge_text']) ? $post['badge_text'] : null,
            'background_image_url' => !empty($post['background_image_url']) ? $post['background_image_url'] : null,
            'status' => $post['status'] ?? $section['status']
        ];

        $success = $this->layoutModel->updateSection($id, $data);

        if ($success) {
            return ['success' => true, 'message' => 'Section updated successfully!'];
        }
        return ['success' => false, 'error' => 'Failed to update section settings.'];
    }

    /**
     * Process banner files and save them to the database (handles create, update, and deletions).
     */
    private function saveBannersForSection(int $bannerSectionId, array $post, array $files): void {
        // Delete all banner database records for this section
        $this->db->execute("DELETE FROM banners WHERE section_id = ?", [$bannerSectionId]);

        $pageTarget = $post['page_target'] ?? 'desktop';

        // Iterate through all slots submitted in the post
        if (!empty($post['banner_titles'])) {
            foreach ($post['banner_titles'] as $index => $title) {
                $desktopPath = $post['existing_desktop'][$index] ?? '';
                $mobilePath = $post['existing_mobile'][$index] ?? '';

                $newDesktopUploaded = false;
                $newMobileUploaded = false;

                // Check if a new desktop image was uploaded
                if (!empty($files['images_desktop']['name'][$index])) {
                    $fileDesktop = [
                        'name' => $files['images_desktop']['name'][$index],
                        'type' => $files['images_desktop']['type'][$index] ?? '',
                        'tmp_name' => $files['images_desktop']['tmp_name'][$index],
                        'error' => $files['images_desktop']['error'][$index] ?? 0,
                        'size' => $files['images_desktop']['size'][$index] ?? 0
                    ];
                    $uploadRes = $this->uploadImageFile($fileDesktop, 'desktop');
                    if (is_string($uploadRes) && !empty($uploadRes)) {
                        $desktopPath = $uploadRes;
                        $newDesktopUploaded = true;
                    }
                }

                // Check if a new mobile image was uploaded
                if (!empty($files['images_mobile']['name'][$index])) {
                    $fileMobile = [
                        'name' => $files['images_mobile']['name'][$index],
                        'type' => $files['images_mobile']['type'][$index] ?? '',
                        'tmp_name' => $files['images_mobile']['tmp_name'][$index],
                        'error' => $files['images_mobile']['error'][$index] ?? 0,
                        'size' => $files['images_mobile']['size'][$index] ?? 0
                    ];
                    $uploadRes = $this->uploadImageFile($fileMobile, 'mobile');
                    if (is_string($uploadRes) && !empty($uploadRes)) {
                        $mobilePath = $uploadRes;
                        $newMobileUploaded = true;
                    }
                }

                if ($pageTarget === 'mobile') {
                    // Mobile section: Clean dedicated mobile handling
                    $img = $newMobileUploaded ? $mobilePath : ($newDesktopUploaded ? $desktopPath : ($mobilePath ?: $desktopPath));
                    if (empty($img)) {
                        continue;
                    }
                    $desktopPath = $img;
                    $mobilePath = $img;
                } else {
                    // Desktop section: Clean desktop handling with optional mobile override
                    if ($newDesktopUploaded && !$newMobileUploaded && empty($mobilePath)) {
                        $mobilePath = $desktopPath;
                    }
                    if (empty($desktopPath)) {
                        $desktopPath = $mobilePath;
                    }
                    if (empty($desktopPath)) {
                        continue;
                    }
                }

                // Create banner record
                $bannerData = [
                    'title' => !empty($title) ? trim($title) : 'Banner ' . ($index + 1),
                    'description' => '',
                    'image_url_desktop' => $desktopPath,
                    'image_url_mobile' => $mobilePath ?: $desktopPath,
                    'target_url' => !empty($post['banner_targets'][$index]) ? trim($post['banner_targets'][$index]) : '',
                    'section_id' => $bannerSectionId,
                    'display_order' => $index + 1,
                    'status' => 'active',
                    'start_date' => null,
                    'end_date' => null
                ];

                $this->bannerModel->createBanner($bannerData);
            }
        }
    }

    /**
     * File upload helper.
     */
    private function uploadImageFile(array $file, string $type): string|array {
        if (empty($file) || empty($file['name'])) {
            return '';
        }

        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
        if (!in_array($ext, $allowedExts)) {
            return ['success' => false, 'error' => 'Invalid image extension. Allowed: JPG, PNG, GIF, WebP, SVG, AVIF'];
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Image too large. Max 10 MB'];
        }

        $dir = __DIR__ . "/../uploads/banners/{$type}/";
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);

        $filename = uniqid() . '_' . time() . '.' . $ext;
        $dest = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'error' => "Failed to upload {$type} image"];
        }

        return "uploads/banners/{$type}/{$filename}";
    }
}
?>
