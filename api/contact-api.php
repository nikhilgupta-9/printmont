<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ContactController.php';

// Initialize Contact Controller
$contactController = new ContactController();

try {
    // Get contact information
    $contactInfo = $contactController->getContactInfoForAPI();
    
    if ($contactInfo['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Contact information retrieved successfully',
            'data' => $contactInfo['data']
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Contact information not found',
            'data' => null
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>