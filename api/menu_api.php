<?php
/**
 * Unified Menu API
 *
 * GET /api/menu_api.php?type=home                    → Home Page Menu, desktop + mobile
 * GET /api/menu_api.php?type=home&device=desktop      → Home Page Menu, desktop only
 * GET /api/menu_api.php?type=home&device=mobile       → Home Page Menu, mobile only
 * GET /api/menu_api.php?type=inner                    → Inner Page Menu (header top-menu tree)
 *
 * Home Page Menu   = categories flagged desktop_home_show / mobile_home_show = 'yes'
 *                     (the category tiles/sections shown ON the home page)
 * Inner Page Menu  = categories flagged desktop_menu_status / mobile_topbar_status = 'show'
 *                     (the persistent header top-menu shown across all pages)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../controllers/ApiMenuController.php');

$ctrl   = new ApiMenuController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    $ctrl->handleOptions();
    exit;
}

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$type = $_GET['type'] ?? 'home';

if ($type === 'inner') {
    $ctrl->getInnerMenu();
} else {
    $ctrl->getHomeMenu();
}
?>
