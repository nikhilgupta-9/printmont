<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

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

            case 'logout':
                if ($method == 'POST') {
                    echo json_encode($authController->logout());
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'refresh_token':
                if ($method == 'POST') {
                    $refreshToken = $input['refresh_token'] ?? getBearerToken();
                    echo json_encode($authController->refreshToken($refreshToken));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'forgot_password':
                if ($method == 'POST') {
                    echo json_encode($authController->forgotPassword($input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'reset_password':
                if ($method == 'POST') {
                    echo json_encode($authController->resetPassword($input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'delete_account':
                if ($method == 'DELETE' || $method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->deleteAccount($result['user']['id'], $input));
                    } else {
                        http_response_code(401);
                        echo json_encode($result);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'soft_delete_account':
                if ($method == 'PUT' || $method == 'POST') {
                    $token = getBearerToken();

                    if (empty($token)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token provided']);
                        break;
                    }

                    $result = $authController->verifyToken($token);
                    if ($result['success']) {
                        echo json_encode($authController->softDeleteAccount($result['user']['id']));
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
                    $userId = null;
                    $token = getBearerToken();
                    if (!empty($token)) {
                        $result = $authController->verifyToken($token);
                        if ($result['success'] && !empty($result['user']['id'])) {
                            $userId = $result['user']['id'];
                        }
                    }
                    if (!$userId && !empty($_GET['user_id'])) {
                        $userId = $_GET['user_id'];
                    }
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token or user_id provided']);
                        break;
                    }
                    echo json_encode($authController->getAddresses($userId));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'add_address':
                if ($method == 'POST') {
                    $userId = null;
                    $token = getBearerToken();
                    if (!empty($token)) {
                        $result = $authController->verifyToken($token);
                        if ($result['success'] && !empty($result['user']['id'])) {
                            $userId = $result['user']['id'];
                        }
                    }
                    if (!$userId && !empty($input['user_id'])) {
                        $userId = $input['user_id'];
                    }
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token or user_id provided']);
                        break;
                    }
                    echo json_encode($authController->addAddress($userId, $input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'update_address':
                if ($method == 'POST') {
                    $userId = null;
                    $token = getBearerToken();
                    if (!empty($token)) {
                        $result = $authController->verifyToken($token);
                        if ($result['success'] && !empty($result['user']['id'])) {
                            $userId = $result['user']['id'];
                        }
                    }
                    if (!$userId && !empty($input['user_id'])) {
                        $userId = $input['user_id'];
                    }
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token or user_id provided']);
                        break;
                    }
                    echo json_encode($authController->updateAddress($userId, $input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'delete_address':
                if ($method == 'POST') {
                    $userId = null;
                    $token = getBearerToken();
                    if (!empty($token)) {
                        $result = $authController->verifyToken($token);
                        if ($result['success'] && !empty($result['user']['id'])) {
                            $userId = $result['user']['id'];
                        }
                    }
                    if (!$userId && !empty($input['user_id'])) {
                        $userId = $input['user_id'];
                    }
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token or user_id provided']);
                        break;
                    }
                    echo json_encode($authController->deleteAddress($userId, $input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'set_default_address':
                if ($method == 'POST') {
                    $userId = null;
                    $token = getBearerToken();
                    if (!empty($token)) {
                        $result = $authController->verifyToken($token);
                        if ($result['success'] && !empty($result['user']['id'])) {
                            $userId = $result['user']['id'];
                        }
                    }
                    if (!$userId && !empty($input['user_id'])) {
                        $userId = $input['user_id'];
                    }
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'No token or user_id provided']);
                        break;
                    }
                    echo json_encode($authController->setDefaultAddress($userId, $input));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            // === ORDER ENDPOINTS ===
            case 'get_orders':
                if ($method == 'GET') {
                    // Scope to the token holder. Previously this passed
                    // $queryParams straight into getAllOrders($page, ...) — the
                    // filters were dropped and every customer's orders came back.
                    $auth = $authController->verifyToken(getBearerToken());
                    if (!$auth['success']) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                        break;
                    }
                    echo json_encode($orderController->getOrdersForUser($auth['user']['id'], $queryParams));
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            case 'get_order':
                if ($method == 'GET' && isset($queryParams['id'])) {
                    $auth = $authController->verifyToken(getBearerToken());
                    if (!$auth['success']) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                        break;
                    }
                    $result = $orderController->getOrderForUser($queryParams['id'], $auth['user']['id']);
                    if (!$result['success']) {
                        http_response_code(404);
                    }
                    echo json_encode($result);
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed or missing ID']);
                }
                break;

            case 'create_order':
                if ($method == 'POST') {
                    // Attach the order to the token holder when one is present.
                    // Guest checkout still works — the order just has no user_id.
                    $auth = $authController->verifyToken(getBearerToken());
                    $orderUserId = $auth['success'] ? $auth['user']['id'] : null;

                    $result = $orderController->createOrder($input, $orderUserId);
                    if (!$result['success']) {
                        http_response_code(400);
                    }
                    echo json_encode($result);
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                }
                break;

            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Action not found. Available actions: register, login, logout, refresh_token, forgot_password, reset_password, profile, update_profile, delete_account, soft_delete_account, get_orders, get_order, create_order, ...'
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