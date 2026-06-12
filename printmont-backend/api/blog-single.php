<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/BlogPostController.php';

// Initialize Controller
$postController = new BlogPostController();

try {
    // Get slug from URL parameter
    $slug = $_GET['slug'] ?? '';

    if (empty($slug)) {
        http_response_code(400); // Bad request
        echo json_encode([
            'success' => false,
            'message' => 'Slug parameter is required',
            'data' => null
        ]);
        exit();
    }

    // Fetch post by slug
    $post = $postController->getPostBySlug($slug);

    if ($post && $post['status'] === 'published') {
        // Increment views when post is accessed
        $postController->incrementViews($post['id']);
        
        http_response_code(200); // OK
        echo json_encode([
            'success' => true,
            'message' => 'Blog post retrieved successfully',
            'data' => $post
        ]);
    } else {
        http_response_code(404); // Not found
        echo json_encode([
            'success' => false,
            'message' => 'Blog post not found',
            'data' => null
        ]);
    }

} catch (Exception $e) {
    http_response_code(500); // Internal server error
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>