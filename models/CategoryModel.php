<?php
class Category {
    private $conn;
    private $table = "categories";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllWithHierarchy() {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE status = 'active'
                 ORDER BY 
                    CASE 
                        WHEN display_order > 0 THEN display_order 
                        WHEN desktop_menu_order > 0 THEN desktop_menu_order 
                        ELSE 999999 
                    END ASC, 
                    display_order ASC, 
                    name ASC";
        
        $result = $this->conn->query($query);
        
        if (!$result) {
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $this->buildTree($categories);
    }

    private function buildTree(array $categories, $parentId = 0) {
        $branch = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        
        usort($branch, function($a, $b) {
            $ordA = isset($a['display_order']) && (int)$a['display_order'] > 0 ? (int)$a['display_order'] : (isset($a['desktop_menu_order']) && (int)$a['desktop_menu_order'] > 0 ? (int)$a['desktop_menu_order'] : 999999);
            $ordB = isset($b['display_order']) && (int)$b['display_order'] > 0 ? (int)$b['display_order'] : (isset($b['desktop_menu_order']) && (int)$b['desktop_menu_order'] > 0 ? (int)$b['desktop_menu_order'] : 999999);
            if ($ordA !== $ordB) return $ordA <=> $ordB;
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        return $branch;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create($data) {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Calculate level based on parent
        $level = 0;
        if (!empty($data['parent_id'])) {
            $parent = $this->getById($data['parent_id']);
            $level = $parent ? $parent['level'] + 1 : 0;
        }

        $query = "INSERT INTO " . $this->table . " 
                 (name, slug, description, parent_id, image, icon, status, display_order, is_featured, level) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "sssisssiii",
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['parent_id'],
            $data['image'],
            $data['icon'],
            $data['status'],
            $data['display_order'],
            $data['is_featured'],
            $level
        );
        
        return $stmt->execute();
    }

    public function getLastInsertId() {
        return $this->conn->insert_id;
    }

    public function update($id, $data) {
        // Recalculate level if parent changed
        if (isset($data['parent_id'])) {
            $level = 0;
            if (!empty($data['parent_id'])) {
                $parent = $this->getById($data['parent_id']);
                $level = $parent ? $parent['level'] + 1 : 0;
            }
            $data['level'] = $level;
        }

        $setClause = [];
        $types = "";
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            
            if (is_int($value)) {
                $types .= "i";
            } else {
                $types .= "s";
            }
            
            $values[] = $value;
        }
        
        $values[] = $id;
        $types .= "i";
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters dynamically
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public function delete($id) {
        // Check if category has subcategories
        $subcategories = $this->getSubcategories($id);
        if (!empty($subcategories)) {
            throw new Exception("Cannot delete category with subcategories. Please delete subcategories first.");
        }

        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getParentCategories() {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE parent_id = 0 AND status = 'active' 
                 ORDER BY display_order ASC, name ASC";
        
        $result = $this->conn->query($query);
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }

    public function getSubcategories($parent_id) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE parent_id = ? AND status = 'active' 
                 ORDER BY display_order ASC, name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }

    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists($slug) {
        $query = "SELECT id FROM " . $this->table . " WHERE slug = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function getAllCategoriesFlat() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY display_order ASC, name ASC";
        $result = $this->conn->query($query);
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
}
?>