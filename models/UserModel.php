<?php
require_once 'BaseModel.php';

class UserModel extends BaseModel {
    
    public function __construct($db) {
        parent::__construct($db, 'users');
    }

    public function createUser($userData, $customerData = []) {
        $this->db->begin_transaction();
        
        try {
            // Validate that all required user data exists
            if (!isset($userData['firstName']) || !isset($userData['lastName']) || 
                !isset($userData['email']) || !isset($userData['mobile']) || 
                !isset($userData['gender']) || !isset($userData['password'])) {
                throw new Exception("Missing required user data");
            }

            // Prepare user data
            $firstName = $this->db->real_escape_string(trim($userData['firstName']));
            $lastName = $this->db->real_escape_string(trim($userData['lastName']));
            $email = $this->db->real_escape_string(trim($userData['email']));
            $phone = $this->db->real_escape_string(trim($userData['mobile']));
            $gender = $this->db->real_escape_string(trim($userData['gender']));
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert into users table
            $userQuery = "INSERT INTO users (first_name, last_name, email, phone, gender, password, role, status, created_at) 
                         VALUES ('$firstName', '$lastName', '$email', '$phone', '$gender', '$hashedPassword', 'customer', 'active', NOW())";
            
            if (!$this->db->query($userQuery)) {
                throw new Exception("User insertion failed: " . $this->db->error);
            }
            
            $userId = $this->db->insert_id;
            
            // Prepare customer data
            $companyName = $this->db->real_escape_string($customerData['company_name'] ?? ($firstName . ' ' . $lastName));
            $address = $this->db->real_escape_string($customerData['address'] ?? '');
            $city = $this->db->real_escape_string($customerData['city'] ?? '');
            $state = $this->db->real_escape_string($customerData['state'] ?? '');
            $country = $this->db->real_escape_string($customerData['country'] ?? '');
            $postalCode = $this->db->real_escape_string($customerData['postal_code'] ?? '');
            
            // Insert into customers table
            $customerQuery = "INSERT INTO customers (user_id, company_name, phone, address, city, state, country, postal_code, customer_type, status, registration_date, created_at) 
                             VALUES ($userId, '$companyName', '$phone', '$address', '$city', '$state', '$country', '$postalCode', 'individual', 'active', NOW(), NOW())";
            
            if (!$this->db->query($customerQuery)) {
                throw new Exception("Customer insertion failed: " . $this->db->error);
            }
            
            $this->db->commit();
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

      // Generate unique username from email
    private function generateUsername($email) {
        // Extract username part from email (before @)
        $username = strtok($email, '@');
        
        // Remove special characters and make lowercase
        $username = preg_replace('/[^a-zA-Z0-9]/', '', $username);
        $username = strtolower($username);
        
        // Check if username already exists, if yes, append random number
        $originalUsername = $username;
        $counter = 1;
        
        while ($this->checkUsernameExists($username)) {
            $username = $originalUsername . $counter;
            $counter++;
            
            // Safety check to prevent infinite loop
            if ($counter > 100) {
                $username = $originalUsername . '_' . uniqid();
                break;
            }
        }
        
        return $username;
    }

    // Check if username exists
    public function checkUsernameExists($username) {
        $username = $this->db->real_escape_string($username);
        $result = $this->db->query("SELECT id FROM users WHERE username = '$username'");
        return $result->num_rows > 0;
    }

    public function getUserByEmail($email) {
        $email = $this->db->real_escape_string($email);
        $query = "SELECT u.*, c.id as customer_id, c.company_name, c.address, c.city, c.state, c.country, c.postal_code, c.customer_type,
                         c.total_orders, c.total_spent, c.registration_date, c.last_login as customer_last_login
                  FROM users u 
                  LEFT JOIN customers c ON u.id = c.user_id 
                  WHERE u.email = '$email' AND u.status = 'active'";
        
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }

    public function getUserById($id) {
        $id = (int)$id;
        $query = "SELECT u.*, c.id as customer_id, c.company_name, c.address, c.city, c.state, c.country, c.postal_code, c.customer_type,
                         c.total_orders, c.total_spent, c.registration_date, c.last_login as customer_last_login
                  FROM users u 
                  LEFT JOIN customers c ON u.id = c.user_id 
                  WHERE u.id = $id AND u.status = 'active'";
        
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }

    public function verifyPassword($email, $password) {
        $user = $this->getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }

    public function updateLastLogin($userId) {
        $userId = (int)$userId;
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = $userId");
        $this->db->query("UPDATE customers SET last_login = NOW() WHERE user_id = $userId");
    }

    public function checkEmailExists($email) {
        $email = $this->db->real_escape_string($email);
        $result = $this->db->query("SELECT id FROM users WHERE email = '$email'");
        return $result->num_rows > 0;
    }

    public function checkPhoneExists($phone) {
        $phone = $this->db->real_escape_string($phone);
        $result = $this->db->query("SELECT id FROM users WHERE phone = '$phone'");
        return $result->num_rows > 0;
    }

    public function updateProfile($userId, $userData, $customerData = []) {
        $this->db->begin_transaction();
        
        try {
            // Update users table
            $firstName = $this->db->real_escape_string(trim($userData['firstName']));
            $lastName = $this->db->real_escape_string(trim($userData['lastName']));
            $phone = $this->db->real_escape_string(trim($userData['phone']));
            $gender = $this->db->real_escape_string(trim($userData['gender']));
            
            $userQuery = "UPDATE users SET first_name = '$firstName', last_name = '$lastName', phone = '$phone', gender = '$gender', updated_at = NOW() WHERE id = $userId";
            
            if (!$this->db->query($userQuery)) {
                throw new Exception("User update failed: " . $this->db->error);
            }
            
            // Update customers table
            $companyName = $this->db->real_escape_string($customerData['company_name'] ?? '');
            $address = $this->db->real_escape_string($customerData['address'] ?? '');
            $city = $this->db->real_escape_string($customerData['city'] ?? '');
            $state = $this->db->real_escape_string($customerData['state'] ?? '');
            $country = $this->db->real_escape_string($customerData['country'] ?? '');
            $postalCode = $this->db->real_escape_string($customerData['postal_code'] ?? '');
            
            $customerQuery = "UPDATE customers SET company_name = '$companyName', phone = '$phone', address = '$address', city = '$city', state = '$state', country = '$country', postal_code = '$postalCode', updated_at = NOW() WHERE user_id = $userId";
            
            if (!$this->db->query($customerQuery)) {
                throw new Exception("Customer update failed: " . $this->db->error);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function changePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = '$hashedPassword', updated_at = NOW() WHERE id = $userId";
        return $this->db->query($query);
    }
}
?>