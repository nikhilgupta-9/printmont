<?php
require_once(__DIR__ . '/../config/database.php');

class ProductModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

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
            $query = "INSERT INTO products (name, description, category_id, brand, price, discount_price, stock_quantity, sku, status, featured) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['brand'],
                $data['price'],
                $data['discount_price'],
                $data['stock_quantity'],
                $data['sku'],
                $data['status'],
                $data['featured']
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
                      status = ?, featured = ?, updated_at = NOW() 
                      WHERE id = ?";
            
            $params = [
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['brand'],
                $data['price'],
                $data['discount_price'],
                $data['stock_quantity'],
                $data['sku'],
                $data['status'],
                $data['featured'],
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
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

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
}
?>