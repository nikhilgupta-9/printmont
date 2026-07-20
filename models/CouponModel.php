<?php
require_once(__DIR__ . '/../config/database.php');

class CouponModel {
    private $db;
    private $table = 'coupons';

    public function __construct() {
        $this->db = new Database();
    }

    // ----------------------------------------------------------------
    // Reads
    // ----------------------------------------------------------------

    /**
     * Scope fragment shared by the list pages.
     *   'expired' -> past end date, or explicitly flagged expired
     *   'active'  -> still running and flagged active
     */
    private function scopeWhere(string $scope): string {
        switch ($scope) {
            case 'expired':
                return "(c.end_date IS NOT NULL AND c.end_date < CURDATE()) OR c.status = 'expired'";
            case 'active':
                return "(c.end_date IS NULL OR c.end_date >= CURDATE()) AND c.status = 'active'";
            default:
                return "1=1";
        }
    }

    public function getCouponById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function getCouponsWithPagination(int $page = 1, int $perPage = 15, string $search = '', string $scope = 'all'): array {
        $offset = ($page - 1) * $perPage;
        $where  = $this->scopeWhere($scope);
        $params = [];

        if ($search !== '') {
            $where  .= " AND c.coupon_code LIKE ?";
            $params[] = "%{$search}%";
        }

        return $this->db->fetchAll(
            "SELECT c.*,
                    mc.name  AS category_name,
                    sc.name  AS sub_category_name,
                    ssc.name AS sub_sub_category_name
             FROM {$this->table} c
             LEFT JOIN categories mc  ON mc.id  = c.category_id
             LEFT JOIN categories sc  ON sc.id  = c.sub_category_id
             LEFT JOIN categories ssc ON ssc.id = c.sub_sub_category_id
             WHERE {$where}
             ORDER BY c.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
    }

    public function getCouponsCount(string $search = '', string $scope = 'all'): int {
        $where  = $this->scopeWhere($scope);
        $params = [];

        if ($search !== '') {
            $where  .= " AND c.coupon_code LIKE ?";
            $params[] = "%{$search}%";
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM {$this->table} c WHERE {$where}",
            $params
        );
        return (int)($row['total'] ?? 0);
    }

    public function getCouponStats(): array {
        $row = $this->db->fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active'
                          AND (end_date IS NULL OR end_date >= CURDATE()) THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'expired'
                          OR (end_date IS NOT NULL AND end_date < CURDATE()) THEN 1 ELSE 0 END) AS expired,
                SUM(used_count) AS redeemed
             FROM {$this->table}"
        );

        return [
            'total'    => (int)($row['total'] ?? 0),
            'active'   => (int)($row['active'] ?? 0),
            'expired'  => (int)($row['expired'] ?? 0),
            'redeemed' => (int)($row['redeemed'] ?? 0),
        ];
    }

    /**
     * Every category in one query. The add/edit form filters these
     * client-side to drive the three cascading selects.
     */
    public function getAllCategories(): array {
        return $this->db->fetchAll(
            "SELECT id, name, parent_id, level
             FROM categories
             WHERE status = 'active'
             ORDER BY level ASC, name ASC"
        );
    }

    public function getAllCarousels(): array {
        return $this->db->fetchAll(
            "SELECT id, title, slug FROM carousels ORDER BY title ASC"
        );
    }

    /** Uniqueness guard so a duplicate code surfaces as a message, not a raw SQL error. */
    public function codeExists(string $code, ?int $ignoreId = null): bool {
        if ($ignoreId) {
            $row = $this->db->fetch(
                "SELECT id FROM {$this->table} WHERE coupon_code = ? AND id != ?",
                [$code, $ignoreId]
            );
        } else {
            $row = $this->db->fetch(
                "SELECT id FROM {$this->table} WHERE coupon_code = ?",
                [$code]
            );
        }
        return (bool)$row;
    }

    // ----------------------------------------------------------------
    // Writes
    // ----------------------------------------------------------------

    public function createCoupon(array $d): int {
        return $this->db->insert(
            "INSERT INTO {$this->table}
                (coupon_code, category_id, sub_category_id, sub_sub_category_id, carousel_type,
                 discount_format, discount_value, min_order_value, quantity,
                 start_date, end_date, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $d['coupon_code'], $d['category_id'], $d['sub_category_id'], $d['sub_sub_category_id'],
                $d['carousel_type'], $d['discount_format'], $d['discount_value'], $d['min_order_value'],
                $d['quantity'], $d['start_date'], $d['end_date'], $d['status'],
            ]
        );
    }

    public function updateCoupon(int $id, array $d): bool {
        return $this->db->execute(
            "UPDATE {$this->table} SET
                coupon_code = ?, category_id = ?, sub_category_id = ?, sub_sub_category_id = ?,
                carousel_type = ?, discount_format = ?, discount_value = ?, min_order_value = ?,
                quantity = ?, start_date = ?, end_date = ?, status = ?
             WHERE id = ?",
            [
                $d['coupon_code'], $d['category_id'], $d['sub_category_id'], $d['sub_sub_category_id'],
                $d['carousel_type'], $d['discount_format'], $d['discount_value'], $d['min_order_value'],
                $d['quantity'], $d['start_date'], $d['end_date'], $d['status'], $id,
            ]
        );
    }

    public function deleteCoupon(int $id): bool {
        return $this->db->execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
}
