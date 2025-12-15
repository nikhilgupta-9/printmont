<?php

require_once 'config/Database.php';

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
        $query = "SELECT * FROM contact_info LIMIT 1";
        $result = $this->db->query($query);

        return $result->fetch_assoc();
    }

    /* ============================
       UPDATE Contact Information
       ============================ */
    public function updateContactInfo($data)
    {
        $id = $data['id'];

        $help_number = $this->db->real_escape_string($data['help_number']);
        $service_time = $this->db->real_escape_string($data['service_time']);
        $sales_email = $this->db->real_escape_string($data['sales_email']);
        $corporate_email = $this->db->real_escape_string($data['corporate_email']);
        $address_one = $this->db->real_escape_string($data['address_one']);
        $address_two = $this->db->real_escape_string($data['address_two']);

        $query = "
        UPDATE contact_info SET
            help_number = '$help_number',
            service_time = '$service_time',
            sales_email = '$sales_email',
            corporate_email = '$corporate_email',
            address_one = '$address_one',
            address_two = '$address_two'
        WHERE id = $id
    ";

        if ($this->db->query($query)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => $this->db->error];
        }
    }

}

?>