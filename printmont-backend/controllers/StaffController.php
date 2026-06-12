<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/Staff.php');

class StaffController {
    private $db;
    private $staff;

    public function __construct() {
        $this->db = new Database();
        $this->staff = new Staff($this->db->getConnection());
    }

    public function getAllStaff($includeInactive = false) {
        return $this->staff->getAllStaff($includeInactive);
    }

    public function getDeactivatedStaff() {
        return $this->staff->getDeactivatedStaff();
    }

    public function getStaffById($id) {
        return $this->staff->getById($id);
    }

    public function createStaff($data) {
        // Validate email uniqueness
        if ($this->staff->emailExists($data['email'])) {
            throw new Exception("Email already exists.");
        }

        // Validate username uniqueness
        if ($this->staff->usernameExists($data['username'])) {
            throw new Exception("Username already exists.");
        }

        return $this->staff->create($data);
    }

    public function updateStaff($id, $data) {
        // Validate email uniqueness
        if ($this->staff->emailExists($data['email'], $id)) {
            throw new Exception("Email already exists.");
        }

        // Validate username uniqueness
        if ($this->staff->usernameExists($data['username'], $id)) {
            throw new Exception("Username already exists.");
        }

        return $this->staff->update($id, $data);
    }

    public function deleteStaff($id) {
        return $this->staff->delete($id);
    }

    public function deactivateStaff($id) {
        return $this->staff->deactivate($id);
    }

    public function activateStaff($id) {
        return $this->staff->activate($id);
    }

    public function getStaffStats() {
        return $this->staff->getStaffStats();
    }
}
?>