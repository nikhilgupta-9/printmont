<?php
// api/wishlist-api.php
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/WishlistController.php');
require_once(__DIR__ . '/../controllers/AuthControllerAPI.php');

// Same contract as cart-api.php: wishlist_items.customer_id is NOT NULL, so
// every request needs a valid Authorization: Bearer <access_token>.
if (!function_exists('getBearerToken')) {
    function getBearerToken(): ?string {
        $headers = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $authController = new AuthController($db);
    $wishlistController = new WishlistController();

    $token = getBearerToken();
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No token provided']);
        exit;
    }

    $authResult = $authController->verifyToken($token);
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode($authResult);
        exit;
    }
    $userId = (int) $authResult['user']['id'];

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Clean output buffer
    if (ob_get_length()) {
        ob_clean();
    }

    switch ($method) {
        case 'GET':
            // getWishlist already returns { success, data, count } — do not re-wrap.
            echo json_encode($wishlistController->getWishlist($userId));
            break;

        case 'POST':
            $productId = (int) ($input['product_id'] ?? 0);

            if ($productId < 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                break;
            }

            echo json_encode($wishlistController->addToWishlist($userId, $productId));
            break;

        case 'DELETE':
            $productId = (int) ($_GET['product_id'] ?? 0);

            if ($productId < 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                break;
            }

            echo json_encode($wishlistController->removeFromWishlist($userId, $productId));
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