<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../controllers/HomeLayoutController.php');
$controller = new HomeLayoutController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $target = $_GET['target'] ?? 'desktop';
    if (!in_array($target, ['desktop', 'mobile'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid target.']);
        exit;
    }

    // For public frontend API, only fetch active sections
    $db = new Database();
    $layoutModel = new HomeLayoutModel();
    $sections = $layoutModel->getActiveSections($target);

    // Fetch banners for slider/banner type sections
    $bannerModel = new BannerModel();
    foreach ($sections as &$section) {
        if ($section['section_type'] === 'slider' || $section['section_type'] === 'banner') {
            $sectionDbRow = $db->fetch(
                "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                [$section['section_key']]
            );
            if ($sectionDbRow) {
                // Return formatted banner objects
                $banners = $bannerModel->getBannersBySection((int)$sectionDbRow['id']);
                $formattedBanners = [];
                foreach ($banners as $b) {
                    $formattedBanners[] = [
                        'id' => (int)$b['id'],
                        'title' => $b['title'],
                        'description' => $b['description'],
                        'large' => $b['image_url_desktop'],
                        'small' => $b['image_url_mobile'],
                        'target' => $b['target_url'],
                        'display_order' => (int)$b['display_order']
                    ];
                }
                $section['banners'] = $formattedBanners;
            } else {
                $section['banners'] = [];
            }
        } else {
            $section['banners'] = [];
        }
    }

    echo json_encode(['success' => true, 'data' => $sections]);
    exit;
}

// For POST actions, verify admin session
$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin login required.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'reorder') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No IDs provided for reordering.']);
        exit;
    }
    $res = $controller->reorderSections($ids);
    echo json_encode($res);
    exit;
}

if ($action === 'toggle') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    if (!$id || empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID and status are required.']);
        exit;
    }
    $res = $controller->toggleSection($id, $status);
    echo json_encode($res);
    exit;
}

if ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Section ID is required for deletion.']);
        exit;
    }
    $res = $controller->deleteSection($id);
    echo json_encode($res);
    exit;
}

if ($action === 'create') {
    // Multi-part form post
    $res = $controller->createSection($_POST, $_FILES);
    echo json_encode($res);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Section ID is required for update.']);
        exit;
    }
    $res = $controller->updateSection($id, $_POST, $_FILES);
    echo json_encode($res);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action.']);
exit;
?>
