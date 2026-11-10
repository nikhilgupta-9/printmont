<?php
// api/product-info-banner-api.php
// GET ?product_id=364 -> active image+text banners for that product (admin: product.php, section 1 point 13)
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
require_once(__DIR__ . '/../config/product-info-banners-schema.php');

$database = new Database();
$db = $database->getConnection();
ensureProductInfoBannersTable($db);

$productId = (int)($_GET['product_id'] ?? 0);
if ($productId <= 0) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$stmt = $db->prepare("SELECT id, title, description, button_text, button_link, image, display_order
    FROM product_info_banners
    WHERE product_id = ? AND status = 'active'
    ORDER BY display_order ASC, id ASC");
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['display_order'] = (int)$row['display_order'];
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
