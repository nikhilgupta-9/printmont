<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/ProductModel.php');
require_once(__DIR__ . '/../models/CategoryModel.php');

class ProductController
{
    private $productModel;
    private $categoryModel;

    public function __construct($db)
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new Category($db);
    }

    // API Methods - Fetch Only
    public function getAllProductsApi()
    {
        $products = $this->productModel->getAllProducts();

        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }

        return $formattedProducts;
    }

    public function getProductByIdApi($id)
    {
        $product = $this->productModel->getProductById($id);
        if ($product) {
            return $this->formatProductForApi($product);
        }
        return null;
    }

    private function formatProductForApi($product)
    {
        // Get base URL for absolute image paths
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $protocol . "://" . $host . $basePath . "/";

        // Format images with full URLs
        $formattedImages = [];
        if (!empty($product['images'])) {
            foreach ($product['images'] as $image) {
                $imagePath = $image['image_url'];
                // Convert relative path to absolute URL
                $absoluteUrl = $baseUrl . $imagePath;

                $formattedImages[] = [
                    'id' => (int)$image['id'],
                    'image_url' => $absoluteUrl,
                    'is_primary' => (bool)$image['is_primary'],
                    'display_order' => (int)$image['display_order'],
                    'created_at' => $image['created_at']
                ];
            }
        }

        return [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'category_id' => (int)$product['category_id'],
            'category_name' => $product['category_name'] ?? null,
            'brand' => $product['brand'],
            'price' => (float)$product['price'],
            'discount_price' => $product['discount_price'] ? (float)$product['discount_price'] : null,
            'stock_quantity' => (int)$product['stock_quantity'],
            'sku' => $product['sku'],
            'status' => $product['status'],
            'featured' => (bool)$product['featured'],
            'images' => $formattedImages,
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at']
        ];
    }


    public function getCategories()
    {
        return $this->categoryModel->getAllCategoriesFlat();
    }

    public function addProduct($data, $files)
    {
        try {
            // Validate required fields
            $required = ['name', 'category_id', 'price', 'stock_quantity', 'sku'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            // Prepare product data
            $productData = [
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'category_id' => $data['category_id'],
                'brand' => trim($data['brand'] ?? ''),
                'price' => floatval($data['price']),
                'discount_price' => !empty($data['discount_price']) ? floatval($data['discount_price']) : null,
                'stock_quantity' => intval($data['stock_quantity']),
                'sku' => trim($data['sku']),
                'status' => $data['status'] ?? 'active',
                'featured' => isset($data['featured']) ? 1 : 0
            ];

            // Handle image upload
            $images = [];
            if (!empty($files['images']['name'][0])) {
                $images = $this->handleImageUpload($files['images']);
            }

            // Create product
            $productId = $this->productModel->createProduct($productData, $images);

            return ['success' => true, 'product_id' => $productId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function handleImageUpload($files)
    {
        $uploadedImages = [];
        $uploadDir = 'uploads/products/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files['tmp_name'] as $key => $tmp_name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($tmp_name);

                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.");
                }

                // Generate unique filename
                $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '', $files['name'][$key]);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($tmp_name, $filePath)) {
                    $uploadedImages[] = [
                        'image_url' => $filePath,
                        'is_primary' => ($key === 0), // First image as primary
                        'display_order' => $key
                    ];
                } else {
                    throw new Exception("Failed to upload image: " . $files['name'][$key]);
                }
            }
        }

        return $uploadedImages;
    }

    // Other methods for edit, list, delete...
    public function getAllProducts()
    {
        return $this->productModel->getAllProducts();
    }


    public function getProductById($id)
    {
        return $this->productModel->getProductById($id);
    }

    public function updateProduct($id, $data, $files)
    {
        try {
            $required = ['name', 'category_id', 'price', 'stock_quantity', 'sku'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            $productData = [
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'category_id' => $data['category_id'],
                'brand' => trim($data['brand'] ?? ''),
                'price' => floatval($data['price']),
                'discount_price' => !empty($data['discount_price']) ? floatval($data['discount_price']) : null,
                'stock_quantity' => intval($data['stock_quantity']),
                'sku' => trim($data['sku']),
                'status' => $data['status'] ?? 'active',
                'featured' => isset($data['featured']) ? 1 : 0
            ];

            $images = [];
            if (!empty($files['images']['name'][0])) {
                $images = $this->handleImageUpload($files['images']);
            }

            $success = $this->productModel->updateProduct($id, $productData, $images);

            if ($success) {
                return ['success' => true];
            } else {
                throw new Exception("Failed to update product");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function handleUpdateProduct($id)
    {
        try {
            $required = ['name', 'category_id', 'price', 'stock_quantity', 'sku'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            $productData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'category_id' => $_POST['category_id'],
                'brand' => trim($_POST['brand'] ?? ''),
                'price' => floatval($_POST['price']),
                'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
                'stock_quantity' => intval($_POST['stock_quantity']),
                'sku' => trim($_POST['sku']),
                'status' => $_POST['status'] ?? 'active',
                'featured' => isset($_POST['featured']) ? 1 : 0
            ];

            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $images = $this->handleImageUpload($_FILES['images']);
            }

            $success = $this->productModel->updateProduct($id, $productData, $images);

            if ($success) {
                return ['success' => true];
            } else {
                throw new Exception("Failed to update product");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // private function handleImageUpload($files) {
    //     $uploadedImages = [];
    //     $uploadDir = 'uploads/products/';

    //     if (!is_dir($uploadDir)) {
    //         mkdir($uploadDir, 0755, true);
    //     }

    //     foreach ($files['tmp_name'] as $key => $tmp_name) {
    //         if ($files['error'][$key] === UPLOAD_ERR_OK) {
    //             $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    //             $fileType = mime_content_type($tmp_name);

    //             if (!in_array($fileType, $allowedTypes)) {
    //                 throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.");
    //             }

    //             $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '', $files['name'][$key]);
    //             $filePath = $uploadDir . $fileName;

    //             if (move_uploaded_file($tmp_name, $filePath)) {
    //                 $uploadedImages[] = [
    //                     'image_url' => $filePath,
    //                     'is_primary' => ($key === 0),
    //                     'display_order' => $key
    //                 ];
    //             } else {
    //                 throw new Exception("Failed to upload image: " . $files['name'][$key]);
    //             }
    //         }
    //     }

    //     return $uploadedImages;
    // }



    public function deleteProduct($id)
    {
        return $this->productModel->deleteProduct($id);
    }

    public function getDeactiveProducts()
    {
        $products = $this->productModel->getDeactiveProducts();

        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }

        return $formattedProducts;
    }

    // Add to ProductController class
    public function toggleBestseller($productId)
    {
        try {
            $result = $this->productModel->toggleBestseller($productId);

            if ($result) {
                // Get updated product to return current status
                $product = $this->productModel->getProductById($productId);
                return [
                    'success' => true,
                    'message' => 'Bestseller status updated successfully',
                    'is_bestseller' => $product['our_bestseller']
                ];
            } else {
                throw new Exception("Failed to update bestseller status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setBestsellerStatus($productId, $status)
    {
        try {
            $result = $this->productModel->setBestsellerStatus($productId, $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bestseller status updated successfully',
                    'is_bestseller' => $status
                ];
            } else {
                throw new Exception("Failed to update bestseller status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getBestsellerProducts()
    {
        $products = $this->productModel->getBestsellerProducts();

        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }

        return $formattedProducts;
    }

    // Add to ProductController class
    public function getProductsWithPagination($search = '', $category_id = '', $status = '', $offset = 0, $limit = 10)
    {
        return $this->productModel->getProductsWithPagination($search, $category_id, $status, $offset, $limit);
    }

    // public function getCategories() {
    //     return $this->categoryModel->getAllCategoriesFlat();
    // }

    // Add these methods to your ProductController class

    // Bestseller API methods
    public function getBestsellerProductsApi()
    {
        $products = $this->productModel->getBestsellerProducts();
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }
        return $formattedProducts;
    }

    // Top Selection methods
    public function getTopSelectionProducts()
    {
        return $this->productModel->getTopSelectionProducts();
    }

    public function getTopSelectionProductsApi()
    {
        $products = $this->productModel->getTopSelectionProducts();
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }
        return $formattedProducts;
    }

    public function toggleTopSelection($productId)
    {
        try {
            $result = $this->productModel->toggleTopSelection($productId);

            if ($result) {
                $product = $this->productModel->getProductById($productId);
                return [
                    'success' => true,
                    'message' => 'Top Selection status updated successfully',
                    'is_top_selection' => $product['top_selection'] ?? 0
                ];
            } else {
                throw new Exception("Failed to update top selection status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setTopSelectionStatus($productId, $status)
    {
        try {
            $result = $this->productModel->setTopSelectionStatus($productId, $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Top Selection status updated successfully',
                    'is_top_selection' => $status
                ];
            } else {
                throw new Exception("Failed to update top selection status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Top Rated methods
    public function getTopRatedProducts()
    {
        return $this->productModel->getTopRatedProducts();
    }

    public function getTopRatedProductsApi()
    {
        $products = $this->productModel->getTopRatedProducts();
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }
        return $formattedProducts;
    }

    public function toggleTopRated($productId)
    {
        try {
            $result = $this->productModel->toggleTopRated($productId);

            if ($result) {
                $product = $this->productModel->getProductById($productId);
                return [
                    'success' => true,
                    'message' => 'Top Rated status updated successfully',
                    'is_top_rated' => $product['top_rated'] ?? 0
                ];
            } else {
                throw new Exception("Failed to update top rated status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setTopRatedStatus($productId, $status)
    {
        try {
            $result = $this->productModel->setTopRatedStatus($productId, $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Top Rated status updated successfully',
                    'is_top_rated' => $status
                ];
            } else {
                throw new Exception("Failed to update top rated status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Add to ProductController class
    public function getProductsWithFilters($params = [])
    {
        $search = $params['search'] ?? '';
        $category_id = $params['category_id'] ?? '';
        $status = $params['status'] ?? '';
        $featured = $params['featured'] ?? '';
        $bestseller = $params['bestseller'] ?? '';
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $offset = ($page - 1) * $limit;

        return $this->productModel->getProductsWithFilters(
            $search,
            $category_id,
            $status,
            $featured,
            $bestseller,
            $offset,
            $limit
        );
    }

    public function getTotalProductsCount($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '')
    {
        return $this->productModel->getTotalProductsCount($search, $category_id, $status, $featured, $bestseller);
    }

    public function getAllCategoriesForFilter()
    {
        return $this->categoryModel->getAllCategoriesFlat();
    }


    // Add to ProductController class

    // Existing methods you already have
    // public function getTopSelectionProductsApi() {
    //     $products = $this->productModel->getTopSelectionProducts();
    //     return ['success' => true, 'data' => $products];
    // }

    // public function getTopRatedProductsApi() {
    //     $products = $this->productModel->getTopRatedProducts();
    //     return ['success' => true, 'data' => $products];
    // }

    // public function getTopDealByCategoriesProductsApi()
    // {
    //     $products = $this->productModel->getTopDealByCategoriesProducts();
    //     return ['success' => true, 'data' => $products];
    // }

    // NEW METHODS TO ADD:
    public function getDiscountProductsApi()
    {
        try {
            $products = $this->productModel->getDiscountProducts();
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Recently Viewed API  
    public function getRecentlyViewedApi()
    {
        try {
            $products = $this->productModel->getRecentlyViewedProducts();
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Categories API
    public function getAllCategoriesApi()
    {
        try {
            $categories = $this->categoryModel->getAllWithHierarchy();
            return ['success' => true, 'data' => $categories];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Products by Category Slug API
    public function getProductsByCategoryApi($categorySlug)
    {
        try {
            $products = $this->productModel->getProductsByCategorySlug($categorySlug);
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Fix method name - you had typo in method name
    public function getTopDealByCategoriesProductsApi()
    {
        try {
            $products = $this->productModel->getTopDealByCategoriesProducts();
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

     public function searchProducts($params) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "
                SELECT 
                    p.*,
                    c.name as category_name,
                    GROUP_CONCAT(pi.image_url) as image_urls,
                    GROUP_CONCAT(pi.is_primary) as primary_flags
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id
                WHERE p.status = 'active'
            ";
            
            $conditions = [];
            $bindings = [];
            
            if (!empty($params['query'])) {
                $conditions[] = "(p.name LIKE :query OR p.description LIKE :query OR c.name LIKE :query)";
                $bindings[':query'] = "%{$params['query']}%";
            }
            
            if (!empty($params['category'])) {
                $conditions[] = "c.name = :category";
                $bindings[':category'] = $params['category'];
            }
            
            if (!empty($params['min_price'])) {
                $conditions[] = "p.price >= :min_price";
                $bindings[':min_price'] = $params['min_price'];
            }
            
            if (!empty($params['max_price'])) {
                $conditions[] = "p.price <= :max_price";
                $bindings[':max_price'] = $params['max_price'];
            }
            
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }
            
            $query .= " GROUP BY p.id ORDER BY p.created_at DESC";
            
            if (!empty($params['limit'])) {
                $query .= " LIMIT :limit";
                $bindings[':limit'] = (int)$params['limit'];
            }
            
            $stmt = $db->prepare($query);
            
            foreach ($bindings as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = $this->formatProductForApi($row);
            }
            
            return $products;
            
        } catch (Exception $e) {
            throw new Exception("Search failed: " . $e->getMessage());
        }
    }
}
