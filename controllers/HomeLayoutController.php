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
            if ($section['section_type'] === 'slider' || $section['section_type'] === 'banner') {
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
        if ($section['section_type'] === 'slider' || $section['section_type'] === 'banner') {
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
     * Create a layout section (Banners, Sliders, or Products).
     */
    public function createSection(array $post, array $files): array {
        $pageTarget = $post['page_target'] ?? 'desktop';
        $sectionType = $post['section_type'] ?? '';
        $label = trim($post['label'] ?? '');

        if (empty($sectionType) || empty($label)) {
            return ['success' => false, 'error' => 'Section type and label are required.'];
        }

        // Determine section key
        $sectionKey = trim($post['section_key'] ?? '');
        if (empty($sectionKey)) {
            $sectionKey = 'sec_' . strtolower(preg_replace('/[^A-Za-z0-9]/', '_', $label)) . '_' . time();
        }

        $maxOrder = $this->layoutModel->getMaxOrder($pageTarget);

        if ($sectionType === 'slider' || $sectionType === 'banner') {
            // Slider / Banner Creation
            $columnsPerRow = (int)($post['columns_per_row'] ?? 1);
            $isSlider = ($sectionType === 'slider') ? 1 : 0;

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
            $data = [
                'page_target' => $pageTarget,
                'section_type' => $sectionType,
                'section_key' => $sectionKey,
                'label' => $label,
                'api_action' => !empty($post['api_action']) ? $post['api_action'] : null,
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

        $label = trim($post['label'] ?? $section['label']);
        if (empty($label)) {
            return ['success' => false, 'error' => 'Section label cannot be empty.'];
        }

        $sectionType = $section['section_type'];

        if ($sectionType === 'slider' || $sectionType === 'banner') {
            // Update columns / settings for banner sections
            $columnsPerRow = (int)($post['columns_per_row'] ?? $section['columns_per_row']);
            $isSlider = ($sectionType === 'slider') ? 1 : 0;

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
            }

            $data = [
                'label' => $label,
                'columns_per_row' => $columnsPerRow,
                'is_slider' => $isSlider,
                'status' => $post['status'] ?? $section['status']
            ];

            $success = $this->layoutModel->updateSection($id, $data);
        } else {
            // Update product section config
            $data = [
                'label' => $label,
                'api_action' => !empty($post['api_action']) ? $post['api_action'] : null,
                'product_limit' => !empty($post['product_limit']) ? (int)$post['product_limit'] : 10,
                'badge_text' => !empty($post['badge_text']) ? $post['badge_text'] : null,
                'background_image_url' => !empty($post['background_image_url']) ? $post['background_image_url'] : null,
                'status' => $post['status'] ?? $section['status']
            ];

            $success = $this->layoutModel->updateSection($id, $data);
        }

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

        // Iterate through all slots submitted in the post
        if (!empty($post['banner_titles'])) {
            foreach ($post['banner_titles'] as $index => $title) {
                $desktopPath = $post['existing_desktop'][$index] ?? '';
                $mobilePath = $post['existing_mobile'][$index] ?? '';

                // Check if a new desktop image was uploaded
                if (!empty($files['images_desktop']['name'][$index])) {
                    $fileDesktop = [
                        'name' => $files['images_desktop']['name'][$index],
                        'type' => $files['images_desktop']['type'][$index],
                        'tmp_name' => $files['images_desktop']['tmp_name'][$index],
                        'error' => $files['images_desktop']['error'][$index],
                        'size' => $files['images_desktop']['size'][$index]
                    ];
                    $uploadRes = $this->uploadImageFile($fileDesktop, 'desktop');
                    if (is_string($uploadRes)) {
                        $desktopPath = $uploadRes;
                    }
                }

                // Check if a new mobile image was uploaded
                if (!empty($files['images_mobile']['name'][$index])) {
                    $fileMobile = [
                        'name' => $files['images_mobile']['name'][$index],
                        'type' => $files['images_mobile']['type'][$index],
                        'tmp_name' => $files['images_mobile']['tmp_name'][$index],
                        'error' => $files['images_mobile']['error'][$index],
                        'size' => $files['images_mobile']['size'][$index]
                    ];
                    $uploadRes = $this->uploadImageFile($fileMobile, 'mobile');
                    if (is_string($uploadRes)) {
                        $mobilePath = $uploadRes;
                    }
                }

                // Skip if we don't have at least a desktop image (might happen if adding a new blank slot and not uploading)
                if (empty($desktopPath)) {
                    continue;
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

        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'error' => 'Invalid image type. Allowed: JPG, PNG, GIF, WebP'];
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Image too large. Max 5 MB'];
        }

        $dir = __DIR__ . "/../uploads/banners/{$type}/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'error' => "Failed to upload {$type} image"];
        }

        return "uploads/banners/{$type}/{$filename}";
    }
}
?>
