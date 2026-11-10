<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

/**
 * Active content blocks for one page, in display order.
 *
 *   GET content-blocks-api.php?page=home
 *
 * Each block carries its `pattern` and its `data` (decoded from the
 * stored JSON), so the frontend just needs a renderer per pattern.
 */
try {
    $pageKey = trim($_GET['page'] ?? '');

    if ($pageKey === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'A page key is required, e.g. ?page=home',
            'data' => null,
        ]);
        exit;
    }

    $db = (new Database())->getConnection();

    $stmt = $db->prepare(
        'SELECT id, page_key, pattern, title, data, display_order
         FROM content_blocks
         WHERE page_key = ? AND is_active = 1
         ORDER BY display_order ASC, id ASC'
    );

    if (!$stmt) {
        throw new Exception('Could not prepare the query.');
    }

    $stmt->bind_param('s', $pageKey);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['display_order'] = (int) $row['display_order'];
        $row['data'] = json_decode($row['data'], true) ?: [];
        $rows[] = $row;
    }
    $stmt->close();

    http_response_code(200);
    echo json_encode([
        'success'  => true,
        'page_key' => $pageKey,
        'data'     => $rows,
        'count'    => count($rows),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => null,
    ]);
}
