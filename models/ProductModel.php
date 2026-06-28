<?php
require_once(__DIR__ . '/../config/database.php');

class ProductModel
{
    private $db;

    // All valid columns that can be written to the products table
    private $allowedColumns = [
        'name', 'product_slug', 'description', 'long_description', 'instructions',
        'delivery_info', 'alt_tag', 'thumbnail_image', 'gallery_images',
        'category_id', 'sub_category_id', 'sub_sub_category_id',
        'homepage_category_id', 'homepage_carousel_id',
        'additional_category_ids', 'additional_sub_category_ids', 'additional_sub_sub_category_ids',
        'brand', 'color', 'type', 'material', 'occasion', 'discount_type',
        'shape', 'gender', 'gift_type', 'ideal_for', 'customization_tech',
        'customization_location', 'capacity', 'ink_color', 'features',
        'price', 'regular_price', 'offer_price', 'discount_price', 'sort_order',
        'stock_quantity', 'product_quantity', 'out_of_stock_status', 'minimum_quantity',
        'bulk_price_variance', 'sku', 'status', 'featured',
        'top_selection', 'our_bestseller', 'top_rated', 'top_deal_by_categories', 'view_count',
        // SEO
        'meta_title', 'meta_keywords', 'meta_description',
        // Visibility / Status toggles
        'show_quantity', 'show_bulk_form', 'show_help_button', 'help_button_text', 'help_button_link',
        // Customization
        'show_customization_label', 'customization_label', 'customization_fields',
        // Addons
        'addon_product_ids',
        // Attributes
        'show_sizes', 'show_colors',
        'size_attributes', 'color_attributes', 'material_attributes',
        'lamination_attributes', 'orientation_attributes', 'quantity_price_breaks',
        // Shipping
        'shipping_method_status',
        'local_shipping_charge', 'local_shipping_message',
        'regional_shipping_charge', 'regional_shipping_message',
        'national_shipping_charge', 'national_shipping_message',
        // Cancel
        'cancel_available', 'cancel_time', 'cancel_type',
        // COD
        'cod_available',
        // Timestamps
        'created_at', 'updated_at',
    ];

    public function __construct()
    {
        $this->db = new Database();
    }

