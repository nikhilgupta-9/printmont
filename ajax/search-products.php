<?php
session_start();
require_once '../config/database.php';
require_once '../controllers/ProductController.php';

header('Content-Type: application/json');

$database = new Database();
$db       = $database->getConnection();
$ctrl     = new ProductController($db);

$term      = trim($_GET['q'] ?? '');
$excludeId = isset($_GET['exclude']) ? (int) $_GET['exclude'] : null;

if (strlen($term) < 1) {
    echo json_encode([]);
    exit;
}

$results = $ctrl->searchProductsForAddon($term, $excludeId);

$baseUrl  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

$out = [];
foreach ($results as $p) {
    $thumb = $p['thumbnail_image'] ?? '';
    if ($thumb && strpos($thumb, 'http') !== 0) {
        $thumb = $baseUrl . $basePath . '/' . ltrim($thumb, '/');
    }
    $out[] = [
        'id'    => (int) $p['id'],
        'name'  => $p['name'],
        'sku'   => $p['sku'],
        'price' => (float) ($p['discount_price'] ?: $p['price']),
        'thumb' => $thumb,
    ];
}

echo json_encode($out);
