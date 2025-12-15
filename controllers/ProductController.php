<?php
require_once(__DIR__ . '/../models/ProductModel.php');
require_once(__DIR__ . '/../models/CategoryModel.php');

class ProductController {
    private $productModel;
    private $categoryModel;

    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }

     // API Methods - Fetch Only
    public function getAllProductsApi() {
        $products = $this->productModel->getAllProducts();
        
        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }
        
        return $formattedProducts;
    }

    public function getProductByIdApi($id) {
        $product = $this->productModel->getProductById($id);
        if ($product) {
            return $this->formatProductForApi($product);
        }
        return null;
    }

    private function formatProductForApi($product) {
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


    public function getCategories() {
        return $this->categoryModel->getAllCategories();
    }

     public function addProduct($data, $files) {
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

    private function handleImageUpload($files) {
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
     public function getAllProducts() {
        return $this->productModel->getAllProducts();
    }


      public function getProductById($id) {
        return $this->productModel->getProductById($id);
    }

    public function updateProduct($id, $data, $files) {
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

    private function handleUpdateProduct($id) {
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



    public function deleteProduct($id) {
        return $this->productModel->deleteProduct($id);
    }
}
?>