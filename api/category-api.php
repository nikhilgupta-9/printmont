<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow cross-origin requests
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Specify allowed methods
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';
require_once '../controllers/CategoryController.php';

$database = new Database();
$db = $database->getConnection();
$categoryController = new CategoryController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Handle GET requests (e.g., retrieving category data)
        if (isset($_GET['id'])) {
            // Get a specific category by ID
            $id = intval($_GET['id']);
            $result = $categoryController->getCategoryById($id);
            if ($result->num_rows > 0) {
                $category = $result->fetch_assoc();
                echo json_encode(["success" => true, "data" => $category]);
            } else {
                echo json_encode(["success" => false, "message" => "Category not found"]);
            }
        } else {
            // Get all categories
            $result = $categoryController->getAllCategories();
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            echo json_encode(["success" => true, "data" => $categories]);
        }
        break;

    case 'POST':
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'create':
                    // Validate input data
                    if (empty($_POST['name'])) {
                        throw new Exception("Category name is required");
                    }

                    $result = $categoryController->createCategory($_POST, $_FILES);
                    echo json_encode($result);
                    break;

                case 'update':
                    // Validate input data
                    if (empty($_POST['name'])) {
                        throw new Exception("Category name is required");
                    }

                    $id = intval($_POST['id'] ?? 0);
                    $result = $categoryController->updateCategory($id, $_POST, $_FILES);
                    echo json_encode($result);
                    break;

                case 'delete':
                    $id = intval($_POST['id'] ?? 0);
                    $result = $categoryController->deleteCategory($id);
                    echo json_encode($result);
                    break;

                default:
                    echo json_encode(["success" => false, "message" => "Invalid action"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        break;

    case 'OPTIONS':
        // Handle OPTIONS requests for CORS preflight
        http_response_code(200);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>