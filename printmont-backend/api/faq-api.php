<?php
// api/faqs/index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/FaqController.php');

// Create DB connection
$database = new Database();
$db = $database->getConnection();

// Pass DB to controller
$faqController = new FaqController($db);

$method = $_SERVER['REQUEST_METHOD'];

// Handle OPTIONS preflight
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET allowed
if ($method === 'GET') {

    // Optional filter: ?is_active=1 or 0
    $is_active = isset($_GET['is_active']) ? intval($_GET['is_active']) : null;

    $result = $faqController->getAllFAQs($is_active);

    $faqs = [];
    while ($row = $result->fetch_assoc()) {
        $faqs[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $faqs
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
