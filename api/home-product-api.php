<?php
// api/products.php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');
require_once(__DIR__ . '/../models/CategoryModel.php'); // Add this line

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$productController = new ProductController($db);

$action = $_GET['action'] ?? '';

$response = [];

switch($action) {
    case 'top_selection':
        $response = $productController->getTopSelectionProductsApi();
        break;
    case 'top_rated':
        $response = $productController->getTopRatedProductsApi();
        break;
    case 'top_deal':
        $response = $productController->getTopDealByCategoriesProductsApi(); // Fixed method name
        break;
    case 'discount_for_you':
        $response = $productController->getDiscountProductsApi(); // Add this method
        break;
    case 'recently_viewed':
        $response = $productController->getRecentlyViewedApi(); // Add this method
        break;
    case 'categories':
        $response = $productController->getAllCategoriesApi(); // Add this method
        break;
    case 'men_clothing':
        $response = $productController->getProductsByCategoryApi('men-clothing');
        break;
    case 'women_clothing':
        $response = $productController->getProductsByCategoryApi('women-clothing');
        break;
    case 'kids':
        $response = $productController->getProductsByCategoryApi('kids');
        break;
    case 'mobile':
        $response = $productController->getProductsByCategoryApi('mobile');
        break;
    case 'laptop':
        $response = $productController->getProductsByCategoryApi('laptop');
        break;
    case 'buds':
        $response = $productController->getProductsByCategoryApi('buds');
        break;
    case 'home_decor':
        $response = $productController->getProductsByCategoryApi('home-decor');
        break;
    case 'table_dinnerware':
        $response = $productController->getProductsByCategoryApi('table-dinnerware');
        break;
    case 'women_outfit':
        $response = $productController->getProductsByCategoryApi('women-outfit');
        break;
    case 'men':
        $response = $productController->getProductsByCategoryApi('men');
        break;
    case 'women':
        $response = $productController->getProductsByCategoryApi('women');
        break;
    default:
        $response = ['success' => false, 'error' => 'Invalid action'];
        http_response_code(400);
}

echo json_encode($response);
?>