<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FooterPaymentMethod.php';

class FooterPaymentMethodController {
    private $db;
    private $footerPaymentMethod;

    public function __construct($db = null) {
        if ($db instanceof mysqli) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        $this->footerPaymentMethod = new FooterPaymentMethod($this->db);
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