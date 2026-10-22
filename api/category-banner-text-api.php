<?php
// api/category-banner-text-api.php
// GET ?product_id=364      -> active banner texts for the product's category / sub / sub-sub category
// GET ?category_id=440     -> active banner texts for one category
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/category-banner-texts-schema.php');

$database = new Database();
$db = $database->getConnection();
ensureCategoryBannerTextsTable($db);

$categoryIds = [];

$productId = (int)($_GET['product_id'] ?? 0);
if ($productId > 0) {
    $stmt = $db->prepare("SELECT category_id, sub_category_id, sub_sub_category_id FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product) {
        foreach (['category_id', 'sub_category_id', 'sub_sub_category_id'] as $col) {
            if (!empty($product[$col])) $categoryIds[] = (int)$product[$col];
        }
    }
}

if (!empty($_GET['category_id'])) {
    foreach (explode(',', $_GET['category_id']) as $cid) {
        if ((int)$cid > 0) $categoryIds[] = (int)$cid;
    }
}

$categoryIds = array_values(array_unique($categoryIds));

if (empty($categoryIds)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// IDs are cast to int above, so inlining them is safe
$idList = implode(',', $categoryIds);
$result = $db->query("SELECT id, category_id, title, description, button_text, button_link, image, bg_color, text_color, display_order
    FROM category_banner_texts
    WHERE status = 'active' AND category_id IN ($idList)
    ORDER BY display_order ASC, id ASC");

$data = [];
while ($result && ($row = $result->fetch_assoc())) {
    $row['id'] = (int)$row['id'];
    $row['category_id'] = (int)$row['category_id'];
    $row['display_order'] = (int)$row['display_order'];
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
