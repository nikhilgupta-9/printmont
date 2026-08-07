<?php
require_once __DIR__ . '/../config/database.php';

class AddressModel {
    private $db;
    private $table_name = "addresses";

    public function __construct($db = null) {
        if ($db instanceof Database) {
            $this->db = $db;
        } else {
            $this->db = new Database();
        }
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

        try {
            return $this->db->execute($query);
        } catch (Exception $e) {
            error_log("createTable error: " . $e->getMessage());
            return false;
        }
    }

    // Get all addresses for a user
    public function getAddressesByUserId($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE user_id = ? 
                      ORDER BY is_default DESC, created_at DESC";
            
            return $this->db->fetchAll($query, [(int)$user_id]);
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
            
            return $this->db->fetch($query, [(int)$id, (int)$user_id]);
        } catch (Exception $e) {
            error_log("Error getting address: " . $e->getMessage());
            return false;
        }
    }

    // Add new address
    public function addAddress($user_id, $data) {
        try {
            $existingAddresses = $this->getAddressesByUserId($user_id);
            $is_default = empty($existingAddresses) ? 1 : 0;

            $altPhone = $data['altPhone'] ?? $data['alt_phone'] ?? '';
            $landmark = $data['landmark'] ?? '';
            $type = $data['type'] ?? 'Home';

            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, name, phone, pincode, locality, address, city, state, landmark, alt_phone, type, is_default) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            return $this->db->insert($query, [
                (int)$user_id,
                $data['name'],
                $data['phone'],
                $data['pincode'],
                $data['locality'],
                $data['address'],
                $data['city'],
                $data['state'],
                $landmark,
                $altPhone,
                $type,
                $is_default
            ]);
        } catch (Exception $e) {
            error_log("Error adding address: " . $e->getMessage());
            return false;
        }
    }

    // Update address
    public function updateAddress($id, $user_id, $data) {
        try {
            if (!$this->belongsToUser($id, $user_id)) {
                return false;
            }

            $altPhone = $data['altPhone'] ?? $data['alt_phone'] ?? '';
            $landmark = $data['landmark'] ?? '';
            $type = $data['type'] ?? 'Home';

            $query = "UPDATE " . $this->table_name . " 
                     SET name = ?, phone = ?, pincode = ?, locality = ?, address = ?, 
                         city = ?, state = ?, landmark = ?, alt_phone = ?, type = ?
                     WHERE id = ? AND user_id = ?";
            
            return $this->db->execute($query, [
                $data['name'],
                $data['phone'],
                $data['pincode'],
                $data['locality'],
                $data['address'],
                $data['city'],
                $data['state'],
                $landmark,
                $altPhone,
                $type,
                (int)$id,
                (int)$user_id
            ]);
        } catch (Exception $e) {
            error_log("Error updating address: " . $e->getMessage());
            return false;
        }
    }

    // Delete address
    /**
     * True only when this address exists AND belongs to this user.
     *
     * execute() reports success even when a statement matches zero rows, so an
     * "id = ? AND user_id = ?" write cannot tell "not yours" from "done". Callers
     * check ownership up front instead.
     */
    public function belongsToUser($id, $user_id) {
        $row = $this->db->fetch(
            "SELECT id FROM " . $this->table_name . " WHERE id = ? AND user_id = ?",
            [(int)$id, (int)$user_id]
        );
        return (bool) $row;
    }

    public function deleteAddress($id, $user_id = null) {
        try {
            // The previous version fell back to "DELETE ... WHERE id = ?" with no
            // user_id when the scoped delete returned falsy — one statement error
            // away from letting anyone delete anyone else's address.
            if ($user_id !== null && !$this->belongsToUser($id, $user_id)) {
                return false;
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $params = [(int)$id];

            if ($user_id !== null) {
                $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
                $params[] = (int)$user_id;
            }

            return $this->db->execute($query, $params);
        } catch (Exception $e) {
            error_log("Error deleting address: " . $e->getMessage());
            return false;
        }
    }

    // Set address as default
    public function setDefaultAddress($id, $user_id) {
        try {
            // Report "not yours" instead of a success that changed nothing.
            if (!$this->belongsToUser($id, $user_id)) {
                return false;
            }

            $queryReset = "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = ?";
            $this->db->execute($queryReset, [(int)$user_id]);

            $query = "UPDATE " . $this->table_name . " SET is_default = 1 WHERE id = ? AND user_id = ?";
            return $this->db->execute($query, [(int)$id, (int)$user_id]);
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
            
            return $this->db->execute($query, [(int)$user_id]);
        } catch (Exception $e) {
            error_log("Error setting new default address: " . $e->getMessage());
            return false;
        }
    }

    // Validate address data
    public function validateAddress(&$data) {
        $errors = [];

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
            if (strlen($data['phone']) > 10) {
                $data['phone'] = substr($data['phone'], -10);
            }
        }

        if (isset($data['altPhone'])) {
            $data['altPhone'] = preg_replace('/\D/', '', $data['altPhone']);
            if (strlen($data['altPhone']) > 10) {
                $data['altPhone'] = substr($data['altPhone'], -10);
            }
        }

        if (isset($data['pincode'])) {
            $data['pincode'] = preg_replace('/\D/', '', $data['pincode']);
        }

        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = "Name is required";
        }

        if (empty($data['phone']) || strlen($data['phone']) < 10) {
            $errors[] = "Please provide a valid 10-digit phone number";
        }

        if (empty($data['pincode'])) {
            $errors[] = "Pincode is required";
        }

        if (empty(trim($data['locality'] ?? ''))) {
            $errors[] = "Locality is required";
        }

        if (empty(trim($data['address'] ?? ''))) {
            $errors[] = "Address is required";
        }

        if (empty(trim($data['city'] ?? ''))) {
            $errors[] = "City is required";
        }

        if (empty(trim($data['state'] ?? '')) || $data['state'] === '--Select State--') {
            $errors[] = "State is required";
        }

        return $errors;
    }
}
?>