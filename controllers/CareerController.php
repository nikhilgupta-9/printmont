<?php
require_once( __DIR__ . '/../config/database.php');

class CareerController {
    private $conn;
    private $table_name = "careers";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection(); // Fixed: Use $this->conn instead of $this->db
    }

    // Ensure table exists
    public function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS `careers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `job_title` VARCHAR(255) NOT NULL,
            `department` VARCHAR(100) NOT NULL,
            `job_type` VARCHAR(50) NOT NULL,
            `location` VARCHAR(255) NOT NULL,
            `description` TEXT NOT NULL,
            `requirements` TEXT NOT NULL,
            `responsibilities` TEXT NOT NULL,
            `salary_range` VARCHAR(100) NULL,
            `application_deadline` DATE NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `views_count` INT(11) DEFAULT 0,
            `applications_count` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        return $this->conn->query($query);
    }

    // Get all careers
    public function getAllCareers($is_active = null) {
        $query = "SELECT * FROM {$this->table_name}";
        
        if ($is_active !== null) {
            $query .= " WHERE is_active = ?";
        }
        
        $query .= " ORDER BY created_at DESC";

        if ($is_active !== null) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $is_active);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $careers = [];
            while ($row = $result->fetch_assoc()) {
                $careers[] = $row;
            }
            return $careers;
        }

        $result = $this->conn->query($query);
        $careers = [];
        while ($row = $result->fetch_assoc()) {
            $careers[] = $row;
        }
        return $careers;
    }

    // Get career by ID
    public function getCareerById($id) {
        $query = "SELECT * FROM {$this->table_name} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }

    // Create career
    public function createCareer($data) {
        try {
            $job_title = trim($data['job_title'] ?? '');
            $department = $data['department'] ?? 'technology';
            $job_type = $data['job_type'] ?? 'full_time';
            $location = trim($data['location'] ?? '');
            $description = $data['description'] ?? '';
            $requirements = $data['requirements'] ?? '';
            $responsibilities = $data['responsibilities'] ?? '';
            $salary_range = trim($data['salary_range'] ?? '');
            $application_deadline = !empty($data['application_deadline']) ? $data['application_deadline'] : null;
            $is_active = isset($data['is_active']) ? 1 : 0;

            // Validate required fields
            if (empty($job_title) || empty($location) || empty($description) || empty($requirements)) {
                return ['success' => false, 'message' => 'Job title, location, description, and requirements are required fields.'];
            }

            $query = "INSERT INTO {$this->table_name} 
                     (job_title, department, job_type, location, description, requirements, 
                      responsibilities, salary_range, application_deadline, is_active) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssssssssi", 
                $job_title, $department, $job_type, $location, $description, $requirements,
                $responsibilities, $salary_range, $application_deadline, $is_active
            );
            
            if ($stmt->execute()) {
                $career_id = $stmt->insert_id;
                return [
                    'success' => true, 
                    'message' => 'Career opportunity created successfully!',
                    'career_id' => $career_id
                ];
            } else {
                return ['success' => false, 'message' => 'Error creating career: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Update career
    public function updateCareer($id, $data) {
        try {
            $job_title = trim($data['job_title'] ?? '');
            $department = $data['department'] ?? 'technology';
            $job_type = $data['job_type'] ?? 'full_time';
            $location = trim($data['location'] ?? '');
            $description = $data['description'] ?? '';
            $requirements = $data['requirements'] ?? '';
            $responsibilities = $data['responsibilities'] ?? '';
            $salary_range = trim($data['salary_range'] ?? '');
            $application_deadline = !empty($data['application_deadline']) ? $data['application_deadline'] : null;
            $is_active = isset($data['is_active']) ? 1 : 0;

            // Validate required fields
            if (empty($job_title) || empty($location) || empty($description) || empty($requirements)) {
                return ['success' => false, 'message' => 'Job title, location, description, and requirements are required fields.'];
            }

            $query = "UPDATE {$this->table_name} 
                     SET job_title = ?, department = ?, job_type = ?, location = ?, 
                         description = ?, requirements = ?, responsibilities = ?, 
                         salary_range = ?, application_deadline = ?, is_active = ?, 
                         updated_at = NOW() 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssssssssii", 
                $job_title, $department, $job_type, $location, $description, $requirements,
                $responsibilities, $salary_range, $application_deadline, $is_active, $id
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Career opportunity updated successfully!'
                ];
            } else {
                return ['success' => false, 'message' => 'Error updating career: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Delete career
    public function deleteCareer($id) {
        try {
            $query = "DELETE FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Career opportunity deleted successfully!'
                ];
            } else {
                return ['success' => false, 'message' => 'Error deleting career: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    // Get career statistics
    public function getCareerStats() {
        $stats = [
            'total_jobs' => 0,
            'active_jobs' => 0,
            'total_views' => 0,
            'total_applications' => 0
        ];

        // Total jobs
        $query = "SELECT COUNT(*) as total FROM {$this->table_name}";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_jobs'] = $row['total'];
        }

        // Active jobs
        $query = "SELECT COUNT(*) as active FROM {$this->table_name} WHERE is_active = 1";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['active_jobs'] = $row['active'];
        }

        // Total views and applications
        $query = "SELECT SUM(views_count) as total_views, SUM(applications_count) as total_applications FROM {$this->table_name}";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_views'] = $row['total_views'] ?? 0;
            $stats['total_applications'] = $row['total_applications'] ?? 0;
        }

        return $stats;
    }

    // Get department options - FIXED METHOD NAME
    public function getDepartmentOptions() {
        return [
            'technology' => 'Technology',
            'design' => 'Design',
            'marketing' => 'Marketing',
            'sales' => 'Sales',
            'operations' => 'Operations',
            'hr' => 'Human Resources',
            'finance' => 'Finance'
        ];
    }

    // Get job type options - FIXED METHOD NAME
    public function getJobTypeOptions() {
        return [
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'internship' => 'Internship',
            'remote' => 'Remote'
        ];
    }

    // Get status options
    public function getStatusOptions() {
        return [
            'pending' => 'Pending',
            'reviewed' => 'Reviewed',
            'shortlisted' => 'Shortlisted',
            'rejected' => 'Rejected',
            'hired' => 'Hired'
        ];
    }

    // File Upload Method for Resumes
    public function uploadResume($file) {
        $targetDir = "uploads/careers/resumes/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Generate unique filename
        $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file["name"]));
        $targetFilePath = $targetDir . $fileName;

        // Check file size (5MB limit)
        if ($file["size"] > 5000000) {
            throw new Exception("Sorry, your file is too large. Maximum size is 5MB.");
        }

        // Allow certain file formats
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['pdf', 'doc', 'docx'];
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Sorry, only PDF, DOC, and DOCX files are allowed.");
        }

        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return $targetFilePath;
        }
        
        throw new Exception("Sorry, there was an error uploading your file.");
    }

    // Create job application
    // public function createApplication($data) {
    //     try {
    //         $career_id = intval($data['career_id']);
    //         $full_name = trim($data['full_name']);
    //         $email = trim($data['email']);
    //         $phone = trim($data['phone']);
    //         $cover_letter = $data['cover_letter'] ?? '';
    //         $linkedin_url = $data['linkedin_url'] ?? '';
    //         $portfolio_url = $data['portfolio_url'] ?? '';
    //         $experience = $data['experience'] ?? '';
    //         $education = $data['education'] ?? '';
    //         $skills = $data['skills'] ?? '';
    //         $resume_path = $data['resume_path'] ?? '';
    //         $status = $data['status'] ?? 'pending';
    //         $applied_at = $data['applied_at'] ?? date('Y-m-d H:i:s');

    //         $query = "INSERT INTO job_applications 
    //                  (career_id, full_name, email, phone, cover_letter, linkedin_url, 
    //                   portfolio_url, experience, education, skills, resume_path, status, applied_at) 
    //                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    //         $stmt = $this->conn->prepare($query);
    //         $stmt->bind_param("issssssssssss", 
    //             $career_id, $full_name, $email, $phone, $cover_letter, $linkedin_url,
    //             $portfolio_url, $experience, $education, $skills, $resume_path, $status, $applied_at
    //         );
            
    //         if ($stmt->execute()) {
    //             $application_id = $stmt->insert_id;
                
    //             // Increment application count
    //             $this->incrementApplicationCount($career_id);
                
    //             return [
    //                 'success' => true, 
    //                 'message' => 'Application submitted successfully!',
    //                 'application_id' => $application_id
    //             ];
    //         } else {
    //             return ['success' => false, 'message' => 'Error submitting application: ' . $stmt->error];
    //         }
            
    //     } catch (Exception $e) {
    //         return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    //     }
    // }

    // Increment application count
    public function incrementApplicationCount($id) {
        $query = "UPDATE {$this->table_name} SET applications_count = applications_count + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Increment views count
    public function incrementViewsCount($id) {
        $query = "UPDATE {$this->table_name} SET views_count = views_count + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Get job applications
    public function getJobApplications($careerId = null) {
        $query = "SELECT * FROM job_applications";
        
        if ($careerId !== null) {
            $query .= " WHERE career_id = ?";
        }
        
        $query .= " ORDER BY applied_at DESC";
        
        if ($careerId !== null) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $careerId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($query);
        }
        
        $applications = [];
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        return $applications;
    }

    // Get application by ID
   public function getApplicationById($id) {
    $query = "SELECT ja.*, 
         IFNULL(c.job_title, 'N/A') AS job_title,
         IFNULL(c.department, 'N/A') AS department,
         IFNULL(c.job_type, 'N/A') AS job_type,
         IFNULL(c.location, 'N/A') AS location
         FROM job_applications ja
         LEFT JOIN careers c ON ja.career_id = c.id
         WHERE ja.id = ?";

    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}
    // Update application status
    public function updateApplicationStatus($id, $status) {
        $query = "UPDATE job_applications SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'Application status updated successfully!'
            ];
        } else {
            return ['success' => false, 'message' => 'Error updating application status: ' . $stmt->error];
        }
    }

    // In your CareerController class, update the createApplication method parameter names:
public function createApplication($data) {
    try {
        $career_id = intval($data['career_id']);
        $full_name = trim($data['full_name']);
        $email = trim($data['email']);
        $phone = trim($data['phone']);
        $cover_letter = $data['cover_letter'] ?? '';
        $linkedin_url = $data['linkedin_url'] ?? '';
        $portfolio_url = $data['portfolio_url'] ?? '';
        $experience = $data['experience'] ?? '';
        $education = $data['education'] ?? '';
        $skills = $data['skills'] ?? '';
        $resume_path = $data['resume_path'] ?? '';
        $status = $data['status'] ?? 'pending';
        $applied_at = $data['applied_at'] ?? date('Y-m-d H:i:s');

        // Validate required fields
        if (empty($full_name) || empty($email) || empty($phone)) {
            return ['success' => false, 'message' => 'Full name, email, and phone are required fields.'];
        }

        $query = "INSERT INTO job_applications 
                 (career_id, full_name, email, phone, cover_letter, linkedin_url, 
                  portfolio_url, experience, education, skills, resume_path, status, applied_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issssssssssss", 
            $career_id, $full_name, $email, $phone, $cover_letter, $linkedin_url,
            $portfolio_url, $experience, $education, $skills, $resume_path, $status, $applied_at
        );
        
        if ($stmt->execute()) {
            $application_id = $stmt->insert_id;
            
            // Increment application count
            $this->incrementApplicationCount($career_id);
            
            return [
                'success' => true, 
                'message' => 'Application submitted successfully!',
                'application_id' => $application_id
            ];
        } else {
            return ['success' => false, 'message' => 'Error submitting application: ' . $stmt->error];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}
}
?>