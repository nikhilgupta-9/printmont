<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AboutUsController.php';

// Initialize Controller
$aboutController = new AboutUsController();

try {
    // Fetch sections
    $sections = $aboutController->getAllSections();

    if (!empty($sections)) {
        http_response_code(200); // OK
        echo json_encode([
            'success' => true,
            'message' => 'About Us sections retrieved successfully',
            'data' => $sections
        ]);
    } else {
        http_response_code(404); // Not found
        echo json_encode([
            'success' => false,
            'message' => 'No About Us sections found',
            'data' => []
        ]);
    }

} catch (Exception $e) {
    http_response_code(500); // Internal server error
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
