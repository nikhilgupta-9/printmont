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

        if ($sectionKey === 'home_hero' || $sectionKey === 'mobile_hero_slider') {
            $page = $_GET['page'] ?? '';
            $params = [];
            $pageSql = '';
            if (!empty($page) && $page !== 'all') {
                $pageSql = " AND (target_pages IS NULL OR target_pages = '' OR target_pages = 'all' OR FIND_IN_SET(?, target_pages) OR target_pages LIKE ?)";
                $params = [$page, "%{$page}%"];
            }

            $sliders = $this->db->fetchAll(
                "SELECT id, title, '' as description,
                        desktop_image as image_url_desktop,
                        mobile_image as image_url_mobile,
                        desktop_url as target_url,
                        mobile_url,
                        desktop_design,
                        mobile_design,
                        auto_play_speed,
                        desktop_status,
                        mobile_status,
                        target_pages,
                        position_relation,
                        target_section,
                        placement_rules,
                        desktop_sort_order as display_order
                 FROM sliders
                 WHERE slider_type = 'main' AND status = 'active'
                   {$pageSql}
                   AND (start_date IS NULL OR start_date <= CURDATE())
                   AND (end_date IS NULL OR end_date >= CURDATE())
                 ORDER BY desktop_sort_order ASC, id ASC",
                $params
            );
            if (!empty($sliders)) {
                return [
                    'section_key'     => $sectionKey,
                    'label'           => $section['label'] ?? 'Hero Slider',
                    'columns_per_row' => 1,
                    'is_slider'       => true,
                    'auto_play_speed' => (int)($sliders[0]['auto_play_speed'] ?? 3000),
                    'banners'         => $sliders,
                ];
            }
        }

        if ($sectionKey === 'small_banner_slider' || $sectionKey === 'small_height_carousel') {
            $page = $_GET['page'] ?? '';
            $params = [];
            $pageSql = '';
            if (!empty($page)) {
                $pageSql = " AND (target_pages IS NULL OR target_pages = '' OR target_pages = 'all' OR FIND_IN_SET(?, target_pages) OR target_pages LIKE ?)";
                $params = [$page, "%{$page}%"];
            }

            $sliders = $this->db->fetchAll(
                "SELECT id, title, '' as description,
                        desktop_image as image_url_desktop,
                        mobile_image as image_url_mobile,
                        desktop_url as target_url,
                        mobile_url,
                        desktop_design,
                        mobile_design,
                        auto_play_speed,
                        desktop_status,
                        mobile_status,
                        target_pages,
                        position_relation,
                        target_section,
                        placement_rules,
                        desktop_sort_order as display_order
                 FROM sliders
                 WHERE slider_type = 'small' AND status = 'active'
                   {$pageSql}
                   AND (start_date IS NULL OR start_date <= CURDATE())
                   AND (end_date IS NULL OR end_date >= CURDATE())
                 ORDER BY desktop_sort_order ASC, id ASC",
                $params
            );
            if (!empty($sliders)) {
                return [
                    'section_key'     => $sectionKey,
                    'label'           => $section['label'] ?? 'Small Banner Carousel',
                    'columns_per_row' => 1,
                    'is_slider'       => true,
                    'auto_play_speed' => (int)($sliders[0]['auto_play_speed'] ?? 3000),
                    'banners'         => $sliders,
                ];
            }
        }

        if (!$section) {
            return [];
        }

        $banners = $this->db->fetchAll(
            "SELECT id, title, description,
                    image_url_desktop, image_url_mobile,
                    target_url, display_order, desktop_design, mobile_design,
                    button_arrows, auto_running, auto_run_speed
             FROM banners
             WHERE section_id = ? AND status = 'active'
               AND (start_date IS NULL OR start_date <= NOW())
               AND (end_date IS NULL OR end_date >= NOW())
             ORDER BY display_order ASC",
            [$section['id']]
        );

        $isSlider = (bool) $section['is_slider'];
        $carouselBanners = $this->getCarouselsForSection((int)$section['id']);
        if (!empty($carouselBanners)) {
            $banners = array_merge($carouselBanners, $banners);
            $isSlider = true;
        }

        return [
            'section_key'     => $sectionKey,
            'label'           => $section['label'],
            'columns_per_row' => (int) $section['columns_per_row'],
            'is_slider'       => $isSlider,
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
            $banners = [];
            $autoPlaySpeed = 3000;

            if ($section['section_key'] === 'home_hero' || $section['section_key'] === 'mobile_hero_slider') {
                $sliders = $this->db->fetchAll(
                    "SELECT id, title, '' as description,
                            desktop_image as image_url_desktop,
                            mobile_image as image_url_mobile,
                            desktop_url as target_url,
                            mobile_url,
                            desktop_design,
                            mobile_design,
                            auto_play_speed,
                            desktop_status,
                            mobile_status,
                            target_pages,
                            position_relation,
                            target_section,
                            placement_rules,
                            desktop_sort_order as display_order
                     FROM sliders
                     WHERE slider_type = 'main' AND status = 'active'
                       AND (
                           target_pages IS NULL OR target_pages = '' OR target_pages = 'all'
                           OR FIND_IN_SET(?, target_pages)
                           OR target_pages LIKE ?
                       )
                       AND (start_date IS NULL OR start_date <= CURDATE())
                       AND (end_date IS NULL OR end_date >= CURDATE())
                     ORDER BY desktop_sort_order ASC, id ASC",
                    [$page, "%{$page}%"]
                );
                if (!empty($sliders)) {
                    $banners = $sliders;
                    $autoPlaySpeed = (int)($sliders[0]['auto_play_speed'] ?? 3000);
                }
            } elseif ($section['section_key'] === 'small_banner_slider' || $section['section_key'] === 'small_height_carousel') {
                $sliders = $this->db->fetchAll(
                    "SELECT id, title, '' as description,
                            desktop_image as image_url_desktop,
                            mobile_image as image_url_mobile,
                            desktop_url as target_url,
                            mobile_url,
                            desktop_design,
                            mobile_design,
                            auto_play_speed,
                            desktop_status,
                            mobile_status,
                            target_pages,
                            position_relation,
                            target_section,
                            placement_rules,
                            desktop_sort_order as display_order
                     FROM sliders
                     WHERE slider_type = 'small' AND status = 'active'
                       AND (
                           target_pages IS NULL OR target_pages = '' OR target_pages = 'all'
                           OR FIND_IN_SET(?, target_pages)
                           OR target_pages LIKE ?
                       )
                       AND (start_date IS NULL OR start_date <= CURDATE())
                       AND (end_date IS NULL OR end_date >= CURDATE())
                     ORDER BY desktop_sort_order ASC, id ASC",
                    [$page, "%{$page}%"]
                );
                if (!empty($sliders)) {
                    $banners = $sliders;
                    $autoPlaySpeed = (int)($sliders[0]['auto_play_speed'] ?? 3000);
                }
            }

            if (empty($banners)) {
                $banners = $this->db->fetchAll(
                    "SELECT id, title, description,
                            image_url_desktop, image_url_mobile,
                            target_url, display_order, desktop_design, mobile_design,
                            button_arrows, auto_running, auto_run_speed
                     FROM banners
                     WHERE section_id = ? AND status = 'active'
                       AND (start_date IS NULL OR start_date <= NOW())
                       AND (end_date IS NULL OR end_date >= NOW())
                     ORDER BY display_order ASC",
                    [$section['id']]
                );
            }

            $isSlider = (bool) $section['is_slider'];
            $carouselBanners = $this->getCarouselsForSection((int)$section['id']);
            if (!empty($carouselBanners)) {
                $banners = array_merge($carouselBanners, $banners);
                $isSlider = true;
            }

            if (!empty($banners)) {
                $result[$section['section_key']] = [
                    'label'           => $section['label'],
                    'columns_per_row' => (int) $section['columns_per_row'],
                    'is_slider'       => $isSlider,
                    'auto_play_speed' => $autoPlaySpeed,
                    'banners'         => $banners,
                ];
            }
        }

        return $result;
    }

    /**
     * Get active banners from banner_carousels for a given section.
     */
    private function getCarouselsForSection(int $sectionId): array {
        $carousels = $this->db->fetchAll(
            "SELECT * FROM banner_carousels
             WHERE (desktop_home_carousel_id = ? OR desktop_other_carousel_id = ? OR mobile_carousel_id = ? OR mobile_other_carousel_id = ?)
               AND status = 'active'
               AND (
                   (desktop_status = 'active' AND (desktop_start_date IS NULL OR desktop_start_date <= CURDATE()) AND (desktop_end_date IS NULL OR desktop_end_date >= CURDATE()))
                   OR
                   (mobile_status = 'active' AND (mobile_start_date IS NULL OR mobile_start_date <= CURDATE()) AND (mobile_end_date IS NULL OR mobile_end_date >= CURDATE()))
               )
             ORDER BY desktop_sort_order ASC, id ASC",
            [$sectionId, $sectionId, $sectionId, $sectionId]
        );

        $out = [];
        foreach ($carousels as $c) {
            $deskImgs = !empty($c['desktop_images']) ? json_decode($c['desktop_images'], true) : [];
            $mobImgs  = !empty($c['mobile_images']) ? json_decode($c['mobile_images'], true) : [];
            $deskUrls = !empty($c['desktop_urls']) ? json_decode($c['desktop_urls'], true) : [];
            $mobUrls  = !empty($c['mobile_urls']) ? json_decode($c['mobile_urls'], true) : [];

            if (!empty($deskImgs)) {
                foreach ($deskImgs as $idx => $dImg) {
                    $out[] = [
                        'id'                => (int)$c['id'],
                        'title'             => $c['title'] . (count($deskImgs) > 1 ? " - Slide " . ($idx + 1) : ""),
                        'description'       => '',
                        'image_url_desktop' => $dImg,
                        'image_url_mobile'  => $mobImgs[$idx] ?? ($c['mobile_image'] ?: $dImg),
                        'target_url'        => $deskUrls[$idx] ?? ($c['desktop_url'] ?: ''),
                        'display_order'     => (int)$c['desktop_sort_order'] + $idx,
                        'desktop_design'    => $c['desktop_design'] ?? 'desktop_format_1',
                        'mobile_design'     => $c['mobile_design'] ?? '',
                        'button_arrows'     => $c['button_arrows'] ?? 'enable',
                        'auto_running'      => $c['auto_running'] ?? 'enable',
                        'auto_run_speed'    => (int)($c['auto_run_speed'] ?? 3000),
                    ];
                }
            } elseif (!empty($c['desktop_image']) || !empty($c['mobile_image'])) {
                $out[] = [
                    'id'                => (int)$c['id'],
                    'title'             => $c['title'],
                    'description'       => '',
                    'image_url_desktop' => $c['desktop_image'] ?: ($c['mobile_image'] ?? ''),
                    'image_url_mobile'  => $c['mobile_image'] ?: ($c['desktop_image'] ?? ''),
                    'target_url'        => $c['desktop_url'] ?: ($c['mobile_url'] ?? ''),
                    'display_order'     => (int)$c['desktop_sort_order'],
                    'desktop_design'    => $c['desktop_design'] ?? 'desktop_format_1',
                    'mobile_design'     => $c['mobile_design'] ?? '',
                    'button_arrows'     => $c['button_arrows'] ?? 'enable',
                    'auto_running'      => $c['auto_running'] ?? 'enable',
                    'auto_run_speed'    => (int)($c['auto_run_speed'] ?? 3000),
                ];
            }
        }
        return $out;
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
