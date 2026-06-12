<?php
class Staff {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllStaff($includeInactive = false) {
        if ($includeInactive) {
            $query = "SELECT * FROM {$this->table} WHERE role IN ('admin', 'manager', 'staff') ORDER BY created_at DESC";
        } else {
            $query = "SELECT * FROM {$this->table} WHERE role IN ('admin', 'manager', 'staff') AND status = 'active' ORDER BY created_at DESC";
        }
        
        $result = $this->conn->query($query);
        
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
        
        return $staff;
    }

    public function getDeactivatedStaff() {
        $query = "SELECT * FROM {$this->table} WHERE role IN ('admin', 'manager', 'staff') AND status = 'inactive' ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
        
        return $staff;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ? AND role IN ('admin', 'manager', 'staff')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->bind_param("sssss", $data['username'], $data['email'], $hashedPassword, $data['role'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET username = ?, email = ?, role = ?, status = ?";
        $params = [$data['username'], $data['email'], $data['role'], $data['status']];
        $types = "ssss";

        // If password is provided, update it
        if (!empty($data['password'])) {
            $query .= ", password = ?";
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $params[] = $hashedPassword;
            $types .= "s";
        }

        $query .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ? AND role IN ('admin', 'manager', 'staff')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function deactivate($id) {
        $query = "UPDATE {$this->table} SET status = 'inactive' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function activate($id) {
        $query = "UPDATE {$this->table} SET status = 'active' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE email = ?";
        $params = [$email];
        $types = "s";

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    public function usernameExists($username, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE username = ?";
        $params = [$username];
        $types = "s";

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    public function getStaffStats() {
        $query = "SELECT 
                    COUNT(*) as total_staff,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_staff,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_staff,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as manager_count,
                    SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count
                  FROM {$this->table} 
                  WHERE role IN ('admin', 'manager', 'staff')";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
}
?>