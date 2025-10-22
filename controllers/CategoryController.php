<?php
require_once __DIR__ . '/../config/database.php';

class CategoryController {
    private $conn;
    private $table_name = "categories";
    private $upload_dir = "uploads/categories/";

    public function __construct($db) {
        $this->conn = $db;
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    // Get all categories
    public function getAllCategories() {
        $query = "SELECT c.*, p.name as parent_name 
                  FROM " . $this->table_name . " c
                  LEFT JOIN " . $this->table_name . " p ON c.parent_id = p.id
                  ORDER BY c.parent_id IS NULL DESC, c.display_order ASC, c.name ASC";
        $result = $this->conn->query($query);
        return $result;
    }

    // Get category by ID
    public function getCategoryById($id) {
        $query = "SELECT c.*, p.name as parent_name 
                  FROM " . $this->table_name . " c
                  LEFT JOIN " . $this->table_name . " p ON c.parent_id = p.id
                  WHERE c.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get main categories (no parent)
    public function getMainCategories() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE parent_id IS NULL AND status = 'active'
                  ORDER BY display_order ASC, name ASC";
        $result = $this->conn->query($query);
        return $result;
    }

    // Get subcategories by parent ID
    public function getSubcategories($parent_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE parent_id = ? AND status = 'active'
                  ORDER BY display_order ASC, name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Create new category
    public function createCategory($data, $files = []) {
        // Sanitize input data
        $name = mysqli_real_escape_string($this->conn, $data['name']);
        $description = mysqli_real_escape_string($this->conn, $data['description']);
        $parent_id = intval($data['parent_id'] ?? 0);
        $status = mysqli_real_escape_string($this->conn, $data['status']);
        $display_order = intval($data['display_order'] ?? 0);
        $is_featured = isset($data['is_featured']) ? 1 : 0;

        // Validate parent_id
        if ($parent_id != 0 && !$this->isValidParentId($parent_id)) {
            return ["success" => false, "message" => "Invalid parent category"];
        }

        // Handle image upload
        $image = $this->handleFileUpload($files['image'] ?? null, 'image');
        if ($image === false) {
            return ["success" => false, "message" => "Invalid image file"];
        }

        // Handle icon upload
        $icon = $this->handleFileUpload($files['icon'] ?? null, 'icon');
        if ($icon === false) {
            return ["success" => false, "message" => "Invalid icon file"];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=?, slug=?, description=?, parent_id=?, 
                      image=?, icon=?, status=?, display_order=?";

        $stmt = $this->conn->prepare($query);
        
        $slug = $this->generateSlug($name);

        $stmt->bind_param("sssisssi", 
            $name, $slug, $description, $parent_id,
            $image, $icon, $status, $display_order
        );

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Category created successfully", "id" => $stmt->insert_id];
        } else {
            return ["success" => false, "message" => "Error creating category: " . $stmt->error];
        }
    }

    // Update category
    public function updateCategory($id, $data, $files = []) {
        // Get current category
        $current = $this->getCategoryById($id);
        if ($current->num_rows == 0) {
            return ["success" => false, "message" => "Category not found"];
        }
        $current_data = $current->fetch_assoc();

        // Sanitize input data
        $name = mysqli_real_escape_string($this->conn, $data['name']);
        $description = mysqli_real_escape_string($this->conn, $data['description']);
        $parent_id = intval($data['parent_id'] ?? 0);
        $status = mysqli_real_escape_string($this->conn, $data['status']);
        $display_order = intval($data['display_order'] ?? 0);
        $is_featured = isset($data['is_featured']) ? 1 : 0;

        // Validate parent_id
        if ($parent_id != 0 && !$this->isValidParentId($parent_id)) {
            return ["success" => false, "message" => "Invalid parent category"];
        }

        // Generate slug if name changed
        $slug = $current_data['slug'];
        if ($name != $current_data['name']) {
            $slug = $this->generateSlug($name);
        }

        // Handle file uploads
        $image = $this->handleFileUpload($files['image'] ?? null, 'image');
        if ($image === false) {
            return ["success" => false, "message" => "Invalid image file"];
        }

        $icon = $this->handleFileUpload($files['icon'] ?? null, 'icon');
        if ($icon === false) {
            return ["success" => false, "message" => "Invalid icon file"];
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET name=?, slug=?, description=?, parent_id=?, 
                      image=?, icon=?, status=?, display_order=?
                  WHERE id=?";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bind_param("sssisssii", 
            $name, $slug, $description, $parent_id,
            $image, $icon, $status, $display_order, $id
        );

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Category updated successfully"];
        } else {
            return ["success" => false, "message" => "Error updating category: " . $stmt->error];
        }
    }

    // Delete category
    public function deleteCategory($id) {
        // Check if category has subcategories
        $subcategories = $this->getSubcategories($id);
        if ($subcategories->num_rows > 0) {
            return ["success" => false, "message" => "Cannot delete category with subcategories"];
        }

        // Get category to delete files
        $category = $this->getCategoryById($id);
        if ($category->num_rows > 0) {
            $cat_data = $category->fetch_assoc();
            // Delete image files
            if (!empty($cat_data['image']) && file_exists($cat_data['image'])) {
                unlink($cat_data['image']);
            }
            if (!empty($cat_data['icon']) && file_exists($cat_data['icon'])) {
                unlink($cat_data['icon']);
            }
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Category deleted successfully"];
        } else {
            return ["success" => false, "message" => "Error deleting category: " . $stmt->error];
        }
    }

    // Helper methods
    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Check if slug exists
        $counter = 1;
        $original_slug = $slug;
        
        while ($this->slugExists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists($slug) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE slug = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    private function handleFileUpload($file, $type) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null; // No file uploaded or error occurred
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false; // Invalid file type
        }

        $maxFileSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxFileSize) {
            return false; // File too large
        }

        $uploadDir = "../uploads/categories/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid() . "_" . basename($file['name']);
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        } else {
            return false; // Error moving file
        }
    }

    // Get category tree for dropdown
    public function getCategoryTree($exclude_id = null) {
        $categories = $this->getAllCategories();
        $tree = [];
        
        while ($cat = $categories->fetch_assoc()) {
            if ($exclude_id && $cat['id'] == $exclude_id) {
                continue;
            }
            $tree[] = $cat;
        }
        
        return $this->buildTree($tree);
    }

    private function buildTree($categories, $parent_id = null, $level = 0) {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parent_id) {
                $category['level'] = $level;
                $category['children'] = $this->buildTree($categories, $category['id'], $level + 1);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    private function isValidParentId($parent_id) {
        $sql = "SELECT COUNT(*) FROM categories WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_row()[0];
        return $count > 0;
    }
}
?>