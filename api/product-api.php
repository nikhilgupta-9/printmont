<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');

$database = new Database();
$db = $database->getConnection(); // mysqli connection (not used directly)
$productController = new ProductController($db);

$id     = $_GET['id']     ?? null;
$status = $_GET['status'] ?? null;

// Use Database class methods (mysqli-based) to fetch images
function attachImages($database, &$product) {
    $baseUrl = defined('BASE_URL')
        ? rtrim(BASE_URL, '/') . '/'
        : 'https://' . $_SERVER['HTTP_HOST'] . '/';

    $rows = $database->fetchAll(
        "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC",
        [(int)$product['id']]
    );

    $images     = [];
    $primaryUrl = null;

    foreach ($rows as $img) {
        $path = $img['image_url'] ?? '';
        if (!$path) continue;
        $url = (strpos($path, 'http') === 0) ? $path : $baseUrl . ltrim($path, '/');
        $isPrimary = !empty($img['is_primary']);
        if ($isPrimary && !$primaryUrl) $primaryUrl = $url;
        $images[] = [
            'id'            => (int)$img['id'],
            'image_url'     => $url,
            'is_primary'    => (bool)$isPrimary,
            'display_order' => (int)($img['display_order'] ?? 0),
        ];
    }

    // Fallback: thumbnail_image column on products table
    if (empty($images) && !empty($product['thumbnail_image'])) {
        $path = $product['thumbnail_image'];
        $url  = (strpos($path, 'http') === 0) ? $path : $baseUrl . ltrim($path, '/');
        $primaryUrl = $url;
        $images[] = ['id' => 0, 'image_url' => $url, 'is_primary' => true, 'display_order' => 0];
    }

    if (!$primaryUrl && !empty($images)) $primaryUrl = $images[0]['image_url'];

    $product['images']        = $images;
    $product['primary_image'] = $primaryUrl;
    $product['thumbnail']     = $primaryUrl;
}

try {
    if ($id !== null) {
        $product = $productController->getProductByIdApi($id);
        if ($product) {
            attachImages($database, $product);
            echo json_encode(["success" => true, "product" => $product]);
        } else {
            echo json_encode(["success" => false, "message" => "Product not found"]);
        }
        exit;
    }

    if ($status === 'deactive') {
        $products = $productController->getDeactiveProducts();
        foreach ($products as &$p) attachImages($database, $p);
        echo json_encode(["success" => true, "products" => $products]);
        exit;
    }

    $products = $productController->getAllProductsApi();
    foreach ($products as &$p) attachImages($database, $p);
    echo json_encode(["success" => true, "products" => $products]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
