<?php
header('Content-Type: application/json');

// CORS Configuration - Allow multiple origins
$allowed_origins = [
    'https://printmont.me',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:3000',
    'http://127.0.0.1:3000'
];

$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if the origin is in allowed list
if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
} else {
    // For development, you can allow any origin (remove in production)
    // header("Access-Control-Allow-Origin: *");
    // Or be more restrictive in production
    header("Access-Control-Allow-Origin: https://printmont.me");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../controllers/OrderController.php';
require_once '../controllers/CustomerController.php';
require_once '../controllers/AuthControllerAPI.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path if exists
$base_path = '/printmont-backend/api';
$path = str_replace($base_path, '', $path);

// Initialize controllers
$orderController = new OrderController();
$customerController = new CustomerController();
$authController = new AuthController($db);

// Get request data
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$queryParams = $_GET;

// Function to get authorization header properly
function getAuthorizationHeader()
{
    $headers = null;

    // Check for Authorization header in different server variables
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (!empty($requestHeaders)) {
            // Server-specific fix: handle case where array_combine might fail
            $keys = array_keys($requestHeaders);
            $values = array_values($requestHeaders);
            if (!empty($keys) && !empty($values)) {
                $requestHeaders = array_combine(
                    array_map('ucwords', $keys),
                    $values
                );
            }
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
    }

    return $headers;
}

// Function to get bearer token
function getBearerToken()
{
    $headers = getAuthorizationHeader();

    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    
    // Fallback: check for token in query string or post data (for development)
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }
    
    return null;
}

try {
    // Handle different endpoints
    if (strpos($path, '/user-api.php') !== false || $path == '/user-api.php' || $path == '/') {
        $action = $queryParams['action'] ?? '';

        switch ($action) {
            // === AUTHENTICATION ENDPOINTS ===
            case 'register':
                if ($method == 'POST') {
                    echo json_encode($authController->register($input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'login':
                if ($method == 'POST') {
                    echo json_encode($authController->login($input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'profile':
                if ($method == 'GET') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        // Fallback to old method for compatibility
                        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                        if (strpos($token, 'Bearer ') === 0) {
                            $token = substr($token, 7);
                        }
                    }

                    // Debug logging
                    error_log("Profile API - Token received: " . (!empty($token) ? "yes, length: " . strlen($token) : "no"));

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->getProfile($result['user']['id']));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'update_profile':
                if ($method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                        if (strpos($token, 'Bearer ') === 0) {
                            $token = substr($token, 7);
                        }
                    }

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->updateProfile($result['user']['id'], $input));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'get_addresses':
                if ($method == 'GET') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->getAddresses($result['user']['id']));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'add_address':
                if ($method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->addAddress($result['user']['id'], $input));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'update_address':
                if ($method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->updateAddress($result['user']['id'], $input));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'delete_address':
                if ($method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->deleteAddress($result['user']['id'], $input));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'set_default_address':
                if ($method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->setDefaultAddress($result['user']['id'], $input));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            // === ORDER ENDPOINTS ===
            case 'get_orders':
                if ($method == 'GET') {
                    echo json_encode($orderController->getAllOrders($queryParams));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'get_order':
                if ($method == 'GET' && isset($queryParams['id'])) {
                    echo json_encode($orderController->getOrderById($queryParams['id']));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed or missing ID']);
                }
                break;

            case 'create_order':
                if ($method == 'POST') {
                    echo json_encode($orderController->createOrder($input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Action not found. Available actions: register, login, profile, update_profile, get_orders, get_order, create_order, ...'
                ]);
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