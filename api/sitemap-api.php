<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/SitemapController.php');

try {
    $sitemapController = new SitemapController();
    
    // Check if regenerate requested
    if (isset($_GET['action']) && $_GET['action'] === 'regenerate_xml') {
        $xml = $sitemapController->generateXmlSitemap();
        echo json_encode([
            'success' => true,
            'message' => 'sitemap.xml regenerated successfully!',
            'stats' => $sitemapController->getSitemapStats()
        ]);
        exit;
    }

    $sections = $sitemapController->getGroupedEntries(true);
    $categoriesTree = $sitemapController->getLiveCategoriesTree(100);
    $stats = $sitemapController->getSitemapStats();

    echo json_encode([
        'success' => true,
        'data' => [
            'sections' => $sections,
            'categories_tree' => $categoriesTree,
            'stats' => $stats,
            'xml_url' => '/sitemap.xml'
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating sitemap: ' . $e->getMessage(),
        'data' => []
    ]);
}
