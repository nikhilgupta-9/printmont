<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load required files
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');

// Create DB connection
$db = (new Database())->getConnection();

// Initialize controller
$productController = new ProductController($db);

// Get product ID
$id = $_GET['id'] ?? null;

if ($id === null) {
    echo json_encode([
        "success" => false,
        "message" => "Product ID is required"
    ]);
    exit;
}

try {
    // Fetch main product
    $product = $productController->getProductByIdApi($id);

    if (!$product) {
        echo json_encode([
            "success" => false,
            "message" => "Product not found"
        ]);
        exit;
    }

    // Get category ID
    $category_id = $product['category_id'];

    // Fetch ALL products
    $allProducts = $productController->getAllProductsApi();

    // Filter related products
    $related = [];
    foreach ($allProducts as $p) {
        if ($p['id'] != $id && $p['category_id'] == $category_id) {
            $related[] = $p;
        }
    }

    echo json_encode([
        "success" => true,
        "product_id" => $id,
        "category_id" => $category_id,
        "related_products_count" => count($related),
        "related_products" => $related
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
