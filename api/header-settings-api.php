<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/HeaderSettingsController.php';

try {
    $controller = new HeaderSettingsController();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $config = $controller->getFrontendConfig();
        echo json_encode([
            'success' => true,
            'data' => $config
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? '';

        if ($action === 'toggle') {
            $id = (int)($input['id'] ?? 0);
            $field = $input['field'] ?? '';
            $val = $input['value'] ?? '';

            $updated = $controller->toggleField($id, $field, $val);
            echo json_encode([
                'success' => $updated,
                'message' => $updated ? 'Setting updated successfully.' : 'Failed to update setting.'
            ]);
            exit;
        }

        if ($action === 'update_rule') {
            $id = (int)($input['id'] ?? 0);
            $updated = $controller->updatePageSetting($id, $input);
            echo json_encode([
                'success' => $updated,
                'message' => $updated ? 'Page rule updated successfully.' : 'Failed to update rule.'
            ]);
            exit;
        }

        if ($action === 'add_page_rule') {
            $created = $controller->createPageRule($input);
            echo json_encode([
                'success' => $created,
                'message' => $created ? 'New page rule created successfully.' : 'Failed to create page rule.'
            ]);
            exit;
        }

        if ($action === 'delete_page_rule') {
            $id = (int)($input['id'] ?? 0);
            $deleted = $controller->deletePageRule($id);
            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Page rule removed successfully.' : 'Failed to delete page rule.'
            ]);
            exit;
        }

        if ($action === 'reset_defaults' || $action === 'reset_all_pages') {
            $controller->reseedAllPages();
            echo json_encode([
                'success' => true,
                'message' => 'All individual page rules restored to defaults.'
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'message' => 'Unknown action.'
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
