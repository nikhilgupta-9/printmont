<?php
// models/CareerModel.php
require_once(__DIR__ . '/../config/database.php');

class CareerModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Career CRUD Methods
    public function getAllCareers() {
        $query = "SELECT * FROM careers ORDER BY created_at DESC";
        $result = $this->db->query($query);
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Database error: " . $this->db->error);
            return [];
        }
    }

    public function getCareerById($id) {
        $stmt = $this->db->prepare("SELECT * FROM careers WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createCareer($data) {
        $stmt = $this->db->prepare("INSERT INTO careers (job_title, department, job_type, location, description, requirements, responsibilities, salary_range, application_deadline, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("sssssssssi", 
            $data['job_title'],
            $data['department'],
            $data['job_type'],
            $data['location'],
            $data['description'],
            $data['requirements'],
            $data['responsibilities'],
            $data['salary_range'],
            $data['application_deadline'],
            $data['is_active']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updateCareer($id, $data) {
        $stmt = $this->db->prepare("UPDATE careers SET job_title = ?, department = ?, job_type = ?, location = ?, description = ?, requirements = ?, responsibilities = ?, salary_range = ?, application_deadline = ?, is_active = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("sssssssssii", 
            $data['job_title'],
            $data['department'],
            $data['job_type'],
            $data['location'],
            $data['description'],
            $data['requirements'],
            $data['responsibilities'],
            $data['salary_range'],
            $data['application_deadline'],
            $data['is_active'],
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteCareer($id) {
        $stmt = $this->db->prepare("DELETE FROM careers WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Job Applications Methods
    public function getJobApplications($careerId = null) {
        if ($careerId) {
            $query = "SELECT ja.*, c.job_title FROM job_applications ja 
                     LEFT JOIN careers c ON ja.career_id = c.id 
                     WHERE ja.career_id = ? 
                     ORDER BY ja.applied_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $careerId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $query = "SELECT ja.*, c.job_title FROM job_applications ja 
                     LEFT JOIN careers c ON ja.career_id = c.id 
                     ORDER BY ja.applied_at DESC";
            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function getApplicationById($id) {
        $stmt = $this->db->prepare("SELECT ja.*, c.job_title FROM job_applications ja 
                                   LEFT JOIN careers c ON ja.career_id = c.id 
                                   WHERE ja.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function updateApplicationStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function getCareerStats() {
        $stats = [];
        
        // Total jobs
        $result = $this->db->query("SELECT COUNT(*) as total FROM careers");
        $stats['total_jobs'] = $result->fetch_assoc()['total'];
        
        // Active jobs
        $result = $this->db->query("SELECT COUNT(*) as active FROM careers WHERE is_active = 1");
        $stats['active_jobs'] = $result->fetch_assoc()['active'];
        
        // Total applications
        $result = $this->db->query("SELECT COUNT(*) as total_apps FROM job_applications");
        $stats['total_applications'] = $result->fetch_assoc()['total_apps'];
        
        // Pending applications
        $result = $this->db->query("SELECT COUNT(*) as pending FROM job_applications WHERE status = 'pending'");
        $stats['pending_applications'] = $result->fetch_assoc()['pending'];
        
        return $stats;
    }

    // Add this method to CareerModel class
public function deleteApplication($id) {
    $stmt = $this->db->prepare("DELETE FROM job_applications WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $this->db->error);
        return false;
    }
    
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
}
?>