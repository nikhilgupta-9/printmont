<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');

$request_method = $_SERVER['REQUEST_METHOD'];
$productController = new ProductController();

if ($request_method === 'GET') {
    if (isset($_GET['id'])) {
        // Get single product by ID
        $product = $productController->getProductByIdApi($_GET['id']);
        if ($product) {
            echo json_encode([
                'success' => true,
                'data' => $product
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }
    } else {
        // Get all products
        try {
            $products = $productController->getAllProductsApi();
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => count($products)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching products: ' . $e->getMessage()
            ]);
        }
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>