    public function createProduct($data, $mainImages = [])
    {
        $this->db->beginTransaction();
        try {
            $cols   = [];
            $vals   = [];
            foreach ($data as $col => $val) {
                if (in_array($col, $this->allowedColumns)) {
                    $cols[] = "`$col`";
                    $vals[] = $val;
                }
            }

            if (empty($cols)) {
                throw new Exception("No valid fields provided for product creation");
            }

            $placeholders = implode(', ', array_fill(0, count($cols), '?'));
            $query = "INSERT INTO products (" . implode(', ', $cols) . ") VALUES ($placeholders)";
            $productId = $this->db->insert($query, $vals);

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

    public function updateProduct($id, $data, $mainImages = [])
    {
        $this->db->beginTransaction();
        try {
            $setParts = [];
            $params   = [];
            foreach ($data as $col => $val) {
                if (in_array($col, $this->allowedColumns)) {
                    $setParts[] = "`$col` = ?";
                    $params[]   = $val;
                }
            }

            if (empty($setParts)) {
                throw new Exception("No valid fields to update");
            }

            $params[] = $id;
            $query = "UPDATE products SET " . implode(', ', $setParts) . " WHERE id = ?";
            $this->db->execute($query, $params);

            if (!empty($mainImages)) {
                $this->db->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);
                foreach ($mainImages as $index => $image) {
                    $imgQ = "INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at) VALUES (?, ?, ?, ?, NOW())";
                    $this->db->execute($imgQ, [
                        $id,
                        $image['image_url'],
                        $image['is_primary'] ? 1 : 0,
                        $image['display_order'] ?? $index,
                    ]);
                }
            }

            $this->db->commit();
            return true;
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
            $imagesQuery = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order";
            $product['main_images'] = $this->db->fetchAll($imagesQuery, [$id]);
        }

        return $product;
    }

    public function getAllProducts($filters = [])
    {
        $where  = [];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[]  = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $where[]  = "p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $s = "%{$filters['search']}%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $query = "SELECT p.*, c1.name as main_category_name
                  FROM products p
                  LEFT JOIN categories c1 ON p.category_id = c1.id
                  $whereClause
                  ORDER BY p.sort_order, p.created_at DESC";

        return $this->db->fetchAll($query, $params);
    }

    public function getProductsWithFilters($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '', $offset = 0, $limit = 10)
    {
        $sql    = "SELECT p.*, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, display_order ASC LIMIT 1) as primary_image
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
            $s = "%$search%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
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

        $sql     .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function getTotalProductsCount($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '')
    {
        $sql    = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
            $s = "%$search%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
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

    public function deleteProduct($id)
    {
        $this->db->beginTransaction();
        try {
            $this->db->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);
            $result = $this->db->execute("DELETE FROM products WHERE id = ?", [$id]);
            $this->db->commit();
            return $result;
        } catch (Exception) {
            $this->db->rollback();
            return false;
        }
    }

    public function updateProductStatus($id, $status)
    {
        return $this->db->execute("UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
    }

    public function updateProductFeatured($id, $featured)
    {
        return $this->db->execute("UPDATE products SET featured = ?, updated_at = NOW() WHERE id = ?", [$featured, $id]);
    }

    public function searchProductsByName($term, $excludeId = null, $limit = 10)
    {
        $params = ["%$term%", "%$term%"];
        $sql    = "SELECT id, name, sku, price, discount_price, thumbnail_image FROM products WHERE (name LIKE ? OR sku LIKE ?) AND status = 'active'";
        if ($excludeId) {
            $sql    .= " AND id != ?";
            $params[] = $excludeId;
        }
        $sql     .= " ORDER BY name ASC LIMIT ?";
        $params[] = $limit;
        return $this->db->fetchAll($sql, $params);
    }

    public function getProductsByIds($ids)
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, name, sku, price, discount_price, thumbnail_image FROM products WHERE id IN ($placeholders) AND status = 'active'";
        return $this->db->fetchAll($sql, $ids);
    }

    public function getProductCounts()
    {
        $total      = $this->db->fetch("SELECT COUNT(*) as count FROM products");
        $active     = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
        $featured   = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE featured = 1");
        $outOfStock = $this->db->fetch("SELECT COUNT(*) as count FROM products WHERE out_of_stock_status = 'out_of_stock'");
        return [
            'total'        => $total['count'] ?? 0,
            'active'       => $active['count'] ?? 0,
            'featured'     => $featured['count'] ?? 0,
            'out_of_stock' => $outOfStock['count'] ?? 0,
        ];
    }

    public function toggleBestseller($productId)
    {
        return $this->db->execute("UPDATE products SET our_bestseller = NOT our_bestseller, updated_at = NOW() WHERE id = ?", [$productId]);
    }

    public function setBestsellerStatus($productId, $status)
    {
        return $this->db->execute("UPDATE products SET our_bestseller = ?, updated_at = NOW() WHERE id = ?", [$status, $productId]);
    }

    public function getBestsellerProducts()
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.our_bestseller = 1 AND p.status = 'active' ORDER BY p.created_at DESC");
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function toggleTopSelection($productId)
    {
        return $this->db->execute("UPDATE products SET top_selection = NOT top_selection, updated_at = NOW() WHERE id = ?", [$productId]);
    }

    public function setTopSelectionStatus($productId, $status)
    {
        return $this->db->execute("UPDATE products SET top_selection = ?, updated_at = NOW() WHERE id = ?", [$status, $productId]);
    }

    public function getTopSelectionProducts()
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.top_selection = 1 AND p.status = 'active' ORDER BY p.created_at DESC");
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function toggleTopRated($productId)
    {
        return $this->db->execute("UPDATE products SET top_rated = NOT top_rated, updated_at = NOW() WHERE id = ?", [$productId]);
    }

    public function setTopRatedStatus($productId, $status)
    {
        return $this->db->execute("UPDATE products SET top_rated = ?, updated_at = NOW() WHERE id = ?", [$status, $productId]);
    }

    public function getTopRatedProducts()
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.top_rated = 1 AND p.status = 'active' ORDER BY p.created_at DESC");
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function toggleTopDealByCategories($productId)
    {
        return $this->db->execute("UPDATE products SET top_deal_by_categories = NOT top_deal_by_categories, updated_at = NOW() WHERE id = ?", [$productId]);
    }

    public function setTopDealByCategoriesStatus($productId, $status)
    {
        return $this->db->execute("UPDATE products SET top_deal_by_categories = ?, updated_at = NOW() WHERE id = ?", [$status, $productId]);
    }

    public function getTopDealByCategoriesProducts()
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.top_deal_by_categories = 1 AND p.status = 'active' ORDER BY p.created_at DESC");
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getDeactiveProducts()
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'inactive' ORDER BY p.created_at DESC");
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getActiveProducts()
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC");
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getDiscountProducts($limit = 20)
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.discount_price > 0 AND p.status = 'active' ORDER BY (p.price - p.discount_price) DESC LIMIT ?", [$limit]);
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getProductsByCategory($categoryId)
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.status = 'active' ORDER BY p.created_at DESC", [$categoryId]);
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getProductsByCategorySlug($slug, $limit = 50)
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE c.slug = ? AND p.status = 'active' ORDER BY p.created_at DESC LIMIT ?", [$slug, $limit]);
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getProductsWithPagination($search = '', $category_id = '', $status = '', $offset = 0, $limit = 10)
    {
        $query       = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
        $count_query = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
        $params      = [];
        $count_params = [];

        if (!empty($search)) {
            $cond = " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
            $query       .= $cond;
            $count_query .= $cond;
            $s = "%$search%";
            $params       = array_merge($params, [$s, $s, $s, $s]);
            $count_params = array_merge($count_params, [$s, $s, $s, $s]);
        }
        if (!empty($category_id)) {
            $query       .= " AND p.category_id = ?";
            $count_query .= " AND p.category_id = ?";
            $params[]      = $category_id;
            $count_params[] = $category_id;
        }
        if (!empty($status)) {
            $query       .= " AND p.status = ?";
            $count_query .= " AND p.status = ?";
            $params[]      = $status;
            $count_params[] = $status;
        }

        $total_result = $this->db->fetch($count_query, $count_params);
        $total        = $total_result['total'] ?? 0;

        $query    .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $products_data = $this->db->fetchAll($query, $params);
        $products = [];
        foreach ($products_data as $row) {
            $row['images'] = $this->getProductImages($row['id']);
            $products[]    = $row;
        }

        return ['products' => $products, 'total' => $total];
    }

    public function searchProducts($searchTerm, $limit = 20)
    {
        $s        = "%$searchTerm%";
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.description LIKE ?) AND p.status = 'active' ORDER BY p.created_at DESC LIMIT ?", [$s, $s, $s, $s, $limit]);
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function updateStockQuantity($productId, $newQuantity)
    {
        return $this->db->execute("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?", [$newQuantity, $productId]);
    }

    public function getLowStockProducts($threshold = 10)
    {
        $products = $this->db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity <= ? AND p.status = 'active' ORDER BY p.stock_quantity ASC", [$threshold]);
        foreach ($products as &$product) {
            $product['images'] = $this->getProductImages($product['id']);
        }
        return $products;
    }

    public function getMainCategories()
    {
        return $this->db->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
    }

    public function getSubCategories($parentId)
    {
        return $this->db->fetchAll("SELECT * FROM categories WHERE parent_id = ? ORDER BY name", [$parentId]);
    }

    public function getProductCountByStatus($status = '')
    {
        $query  = "SELECT COUNT(*) as count FROM products";
        $params = [];
        if (!empty($status)) {
            $query   .= " WHERE status = ?";
            $params[] = $status;
        }
        $result = $this->db->fetch($query, $params);
        return $result['count'] ?? 0;
    }

    public function getStatusOptions()
    {
        return ['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft'];
    }

    private function getProductImages($productId)
    {
        return $this->db->fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC", [$productId]);
    }

    private function addProductImages($productId, $images)
    {
        foreach ($images as $index => $image) {
            $this->db->execute(
                "INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)",
                [$productId, $image['image_url'], $image['is_primary'] ? 1 : 0, $image['display_order'] ?? $index]
            );
        }
    }
}
?>
