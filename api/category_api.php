<?php
/**
 * Unified Category API
 *
 * GET /api/category_api.php?action=categories                    → main categories (level 1)
 * GET /api/category_api.php?action=subcategories&parent_id=14     → sub categories (level 2), optionally filtered by parent
 * GET /api/category_api.php?action=subsubcategories&parent_id=75  → sub-sub categories (level 3), optionally filtered by parent
 * GET /api/category_api.php?action=tree                           → full 3-level hierarchy, one call
 * GET /api/category_api.php?id=14                                 → single category by id
 * GET /api/category_api.php?slug=electronics                      → single category by slug
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../controllers/ApiCategoryController.php');

$ctrl   = new ApiCategoryController();
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

$action = $_GET['action'] ?? '';

if (isset($_GET['id']) || isset($_GET['slug'])) {
    $ctrl->getOne();
} elseif ($action === 'subcategories') {
    $ctrl->getSubCategories();
} elseif ($action === 'subsubcategories') {
    $ctrl->getSubSubCategories();
} elseif ($action === 'tree') {
    $ctrl->getTree();
} else {
    // Default: main categories
    $ctrl->getCategories();
}
?>
