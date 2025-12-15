<?php
// api/banners/home.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../controllers/ApiBannerController.php');

$bannerController = new ApiBannerController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    $bannerController->handleOptions();
    exit;
}

if ($method === 'GET') {
    $bannerController->getMiddleSectionFive();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>