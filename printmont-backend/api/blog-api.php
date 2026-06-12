<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/BlogPostController.php';
require_once __DIR__ . '/../controllers/BlogCategoryController.php';

// Initialize Controllers
$postController = new BlogPostController();
$categoryController = new BlogCategoryController();

try {
    // Check if specific endpoint is requested
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathSegments = explode('/', trim($path, '/'));
    
    // Remove 'api' from path segments
    array_shift($pathSegments);
    $endpoint = $pathSegments[1] ?? '';
    $param = $pathSegments[2] ?? null;

    switch ($endpoint) {
        case 'posts':
            if ($param) {
                // Get single post by ID or slug
                if (is_numeric($param)) {
                    $post = $postController->getPostById($param);
                } else {
                    $post = $postController->getPostBySlug($param);
                }
                
                if ($post && $post['status'] === 'published') {
                    // Increment views
                    $postController->incrementViews($post['id']);
                    
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Blog post retrieved successfully',
                        'data' => $post
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Blog post not found',
                        'data' => null
                    ]);
                }
            } else {
                // Get all published posts
                $posts = $postController->getAllPosts('published');
                
                if (!empty($posts)) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Blog posts retrieved successfully',
                        'data' => $posts,
                        'count' => count($posts)
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No blog posts found',
                        'data' => []
                    ]);
                }
            }
            break;

        case 'categories':
            if ($param) {
                // Get single category by ID
                $category = $categoryController->getCategoryById($param);
                
                if ($category && $category['status'] === 'active') {
                    // Get posts for this category
                    $posts = $postController->getPostsByCategory($param);
                    
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Blog category retrieved successfully',
                        'data' => [
                            'category' => $category,
                            'posts' => $posts,
                            'posts_count' => count($posts)
                        ]
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Blog category not found',
                        'data' => null
                    ]);
                }
            } else {
                // Get all active categories
                $categories = $categoryController->getActiveCategories();
                
                if (!empty($categories)) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Blog categories retrieved successfully',
                        'data' => $categories,
                        'count' => count($categories)
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No blog categories found',
                        'data' => []
                    ]);
                }
            }
            break;

        case 'recent':
            // Get recent posts
            $limit = $_GET['limit'] ?? 5;
            $posts = $postController->getRecentPosts($limit);
            
            if (!empty($posts)) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Recent blog posts retrieved successfully',
                    'data' => $posts,
                    'count' => count($posts)
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No recent blog posts found',
                    'data' => []
                ]);
            }
            break;

        case 'popular':
            // Get popular posts
            $limit = $_GET['limit'] ?? 5;
            $posts = $postController->getPopularPosts($limit);
            
            if (!empty($posts)) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Popular blog posts retrieved successfully',
                    'data' => $posts,
                    'count' => count($posts)
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No popular blog posts found',
                    'data' => []
                ]);
            }
            break;

        default:
            // Get all published posts (default endpoint)
            $posts = $postController->getAllPosts('published');
            
            if (!empty($posts)) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Blog posts retrieved successfully',
                    'data' => $posts,
                    'count' => count($posts)
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No blog posts found',
                    'data' => []
                ]);
            }
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>