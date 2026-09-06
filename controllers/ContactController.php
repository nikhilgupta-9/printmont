<?php
require_once __DIR__ . '/../models/ContactModel.php';
require_once __DIR__ . '/../services/MailService.php';

class ContactController {
    private $contactModel;

    public function __construct() {
        $this->contactModel = new ContactModel();
    }

    public function getContactDetails() {
        return $this->contactModel->getContactInfo();
    }

    /**
     * Public contact form submission.
     */
    public function submitInquiry($data) {
        $errors = $this->contactModel->validateInquiry($data);

        if (!empty($errors)) {
            return [
                'success' => false,
                'reason'  => 'invalid',
                'message' => implode(', ', $errors),
            ];
        }

        $inquiry = [
            'name'    => trim($data['name']),
            'email'   => trim($data['email']),
            'phone'   => trim($data['phone'] ?? ''),
            'subject' => trim($data['subject'] ?? ''),
            'message' => trim($data['message']),
        ];

        $result = $this->contactModel->createInquiry($inquiry);

        if (!$result['success']) {
            return $result;
        }

        // Create admin notification for new contact inquiry
        try {
            require_once __DIR__ . '/NotificationController.php';
            $notifController = new NotificationController();
            $subjectPreview = !empty($inquiry['subject']) ? $inquiry['subject'] : substr($inquiry['message'], 0, 40) . '...';
            $notifController->createNotification([
                'title' => 'New Contact Inquiry',
                'message' => 'New inquiry from ' . $inquiry['name'] . ' (' . $inquiry['email'] . '): ' . $subjectPreview,
                'type' => 'info',
                'icon' => 'message-square',
                'link' => 'contact-inquiries.php'
            ]);
        } catch (Exception $e) {
            error_log('Contact inquiry notification error: ' . $e->getMessage());
        }

        // Acknowledge by email. Storing the enquiry is the operation that
        // matters, so a mail failure is reported alongside a successful save
        // rather than turning the whole submission into an error.
        $mailer = new MailService();
        $sent = $mailer->sendContactAcknowledgement(
            $inquiry['email'],
            $inquiry['name'],
            $inquiry['subject'],
            $inquiry['message'],
            $this->contactModel->getContactInfo() ?: []
        );

        $result['mail_sent'] = (bool) $sent;
        if (!$sent) {
            $result['mail_error'] = $mailer->getLastError();
            error_log('Contact acknowledgement not sent to ' . $inquiry['email'] . ': ' . $mailer->getLastError());
        }

        return $result;
    }

    public function getInquiries($status = '') {
        return $this->contactModel->getInquiries($status);
    }

    public function updateInquiryStatus($id, $status, $notes = '') {
        return $this->contactModel->updateInquiryStatus($id, $status, $notes);
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