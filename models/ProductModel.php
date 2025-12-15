<?php
require_once(__DIR__ . '/../config/database.php');

class ProductModel {
    private $db;
    private $table = "products";

    public function __construct() {
        $this->db = new Database();
    }

    // Basic CRUD Operations
    public function getAllProducts() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.created_at DESC";
        $products = $this->db->fetchAll($query);
        
        // Get images for each product
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    public function getProductById($id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ?";
        $product = $this->db->fetch($query, [$id]);
        
        if ($product) {
            $product['images'] = $this->getProductImages($id);
        }
        
        return $product;
    }

    public function createProduct($data, $images = []) {
        $this->db->beginTransaction();
        
        try {
            // Insert product
            $query = "INSERT INTO products (name, description, category_id, brand, price, discount_price, stock_quantity, sku, status, featured, top_selection, our_bestseller, top_rated, top_deal_by_categories) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['name'],
                $data['description'] ?? '',
                $data['category_id'],
                $data['brand'] ?? '',
                $data['price'],
                $data['discount_price'] ?? null,
                $data['stock_quantity'],
                $data['sku'],
                $data['status'] ?? 'active',
                $data['featured'] ?? 0,
                $data['top_selection'] ?? 0,
                $data['our_bestseller'] ?? 0,
                $data['top_rated'] ?? 0,
                $data['top_deal_by_categories'] ?? 0
            ];
            
            $productId = $this->db->insert($query, $params);
            
            // Insert images if provided
            if (!empty($images)) {
                $this->addProductImages($productId, $images);
            }
            
            $this->db->commit();
            return $productId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateProduct($id, $data, $images = []) {
        $this->db->beginTransaction();
        
        try {
            // Update product
            $query = "UPDATE products SET 
                      name = ?, description = ?, category_id = ?, brand = ?, 
                      price = ?, discount_price = ?, stock_quantity = ?, sku = ?, 
                      status = ?, featured = ?, top_selection = ?, our_bestseller = ?,
                      top_rated = ?, top_deal_by_categories = ?, updated_at = NOW() 
                      WHERE id = ?";
            
            $params = [
                $data['name'],
                $data['description'] ?? '',
                $data['category_id'],
                $data['brand'] ?? '',
                $data['price'],
                $data['discount_price'] ?? null,
                $data['stock_quantity'],
                $data['sku'],
                $data['status'] ?? 'active',
                $data['featured'] ?? 0,
                $data['top_selection'] ?? 0,
                $data['our_bestseller'] ?? 0,
                $data['top_rated'] ?? 0,
                $data['top_deal_by_categories'] ?? 0,
                $id
            ];
            
            $success = $this->db->execute($query, $params);
            
            // Update images if new ones are provided
            if (!empty($images)) {
                // Delete old images
                $this->db->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);
                // Add new images
                $this->addProductImages($id, $images);
            }
            
            $this->db->commit();
            return $success;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function deleteProduct($id) {
        $this->db->beginTransaction();
        
        try {
            // Delete product images first
            $this->db->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);
            
            // Delete product
            $result = $this->db->execute("DELETE FROM products WHERE id = ?", [$id]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    // Image Management
    private function getProductImages($productId) {
        $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC";
        return $this->db->fetchAll($query, [$productId]);
    }

    private function addProductImages($productId, $images) {
        foreach ($images as $image) {
            $query = "INSERT INTO product_images (product_id, image_url, is_primary, display_order) 
                      VALUES (?, ?, ?, ?)";
            
            $this->db->execute($query, [
                $productId,
                $image['image_url'],
                $image['is_primary'] ? 1 : 0,
                $image['display_order']
            ]);
        }
    }

    // Status-based Product Retrieval 
    public function getDeactiveProducts() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'inactive'
                  ORDER BY p.created_at DESC";
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    public function getActiveProducts() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active'
                  ORDER BY p.created_at DESC";
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    public function getFeaturedProducts() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.featured = 1 AND p.status = 'active'
                  ORDER BY p.created_at DESC";
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    // Bestseller Methods
    public function toggleBestseller($productId) {
        $query = "UPDATE products SET our_bestseller = NOT our_bestseller, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setBestsellerStatus($productId, $status) {
        $query = "UPDATE products SET our_bestseller = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getBestsellerProducts() {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.our_bestseller = 1 AND p.status = 'active'
                 ORDER BY p.created_at DESC";
        
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    // Top Selection Methods
    public function toggleTopSelection($productId) {
        $query = "UPDATE products SET top_selection = NOT top_selection, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setTopSelectionStatus($productId, $status) {
        $query = "UPDATE products SET top_selection = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getTopSelectionProducts() {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.top_selection = 1 AND p.status = 'active'
                 ORDER BY p.created_at DESC";
        
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    // Top Rated Methods
    public function toggleTopRated($productId) {
        $query = "UPDATE products SET top_rated = NOT top_rated, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setTopRatedStatus($productId, $status) {
        $query = "UPDATE products SET top_rated = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getTopRatedProducts() {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.top_rated = 1 AND p.status = 'active'
                 ORDER BY p.created_at DESC";
        
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    // Top Deal by Categories Methods
    public function toggleTopDealByCategories($productId) {
        $query = "UPDATE products SET top_deal_by_categories = NOT top_deal_by_categories, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setTopDealByCategoriesStatus($productId, $status) {
        $query = "UPDATE products SET top_deal_by_categories = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getTopDealByCategoriesProducts() {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.top_deal_by_categories = 1 AND p.status = 'active'
                 ORDER BY p.created_at DESC";
        
        $products = $this->db->fetchAll($query);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    // Search and Filter Methods
    public function getProductsWithFilters($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '', $offset = 0, $limit = 10) {
        $sql = "SELECT p.*, c.name as category_name,
                       (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, display_order ASC LIMIT 1) as primary_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($category_id)) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        if (!empty($status)) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if ($featured !== '') {
            $sql .= " AND p.featured = ?";
            $params[] = $featured;
        }
        
        if ($bestseller !== '') {
            $sql .= " AND p.our_bestseller = ?";
            $params[] = $bestseller;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getTotalProductsCount($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '') {
        $sql = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($category_id)) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        if (!empty($status)) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if ($featured !== '') {
            $sql .= " AND p.featured = ?";
            $params[] = $featured;
        }
        
        if ($bestseller !== '') {
            $sql .= " AND p.our_bestseller = ?";
            $params[] = $bestseller;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    // Utility Methods
    public function getProductCountByStatus($status = '') {
        $query = "SELECT COUNT(*) as count FROM products";
        $params = [];
        
        if (!empty($status)) {
            $query .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $result = $this->db->fetch($query, $params);
        return $result['count'] ?? 0;
    }

    public function getLowStockProducts($threshold = 10) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.stock_quantity <= ? AND p.status = 'active'
                  ORDER BY p.stock_quantity ASC";
        
        $products = $this->db->fetchAll($query, [$threshold]);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    public function updateStockQuantity($productId, $newQuantity) {
        $query = "UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$newQuantity, $productId]);
    }

    public function getProductsByCategory($categoryId) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = ? AND p.status = 'active'
                  ORDER BY p.created_at DESC";
        
        $products = $this->db->fetchAll($query, [$categoryId]);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    public function searchProducts($searchTerm, $limit = 20) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.description LIKE ?) 
                  AND p.status = 'active'
                  ORDER BY p.created_at DESC 
                  LIMIT ?";
        
        $search_term = "%$searchTerm%";
        $products = $this->db->fetchAll($query, [$search_term, $search_term, $search_term, $search_term, $limit]);
        
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        
        return $products;
    }

    // Get all status options
    public function getStatusOptions() {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive', 
            'draft' => 'Draft'
        ];
    }

      public function getProductsWithPagination($search = '', $category_id = '', $status = '', $offset = 0, $limit = 10) {
        // Build base query
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE 1=1";
        
        $count_query = "SELECT COUNT(*) as total 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE 1=1";
        
        $params = [];
        $count_params = [];
        
        // Add search condition
        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
            $count_query .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
            $search_term = "%$search%";
            $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
            $count_params = array_merge($count_params, [$search_term, $search_term, $search_term, $search_term]);
        }
        
        // Add category filter
        if (!empty($category_id)) {
            $query .= " AND p.category_id = ?";
            $count_query .= " AND p.category_id = ?";
            $params[] = $category_id;
            $count_params[] = $category_id;
        }
        
        // Add status filter
        if (!empty($status)) {
            $query .= " AND p.status = ?";
            $count_query .= " AND p.status = ?";
            $params[] = $status;
            $count_params[] = $status;
        }
        
        // Get total count first (without limit/offset)
        $total_result = $this->db->fetch($count_query, $count_params);
        $total = $total_result['total'] ?? 0;
        
        // Add ordering and pagination to main query
        $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Get paginated products
        $products_data = $this->db->fetchAll($query, $params);
        
        $products = [];
        foreach ($products_data as $row) {
            $row['images'] = $this->getProductImages($row['id']);
            $products[] = $row;
        }
        
        return [
            'products' => $products,
            'total' => $total
        ];
    }

    // Add to ProductModel class

public function getDiscountProducts($limit = 20) {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.discount_price > 0 AND p.status = 'active'
              ORDER BY (p.price - p.discount_price) DESC 
              LIMIT ?";
    
    $products = $this->db->fetchAll($query, [$limit]);
    
    foreach ($products as &$product) {
        $product['images'] = $this->getProductImages($product['id']);
    }
    
    return $products;
}

public function getRecentlyViewedProducts($limit = 20) {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.status = 'active'
              ORDER BY p.created_at DESC 
              LIMIT ?";
    
    $products = $this->db->fetchAll($query, [$limit]);
    
    foreach ($products as &$product) {
        $product['images'] = $this->getProductImages($product['id']);
    }
    
    return $products;
}

public function getProductsByCategorySlug($slug, $limit = 50) {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE c.slug = ? AND p.status = 'active'
              ORDER BY p.created_at DESC 
              LIMIT ?";
    
    $products = $this->db->fetchAll($query, [$slug, $limit]);
    
    foreach ($products as &$product) {
        $product['images'] = $this->getProductImages($product['id']);
    }
    
    return $products;
}

}
?>