<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/SpecialOfferBannerController.php';

$controller = new SpecialOfferBannerController();

$target = $_GET['target'] ?? 'all'; // 'desktop', 'mobile', 'all'
$type   = $_GET['type'] ?? 'all';   // 'banner', 'carousel', 'all'

try {
    $banners = $controller->getActiveBannersForApi($target, $type);

    // Format response
    $formatted = array_map(function($b) {
        return [
            'id'              => (int)$b['id'],
            'title'           => $b['title'],
            'subtitle'        => $b['subtitle'],
            'offer_badge'     => $b['offer_badge'],
            'badge_color'     => $b['badge_color'] ?: '#ef4444',
            'display_type'    => $b['display_type'],
            'layout_format'   => $b['layout_format'],
            'device_target'   => $b['device_target'],
            'image_desktop'   => $b['image_desktop'],
            'image_mobile'    => $b['image_mobile'] ?: $b['image_desktop'],
            'link_url'        => $b['link_url'] ?: '/allproducts?filter=discount',
            'open_in_new_tab' => (bool)$b['open_in_new_tab'],
            'start_date'      => $b['start_date'],
            'end_date'        => $b['end_date'],
            'sort_order'      => (int)$b['sort_order']
        ];
    }, $banners);

    echo json_encode([
        'success' => true,
        'count'   => count($formatted),
        'data'    => $formatted
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
