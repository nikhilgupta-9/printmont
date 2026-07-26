<?php
/**
 * Cart API — requires an Authorization: Bearer <access_token> header on every request
 * (get one from api/user-api.php?action=login or ?action=register).
 *
 * GET    /api/cart-api.php                          → current user's cart + subtotal
 * POST   /api/cart-api.php   {product_id, quantity, attributes?}  → add item (merges into an
 *                                                                    existing line if same product + attributes)
 * PUT    /api/cart-api.php   {item_id, quantity}     → update a line item's quantity
 * DELETE /api/cart-api.php?item_id=123               → remove a line item
 */
// api/cart-api.php
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/CartController.php');
require_once(__DIR__ . '/../controllers/AuthControllerAPI.php');

// Resolve the logged-in user's id from the Authorization: Bearer <token> header.
// Cart rows require a real customer_id (NOT NULL, FK to customers), so every
// cart operation requires a valid, logged-in user — no guest carts.
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

try {
    $database = new Database();
    $db = $database->getConnection();

    $authController = new AuthController($db);
    $cartController = new CartController();

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

    switch ($method) {
        case 'GET':
            echo json_encode($cartController->getCart($userId));
            break;

        case 'POST':
            $productId = (int) ($input['product_id'] ?? 0);
            $quantity = (int) ($input['quantity'] ?? 1);
            $attributes = $input['attributes'] ?? null;

            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                break;
            }

            echo json_encode($cartController->addToCart($userId, $productId, $quantity, $attributes));
            break;

        case 'PUT':
            $itemId = (int) ($input['item_id'] ?? 0);
            $quantity = (int) ($input['quantity'] ?? 1);

            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item ID is required']);
                break;
            }

            echo json_encode($cartController->updateCartItem($userId, $itemId, $quantity));
            break;

        case 'DELETE':
            $itemId = (int) ($_GET['item_id'] ?? 0);

            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item ID is required']);
                break;
            }

            echo json_encode($cartController->removeFromCart($userId, $itemId));
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
?>
