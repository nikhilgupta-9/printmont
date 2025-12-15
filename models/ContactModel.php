<?php
require_once(__DIR__ . '/../config/database.php');

class ContactModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /* ============================
       GET Contact Information
       ============================ */
    public function getContactInfo()
    {
        $query = "SELECT * FROM contact_info WHERE is_active = 1 LIMIT 1";
        $result = $this->db->query($query);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Return default structure if no record found
        return [
            'id' => '',
            'help_number' => '',
            'service_time' => '',
            'sales_email' => '',
            'corporate_email' => '',
            'address_one' => '',
            'address_two' => '',
            'is_active' => 1
        ];
    }

    /* ============================
       UPDATE Contact Information
       ============================ */
    public function updateContactInfo($data)
    {
        // Check if record exists
        $existing = $this->getContactInfo();
        
        if (empty($existing['id'])) {
            // Insert new record
            return $this->insertContactInfo($data);
        } else {
            // Update existing record
            return $this->updateExistingContactInfo($data);
        }
    }

    /* ============================
       INSERT Contact Information
       ============================ */
    private function insertContactInfo($data)
    {
        $help_number = $this->db->real_escape_string($data['help_number']);
        $service_time = $this->db->real_escape_string($data['service_time'] ?? '');
        $sales_email = $this->db->real_escape_string($data['sales_email']);
        $corporate_email = $this->db->real_escape_string($data['corporate_email'] ?? '');
        $address_one = $this->db->real_escape_string($data['address_one']);
        $address_two = $this->db->real_escape_string($data['address_two'] ?? '');

        $query = "
            INSERT INTO contact_info (
                help_number, service_time, sales_email, corporate_email, 
                address_one, address_two, is_active, updated_at
            ) VALUES (
                '$help_number', '$service_time', '$sales_email', '$corporate_email',
                '$address_one', '$address_two', 1, NOW()
            )
        ";

        if ($this->db->query($query)) {
            return ['success' => true, 'message' => 'Contact information created successfully!'];
        } else {
            return ['success' => false, 'message' => 'Insert failed: ' . $this->db->error];
        }
    }

    /* ============================
       UPDATE Existing Contact Information
       ============================ */
    private function updateExistingContactInfo($data)
    {
        $id = $this->db->real_escape_string($data['id']);
        $help_number = $this->db->real_escape_string($data['help_number']);
        $service_time = $this->db->real_escape_string($data['service_time'] ?? '');
        $sales_email = $this->db->real_escape_string($data['sales_email']);
        $corporate_email = $this->db->real_escape_string($data['corporate_email'] ?? '');
        $address_one = $this->db->real_escape_string($data['address_one']);
        $address_two = $this->db->real_escape_string($data['address_two'] ?? '');

        $query = "
            UPDATE contact_info SET
                help_number = '$help_number',
                service_time = '$service_time',
                sales_email = '$sales_email',
                corporate_email = '$corporate_email',
                address_one = '$address_one',
                address_two = '$address_two',
                updated_at = NOW()
            WHERE id = '$id'
        ";

        if ($this->db->query($query)) {
            return ['success' => true, 'message' => 'Contact information updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Update failed: ' . $this->db->error];
        }
    }

    /* ============================
       VALIDATE Contact Data
       ============================ */
    public function validateContactData($data)
    {
        $errors = [];

        // Required fields validation
        if (empty(trim($data['help_number']))) {
            $errors[] = "Help number is required";
        }

        if (empty(trim($data['sales_email']))) {
            $errors[] = "Sales email is required";
        } elseif (!filter_var($data['sales_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid sales email format";
        }

        if (empty(trim($data['address_one']))) {
            $errors[] = "Address one is required";
        }

        // Optional field validation
        if (!empty($data['corporate_email']) && !filter_var($data['corporate_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid corporate email format";
        }

        return $errors;
    }
}
?>