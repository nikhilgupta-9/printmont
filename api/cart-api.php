<?php
// api/cart-api.php
header('Content-Type: application/json');

// CORS headers
$allowed_origins = ['https://printmont.me', 'http://localhost:5173', 'http://127.0.0.1:5173'];
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once(__DIR__ . '/../config/database.php');
    require_once(__DIR__ . '/../controllers/CartController.php');
    
    $cartController = new CartController();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Clean output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Get user ID from token (you'll need to implement this)
    $userId = getUserIdFromToken(); // You need to implement this function
    
    switch ($method) {
        case 'GET':
            $cart = $cartController->getCart($userId);
            echo json_encode([
                'success' => true,
                'data' => $cart
            ]);
            break;
            
        case 'POST':
            $productId = $input['product_id'] ?? null;
            $quantity = $input['quantity'] ?? 1;
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                break;
            }
            
            $result = $cartController->addToCart($userId, $productId, $quantity);
            echo json_encode($result);
            break;
            
        case 'PUT':
            $itemId = $input['item_id'] ?? null;
            $quantity = $input['quantity'] ?? 1;
            
            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item ID is required']);
                break;
            }
            
            $result = $cartController->updateCartItem($userId, $itemId, $quantity);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            $itemId = $_GET['item_id'] ?? null;
            
            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item ID is required']);
                break;
            }
            
            $result = $cartController->removeFromCart($userId, $itemId);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Cart operation failed: ' . $e->getMessage()
    ]);
}

// Helper function to get user ID from token
function getUserIdFromToken() {
    // Implement your token verification logic here
    // This is a placeholder - you need to implement based on your auth system
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    
    if ($token) {
        // Verify token and return user ID
        // This should call your AuthController
        return 1; // Placeholder
    }
    
    return null; // Or handle guest users
}
?>