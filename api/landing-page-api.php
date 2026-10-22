<?php
/**
 * Dynamic Landing Page API
 * 
 * Delivers device-specific, ordered landing page JSON payloads.
 * Supports slug lookup, device filtering (mobile vs desktop), and full SEO data.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $slug = trim($_GET['slug'] ?? 'business-solutions');
    $device = strtolower(trim($_GET['device'] ?? 'desktop'));
    if (!in_array($device, ['mobile', 'desktop', 'both'])) {
        $device = 'desktop';
    }

    // 1. Fetch Landing Page
    $pageStmt = $db->prepare("SELECT id, page_name, slug, meta_title, meta_description, meta_keywords, status FROM landing_pages WHERE slug = ? AND status = 'active' LIMIT 1");
    $pageStmt->bind_param('s', $slug);
    $pageStmt->execute();
    $page = $pageStmt->get_result()->fetch_assoc();
    $pageStmt->close();

    if (!$page) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Landing page not found or inactive.'
        ]);
        exit();
    }

    $pageId = (int)$page['id'];

    // 2. Fetch Sections ordered by display_order
    $secSql = "SELECT id, section_key, section_title, section_subtitle, device_type, display_order, is_active, extra_config_json 
               FROM landing_page_sections 
               WHERE page_id = ? AND is_active = 1 AND (device_type = 'both' OR device_type = ?) 
               ORDER BY display_order ASC, id ASC";
    $secStmt = $db->prepare($secSql);
    $secStmt->bind_param('is', $pageId, $device);
    $secStmt->execute();
    $secResult = $secStmt->get_result();

    $sections = [];
    while ($row = $secResult->fetch_assoc()) {
        $extra = [];
        if (!empty($row['extra_config_json'])) {
            $decoded = json_decode($row['extra_config_json'], true);
            if (is_array($decoded)) {
                $extra = $decoded;
            }
        }

        $sections[] = [
            'id' => (int)$row['id'],
            'section_key' => $row['section_key'],
            'title' => $row['section_title'],
            'subtitle' => $row['section_subtitle'],
            'device_type' => $row['device_type'],
            'display_order' => (int)$row['display_order'],
            'config' => $extra
        ];
    }
    $secStmt->close();

    // 3. Fetch FAQs
    $faqStmt = $db->prepare("SELECT id, question, answer, display_order FROM landing_page_faqs WHERE page_id = ? AND is_active = 1 ORDER BY display_order ASC, id ASC");
    $faqStmt->bind_param('i', $pageId);
    $faqStmt->execute();
    $faqResult = $faqStmt->get_result();
    $faqs = [];
    while ($faqRow = $faqResult->fetch_assoc()) {
        $faqs[] = [
            'id' => (int)$faqRow['id'],
            'question' => $faqRow['question'],
            'answer' => $faqRow['answer'],
            'display_order' => (int)$faqRow['display_order']
        ];
    }
    $faqStmt->close();

    // 4. Output Response
    $payloadData = [
        'page' => [
            'id' => (int)$page['id'],
            'name' => $page['page_name'],
            'slug' => $page['slug'],
            'meta_title' => $page['meta_title'] ?: ($page['page_name'] . ' - Printmont'),
            'meta_description' => $page['meta_description'] ?: '',
            'meta_keywords' => $page['meta_keywords'] ?: ''
        ],
        'device' => $device,
        'sections' => $sections,
        'faqs' => $faqs
    ];

    echo json_encode(array_merge([
        'success' => true,
        'data' => $payloadData
    ], $payloadData), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
