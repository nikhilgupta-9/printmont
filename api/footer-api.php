<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/LogoController.php';
require_once __DIR__ . '/../controllers/FooterStylingController.php';

$database = new Database();
$db = $database->getConnection();
$stylingController = new FooterStylingController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'HEAD':
    case 'GET':
        // 1. Fetch Sections and their Links
        $sections = [];
        $secQuery = "SELECT id, title, column_order, display_order, status FROM footer_sections WHERE status = 'active' ORDER BY column_order ASC, display_order ASC, id ASC";
        $secRes = $db->query($secQuery);

        if ($secRes && $secRes->num_rows > 0) {
            while ($sec = $secRes->fetch_assoc()) {
                $secId = (int)$sec['id'];
                
                // Fetch links for this section
                $linkQuery = "SELECT id, section_id, title, url, link_order, display_order, status FROM footer_links WHERE section_id = {$secId} AND status = 'active' ORDER BY link_order ASC, display_order ASC, id ASC";
                $linkRes = $db->query($linkQuery);
                
                $links = [];
                if ($linkRes && $linkRes->num_rows > 0) {
                    while ($l = $linkRes->fetch_assoc()) {
                        $links[] = [
                            'id'    => (int)$l['id'],
                            'title' => $l['title'],
                            'url'   => $l['url']
                        ];
                    }
                }
                
                $sec['links'] = $links;
                $sections[] = $sec;
            }
        }

        // 2. Fetch Company Info
        $companyInfo = null;
        $compQuery = "SELECT id, company_name, address, customer_care, newsletter_text, copyright_text, status FROM footer_company_info WHERE status = 'active' LIMIT 1";
        $compRes = $db->query($compQuery);
        if ($compRes && $compRes->num_rows > 0) {
            $companyInfo = $compRes->fetch_assoc();
        } else {
            // Default fallback
            $companyInfo = [
                'company_name'   => 'Xordox International Pvt. Ltd',
                'address'        => "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India.",
                'customer_care'  => '+91-9818532463',
                'newsletter_text'=> 'Get updates on new products and offers. Coupons.',
                'copyright_text' => '© ' . date('Y') . ' printmont.com All Rights Reserved.'
            ];
        }

        // 3. Fetch Social Links
        $socialLinks = [];
        $socQuery = "SELECT id, platform, url, icon, display_order, status FROM footer_social_links WHERE status = 'active' ORDER BY display_order ASC, id ASC";
        $socRes = $db->query($socQuery);
        if ($socRes && $socRes->num_rows > 0) {
            while ($s = $socRes->fetch_assoc()) {
                $socialLinks[] = [
                    'id'       => (int)$s['id'],
                    'platform' => $s['platform'],
                    'url'      => $s['url'],
                    'icon'     => $s['icon']
                ];
            }
        }

        // 4. Fetch Bottom Links
        $bottomLinks = [];
        $botQuery = "SELECT id, title, url, icon, link_order, status FROM footer_bottom_links WHERE status = 'active' ORDER BY link_order ASC, id ASC";
        $botRes = $db->query($botQuery);
        if ($botRes && $botRes->num_rows > 0) {
            while ($b = $botRes->fetch_assoc()) {
                $bottomLinks[] = [
                    'id'    => (int)$b['id'],
                    'title' => $b['title'],
                    'url'   => $b['url'],
                    'icon'  => $b['icon']
                ];
            }
        }

        // 5. Fetch Payment Methods
        $paymentMethods = [];
        $payQuery = "SELECT id, name, icon, display_order, status FROM footer_payment_methods WHERE status = 'active' ORDER BY display_order ASC, id ASC";
        $payRes = $db->query($payQuery);
        if ($payRes && $payRes->num_rows > 0) {
            while ($p = $payRes->fetch_assoc()) {
                $paymentMethods[] = [
                    'id'   => (int)$p['id'],
                    'name' => $p['name'],
                    'icon' => $p['icon']
                ];
            }
        }

        // 6. Active Logo
        $logoController = new LogoController($db);
        $logoData = $logoController->getWebsiteLogo();
        $logoUrl = '';
        if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
            $logoUrl = $logoData['file_path'] . $logoData['file_name'];
        }

        // 7. Footer Colors & Theme Styling
        $styling = $stylingController->getStyling();

        echo json_encode([
            'success'         => true,
            'sections'        => $sections,
            'company_info'    => $companyInfo,
            'social_links'    => $socialLinks,
            'bottom_links'    => $bottomLinks,
            'payment_methods' => $paymentMethods,
            'logo_url'        => $logoUrl,
            'styling'         => $styling
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        if (isset($input['styling']) && is_array($input['styling'])) {
            $res = $stylingController->updateStyling($input['styling']);
            echo json_encode($res);
            break;
        }

        $res = $stylingController->updateStyling($input);
        echo json_encode($res);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
