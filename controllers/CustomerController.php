<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/Customer.php');

class CustomerController {
    private $db;
    private $customer;

    public function __construct() {
        $this->db = new Database();
        $this->customer = new Customer($this->db->getConnection());
    }

    public function getAllCustomers($limit = null, $offset = 0) {
        return $this->customer->getAll($limit, $offset);
    }

    public function getCustomerById($id) {
        return $this->customer->getById($id);
    }

    public function getCustomerByUserId($userId) {
        return $this->customer->getByUserId($userId);
    }

    public function createCustomer($data) {
        return $this->customer->create($data);
    }

    public function updateCustomer($id, $data) {
        return $this->customer->update($id, $data);
    }

    public function deleteCustomer($id) {
        return $this->customer->delete($id);
    }

    public function updateCustomerStatus($id, $status) {
        return $this->customer->updateStatus($id, $status);
    }

    public function updateCustomerLastLogin($userId) {
        return $this->customer->updateLastLogin($userId);
    }

    public function incrementCustomerOrderStats($customerId, $amount) {
        return $this->customer->incrementOrderStats($customerId, $amount);
    }

    public function getCustomerStats() {
        return $this->customer->getStats();
    }

    public function searchCustomers($searchTerm) {
        return $this->customer->search($searchTerm);
    }

    public function validateCustomerData($data) {
        $errors = [];

        if (empty($data['user_id'])) {
            $errors[] = "User ID is required.";
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{10,20}$/', $data['phone'])) {
            $errors[] = "Invalid phone number format.";
        }

        if (!empty($data['postal_code']) && !preg_match('/^[0-9a-zA-Z\-\s]{3,10}$/', $data['postal_code'])) {
            $errors[] = "Invalid postal code format.";
        }

        return $errors;
    }
}
?>