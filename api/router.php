<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../controllers/OrderController.php';
require_once '../controllers/CustomerController.php';
// Add this after other require_once statements
require_once '../controllers/AuthControllerAPI.php';



$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Initialize AuthController
$authController = new AuthController($db);
// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Remove query string from URI
$path = parse_url($request_uri, PHP_URL_PATH);
$query_string = parse_url($request_uri, PHP_URL_QUERY);

// Parse query parameters
parse_str($query_string, $queryParams);

// Get JSON input for POST/PUT requests
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Remove base path
$base_path = '/printmont-backend/api';
$path = str_replace($base_path, '', $path);

$orderController = new OrderController();
$customerController = new CustomerController();

try {
    // Simple routing based on query parameters for now
    if (strpos($path, '/user-api.php') !== false || empty($path) || $path == '/') {
        
        $action = $queryParams['action'] ?? '';
        
        switch ($action) {
            case 'get_orders':
                if ($method == 'GET') {
                    echo json_encode($orderController->getAllOrders($queryParams));
                }
                break;
                
            case 'get_order':
                if ($method == 'GET' && isset($queryParams['id'])) {
                    echo json_encode($orderController->getOrderById($queryParams['id']));
                }
                break;
                
            case 'create_order':
                if ($method == 'POST') {
                    echo json_encode($orderController->createOrder($input));
                }
                break;
                
            case 'update_order_status':
                if ($method == 'PUT' && isset($queryParams['id'])) {
                    echo json_encode($orderController->updateOrderStatus(
                        $queryParams['id'], 
                        $input['status'] ?? '', 
                        $input['notes'] ?? '', 
                        $input['created_by'] ?? null
                    ));
                }
                break;
                
            case 'update_payment_status':
                if ($method == 'PUT' && isset($queryParams['id'])) {
                    echo json_encode($orderController->updatePaymentStatus(
                        $queryParams['id'], 
                        $input['payment_status'] ?? ''
                    ));
                }
                break;
                
            case 'get_customer_orders':
                if ($method == 'GET' && isset($queryParams['user_id'])) {
                    echo json_encode($orderController->getCustomerOrders($queryParams['user_id']));
                }
                break;
                
            case 'get_dashboard_stats':
                if ($method == 'GET') {
                    echo json_encode($orderController->getDashboardStats());
                }
                break;
                
            case 'get_customers':
                if ($method == 'GET') {
                    echo json_encode($customerController->getAllCustomers($queryParams));
                }
                break;
                
            case 'get_customer':
                if ($method == 'GET' && isset($queryParams['id'])) {
                    echo json_encode($customerController->getCustomerById($queryParams['id']));
                }
                break;
                
            case 'create_customer':
                if ($method == 'POST') {
                    echo json_encode($customerController->createCustomer($input));
                }
                break;

            case 'register':
    if ($method == 'POST') {
        echo json_encode($authController->register($input));
    }
    break;
    
case 'login':
    if ($method == 'POST') {
        echo json_encode($authController->login($input));
    }
    break;
    
case 'profile':
    if ($method == 'GET') {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        $result = $authController->verifyToken($token);
        if ($result['success']) {
            echo json_encode($authController->getProfile($result['user']['id']));
        } else {
            echo json_encode($result);
        }
    } elseif ($method == 'PUT') {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        $result = $authController->verifyToken($token);
        if ($result['success']) {
            echo json_encode($authController->updateProfile($result['user']['id'], $input));
        } else {
            echo json_encode($result);
        }
    }
    break;
    
case 'change_password':
    if ($method == 'PUT') {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        $result = $authController->verifyToken($token);
        if ($result['success']) {
            echo json_encode($authController->changePassword($result['user']['id'], $input));
        } else {
            echo json_encode($result);
        }
    }
    break;
                
            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Action not found. Available actions: get_orders, get_order, create_order, update_order_status, update_payment_status, get_customer_orders, get_dashboard_stats, get_customers, get_customer, create_customer']);
        }
        
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint not found: ' . $path]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
