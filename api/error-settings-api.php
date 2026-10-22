<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ErrorPageController.php';
require_once __DIR__ . '/../controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$errorController = new ErrorPageController($db);
$logoController = new LogoController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $settings = $errorController->getSettings();
        
        // Fetch active logo for error page branding
        $logoData = $logoController->getWebsiteLogo();
        $logoUrl = '';
        if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
            $logoUrl = $logoData['file_path'] . $logoData['file_name'];
        }

        $response = [
            'success'               => true,
            'badge'                 => $settings['badge'] ?? '404 Error',
            'title'                 => $settings['title'] ?? 'Oops! Page Not Found',
            'message'               => $settings['message'] ?? 'The page you are looking for has been printed elsewhere, moved, or no longer exists. Let’s get you back on track!',
            'image_url'             => $settings['image_url'] ?? 'uploads/error-pages/404-printmont-error.jpg',
            'primary_btn_text'      => $settings['primary_btn_text'] ?? 'Back to Home',
            'primary_btn_url'       => $settings['primary_btn_url'] ?? '/',
            'secondary_btn_text'    => $settings['secondary_btn_text'] ?? 'Explore Top Products',
            'secondary_btn_url'     => $settings['secondary_btn_url'] ?? '/allproducts',
            'show_search_bar'       => ($settings['show_search_bar'] === '1' || $settings['show_search_bar'] === 1 || $settings['show_search_bar'] === true),
            'show_quick_links'      => ($settings['show_quick_links'] === '1' || $settings['show_quick_links'] === 1 || $settings['show_quick_links'] === true),
            'contact_support_text'  => $settings['contact_support_text'] ?? 'Need Help? Reach our 24x7 Customer Desk',
            'support_email'         => $settings['support_email'] ?? 'support@printmont.com',
            'support_phone'         => $settings['support_phone'] ?? '+91 9876543210',
            'logo_url'              => $logoUrl
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
        
        $file = $_FILES['error_image'] ?? null;
        $res = $errorController->updateSettings($input, $file);
        echo json_encode($res);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
