<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SpecialOfferBannerModel.php';

class SpecialOfferBannerController {
    private SpecialOfferBannerModel $model;
    private Database $db;
    private string $uploadDir;

    public function __construct() {
        $this->model = new SpecialOfferBannerModel();
        $this->db = new Database();
        $this->uploadDir = __DIR__ . '/../uploads/banners/special_offers/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function getAllBanners($target = 'all', $type = 'all', $status = 'all'): array {
        return $this->model->getAll($target, $type, $status);
    }

    public function getActiveBannersForApi($target = 'all', $type = 'all'): array {
        return $this->model->getActiveForApi($target, $type);
    }

    public function getBannerById(int $id): ?array {
        $banner = $this->model->getById($id);
        return $banner ?: null;
    }

    public function deleteBanner(int $id): bool {
        $banner = $this->getBannerById($id);
        if ($banner) {
            // Delete uploaded files if local
            if (!empty($banner['image_desktop']) && file_exists(__DIR__ . '/../' . $banner['image_desktop'])) {
                @unlink(__DIR__ . '/../' . $banner['image_desktop']);
            }
            if (!empty($banner['image_mobile']) && file_exists(__DIR__ . '/../' . $banner['image_mobile'])) {
                @unlink(__DIR__ . '/../' . $banner['image_mobile']);
            }
        }
        return (bool)$this->model->delete($id);
    }

    public function toggleStatus(int $id): bool {
        return (bool)$this->model->toggleStatus($id);
    }

    /**
     * Handle single or multi-slot special offer creation
     */
    public function createSpecialOffers(array $post, array $files): int {
        $displayType   = in_array($post['display_type'] ?? '', ['banner', 'carousel']) ? $post['display_type'] : 'banner';
        $layoutFormat  = trim($post['layout_format'] ?? '1'); // '1', '1.5', '2', '2x2', '3'
        $deviceTarget  = in_array($post['device_target'] ?? '', ['all', 'desktop', 'mobile']) ? $post['device_target'] : 'all';
        $startDate     = !empty($post['start_date']) ? $post['start_date'] : null;
        $endDate       = !empty($post['end_date']) ? $post['end_date'] : null;
        $status        = in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : 'active';

        $slots = $post['slots'] ?? [];
        $createdCount = 0;

        if (!empty($slots) && is_array($slots)) {
            foreach ($slots as $idx => $slotData) {
                $desktopImgPath = $this->handleFileUpload($files, "slots_image_desktop_{$idx}", 'special_offer_desk_');
                if (empty($desktopImgPath)) {
                    $desktopImgPath = trim($slotData['image_desktop_url'] ?? '');
                }

                $mobileImgPath = $this->handleFileUpload($files, "slots_image_mobile_{$idx}", 'special_offer_mob_');
                if (empty($mobileImgPath)) {
                    $mobileImgPath = trim($slotData['image_mobile_url'] ?? ($desktopImgPath ?: ''));
                }

                if (empty($desktopImgPath) && empty($mobileImgPath)) {
                    continue; // Skip slot if no image
                }

                $data = [
                    'title'           => trim($slotData['title'] ?? ($post['title'] ?? '')),
                    'subtitle'        => trim($slotData['subtitle'] ?? ($post['subtitle'] ?? '')),
                    'offer_badge'     => trim($slotData['offer_badge'] ?? ($post['offer_badge'] ?? 'Special Offer')),
                    'badge_color'     => trim($slotData['badge_color'] ?? ($post['badge_color'] ?? '#ef4444')),
                    'display_type'    => $displayType,
                    'layout_format'   => $layoutFormat,
                    'device_target'   => $deviceTarget,
                    'image_desktop'   => $desktopImgPath,
                    'image_mobile'    => $mobileImgPath,
                    'link_url'        => trim($slotData['link_url'] ?? ($post['link_url'] ?? '/allproducts?filter=discount')),
                    'open_in_new_tab' => !empty($slotData['open_in_new_tab']) ? 1 : (!empty($post['open_in_new_tab']) ? 1 : 0),
                    'start_date'      => $startDate,
                    'end_date'        => $endDate,
                    'sort_order'      => (int)($slotData['sort_order'] ?? $idx),
                    'status'          => $status
                ];

                $id = $this->model->create($data);
                if ($id) $createdCount++;
            }
        } else {
            // Single banner entry mode
            $desktopImgPath = $this->handleFileUpload($files, 'image_desktop', 'special_offer_desk_');
            if (empty($desktopImgPath)) {
                $desktopImgPath = trim($post['image_desktop_url'] ?? '');
            }

            $mobileImgPath = $this->handleFileUpload($files, 'image_mobile', 'special_offer_mob_');
            if (empty($mobileImgPath)) {
                $mobileImgPath = trim($post['image_mobile_url'] ?? ($desktopImgPath ?: ''));
            }

            if (!empty($desktopImgPath) || !empty($mobileImgPath)) {
                $data = [
                    'title'           => trim($post['title'] ?? ''),
                    'subtitle'        => trim($post['subtitle'] ?? ''),
                    'offer_badge'     => trim($post['offer_badge'] ?? 'Special Offer'),
                    'badge_color'     => trim($post['badge_color'] ?? '#ef4444'),
                    'display_type'    => $displayType,
                    'layout_format'   => $layoutFormat,
                    'device_target'   => $deviceTarget,
                    'image_desktop'   => $desktopImgPath,
                    'image_mobile'    => $mobileImgPath,
                    'link_url'        => trim($post['link_url'] ?? '/allproducts?filter=discount'),
                    'open_in_new_tab' => !empty($post['open_in_new_tab']) ? 1 : 0,
                    'start_date'      => $startDate,
                    'end_date'        => $endDate,
                    'sort_order'      => (int)($post['sort_order'] ?? 0),
                    'status'          => $status
                ];

                $id = $this->model->create($data);
                if ($id) $createdCount++;
            }
        }

        return $createdCount;
    }

