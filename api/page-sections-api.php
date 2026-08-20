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
 * Sections for a CMS-driven marketing page.
 *
 *   GET page-sections-api.php?page=affiliate
 *
 * Returns the rows both flat and grouped by section_type, so a caller can
 * pick whichever shape suits it without regrouping client-side.
 */
try {
    $pageKey = trim($_GET['page'] ?? '');

    if ($pageKey === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'A page key is required, e.g. ?page=affiliate',
            'data' => null,
        ]);
        exit;
    }

    $db = (new Database())->getConnection();

    $stmt = $db->prepare(
        "SELECT id, page_key, section_type, title, content, extra, image_path, display_order
         FROM page_sections
         WHERE page_key = ? AND is_active = 1
         ORDER BY display_order ASC, id ASC"
    );

    if (!$stmt) {
        throw new Exception('Could not prepare the query.');
    }

    $stmt->bind_param('s', $pageKey);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $grouped[$row['section_type']][] = $row;
    }
    $stmt->close();

    http_response_code(200);
    echo json_encode([
        'success'  => true,
        'message'  => 'Page sections retrieved successfully',
        'page_key' => $pageKey,
        'data'     => $rows,
        'sections' => $grouped,
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
