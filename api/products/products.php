<?php
/**
 * Unified Product Section API
 * 
 * Serves product data for homepage sections (carousel, grid, mosaic, etc.).
 * The frontend calls this endpoint via: /api/products/products.php?action=<action>
 * 
 * Supported actions:
 *   bestseller, top_selection, top_rated, top_deal, discount_for_you,
 *   recently_viewed, categories, grouped_categories,
 *   men_clothing, women_clothing, kids, mobile, laptop, buds,
 *   home_decor, table_dinnerware, women_outfit, men, women
 */

require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../controllers/ProductController.php');
require_once(__DIR__ . '/../../models/CategoryModel.php');

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
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

$response = [];

try {
    switch ($action) {
        // ── Product flag-based sections ────────────────────────────────
        case 'bestseller':
            $products = $productController->getBestsellerProductsApi();
            $response = [
                'success' => true,
                'data'    => $limit ? array_slice($products, 0, $limit) : $products,
                'total'   => count($products)
            ];
            break;

        case 'top_selection':
            $products = $productController->getTopSelectionProductsApi();
            if (isset($products['data'])) {
                // Method already returns { success, data } format
                $response = $products;
                if ($limit && is_array($response['data'])) {
                    $response['data'] = array_slice($response['data'], 0, $limit);
                }
            } else {
                $response = [
                    'success' => true,
                    'data'    => $limit ? array_slice($products, 0, $limit) : $products
                ];
            }
            break;

        case 'top_rated':
            $products = $productController->getTopRatedProductsApi();
            if (isset($products['data'])) {
                $response = $products;
                if ($limit && is_array($response['data'])) {
                    $response['data'] = array_slice($response['data'], 0, $limit);
                }
            } else {
                $response = [
                    'success' => true,
                    'data'    => $limit ? array_slice($products, 0, $limit) : $products
                ];
            }
            break;

        case 'top_deal':
            $response = $productController->getTopDealByCategoriesProductsApi();
            if ($limit && isset($response['data']) && is_array($response['data'])) {
                $response['data'] = array_slice($response['data'], 0, $limit);
            }
            break;

        case 'discount_for_you':
        case 'special_offer':
        case 'special_offers':
        case 'flash_sale':
            $response = $productController->getDiscountProductsApi();
            if ($limit && isset($response['data']) && is_array($response['data'])) {
                $response['data'] = array_slice($response['data'], 0, $limit);
            }
            break;

        case 'recently_viewed':
            $response = $productController->getRecentlyViewedApi();
            if ($limit && isset($response['data']) && is_array($response['data'])) {
                $response['data'] = array_slice($response['data'], 0, $limit);
            }
            break;

        // ── Category-based sections ───────────────────────────────────
        case 'categories':
            $response = $productController->getAllCategoriesApi();
            break;

        case 'grouped_categories':
            // Return top-deal products grouped — same as top_deal for now
            $response = $productController->getTopDealByCategoriesProductsApi();
            if ($limit && isset($response['data']) && is_array($response['data'])) {
                $response['data'] = array_slice($response['data'], 0, $limit);
            }
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
            http_response_code(400);
            $response = ['success' => false, 'error' => "Invalid action: '{$action}'"];
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response);
?>
