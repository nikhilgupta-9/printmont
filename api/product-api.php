<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/cors.php');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/ProductController.php');

$database = new Database();
$db = $database->getConnection(); // mysqli connection (not used directly)
$productController = new ProductController($db);

$id     = $_GET['id']     ?? null;
$slug   = $_GET['slug']   ?? null;
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

// Category trail for the product page breadcrumb (Home > Category > Sub > Sub-sub > Product).
// Walks up parent_id from the deepest category the product is assigned to.
function attachBreadcrumb($database, &$product) {
    $startId = 0;
    foreach (['sub_sub_category_id', 'sub_category_id', 'category_id'] as $col) {
        if (!empty($product[$col])) { $startId = (int)$product[$col]; break; }
    }

    $trail = [];
    $seen  = [];
    while ($startId > 0 && !isset($seen[$startId]) && count($trail) < 6) {
        $seen[$startId] = true;
        $rows = $database->fetchAll("SELECT id, name, slug, parent_id FROM categories WHERE id = ?", [$startId]);
        if (empty($rows)) break;
        $cat = $rows[0];
        array_unshift($trail, [
            'id'   => (int)$cat['id'],
            'name' => $cat['name'],
            'slug' => $cat['slug'],
        ]);
        $startId = (int)$cat['parent_id'];
    }

    $product['breadcrumb'] = $trail;
}

try {
    if ($id !== null || $slug !== null) {
        $product = null;
        if ($id !== null && is_numeric($id)) {
            $product = $productController->getProductByIdApi($id);
        }
        if (!$product && ($slug !== null || ($id !== null && !is_numeric($id)))) {
            $lookupSlug = $slug !== null ? $slug : $id;
            $product = $productController->getProductBySlugApi($lookupSlug);
        }

        if ($product) {
            attachImages($database, $product);
            attachBreadcrumb($database, $product);
            echo json_encode([
                "success" => true,
                "data"    => $product,
                "product" => $product
            ]);
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
