<?php
require_once 'config/constants.php';
require_once 'models/Database.php';
require_once 'models/User.php';

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = new User($this->db->getConnection());
    }

    public function getUsersWithoutCustomerProfile() {
        // Get users who don't have entries in customers table
        $query = "SELECT u.id, u.username, u.email 
                  FROM users u 
                  LEFT JOIN customers c ON u.id = c.user_id 
                  WHERE c.user_id IS NULL 
                  AND u.role = 'customer' 
                  ORDER BY u.username ASC";
        
        $result = $this->conn->query($query);
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }

    public function getUserById($id) {
        return $this->user->getById($id);
    }

    public function getAllUsers() {
        return $this->user->getAll();
    }
}
?>