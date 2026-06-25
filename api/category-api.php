<?php
// api/category-api.php
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