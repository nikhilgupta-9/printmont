<?php
require_once(__DIR__ . '/../config/database.php');

class BannerModel {
    private $db;
    private $table = 'banners';

    public function __construct() {
        $this->db = new Database();
    }

    // ----------------------------------------------------------------
    // Reads
    // ----------------------------------------------------------------

    public function getAllBanners(): array {
        return $this->db->fetchAll(
            "SELECT b.*, s.label AS section_label, s.page AS section_page,
                    s.columns_per_row, s.is_slider, s.section_key
             FROM {$this->table} b
             LEFT JOIN banner_sections s ON s.id = b.section_id
             ORDER BY s.page ASC, s.display_order ASC, b.display_order ASC"
        );
    }

    public function getBannerById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT b.*, s.label AS section_label, s.section_key, s.columns_per_row, s.is_slider
             FROM {$this->table} b
             LEFT JOIN banner_sections s ON s.id = b.section_id
             WHERE b.id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function getBannersBySection(int $sectionId): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE section_id = ? AND status = 'active'
               AND (start_date IS NULL OR start_date <= NOW())
               AND (end_date IS NULL OR end_date >= NOW())
             ORDER BY display_order ASC",
            [$sectionId]
        );
    }

    public function getBannersWithPagination(int $page = 1, int $perPage = 20, string $search = '', string $pageFilter = '', int $sectionId = 0): array {
        $offset = ($page - 1) * $perPage;
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (b.title LIKE ? OR s.label LIKE ? OR s.page LIKE ? OR b.target_url LIKE ?)";
            $like    = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($pageFilter !== '') {
            $where   .= " AND s.page = ?";
            $params[] = $pageFilter;
        }

        if ($sectionId > 0) {
            $where   .= " AND b.section_id = ?";
            $params[] = $sectionId;
        }

        return $this->db->fetchAll(
            "SELECT b.*, s.label AS section_label, s.page AS section_page, s.section_key, s.columns_per_row, s.is_slider
             FROM {$this->table} b
             LEFT JOIN banner_sections s ON s.id = b.section_id
             WHERE {$where}
             ORDER BY s.page ASC, s.display_order ASC, b.display_order ASC, b.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
    }

    public function getBannersCount(string $search = '', string $pageFilter = '', int $sectionId = 0): int {
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   .= " AND (b.title LIKE ? OR s.label LIKE ? OR s.page LIKE ? OR b.target_url LIKE ?)";
            $like    = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($pageFilter !== '') {
            $where   .= " AND s.page = ?";
            $params[] = $pageFilter;
        }

        if ($sectionId > 0) {
            $where   .= " AND b.section_id = ?";
            $params[] = $sectionId;
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM {$this->table} b
             LEFT JOIN banner_sections s ON s.id = b.section_id
             WHERE {$where}",
            $params
        );
        return (int)($row['total'] ?? 0);
    }

    public function getBannerStats(): array {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'active')        AS active,
                    SUM(status = 'inactive')      AS inactive,
                    SUM(start_date > NOW())       AS scheduled,
                    SUM(end_date < NOW() AND end_date IS NOT NULL) AS expired
             FROM {$this->table}"
        );
        $stats = $row ?: [];

        // Also get counts by page
        $pageCounts = $this->db->fetchAll(
            "SELECT s.page, COUNT(b.id) AS count
             FROM banner_sections s
             LEFT JOIN {$this->table} b ON b.section_id = s.id
             WHERE b.id IS NOT NULL
             GROUP BY s.page"
        );
        $stats['by_page'] = [];
        foreach ($pageCounts as $pc) {
            $stats['by_page'][$pc['page']] = (int)$pc['count'];
        }

        return $stats;
    }

    // ----------------------------------------------------------------
    // Sections (for admin dropdown)
    // ----------------------------------------------------------------

    public function getAllSections(): array {
        return $this->db->fetchAll(
            "SELECT id, page, section_key, label, columns_per_row, is_slider
             FROM banner_sections
             WHERE status = 'active'
             ORDER BY page ASC, display_order ASC"
        );
    }

    public function getSectionById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM banner_sections WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function sectionKeyExists(string $key): bool {
        return (bool)$this->db->fetch(
            "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
            [$key]
        );
    }

    public function createSection(string $page, string $key, string $label, int $isSlider = 0, int $columns = 1): int|false {
        $next = $this->db->fetch(
            "SELECT COALESCE(MAX(display_order), 0) + 1 AS n FROM banner_sections WHERE page = ?",
            [$page]
        );
        return $this->db->insert(
            "INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, display_order, status)
             VALUES (?, ?, ?, ?, ?, ?, 'active')",
            [$page, $key, $label, $columns, $isSlider, (int)($next['n'] ?? 1)]
        );
    }

    /** Sections for the admin page-banner screens (every page except the one excluded, e.g. 'home'). */
    public function getSectionsExcludingPage(string $excludePage = 'home'): array {
        return $this->db->fetchAll(
            "SELECT s.id, s.page, s.section_key, s.label, s.columns_per_row, s.is_slider,
                    (SELECT COUNT(*) FROM {$this->table} b WHERE b.section_id = s.id) AS banner_count
             FROM banner_sections s
             WHERE s.status = 'active' AND s.page <> ?
             ORDER BY s.page ASC, s.display_order ASC, s.id ASC",
            [$excludePage]
        );
    }

    /** All banners that belong to sections of pages other than $excludePage. */
    public function getBannersExcludingPage(string $excludePage = 'home'): array {
        return $this->db->fetchAll(
            "SELECT b.*, s.label AS section_label, s.page AS section_page, s.section_key, s.is_slider
             FROM {$this->table} b
             INNER JOIN banner_sections s ON s.id = b.section_id
             WHERE s.page <> ?
             ORDER BY s.page ASC, s.display_order ASC, s.id ASC, b.display_order ASC, b.id ASC",
            [$excludePage]
        );
    }

    public function updateSectionSlider(int $sectionId, int $isSlider): bool {
        return (bool)$this->db->execute(
            "UPDATE banner_sections SET is_slider = ?, updated_at = NOW() WHERE id = ?",
            [$isSlider, $sectionId]
        );
    }

    public function updateSectionColumns(int $sectionId, int $columns): bool {
        return (bool)$this->db->execute(
            "UPDATE banner_sections SET columns_per_row = ?, updated_at = NOW() WHERE id = ?",
            [$columns, $sectionId]
        );
    }

    // ----------------------------------------------------------------
    // Helpers: Categories & Carousels
    // ----------------------------------------------------------------

    public function getAllCategories(): array {
        return $this->db->fetchAll("SELECT id, name, slug FROM categories ORDER BY name ASC");
    }

    public function getAllCarousels(): array {
        return $this->db->fetchAll(
            "SELECT id, label AS title, section_key, page, is_slider, columns_per_row
             FROM banner_sections
             ORDER BY page ASC, display_order ASC"
        );
    }

    public function getDeactivatedBanners(): array {
        return $this->db->fetchAll(
            "SELECT b.*, s.label AS section_label, s.page AS section_page, s.section_key
             FROM {$this->table} b
             LEFT JOIN banner_sections s ON s.id = b.section_id
             WHERE b.status = 'inactive' OR b.desktop_status = 'inactive'
             ORDER BY b.updated_at DESC, b.id DESC"
        );
    }

    public function toggleBannerStatus(int $id, string $status): bool {
        $newStatus = ($status === 'active') ? 'active' : 'inactive';
        return (bool)$this->db->execute(
            "UPDATE {$this->table} SET status = ?, desktop_status = ?, mobile_status = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $newStatus, $newStatus, $id]
        );
    }

    // ----------------------------------------------------------------
    // Writes: Banners
    // ----------------------------------------------------------------

    public function createBanner(array $data): int|false {
        $desktopImg = $data['desktop_image'] ?? $data['image_url_desktop'] ?? '';
        $mobileImg  = $data['mobile_image']  ?? $data['image_url_mobile']  ?? $desktopImg;
        $desktopUrl = $data['desktop_url']   ?? $data['target_url'] ?? '';
        $mobileUrl  = $data['mobile_url']    ?? $desktopUrl;

        return $this->db->insert(
            "INSERT INTO {$this->table}
             (title, alt_text, slogan, show_slogan, description, meta_title, meta_keywords, meta_description,
              target_page, other_pages_display,
              show_on_category, show_on_product, show_on_product_detail,
              show_on_franchise, show_on_affiliate, show_on_career,
              show_on_business_solutions, show_on_advertising,
              image_url_desktop, image_url_mobile, target_url, section_id,
              layout_format, display_order, status, start_date, end_date, banner_type, page_target, banner_subtype,
              extra_product_id,
              desktop_show_other_pages, desktop_other_category_id, desktop_other_carousel_id,
              desktop_home_category_id, desktop_home_carousel_id, desktop_design,
              desktop_image, desktop_images, desktop_url, desktop_urls,
              desktop_status, button_arrows, auto_running, auto_run_speed,
              desktop_sort_order, desktop_anchor_type, desktop_anchor_id, desktop_anchor_position,
              desktop_start_date, desktop_end_date,
              mobile_show_other_pages, mobile_other_category_id, mobile_other_carousel_id,
              mobile_home_category_id, mobile_home_carousel_id, mobile_design, mobile_same_design,
              mobile_image, mobile_images, mobile_url, mobile_urls,
              mobile_status, mobile_sort_order, mobile_anchor_type, mobile_anchor_id, mobile_anchor_position,
              mobile_start_date, mobile_end_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['title'],
                $data['alt_text'] ?? null,
                $data['slogan'] ?? null,
                isset($data['show_slogan']) ? (int)$data['show_slogan'] : 1,
                $data['description'] ?? '',
                $data['meta_title'] ?? null,
                $data['meta_keywords'] ?? null,
                $data['meta_description'] ?? null,
                $data['target_page'] ?? 'home',
                $data['other_pages_display'] ?? null,
                $data['show_on_category'] ?? 'no',
                $data['show_on_product'] ?? 'no',
                $data['show_on_product_detail'] ?? 'no',
                $data['show_on_franchise'] ?? 'no',
                $data['show_on_affiliate'] ?? 'no',
                $data['show_on_career'] ?? 'no',
                $data['show_on_business_solutions'] ?? 'no',
                $data['show_on_advertising'] ?? 'no',
                $desktopImg,
                $mobileImg,
                $desktopUrl,
                $data['section_id'] ?? null,
                $data['layout_format'] ?? 'desktop_format_1',
                $data['display_order'] ?? ($data['desktop_sort_order'] ?? 0),
                $data['status'] ?? 'active',
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['banner_type'] ?? 'single',
                $data['page_target'] ?? 'both',
                $data['banner_subtype'] ?? 'normal',
                $data['extra_product_id'] ?: null,
                $data['desktop_show_other_pages'] ?? 'no',
                $data['desktop_other_category_id'] ?: null,
                $data['desktop_other_carousel_id'] ?: null,
                $data['desktop_home_category_id'] ?: null,
                $data['desktop_home_carousel_id'] ?: null,
                $data['desktop_design'] ?? '',
                $desktopImg,
                $data['desktop_images'] ?? null,
                $desktopUrl,
                $data['desktop_urls'] ?? null,
                $data['desktop_status'] ?? 'active',
                $data['button_arrows'] ?? 'enable',
                $data['auto_running'] ?? 'enable',
                (int)($data['auto_run_speed'] ?? 3000),
                (float)($data['desktop_sort_order'] ?? 0),
                $data['desktop_anchor_type'] ?: null,
                $data['desktop_anchor_id'] ?: null,
                $data['desktop_anchor_position'] ?? 'after',
                $data['desktop_start_date'] ?: null,
                $data['desktop_end_date'] ?: null,
                $data['mobile_show_other_pages'] ?? 'no',
                $data['mobile_other_category_id'] ?: null,
                $data['mobile_other_carousel_id'] ?: null,
                $data['mobile_home_category_id'] ?: null,
                $data['mobile_home_carousel_id'] ?: null,
                $data['mobile_design'] ?? '',
                $data['mobile_same_design'] ?? 'no',
                $mobileImg,
                $data['mobile_images'] ?? null,
                $mobileUrl,
                $data['mobile_urls'] ?? null,
                $data['mobile_status'] ?? 'active',
                (float)($data['mobile_sort_order'] ?? 0),
                $data['mobile_anchor_type'] ?: null,
                $data['mobile_anchor_id'] ?: null,
                $data['mobile_anchor_position'] ?? 'after',
                $data['mobile_start_date'] ?: null,
                $data['mobile_end_date'] ?: null,
            ]
        );
    }

    public function updateBanner(int $id, array $data): bool {
        $desktopImg = $data['desktop_image'] ?? $data['image_url_desktop'] ?? '';
        $mobileImg  = $data['mobile_image']  ?? $data['image_url_mobile']  ?? $desktopImg;
        $desktopUrl = $data['desktop_url']   ?? $data['target_url'] ?? '';
        $mobileUrl  = $data['mobile_url']    ?? $desktopUrl;

        return (bool)$this->db->execute(
            "UPDATE {$this->table} SET
             title = ?, alt_text = ?, slogan = ?, show_slogan = ?, description = ?, meta_title = ?, meta_keywords = ?, meta_description = ?,
             target_page = ?, other_pages_display = ?,
             show_on_category = ?, show_on_product = ?, show_on_product_detail = ?,
             show_on_franchise = ?, show_on_affiliate = ?, show_on_career = ?,
             show_on_business_solutions = ?, show_on_advertising = ?,
             image_url_desktop = ?, image_url_mobile = ?, target_url = ?,
             section_id = ?, layout_format = ?, display_order = ?, status = ?, start_date = ?, end_date = ?, banner_type = ?,
             page_target = ?, banner_subtype = ?, extra_product_id = ?,
             desktop_show_other_pages = ?, desktop_other_category_id = ?, desktop_other_carousel_id = ?,
             desktop_home_category_id = ?, desktop_home_carousel_id = ?, desktop_design = ?,
             desktop_image = ?, desktop_images = ?, desktop_url = ?, desktop_urls = ?,
             desktop_status = ?, button_arrows = ?, auto_running = ?, auto_run_speed = ?,
             desktop_sort_order = ?, desktop_anchor_type = ?, desktop_anchor_id = ?, desktop_anchor_position = ?,
             desktop_start_date = ?, desktop_end_date = ?,
             mobile_show_other_pages = ?, mobile_other_category_id = ?, mobile_other_carousel_id = ?,
             mobile_home_category_id = ?, mobile_home_carousel_id = ?, mobile_design = ?, mobile_same_design = ?,
             mobile_image = ?, mobile_images = ?, mobile_url = ?, mobile_urls = ?,
             mobile_status = ?, mobile_sort_order = ?, mobile_anchor_type = ?, mobile_anchor_id = ?, mobile_anchor_position = ?,
             mobile_start_date = ?, mobile_end_date = ?,
             updated_at = NOW()
             WHERE id = ?",
            [
                $data['title'],
                $data['alt_text'] ?? null,
                $data['slogan'] ?? null,
                isset($data['show_slogan']) ? (int)$data['show_slogan'] : 1,
                $data['description'] ?? '',
                $data['meta_title'] ?? null,
                $data['meta_keywords'] ?? null,
                $data['meta_description'] ?? null,
                $data['target_page'] ?? 'home',
                $data['other_pages_display'] ?? null,
                $data['show_on_category'] ?? 'no',
                $data['show_on_product'] ?? 'no',
                $data['show_on_product_detail'] ?? 'no',
                $data['show_on_franchise'] ?? 'no',
                $data['show_on_affiliate'] ?? 'no',
                $data['show_on_career'] ?? 'no',
                $data['show_on_business_solutions'] ?? 'no',
                $data['show_on_advertising'] ?? 'no',
                $desktopImg,
                $mobileImg,
                $desktopUrl,
                $data['section_id'] ?? null,
                $data['layout_format'] ?? 'desktop_format_1',
                $data['display_order'] ?? ($data['desktop_sort_order'] ?? 0),
                $data['status'] ?? 'active',
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['banner_type'] ?? 'single',
                $data['page_target'] ?? 'both',
                $data['banner_subtype'] ?? 'normal',
                $data['extra_product_id'] ?: null,
                $data['desktop_show_other_pages'] ?? 'no',
                $data['desktop_other_category_id'] ?: null,
                $data['desktop_other_carousel_id'] ?: null,
                $data['desktop_home_category_id'] ?: null,
                $data['desktop_home_carousel_id'] ?: null,
                $data['desktop_design'] ?? '',
                $desktopImg,
                $data['desktop_images'] ?? null,
                $desktopUrl,
                $data['desktop_urls'] ?? null,
                $data['desktop_status'] ?? 'active',
                $data['button_arrows'] ?? 'enable',
                $data['auto_running'] ?? 'enable',
                (int)($data['auto_run_speed'] ?? 3000),
                (float)($data['desktop_sort_order'] ?? 0),
                $data['desktop_anchor_type'] ?: null,
                $data['desktop_anchor_id'] ?: null,
                $data['desktop_anchor_position'] ?? 'after',
                $data['desktop_start_date'] ?: null,
                $data['desktop_end_date'] ?: null,
                $data['mobile_show_other_pages'] ?? 'no',
                $data['mobile_other_category_id'] ?: null,
                $data['mobile_other_carousel_id'] ?: null,
                $data['mobile_home_category_id'] ?: null,
                $data['mobile_home_carousel_id'] ?: null,
                $data['mobile_design'] ?? '',
                $data['mobile_same_design'] ?? 'no',
                $mobileImg,
                $data['mobile_images'] ?? null,
                $mobileUrl,
                $data['mobile_urls'] ?? null,
                $data['mobile_status'] ?? 'active',
                (float)($data['mobile_sort_order'] ?? 0),
                $data['mobile_anchor_type'] ?: null,
                $data['mobile_anchor_id'] ?: null,
                $data['mobile_anchor_position'] ?? 'after',
                $data['mobile_start_date'] ?: null,
                $data['mobile_end_date'] ?: null,
                $id,
            ]
        );
    }

    public function deleteBanner(int $id): bool {
        return (bool)$this->db->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    // ----------------------------------------------------------------
    // Writes: Banner Carousels
    // ----------------------------------------------------------------

    public function getAllBannerCarousels(string $pageFilter = '', string $search = '', int $sectionId = 0): array {
        $where = '1=1';
        $params = [];

        if ($pageFilter !== '') {
            $where .= " AND (c.desktop_page = ? OR c.mobile_page = ? OR sec.page = ?)";
            $params[] = $pageFilter;
            $params[] = $pageFilter;
            $params[] = $pageFilter;
        }

        if ($search !== '') {
            $where .= " AND (c.title LIKE ? OR sec.label LIKE ? OR sec.section_key LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($sectionId > 0) {
            $where .= " AND (c.desktop_home_carousel_id = ? OR c.desktop_other_carousel_id = ? OR c.mobile_carousel_id = ? OR c.mobile_other_carousel_id = ?)";
            $params[] = $sectionId;
            $params[] = $sectionId;
            $params[] = $sectionId;
            $params[] = $sectionId;
        }

        return $this->db->fetchAll(
            "SELECT c.*, cat.name AS desktop_category_name, sec.label AS carousel_label, sec.page AS section_page
             FROM banner_carousels c
             LEFT JOIN categories cat ON cat.id = c.desktop_home_category_id
             LEFT JOIN banner_sections sec ON sec.id = c.desktop_home_carousel_id
             WHERE {$where}
             ORDER BY c.desktop_sort_order ASC, c.id DESC",
            $params
        );
    }

    public function getBannerCarouselById(int $id): ?array {
        $row = $this->db->fetch("SELECT * FROM banner_carousels WHERE id = ?", [$id]);
        return $row ?: null;
    }

    public function createBannerCarousel(array $data): int|false {
        return $this->db->insert(
            "INSERT INTO banner_carousels
             (title, alt_text, description, meta_title, meta_keywords, meta_description,
              target_page, other_pages_display,
              show_on_category, show_on_product, show_on_product_detail,
              show_on_franchise, show_on_affiliate, show_on_career,
              show_on_business_solutions, show_on_advertising,
              slider_subtype, page_target, text_tag_name, extra_product_id,
              desktop_anchor_type, desktop_anchor_id, desktop_anchor_position,
              mobile_anchor_type, mobile_anchor_id, mobile_anchor_position, mobile_same_design,
              desktop_page, desktop_home_category_id, desktop_home_carousel_id,
              desktop_show_other_pages, desktop_other_category_id, desktop_other_carousel_id,
              desktop_design, desktop_image, desktop_images, desktop_url, desktop_urls,
              desktop_with_printing_image, desktop_without_printing_image,
              desktop_status, button_arrows, auto_running, auto_run_speed,
              desktop_sort_order, desktop_start_date, desktop_end_date,
              mobile_design, mobile_page, mobile_category_id, mobile_carousel_id,
              mobile_other_pages, mobile_other_category_id, mobile_other_carousel_id,
              mobile_image, mobile_images, mobile_url, mobile_urls,
              mobile_with_printing_image, mobile_without_printing_image,
              mobile_status, mobile_sort_order, mobile_other_sort_order,
              mobile_start_date, mobile_end_date,
              show_product_category_carousel, show_home_category, show_category_carousel, show_home_product,
              mobile_show_product_category_carousel, mobile_show_home_category, mobile_show_category_carousel, mobile_show_home_product,
              status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['title'],
                $data['alt_text'] ?? null,
                $data['description'] ?? '',
                $data['meta_title'] ?? null,
                $data['meta_keywords'] ?? null,
                $data['meta_description'] ?? null,
                $data['target_page'] ?? 'home',
                $data['other_pages_display'] ?? null,
                $data['show_on_category'] ?? 'no',
                $data['show_on_product'] ?? 'no',
                $data['show_on_product_detail'] ?? 'no',
                $data['show_on_franchise'] ?? 'no',
                $data['show_on_affiliate'] ?? 'no',
                $data['show_on_career'] ?? 'no',
                $data['show_on_business_solutions'] ?? 'no',
                $data['show_on_advertising'] ?? 'no',
                $data['slider_subtype'] ?? 'top_slider',
                $data['page_target'] ?? 'both',
                $data['text_tag_name'] ?: null,
                $data['extra_product_id'] ?: null,
                $data['desktop_anchor_type'] ?: null,
                $data['desktop_anchor_id'] ?: null,
                $data['desktop_anchor_position'] ?? 'after',
                $data['mobile_anchor_type'] ?: null,
                $data['mobile_anchor_id'] ?: null,
                $data['mobile_anchor_position'] ?? 'after',
                $data['mobile_same_design'] ?? 'no',
                $data['desktop_page'] ?? 'home',
                $data['desktop_home_category_id'] ?: null,
                $data['desktop_home_carousel_id'] ?: null,
                $data['desktop_show_other_pages'] ?? 'no',
                $data['desktop_other_category_id'] ?: null,
                $data['desktop_other_carousel_id'] ?: null,
                $data['desktop_design'] ?? '',
                $data['desktop_image'] ?? '',
                $data['desktop_images'] ?? null,
                $data['desktop_url'] ?? '',
                $data['desktop_urls'] ?? null,
                $data['desktop_with_printing_image'] ?? null,
                $data['desktop_without_printing_image'] ?? null,
                $data['desktop_status'] ?? 'active',
                $data['button_arrows'] ?? 'enable',
                $data['auto_running'] ?? 'enable',
                (int)($data['auto_run_speed'] ?? 3000),
                $data['desktop_sort_order'] ?? 0,
                $data['desktop_start_date'] ?: null,
                $data['desktop_end_date'] ?: null,
                $data['mobile_design'] ?? '',
                $data['mobile_page'] ?? 'home',
                $data['mobile_category_id'] ?: null,
                $data['mobile_carousel_id'] ?: null,
                $data['mobile_other_pages'] ?? 'no',
                $data['mobile_other_category_id'] ?: null,
                $data['mobile_other_carousel_id'] ?: null,
                $data['mobile_image'] ?? '',
                $data['mobile_images'] ?? null,
                $data['mobile_url'] ?? '',
                $data['mobile_urls'] ?? null,
                $data['mobile_with_printing_image'] ?? null,
                $data['mobile_without_printing_image'] ?? null,
                $data['mobile_status'] ?? 'active',
                $data['mobile_sort_order'] ?? 0,
                $data['mobile_other_sort_order'] ?? 0,
                $data['mobile_start_date'] ?: null,
                $data['mobile_end_date'] ?: null,
                $data['show_product_category_carousel'] ?? 'yes',
                $data['show_home_category'] ?? 'yes',
                $data['show_category_carousel'] ?? 'yes',
                $data['show_home_product'] ?? 'yes',
                $data['mobile_show_product_category_carousel'] ?? 'yes',
                $data['mobile_show_home_category'] ?? 'yes',
                $data['mobile_show_category_carousel'] ?? 'yes',
                $data['mobile_show_home_product'] ?? 'yes',
                $data['status'] ?? 'active',
            ]
        );
    }

    public function updateBannerCarousel(int $id, array $data): bool {
        return (bool)$this->db->execute(
            "UPDATE banner_carousels SET
             title = ?, alt_text = ?, description = ?, meta_title = ?, meta_keywords = ?, meta_description = ?,
             target_page = ?, other_pages_display = ?,
             show_on_category = ?, show_on_product = ?, show_on_product_detail = ?,
             show_on_franchise = ?, show_on_affiliate = ?, show_on_career = ?,
             show_on_business_solutions = ?, show_on_advertising = ?,
             slider_subtype = ?, page_target = ?, text_tag_name = ?, extra_product_id = ?,
             desktop_anchor_type = ?, desktop_anchor_id = ?, desktop_anchor_position = ?,
             mobile_anchor_type = ?, mobile_anchor_id = ?, mobile_anchor_position = ?, mobile_same_design = ?,
             desktop_page = ?, desktop_home_category_id = ?, desktop_home_carousel_id = ?,
             desktop_show_other_pages = ?, desktop_other_category_id = ?, desktop_other_carousel_id = ?,
             desktop_design = ?, desktop_image = ?, desktop_images = ?, desktop_url = ?, desktop_urls = ?,
             desktop_with_printing_image = ?, desktop_without_printing_image = ?,
             desktop_status = ?, button_arrows = ?, auto_running = ?, auto_run_speed = ?,
             desktop_sort_order = ?, desktop_start_date = ?, desktop_end_date = ?,
             mobile_design = ?, mobile_page = ?, mobile_category_id = ?, mobile_carousel_id = ?,
             mobile_other_pages = ?, mobile_other_category_id = ?, mobile_other_carousel_id = ?,
             mobile_image = ?, mobile_images = ?, mobile_url = ?, mobile_urls = ?,
             mobile_with_printing_image = ?, mobile_without_printing_image = ?,
             mobile_status = ?, mobile_sort_order = ?, mobile_other_sort_order = ?,
             mobile_start_date = ?, mobile_end_date = ?,
             show_product_category_carousel = ?, show_home_category = ?, show_category_carousel = ?, show_home_product = ?,
             mobile_show_product_category_carousel = ?, mobile_show_home_category = ?, mobile_show_category_carousel = ?, mobile_show_home_product = ?,
             status = ?,
             updated_at = NOW()
             WHERE id = ?",
            [
                $data['title'],
                $data['alt_text'] ?? null,
                $data['description'] ?? '',
                $data['meta_title'] ?? null,
                $data['meta_keywords'] ?? null,
                $data['meta_description'] ?? null,
                $data['target_page'] ?? 'home',
                $data['other_pages_display'] ?? null,
                $data['show_on_category'] ?? 'no',
                $data['show_on_product'] ?? 'no',
                $data['show_on_product_detail'] ?? 'no',
                $data['show_on_franchise'] ?? 'no',
                $data['show_on_affiliate'] ?? 'no',
                $data['show_on_career'] ?? 'no',
                $data['show_on_business_solutions'] ?? 'no',
                $data['show_on_advertising'] ?? 'no',
                $data['slider_subtype'] ?? 'top_slider',
                $data['page_target'] ?? 'both',
                $data['text_tag_name'] ?: null,
                $data['extra_product_id'] ?: null,
                $data['desktop_anchor_type'] ?: null,
                $data['desktop_anchor_id'] ?: null,
                $data['desktop_anchor_position'] ?? 'after',
                $data['mobile_anchor_type'] ?: null,
                $data['mobile_anchor_id'] ?: null,
                $data['mobile_anchor_position'] ?? 'after',
                $data['mobile_same_design'] ?? 'no',
                $data['desktop_page'] ?? 'home',
                $data['desktop_home_category_id'] ?: null,
                $data['desktop_home_carousel_id'] ?: null,
                $data['desktop_show_other_pages'] ?? 'no',
                $data['desktop_other_category_id'] ?: null,
                $data['desktop_other_carousel_id'] ?: null,
                $data['desktop_design'] ?? '',
                $data['desktop_image'] ?? '',
                $data['desktop_images'] ?? null,
                $data['desktop_url'] ?? '',
                $data['desktop_urls'] ?? null,
                $data['desktop_with_printing_image'] ?? null,
                $data['desktop_without_printing_image'] ?? null,
                $data['desktop_status'] ?? 'active',
                $data['button_arrows'] ?? 'enable',
                $data['auto_running'] ?? 'enable',
                (int)($data['auto_run_speed'] ?? 3000),
                $data['desktop_sort_order'] ?? 0,
                $data['desktop_start_date'] ?: null,
                $data['desktop_end_date'] ?: null,
                $data['mobile_design'] ?? '',
                $data['mobile_page'] ?? 'home',
                $data['mobile_category_id'] ?: null,
                $data['mobile_carousel_id'] ?: null,
                $data['mobile_other_pages'] ?? 'no',
                $data['mobile_other_category_id'] ?: null,
                $data['mobile_other_carousel_id'] ?: null,
                $data['mobile_image'] ?? '',
                $data['mobile_images'] ?? null,
                $data['mobile_url'] ?? '',
                $data['mobile_urls'] ?? null,
                $data['mobile_with_printing_image'] ?? null,
                $data['mobile_without_printing_image'] ?? null,
                $data['mobile_status'] ?? 'active',
                $data['mobile_sort_order'] ?? 0,
                $data['mobile_other_sort_order'] ?? 0,
                $data['mobile_start_date'] ?: null,
                $data['mobile_end_date'] ?: null,
                $data['show_product_category_carousel'] ?? 'yes',
                $data['show_home_category'] ?? 'yes',
                $data['show_category_carousel'] ?? 'yes',
                $data['show_home_product'] ?? 'yes',
                $data['mobile_show_product_category_carousel'] ?? 'yes',
                $data['mobile_show_home_category'] ?? 'yes',
                $data['mobile_show_category_carousel'] ?? 'yes',
                $data['mobile_show_home_product'] ?? 'yes',
                $data['status'] ?? 'active',
                $id,
            ]
        );
    }

    public function toggleBannerCarouselStatus(int $id, string $status): bool {
        $newStatus = ($status === 'active') ? 'active' : 'inactive';
        return (bool)$this->db->execute(
            "UPDATE banner_carousels SET status = ?, desktop_status = ?, mobile_status = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $newStatus, $newStatus, $id]
        );
    }

    public function deleteBannerCarousel(int $id): bool {
        return (bool)$this->db->execute("DELETE FROM banner_carousels WHERE id = ?", [$id]);
    }

    // ----------------------------------------------------------------
    // Master Module Unified Retrieval & Instant Toggles
    // ----------------------------------------------------------------

    public function getAllMasterItems(string $pageFilter = 'all', string $typeFilter = 'all', string $search = '', string $statusFilter = 'all'): array {
        $items = [];
        $searchLike = !empty($search) ? "%{$search}%" : '';

        // Helper to build page where condition
        $getPageCond = function(string $pFilter, string $table = 'banners') {
            if (empty($pFilter) || $pFilter === 'all') return '1=1';
            if ($pFilter === 'home') {
                return "(target_page = 'home' OR target_page = 'both' OR target_page IS NULL OR target_page = '')";
            }
            if ($pFilter === 'about' || $pFilter === 'about_us') {
                if ($table === 'banners') {
                    return "(target_page IN ('about', 'about_us') OR target_page = 'both' OR show_on_about_us = 'yes' OR other_pages_display LIKE '%about%' OR position = 'about' OR section_id IN (28, 29, 49, 50, 51))";
                }
                return "(target_page IN ('about', 'about_us') OR target_page = 'both' OR show_on_about_us = 'yes' OR other_pages_display LIKE '%about%')";
            }
            $colMap = [
                'category'           => 'show_on_category',
                'product'            => 'show_on_product',
                'product_detail'     => 'show_on_product_detail',
                'franchise'          => 'show_on_franchise',
                'affiliate_program'  => 'show_on_affiliate',
                'career'             => 'show_on_career',
                'business_solutions' => 'show_on_business_solutions',
                'advertising'        => 'show_on_advertising',
                'about_us'           => 'show_on_about_us',
            ];
            $col = $colMap[$pFilter] ?? '';
            if ($col) {
                return "(target_page = '{$pFilter}' OR target_page = 'both' OR {$col} = 'yes' OR other_pages_display LIKE '%{$pFilter}%')";
            }
            return "(target_page = '{$pFilter}' OR target_page = 'both' OR other_pages_display LIKE '%{$pFilter}%')";
        };

        // 1. Fetch Banners
        if ($typeFilter === 'all' || $typeFilter === 'banner') {
            $where = $getPageCond($pageFilter, 'banners');
            $params = [];
            if ($statusFilter !== 'all') {
                $where .= " AND status = ?";
                $params[] = $statusFilter;
            }
            if (!empty($searchLike)) {
                $where .= " AND (title LIKE ? OR alt_text LIKE ? OR description LIKE ? OR meta_title LIKE ?)";
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
            }
            $rows = $this->db->fetchAll(
                "SELECT id, 'banner' as item_type, title, alt_text, description, meta_title, meta_keywords, meta_description,
                        image_url_desktop, image_url_mobile, desktop_image, mobile_image, target_url,
                        target_page, other_pages_display,
                        show_on_category, show_on_product, show_on_product_detail,
                        show_on_franchise, show_on_affiliate, show_on_career,
                        show_on_business_solutions, show_on_advertising, show_on_about_us,
                        desktop_design as layout_format, banner_subtype,
                        desktop_sort_order, desktop_anchor_type, desktop_anchor_id, desktop_anchor_position,
                        status, desktop_status, mobile_status, created_at, updated_at
                 FROM banners
                 WHERE {$where}
                 ORDER BY desktop_sort_order ASC, id DESC",
                $params
            );
            foreach ($rows as $r) {
                $r['thumb'] = $r['desktop_image'] ?: ($r['image_url_desktop'] ?: ($r['mobile_image'] ?: ''));
                $items[] = $r;
            }
        }

        // 2. Fetch Carousels (Product & Category carousels)
        if ($typeFilter === 'all' || $typeFilter === 'carousel') {
            $where = $getPageCond($pageFilter, 'carousels');
            $params = [];
            if ($statusFilter !== 'all') {
                $where .= " AND status = ?";
                $params[] = $statusFilter;
            }
            if (!empty($searchLike)) {
                $where .= " AND (title LIKE ? OR alt_text LIKE ? OR description LIKE ? OR meta_title LIKE ?)";
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
            }
            $rows = $this->db->fetchAll(
                "SELECT id, 'carousel' as item_type, title, alt_text, description, meta_title, meta_keywords, meta_description,
                        desktop_bg_image as image_url_desktop, mobile_bg_image as image_url_mobile, desktop_bg_image as desktop_image, mobile_bg_image as mobile_image, '' as target_url,
                        target_page, other_pages_display,
                        show_on_category, show_on_product, show_on_product_detail,
                        show_on_franchise, show_on_affiliate, show_on_career,
                        show_on_business_solutions, show_on_advertising, show_on_about_us,
                        desktop_home_design as layout_format, carousel_type as banner_subtype,
                        desktop_sort_order, desktop_anchor_type, desktop_anchor_id, desktop_anchor_position,
                        status, desktop_status, mobile_status, created_at, updated_at
                 FROM carousels
                 WHERE {$where}
                 ORDER BY desktop_sort_order ASC, id DESC",
                $params
            );
            foreach ($rows as $r) {
                $r['thumb'] = $r['desktop_image'] ?? ($r['mobile_image'] ?? '');
                $items[] = $r;
            }
        }

        // 3. Fetch Banner Carousels / Sliders from banner_carousels
        if ($typeFilter === 'all' || $typeFilter === 'carousel' || $typeFilter === 'banner_carousel') {
            $where = $getPageCond($pageFilter, 'banner_carousels');
            $params = [];
            if ($statusFilter !== 'all') {
                $where .= " AND status = ?";
                $params[] = $statusFilter;
            }
            if (!empty($searchLike)) {
                $where .= " AND (title LIKE ? OR alt_text LIKE ? OR description LIKE ? OR meta_title LIKE ?)";
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
            }
            $rowsBC = $this->db->fetchAll(
                "SELECT id, 'banner_carousel' as item_type, title, alt_text, description, meta_title, meta_keywords, meta_description,
                        desktop_image as image_url_desktop, mobile_image as image_url_mobile, desktop_image, mobile_image, desktop_url as target_url,
                        target_page, other_pages_display,
                        show_on_category, show_on_product, show_on_product_detail,
                        show_on_franchise, show_on_affiliate, show_on_career,
                        show_on_business_solutions, show_on_advertising, show_on_about_us,
                        desktop_design as layout_format, slider_subtype as banner_subtype,
                        desktop_sort_order, desktop_anchor_type, desktop_anchor_id, desktop_anchor_position,
                        status, desktop_status, mobile_status, created_at, updated_at
                 FROM banner_carousels
                 WHERE {$where}
                 ORDER BY desktop_sort_order ASC, id DESC",
                $params
            );
            foreach ($rowsBC as $r) {
                $r['thumb'] = $r['desktop_image'] ?: ($r['mobile_image'] ?: '');
                $items[] = $r;
            }
        }

        // Sort combined items by sort_order
        usort($items, function($a, $b) {
            $oa = (float)($a['desktop_sort_order'] ?? 0);
            $ob = (float)($b['desktop_sort_order'] ?? 0);
            if ($oa == $ob) {
                return (int)$b['id'] - (int)$a['id'];
            }
            return $oa <=> $ob;
        });

        return $items;
    }

    public function togglePageVisibility(string $itemType, int $id, string $pageKey, string $targetVal): bool {
        $colMap = [
            'category'           => 'show_on_category',
            'product'            => 'show_on_product',
            'product_detail'     => 'show_on_product_detail',
            'franchise'          => 'show_on_franchise',
            'affiliate_program'  => 'show_on_affiliate',
            'career'             => 'show_on_career',
            'business_solutions' => 'show_on_business_solutions',
            'advertising'        => 'show_on_advertising',
            'about_us'           => 'show_on_about_us',
        ];

        $col = $colMap[$pageKey] ?? null;
        if (!$col) return false;

        $val = ($targetVal === 'yes') ? 'yes' : 'no';
        $table = ($itemType === 'carousel') ? 'carousels' : (($itemType === 'banner_carousel') ? 'banner_carousels' : 'banners');

        // Fetch current other_pages_display JSON
        $row = $this->db->fetch("SELECT other_pages_display FROM {$table} WHERE id = ?", [$id]);
        $currentList = [];
        if (!empty($row['other_pages_display'])) {
            $decoded = json_decode($row['other_pages_display'], true);
            if (is_array($decoded)) $currentList = $decoded;
        }

        if ($val === 'yes') {
            if (!in_array($pageKey, $currentList)) $currentList[] = $pageKey;
        } else {
            $currentList = array_values(array_diff($currentList, [$pageKey]));
        }
        $updatedJson = json_encode($currentList);

        return (bool)$this->db->execute(
            "UPDATE {$table} SET {$col} = ?, other_pages_display = ?, updated_at = NOW() WHERE id = ?",
            [$val, $updatedJson, $id]
        );
    }

    public function toggleCarouselStatus(int $id, string $status): bool {
        $status = ($status === 'active') ? 'active' : 'inactive';
        return (bool)$this->db->execute("UPDATE carousels SET status = ?, desktop_status = ?, mobile_status = ?, updated_at = NOW() WHERE id = ?", [$status, $status, $status, $id]);
    }

    public function deleteCarousel(int $id): bool {
        return (bool)$this->db->execute("DELETE FROM carousels WHERE id = ?", [$id]);
    }

    public function toggleMasterStatus(string $itemType, int $id, string $status): bool {
        if ($itemType === 'carousel') {
            return $this->toggleCarouselStatus($id, $status);
        } elseif ($itemType === 'banner_carousel') {
            return $this->toggleBannerCarouselStatus($id, $status);
        } else {
            return $this->toggleBannerStatus($id, $status);
        }
    }

    public function deleteMasterItem(string $itemType, int $id): bool {
        if ($itemType === 'carousel') {
            return $this->deleteCarousel($id);
        } elseif ($itemType === 'banner_carousel') {
            return $this->deleteBannerCarousel($id);
        } else {
            return $this->deleteBanner($id);
        }
    }
}
?>
