<?php
// api/checkout-api.php
header('Content-Type: application/json');

// CORS headers
$allowed_origins = ['https://printmont.me', 'http://localhost:5173', 'http://127.0.0.1:5173'];
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once(__DIR__ . '/../config/database.php');
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Clean output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Placeholder function for user ID
    $userId = 1; // Assuming a logged in user for now
    
    switch ($method) {
        case 'POST':
            // Place an order
            $address = $input['address'] ?? null;
            $paymentMethod = $input['paymentMethod'] ?? null;
            $items = $input['items'] ?? [];
            $totalAmount = $input['totalAmount'] ?? 0;
            
            if (!$address || !$paymentMethod || empty($items)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required order details']);
                break;
            }
            
            // In a real scenario, you would:
            // 1. Insert order into `orders` table
            // 2. Insert order items into `order_items` table
            // 3. Clear the user's cart in `cart` table
            // 4. Return order ID
            
            // Placeholder logic:
            $orderId = 'ORD-' . strtoupper(uniqid());
            
            echo json_encode([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $orderId,
                'total_paid' => $totalAmount
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Checkout operation failed: ' . $e->getMessage()
    ]);
}
?>
