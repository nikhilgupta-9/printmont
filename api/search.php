<?php
/**
 * GET /api/search.php
 * Handles both live suggestions and search results page queries.
 */
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

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
