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

    public function getBannersWithPagination(int $page = 1, int $perPage = 20, string $search = ''): array {
        $offset = ($page - 1) * $perPage;
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where   = "(b.title LIKE ? OR s.label LIKE ? OR s.page LIKE ?)";
            $like    = "%{$search}%";
            $params  = [$like, $like, $like];
        }

        return $this->db->fetchAll(
            "SELECT b.*, s.label AS section_label, s.page AS section_page, s.section_key
             FROM {$this->table} b
             LEFT JOIN banner_sections s ON s.id = b.section_id
             WHERE {$where}
             ORDER BY s.page ASC, s.display_order ASC, b.display_order ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
    }

    public function getBannersCount(string $search = ''): int {
        $where  = '1=1';
        $params = [];

        if ($search !== '') {
            $where  = "(b.title LIKE ? OR s.label LIKE ? OR s.page LIKE ?)";
            $like   = "%{$search}%";
            $params = [$like, $like, $like];
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
        return $row ?: [];
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

    public function updateSectionColumns(int $sectionId, int $columns): bool {
        return (bool)$this->db->execute(
            "UPDATE banner_sections SET columns_per_row = ?, updated_at = NOW() WHERE id = ?",
            [$columns, $sectionId]
        );
    }

    // ----------------------------------------------------------------
    // Writes
    // ----------------------------------------------------------------

    public function createBanner(array $data): int|false {
        return $this->db->insert(
            "INSERT INTO {$this->table}
             (title, description, image_url_desktop, image_url_mobile,
              target_url, section_id, display_order, status, start_date, end_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['title'],
                $data['description'] ?? '',
                $data['image_url_desktop'] ?? '',
                $data['image_url_mobile']  ?? '',
                $data['target_url']        ?? '',
                $data['section_id']        ?? null,
                $data['display_order']     ?? 0,
                $data['status']            ?? 'active',
                $data['start_date']        ?: null,
                $data['end_date']          ?: null,
            ]
        );
    }

    public function updateBanner(int $id, array $data): bool {
        return (bool)$this->db->execute(
            "UPDATE {$this->table} SET
             title = ?, description = ?, image_url_desktop = ?, image_url_mobile = ?,
             target_url = ?, section_id = ?, display_order = ?, status = ?,
             start_date = ?, end_date = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['title'],
                $data['description']    ?? '',
                $data['image_url_desktop'] ?? '',
                $data['image_url_mobile']  ?? '',
                $data['target_url']     ?? '',
                $data['section_id']     ?? null,
                $data['display_order']  ?? 0,
                $data['status']         ?? 'active',
                $data['start_date']     ?: null,
                $data['end_date']       ?: null,
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
}
?>
