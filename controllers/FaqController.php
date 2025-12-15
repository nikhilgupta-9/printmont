<?php
class FaqController {
    private $conn;
    private $table_name = "faqs";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Ensure table exists with all fields
    public function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer LONGTEXT NOT NULL,
            category_id INT NULL,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            keywords VARCHAR(255),
            view_count INT DEFAULT 0,
            helpful_count INT DEFAULT 0,
            not_helpful_count INT DEFAULT 0,
            created_by INT NULL,
            updated_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES faq_categories(id) ON DELETE SET NULL
        )";

        return $this->conn->query($query);
    }

    // Get all FAQs
    public function getAllFAQs($is_active = null) {
        $query = "SELECT f.*, c.name as category_name 
                  FROM {$this->table_name} f 
                  LEFT JOIN faq_categories c ON f.category_id = c.id";
        
        if ($is_active !== null) {
            $query .= " WHERE f.is_active = ?";
        }
        
        $query .= " ORDER BY f.display_order ASC, f.created_at DESC";

        if ($is_active !== null) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $is_active);
            $stmt->execute();
            return $stmt->get_result();
        }

        return $this->conn->query($query);
    }

    // Get FAQ by ID
    public function getFAQById($id) {
        $query = "SELECT f.*, c.name as category_name 
                  FROM {$this->table_name} f 
                  LEFT JOIN faq_categories c ON f.category_id = c.id 
                  WHERE f.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Create new FAQ
    public function createFAQ($data) {
        try {
            $question = trim($data['question'] ?? '');
            $answer = $data['answer'] ?? ''; // KEEP HTML
            $category_id = !empty($data['category_id']) ? intval($data['category_id']) : null;
            $is_active = isset($data['is_active']) ? 1 : 0;
            $display_order = intval($data['display_order'] ?? 0);
            $keywords = trim($data['keywords'] ?? '');
            $view_count = intval($data['view_count'] ?? 0);
            $helpful_count = intval($data['helpful_count'] ?? 0);
            $not_helpful_count = intval($data['not_helpful_count'] ?? 0);
            $created_by = $data['created_by'] ?? 1;
            $updated_by = $data['updated_by'] ?? 1;

            // Validate required fields
            if (empty($question) || empty($answer)) {
                return ['success' => false, 'message' => 'Question and Answer are required fields.'];
            }

            $query = "INSERT INTO {$this->table_name}
                     (question, answer, category_id, is_active, display_order, keywords, 
                      view_count, helpful_count, not_helpful_count, created_by, updated_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssiisiiiis", 
                $question, 
                $answer, 
                $category_id, 
                $is_active, 
                $display_order, 
                $keywords, 
                $view_count, 
                $helpful_count, 
                $not_helpful_count, 
                $created_by, 
                $updated_by
            );

            if ($stmt->execute()) {
                $faq_id = $stmt->insert_id;
                return [
                    'success' => true, 
                    'message' => 'FAQ created successfully',
                    'faq_id' => $faq_id
                ];
            } else {
                return ['success' => false, 'message' => 'Error creating FAQ: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Update FAQ
    public function updateFAQ($id, $data) {
    try {
        $question = trim($data['question'] ?? '');
        $answer = $data['answer'] ?? ''; // KEEP HTML
        $category_id = !empty($data['category_id']) ? intval($data['category_id']) : null;
        $is_active = isset($data['is_active']) ? 1 : 0;
        $display_order = intval($data['display_order'] ?? 0);
        $keywords = trim($data['keywords'] ?? '');
        $view_count = intval($data['view_count'] ?? 0);
        $helpful_count = intval($data['helpful_count'] ?? 0);
        $not_helpful_count = intval($data['not_helpful_count'] ?? 0);
        $updated_by = $data['updated_by'] ?? 1;

        // Validate required fields
        if (empty($question) || empty($answer)) {
            return ['success' => false, 'message' => 'Question and Answer are required fields.'];
        }

        $query = "UPDATE {$this->table_name}
                 SET question = ?, answer = ?, category_id = ?, is_active = ?, 
                     display_order = ?, keywords = ?, view_count = ?, 
                     helpful_count = ?, not_helpful_count = ?, updated_by = ?, 
                     updated_at = NOW()
                 WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        
        // Count the parameters: 11 parameters total (10 SET values + 1 WHERE condition)
        // Debug: Let's see what we're binding
        $bind_types = "sssiisiiiii"; // 11 characters for 11 parameters
        $bind_params = [
            $question, 
            $answer, 
            $category_id, 
            $is_active, 
            $display_order, 
            $keywords, 
            $view_count, 
            $helpful_count, 
            $not_helpful_count, 
            $updated_by, 
            $id
        ];

        // Debug output (you can remove this after testing)
        error_log("Binding types: " . $bind_types);
        error_log("Number of parameters: " . count($bind_params));
        
        $stmt->bind_param($bind_types, ...$bind_params);

        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'FAQ updated successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Error updating FAQ: ' . $stmt->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

    // Delete FAQ
    public function deleteFAQ($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'FAQ deleted successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Error deleting FAQ: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Get FAQ statistics
    public function getFAQStats() {
        $stats = [
            'total_faqs' => 0,
            'active_faqs' => 0,
            'total_views' => 0,
            'helpful_votes' => 0,
            'not_helpful_votes' => 0
        ];

        $query = "SELECT 
                     COUNT(*) as total_faqs,
                     SUM(is_active) as active_faqs,
                     SUM(view_count) as total_views,
                     SUM(helpful_count) as helpful_votes,
                     SUM(not_helpful_count) as not_helpful_votes
                  FROM {$this->table_name}";

        $result = $this->conn->query($query);

        if ($result && $row = $result->fetch_assoc()) {
            $stats = [
                'total_faqs' => $row['total_faqs'] ?? 0,
                'active_faqs' => $row['active_faqs'] ?? 0,
                'total_views' => $row['total_views'] ?? 0,
                'helpful_votes' => $row['helpful_votes'] ?? 0,
                'not_helpful_votes' => $row['not_helpful_votes'] ?? 0
            ];
        }

        return $stats;
    }

    // Search FAQs
    public function searchFAQs($searchTerm, $category_id = null, $is_active = true) {
        $query = "SELECT f.*, c.name as category_name 
                  FROM {$this->table_name} f 
                  LEFT JOIN faq_categories c ON f.category_id = c.id 
                  WHERE (f.question LIKE ? OR f.answer LIKE ? OR f.keywords LIKE ?)";
        
        $params = ["sss"];
        $searchPattern = "%" . $searchTerm . "%";
        $bind_params = [$searchPattern, $searchPattern, $searchPattern];

        if ($is_active) {
            $query .= " AND f.is_active = 1";
        }

        if ($category_id !== null) {
            $query .= " AND f.category_id = ?";
            $params[0] .= "i";
            $bind_params[] = $category_id;
        }
        
        $query .= " ORDER BY f.display_order ASC, f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        // Dynamic binding based on parameters
        $stmt->bind_param(...array_merge($params, $bind_params));
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get FAQs by category
    public function getFAQsByCategory($category_id, $is_active = true) {
        $query = "SELECT f.*, c.name as category_name 
                  FROM {$this->table_name} f 
                  LEFT JOIN faq_categories c ON f.category_id = c.id 
                  WHERE f.category_id = ?";
        
        if ($is_active) {
            $query .= " AND f.is_active = 1";
        }
        
        $query .= " ORDER BY f.display_order ASC, f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Increment view count
    public function incrementViewCount($id) {
        $query = "UPDATE {$this->table_name} 
                  SET view_count = view_count + 1 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Update helpful count
    public function updateHelpfulCount($id, $is_helpful = true) {
        $field = $is_helpful ? 'helpful_count' : 'not_helpful_count';
        $query = "UPDATE {$this->table_name} 
                  SET {$field} = {$field} + 1 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Get popular FAQs (most viewed)
    public function getPopularFAQs($limit = 5, $is_active = true) {
        $query = "SELECT f.*, c.name as category_name 
                  FROM {$this->table_name} f 
                  LEFT JOIN faq_categories c ON f.category_id = c.id";
        
        if ($is_active) {
            $query .= " WHERE f.is_active = 1";
        }
        
        $query .= " ORDER BY f.view_count DESC, f.display_order ASC 
                   LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get recent FAQs
    public function getRecentFAQs($limit = 5, $is_active = true) {
        $query = "SELECT f.*, c.name as category_name 
                  FROM {$this->table_name} f 
                  LEFT JOIN faq_categories c ON f.category_id = c.id";
        
        if ($is_active) {
            $query .= " WHERE f.is_active = 1";
        }
        
        $query .= " ORDER BY f.created_at DESC, f.display_order ASC 
                   LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get FAQs by category
// public function getFAQsByCategory($category_id, $is_active = true) {
//     $query = "SELECT f.*, c.name as category_name 
//               FROM {$this->table_name} f 
//               LEFT JOIN faq_categories c ON f.category_id = c.id 
//               WHERE f.category_id = ?";
    
//     if ($is_active) {
//         $query .= " AND f.is_active = 1";
//     }
    
//     $query .= " ORDER BY f.display_order ASC, f.created_at DESC";
    
//     $stmt = $this->conn->prepare($query);
//     $stmt->bind_param("i", $category_id);
//     $stmt->execute();
//     return $stmt->get_result();
// }
}
?>