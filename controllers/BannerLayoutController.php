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

    public function getBannersWithPagination(int $page = 1, int $perPage = 20, string $search = '', string $pageFilter = '', int $sectionId = 0): array {
        return $this->bannerModel->getBannersWithPagination($page, $perPage, $search, $pageFilter, $sectionId);
    }

    public function getBannersCount(string $search = '', string $pageFilter = '', int $sectionId = 0): int {
        return $this->bannerModel->getBannersCount($search, $pageFilter, $sectionId);
    }

    public function getBannerStats(): array {
        return $this->bannerModel->getBannerStats();
    }

    public function createBanner(array $post, array $files): array {
        // Desktop single image
        $desktopFile = $files['desktop_image'] ?? $files['image_desktop'] ?? null;
        $desktopPath = $this->uploadImage($desktopFile, 'desktop');
        if (is_array($desktopPath) && isset($desktopPath['error'])) return $desktopPath;

        // Mobile single image
        $mobileFile = $files['mobile_image'] ?? $files['image_mobile'] ?? null;
        $mobilePath = $this->uploadImage($mobileFile, 'mobile');
        if (is_array($mobilePath) && isset($mobilePath['error'])) return $mobilePath;

        // Desktop multi-images (for grid formats)
        $desktopImagesPaths = $this->uploadMultipleImages($files['desktop_images'] ?? null, 'desktop');
        // Mobile multi-images
        $mobileImagesPaths  = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');

        $desktopUrls = !empty($post['desktop_urls']) && is_array($post['desktop_urls'])
            ? json_encode(array_values(array_filter($post['desktop_urls'])))
            : (!empty($post['desktop_urls']) ? $post['desktop_urls'] : null);

        $mobileUrls = !empty($post['mobile_urls']) && is_array($post['mobile_urls'])
            ? json_encode(array_values(array_filter($post['mobile_urls'])))
            : (!empty($post['mobile_urls']) ? $post['mobile_urls'] : null);

        $desktopImgFinal = $desktopPath ?: ($desktopImagesPaths[0] ?? '');
        $mobileImgFinal  = $mobilePath ?: ($mobileImagesPaths[0] ?? $desktopImgFinal);

        $desktopUrlPrimary = trim($post['desktop_url'] ?? $post['target_url'] ?? '');
        if (empty($desktopUrlPrimary) && !empty($post['desktop_urls']) && is_array($post['desktop_urls'])) {
            $desktopUrlPrimary = $post['desktop_urls'][0] ?? '';
        }

        $mobileUrlPrimary = trim($post['mobile_url'] ?? $desktopUrlPrimary);
        if (empty($mobileUrlPrimary) && !empty($post['mobile_urls']) && is_array($post['mobile_urls'])) {
            $mobileUrlPrimary = $post['mobile_urls'][0] ?? '';
        }

        $data = [
            'title'                      => trim($post['title'] ?? ''),
            'description'                => trim($post['description'] ?? ''),
            'target_url'                 => $desktopUrlPrimary,
            'section_id'                 => (int)($post['section_id'] ?? $post['desktop_home_carousel_id'] ?? 0) ?: null,
            'layout_format'              => trim($post['desktop_design'] ?? $post['layout_format'] ?? 'desktop_format_1'),
            'display_order'              => (int)($post['desktop_sort_order'] ?? $post['display_order'] ?? 0),
            'status'                     => in_array($post['status'] ?? $post['desktop_status'] ?? '', ['active','inactive']) ? ($post['status'] ?? $post['desktop_status']) : 'active',
            'start_date'                 => $post['desktop_start_date'] ?? $post['start_date'] ?: null,
            'end_date'                   => $post['desktop_end_date'] ?? $post['end_date'] ?: null,
            'banner_type'                => trim($post['banner_type'] ?? 'single'),
            'image_url_desktop'          => $desktopImgFinal,
            'image_url_mobile'           => $mobileImgFinal,
            'desktop_show_other_pages'   => $post['desktop_show_other_pages'] ?? 'no',
            'desktop_other_category_id'  => (int)($post['desktop_other_category_id'] ?? 0) ?: null,
            'desktop_other_carousel_id'  => (int)($post['desktop_other_carousel_id'] ?? 0) ?: null,
            'desktop_home_category_id'   => (int)($post['desktop_home_category_id'] ?? 0) ?: null,
            'desktop_home_carousel_id'   => (int)($post['desktop_home_carousel_id'] ?? 0) ?: null,
            'desktop_design'             => trim($post['desktop_design'] ?? 'desktop_format_1'),
            'desktop_image'              => $desktopImgFinal,
            'desktop_images'             => !empty($desktopImagesPaths) ? json_encode($desktopImagesPaths) : null,
            'desktop_url'                => $desktopUrlPrimary,
            'desktop_urls'               => $desktopUrls,
            'desktop_status'             => $post['desktop_status'] ?? 'active',
            'button_arrows'              => in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : 'enable',
            'auto_running'               => in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : 'enable',
            'auto_run_speed'             => !empty($post['auto_run_speed']) ? (int)$post['auto_run_speed'] : 3000,
            'desktop_sort_order'         => (int)($post['desktop_sort_order'] ?? 0),
            'desktop_start_date'         => $post['desktop_start_date'] ?: null,
            'desktop_end_date'           => $post['desktop_end_date'] ?: null,
            'mobile_show_other_pages'    => $post['mobile_show_other_pages'] ?? 'no',
            'mobile_other_category_id'   => (int)($post['mobile_other_category_id'] ?? 0) ?: null,
            'mobile_other_carousel_id'   => (int)($post['mobile_other_carousel_id'] ?? 0) ?: null,
            'mobile_home_category_id'    => (int)($post['mobile_home_category_id'] ?? 0) ?: null,
            'mobile_home_carousel_id'    => (int)($post['mobile_home_carousel_id'] ?? 0) ?: null,
            'mobile_design'              => trim($post['mobile_design'] ?? ''),
            'mobile_image'               => $mobileImgFinal,
            'mobile_images'              => !empty($mobileImagesPaths) ? json_encode($mobileImagesPaths) : null,
            'mobile_url'                 => $mobileUrlPrimary,
            'mobile_urls'                => $mobileUrls,
            'mobile_status'              => $post['mobile_status'] ?? 'active',
            'mobile_sort_order'          => (int)($post['mobile_sort_order'] ?? 0),
            'mobile_start_date'          => $post['mobile_start_date'] ?: null,
            'mobile_end_date'            => $post['mobile_end_date'] ?: null,
        ];

        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Banner title name is required'];
        }

        $id = $this->bannerModel->createBanner($data);
        if ($id) {
            return ['success' => true, 'message' => 'Banner created successfully', 'id' => $id];
        }
        return ['success' => false, 'error' => 'Failed to create banner'];
    }

    // ----------------------------------------------------------------
    // Page banners (every page except Home): single banner or carousel
    // ----------------------------------------------------------------

    public function getPageSections(): array {
        return $this->bannerModel->getSectionsExcludingPage('home');
    }

    public function getPageBanners(): array {
        return $this->bannerModel->getBannersExcludingPage('home');
    }

    private function slugify(string $value): string {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $value), '_'));
        return $slug !== '' ? $slug : 'section';
    }

    private function fileAt(?array $files, int $idx): ?array {
        if (empty($files) || !isset($files['name'][$idx]) || $files['name'][$idx] === '') {
            return null;
        }
        return [
            'name'     => $files['name'][$idx],
            'type'     => $files['type'][$idx],
            'tmp_name' => $files['tmp_name'][$idx],
            'error'    => $files['error'][$idx],
            'size'     => $files['size'][$idx],
        ];
    }

    public function createPageBanners(array $post, array $files): array {
        $type = ($post['display_type'] ?? 'single') === 'carousel' ? 'carousel' : 'single';

        // 1. Resolve the target section (existing, or a new one on a new/custom page)
        $sectionId = (int)($post['section_id'] ?? 0);
        if ($sectionId > 0) {
            $section = $this->bannerModel->getSectionById($sectionId);
            if (!$section || $section['page'] === 'home') {
                return ['success' => false, 'error' => 'Please choose a valid section for a non-home page.'];
            }
        } else {
            $rawPage = trim($post['custom_page'] ?? '');
            if ($rawPage === '' && ($post['page'] ?? '') !== '__custom') {
                $rawPage = trim($post['page'] ?? '');
            }
            $page = $rawPage === '' ? '' : $this->slugify($rawPage);
            $label = trim($post['new_section_label'] ?? '');
            if ($page === '' || $page === 'section' || $page === 'home') {
                return ['success' => false, 'error' => 'Please choose a page (Home is managed from the Home Page banners menu).'];
            }
            if ($label === '') {
                return ['success' => false, 'error' => 'Please enter a name for the new section.'];
            }
            $key = $this->slugify($page . '_' . $label);
            $base = $key; $n = 2;
            while ($this->bannerModel->sectionKeyExists($key)) {
                $key = $base . '_' . $n++;
            }
            $sectionId = (int)$this->bannerModel->createSection($page, $key, $label, $type === 'carousel' ? 1 : 0, 1);
            if (!$sectionId) {
                return ['success' => false, 'error' => 'Could not create the new section.'];
            }
        }

        // 2. Collect slides (single = exactly one slide)
        $slides = $post['slides'] ?? [];
        if (!is_array($slides) || empty($slides)) {
            return ['success' => false, 'error' => 'Please add at least one banner image.'];
        }
        if ($type === 'single') {
            $slides = [array_values($slides)[0]];
        }

        $status = in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : 'active';
        $start  = !empty($post['start_date']) ? $post['start_date'] : null;
        $end    = !empty($post['end_date']) ? $post['end_date'] : null;
        $arrows = in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : 'enable';
        $auto   = in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : 'enable';
        $speed  = !empty($post['auto_run_speed']) ? max(1000, (int)$post['auto_run_speed']) : 3000;
        $baseTitle = trim($post['title'] ?? '');

        // 3. Upload + insert each slide
        $created = 0;
        $order = 0;
        foreach (array_keys($slides) as $idx) {
            $slide = $slides[$idx];
            $desktop = $this->uploadImage($this->fileAt($files['slide_desktop'] ?? null, (int)$idx), 'desktop');
            if (is_array($desktop)) return $desktop;
            $mobile = $this->uploadImage($this->fileAt($files['slide_mobile'] ?? null, (int)$idx), 'mobile');
            if (is_array($mobile)) return $mobile;
            if ($desktop === '' && $mobile === '') {
                continue;
            }
            $desktop = $desktop ?: $mobile;
            $mobile  = $mobile ?: $desktop;

            $url = trim($slide['url'] ?? '');
            $slideTitle = trim($slide['title'] ?? '');
            $title = $slideTitle !== '' ? $slideTitle : ($baseTitle !== '' ? $baseTitle . ($type === 'carousel' ? ' – Slide ' . ($order + 1) : '') : 'Banner');

            $id = $this->bannerModel->createBanner([
                'title'             => $title,
                'description'       => '',
                'target_url'        => $url,
                'section_id'        => $sectionId,
                'layout_format'     => 'desktop_format_1',
                'display_order'     => $order,
                'status'            => $status,
                'start_date'        => $start,
                'end_date'          => $end,
                'banner_type'       => $type,
                'image_url_desktop' => $desktop,
                'image_url_mobile'  => $mobile,
                'desktop_image'     => $desktop,
                'mobile_image'      => $mobile,
                'desktop_url'       => $url,
                'mobile_url'        => $url,
                'desktop_design'    => 'desktop_format_1',
                'mobile_design'     => 'mobile_format_1',
                'desktop_status'    => $status,
                'mobile_status'     => $status,
                'button_arrows'     => $arrows,
                'auto_running'      => $auto,
                'auto_run_speed'    => $speed,
                'desktop_sort_order' => $order,
                'mobile_sort_order'  => $order,
                'desktop_start_date' => $start,
                'desktop_end_date'   => $end,
                'mobile_start_date'  => $start,
                'mobile_end_date'    => $end,
            ]);
            if ($id) { $created++; $order++; }
        }

        if ($created === 0) {
            return ['success' => false, 'error' => 'Please upload at least one desktop or mobile image.'];
        }

        $this->bannerModel->updateSectionSlider($sectionId, $type === 'carousel' ? 1 : 0);

        return [
            'success' => true,
            'message' => $type === 'carousel'
                ? "Carousel created with {$created} slide" . ($created > 1 ? 's' : '') . '.'
                : 'Banner created successfully.',
            'section_id' => $sectionId,
        ];
    }

    public function updateBanner(int $id, array $post, array $files): array {
        $existing = $this->bannerModel->getBannerById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Banner not found'];
        }

        $desktopImgFinal = $existing['desktop_image'] ?: $existing['image_url_desktop'];
        $mobileImgFinal  = $existing['mobile_image'] ?: $existing['image_url_mobile'];

        // Desktop single image
        $desktopFile = $files['desktop_image'] ?? $files['image_desktop'] ?? null;
        if (!empty($desktopFile['name'])) {
            $result = $this->uploadImage($desktopFile, 'desktop');
            if (is_array($result) && isset($result['error'])) return $result;
            $desktopImgFinal = $result;
        }

        // Mobile single image
        $mobileFile = $files['mobile_image'] ?? $files['image_mobile'] ?? null;
        if (!empty($mobileFile['name'])) {
            $result = $this->uploadImage($mobileFile, 'mobile');
            if (is_array($result) && isset($result['error'])) return $result;
            $mobileImgFinal = $result;
        }

        // Desktop multi-images
        $desktopImagesPaths = $this->uploadMultipleImages($files['desktop_images'] ?? null, 'desktop');
        $desktopImagesJson = !empty($desktopImagesPaths) ? json_encode($desktopImagesPaths) : $existing['desktop_images'];

        // Mobile multi-images
        $mobileImagesPaths = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');
        $mobileImagesJson = !empty($mobileImagesPaths) ? json_encode($mobileImagesPaths) : $existing['mobile_images'];

        $desktopUrls = !empty($post['desktop_urls']) && is_array($post['desktop_urls'])
            ? json_encode(array_values(array_filter($post['desktop_urls'])))
            : (!empty($post['desktop_urls']) ? $post['desktop_urls'] : $existing['desktop_urls']);

        $mobileUrls = !empty($post['mobile_urls']) && is_array($post['mobile_urls'])
            ? json_encode(array_values(array_filter($post['mobile_urls'])))
            : (!empty($post['mobile_urls']) ? $post['mobile_urls'] : $existing['mobile_urls']);

        $desktopUrlPrimary = trim($post['desktop_url'] ?? $post['target_url'] ?? $existing['desktop_url']);
        $mobileUrlPrimary  = trim($post['mobile_url'] ?? $existing['mobile_url'] ?? $desktopUrlPrimary);

        $data = [
            'title'                      => trim($post['title'] ?? $existing['title']),
            'description'                => trim($post['description'] ?? $existing['description']),
            'target_url'                 => $desktopUrlPrimary,
            'section_id'                 => (int)($post['section_id'] ?? $post['desktop_home_carousel_id'] ?? $existing['section_id']) ?: null,
            'layout_format'              => trim($post['desktop_design'] ?? $post['layout_format'] ?? $existing['layout_format']),
            'display_order'              => (int)($post['desktop_sort_order'] ?? $post['display_order'] ?? $existing['display_order']),
            'status'                     => in_array($post['status'] ?? $post['desktop_status'] ?? '', ['active','inactive']) ? ($post['status'] ?? $post['desktop_status']) : ($existing['status'] ?? 'active'),
            'start_date'                 => $post['desktop_start_date'] ?? $post['start_date'] ?: $existing['start_date'],
            'end_date'                   => $post['desktop_end_date'] ?? $post['end_date'] ?: $existing['end_date'],
            'banner_type'                => trim($post['banner_type'] ?? $existing['banner_type'] ?? 'single'),
            'image_url_desktop'          => $desktopImgFinal,
            'image_url_mobile'           => $mobileImgFinal ?: $desktopImgFinal,
            'desktop_show_other_pages'   => $post['desktop_show_other_pages'] ?? $existing['desktop_show_other_pages'] ?? 'no',
            'desktop_other_category_id'  => (int)($post['desktop_other_category_id'] ?? 0) ?: null,
            'desktop_other_carousel_id'  => (int)($post['desktop_other_carousel_id'] ?? 0) ?: null,
            'desktop_home_category_id'   => (int)($post['desktop_home_category_id'] ?? 0) ?: null,
            'desktop_home_carousel_id'   => (int)($post['desktop_home_carousel_id'] ?? 0) ?: null,
            'desktop_design'             => trim($post['desktop_design'] ?? $existing['desktop_design'] ?? 'desktop_format_1'),
            'desktop_image'              => $desktopImgFinal,
            'desktop_images'             => $desktopImagesJson,
            'desktop_url'                => $desktopUrlPrimary,
            'desktop_urls'               => $desktopUrls,
            'desktop_status'             => $post['desktop_status'] ?? $existing['desktop_status'] ?? 'active',
            'button_arrows'              => in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : ($existing['button_arrows'] ?? 'enable'),
            'auto_running'               => in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : ($existing['auto_running'] ?? 'enable'),
            'auto_run_speed'             => !empty($post['auto_run_speed']) ? (int)$post['auto_run_speed'] : (int)($existing['auto_run_speed'] ?? 3000),
            'desktop_sort_order'         => (int)($post['desktop_sort_order'] ?? $existing['desktop_sort_order'] ?? 0),
            'desktop_start_date'         => $post['desktop_start_date'] ?: $existing['desktop_start_date'],
            'desktop_end_date'           => $post['desktop_end_date'] ?: $existing['desktop_end_date'],
            'mobile_show_other_pages'    => $post['mobile_show_other_pages'] ?? $existing['mobile_show_other_pages'] ?? 'no',
            'mobile_other_category_id'   => (int)($post['mobile_other_category_id'] ?? 0) ?: null,
            'mobile_other_carousel_id'   => (int)($post['mobile_other_carousel_id'] ?? 0) ?: null,
            'mobile_home_category_id'    => (int)($post['mobile_home_category_id'] ?? 0) ?: null,
            'mobile_home_carousel_id'    => (int)($post['mobile_home_carousel_id'] ?? 0) ?: null,
            'mobile_design'              => trim($post['mobile_design'] ?? $existing['mobile_design'] ?? ''),
            'mobile_image'               => $mobileImgFinal,
            'mobile_images'              => $mobileImagesJson,
            'mobile_url'                 => $mobileUrlPrimary,
            'mobile_urls'                => $mobileUrls,
            'mobile_status'              => $post['mobile_status'] ?? $existing['mobile_status'] ?? 'active',
            'mobile_sort_order'          => (int)($post['mobile_sort_order'] ?? $existing['mobile_sort_order'] ?? 0),
            'mobile_start_date'          => $post['mobile_start_date'] ?: $existing['mobile_start_date'],
            'mobile_end_date'            => $post['mobile_end_date'] ?: $existing['mobile_end_date'],
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
            return ['success' => true, 'message' => 'Banner deleted successfully'];
        }
        return ['success' => false, 'error' => 'Failed to delete banner'];
    }

    public function toggleBannerStatus(int $id, string $status): array {
        if ($this->bannerModel->toggleBannerStatus($id, $status)) {
            return ['success' => true, 'message' => 'Status updated successfully'];
        }
        return ['success' => false, 'error' => 'Failed to update status'];
    }

    public function getDeactivatedBanners(): array {
        return $this->bannerModel->getDeactivatedBanners();
    }

    public function getAllCategories(): array {
        return $this->bannerModel->getAllCategories();
    }

    public function getAllCarousels(): array {
        return $this->bannerModel->getAllCarousels();
    }

    // ----------------------------------------------------------------
    // Banner Carousel Management
    // ----------------------------------------------------------------

    public function getAllBannerCarousels(string $pageFilter = '', string $search = '', int $sectionId = 0): array {
        return $this->bannerModel->getAllBannerCarousels($pageFilter, $search, $sectionId);
    }

    public function getBannerCarouselById(int $id): ?array {
        return $this->bannerModel->getBannerCarouselById($id);
    }

    public function createBannerCarousel(array $post, array $files): array {
        $desktopFile = $files['desktop_image'] ?? null;
        $desktopPath = $this->uploadImage($desktopFile, 'desktop');
        if (is_array($desktopPath) && isset($desktopPath['error'])) return $desktopPath;

        $mobileFile = $files['mobile_image'] ?? null;
        $mobilePath = $this->uploadImage($mobileFile, 'mobile');
        if (is_array($mobilePath) && isset($mobilePath['error'])) return $mobilePath;

        $desktopImagesPaths = $this->uploadMultipleImages($files['desktop_images'] ?? null, 'desktop');
        $mobileImagesPaths  = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');

        $desktopUrls = !empty($post['desktop_urls']) && is_array($post['desktop_urls'])
            ? json_encode(array_values(array_filter($post['desktop_urls'])))
            : (!empty($post['desktop_urls']) ? $post['desktop_urls'] : null);

        $mobileUrls = !empty($post['mobile_urls']) && is_array($post['mobile_urls'])
            ? json_encode(array_values(array_filter($post['mobile_urls'])))
            : (!empty($post['mobile_urls']) ? $post['mobile_urls'] : null);

        $desktopImgFinal = $desktopPath ?: ($desktopImagesPaths[0] ?? '');
        $mobileImgFinal  = $mobilePath ?: ($mobileImagesPaths[0] ?? $desktopImgFinal);

        $data = [
            'title'                      => trim($post['title'] ?? ''),
            'desktop_page'               => trim($post['desktop_page'] ?? 'home'),
            'desktop_home_category_id'   => (int)($post['desktop_home_category_id'] ?? 0) ?: null,
            'desktop_home_carousel_id'   => (int)($post['desktop_home_carousel_id'] ?? 0) ?: null,
            'desktop_show_other_pages'   => $post['desktop_show_other_pages'] ?? 'no',
            'desktop_other_category_id'  => (int)($post['desktop_other_category_id'] ?? 0) ?: null,
            'desktop_other_carousel_id'  => (int)($post['desktop_other_carousel_id'] ?? 0) ?: null,
            'desktop_design'             => trim($post['desktop_design'] ?? 'desktop_format_1'),
            'desktop_image'              => $desktopImgFinal,
            'desktop_images'             => !empty($desktopImagesPaths) ? json_encode($desktopImagesPaths) : null,
            'desktop_url'                => trim($post['desktop_url'] ?? ''),
            'desktop_urls'               => $desktopUrls,
            'desktop_status'             => $post['desktop_status'] ?? 'active',
            'button_arrows'              => in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : 'enable',
            'auto_running'               => in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : 'enable',
            'auto_run_speed'             => !empty($post['auto_run_speed']) ? (int)$post['auto_run_speed'] : 3000,
            'desktop_sort_order'         => (int)($post['desktop_sort_order'] ?? 0),
            'desktop_start_date'         => !empty($post['desktop_start_date']) ? $post['desktop_start_date'] : null,
            'desktop_end_date'           => !empty($post['desktop_end_date']) ? $post['desktop_end_date'] : null,
            'mobile_design'              => trim($post['mobile_design'] ?? ''),
            'mobile_page'                => trim($post['mobile_page'] ?? 'home'),
            'mobile_category_id'         => (int)($post['mobile_category_id'] ?? 0) ?: null,
            'mobile_carousel_id'         => (int)($post['mobile_carousel_id'] ?? 0) ?: null,
            'mobile_other_pages'         => $post['mobile_other_pages'] ?? 'no',
            'mobile_other_category_id'   => (int)($post['mobile_other_category_id'] ?? 0) ?: null,
            'mobile_other_carousel_id'   => (int)($post['mobile_other_carousel_id'] ?? 0) ?: null,
            'mobile_image'               => $mobileImgFinal,
            'mobile_images'              => !empty($mobileImagesPaths) ? json_encode($mobileImagesPaths) : null,
            'mobile_url'                 => trim($post['mobile_url'] ?? ''),
            'mobile_urls'                => $mobileUrls,
            'mobile_status'              => $post['mobile_status'] ?? 'active',
            'mobile_sort_order'          => (int)($post['mobile_sort_order'] ?? 0),
            'mobile_other_sort_order'    => (int)($post['mobile_other_sort_order'] ?? 0),
            'mobile_start_date'          => !empty($post['mobile_start_date']) ? $post['mobile_start_date'] : null,
            'mobile_end_date'            => !empty($post['mobile_end_date']) ? $post['mobile_end_date'] : null,
            'status'                     => $post['status'] ?? $post['desktop_status'] ?? 'active',
        ];

        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Banner carousel title is required'];
        }

        $id = $this->bannerModel->createBannerCarousel($data);
        if ($id) {
            return ['success' => true, 'message' => 'Banner carousel created successfully', 'id' => $id];
        }
        return ['success' => false, 'error' => 'Failed to create banner carousel'];
    }

    public function updateBannerCarousel(int $id, array $post, array $files): array {
        $existing = $this->bannerModel->getBannerCarouselById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Banner carousel not found'];
        }

        $desktopImgFinal = $existing['desktop_image'];
        $mobileImgFinal  = $existing['mobile_image'];

        $desktopFile = $files['desktop_image'] ?? null;
        if (!empty($desktopFile['name'])) {
            $result = $this->uploadImage($desktopFile, 'desktop');
            if (is_array($result) && isset($result['error'])) return $result;
            $desktopImgFinal = $result;
        }

        $mobileFile = $files['mobile_image'] ?? null;
        if (!empty($mobileFile['name'])) {
            $result = $this->uploadImage($mobileFile, 'mobile');
            if (is_array($result) && isset($result['error'])) return $result;
            $mobileImgFinal = $result;
        }

        // Merge or replace desktop multi-images slot by slot if existing_desktop_images is provided
        $mergedDesktopImages = [];
        $existingDesktopImgs = !empty($existing['desktop_images']) ? json_decode($existing['desktop_images'], true) : [];
        if (!empty($post['existing_desktop_images']) && is_array($post['existing_desktop_images'])) {
            $mergedDesktopImages = $post['existing_desktop_images'];
        } else {
            $mergedDesktopImages = $existingDesktopImgs;
        }

        if (!empty($files['desktop_images']['name']) && is_array($files['desktop_images']['name'])) {
            foreach ($files['desktop_images']['name'] as $idx => $fname) {
                if (!empty($fname)) {
                    $singleFile = [
                        'name'     => $files['desktop_images']['name'][$idx],
                        'type'     => $files['desktop_images']['type'][$idx],
                        'tmp_name' => $files['desktop_images']['tmp_name'][$idx],
                        'error'    => $files['desktop_images']['error'][$idx],
                        'size'     => $files['desktop_images']['size'][$idx],
                    ];
                    $uploadRes = $this->uploadImage($singleFile, 'desktop');
                    if (!is_array($uploadRes)) {
                        $mergedDesktopImages[$idx] = $uploadRes;
                    }
                }
            }
        }
        $desktopImagesPaths = $this->uploadMultipleImages($files['desktop_images'] ?? null, 'desktop');
        if (!empty($desktopImagesPaths) && empty($mergedDesktopImages)) {
            $mergedDesktopImages = $desktopImagesPaths;
        }
        if (!empty($mergedDesktopImages)) {
            ksort($mergedDesktopImages);
            $desktopImagesJson = json_encode(array_values(array_filter($mergedDesktopImages)));
            $firstImg = reset($mergedDesktopImages);
            if (empty($desktopImgFinal) && !empty($firstImg)) {
                $desktopImgFinal = $firstImg;
            }
        } else {
            $desktopImagesJson = $existing['desktop_images'];
        }

        // Mobile multi-images
        $mergedMobileImages = [];
        $existingMobileImgs = !empty($existing['mobile_images']) ? json_decode($existing['mobile_images'], true) : [];
        if (!empty($post['existing_mobile_images']) && is_array($post['existing_mobile_images'])) {
            $mergedMobileImages = $post['existing_mobile_images'];
        } else {
            $mergedMobileImages = $existingMobileImgs;
        }
        if (!empty($files['mobile_images']['name']) && is_array($files['mobile_images']['name'])) {
            foreach ($files['mobile_images']['name'] as $idx => $fname) {
                if (!empty($fname)) {
                    $singleFile = [
                        'name'     => $files['mobile_images']['name'][$idx],
                        'type'     => $files['mobile_images']['type'][$idx],
                        'tmp_name' => $files['mobile_images']['tmp_name'][$idx],
                        'error'    => $files['mobile_images']['error'][$idx],
                        'size'     => $files['mobile_images']['size'][$idx],
                    ];
                    $uploadRes = $this->uploadImage($singleFile, 'mobile');
                    if (!is_array($uploadRes)) {
                        $mergedMobileImages[$idx] = $uploadRes;
                    }
                }
            }
        }
        $mobileImagesPaths = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');
        if (!empty($mobileImagesPaths) && empty($mergedMobileImages)) {
            $mergedMobileImages = $mobileImagesPaths;
        }
        if (!empty($mergedMobileImages)) {
            ksort($mergedMobileImages);
            $mobileImagesJson = json_encode(array_values(array_filter($mergedMobileImages)));
        } else {
            $mobileImagesJson = $existing['mobile_images'];
        }

        $desktopUrls = !empty($post['desktop_urls']) && is_array($post['desktop_urls'])
            ? json_encode(array_values(array_filter($post['desktop_urls'])))
            : (!empty($post['desktop_urls']) ? $post['desktop_urls'] : $existing['desktop_urls']);

        $mobileUrls = !empty($post['mobile_urls']) && is_array($post['mobile_urls'])
            ? json_encode(array_values(array_filter($post['mobile_urls'])))
            : (!empty($post['mobile_urls']) ? $post['mobile_urls'] : $existing['mobile_urls']);

        $data = [
            'title'                      => trim($post['title'] ?? $existing['title']),
            'desktop_page'               => trim($post['desktop_page'] ?? ($existing['desktop_page'] ?? 'home')),
            'desktop_home_category_id'   => (int)($post['desktop_home_category_id'] ?? $existing['desktop_home_category_id']) ?: null,
            'desktop_home_carousel_id'   => (int)($post['desktop_home_carousel_id'] ?? $existing['desktop_home_carousel_id']) ?: null,
            'desktop_show_other_pages'   => $post['desktop_show_other_pages'] ?? ($existing['desktop_show_other_pages'] ?? 'no'),
            'desktop_other_category_id'  => (int)($post['desktop_other_category_id'] ?? $existing['desktop_other_category_id']) ?: null,
            'desktop_other_carousel_id'  => (int)($post['desktop_other_carousel_id'] ?? $existing['desktop_other_carousel_id']) ?: null,
            'desktop_design'             => trim($post['desktop_design'] ?? $existing['desktop_design'] ?? 'desktop_format_1'),
            'desktop_image'              => $desktopImgFinal,
            'desktop_images'             => $desktopImagesJson,
            'desktop_url'                => trim($post['desktop_url'] ?? $existing['desktop_url']),
            'desktop_urls'               => $desktopUrls,
            'desktop_status'             => $post['desktop_status'] ?? $existing['desktop_status'] ?? 'active',
            'button_arrows'              => in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : ($existing['button_arrows'] ?? 'enable'),
            'auto_running'               => in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : ($existing['auto_running'] ?? 'enable'),
            'auto_run_speed'             => !empty($post['auto_run_speed']) ? (int)$post['auto_run_speed'] : (int)($existing['auto_run_speed'] ?? 3000),
            'desktop_sort_order'         => (int)($post['desktop_sort_order'] ?? $existing['desktop_sort_order'] ?? 0),
            'desktop_start_date'         => !empty($post['desktop_start_date']) ? $post['desktop_start_date'] : ($existing['desktop_start_date'] ?? null),
            'desktop_end_date'           => !empty($post['desktop_end_date']) ? $post['desktop_end_date'] : ($existing['desktop_end_date'] ?? null),
            'mobile_design'              => trim($post['mobile_design'] ?? $existing['mobile_design'] ?? ''),
            'mobile_page'                => trim($post['mobile_page'] ?? ($existing['mobile_page'] ?? 'home')),
            'mobile_category_id'         => (int)($post['mobile_category_id'] ?? $existing['mobile_category_id']) ?: null,
            'mobile_carousel_id'         => (int)($post['mobile_carousel_id'] ?? $existing['mobile_carousel_id']) ?: null,
            'mobile_other_pages'         => $post['mobile_other_pages'] ?? ($existing['mobile_other_pages'] ?? 'no'),
            'mobile_other_category_id'   => (int)($post['mobile_other_category_id'] ?? $existing['mobile_other_category_id']) ?: null,
            'mobile_other_carousel_id'   => (int)($post['mobile_other_carousel_id'] ?? $existing['mobile_other_carousel_id']) ?: null,
            'mobile_image'               => $mobileImgFinal,
            'mobile_images'              => $mobileImagesJson,
            'mobile_url'                 => trim($post['mobile_url'] ?? $existing['mobile_url']),
            'mobile_urls'                => $mobileUrls,
            'mobile_status'              => $post['mobile_status'] ?? $existing['mobile_status'] ?? 'active',
            'mobile_sort_order'          => (int)($post['mobile_sort_order'] ?? $existing['mobile_sort_order'] ?? 0),
            'mobile_other_sort_order'    => (int)($post['mobile_other_sort_order'] ?? $existing['mobile_other_sort_order'] ?? 0),
            'mobile_start_date'          => !empty($post['mobile_start_date']) ? $post['mobile_start_date'] : ($existing['mobile_start_date'] ?? null),
            'mobile_end_date'            => !empty($post['mobile_end_date']) ? $post['mobile_end_date'] : ($existing['mobile_end_date'] ?? null),
            'status'                     => $post['status'] ?? $existing['status'] ?? 'active',
        ];

        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Banner carousel title is required'];
        }

        if ($this->bannerModel->updateBannerCarousel($id, $data)) {
            return ['success' => true, 'message' => 'Banner carousel updated successfully'];
        }
        return ['success' => false, 'error' => 'Failed to update banner carousel'];
    }

    public function deleteBannerCarousel(int $id): array {
        if ($this->bannerModel->deleteBannerCarousel($id)) {
            return ['success' => true, 'message' => 'Banner carousel deleted successfully'];
        }
        return ['success' => false, 'error' => 'Failed to delete banner carousel'];
    }

    public function toggleBannerCarouselStatus(int $id, string $status): array {
        if ($this->bannerModel->toggleBannerCarouselStatus($id, $status)) {
            return ['success' => true, 'message' => 'Banner carousel status updated successfully'];
        }
        return ['success' => false, 'error' => 'Failed to update carousel status'];
    }

    // ----------------------------------------------------------------
    // Multi-Image Upload Helper
    // ----------------------------------------------------------------

    private function uploadMultipleImages(?array $files, string $type): array {
        $paths = [];
        if (empty($files) || empty($files['name'])) {
            return $paths;
        }

        // If array of files
        if (is_array($files['name'])) {
            foreach ($files['name'] as $idx => $name) {
                if (empty($name)) continue;
                $fileItem = [
                    'name'     => $files['name'][$idx],
                    'type'     => $files['type'][$idx],
                    'tmp_name' => $files['tmp_name'][$idx],
                    'error'    => $files['error'][$idx],
                    'size'     => $files['size'][$idx],
                ];
                $res = $this->uploadImage($fileItem, $type);
                if (is_string($res) && !empty($res)) {
                    $paths[] = $res;
                }
            }
        } else {
            $res = $this->uploadImage($files, $type);
            if (is_string($res) && !empty($res)) {
                $paths[] = $res;
            }
        }
        return $paths;
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
