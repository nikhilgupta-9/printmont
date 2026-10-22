<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PaymentSettingsController.php';
require_once __DIR__ . '/../controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$paymentController = new PaymentSettingsController($db);
$logoController = new LogoController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $settings = $paymentController->getSettings(false); // Exclude private secret for security
        
        $logoData = $logoController->getWebsiteLogo();
        $logoUrl = '';
        if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
            $logoUrl = $logoData['file_path'] . $logoData['file_name'];
        }

        $response = [
            'success'                        => true,
            'razorpay_enabled'               => ($settings['razorpay_enabled'] === '1' || $settings['razorpay_enabled'] === 1 || $settings['razorpay_enabled'] === true),
            'razorpay_mode'                  => $settings['razorpay_mode'] ?? 'test',
            'razorpay_key_id'                => $settings['active_key_id'] ?? '',
            'cod_enabled'                    => ($settings['cod_enabled'] === '1' || $settings['cod_enabled'] === 1 || $settings['cod_enabled'] === true),
            'cod_advance_type'               => $settings['cod_advance_type'] ?? 'percentage',
            'cod_advance_percent'            => (int)($settings['cod_advance_percent'] ?? 30),
            'cod_advance_fixed_amount'       => (float)($settings['cod_advance_fixed_amount'] ?? 150),
            'cod_extra_fee'                  => (float)($settings['cod_extra_fee'] ?? 0),
            'cod_free_threshold'             => (float)($settings['cod_free_threshold'] ?? 999),
            'cod_min_amount'                 => (float)($settings['cod_min_amount'] ?? 0),
            'cod_max_amount'                 => (float)($settings['cod_max_amount'] ?? 50000),
            'cod_customized_advance_percent' => (int)($settings['cod_customized_advance_percent'] ?? 50),
            'cod_instructions'               => $settings['cod_instructions'] ?? 'Pay remaining balance upon delivery in cash or UPI QR.',
            'cod_restricted_pincodes'        => $settings['cod_restricted_pincodes'] ?? '',
            'upi_enabled'                    => ($settings['upi_enabled'] === '1' || $settings['upi_enabled'] === 1 || $settings['upi_enabled'] === true),
            'cards_enabled'                  => ($settings['cards_enabled'] === '1' || $settings['cards_enabled'] === 1 || $settings['cards_enabled'] === true),
            'netbanking_enabled'             => ($settings['netbanking_enabled'] === '1' || $settings['netbanking_enabled'] === 1 || $settings['netbanking_enabled'] === true),
            'emi_enabled'                    => ($settings['emi_enabled'] === '1' || $settings['emi_enabled'] === 1 || $settings['emi_enabled'] === true),
            'brand_name'                     => $settings['brand_name'] ?? 'Printmont',
            'theme_color'                    => $settings['theme_color'] ?? '#0B53A1',
            'logo_url'                       => $logoUrl
        ];

        echo json_encode($response);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        $action = $input['action'] ?? '';

        // 1. Verify Razorpay Payment Signature
        if ($action === 'verify_signature') {
            $fullSettings = $paymentController->getSettings(true);
            $secret = $fullSettings['active_secret'] ?? '';
            
            $orderId = $input['razorpay_order_id'] ?? '';
            $paymentId = $input['razorpay_payment_id'] ?? '';
            $signature = $input['razorpay_signature'] ?? '';

            if (empty($secret) || empty($paymentId) || empty($signature)) {
                echo json_encode(['success' => false, 'message' => 'Missing verification parameters or secret key not configured']);
                break;
            }

            $generatedSignature = hash_hmac('sha256', $orderId . "|" . $paymentId, $secret);
            if (hash_equals($generatedSignature, $signature)) {
                echo json_encode(['success' => true, 'message' => 'Payment signature verified successfully!']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid payment signature verification failed']);
            }
            break;
        }

        // 2. Update Settings (Admin request)
        $res = $paymentController->updateSettings($input);
        echo json_encode($res);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
