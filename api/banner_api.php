<?php
/**
 * Unified Banner API
 *
 * GET /api/banner_api.php?page=home          → all sections for the home page
 * GET /api/banner_api.php?page=blog          → all sections for the blog page
 * GET /api/banner_api.php?page=product       → all sections for the product page
 * GET /api/banner_api.php?section=home_hero  → one specific section by key
 * GET /api/banner_api.php?action=sections    → full section list (for admin)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../controllers/ApiBannerController.php');

$ctrl   = new ApiBannerController();
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

$action  = $_GET['action']  ?? '';
$page    = $_GET['page']    ?? '';
$section = $_GET['section'] ?? '';

if ($action === 'sections') {
    $ctrl->getSectionsList();
} elseif ($section !== '') {
    $ctrl->getBySection();
} elseif ($page !== '') {
    $ctrl->getByPage();
} else {
    // Default: return home page banners
    $_GET['page'] = 'home';
    $ctrl->getByPage();
}
?>
