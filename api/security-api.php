<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SecurityController.php';

try {
    $controller = new SecurityController();
    $result = $controller->getSectionsApi();

    http_response_code($result['success'] ? 200 : 500);
    echo json_encode([
        'success' => $result['success'],
        'message' => $result['success'] ? 'Security sections retrieved successfully' : ($result['message'] ?? 'Error'),
        'data'    => $result['data'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data'    => [],
    ]);
}
