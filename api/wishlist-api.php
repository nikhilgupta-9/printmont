<?php
// api/wishlist-api.php
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

try {
    require_once(__DIR__ . '/../config/database.php');
    require_once(__DIR__ . '/../controllers/WishlistController.php');
    
    $wishlistController = new WishlistController();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Clean output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    $userId = getUserIdFromToken(); // Implement this function
    
    switch ($method) {
        case 'GET':
            $wishlist = $wishlistController->getWishlist($userId);
            echo json_encode([
                'success' => true,
                'data' => $wishlist
            ]);
            break;
            
        case 'POST':
            $productId = $input['product_id'] ?? null;
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                break;
            }
            
            $result = $wishlistController->addToWishlist($userId, $productId);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            $productId = $_GET['product_id'] ?? null;
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                break;
            }
            
            $result = $wishlistController->removeFromWishlist($userId, $productId);
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
        'error' => 'Wishlist operation failed: ' . $e->getMessage()
    ]);
}
?>