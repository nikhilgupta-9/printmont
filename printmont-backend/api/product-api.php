<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow public API access
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Load required files
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');

// Create DB connection
$db = (new Database())->getConnection();

// Initialize Controller
$productController = new ProductController($db);

// Handle API Routes
$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

try {
    // If ID provided → return that single product
    if ($id !== null) {
        $product = $productController->getProductByIdApi($id);

        if ($product) {
            echo json_encode([
                "success" => true,
                "product" => $product
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Product not found"]);
        }
        exit;
    }

    // Get deactive products
    if ($status === 'deactive') {
        $products = $productController->getDeactiveProducts();
        echo json_encode([
            "success" => true,
            "products" => $products
        ]);
        exit;
    }

    // Default → get all products
    $products = $productController->getAllProductsApi();

    echo json_encode([
        "success" => true,
        "products" => $products
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
