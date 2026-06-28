<?php
require_once(__DIR__ . '/../models/BannerModel.php');

/**
 * Admin banner controller.
 * Kept as BannerController (not renamed) so existing admin PHP pages don't break.
 * Also aliased as BannerLayoutController for files that use that class name.
 */
class BannerController {
    private BannerModel $bannerModel;

    public function __construct() {
        $this->bannerModel = new BannerModel();
    }

    // ----------------------------------------------------------------
    // Banners
    // ----------------------------------------------------------------

    public function getAllBanners(): array {
        return $this->bannerModel->getAllBanners();
    }

    public function getBannerById(int $id): ?array {
        return $this->bannerModel->getBannerById($id);
    }

    public function getBannersWithPagination(int $page = 1, int $perPage = 20, string $search = ''): array {
        return $this->bannerModel->getBannersWithPagination($page, $perPage, $search);
    }

    public function getBannersCount(string $search = ''): int {
        return $this->bannerModel->getBannersCount($search);
    }

    public function getBannerStats(): array {
        return $this->bannerModel->getBannerStats();
    }

    public function createBanner(array $post, array $files): array {
        // Upload desktop image
        $desktopPath = $this->uploadImage($files['image_desktop'] ?? null, 'desktop');
        if (isset($desktopPath['error'])) return $desktopPath;

        // Upload mobile image
        $mobilePath = $this->uploadImage($files['image_mobile'] ?? null, 'mobile');
        if (isset($mobilePath['error'])) return $mobilePath;

        $data = [
            'title'             => trim($post['title']         ?? ''),
            'description'       => trim($post['description']   ?? ''),
            'target_url'        => trim($post['target_url']    ?? ''),
            'section_id'        => (int)($post['section_id']   ?? 0) ?: null,
            'display_order'     => (int)($post['display_order'] ?? 0),
            'status'            => in_array($post['status'] ?? '', ['active','inactive']) ? $post['status'] : 'active',
            'start_date'        => $post['start_date'] ?: null,
            'end_date'          => $post['end_date']   ?: null,
            'image_url_desktop' => $desktopPath,
            'image_url_mobile'  => $mobilePath,
        ];

        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Banner title is required'];
        }

        $id = $this->bannerModel->createBanner($data);
        if ($id) {
            return ['success' => true, 'message' => 'Banner created successfully', 'id' => $id];
        }
        return ['success' => false, 'error' => 'Failed to create banner'];
    }

    public function updateBanner(int $id, array $post, array $files): array {
        $existing = $this->bannerModel->getBannerById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Banner not found'];
        }

        $desktopPath = $existing['image_url_desktop'];
        $mobilePath  = $existing['image_url_mobile'];

        if (!empty($files['image_desktop']['name'])) {
            $result = $this->uploadImage($files['image_desktop'], 'desktop');
            if (isset($result['error'])) return $result;
            $desktopPath = $result;
        }

        if (!empty($files['image_mobile']['name'])) {
            $result = $this->uploadImage($files['image_mobile'], 'mobile');
            if (isset($result['error'])) return $result;
            $mobilePath = $result;
        }

        $data = [
            'title'             => trim($post['title']         ?? ''),
            'description'       => trim($post['description']   ?? ''),
            'target_url'        => trim($post['target_url']    ?? ''),
            'section_id'        => (int)($post['section_id']   ?? 0) ?: null,
            'display_order'     => (int)($post['display_order'] ?? 0),
            'status'            => in_array($post['status'] ?? '', ['active','inactive']) ? $post['status'] : 'active',
            'start_date'        => $post['start_date'] ?: null,
            'end_date'          => $post['end_date']   ?: null,
            'image_url_desktop' => $desktopPath,
            'image_url_mobile'  => $mobilePath,
        ];

        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Banner title is required'];
        }

        if ($this->bannerModel->updateBanner($id, $data)) {
            return ['success' => true, 'message' => 'Banner updated successfully'];
        }
        return ['success' => false, 'error' => 'Failed to update banner'];
    }

    public function deleteBanner(int $id): array {
        if ($this->bannerModel->deleteBanner($id)) {
            return ['success' => true, 'message' => 'Banner deleted'];
        }
        return ['success' => false, 'error' => 'Failed to delete banner'];
    }

    // ----------------------------------------------------------------
    // Sections
    // ----------------------------------------------------------------

    public function getAllSections(): array {
        return $this->bannerModel->getAllSections();
    }

    public function getSectionById(int $id): ?array {
        return $this->bannerModel->getSectionById($id);
    }

    /**
     * Update columns_per_row for a section (1/2/3/4).
     */
    public function updateSectionColumns(int $sectionId, int $columns): array {
        $columns = max(1, min(4, $columns));
        if ($this->bannerModel->updateSectionColumns($sectionId, $columns)) {
            return ['success' => true, 'message' => "Section updated to {$columns} column(s)"];
        }
        return ['success' => false, 'error' => 'Failed to update section'];
    }

    // ----------------------------------------------------------------
    // Image upload helper
    // ----------------------------------------------------------------

    private function uploadImage(?array $file, string $type): string|array {
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

// Alias so files using BannerLayoutController still work
class_alias('BannerController', 'BannerLayoutController');
?>
