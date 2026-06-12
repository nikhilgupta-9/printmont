<?php
require_once(__DIR__ . '/../config/database.php');

class BlogCategoryModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection(); // mysqli connection
    }

    // Generate slug
    public function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists($slug, $excludeId = null) {
        $slug = $this->conn->real_escape_string($slug);

        $query = "SELECT id FROM blog_categories WHERE slug = '$slug'";
        if ($excludeId) {
            $excludeId = intval($excludeId);
            $query .= " AND id != $excludeId";
        }

        $result = $this->conn->query($query);
        return ($result->num_rows > 0);
    }

    // Get all categories
    public function getAllCategories() {
        $query = "SELECT * FROM blog_categories ORDER BY name ASC";
        $result = $this->conn->query($query);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // Get active categories
    public function getActiveCategories() {
        $query = "SELECT * FROM blog_categories WHERE status = 'active' ORDER BY name ASC";
        $result = $this->conn->query($query);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // Get category by ID
    public function getCategoryById($id) {
        $id = intval($id);
        $query = "SELECT * FROM blog_categories WHERE id = $id";

        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    // Create category
    public function createCategory($data) {
        $name        = $this->conn->real_escape_string($data['name']);
        $description = $this->conn->real_escape_string($data['description']);
        $status      = $this->conn->real_escape_string($data['status']);

        if (empty($data['slug'])) {
            $slug = $this->generateSlug($data['name']);
        } else {
            $slug = $this->conn->real_escape_string($data['slug']);
        }

        $query = "
            INSERT INTO blog_categories (name, slug, description, status)
            VALUES ('$name', '$slug', '$description', '$status')
        ";

        return $this->conn->query($query);
    }

    // Update category
    public function updateCategory($id, $data) {
        $id = intval($id);

        $name        = $this->conn->real_escape_string($data['name']);
        $description = $this->conn->real_escape_string($data['description']);
        $status      = $this->conn->real_escape_string($data['status']);

        if (empty($data['slug'])) {
            $slug = $this->generateSlug($data['name']);
        } else {
            $slug = $this->conn->real_escape_string($data['slug']);
        }

        $query = "
            UPDATE blog_categories SET 
                name = '$name',
                slug = '$slug',
                description = '$description',
                status = '$status',
                updated_at = NOW()
            WHERE id = $id
        ";

        return $this->conn->query($query);
    }

    // Delete category
    public function deleteCategory($id) {
        $id = intval($id);

        // Check if posts exist
        $checkQuery = "SELECT COUNT(*) AS post_count FROM blog_posts WHERE category_id = $id";
        $checkResult = $this->conn->query($checkQuery);
        $data = $checkResult->fetch_assoc();

        if ($data['post_count'] > 0) {
            throw new Exception("Cannot delete category. There are posts associated with this category.");
        }

        $query = "DELETE FROM blog_categories WHERE id = $id";
        return $this->conn->query($query);
    }

    // Update category status
    public function updateStatus($id, $status) {
        $id = intval($id);
        $status = $this->conn->real_escape_string($status);

        $query = "
            UPDATE blog_categories SET 
                status = '$status',
                updated_at = NOW() 
            WHERE id = $id
        ";

        return $this->conn->query($query);
    }
}
?>
