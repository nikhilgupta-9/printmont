<?php
// Remove authentication includes - API should be public
// require_once 'config/constants.php';
// require_once 'controllers/AuthController.php';

require_once 'config/database.php';
require_once 'controllers/FaqController.php';

class FaqAPIController {
    private $faqController;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->faqController = new FaqController($db);
        
        // Set headers for API FIRST - before any output
        header('Content-Type: application/json');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    // Send JSON response
    private function sendResponse($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit();
    }
    
    // Get all FAQs (PUBLIC - only active ones)
    public function getAllFAQs() {
        try {
            // Get only active FAQs for public API
            $result = $this->faqController->getAllFAQs(1);
            $faqs = [];
            
            while ($row = $result->fetch_assoc()) {
                $faqs[] = [
                    'id' => (int)$row['id'],
                    'question' => $row['question'],
                    'answer' => $row['answer'],
                    'keywords' => $row['keywords'],
                    'display_order' => (int)$row['display_order'],
                    'view_count' => (int)$row['view_count'],
                    'helpful_count' => (int)$row['helpful_count'],
                    'not_helpful_count' => (int)$row['not_helpful_count'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
            
            $this->sendResponse(true, 'FAQs retrieved successfully', $faqs);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error retrieving FAQs: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Get FAQ by ID (PUBLIC)
    public function getFAQById($id) {
        try {
            $result = $this->faqController->getFAQById($id);
            
            if ($result && $result->num_rows > 0) {
                $faq = $result->fetch_assoc();
                
                // Only return active FAQs for public API
                if ($faq['is_active']) {
                    $formattedFaq = [
                        'id' => (int)$faq['id'],
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'keywords' => $faq['keywords'],
                        'display_order' => (int)$faq['display_order'],
                        'view_count' => (int)$faq['view_count'],
                        'helpful_count' => (int)$faq['helpful_count'],
                        'not_helpful_count' => (int)$faq['not_helpful_count'],
                        'created_at' => $faq['created_at'],
                        'updated_at' => $faq['updated_at']
                    ];
                    
                    $this->sendResponse(true, 'FAQ retrieved successfully', $formattedFaq);
                } else {
                    $this->sendResponse(false, 'FAQ not found', null, 404);
                }
            } else {
                $this->sendResponse(false, 'FAQ not found', null, 404);
            }
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error retrieving FAQ: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Search FAQs (PUBLIC)
    public function searchFAQs($searchTerm) {
        try {
            $result = $this->faqController->searchFAQs($searchTerm);
            $faqs = [];
            
            while ($row = $result->fetch_assoc()) {
                $faqs[] = [
                    'id' => (int)$row['id'],
                    'question' => $row['question'],
                    'answer' => $row['answer'],
                    'keywords' => $row['keywords'],
                    'display_order' => (int)$row['display_order'],
                    'is_active' => (bool)$row['is_active']
                ];
            }
            
            $this->sendResponse(true, 'Search completed successfully', $faqs);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error searching FAQs: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Get FAQ statistics (PUBLIC)
    public function getStats() {
        try {
            $stats = $this->faqController->getFAQStats();
            $this->sendResponse(true, 'Statistics retrieved successfully', $stats);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error retrieving statistics: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Create new FAQ (PROTECTED - you might want to add authentication here)
    public function createFAQ() {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->sendResponse(false, 'Invalid JSON input', null, 400);
            }
            
            // Validate required fields
            if (empty($input['question']) || empty($input['answer'])) {
                $this->sendResponse(false, 'Question and answer are required', null, 400);
            }
            
            $result = $this->faqController->createFAQ($input);
            
            if ($result['success']) {
                $this->sendResponse(true, $result['message'], ['id' => $result['id'] ?? null], 201);
            } else {
                $this->sendResponse(false, $result['message'], null, 400);
            }
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error creating FAQ: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Update FAQ (PROTECTED)
    public function updateFAQ($id) {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->sendResponse(false, 'Invalid JSON input', null, 400);
            }
            
            // Validate required fields
            if (empty($input['question']) || empty($input['answer'])) {
                $this->sendResponse(false, 'Question and answer are required', null, 400);
            }
            
            $result = $this->faqController->updateFAQ($id, $input);
            
            if ($result['success']) {
                $this->sendResponse(true, $result['message']);
            } else {
                $this->sendResponse(false, $result['message'], null, 400);
            }
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error updating FAQ: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Delete FAQ (PROTECTED)
    public function deleteFAQ($id) {
        try {
            $result = $this->faqController->deleteFAQ($id);
            
            if ($result['success']) {
                $this->sendResponse(true, $result['message']);
            } else {
                $this->sendResponse(false, $result['message'], null, 400);
            }
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Error deleting FAQ: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Handle API requests
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    $this->getFAQById($_GET['id']);
                } elseif (isset($_GET['search'])) {
                    $this->searchFAQs($_GET['search']);
                } elseif (isset($_GET['stats'])) {
                    $this->getStats();
                } else {
                    $this->getAllFAQs();
                }
                break;
                
            case 'POST':
                $this->createFAQ();
                break;
                
            case 'PUT':
            case 'PATCH':
                if (isset($_GET['id'])) {
                    $this->updateFAQ($_GET['id']);
                } else {
                    $this->sendResponse(false, 'FAQ ID is required for update', null, 400);
                }
                break;
                
            case 'DELETE':
                if (isset($_GET['id'])) {
                    $this->deleteFAQ($_GET['id']);
                } else {
                    $this->sendResponse(false, 'FAQ ID is required for deletion', null, 400);
                }
                break;
                
            default:
                $this->sendResponse(false, 'Method not allowed', null, 405);
                break;
        }
    }
}

// Initialize and handle API request
try {
    $api = new FaqAPIController();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API Error: ' . $e->getMessage(),
        'data' => null,
        'timestamp' => date('c')
    ]);
}
?>