<?php
require_once(__DIR__ . '/../config/database.php');

class ProductModel
{
    private $db;
    private $table = "products";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Basic CRUD Operations
    // public function getAllProducts()
    // {
    //     $query = "SELECT p.*, c.name as category_name 
    //               FROM products p 
    //               LEFT JOIN categories c ON p.category_id = c.id 
    //               ORDER BY p.created_at DESC";
    //     $products = $this->db->fetchAll($query);

    //     // Get images for each product
    //     foreach ($products as &$product) {
    //         $product['images'] = $this->getProductImages($product['id']);
    //     }

    //     return $products;
    // }

    // public function getProductById($id)
    // {
    //     $query = "SELECT p.*, c.name as category_name 
    //               FROM products p 
    //               LEFT JOIN categories c ON p.category_id = c.id 
    //               WHERE p.id = ?";
    //     $product = $this->db->fetch($query, [$id]);

    //     if ($product) {
    //         $product['images'] = $this->getProductImages($id);
    //     }

    //     return $product;
    // }

    public function createProduct($data, $mainImages = [])
    {
        $this->db->beginTransaction();

        try {
            // Insert into products table
            $query = "INSERT INTO products (
                name, product_slug, description, long_description, instructions, 
                delivery_info, alt_tag, thumbnail_image, gallery_images,
                category_id, sub_category_id, sub_sub_category_id, 
                homepage_category_id, homepage_carousel_id,
                brand, color, type, material, occasion, discount_type,
                shape, gender, gift_type, ideal_for, customization_tech,
                customization_location, capacity, ink_color, features,
                price, discount_price, sort_order, stock_quantity,
                out_of_stock_status, minimum_quantity, bulk_price_variance,
                sku, status, featured, top_selection, our_bestseller,
                top_rated, top_deal_by_categories, view_count,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?
            )";

            $params = [
                $data['name'],
                $data['product_slug'],
                $data['description'],
                $data['long_description'],
                $data['instructions'],
                $data['delivery_info'],
                $data['alt_tag'],
                $data['thumbnail_image'],
                $data['gallery_images'],

                // Categories
                $data['category_id'],
                $data['sub_category_id'],
                $data['sub_sub_category_id'],
                $data['homepage_category_id'],
                $data['homepage_carousel_id'],

                // Filters
                $data['brand'],
                $data['color'],
                $data['type'],
                $data['material'],
                $data['occasion'],
                $data['discount_type'],
                $data['shape'],
                $data['gender'],
                $data['gift_type'],
                $data['ideal_for'],
                $data['customization_tech'],
                $data['customization_location'],
                $data['capacity'],
                $data['ink_color'],
                $data['features'],

                // Pricing & Inventory
                $data['price'],
                $data['discount_price'],
                $data['sort_order'],
                $data['stock_quantity'],
                $data['out_of_stock_status'],
                $data['minimum_quantity'],
                $data['bulk_price_variance'],
                $data['sku'],
                $data['status'],
                $data['featured'],

                // Additional flags
                $data['top_selection'],
                $data['our_bestseller'],
                $data['top_rated'],
                $data['top_deal_by_categories'],
                $data['view_count'],

                // Timestamps
                $data['created_at'],
                $data['updated_at']
            ];

            $productId = $this->db->insert($query, $params);

            // Insert main images into product_images table
            if (!empty($mainImages)) {
                $this->addProductImages($productId, $mainImages);
            }

            $this->db->commit();
            return $productId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // private function addProductImages($productId, $images)
    // {
    //     foreach ($images as $index => $imagePath) {
    //         $query = "INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at) 
    //                   VALUES (?, ?, ?, ?, NOW())";

    //         $params = [
    //             $productId,
    //             $imagePath,
    //             ($index === 0) ? 1 : 0, // First image is primary
    //             $index
    //         ];

    //         $this->db->execute($query, $params);
    //     }
    // }

    public function updateProduct($id, $data, $mainImages = [])
    {
        $this->db->beginTransaction();

        try {
            // Build update query with all fields
            $query = "UPDATE products SET 
            name = ?, product_slug = ?, description = ?, long_description = ?, 
            instructions = ?, delivery_info = ?, alt_tag = ?, thumbnail_image = ?, 
            gallery_images = ?, category_id = ?, sub_category_id = ?, 
            sub_sub_category_id = ?, brand = ?, color = ?, type = ?, 
            material = ?, occasion = ?, discount_type = ?, shape = ?, 
            gender = ?, gift_type = ?, ideal_for = ?, customization_tech = ?, 
            customization_location = ?, capacity = ?, ink_color = ?, 
            features = ?, price = ?, discount_price = ?, sort_order = ?, 
            stock_quantity = ?, out_of_stock_status = ?, minimum_quantity = ?, 
            bulk_price_variance = ?, sku = ?, status = ?, featured = ?, 
            top_selection = ?, our_bestseller = ?, top_rated = ?, 
            top_deal_by_categories = ?, updated_at = ? 
            WHERE id = ?";

            $params = [
                $data['name'],
                $data['product_slug'],
                $data['description'],
                $data['long_description'],
                $data['instructions'],
                $data['delivery_info'],
                $data['alt_tag'],
                $data['thumbnail_image'],
                $data['gallery_images'],
                $data['category_id'],
                $data['sub_category_id'] ?? null,
                $data['sub_sub_category_id'] ?? null,
                $data['brand'],
                $data['color'],
                $data['type'],
                $data['material'],
                $data['occasion'],
                $data['discount_type'],
                $data['shape'],
                $data['gender'],
                $data['gift_type'],
                $data['ideal_for'],
                $data['customization_tech'],
                $data['customization_location'],
                $data['capacity'],
                $data['ink_color'],
                $data['features'],
                $data['price'],
                $data['discount_price'],
                $data['sort_order'],
                $data['stock_quantity'],
                $data['out_of_stock_status'],
                $data['minimum_quantity'],
                $data['bulk_price_variance'],
                $data['sku'],
                $data['status'],
                $data['featured'],
                $data['top_selection'],
                $data['our_bestseller'],
                $data['top_rated'],
                $data['top_deal_by_categories'],
                $data['updated_at'],
                $id
            ];

            $success = $this->db->execute($query, $params);

            // Update main images if provided
            if (!empty($mainImages)) {
                // Delete old main images
                $this->db->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);

                // Add new main images
                foreach ($mainImages as $index => $image) {
                    $imageQuery = "INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at) 
                               VALUES (?, ?, ?, ?, NOW())";

                    $this->db->execute($imageQuery, [
                        $id,
                        $image['image_url'],
                        $image['is_primary'] ? 1 : 0,
                        $image['display_order'] ?? $index
                    ]);
                }
            }

            $this->db->commit();
            return $success;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getProductById($id)
    {
        $query = "SELECT p.*, c1.name as main_category_name
                  FROM products p
                  LEFT JOIN categories c1 ON p.category_id = c1.id
                  WHERE p.id = ?";

        $product = $this->db->fetch($query, [$id]);

        if ($product) {
            // Get product images
            $imagesQuery = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order";
            $product['main_images'] = $this->db->fetchAll($imagesQuery, [$id]);
        }

        return $product;
    }

    public function getAllProducts($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['featured'])) {
            $where[] = "p.featured = 1";
        }

        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT p.*, 
                  c1.name as main_category_name,
                  
                  
                  FROM products p
                  LEFT JOIN categories c1 ON p.category_id = c1.id
                  -- removed invalid join
                  -- removed invalid join
                  $whereClause
                  ORDER BY p.sort_order, p.created_at DESC";

        return $this->db->fetchAll($query, $params);
    }

