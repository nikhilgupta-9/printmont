<?php
require_once(__DIR__ . '/../config/database.php');

class ApiCategoryModel {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = new Database();
    }

    public function getMainCategories(): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE level = 1 AND status = 'active'
             ORDER BY display_order ASC, name ASC"
        );
    }

    public function getSubCategories(?int $parentId = null): array {
        if ($parentId) {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->table}
                 WHERE level = 2 AND status = 'active' AND parent_id = ?
                 ORDER BY display_order ASC, name ASC",
                [$parentId]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE level = 2 AND status = 'active'
             ORDER BY display_order ASC, name ASC"
        );
    }

    public function getSubSubCategories(?int $parentId = null): array {
        if ($parentId) {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->table}
                 WHERE level = 3 AND status = 'active' AND parent_id = ?
                 ORDER BY display_order ASC, name ASC",
                [$parentId]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE level = 3 AND status = 'active'
             ORDER BY display_order ASC, name ASC"
        );
    }

    public function getById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND status = 'active'",
            [$id]
        );
        return $row ?: null;
    }

    public function getBySlug(string $slug): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE slug = ? AND status = 'active'",
            [$slug]
        );
        return $row ?: null;
    }

    // All active categories in ONE query (used to build the full tree in-memory, no N+1)
    public function getAllActiveFlat(): array {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status = 'active'
             ORDER BY display_order ASC, name ASC"
        );
    }
}
?>
