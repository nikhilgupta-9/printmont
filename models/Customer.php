<?php
class Customer {
    private $conn;
    private $table = "customers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($limit = null, $offset = 0) {
        $query = "SELECT c.*, u.username, u.email, u.first_name, u.last_name, u.phone as user_phone, u.profile_picture, u.role, u.status as user_status, u.is_verified, u.created_at as user_created_at, u.last_login as user_last_login 
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
        $query = "SELECT c.*, u.username, u.email, u.first_name, u.last_name, u.phone as user_phone, u.profile_picture, u.role, u.status as user_status, u.is_verified, u.created_at as user_created_at, u.last_login as user_last_login 
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
        $query = "SELECT c.*, u.username, u.email, u.first_name, u.last_name, u.phone as user_phone, u.profile_picture, u.role, u.status as user_status, u.is_verified, u.created_at as user_created_at, u.last_login as user_last_login 
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
        $id = (int)$id;
        $cust = $this->getById($id);
        $userId = !empty($cust['user_id']) ? (int)$cust['user_id'] : null;

        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $res = $stmt->execute();

        if ($userId) {
            $stmtUser = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmtUser->bind_param("i", $userId);
            $stmtUser->execute();

            // Clean up related customer data
            @$this->conn->query("DELETE FROM user_addresses WHERE user_id = $userId");
            @$this->conn->query("DELETE FROM customer_images WHERE customer_id = $id");
        }

        return $res;
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
        $query = "SELECT c.*, u.username, u.email, u.first_name, u.last_name, u.phone as user_phone, u.profile_picture, u.role, u.status as user_status, u.is_verified, u.created_at as user_created_at, u.last_login as user_last_login 
                  FROM {$this->table} c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR c.company_name LIKE ? OR c.phone LIKE ? OR u.phone LIKE ? OR c.city LIKE ? OR c.state LIKE ?
                  ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchPattern = "%$searchTerm%";
        $stmt->bind_param("sssssssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        $stmt->close();
        return $customers;
    }
        
    public function getCustomerOrders($customerId, $userId = null, $email = '', $phone = '') {
        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($userId)) {
            $conditions[] = "o.user_id = ?";
            $params[] = (int)$userId;
            $types .= "i";
        }
        if (!empty($customerId)) {
            $conditions[] = "o.customer_id = ?";
            $params[] = (int)$customerId;
            $types .= "i";
        }
        if (!empty($email)) {
            $conditions[] = "o.customer_email = ?";
            $params[] = $email;
            $types .= "s";
        }
        if (!empty($phone)) {
            $conditions[] = "o.customer_phone = ?";
            $params[] = $phone;
            $types .= "s";
        }

        if (empty($conditions)) {
            return [];
        }

        $whereClause = implode(" OR ", $conditions);
        $query = "SELECT o.*, 
                         COUNT(oi.id) as items_count,
                         SUM(oi.quantity) as total_quantity
                  FROM orders o
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  WHERE {$whereClause}
                  GROUP BY o.id
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        return $orders;
    }

    public function getCustomerAddresses($userId) {
        if (empty($userId)) return [];
        $query = "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $addresses = [];
        while ($row = $result->fetch_assoc()) {
            $addresses[] = $row;
        }
        $stmt->close();
        return $addresses;
    }

    public function getCustomerFullStats($customerId, $userId = null, $email = '', $phone = '') {
        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($userId)) {
            $conditions[] = "user_id = ?";
            $params[] = (int)$userId;
            $types .= "i";
        }
        if (!empty($customerId)) {
            $conditions[] = "customer_id = ?";
            $params[] = (int)$customerId;
            $types .= "i";
        }
        if (!empty($email)) {
            $conditions[] = "customer_email = ?";
            $params[] = $email;
            $types .= "s";
        }
        if (!empty($phone)) {
            $conditions[] = "customer_phone = ?";
            $params[] = $phone;
            $types .= "s";
        }

        if (empty($conditions)) {
            return [
                'total_orders' => 0,
                'total_spent' => 0,
                'avg_order_value' => 0,
                'completed_orders' => 0,
                'cancelled_orders' => 0,
                'pending_orders' => 0,
                'last_order_date' => null
            ];
        }

        $whereClause = implode(" OR ", $conditions);
        $query = "SELECT 
                    COUNT(id) as total_orders,
                    COALESCE(SUM(grand_total), 0) as total_spent,
                    COALESCE(AVG(grand_total), 0) as avg_order_value,
                    COALESCE(SUM(CASE WHEN status IN ('delivered', 'completed') THEN 1 ELSE 0 END), 0) as completed_orders,
                    COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_orders,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
                    MAX(created_at) as last_order_date
                  FROM orders
                  WHERE {$whereClause}";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return [
                'total_orders' => 0,
                'total_spent' => 0,
                'avg_order_value' => 0,
                'completed_orders' => 0,
                'cancelled_orders' => 0,
                'pending_orders' => 0,
                'last_order_date' => null
            ];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        return $stats ?: [
            'total_orders' => 0,
            'total_spent' => 0,
            'avg_order_value' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'pending_orders' => 0,
            'last_order_date' => null
        ];
    }
}
?>