<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../controllers/PolicyController.php');

$policyController = new PolicyController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    $policyController->handleOptions();
    exit;
}

if ($method === 'GET') {
    try {
        $policies = $policyController->getAllPoliciesApi();
        
        // Check if policies is false or empty
        if ($policies === false) {
            throw new Exception('Failed to fetch policies');
        }
        
        echo json_encode([
            'success' => true, 
            'data' => $policies,
            'count' => count($policies)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>