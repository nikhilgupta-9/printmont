<?php
class Review {
    private $conn;
    private $table = "product_reviews";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all reviews with pagination and filters
    public function getAllReviews($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["1=1"];
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "r.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['rating'])) {
            $where_conditions[] = "r.rating = ?";
            $params[] = $filters['rating'];
            $types .= "i";
        }
        
        if (!empty($filters['product_id'])) {
            $where_conditions[] = "r.product_id = ?";
            $params[] = $filters['product_id'];
            $types .= "i";
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "(r.customer_name LIKE ? OR r.title LIKE ? OR p.name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $types .= "sss";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT r.*, 
                         p.name as product_name,
                         p.sku as product_sku,
                         pi.image_url as product_image
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                  WHERE {$where_clause}
                  ORDER BY r.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total 
                       FROM {$this->table} r
                       LEFT JOIN products p ON r.product_id = p.id
                       WHERE {$where_clause}";
        $count_stmt = $this->conn->prepare($count_query);
        
        if (!empty($params)) {
            $count_params = array_slice($params, 0, count($params) - 2);
            $count_types = substr($types, 0, -2);
            if (!empty($count_params)) {
                $count_stmt->bind_param($count_types, ...$count_params);
            }
        }
        
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()['total'];
        
        return [
            'reviews' => $reviews,
            'total_count' => $total_count,
            'total_pages' => ceil($total_count / $limit),
            'current_page' => $page
        ];
    }

    // Get review by ID
    public function getReviewById($id) {
        $query = "SELECT r.*, 
                         p.name as product_name,
                         p.sku as product_sku,
                         pi.image_url as product_image
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                  WHERE r.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Create review
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                 (product_id, user_id, customer_name, customer_email, rating, title, comment, status, is_verified, occasion, city, images) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $userId = $data['user_id'] ?? null;
        $customerName = $data['customer_name'] ?? ($data['reviewer_name'] ?? 'Anonymous');
        $customerEmail = $data['customer_email'] ?? null;
        $rating = (int)($data['rating'] ?? 5);
        $title = $data['title'] ?? ($data['review_title'] ?? '');
        $comment = $data['comment'] ?? ($data['review_text'] ?? '');
        $status = $data['status'] ?? 'pending';
        $isVerified = isset($data['is_verified']) ? (int)$data['is_verified'] : 1;
        $occasion = $data['occasion'] ?? null;
        $city = $data['city'] ?? null;
        $productId = (int)($data['product_id'] ?? 0);
        
        $images = null;
        if (isset($data['images'])) {
            if (is_array($data['images'])) {
                $images = !empty($data['images']) ? json_encode(array_values($data['images'])) : null;
            } elseif (is_string($data['images']) && !empty($data['images'])) {
                $images = $data['images'];
            }
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iississsisss",
            $productId,
            $userId,
            $customerName,
            $customerEmail,
            $rating,
            $title,
            $comment,
            $status,
            $isVerified,
            $occasion,
            $city,
            $images
        );
        
        return $stmt->execute();
    }

    // Update review
    public function update($id, $data) {
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
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            return $stmt->execute();
        }
        
        return false;
    }

    // Delete review
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Update review status
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    // Get review statistics
    public function getReviewStats() {
        $query = "SELECT 
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_reviews
                  FROM {$this->table}";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    // Get product reviews
    public function getProductReviews($product_id, $status = 'approved') {
        $query = "SELECT r.*, 
                         p.name as product_name
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  WHERE r.product_id = ? AND r.status = ?
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $product_id, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        return $reviews;
    }

    // Get approved reviews for a category or all categories
    public function getCategoryReviews($category_id = null, $limit = 20) {
        $where_conditions = ["r.status = 'approved'"];
        $params = [];
        $types = "";

        if (!empty($category_id)) {
            $where_conditions[] = "(p.category_id = ? OR p.sub_category_id = ?)";
            $params[] = (int)$category_id;
            $params[] = (int)$category_id;
            $types .= "ii";
        }

        $where_clause = implode(" AND ", $where_conditions);

        $query = "SELECT r.*, 
                         p.name as product_name,
                         p.slug as product_slug,
                         p.thumbnail_image as product_image
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  WHERE {$where_clause}
                  ORDER BY r.created_at DESC
                  LIMIT ?";

        $params[] = (int)$limit;
        $types .= "i";

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        return $reviews;
    }
}
?>