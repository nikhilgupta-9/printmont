<?php
/**
 * GET /api/category-products-api.php
 * Handles fetching products scoped to a category/subcategory with facets and pagination.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/cors.php';

try {
    require_once __DIR__ . '/../services/SearchService.php';
    $searchService = new SearchService();

    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $params = $_GET;

    $results = $searchService->getSearchResults($params);

    echo json_encode([
        'success' => true,
        'category_id' => $categoryId,
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Category products query failed: ' . $e->getMessage()
    ]);
}
