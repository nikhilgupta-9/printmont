<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control, Pragma');
header('Access-Control-Max-Age: 86400');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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

        if ($action === 'save_category_image_settings') {
            $shape = $input['category_image_shape'] ?? $input['shape'] ?? 'circle';
            $size = (int)($input['category_image_size'] ?? $input['size'] ?? 56);
            $updated = $controller->updateCategoryImageSettings($shape, $size);
            echo json_encode([
                'success' => $updated,
                'message' => $updated ? 'Category image appearance settings updated.' : 'Failed to update settings.'
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
