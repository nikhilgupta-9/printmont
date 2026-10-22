<?php
/**
 * Lead Inquiries API
 * 
 * Handles submission of corporate business leads and inquiries.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit();
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $fullName = trim($data['full_name'] ?? '');
    $contactNumber = trim($data['contact_number'] ?? '');
    $emailId = trim($data['email_id'] ?? '');
    $companyName = trim($data['company_name'] ?? '');
    $budget = trim($data['budget'] ?? '');
    $productRequirement = trim($data['product_requirement'] ?? '');
    $sourcePage = trim($data['source_page'] ?? 'business-solutions');
    $deviceType = trim($data['device_type'] ?? 'desktop');

    // Validation
    if (empty($fullName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide your full name.']);
        exit();
    }

    if (empty($contactNumber)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide your contact phone number.']);
        exit();
    }

    if (empty($emailId) || !filter_var($emailId, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide a valid corporate email address.']);
        exit();
    }

    $stmt = $db->prepare("INSERT INTO lead_inquiries (full_name, contact_number, company_name, email_id, budget, product_requirement, source_page, device_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
    $stmt->bind_param('ssssssss', $fullName, $contactNumber, $companyName, $emailId, $budget, $productRequirement, $sourcePage, $deviceType);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error while saving lead.');
    }

    $leadId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your business inquiry has been received. Our corporate team will reach out within 25 minutes.',
        'lead_id' => $leadId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process inquiry: ' . $e->getMessage()
    ]);
}