    // public function deleteProduct($id)
    // {
    //     // Delete product images first
    //     $this->db->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);

    //     // Delete product
    //     $query = "DELETE FROM products WHERE id = ?";
    //     return $this->db->execute($query, [$id]);
    // }

    public function updateProductStatus($id, $status)
    {
        $query = "UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $id]);
    }

    public function updateProductFeatured($id, $featured)
    {
        $query = "UPDATE products SET featured = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$featured, $id]);
    }

    public function getProductCounts()
    {
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM products");
        $active = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $featured = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE featured = 1");
        $outOfStock = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE out_of_stock_status = 'out_of_stock'");

        return [
            'total' => $total['count'] ?? 0,
            'active' => $active['count'] ?? 0,
            'featured' => $featured['count'] ?? 0,
            'out_of_stock' => $outOfStock['count'] ?? 0
        ];
    }

    public function deleteProduct($id)
    {
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
    private function getProductImages($productId)
    {
        $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC";
        return $this->db->fetchAll($query, [$productId]);
    }

    private function addProductImages($productId, $images)
    {
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
    public function getDeactiveProducts()
    {
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

    public function getActiveProducts()
    {
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

    public function getFeaturedProducts()
    {
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
    public function toggleBestseller($productId)
    {
        $query = "UPDATE products SET our_bestseller = NOT our_bestseller, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setBestsellerStatus($productId, $status)
    {
        $query = "UPDATE products SET our_bestseller = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getBestsellerProducts()
    {
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
    public function toggleTopSelection($productId)
    {
        $query = "UPDATE products SET top_selection = NOT top_selection, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setTopSelectionStatus($productId, $status)
    {
        $query = "UPDATE products SET top_selection = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getTopSelectionProducts()
    {
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
    public function toggleTopRated($productId)
    {
        $query = "UPDATE products SET top_rated = NOT top_rated, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setTopRatedStatus($productId, $status)
    {
        $query = "UPDATE products SET top_rated = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getTopRatedProducts()
    {
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
    public function toggleTopDealByCategories($productId)
    {
        $query = "UPDATE products SET top_deal_by_categories = NOT top_deal_by_categories, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$productId]);
    }

    public function setTopDealByCategoriesStatus($productId, $status)
    {
        $query = "UPDATE products SET top_deal_by_categories = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$status, $productId]);
    }

    public function getTopDealByCategoriesProducts()
    {
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
    public function getProductsWithFilters($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '', $offset = 0, $limit = 10)
    {
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

    public function getTotalProductsCount($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '')
    {
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
    public function getProductCountByStatus($status = '')
    {
        $query = "SELECT COUNT(*) as count FROM products";
        $params = [];

        if (!empty($status)) {
            $query .= " WHERE status = ?";
            $params[] = $status;
        }

        $result = $this->db->fetch($query, $params);
        return $result['count'] ?? 0;
    }

    public function getLowStockProducts($threshold = 10)
    {
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

    public function updateStockQuantity($productId, $newQuantity)
    {
        $query = "UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$newQuantity, $productId]);
    }

    public function getProductsByCategory($categoryId)
    {
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

    public function searchProducts($searchTerm, $limit = 20)
    {
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
    public function getStatusOptions()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'draft' => 'Draft'
        ];
    }

    public function getProductsWithPagination($search = '', $category_id = '', $status = '', $offset = 0, $limit = 10)
    {
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

    public function getDiscountProducts($limit = 20)
    {
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

    public function getRecentlyViewedProducts($limit = 20)
    {
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

    public function getProductsByCategorySlug($slug, $limit = 50)
    {
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

    public function getMainCategories()
    {
        $query = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name";
        return $this->db->fetchAll($query);
    }

    public function getSubCategories($parentId)
    {
        $query = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name";
        return $this->db->fetchAll($query, [$parentId]);
    }

    public function getSubSubCategories($parentId)
    {
        $query = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name";
        return $this->db->fetchAll($query, [$parentId]);
    }

}
?>