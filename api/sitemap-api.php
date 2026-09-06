<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/SitemapController.php');

try {
    $sitemapController = new SitemapController();
    $grouped = $sitemapController->getGroupedEntries(true);
    $stats = $sitemapController->getSitemapStats();

    echo json_encode([
        'success' => true,
        'data' => [
            'sections' => $grouped,
            'stats' => $stats,
            'xml_url' => 'sitemap.xml'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => []
    ]);
}
