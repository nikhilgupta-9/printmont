<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/CareerController.php');

class ApiCareer {
    private $careerController;

    public function __construct() {
        $this->careerController = new CareerController();
    }

    public function handleOptions() {
        http_response_code(200);
        exit;
    }

    public function getCareers() {
        try {
            $active_only = isset($_GET['active_only']) ? intval($_GET['active_only']) : 1;
            $department = $_GET['department'] ?? null;
            $job_type = $_GET['job_type'] ?? null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            $featured = isset($_GET['featured']) ? intval($_GET['featured']) : null;

            // Get careers as ARRAY from Controller
            $careers_list = $this->careerController->getAllCareers($active_only); // returns array

            $filtered = [];
            foreach ($careers_list as $row) {

                if ($department && $row['department'] !== $department) continue;
                if ($job_type && $row['job_type'] !== $job_type) continue;
                if ($featured !== null && intval($row['is_featured']) !== $featured) continue;

                $filtered[] = $this->formatCareer($row);
            }

            $total_count = count($filtered);

            if ($limit && $limit > 0) {
                $filtered = array_slice($filtered, $offset, $limit);
            }

            $response = [
                'careers' => $filtered,
                'filters' => [
                    'departments' => $this->careerController->getDepartmentOptions(),
                    'job_types' => $this->careerController->getJobTypeOptions()
                ],
                'pagination' => [
                    'total' => $total_count,
                    'limit' => $limit,
                    'offset' => $offset,
                    'returned' => count($filtered)
                ]
            ];

            $this->sendSuccess($response);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function getCareerById() {
        try {
            $career_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if (!$career_id) $this->sendError("Career ID is required");

            $this->careerController->incrementViewsCount($career_id);

            // Should return ARRAY
            $career = $this->careerController->getCareerById($career_id);

            if (!$career) {
                $this->sendError("Career not found", 404);
            }

            $this->sendSuccess([
                'career' => $this->formatCareer($career)
            ]);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function formatCareer($career) {
        return [
            'id' => intval($career['id']),
            'job_title' => $career['job_title'],
            'department' => $career['department'],
            'job_type' => $career['job_type'],
            'location' => $career['location'],
            'description' => $career['description'],
            'requirements' => $career['requirements'],
            'responsibilities' => $career['responsibilities'],
            'salary_range' => $career['salary_range'],
            'application_deadline' => $career['application_deadline'],
            'is_active' => intval($career['is_active']),
            'views_count' => intval($career['views_count']),
            'applications_count' => intval($career['applications_count']),
            'created_at' => $career['created_at'],
            'updated_at' => $career['updated_at'],
            'status' => $this->getJobStatus($career['application_deadline'])
        ];
    }

    private function getJobStatus($deadline) {
        if (!$deadline) return "open";

        return (date('Y-m-d') > date('Y-m-d', strtotime($deadline))) ? 'closed' : 'open';
    }

    private function sendSuccess($data) {
        echo json_encode(["success" => true, "data" => $data]);
        exit;
    }

    private function sendError($msg, $code = 400) {
        http_response_code($code);
        echo json_encode(["success" => false, "error" => $msg]);
        exit;
    }
}

$api = new ApiCareer();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') $api->handleOptions();
if ($method === 'GET') {
    isset($_GET['id']) ? $api->getCareerById() : $api->getCareers();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
