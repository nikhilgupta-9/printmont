<?php
/**
 * Carousel Model  (table: carousels)
 * Follows the same mysqli style as models/CategoryModel.php
 */
class Carousel {
    private $conn;
    private $table = "carousels";

    // Every writable column on the `carousels` table (DOCX §8 full spec).
    private $cols = [
        'title', 'slug',
        'desktop_other_pages', 'desktop_other_category_id', 'desktop_other_banner_id',
        'desktop_home_category_id', 'desktop_home_design', 'desktop_bg_color', 'desktop_bg_image',
        'desktop_sort_order', 'desktop_status',
        'mobile_other_pages', 'mobile_category_id', 'mobile_banner_id', 'mobile_home_show',
        'mobile_home_category_id', 'mobile_home_design', 'mobile_other_design', 'mobile_bg_color',
        'mobile_bg_image', 'mobile_sort_order', 'mobile_status',
        'meta_title', 'meta_keywords', 'meta_description', 'status'
    ];

    // Columns cast to INT.
    private $intCols = [
        'desktop_other_category_id', 'desktop_other_banner_id', 'desktop_home_category_id',
        'desktop_sort_order', 'mobile_category_id', 'mobile_banner_id', 'mobile_home_category_id',
        'mobile_sort_order'
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($orderBy = 'desktop_sort_order', $dir = 'ASC') {
        $orderBy = preg_replace('/[^a-z_]/', '', $orderBy);
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $result = $this->conn->query("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$dir}, id DESC");
        if (!$result) return [];
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        [$setCols, $vals] = $this->buildAssignments($data);
        if (empty($setCols)) return false;
        $sql = "INSERT INTO {$this->table} (" . implode(',', $setCols) . ", created_at) VALUES (" . implode(',', $vals) . ", NOW())";
        return $this->conn->query($sql) ? $this->conn->insert_id : false;
    }

    public function update($id, $data) {
        [$setCols, $vals] = $this->buildAssignments($data);
        if (empty($setCols)) return false;
        $assignments = [];
        foreach ($setCols as $i => $col) {
            $assignments[] = "{$col} = {$vals[$i]}";
        }
        $id = (int)$id;
        $sql = "UPDATE {$this->table} SET " . implode(',', $assignments) . " WHERE id = {$id}";
        return $this->conn->query($sql);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Whitelist + escape the incoming data into parallel columns/values arrays.
    private function buildAssignments($data) {
        $e = fn($v) => $this->conn->real_escape_string($v ?? '');
        $setCols = [];
        $vals = [];
        foreach ($this->cols as $col) {
            if (!array_key_exists($col, $data)) continue;
            $setCols[] = $col;
            $vals[] = in_array($col, $this->intCols, true)
                ? (int)$data[$col]
                : "'" . $e($data[$col]) . "'";
        }
        return [$setCols, $vals];
    }
}
