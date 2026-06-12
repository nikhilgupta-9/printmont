<?php
// api/faqs/all-categories.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/FaqController.php');
require_once(__DIR__ . '/../controllers/FaqCategoryController.php');

class ApiAllCategoriesWithFAQs {
    private $faqController;
    private $categoryController;
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->faqController = new FaqController($this->db);
        $this->categoryController = new CategoryController($this->db);
    }
    
    public function handleOptions() {
        http_response_code(200);
        exit;
    }
    
    public function getAllCategoriesWithFAQs() {
        try {
            // Get query parameters
            $active_only = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
            $include_empty_categories = isset($_GET['include_empty']) ? filter_var($_GET['include_empty'], FILTER_VALIDATE_BOOLEAN) : true;
            $category_type = isset($_GET['type']) ? trim($_GET['type']) : null;
            
            // Get all active categories
            $categories_result = $this->categoryController->getAllCategories($active_only);
            
            if (!$categories_result) {
                throw new Exception('Failed to fetch categories');
            }
            
            $categories_with_faqs = [];
            $total_faqs_count = 0;
            
            // Get all categories
            while ($category = $categories_result->fetch_assoc()) {
                // Filter by type if specified
                if ($category_type && $category['type'] !== $category_type) {
                    continue;
                }
                
                // Get FAQs for this category
                $faqs_result = $this->faqController->getFAQsByCategory($category['id'], $active_only);
                $faqs = [];
                
                if ($faqs_result) {
                    while ($faq = $faqs_result->fetch_assoc()) {
                        $faqs[] = $this->formatFAQ($faq);
                    }
                }
                
                // Skip empty categories if include_empty is false
                if (!$include_empty_categories && empty($faqs)) {
                    continue;
                }
                
                $category_data = $this->formatCategory($category);
                $category_data['faqs'] = $faqs;
                $category_data['faq_count'] = count($faqs);
                
                $categories_with_faqs[] = $category_data;
                $total_faqs_count += count($faqs);
            }
            
            // Sort categories by display_order
            usort($categories_with_faqs, function($a, $b) {
                return $a['display_order'] - $b['display_order'];
            });
            
            $response = [
                'categories' => $categories_with_faqs,
                'summary' => [
                    'total_categories' => count($categories_with_faqs),
                    'total_faqs' => $total_faqs_count,
                    'active_only' => $active_only,
                    'include_empty_categories' => $include_empty_categories
                ]
            ];
            
            $this->sendSuccess($response);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
    
    private function formatCategory($category) {
        return [
            'id' => (int)$category['id'],
            'name' => $category['name'],
            'description' => $category['description'],
            'type' => $category['type'],
            'icon' => $category['icon'],
            'color' => $category['color'],
            'is_active' => (bool)$category['is_active'],
            'display_order' => (int)$category['display_order'],
            'faq_count' => (int)$category['faq_count'],
            'created_at' => $category['created_at'],
            'updated_at' => $category['updated_at']
        ];
    }
    
    private function formatFAQ($faq) {
        return [
            'id' => (int)$faq['id'],
            'question' => $faq['question'],
            'answer' => $faq['answer'],
            'category_id' => (int)$faq['category_id'],
            'is_active' => (bool)$faq['is_active'],
            'display_order' => (int)$faq['display_order'],
            'keywords' => $faq['keywords'] ? array_map('trim', explode(',', $faq['keywords'])) : [],
            'view_count' => (int)$faq['view_count'],
            'helpful_count' => (int)$faq['helpful_count'],
            'not_helpful_count' => (int)$faq['not_helpful_count'],
            'created_at' => $faq['created_at'],
            'updated_at' => $faq['updated_at']
        ];
    }
    
    private function sendSuccess($data) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$api = new ApiAllCategoriesWithFAQs();

if ($method === 'OPTIONS') {
    $api->handleOptions();
    exit;
}

if ($method === 'GET') {
    $api->getAllCategoriesWithFAQs();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>