<?php

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/CategoryModel.php');

class CategoryController
{
    private $db;
    private $category;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->category = new Category($this->db);
    }

    public function getAllCategories()
    {
        return $this->category->getAllWithHierarchy();
    }

    public function getById($id)
    {
        return $this->category->getById($id);
    }

    // public function createCategory($data)
    // {
    //     return $this->category->create($data);
    // }

    public function updateCategory($id, $data)
    {
        return $this->category->update($id, $data);
    }

    public function deleteCategory($id)
    {
        return $this->category->delete($id);
    }

    // public function getParentCategories()
    // {
    //     return $this->category->getParentCategories();
    // }

    // public function getSubcategories($parent_id)
    // {
    //     return $this->category->getSubcategories($parent_id);
    // }


    public function getAllCategoriesAPI()
    {
        try {
            $categories = $this->category->getAllWithHierarchy();
            return $categories; // Only return data, DO NOT echo response
        } catch (Exception $e) {
            return []; // or handle safely
        }
    }


    // Add these helper methods for consistent responses
    private function sendSuccessResponse($data = [], $message = 'Success')
    {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    private function sendErrorResponse($error = 'An error occurred')
    {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
        exit;
    }

    // Get Main Categories (Level 1)
    public function getMainCategories()
    {
        $sql = "SELECT * FROM categories WHERE level = 1 AND status = 'active' ORDER BY display_order, name";
        $result = $this->db->query($sql);

        $categories = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    // Get Sub Categories (Level 2)
    public function getSubCategories($parent_id = null)
    {
        $sql = "SELECT * FROM categories WHERE level = 2 AND status = 'active'";

        if ($parent_id) {
            $sql .= " AND parent_id = " . (int) $parent_id;
        }

        $sql .= " ORDER BY display_order, name";

        $result = $this->db->query($sql);

        $categories = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    // Get Sub Sub Categories (Level 3)
    // Get Sub Sub Categories by parent_id (Level 3)
    public function getSubSubCategories($parent_id = null)
    {
        if ($parent_id) {
            $parent_id = (int) $parent_id;
            $sql = "SELECT id, name, slug FROM categories WHERE parent_id = $parent_id AND level = 3 AND status = 'active' ORDER BY display_order, name";
        } else {
            $sql = "SELECT id, name, slug FROM categories WHERE level = 3 AND status = 'active' ORDER BY display_order, name";
        }

        $result = $this->db->query($sql);

        $categories = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    // Get category by ID
    // public function getCategoryById($id)
    // {
    //     $id = (int) $id;
    //     $sql = "SELECT * FROM categories WHERE id = $id AND status = 'active'";
    //     $result = $this->conn->query($sql);

    //     if ($result && $result->num_rows > 0) {
    //         return $result->fetch_assoc();
    //     }
    //     return null;
    // }

    // Get category name by ID
    public function getCategoryName($id)
    {
        $category = $this->getCategoryById($id);
        return $category ? $category['name'] : '';
    }

    // Get All Parent Categories (for old form compatibility)
    public function getParentCategories()
    {
        $sql = "SELECT * FROM categories WHERE parent_id = 0 OR parent_id IS NULL ORDER BY display_order, name";
        $result = $this->db->query($sql);

        $categories = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    // Create Category
    public function createCategory($data)
    {
        $name = $this->db->real_escape_string($data['name']);
        $slug = $this->db->real_escape_string($data['slug']);
        $description = $this->db->real_escape_string($data['description']);
        $parent_id = (int) $data['parent_id'];
        $image = $this->db->real_escape_string($data['image']);
        $icon = $this->db->real_escape_string($data['icon']);
        $status = $this->db->real_escape_string($data['status']);
        $display_order = (int) $data['display_order'];
        $is_featured = (int) $data['is_featured'];
        $level = (int) $data['level'];

        $sql = "INSERT INTO categories 
                (name, slug, description, parent_id, image, icon, status, display_order, is_featured, level, created_at) 
                VALUES 
                ('$name', '$slug', '$description', $parent_id, '$image', '$icon', '$status', $display_order, $is_featured, $level, NOW())";

        return $this->db->query($sql);
    }

    // Check if category exists
    public function checkCategoryExists($name, $slug, $id = 0)
    {
        $name = $this->db->real_escape_string($name);
        $slug = $this->db->real_escape_string($slug);
        $id = (int) $id;

        $sql = "SELECT id FROM categories WHERE (name = '$name' OR slug = '$slug') AND id != $id";
        $result = $this->db->query($sql);

        return $result && $result->num_rows > 0;
    }


}
?>