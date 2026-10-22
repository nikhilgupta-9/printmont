<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/MaintenanceController.php';
require_once __DIR__ . '/../controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$maintenanceController = new MaintenanceController($db);
$logoController = new LogoController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $settings = $maintenanceController->getSettings();
        
        // Fetch active logo for maintenance page branding
        $logoData = $logoController->getWebsiteLogo();
        $logoUrl = '';
        if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
            $logoUrl = $logoData['file_path'] . $logoData['file_name'];
        }

        $response = [
            'success'            => true,
            'is_maintenance'     => ($settings['is_enabled'] === '1' || $settings['is_enabled'] === 1 || $settings['is_enabled'] === true),
            'title'              => $settings['title'],
            'message'            => $settings['message'],
            'estimated_end_time' => $settings['estimated_end_time'],
            'contact_email'      => $settings['contact_email'],
            'contact_phone'      => $settings['contact_phone'],
            'secret_bypass_key'  => $settings['secret_bypass_key'],
            'show_countdown'     => ($settings['show_countdown'] === '1'),
            'logo_url'           => $logoUrl,
            'server_time'        => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        
        if (isset($input['toggle'])) {
            $status = !empty($input['is_enabled']);
            $res = $maintenanceController->toggleMaintenance($status);
            echo json_encode($res);
            break;
        }

        $res = $maintenanceController->updateSettings($input);
        echo json_encode($res);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
