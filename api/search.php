<?php
/**
 * GET /api/search.php
 * Handles both live suggestions and search results page queries.
 */
header('Content-Type: application/json');

// CORS setup for local & production React frontend
$allowed_origins = ['https://printmont.me', 'http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost:3000'];
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($http_origin, $allowed_origins)) {
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
