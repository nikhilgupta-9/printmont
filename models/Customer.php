<?php
class Customer {
    private $conn;
    private $table = "customers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($limit = null, $offset = 0) {
        $query = "SELECT c.*, u.username, u.email, u.role, u.status as user_status 
                  FROM {$this->table} c 
                  JOIN users u ON c.user_id = u.id 
                  ORDER BY c.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($query);
        }
        
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        
        return $customers;
    }

    public function getById($id) {
        $query = "SELECT c.*, u.username, u.email, u.role, u.status as user_status 
                  FROM {$this->table} c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function getByUserId($userId) {
        $query = "SELECT c.*, u.username, u.email, u.role, u.status as user_status 
                  FROM {$this->table} c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (user_id, company_name, phone, address, city, state, country, postal_code, customer_type, status, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issssssssss", 
            $data['user_id'], 
            $data['company_name'], 
            $data['phone'], 
            $data['address'], 
            $data['city'], 
            $data['state'], 
            $data['country'], 
            $data['postal_code'], 
            $data['customer_type'], 
            $data['status'], 
            $data['notes']
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET 
                  company_name = ?, phone = ?, address = ?, city = ?, state = ?, 
                  country = ?, postal_code = ?, customer_type = ?, status = ?, notes = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssssi", 
            $data['company_name'], 
            $data['phone'], 
            $data['address'], 
            $data['city'], 
            $data['state'], 
            $data['country'], 
            $data['postal_code'], 
            $data['customer_type'], 
            $data['status'], 
            $data['notes'], 
            $id
        );
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function updateLastLogin($userId) {
        $query = "UPDATE {$this->table} SET last_login = NOW() WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function incrementOrderStats($customerId, $amount) {
        $query = "UPDATE {$this->table} SET 
                  total_orders = total_orders + 1, 
                  total_spent = total_spent + ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("di", $amount, $customerId);
        return $stmt->execute();
    }

    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_customers,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_customers,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_customers,
                    SUM(CASE WHEN customer_type = 'business' THEN 1 ELSE 0 END) as business_customers,
                    SUM(CASE WHEN customer_type = 'individual' THEN 1 ELSE 0 END) as individual_customers,
                    SUM(total_orders) as total_orders,
                    SUM(total_spent) as total_revenue
                  FROM {$this->table}";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    public function search($searchTerm) {
        $query = "SELECT c.*, u.username, u.email 
                  FROM {$this->table} c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE u.username LIKE ? OR u.email LIKE ? OR c.company_name LIKE ? OR c.phone LIKE ?
                  ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchPattern = "%$searchTerm%";
        $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        
        return $customers;
    }
}
?>