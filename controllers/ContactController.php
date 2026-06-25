<?php
require_once __DIR__ . '/../models/ContactModel.php';

class ContactController {
    private $contactModel;

    public function __construct() {
        $this->contactModel = new ContactModel();
    }

    public function getContactDetails() {
        return $this->contactModel->getContactInfo();
    }

    public function updateContactDetails($data) {
        // Validate data
        $validationErrors = $this->contactModel->validateContactData($data);
        
        if (!empty($validationErrors)) {
            return [
                'success' => false, 
                'message' => implode(', ', $validationErrors)
            ];
        }

        // Process the update
        $result = $this->contactModel->updateContactInfo($data);
        
        return $result;
    }

    // public function getContactInfoForAPI() {
    //     $contactInfo = $this->contactModel->getContactInfo();
        
    //     return [
    //         'success' => true,
    //         'data' => [
    //             'help_number' => $contactInfo['help_number'] ?? '',
    //             'service_time' => $contactInfo['service_time'] ?? '',
    //             'sales_email' => $contactInfo['sales_email'] ?? '',
    //             'corporate_email' => $contactInfo['corporate_email'] ?? '',
    //             'address_one' => $contactInfo['address_one'] ?? '',
    //             'address_two' => $contactInfo['address_two'] ?? '',
    //             'updated_at' => $contactInfo['updated_at'] ?? ''
    //         ]
    //     ];
    // }

    public function getContactInfoForAPI() {
    $contactInfo = $this->contactModel->getContactInfo();
    
    if (empty($contactInfo['id'])) {
        return [
            'success' => false,
            'message' => 'No contact information found'
        ];
    }
    
    return [
        'success' => true,
        'data' => [
            'id' => $contactInfo['id'],
            'help_number' => $contactInfo['help_number'] ?? '',
            'service_time' => $contactInfo['service_time'] ?? '',
            'sales_email' => $contactInfo['sales_email'] ?? '',
            'corporate_email' => $contactInfo['corporate_email'] ?? '',
            'address_one' => $contactInfo['address_one'] ?? '',
            'address_two' => $contactInfo['address_two'] ?? '',
            'is_active' => (bool)($contactInfo['is_active'] ?? true),
            'updated_at' => $contactInfo['updated_at'] ?? '',
            'last_updated' => !empty($contactInfo['updated_at']) ? 
                date('Y-m-d H:i:s', strtotime($contactInfo['updated_at'])) : 
                date('Y-m-d H:i:s')
        ]
    ];
}
}
?>