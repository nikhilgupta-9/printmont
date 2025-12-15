<?php
// models/HelpCenterModel.php
require_once(__DIR__ . '/../config/database.php');

class HelpCenterModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Categories CRUD
    public function getAllCategories() {
        $query = "SELECT * FROM help_categories ORDER BY display_order ASC, name ASC";
        $result = $this->db->query($query);
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Database error: " . $this->db->error);
            return [];
        }
    }

    public function getCategoryById($id) {
        $stmt = $this->db->prepare("SELECT * FROM help_categories WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createCategory($data) {
        $stmt = $this->db->prepare("INSERT INTO help_categories (name, description, icon, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("sssii", 
            $data['name'],
            $data['description'],
            $data['icon'],
            $data['display_order'],
            $data['is_active']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updateCategory($id, $data) {
        $stmt = $this->db->prepare("UPDATE help_categories SET name = ?, description = ?, icon = ?, display_order = ?, is_active = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("sssiii", 
            $data['name'],
            $data['description'],
            $data['icon'],
            $data['display_order'],
            $data['is_active'],
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteCategory($id) {
        $stmt = $this->db->prepare("DELETE FROM help_categories WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Articles CRUD
    public function getAllArticles($categoryId = null) {
        if ($categoryId) {
            $query = "SELECT a.*, c.name as category_name 
                     FROM help_articles a 
                     LEFT JOIN help_categories c ON a.category_id = c.id 
                     WHERE a.category_id = ? 
                     ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $query = "SELECT a.*, c.name as category_name 
                     FROM help_articles a 
                     LEFT JOIN help_categories c ON a.category_id = c.id 
                     ORDER BY a.created_at DESC";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function getArticleById($id) {
        $stmt = $this->db->prepare("SELECT a.*, c.name as category_name 
                                   FROM help_articles a 
                                   LEFT JOIN help_categories c ON a.category_id = c.id 
                                   WHERE a.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getArticleBySlug($slug) {
        $stmt = $this->db->prepare("SELECT a.*, c.name as category_name 
                                   FROM help_articles a 
                                   LEFT JOIN help_categories c ON a.category_id = c.id 
                                   WHERE a.slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createArticle($data) {
        $stmt = $this->db->prepare("INSERT INTO help_articles (category_id, title, slug, content, meta_description, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("issssii", 
            $data['category_id'],
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['meta_description'],
            $data['is_featured'],
            $data['is_active']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updateArticle($id, $data) {
        $stmt = $this->db->prepare("UPDATE help_articles SET category_id = ?, title = ?, slug = ?, content = ?, meta_description = ?, is_featured = ?, is_active = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("issssiii", 
            $data['category_id'],
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['meta_description'],
            $data['is_featured'],
            $data['is_active'],
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteArticle($id) {
        $stmt = $this->db->prepare("DELETE FROM help_articles WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // FAQs CRUD
    public function getAllFaqs($categoryId = null) {
        if ($categoryId) {
            $query = "SELECT f.*, c.name as category_name 
                     FROM help_faqs f 
                     LEFT JOIN help_categories c ON f.category_id = c.id 
                     WHERE f.category_id = ? 
                     ORDER BY f.display_order ASC, f.id ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $query = "SELECT f.*, c.name as category_name 
                     FROM help_faqs f 
                     LEFT JOIN help_categories c ON f.category_id = c.id 
                     ORDER BY f.display_order ASC, f.id ASC";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function getFaqById($id) {
        $stmt = $this->db->prepare("SELECT f.*, c.name as category_name 
                                   FROM help_faqs f 
                                   LEFT JOIN help_categories c ON f.category_id = c.id 
                                   WHERE f.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createFaq($data) {
        $stmt = $this->db->prepare("INSERT INTO help_faqs (question, answer, category_id, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("ssiii", 
            $data['question'],
            $data['answer'],
            $data['category_id'],
            $data['display_order'],
            $data['is_active']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updateFaq($id, $data) {
        $stmt = $this->db->prepare("UPDATE help_faqs SET question = ?, answer = ?, category_id = ?, display_order = ?, is_active = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("sssiii", 
            $data['question'],
            $data['answer'],
            $data['category_id'],
            $data['display_order'],
            $data['is_active'],
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteFaq($id) {
        $stmt = $this->db->prepare("DELETE FROM help_faqs WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Statistics
    public function getHelpCenterStats() {
        $stats = [];
        
        // Total categories
        $result = $this->db->query("SELECT COUNT(*) as total FROM help_categories");
        $stats['total_categories'] = $result->fetch_assoc()['total'];
        
        // Active categories
        $result = $this->db->query("SELECT COUNT(*) as active FROM help_categories WHERE is_active = 1");
        $stats['active_categories'] = $result->fetch_assoc()['active'];
        
        // Total articles
        $result = $this->db->query("SELECT COUNT(*) as total FROM help_articles");
        $stats['total_articles'] = $result->fetch_assoc()['total'];
        
        // Featured articles
        $result = $this->db->query("SELECT COUNT(*) as featured FROM help_articles WHERE is_featured = 1");
        $stats['featured_articles'] = $result->fetch_assoc()['featured'];
        
        // Total FAQs
        $result = $this->db->query("SELECT COUNT(*) as total FROM help_faqs");
        $stats['total_faqs'] = $result->fetch_assoc()['total'];
        
        // Total views
        $result = $this->db->query("SELECT SUM(views_count) as total_views FROM help_articles");
        $stats['total_views'] = $result->fetch_assoc()['total_views'] ?? 0;
        
        return $stats;
    }

    // Generate slug from title
    public function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists($slug) {
        $stmt = $this->db->prepare("SELECT id FROM help_articles WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
?>