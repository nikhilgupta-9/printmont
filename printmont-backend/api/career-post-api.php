<?php
// api/careers/apply.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/CareerController.php');

class ApiCareerApplication {
    private $careerController;
    
    public function __construct() {
        $this->careerController = new CareerController();
    }
    
    public function handleOptions() {
        http_response_code(200);
        exit;
    }
    
    public function submitApplication() {
        try {
            // Check if it's a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Only POST method is allowed');
            }
            
            // Get input data (support both JSON and form-data)
            $input = $this->getInputData();
            
            if (!$input) {
                throw new Exception('Invalid input data');
            }
            
            // Validate required fields
            $required_fields = ['career_id', 'full_name', 'email', 'phone'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Required field missing: $field");
                }
            }
            
            $career_id = intval($input['career_id']);
            $full_name = trim($input['full_name']);
            $email = trim($input['email']);
            $phone = trim($input['phone']);
            $cover_letter = $input['cover_letter'] ?? '';
            $linkedin_url = $input['linkedin_url'] ?? '';
            $portfolio_url = $input['portfolio_url'] ?? '';
            $experience = $input['experience'] ?? '';
            $education = $input['education'] ?? '';
            $skills = $input['skills'] ?? '';
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
            
            // Validate career exists and is active
            $career = $this->careerController->getCareerById($career_id);
            if (!$career) {
                throw new Exception('Career opportunity not found');
            }
            
            if (!$career['is_active']) {
                throw new Exception('This career opportunity is no longer active');
            }
            
            // Check application deadline
            if (!empty($career['application_deadline'])) {
                $current_date = date('Y-m-d');
                $deadline_date = date('Y-m-d', strtotime($career['application_deadline']));
                
                if ($current_date > $deadline_date) {
                    throw new Exception('Application deadline has passed');
                }
            }
            
            // Handle file upload if provided
            $resume_path = '';
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                try {
                    $resume_path = $this->careerController->uploadResume($_FILES['resume']);
                } catch (Exception $e) {
                    throw new Exception('Resume upload failed: ' . $e->getMessage());
                }
            } elseif (!empty($input['resume_url'])) {
                $resume_path = $input['resume_url']; // For external resume URLs
            } else {
                throw new Exception('Resume is required');
            }
            
            // Prepare application data
            $application_data = [
                'career_id' => $career_id,
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'cover_letter' => $cover_letter,
                'linkedin_url' => $linkedin_url,
                'portfolio_url' => $portfolio_url,
                'experience' => $experience,
                'education' => $education,
                'skills' => $skills,
                'resume_path' => $resume_path,
                'status' => 'pending',
                'applied_at' => date('Y-m-d H:i:s')
            ];
            
            // Save application to database
            $result = $this->careerController->createApplication($application_data);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            $response = [
                'application_id' => $result['application_id'],
                'message' => 'Application submitted successfully!',
                'next_steps' => 'Our team will review your application and contact you soon.'
            ];
            
            $this->sendSuccess($response);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
    
    private function getInputData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Check if it's JSON
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            return $input ?: [];
        }
        
        // Check if it's multipart/form-data (file upload)
        if (strpos($contentType, 'multipart/form-data') !== false) {
            return $_POST;
        }
        
        // Check if it's application/x-www-form-urlencoded
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str(file_get_contents('php://input'), $input);
            return $input;
        }
        
        // Default to empty array
        return [];
    }
    
    private function sendSuccess($data) {
        http_response_code(201);
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
$api = new ApiCareerApplication();

if ($method === 'OPTIONS') {
    $api->handleOptions();
    exit;
}

if ($method === 'POST') {
    $api->submitApplication();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error' => 'Method not allowed. Only POST requests are accepted.'
    ]);
}
?>