<?php
session_start();
require_once '../config/database.php';
require_once '../controllers/CategoryController.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if (isset($_GET['parent_id'])) {
        $parent_id = (int) $_GET['parent_id'];

        if ($parent_id <= 0) {
            echo json_encode(['error' => 'Invalid parent ID']);
            exit();
        }

        // Create category controller
        $categoryController = new CategoryController();

        // Get sub sub categories for the selected parent (level 3 categories)
        $subSubCategories = $categoryController->getSubSubCategories($parent_id);

        // Return as JSON
        echo json_encode($subSubCategories);
    } else {
        echo json_encode(['error' => 'Parent ID is required']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>