<?php
require_once(__DIR__ . '/../models/BannerModel.php');
require_once(__DIR__ . '/../includes/banner-helpers.php');
require_once(__DIR__ . '/../includes/image-upload-helper.php');

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
        if (isset($desktopImagesPaths['error'])) return $desktopImagesPaths;
        // Mobile multi-images
        $mobileImagesPaths  = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');
        if (isset($mobileImagesPaths['error'])) return $mobileImagesPaths;

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

        $desktopAnchorType = trim($post['desktop_anchor_type'] ?? '');
        $desktopAnchorId   = !empty($post['desktop_anchor_id']) ? (int)$post['desktop_anchor_id'] : null;
        $desktopAnchorPos  = ($post['desktop_anchor_position'] ?? 'after') === 'before' ? 'before' : 'after';

        $mobileAnchorType  = trim($post['mobile_anchor_type'] ?? '');
        $mobileAnchorId    = !empty($post['mobile_anchor_id']) ? (int)$post['mobile_anchor_id'] : null;
        $mobileAnchorPos   = ($post['mobile_anchor_position'] ?? 'after') === 'before' ? 'before' : 'after';

        $dbConn = (new Database())->getConnection();

        $desktopSortOrder  = (isset($post['desktop_sort_order']) && is_numeric($post['desktop_sort_order']) && $post['desktop_sort_order'] !== '')
            ? (float)$post['desktop_sort_order']
            : resolveAnchoredSortOrder($dbConn, $desktopAnchorType, $desktopAnchorId, $desktopAnchorPos, 'desktop', 'banners');

        $mobileSortOrder   = (isset($post['mobile_sort_order']) && is_numeric($post['mobile_sort_order']) && $post['mobile_sort_order'] !== '')
            ? (float)$post['mobile_sort_order']
            : resolveAnchoredSortOrder($dbConn, $mobileAnchorType, $mobileAnchorId, $mobileAnchorPos, 'mobile', 'banners');

        $data = [
            'title'                      => trim($post['title'] ?? ''),
            'slogan'                     => trim($post['slogan'] ?? '') ?: null,
            'show_slogan'                => isset($post['show_slogan']) ? (int)$post['show_slogan'] : 1,
            'description'                => trim($post['description'] ?? ''),
            'target_url'                 => $desktopUrlPrimary,
            'section_id'                 => (int)($post['section_id'] ?? $post['desktop_home_carousel_id'] ?? 0) ?: null,
            'layout_format'              => trim($post['desktop_design'] ?? $post['layout_format'] ?? 'desktop_format_1'),
            'display_order'              => (float)$desktopSortOrder,
            'status'                     => in_array($post['status'] ?? $post['desktop_status'] ?? '', ['active','inactive']) ? ($post['status'] ?? $post['desktop_status']) : 'active',
            'start_date'                 => !empty($post['desktop_start_date']) ? $post['desktop_start_date'] : (!empty($post['start_date']) ? $post['start_date'] : null),
            'end_date'                   => !empty($post['desktop_end_date']) ? $post['desktop_end_date'] : (!empty($post['end_date']) ? $post['end_date'] : null),
            'banner_type'                => trim($post['banner_type'] ?? 'single'),
            'page_target'                => in_array($post['page_target'] ?? '', ['home_page', 'other_pages', 'both']) ? $post['page_target'] : 'both',
            'banner_subtype'             => in_array($post['banner_subtype'] ?? '', ['small', 'normal', 'service', 'product_detail', 'dashboard']) ? $post['banner_subtype'] : 'normal',
            'extra_product_id'           => (int)($post['extra_product_id'] ?? 0) ?: null,
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
            'desktop_sort_order'         => (float)$desktopSortOrder,
            'desktop_anchor_type'        => $desktopAnchorType ?: null,
            'desktop_anchor_id'          => $desktopAnchorId,
            'desktop_anchor_position'    => $desktopAnchorPos,
            'desktop_start_date'         => !empty($post['desktop_start_date']) ? $post['desktop_start_date'] : null,
            'desktop_end_date'           => !empty($post['desktop_end_date']) ? $post['desktop_end_date'] : null,
            'mobile_show_other_pages'    => $post['mobile_show_other_pages'] ?? 'no',
            'mobile_other_category_id'   => (int)($post['mobile_other_category_id'] ?? 0) ?: null,
            'mobile_other_carousel_id'   => (int)($post['mobile_other_carousel_id'] ?? 0) ?: null,
            'mobile_home_category_id'    => (int)($post['mobile_home_category_id'] ?? 0) ?: null,
            'mobile_home_carousel_id'    => (int)($post['mobile_home_carousel_id'] ?? 0) ?: null,
            'mobile_design'              => trim($post['mobile_design'] ?? ''),
            'mobile_same_design'         => ($post['mobile_same_design'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'mobile_image'               => $mobileImgFinal,
            'mobile_images'              => !empty($mobileImagesPaths) ? json_encode($mobileImagesPaths) : null,
            'mobile_url'                 => $mobileUrlPrimary,
            'mobile_urls'                => $mobileUrls,
            'mobile_status'              => $post['mobile_status'] ?? 'active',
            'mobile_sort_order'          => (float)$mobileSortOrder,
            'mobile_anchor_type'         => $mobileAnchorType ?: null,
            'mobile_anchor_id'           => $mobileAnchorId,
            'mobile_anchor_position'     => $mobileAnchorPos,
            'mobile_start_date'          => !empty($post['mobile_start_date']) ? $post['mobile_start_date'] : null,
            'mobile_end_date'            => !empty($post['mobile_end_date']) ? $post['mobile_end_date'] : null,
        ];

        $data = array_merge($data, $this->parseMultiPageAndSeo($post));

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
        if (isset($desktopImagesPaths['error'])) return $desktopImagesPaths;
        $desktopImagesJson = !empty($desktopImagesPaths) ? json_encode($desktopImagesPaths) : $existing['desktop_images'];

        // Mobile multi-images
        $mobileImagesPaths = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');
        if (isset($mobileImagesPaths['error'])) return $mobileImagesPaths;
        $mobileImagesJson = !empty($mobileImagesPaths) ? json_encode($mobileImagesPaths) : $existing['mobile_images'];

        $desktopUrls = !empty($post['desktop_urls']) && is_array($post['desktop_urls'])
            ? json_encode(array_values(array_filter($post['desktop_urls'])))
            : (!empty($post['desktop_urls']) ? $post['desktop_urls'] : $existing['desktop_urls']);

        $mobileUrls = !empty($post['mobile_urls']) && is_array($post['mobile_urls'])
            ? json_encode(array_values(array_filter($post['mobile_urls'])))
            : (!empty($post['mobile_urls']) ? $post['mobile_urls'] : $existing['mobile_urls']);

        $desktopUrlPrimary = trim($post['desktop_url'] ?? $post['target_url'] ?? $existing['desktop_url']);
        $mobileUrlPrimary  = trim($post['mobile_url'] ?? $existing['mobile_url'] ?? $desktopUrlPrimary);

        $desktopAnchorType = trim($post['desktop_anchor_type'] ?? ($existing['desktop_anchor_type'] ?? ''));
        $desktopAnchorId   = !empty($post['desktop_anchor_id']) ? (int)$post['desktop_anchor_id'] : ($existing['desktop_anchor_id'] ?? null);
        $desktopAnchorPos  = ($post['desktop_anchor_position'] ?? ($existing['desktop_anchor_position'] ?? 'after')) === 'before' ? 'before' : 'after';

        $mobileAnchorType  = trim($post['mobile_anchor_type'] ?? ($existing['mobile_anchor_type'] ?? ''));
        $mobileAnchorId    = !empty($post['mobile_anchor_id']) ? (int)$post['mobile_anchor_id'] : ($existing['mobile_anchor_id'] ?? null);
        $mobileAnchorPos   = ($post['mobile_anchor_position'] ?? ($existing['mobile_anchor_position'] ?? 'after')) === 'before' ? 'before' : 'after';

        $dbConn = (new Database())->getConnection();

        $desktopSortOrder  = (isset($post['desktop_sort_order']) && is_numeric($post['desktop_sort_order']) && $post['desktop_sort_order'] !== '')
            ? (float)$post['desktop_sort_order']
            : ($existing['desktop_sort_order'] ?? resolveAnchoredSortOrder($dbConn, $desktopAnchorType, $desktopAnchorId, $desktopAnchorPos, 'desktop', 'banners'));

        $mobileSortOrder   = (isset($post['mobile_sort_order']) && is_numeric($post['mobile_sort_order']) && $post['mobile_sort_order'] !== '')
            ? (float)$post['mobile_sort_order']
            : ($existing['mobile_sort_order'] ?? resolveAnchoredSortOrder($dbConn, $mobileAnchorType, $mobileAnchorId, $mobileAnchorPos, 'mobile', 'banners'));

        $data = [
            'title'                      => trim($post['title'] ?? $existing['title']),
            'slogan'                     => trim($post['slogan'] ?? ($existing['slogan'] ?? '')) ?: null,
            'show_slogan'                => isset($post['show_slogan']) ? (int)$post['show_slogan'] : (int)($existing['show_slogan'] ?? 1),
            'description'                => trim($post['description'] ?? $existing['description']),
            'target_url'                 => $desktopUrlPrimary,
            'section_id'                 => (int)($post['section_id'] ?? $post['desktop_home_carousel_id'] ?? $existing['section_id']) ?: null,
            'layout_format'              => trim($post['desktop_design'] ?? $post['layout_format'] ?? $existing['layout_format']),
            'display_order'              => (float)$desktopSortOrder,
            'status'                     => in_array($post['status'] ?? $post['desktop_status'] ?? '', ['active','inactive']) ? ($post['status'] ?? $post['desktop_status']) : ($existing['status'] ?? 'active'),
            'start_date'                 => !empty($post['desktop_start_date']) ? $post['desktop_start_date'] : (!empty($post['start_date']) ? $post['start_date'] : $existing['start_date']),
            'end_date'                   => !empty($post['desktop_end_date']) ? $post['desktop_end_date'] : (!empty($post['end_date']) ? $post['end_date'] : $existing['end_date']),
            'banner_type'                => trim($post['banner_type'] ?? $existing['banner_type'] ?? 'single'),
            'page_target'                => in_array($post['page_target'] ?? '', ['home_page', 'other_pages', 'both']) ? $post['page_target'] : ($existing['page_target'] ?? 'both'),
            'banner_subtype'             => in_array($post['banner_subtype'] ?? '', ['small', 'normal', 'service', 'product_detail', 'dashboard']) ? $post['banner_subtype'] : ($existing['banner_subtype'] ?? 'normal'),
            'extra_product_id'           => (int)($post['extra_product_id'] ?? ($existing['extra_product_id'] ?? 0)) ?: null,
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
            'desktop_sort_order'         => (float)$desktopSortOrder,
            'desktop_anchor_type'        => $desktopAnchorType ?: null,
            'desktop_anchor_id'          => $desktopAnchorId,
            'desktop_anchor_position'    => $desktopAnchorPos,
            'desktop_start_date'         => !empty($post['desktop_start_date']) ? $post['desktop_start_date'] : ($existing['desktop_start_date'] ?? null),
            'desktop_end_date'           => !empty($post['desktop_end_date']) ? $post['desktop_end_date'] : ($existing['desktop_end_date'] ?? null),
            'mobile_show_other_pages'    => $post['mobile_show_other_pages'] ?? $existing['mobile_show_other_pages'] ?? 'no',
            'mobile_other_category_id'   => (int)($post['mobile_other_category_id'] ?? 0) ?: null,
            'mobile_other_carousel_id'   => (int)($post['mobile_other_carousel_id'] ?? 0) ?: null,
            'mobile_home_category_id'    => (int)($post['mobile_home_category_id'] ?? 0) ?: null,
            'mobile_home_carousel_id'    => (int)($post['mobile_home_carousel_id'] ?? 0) ?: null,
            'mobile_design'              => trim($post['mobile_design'] ?? $existing['mobile_design'] ?? ''),
            'mobile_same_design'         => ($post['mobile_same_design'] ?? ($existing['mobile_same_design'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'mobile_image'               => $mobileImgFinal,
            'mobile_images'              => $mobileImagesJson,
            'mobile_url'                 => $mobileUrlPrimary,
            'mobile_urls'                => $mobileUrls,
            'mobile_status'              => $post['mobile_status'] ?? $existing['mobile_status'] ?? 'active',
            'mobile_sort_order'          => (float)$mobileSortOrder,
            'mobile_anchor_type'         => $mobileAnchorType ?: null,
            'mobile_anchor_id'           => $mobileAnchorId,
            'mobile_anchor_position'     => $mobileAnchorPos,
            'mobile_start_date'          => !empty($post['mobile_start_date']) ? $post['mobile_start_date'] : ($existing['mobile_start_date'] ?? null),
            'mobile_end_date'            => !empty($post['mobile_end_date']) ? $post['mobile_end_date'] : ($existing['mobile_end_date'] ?? null),
        ];

        $data = array_merge($data, $this->parseMultiPageAndSeo($post, $existing));

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
        if (isset($desktopImagesPaths['error'])) return $desktopImagesPaths;
        $mobileImagesPaths  = $this->uploadMultipleImages($files['mobile_images'] ?? null, 'mobile');
        if (isset($mobileImagesPaths['error'])) return $mobileImagesPaths;

        // Service slider with printing / without printing images
        $desktopWithPrint = $this->uploadImage($files['desktop_with_printing_image'] ?? null, 'desktop');
        if (is_array($desktopWithPrint) && isset($desktopWithPrint['error'])) return $desktopWithPrint;

        $desktopWithoutPrint = $this->uploadImage($files['desktop_without_printing_image'] ?? null, 'desktop');
        if (is_array($desktopWithoutPrint) && isset($desktopWithoutPrint['error'])) return $desktopWithoutPrint;

        $mobileWithPrint = $this->uploadImage($files['mobile_with_printing_image'] ?? null, 'mobile');
        if (is_array($mobileWithPrint) && isset($mobileWithPrint['error'])) return $mobileWithPrint;

        $mobileWithoutPrint = $this->uploadImage($files['mobile_without_printing_image'] ?? null, 'mobile');
        if (is_array($mobileWithoutPrint) && isset($mobileWithoutPrint['error'])) return $mobileWithoutPrint;

        $mobileSameDesign = ($post['mobile_same_design'] ?? 'no') === 'yes' ? 'yes' : 'no';
        if ($mobileSameDesign === 'yes') {
            if (empty($mobileImagesPaths) && !empty($desktopImagesPaths)) {
                $mobileImagesPaths = $desktopImagesPaths;
            }
            if (empty($mobilePath) && !empty($desktopPath)) {
                $mobilePath = $desktopPath;
            }
            if (empty($mobileWithPrint) && !empty($desktopWithPrint)) {
                $mobileWithPrint = $desktopWithPrint;
            }
            if (empty($mobileWithoutPrint) && !empty($desktopWithoutPrint)) {
                $mobileWithoutPrint = $desktopWithoutPrint;
            }
        }

        $desktopUrls = !empty($post['desktop_urls']) && is_array($post['desktop_urls'])
            ? json_encode(array_values(array_filter($post['desktop_urls'])))
            : (!empty($post['desktop_urls']) ? $post['desktop_urls'] : null);

        $mobileUrls = !empty($post['mobile_urls']) && is_array($post['mobile_urls'])
            ? json_encode(array_values(array_filter($post['mobile_urls'])))
            : (!empty($post['mobile_urls']) ? $post['mobile_urls'] : ($mobileSameDesign === 'yes' ? $desktopUrls : null));

        $desktopImgFinal = $desktopPath ?: ($desktopImagesPaths[0] ?? '');
        $mobileImgFinal  = $mobilePath ?: ($mobileImagesPaths[0] ?? ($mobileSameDesign === 'yes' ? $desktopImgFinal : ''));

        $desktopAnchorType = trim($post['desktop_anchor_type'] ?? '');
        $desktopAnchorId   = !empty($post['desktop_anchor_id']) ? (int)$post['desktop_anchor_id'] : null;
        $desktopAnchorPos  = ($post['desktop_anchor_position'] ?? 'after') === 'before' ? 'before' : 'after';

        $mobileAnchorType  = trim($post['mobile_anchor_type'] ?? '');
        $mobileAnchorId    = !empty($post['mobile_anchor_id']) ? (int)$post['mobile_anchor_id'] : null;
        $mobileAnchorPos   = ($post['mobile_anchor_position'] ?? 'after') === 'before' ? 'before' : 'after';

        require_once __DIR__ . '/../includes/banner-helpers.php';
        $dbConn = (new Database())->getConnection();

        $desktopSortOrder = (isset($post['desktop_sort_order']) && is_numeric($post['desktop_sort_order']) && $post['desktop_sort_order'] !== '')
            ? (float)$post['desktop_sort_order']
            : resolveAnchoredSortOrder($dbConn, $desktopAnchorType, $desktopAnchorId, $desktopAnchorPos, 'desktop', 'banner_carousels');

        $mobileSortOrder = (isset($post['mobile_sort_order']) && is_numeric($post['mobile_sort_order']) && $post['mobile_sort_order'] !== '')
            ? (float)$post['mobile_sort_order']
            : resolveAnchoredSortOrder($dbConn, $mobileAnchorType, $mobileAnchorId, $mobileAnchorPos, 'mobile', 'banner_carousels');

        $data = [
            'title'                      => trim($post['title'] ?? ''),
            'slider_subtype'             => in_array($post['slider_subtype'] ?? '', ['top_slider', 'small_slider', 'offer_slider', 'service_slider', 'product_detail_slider', 'dashboard_slider']) ? $post['slider_subtype'] : 'top_slider',
            'page_target'                => in_array($post['page_target'] ?? '', ['home_page', 'other_pages', 'both']) ? $post['page_target'] : 'both',
            'text_tag_name'              => trim($post['text_tag_name'] ?? ''),
            'extra_product_id'           => (int)($post['extra_product_id'] ?? 0) ?: null,
            'desktop_anchor_type'        => $desktopAnchorType ?: null,
            'desktop_anchor_id'          => $desktopAnchorId,
            'desktop_anchor_position'    => $desktopAnchorPos,
            'mobile_anchor_type'         => $mobileAnchorType ?: null,
            'mobile_anchor_id'           => $mobileAnchorId,
            'mobile_anchor_position'     => $mobileAnchorPos,
            'mobile_same_design'         => $mobileSameDesign,
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
            'desktop_with_printing_image' => is_string($desktopWithPrint) && $desktopWithPrint !== '' ? $desktopWithPrint : null,
            'desktop_without_printing_image' => is_string($desktopWithoutPrint) && $desktopWithoutPrint !== '' ? $desktopWithoutPrint : null,
            'desktop_status'             => $post['desktop_status'] ?? 'active',
            'button_arrows'              => in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : 'enable',
            'auto_running'               => in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : 'enable',
            'auto_run_speed'             => !empty($post['auto_run_speed']) ? (int)$post['auto_run_speed'] : 3000,
            'desktop_sort_order'         => $desktopSortOrder,
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
            'mobile_with_printing_image' => is_string($mobileWithPrint) && $mobileWithPrint !== '' ? $mobileWithPrint : null,
            'mobile_without_printing_image' => is_string($mobileWithoutPrint) && $mobileWithoutPrint !== '' ? $mobileWithoutPrint : null,
            'mobile_status'              => $post['mobile_status'] ?? 'active',
            'mobile_sort_order'          => $mobileSortOrder,
            'mobile_other_sort_order'    => (int)($post['mobile_other_sort_order'] ?? 0),
            'mobile_start_date'          => !empty($post['mobile_start_date']) ? $post['mobile_start_date'] : null,
            'mobile_end_date'            => !empty($post['mobile_end_date']) ? $post['mobile_end_date'] : null,
            'show_product_category_carousel' => ($post['show_product_category_carousel'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'show_home_category'         => ($post['show_home_category'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'show_category_carousel'     => ($post['show_category_carousel'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'show_home_product'          => ($post['show_home_product'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'mobile_show_product_category_carousel' => ($post['mobile_show_product_category_carousel'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'mobile_show_home_category'  => ($post['mobile_show_home_category'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'mobile_show_category_carousel' => ($post['mobile_show_category_carousel'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'mobile_show_home_product'   => ($post['mobile_show_home_product'] ?? 'no') === 'yes' ? 'yes' : 'no',
            'status'                     => $post['status'] ?? $post['desktop_status'] ?? 'active',
        ];

        $data = array_merge($data, $this->parseMultiPageAndSeo($post));

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
                    if (is_array($uploadRes) && isset($uploadRes['error'])) return $uploadRes;
                    if (is_string($uploadRes) && $uploadRes !== '') $mergedDesktopImages[$idx] = $uploadRes;
                }
            }
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
                    if (is_array($uploadRes) && isset($uploadRes['error'])) return $uploadRes;
                    if (is_string($uploadRes) && $uploadRes !== '') $mergedMobileImages[$idx] = $uploadRes;
                }
            }
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

        // Service slider with printing / without printing images
        $desktopWithPrint = $existing['desktop_with_printing_image'] ?? null;
        if (!empty($files['desktop_with_printing_image']['name'])) {
            $res = $this->uploadImage($files['desktop_with_printing_image'], 'desktop');
            if (is_array($res) && isset($res['error'])) return $res;
            $desktopWithPrint = $res;
        }

        $desktopWithoutPrint = $existing['desktop_without_printing_image'] ?? null;
        if (!empty($files['desktop_without_printing_image']['name'])) {
            $res = $this->uploadImage($files['desktop_without_printing_image'], 'desktop');
            if (is_array($res) && isset($res['error'])) return $res;
            $desktopWithoutPrint = $res;
        }

        $mobileWithPrint = $existing['mobile_with_printing_image'] ?? null;
        if (!empty($files['mobile_with_printing_image']['name'])) {
            $res = $this->uploadImage($files['mobile_with_printing_image'], 'mobile');
            if (is_array($res) && isset($res['error'])) return $res;
            $mobileWithPrint = $res;
        }

        $mobileWithoutPrint = $existing['mobile_without_printing_image'] ?? null;
        if (!empty($files['mobile_without_printing_image']['name'])) {
            $res = $this->uploadImage($files['mobile_without_printing_image'], 'mobile');
            if (is_array($res) && isset($res['error'])) return $res;
            $mobileWithoutPrint = $res;
        }

        $mobileSameDesign = ($post['mobile_same_design'] ?? ($existing['mobile_same_design'] ?? 'no')) === 'yes' ? 'yes' : 'no';
        if ($mobileSameDesign === 'yes') {
            if (empty($mobileImagesJson) || $mobileImagesJson === '[]') {
                $mobileImagesJson = $desktopImagesJson;
            }
            if (empty($mobileImgFinal) && !empty($desktopImgFinal)) {
                $mobileImgFinal = $desktopImgFinal;
            }
            if (empty($mobileWithPrint) && !empty($desktopWithPrint)) {
                $mobileWithPrint = $desktopWithPrint;
            }
            if (empty($mobileWithoutPrint) && !empty($desktopWithoutPrint)) {
                $mobileWithoutPrint = $desktopWithoutPrint;
            }
        }

        $desktopAnchorType = trim($post['desktop_anchor_type'] ?? ($existing['desktop_anchor_type'] ?? ''));
        $desktopAnchorId   = !empty($post['desktop_anchor_id']) ? (int)$post['desktop_anchor_id'] : ($existing['desktop_anchor_id'] ?? null);
        $desktopAnchorPos  = ($post['desktop_anchor_position'] ?? ($existing['desktop_anchor_position'] ?? 'after')) === 'before' ? 'before' : 'after';

        $mobileAnchorType  = trim($post['mobile_anchor_type'] ?? ($existing['mobile_anchor_type'] ?? ''));
        $mobileAnchorId    = !empty($post['mobile_anchor_id']) ? (int)$post['mobile_anchor_id'] : ($existing['mobile_anchor_id'] ?? null);
        $mobileAnchorPos   = ($post['mobile_anchor_position'] ?? ($existing['mobile_anchor_position'] ?? 'after')) === 'before' ? 'before' : 'after';

        require_once __DIR__ . '/../includes/banner-helpers.php';
        $dbConn = (new Database())->getConnection();

        $desktopSortOrder  = (isset($post['desktop_sort_order']) && is_numeric($post['desktop_sort_order']) && $post['desktop_sort_order'] !== '')
            ? (float)$post['desktop_sort_order']
            : ($existing['desktop_sort_order'] ?? resolveAnchoredSortOrder($dbConn, $desktopAnchorType, $desktopAnchorId, $desktopAnchorPos, 'desktop', 'banner_carousels'));

        $mobileSortOrder   = (isset($post['mobile_sort_order']) && is_numeric($post['mobile_sort_order']) && $post['mobile_sort_order'] !== '')
            ? (float)$post['mobile_sort_order']
            : ($existing['mobile_sort_order'] ?? resolveAnchoredSortOrder($dbConn, $mobileAnchorType, $mobileAnchorId, $mobileAnchorPos, 'mobile', 'banner_carousels'));

        $data = [
            'title'                      => trim($post['title'] ?? $existing['title']),
            'slider_subtype'             => in_array($post['slider_subtype'] ?? '', ['top_slider', 'small_slider', 'offer_slider', 'service_slider', 'product_detail_slider', 'dashboard_slider']) ? $post['slider_subtype'] : ($existing['slider_subtype'] ?? 'top_slider'),
            'page_target'                => in_array($post['page_target'] ?? '', ['home_page', 'other_pages', 'both']) ? $post['page_target'] : ($existing['page_target'] ?? 'both'),
            'text_tag_name'              => trim($post['text_tag_name'] ?? ($existing['text_tag_name'] ?? '')),
            'extra_product_id'           => (int)($post['extra_product_id'] ?? ($existing['extra_product_id'] ?? 0)) ?: null,
            'desktop_anchor_type'        => $desktopAnchorType ?: null,
            'desktop_anchor_id'          => $desktopAnchorId,
            'desktop_anchor_position'    => $desktopAnchorPos,
            'mobile_anchor_type'         => $mobileAnchorType ?: null,
            'mobile_anchor_id'           => $mobileAnchorId,
            'mobile_anchor_position'     => $mobileAnchorPos,
            'mobile_same_design'         => $mobileSameDesign,
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
            'desktop_with_printing_image' => $desktopWithPrint,
            'desktop_without_printing_image' => $desktopWithoutPrint,
            'desktop_status'             => $post['desktop_status'] ?? $existing['desktop_status'] ?? 'active',
            'button_arrows'              => in_array($post['button_arrows'] ?? '', ['enable', 'disable']) ? $post['button_arrows'] : ($existing['button_arrows'] ?? 'enable'),
            'auto_running'               => in_array($post['auto_running'] ?? '', ['enable', 'disable']) ? $post['auto_running'] : ($existing['auto_running'] ?? 'enable'),
            'auto_run_speed'             => !empty($post['auto_run_speed']) ? (int)$post['auto_run_speed'] : (int)($existing['auto_run_speed'] ?? 3000),
            'desktop_sort_order'         => $desktopSortOrder,
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
            'mobile_with_printing_image' => $mobileWithPrint,
            'mobile_without_printing_image' => $mobileWithoutPrint,
            'mobile_status'              => $post['mobile_status'] ?? $existing['mobile_status'] ?? 'active',
            'mobile_sort_order'          => $mobileSortOrder,
            'mobile_other_sort_order'    => (int)($post['mobile_other_sort_order'] ?? $existing['mobile_other_sort_order'] ?? 0),
            'mobile_start_date'          => !empty($post['mobile_start_date']) ? $post['mobile_start_date'] : ($existing['mobile_start_date'] ?? null),
            'mobile_end_date'            => !empty($post['mobile_end_date']) ? $post['mobile_end_date'] : ($existing['mobile_end_date'] ?? null),
            'show_product_category_carousel' => ($post['show_product_category_carousel'] ?? ($existing['show_product_category_carousel'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'show_home_category'         => ($post['show_home_category'] ?? ($existing['show_home_category'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'show_category_carousel'     => ($post['show_category_carousel'] ?? ($existing['show_category_carousel'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'show_home_product'          => ($post['show_home_product'] ?? ($existing['show_home_product'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'mobile_show_product_category_carousel' => ($post['mobile_show_product_category_carousel'] ?? ($existing['mobile_show_product_category_carousel'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'mobile_show_home_category'  => ($post['mobile_show_home_category'] ?? ($existing['mobile_show_home_category'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'mobile_show_category_carousel' => ($post['mobile_show_category_carousel'] ?? ($existing['mobile_show_category_carousel'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'mobile_show_home_product'   => ($post['mobile_show_home_product'] ?? ($existing['mobile_show_home_product'] ?? 'no')) === 'yes' ? 'yes' : 'no',
            'status'                     => $post['status'] ?? $existing['status'] ?? 'active',
        ];

        $data = array_merge($data, $this->parseMultiPageAndSeo($post, $existing));

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
                } elseif (is_array($res) && isset($res['error'])) {
                    $this->removeUploadedWebpFiles($paths);
                    return $res;
                }
            }
        } else {
            $res = $this->uploadImage($files, $type);
            if (is_string($res) && !empty($res)) {
                $paths[] = $res;
            } elseif (is_array($res) && isset($res['error'])) {
                return $res;
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
        $dir = __DIR__ . "/../uploads/banners/{$type}/";
        $result = saveUploadedImageAsWebp($file, $dir, "uploads/banners/{$type}", [
            'max_bytes' => 10 * 1024 * 1024,
            'quality' => 84,
        ]);
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }
        return $result['path'];
    }

    private function removeUploadedWebpFiles(array $relativePaths): void {
        foreach ($relativePaths as $relativePath) {
            if (is_string($relativePath) && strpos($relativePath, 'uploads/banners/') === 0 && substr($relativePath, -5) === '.webp') {
                $absolutePath = __DIR__ . '/../' . $relativePath;
                if (is_file($absolutePath)) @unlink($absolutePath);
            }
        }
    }

    // ----------------------------------------------------------------
    // Multi-Page & SEO Field Parsing
    // ----------------------------------------------------------------

    private function parseMultiPageAndSeo(array $post, array $existing = []): array {
        $targetPage = trim($post['target_page'] ?? ($existing['target_page'] ?? 'home'));
        if (empty($targetPage)) $targetPage = 'home';

        $showCat   = (!empty($post['show_on_category']) && $post['show_on_category'] !== 'no') ? 'yes' : ($existing['show_on_category'] ?? 'no');
        $showProd  = (!empty($post['show_on_product']) && $post['show_on_product'] !== 'no') ? 'yes' : ($existing['show_on_product'] ?? 'no');
        $showPDet  = (!empty($post['show_on_product_detail']) && $post['show_on_product_detail'] !== 'no') ? 'yes' : ($existing['show_on_product_detail'] ?? 'no');
        $showFran  = (!empty($post['show_on_franchise']) && $post['show_on_franchise'] !== 'no') ? 'yes' : ($existing['show_on_franchise'] ?? 'no');
        $showAff   = (!empty($post['show_on_affiliate']) || !empty($post['show_on_affiliate_program'])) ? 'yes' : ($existing['show_on_affiliate'] ?? 'no');
        $showCar   = (!empty($post['show_on_career']) && $post['show_on_career'] !== 'no') ? 'yes' : ($existing['show_on_career'] ?? 'no');
        $showBiz   = (!empty($post['show_on_business_solutions']) && $post['show_on_business_solutions'] !== 'no') ? 'yes' : ($existing['show_on_business_solutions'] ?? 'no');
        $showAdv   = (!empty($post['show_on_advertising']) && $post['show_on_advertising'] !== 'no') ? 'yes' : ($existing['show_on_advertising'] ?? 'no');

        // Check if other_pages array is passed directly, e.g. post['other_pages'] = ['category', 'product']
        if (isset($post['other_pages']) && is_array($post['other_pages'])) {
            $showCat  = in_array('category', $post['other_pages']) ? 'yes' : 'no';
            $showProd = in_array('product', $post['other_pages']) ? 'yes' : 'no';
            $showPDet = in_array('product_detail', $post['other_pages']) ? 'yes' : 'no';
            $showFran = in_array('franchise', $post['other_pages']) ? 'yes' : 'no';
            $showAff  = (in_array('affiliate_program', $post['other_pages']) || in_array('affiliate', $post['other_pages'])) ? 'yes' : 'no';
            $showCar  = in_array('career', $post['other_pages']) ? 'yes' : 'no';
            $showBiz  = in_array('business_solutions', $post['other_pages']) ? 'yes' : 'no';
            $showAdv  = in_array('advertising', $post['other_pages']) ? 'yes' : 'no';
        }

        $otherPagesList = [];
        if ($showCat === 'yes') $otherPagesList[] = 'category';
        if ($showProd === 'yes') $otherPagesList[] = 'product';
        if ($showPDet === 'yes') $otherPagesList[] = 'product_detail';
        if ($showFran === 'yes') $otherPagesList[] = 'franchise';
        if ($showAff === 'yes') $otherPagesList[] = 'affiliate_program';
        if ($showCar === 'yes') $otherPagesList[] = 'career';
        if ($showBiz === 'yes') $otherPagesList[] = 'business_solutions';
        if ($showAdv === 'yes') $otherPagesList[] = 'advertising';

        $otherPagesDisplay = !empty($otherPagesList) ? json_encode($otherPagesList) : null;

        return [
            'alt_text'                    => trim($post['alt_text'] ?? ($existing['alt_text'] ?? '')),
            'meta_title'                  => trim($post['meta_title'] ?? ($existing['meta_title'] ?? '')),
            'meta_keywords'               => trim($post['meta_keywords'] ?? ($existing['meta_keywords'] ?? '')),
            'meta_description'            => trim($post['meta_description'] ?? ($existing['meta_description'] ?? '')),
            'target_page'                 => $targetPage,
            'other_pages_display'         => $otherPagesDisplay,
            'show_on_category'           => $showCat,
            'show_on_product'            => $showProd,
            'show_on_product_detail'     => $showPDet,
            'show_on_franchise'          => $showFran,
            'show_on_affiliate'          => $showAff,
            'show_on_career'             => $showCar,
            'show_on_business_solutions' => $showBiz,
            'show_on_advertising'        => $showAdv,
        ];
    }

    // ----------------------------------------------------------------
    // Master Unified Module Handlers
    // ----------------------------------------------------------------

    public function getAllMasterItems(string $pageFilter = 'all', string $typeFilter = 'all', string $search = '', string $statusFilter = 'all'): array {
        return $this->bannerModel->getAllMasterItems($pageFilter, $typeFilter, $search, $statusFilter);
    }

    public function togglePageVisibility(string $itemType, int $id, string $pageKey, string $targetVal): array {
        $ok = $this->bannerModel->togglePageVisibility($itemType, $id, $pageKey, $targetVal);
        return [
            'success' => $ok,
            'message' => $ok ? 'Page visibility updated successfully' : 'Failed to update page visibility'
        ];
    }

    public function toggleMasterStatus(string $itemType, int $id, string $status): array {
        $ok = $this->bannerModel->toggleMasterStatus($itemType, $id, $status);
        return [
            'success' => $ok,
            'message' => $ok ? 'Status updated successfully' : 'Failed to update status'
        ];
    }

    public function deleteMasterItem(string $itemType, int $id): array {
        $ok = $this->bannerModel->deleteMasterItem($itemType, $id);
        return [
            'success' => $ok,
            'message' => $ok ? 'Item deleted successfully' : 'Failed to delete item'
        ];
    }
}

// Alias so files using BannerLayoutController still work
class_alias('BannerController', 'BannerLayoutController');
?>
