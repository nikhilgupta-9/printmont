<?php
// api/category-api.php
header('Content-Type: application/json');

// CORS headers
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($http_origin)) {
    header("Access-Control-Allow-Origin: $http_origin");
} else {
    header("Access-Control-Allow-Origin: *");
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Turn off error display
ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once(__DIR__ . '/../config/database.php');
    require_once(__DIR__ . '/../models/CategoryModel.php');  
    require_once(__DIR__ . '/../controllers/CategoryController.php');

    $categoryController = new CategoryController();
    
    // Clean output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    $categories = $categoryController->getAllCategoriesAPI();
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
    
    exit();
    
} catch (Exception $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Server error'
    ]);
    exit();
}