<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Fetch Settings
        $settings_res = $conn->query("SELECT setting_key, setting_value FROM gift_finder_settings WHERE status = 'active'");
        $settings = [];
        while ($row = $settings_res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // Fetch Options
        $options_res = $conn->query("SELECT id, question, option_type, options, status FROM gift_finder_options WHERE status = 'active' ORDER BY display_order ASC");
        $occasions = [];
        $gift_types = [];

        while ($row = $options_res->fetch_assoc()) {
            $parsed_options = json_decode($row['options'], true) ?: [];
            if (strtolower(trim($row['question'])) === 'occasion') {
                $occasions = $parsed_options;
            } elseif (strtolower(trim($row['question'])) === 'gift type' || strtolower(trim($row['question'])) === 'gift types') {
                $gift_types = $parsed_options;
            }
        }

        // Fallbacks if empty
        if (empty($occasions)) {
            $occasions = [
                ['id' => 'corporate', 'label' => 'Corporate & Business', 'query' => 'Corporate'],
                ['id' => 'birthday', 'label' => 'Birthday Celebration', 'query' => 'Birthday'],
                ['id' => 'anniversary', 'label' => 'Anniversary & Milestones', 'query' => 'Anniversary'],
                ['id' => 'festival', 'label' => 'Festivals & Diwali', 'query' => 'Festive'],
                ['id' => 'employee', 'label' => 'Employee Welcome Kit', 'query' => 'Executive'],
                ['id' => 'farewell', 'label' => 'Farewell & Appreciation', 'query' => 'Gifts']
            ];
        }

        if (empty($gift_types)) {
            $gift_types = [
                ['id' => 'apparel', 'label' => 'Custom Apparel & T-Shirts', 'category_id' => '47', 'query' => 'Shirt'],
                ['id' => 'tech', 'label' => 'Tech & Smart Gadgets', 'category_id' => '84', 'query' => 'Tech'],
                ['id' => 'drinkware', 'label' => 'Drinkware & Flasks', 'category_id' => '31', 'query' => 'Mug'],
                ['id' => 'eco', 'label' => 'Eco-Friendly & Plants', 'category_id' => '115', 'query' => 'Eco'],
                ['id' => 'personalized', 'label' => 'Personalized Gifts', 'category_id' => '1', 'query' => 'Personalized'],
                ['id' => 'hampers', 'label' => 'Corporate Gift Hampers', 'category_id' => '17', 'query' => 'Gift Kit']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'enabled' => isset($settings['enabled']) ? (bool)(int)$settings['enabled'] : true,
                'title' => $settings['title'] ?? 'Find Gift for your Business and Loved Ones',
                'subtitle' => $settings['subtitle'] ?? 'Curated custom gifts and corporate merchandise delivered across India',
                'pincode_required' => isset($settings['pincode_required']) ? (bool)(int)$settings['pincode_required'] : false,
                'occasions' => $occasions,
                'gift_types' => $gift_types
            ]
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // 1. Update Settings
        if (isset($input['title'])) {
            $title = $conn->real_escape_string(trim($input['title']));
            $conn->query("INSERT INTO gift_finder_settings (setting_key, setting_value, setting_type, status) VALUES ('title', '$title', 'text', 'active') ON DUPLICATE KEY UPDATE setting_value = '$title'");
        }
        if (isset($input['subtitle'])) {
            $subtitle = $conn->real_escape_string(trim($input['subtitle']));
            $conn->query("INSERT INTO gift_finder_settings (setting_key, setting_value, setting_type, status) VALUES ('subtitle', '$subtitle', 'text', 'active') ON DUPLICATE KEY UPDATE setting_value = '$subtitle'");
        }
        if (isset($input['enabled'])) {
            $enabled = $input['enabled'] ? '1' : '0';
            $conn->query("INSERT INTO gift_finder_settings (setting_key, setting_value, setting_type, status) VALUES ('enabled', '$enabled', 'boolean', 'active') ON DUPLICATE KEY UPDATE setting_value = '$enabled'");
        }

        // 2. Update Occasions
        if (isset($input['occasions']) && is_array($input['occasions'])) {
            $occ_json = $conn->real_escape_string(json_encode(array_values($input['occasions'])));
            $chk = $conn->query("SELECT id FROM gift_finder_options WHERE question = 'Occasion'")->fetch_assoc();
            if ($chk) {
                $conn->query("UPDATE gift_finder_options SET options = '$occ_json' WHERE id = " . (int)$chk['id']);
            } else {
                $conn->query("INSERT INTO gift_finder_options (question, option_type, options, display_order, status) VALUES ('Occasion', 'single', '$occ_json', 1, 'active')");
            }
        }

        // 3. Update Gift Types
        if (isset($input['gift_types']) && is_array($input['gift_types'])) {
            $gt_json = $conn->real_escape_string(json_encode(array_values($input['gift_types'])));
            $chk = $conn->query("SELECT id FROM gift_finder_options WHERE question = 'Gift Type'")->fetch_assoc();
            if ($chk) {
                $conn->query("UPDATE gift_finder_options SET options = '$gt_json' WHERE id = " . (int)$chk['id']);
            } else {
                $conn->query("INSERT INTO gift_finder_options (question, option_type, options, display_order, status) VALUES ('Gift Type', 'single', '$gt_json', 2, 'active')");
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Gift Finder settings updated successfully'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported request method']);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing gift finder API: ' . $e->getMessage()
    ]);
}
