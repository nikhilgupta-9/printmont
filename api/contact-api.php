<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Browsers preflight the POST because of the JSON content type.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ContactController.php';

$contactController = new ContactController();

try {
    /* ------------------------------------------------------------------
       POST — public contact form submission, stored in contact_inquiries
       and read back by contact-inquiries.php in the admin panel.
       ------------------------------------------------------------------ */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        // Fall back to form-encoded so a plain <form> post works too.
        if (!is_array($input) || empty($input)) {
            $input = $_POST;
        }

        $result = $contactController->submitInquiry($input);

        if (!$result['success']) {
            http_response_code(($result['reason'] ?? '') === 'invalid' ? 400 : 500);
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Could not submit your message.',
            ]);
            exit;
        }

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'data'    => [
                'id' => $result['id'],
                // The enquiry is saved either way; this reports whether the
                // acknowledgement email went out, so a mail misconfiguration
                // is visible instead of failing silently.
                'mail_sent' => $result['mail_sent'] ?? false,
            ],
        ]);
        exit;
    }

    /* ------------------------------------------------------------------
       GET — contact details shown on the storefront Contact page
       ------------------------------------------------------------------ */
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
