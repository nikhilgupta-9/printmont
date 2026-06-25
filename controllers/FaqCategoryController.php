<?php
class CategoryController {
    private $conn;
    private $table_name = "faq_categories";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Ensure table exists
    public function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS `faq_categories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `type` ENUM('general', 'technical', 'billing', 'account', 'product', 'support', 'other') DEFAULT 'general',
            `is_active` TINYINT(1) DEFAULT 1,
            `display_order` INT(11) DEFAULT 0,
            `icon` VARCHAR(100) NULL,
            `color` VARCHAR(7) DEFAULT '#6c757d',
            `faq_count` INT(11) DEFAULT 0,
            `created_by` INT(11) NULL,
            `updated_by` INT(11) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    // Get all categories
    public function getAllCategories($is_active = null) {
        $query = "SELECT * FROM {$this->table_name}";
        
        if ($is_active !== null) {
            $query .= " WHERE is_active = ?";
        }
        
        $query .= " ORDER BY display_order ASC, name ASC";

        if ($is_active !== null) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $is_active);
            $stmt->execute();
            return $stmt->get_result();
        }

        return $this->conn->query($query);
    }

    // Get category by ID
    public function getCategoryById($id) {
        $query = "SELECT * FROM {$this->table_name} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Create category
    public function createCategory($data) {
        try {
            $name = $data['name'] ?? '';
            $description = $data['description'] ?? '';
            $type = $data['type'] ?? 'general';
            $is_active = isset($data['is_active']) ? 1 : 0;
            $display_order = isset($data['display_order']) ? intval($data['display_order']) : 0;
            $icon = $data['icon'] ?? '';
            $color = $data['color'] ?? '#6c757d';
            $created_by = $data['created_by'] ?? 1;
            $updated_by = $data['updated_by'] ?? 1;

            // Validate required fields
            if (empty($name)) {
                return ['success' => false, 'message' => 'Category name is required.'];
            }

            // Check if category name already exists
            $check_query = "SELECT id FROM {$this->table_name} WHERE name = ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => false, 'message' => 'Category name already exists.'];
            }

            $query = "INSERT INTO {$this->table_name} 
                     (name, description, type, is_active, display_order, icon, color, created_by, updated_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssiisssi", $name, $description, $type, $is_active, $display_order, $icon, $color, $created_by, $updated_by);
            
            if ($stmt->execute()) {
                $category_id = $stmt->insert_id;
                return [
                    'success' => true, 
                    'message' => 'Category created successfully!',
                    'category_id' => $category_id
                ];
            } else {
                return ['success' => false, 'message' => 'Error creating category: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Update category
    public function updateCategory($id, $data) {
        try {
            $name = $data['name'] ?? '';
            $description = $data['description'] ?? '';
            $type = $data['type'] ?? 'general';
            $is_active = isset($data['is_active']) ? 1 : 0;
            $display_order = isset($data['display_order']) ? intval($data['display_order']) : 0;
            $icon = $data['icon'] ?? '';
            $color = $data['color'] ?? '#6c757d';
            $updated_by = $data['updated_by'] ?? 1;

            if (empty($name)) {
                return ['success' => false, 'message' => 'Category name is required.'];
            }

            // Check if category name already exists (excluding current category)
            $check_query = "SELECT id FROM {$this->table_name} WHERE name = ? AND id != ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bind_param("si", $name, $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => false, 'message' => 'Category name already exists.'];
            }

            $query = "UPDATE {$this->table_name} 
                     SET name = ?, description = ?, type = ?, is_active = ?, 
                         display_order = ?, icon = ?, color = ?, updated_by = ?, updated_at = NOW() 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssiisssi", $name, $description, $type, $is_active, $display_order, $icon, $color, $updated_by, $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Category updated successfully!'
                ];
            } else {
                return ['success' => false, 'message' => 'Error updating category: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Delete category
    public function deleteCategory($id) {
        try {
            // Check if category has FAQs
            $check_query = "SELECT COUNT(*) as faq_count FROM faqs WHERE category_id = ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['faq_count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete category. It has ' . $row['faq_count'] . ' FAQs associated with it.'];
            }

            $query = "DELETE FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Category deleted successfully!'
                ];
            } else {
                return ['success' => false, 'message' => 'Error deleting category: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Get category statistics
    public function getCategoryStats() {
        $stats = [
            'total_categories' => 0,
            'active_categories' => 0,
            'total_faqs_in_categories' => 0
        ];

        // Total categories
        $query = "SELECT COUNT(*) as total FROM {$this->table_name}";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_categories'] = $row['total'];
        }

        // Active categories
        $query = "SELECT COUNT(*) as active FROM {$this->table_name} WHERE is_active = 1";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['active_categories'] = $row['active'];
        }

        // Total FAQs in categories
        $query = "SELECT SUM(faq_count) as total_faqs FROM {$this->table_name}";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_faqs_in_categories'] = $row['total_faqs'] ?? 0;
        }

        return $stats;
    }

    // Get category types for dropdown
    public function getCategoryTypes() {
        return [
            'general' => 'General',
            'myaccount' => 'My Account',
            'devlivery-information' => 'Delivery information',
            'order-modification-cancellation' => 'Order modification/cancellation',
            'product' => 'Product',
            'designing-my-product' => 'Designing My product',
            'technical' => 'Technical',
            'payments-and-refunds' => 'Payments and Refunds',
            'billing' => 'Billing',
            'account' => 'Account',
            'support' => 'Support',
            'order' => 'Order',
            'other' => 'Other'
        ];
    }

    // Update FAQ count for categories
    public function updateFAQCounts() {
        $query = "UPDATE {$this->table_name} c 
                 LEFT JOIN (
                     SELECT category_id, COUNT(*) as count 
                     FROM faqs 
                     WHERE is_active = 1 
                     GROUP BY category_id
                 ) f ON c.id = f.category_id 
                 SET c.faq_count = COALESCE(f.count, 0)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>