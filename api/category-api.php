<?php
// api/category-api.php
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

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