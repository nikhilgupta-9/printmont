<?php
// controllers/ContactController.php

require_once __DIR__ . '/../models/ContactModel.php';

class ContactController {
    private $contactModel;
    private $table_name = "contact_info";

    public function __construct() {
        $this->contactModel = new ContactModel();
    }

    public function getContactDetails() {
        return $this->contactModel->getContactInfo();
    }

    public function updateContactDetails($data) {

        if (empty($data['help_number']) || empty($data['sales_email']) || empty($data['address_one'])) {
            return ['success' => false, 'message' => 'Required fields cannot be empty.'];
        }

        $result = $this->contactModel->updateContactInfo($data);
        
        if ($result['success']) {
            return ['success' => true, 'message' => 'Contact details updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Update failed: ' . ($result['message'] ?? 'Database error.')];
        }
    }
}
