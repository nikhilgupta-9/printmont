<?php
// api/search-api.php
header('Content-Type: application/json');

// CORS headers
$allowed_origins = ['https://printmont.me', 'http://localhost:5173', 'http://127.0.0.1:5173'];
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once(__DIR__ . '/../config/database.php');
    require_once(__DIR__ . '/../controllers/ProductController.php');
    
    $productController = new ProductController();
    
    $query = $_GET['q'] ?? '';
    $category = $_GET['category'] ?? '';
    $minPrice = $_GET['min_price'] ?? '';
    $maxPrice = $_GET['max_price'] ?? '';
    $limit = $_GET['limit'] ?? 10;
    
    if (empty($query)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit();
    }
    
    $searchParams = [
        'query' => $query,
        'category' => $category,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'limit' => $limit
    ];
    
    $products = $productController->searchProducts($searchParams);
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'query' => $query,
        'total_results' => count($products)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Search failed: ' . $e->getMessage()
    ]);
}
?>