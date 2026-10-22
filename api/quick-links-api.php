<?php
// API for Quick Links management and frontend delivery
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    // Handle GET requests (Frontend & Admin List)
    if ($method === 'GET') {
        $isAdmin = isset($_GET['admin']) && $_GET['admin'] == '1';

        $whereClause = $isAdmin ? "" : "WHERE status = 'active'";
        $query = "SELECT * FROM quick_links {$whereClause} ORDER BY display_order ASC, id ASC";
        $result = $db->query($query);

        $items = [];
        $grouped = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
            $section = $row['section_name'] ?: 'General';
            if (!isset($grouped[$section])) {
                $grouped[$section] = [
                    'section_name' => $row['section_name'],
                    'links' => []
                ];
            }
            $grouped[$section]['links'][] = $row;
        }

        echo json_encode([
            'success' => true,
            'count' => count($items),
            'sections' => array_values($grouped),
            'data' => $items
        ]);
        exit();
    }

    // Handle POST requests (Admin Mutations)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? $action;

        if ($action === 'create') {
            $section_name = trim($input['section_name'] ?? '');
            $title = trim($input['title'] ?? '');
            $subtext = trim($input['subtext'] ?? '');
            $icon_name = trim($input['icon_name'] ?? 'default');
            $url = trim($input['url'] ?? '#');
            $display_order = intval($input['display_order'] ?? 0);
            $status = in_array($input['status'] ?? '', ['active', 'inactive']) ? $input['status'] : 'active';

            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit();
            }

            $stmt = $db->prepare("INSERT INTO quick_links (section_name, title, subtext, icon_name, url, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $section_name, $title, $subtext, $icon_name, $url, $display_order, $status);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quick link added successfully', 'id' => $stmt->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create quick link: ' . $stmt->error]);
            }
            exit();
        }

        if ($action === 'update') {
            $id = intval($input['id'] ?? 0);
            $section_name = trim($input['section_name'] ?? '');
            $title = trim($input['title'] ?? '');
            $subtext = trim($input['subtext'] ?? '');
            $icon_name = trim($input['icon_name'] ?? 'default');
            $url = trim($input['url'] ?? '#');
            $display_order = intval($input['display_order'] ?? 0);
            $status = in_array($input['status'] ?? '', ['active', 'inactive']) ? $input['status'] : 'active';

            if ($id <= 0 || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID or empty title']);
                exit();
            }

            $stmt = $db->prepare("UPDATE quick_links SET section_name = ?, title = ?, subtext = ?, icon_name = ?, url = ?, display_order = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssisi", $section_name, $title, $subtext, $icon_name, $url, $display_order, $status, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quick link updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $stmt->error]);
            }
            exit();
        }

        if ($action === 'toggle_status') {
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit();
            }

            $res = $db->query("SELECT status FROM quick_links WHERE id = {$id}");
            $row = $res->fetch_assoc();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit();
            }

            $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
            $stmt = $db->prepare("UPDATE quick_links SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => "Status changed to {$newStatus}", 'new_status' => $newStatus]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to toggle status']);
            }
            exit();
        }

        if ($action === 'delete') {
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit();
            }

            $stmt = $db->prepare("DELETE FROM quick_links WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quick link deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete: ' . $stmt->error]);
            }
            exit();
        }

        if ($action === 'reorder') {
            $orders = $input['orders'] ?? []; // Array of { id, display_order }
            if (is_array($orders)) {
                $stmt = $db->prepare("UPDATE quick_links SET display_order = ? WHERE id = ?");
                foreach ($orders as $ord) {
                    $item_id = intval($ord['id'] ?? 0);
                    $item_order = intval($ord['display_order'] ?? 0);
                    if ($item_id > 0) {
                        $stmt->bind_param("ii", $item_order, $item_id);
                        $stmt->execute();
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
                exit();
            }
        }

        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
