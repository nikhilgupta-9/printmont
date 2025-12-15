<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

$productController = new ProductController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get bestseller products
        $products = $productController->getBestsellerProductsApi();
        echo json_encode([
            'success' => true,
            'data' => $products,
            'total' => count($products)
        ]);
        break;
        
    case 'POST':
        // Set product as bestseller
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['product_id']) && isset($input['status'])) {
            $result = $productController->setBestsellerStatus($input['product_id'], $input['status']);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product ID and status are required']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?>