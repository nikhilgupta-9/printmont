<?php
/**
 * Unified Menu API
 *
 * GET /api/menu_api.php?type=home                    → Home Page Menu tree, desktop + mobile
 * GET /api/menu_api.php?type=home&device=desktop      → Home Page Menu tree, desktop only
 * GET /api/menu_api.php?type=home&device=mobile       → Home Page Menu tree, mobile only
 * GET /api/menu_api.php?type=inner                    → Inner Page Menu (header top-menu tree)
 *
 * Both are returned as the FULL 3-level category tree (Main → Sub → Sub-Sub),
 * unpruned — every active category appears. Each node carries a flag
 * (shown_on_home, or desktop/mobile.status for inner) telling the caller
 * whether that specific category is actually meant to render there.
 *
 * Home Page Menu   = tree with shown_on_home = desktop_home_show / mobile_home_show = 'yes'
 *                     (the category tiles/sections shown ON the home page)
 * Inner Page Menu  = tree pruned only where BOTH desktop_menu_status and
 *                     mobile_topbar_status = 'hide' (the persistent header top-menu)
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
