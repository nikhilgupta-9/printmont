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

require_once(__DIR__ . '/../controllers/ProductLayoutController.php');
$controller = new ProductLayoutController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $target = $_GET['target'] ?? 'mobile';
    $action = $_GET['action'] ?? '';

    if ($action === 'all') {
        // Admin view: returns all sections including inactive
        $sections = $controller->getSections($target);
        echo json_encode(['success' => true, 'data' => $sections]);
        exit;
    }

    // Default public/frontend consumption: returns only active sections
    $sections = $controller->getActiveSections($target);
    echo json_encode(['success' => true, 'data' => $sections]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'toggle_status':
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $result = $controller->toggleSection($id, $status);
            echo json_encode($result);
            exit;

        case 'reorder':
            $sectionIds = $input['section_ids'] ?? [];
            if (!is_array($sectionIds) || empty($sectionIds)) {
                echo json_encode(['success' => false, 'error' => 'Invalid section IDs array.']);
                exit;
            }
            $result = $controller->reorderSections($sectionIds);
            echo json_encode($result);
            exit;

        case 'create':
            $result = $controller->createSection($input);
            echo json_encode($result);
            exit;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            $result = $controller->updateSection($id, $input);
            echo json_encode($result);
            exit;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            $result = $controller->deleteSection($id);
            echo json_encode($result);
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            exit;
    }
}