    /**
     * Update single special offer banner
     */
    public function updateSpecialOffer(int $id, array $post, array $files): bool {
        $existing = $this->getBannerById($id);
        if (!$existing) return false;

        $desktopImgPath = $this->handleFileUpload($files, 'image_desktop', 'special_offer_desk_');
        if (empty($desktopImgPath)) {
            $desktopImgPath = !empty($post['image_desktop_url']) ? trim($post['image_desktop_url']) : $existing['image_desktop'];
        }

        $mobileImgPath = $this->handleFileUpload($files, 'image_mobile', 'special_offer_mob_');
        if (empty($mobileImgPath)) {
            $mobileImgPath = !empty($post['image_mobile_url']) ? trim($post['image_mobile_url']) : $existing['image_mobile'];
        }

        $data = [
            'title'           => trim($post['title'] ?? ''),
            'subtitle'        => trim($post['subtitle'] ?? ''),
            'offer_badge'     => trim($post['offer_badge'] ?? 'Special Offer'),
            'badge_color'     => trim($post['badge_color'] ?? '#ef4444'),
            'display_type'    => in_array($post['display_type'] ?? '', ['banner', 'carousel']) ? $post['display_type'] : $existing['display_type'],
            'layout_format'   => trim($post['layout_format'] ?? $existing['layout_format']),
            'device_target'   => in_array($post['device_target'] ?? '', ['all', 'desktop', 'mobile']) ? $post['device_target'] : $existing['device_target'],
            'image_desktop'   => $desktopImgPath,
            'image_mobile'    => $mobileImgPath,
            'link_url'        => trim($post['link_url'] ?? $existing['link_url']),
            'open_in_new_tab' => !empty($post['open_in_new_tab']) ? 1 : 0,
            'start_date'      => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date'        => !empty($post['end_date']) ? $post['end_date'] : null,
            'sort_order'      => (int)($post['sort_order'] ?? $existing['sort_order']),
            'status'          => in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : $existing['status']
        ];

        return (bool)$this->model->update($id, $data);
    }

    private function handleFileUpload(array $files, string $key, string $prefix): string {
        if (!isset($files[$key]) || empty($files[$key]['name']) || $files[$key]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $file = $files[$key];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            return '';
        }

        $filename = $prefix . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $targetPath = $this->uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/banners/special_offers/' . $filename;
        }

        return '';
    }
}
