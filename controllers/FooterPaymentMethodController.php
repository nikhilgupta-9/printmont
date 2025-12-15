<?php
require_once 'config/constants.php';
require_once 'models/Database.php';
require_once 'models/FooterPaymentMethod.php';

class FooterPaymentMethodController {
    private $db;
    private $footerPaymentMethod;

    public function __construct() {
        $this->db = new Database();
        $this->footerPaymentMethod = new FooterPaymentMethod($this->db->getConnection());
    }

    public function getAllPaymentMethods() {
        return $this->footerPaymentMethod->getAll();
    }

    public function getPaymentMethodById($id) {
        return $this->footerPaymentMethod->getById($id);
    }

    public function createPaymentMethod($data) {
        return $this->footerPaymentMethod->create($data);
    }

    public function updatePaymentMethod($id, $data) {
        return $this->footerPaymentMethod->update($id, $data);
    }

    public function deletePaymentMethod($id) {
        return $this->footerPaymentMethod->delete($id);
    }

    public function getActivePaymentMethods() {
        return $this->footerPaymentMethod->getActiveMethods();
    }

    public function getPaymentOptions() {
        return $this->footerPaymentMethod->getPaymentOptions();
    }
}
?>