<?php
/**
 * GET /api/search.php
 * Handles both live suggestions and search results page queries.
 */
header('Content-Type: application/json');

// CORS setup for local & production React frontend
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($http_origin)) {
    header("Access-Control-Allow-Origin: $http_origin");
} else {
    header("Access-Control-Allow-Origin: *");
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../services/SearchService.php';
    $searchService = new SearchService();

    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all'; // 'suggestions' or 'full' or 'all'

    if ($type === 'suggestions') {
        $suggestions = $searchService->getLiveSuggestions($query);
        echo json_encode([
            'success' => true,
            'query' => $query,
            'suggestions' => $suggestions
        ]);
        exit();
    }

    // Default: Return both suggestions AND paginated full results
    $suggestions = $searchService->getLiveSuggestions($query);
    $fullResults = $searchService->getSearchResults($_GET);

    echo json_encode([
        'success' => true,
        'query' => $query,
        'suggestions' => $suggestions,
        'results' => $fullResults
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search operation failed: ' . $e->getMessage()
    ]);
}
