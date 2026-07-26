<?php
/**
 * Address Model
 *
 * NOTE: this model receives the shared mysqli connection (config/database.php).
 * It was originally written against PDO (bindParam / fetch(PDO::FETCH_ASSOC) /
 * lastInsertId), which fatals on mysqli — every address call died with
 * "Call to undefined method mysqli_stmt::bindParam()". All queries below use the
 * mysqli API: bind_param() with a type string, get_result(), and insert_id.
 */
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

        return $this->conn->query($query);
    }

    // Get all addresses for a user
    public function getAddressesByUserId($user_id) {
        try {
            $user_id = (int)$user_id;
            $query = "SELECT * FROM " . $this->table_name . "
                      WHERE user_id = ?
                      ORDER BY is_default DESC, created_at DESC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return [];
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $addresses = [];
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }
            $stmt->close();

            return $addresses;

        } catch (Exception $e) {
            error_log("Error getting addresses: " . $e->getMessage());
            return [];
        }
    }

    // Get single address by ID
    public function getAddressById($id, $user_id) {
        try {
            $id = (int)$id;
            $user_id = (int)$user_id;
            $query = "SELECT * FROM " . $this->table_name . "
                      WHERE id = ? AND user_id = ?";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $row;

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
            if (!$stmt) return false;

            // bind_param() takes variables by reference, so normalise into locals first.
            $user_id  = (int)$user_id;
            $name     = (string)($data['name'] ?? '');
            $phone    = (string)($data['phone'] ?? '');
            $pincode  = (string)($data['pincode'] ?? '');
            $locality = (string)($data['locality'] ?? '');
            $address  = (string)($data['address'] ?? '');
            $city     = (string)($data['city'] ?? '');
            $state    = (string)($data['state'] ?? '');
            $landmark = (string)($data['landmark'] ?? '');
            $altPhone = (string)($data['altPhone'] ?? $data['alt_phone'] ?? '');
            $type     = (string)($data['type'] ?? 'Home');

            $stmt->bind_param(
                "issssssssssi",
                $user_id, $name, $phone, $pincode, $locality, $address,
                $city, $state, $landmark, $altPhone, $type, $is_default
            );

            if ($stmt->execute()) {
                $insertId = $this->conn->insert_id;
                $stmt->close();
                return $insertId;
            }

            $stmt->close();
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
            if (!$stmt) return false;

            $name     = (string)($data['name'] ?? '');
            $phone    = (string)($data['phone'] ?? '');
            $pincode  = (string)($data['pincode'] ?? '');
            $locality = (string)($data['locality'] ?? '');
            $address  = (string)($data['address'] ?? '');
            $city     = (string)($data['city'] ?? '');
            $state    = (string)($data['state'] ?? '');
            $landmark = (string)($data['landmark'] ?? '');
            $altPhone = (string)($data['altPhone'] ?? $data['alt_phone'] ?? '');
            $type     = (string)($data['type'] ?? 'Home');
            $id       = (int)$id;
            $user_id  = (int)$user_id;

            $stmt->bind_param(
                "ssssssssssii",
                $name, $phone, $pincode, $locality, $address,
                $city, $state, $landmark, $altPhone, $type, $id, $user_id
            );

            $result = $stmt->execute();
            $stmt->close();
            return $result;

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

            $id = (int)$id;
            $user_id = (int)$user_id;
            $query = "DELETE FROM " . $this->table_name . "
                     WHERE id = ? AND user_id = ?";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("ii", $id, $user_id);
            $result = $stmt->execute();
            $stmt->close();

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
            $id = (int)$id;
            $user_id = (int)$user_id;

            // First reset all addresses to non-default
            $stmt = $this->conn->prepare(
                "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = ?"
            );
            if (!$stmt) return false;
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Then set the specified address as default
            $stmt = $this->conn->prepare(
                "UPDATE " . $this->table_name . " SET is_default = 1 WHERE id = ? AND user_id = ?"
            );
            if (!$stmt) return false;
            $stmt->bind_param("ii", $id, $user_id);
            $result = $stmt->execute();
            $stmt->close();

            return $result;

        } catch (Exception $e) {
            error_log("Error setting default address: " . $e->getMessage());
            return false;
        }
    }

    // Set new default address after deletion
    private function setNewDefaultAddress($user_id) {
        try {
            $user_id = (int)$user_id;
            $query = "UPDATE " . $this->table_name . "
                     SET is_default = 1
                     WHERE user_id = ?
                     ORDER BY created_at DESC
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("i", $user_id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;

        } catch (Exception $e) {
            error_log("Error setting new default address: " . $e->getMessage());
            return false;
        }
    }

    // Validate address data
    public function validateAddress($data) {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = "Name is required";
        }

        if (empty(trim($data['phone'] ?? ''))) {
            $errors[] = "Phone number is required";
        } elseif (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
            $errors[] = "Phone number must be 10 digits";
        }

        if (empty(trim($data['pincode'] ?? ''))) {
            $errors[] = "Pincode is required";
        } elseif (!preg_match('/^[0-9]{6}$/', $data['pincode'])) {
            $errors[] = "Pincode must be 6 digits";
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

        if (!empty($data['altPhone']) && !preg_match('/^[0-9]{10}$/', $data['altPhone'])) {
            $errors[] = "Alternate phone must be 10 digits";
        }

        return $errors;
    }
}
?>
