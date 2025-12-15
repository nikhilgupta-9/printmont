<?php
// models/AboutUsModel.php
require_once(__DIR__ . '/../config/database.php');

class AboutUsModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // About Us Sections CRUD
    public function getAllSections() {
        $query = "SELECT * FROM about_us ORDER BY display_order ASC";
        $result = $this->db->query($query);
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Database error: " . $this->db->error);
            return [];
        }
    }

    public function getSectionById($id) {
        $stmt = $this->db->prepare("SELECT * FROM about_us WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createSection($data) {
        $stmt = $this->db->prepare("INSERT INTO about_us (section_title, section_content, section_type, image_path, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("ssssii", $data['section_title'], $data['section_content'], $data['section_type'], $data['image_path'], $data['display_order'], $data['is_active']);
        return $stmt->execute();
    }

    public function updateSection($id, $data) {
        $stmt = $this->db->prepare("UPDATE about_us SET section_title = ?, section_content = ?, section_type = ?, image_path = ?, display_order = ?, is_active = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("ssssiii", $data['section_title'], $data['section_content'], $data['section_type'], $data['image_path'], $data['display_order'], $data['is_active'], $id);
        return $stmt->execute();
    }

    public function deleteSection($id) {
        $stmt = $this->db->prepare("DELETE FROM about_us WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Team Members CRUD
    public function getAllTeamMembers() {
        $query = "SELECT * FROM team_members ORDER BY display_order ASC";
        $result = $this->db->query($query);
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Database error: " . $this->db->error);
            return [];
        }
    }

    public function getTeamMemberById($id) {
        $stmt = $this->db->prepare("SELECT * FROM team_members WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createTeamMember($data) {
        $stmt = $this->db->prepare("INSERT INTO team_members (name, position, bio, image_path, email, phone, social_links, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $socialLinks = json_encode($data['social_links'] ?? []);
        $stmt->bind_param("sssssssii", $data['name'], $data['position'], $data['bio'], $data['image_path'], $data['email'], $data['phone'], $socialLinks, $data['display_order'], $data['is_active']);
        return $stmt->execute();
    }

    public function updateTeamMember($id, $data) {
        $stmt = $this->db->prepare("UPDATE team_members SET name = ?, position = ?, bio = ?, image_path = ?, email = ?, phone = ?, social_links = ?, display_order = ?, is_active = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $socialLinks = json_encode($data['social_links'] ?? []);
        $stmt->bind_param("sssssssiii", $data['name'], $data['position'], $data['bio'], $data['image_path'], $data['email'], $data['phone'], $socialLinks, $data['display_order'], $data['is_active'], $id);
        return $stmt->execute();
    }

    public function deleteTeamMember($id) {
        $stmt = $this->db->prepare("DELETE FROM team_members WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Close connection (optional)
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }

    
}
?>