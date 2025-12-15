<?php
class AddressModel {
    private $conn;
    private $table_name = "addresses";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create addresses table if not exists
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            pincode VARCHAR(10) NOT NULL,
            locality VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            landmark VARCHAR(255),
            alt_phone VARCHAR(20),
            type ENUM('Home', 'Work') DEFAULT 'Home',
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX user_id_index (user_id)
        )";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    // Get all addresses for a user
    public function getAddressesByUserId($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE user_id = ? 
                      ORDER BY is_default DESC, created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            
            $addresses = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $addresses[] = $row;
            }
            
            return $addresses;
            
        } catch (Exception $e) {
            error_log("Error getting addresses: " . $e->getMessage());
            return [];
        }
    }

    // Get single address by ID
    public function getAddressById($id, $user_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $user_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting address: " . $e->getMessage());
            return false;
        }
    }

    // Add new address
    public function addAddress($user_id, $data) {
        try {
            // If this is the first address, set it as default
            $existingAddresses = $this->getAddressesByUserId($user_id);
            $is_default = empty($existingAddresses) ? 1 : 0;

            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, name, phone, pincode, locality, address, city, state, landmark, alt_phone, type, is_default) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $data['name']);
            $stmt->bindParam(3, $data['phone']);
            $stmt->bindParam(4, $data['pincode']);
            $stmt->bindParam(5, $data['locality']);
            $stmt->bindParam(6, $data['address']);
            $stmt->bindParam(7, $data['city']);
            $stmt->bindParam(8, $data['state']);
            $stmt->bindParam(9, $data['landmark']);
            $stmt->bindParam(10, $data['altPhone']);
            $stmt->bindParam(11, $data['type']);
            $stmt->bindParam(12, $is_default);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error adding address: " . $e->getMessage());
            return false;
        }
    }

    // Update address
    public function updateAddress($id, $user_id, $data) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET name = ?, phone = ?, pincode = ?, locality = ?, address = ?, 
                         city = ?, state = ?, landmark = ?, alt_phone = ?, type = ?, 
                         updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(1, $data['name']);
            $stmt->bindParam(2, $data['phone']);
            $stmt->bindParam(3, $data['pincode']);
            $stmt->bindParam(4, $data['locality']);
            $stmt->bindParam(5, $data['address']);
            $stmt->bindParam(6, $data['city']);
            $stmt->bindParam(7, $data['state']);
            $stmt->bindParam(8, $data['landmark']);
            $stmt->bindParam(9, $data['altPhone']);
            $stmt->bindParam(10, $data['type']);
            $stmt->bindParam(11, $id);
            $stmt->bindParam(12, $user_id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error updating address: " . $e->getMessage());
            return false;
        }
    }

    // Delete address
    public function deleteAddress($id, $user_id) {
        try {
            // First check if this is the default address
            $address = $this->getAddressById($id, $user_id);
            $was_default = $address && $address['is_default'] == 1;

            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $user_id);
            $result = $stmt->execute();

            // If we deleted the default address, set a new default
            if ($result && $was_default) {
                $this->setNewDefaultAddress($user_id);
            }

            return $result;
            
        } catch (Exception $e) {
            error_log("Error deleting address: " . $e->getMessage());
            return false;
        }
    }

    // Set address as default
    public function setDefaultAddress($id, $user_id) {
        try {
            // First reset all addresses to non-default
            $query = "UPDATE " . $this->table_name . " 
                     SET is_default = 0 
                     WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->execute();

            // Then set the specified address as default
            $query = "UPDATE " . $this->table_name . " 
                     SET is_default = 1 
                     WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $user_id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error setting default address: " . $e->getMessage());
            return false;
        }
    }

    // Set new default address after deletion
    private function setNewDefaultAddress($user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET is_default = 1 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error setting new default address: " . $e->getMessage());
            return false;
        }
    }

    // Validate address data
    public function validateAddress($data) {
        $errors = [];

        if (empty(trim($data['name']))) {
            $errors[] = "Name is required";
        }

        if (empty(trim($data['phone']))) {
            $errors[] = "Phone number is required";
        } elseif (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
            $errors[] = "Phone number must be 10 digits";
        }

        if (empty(trim($data['pincode']))) {
            $errors[] = "Pincode is required";
        } elseif (!preg_match('/^[0-9]{6}$/', $data['pincode'])) {
            $errors[] = "Pincode must be 6 digits";
        }

        if (empty(trim($data['locality']))) {
            $errors[] = "Locality is required";
        }

        if (empty(trim($data['address']))) {
            $errors[] = "Address is required";
        }

        if (empty(trim($data['city']))) {
            $errors[] = "City is required";
        }

        if (empty(trim($data['state'])) || $data['state'] === '--Select State--') {
            $errors[] = "State is required";
        }

        if (!empty($data['altPhone']) && !preg_match('/^[0-9]{10}$/', $data['altPhone'])) {
            $errors[] = "Alternate phone must be 10 digits";
        }

        return $errors;
    }
}
?>