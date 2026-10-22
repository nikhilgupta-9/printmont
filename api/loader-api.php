<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/LoaderController.php';
require_once __DIR__ . '/../controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$loaderController = new LoaderController($db);
$logoController = new LogoController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $settings = $loaderController->getSettings();
        
        // Fetch active logo for brand loader
        $logoData = $logoController->getWebsiteLogo();
        $logoUrl = '';
        if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
            $logoUrl = $logoData['file_path'] . $logoData['file_name'];
        }

        $response = [
            'success'             => true,
            'is_enabled'          => ($settings['is_enabled'] === '1' || $settings['is_enabled'] === 1 || $settings['is_enabled'] === true),
            'loader_type'         => $settings['loader_type'] ?? 'brand_logo_pulse',
            'custom_loader_image' => $settings['custom_loader_image'] ?? '',
            'loading_text'        => $settings['loading_text'] ?? 'Printing Ideas Into Reality...',
            'show_text'           => ($settings['show_text'] === '1' || $settings['show_text'] === 1 || $settings['show_text'] === true),
            'bg_color'            => $settings['bg_color'] ?? '#ffffff',
            'bg_opacity'          => $settings['bg_opacity'] ?? '0.96',
            'accent_color'        => $settings['accent_color'] ?? '#0B53A1',
            'secondary_color'     => $settings['secondary_color'] ?? '#f8991d',
            'min_duration'        => (int)($settings['min_duration'] ?? 500),
            'blur_backdrop'       => ($settings['blur_backdrop'] === '1' || $settings['blur_backdrop'] === 1 || $settings['blur_backdrop'] === true),
            'preset'              => $settings['preset'] ?? 'brand_pulse',
            'logo_url'            => $logoUrl
        ];

        echo json_encode($response);
        break;

    case 'POST':
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents("php://input");
            if (!empty($raw)) {
                $input = json_decode($raw, true) ?? [];
            }
        }
        
        $file = $_FILES['loader_file'] ?? null;
        $res = $loaderController->updateSettings($input, $file);
        echo json_encode($res);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
