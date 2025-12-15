<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/EmailTemplate.php');
require_once(__DIR__ . '/../models/EmailConfiguration.php');

class EmailController {
    private $db;
    private $emailTemplate;
    private $emailConfiguration;

    public function __construct() {
        $this->db = new Database();
        $this->emailTemplate = new EmailTemplate($this->db->getConnection());
        $this->emailConfiguration = new EmailConfiguration($this->db->getConnection());
    }

    // Email Template Methods
    public function getAllTemplates() {
        return $this->emailTemplate->getAll();
    }

    public function getTemplateById($id) {
        return $this->emailTemplate->getById($id);
    }

    public function createTemplate($data) {
        return $this->emailTemplate->create($data);
    }

    public function updateTemplate($id, $data) {
        return $this->emailTemplate->update($id, $data);
    }

    public function deleteTemplate($id) {
        return $this->emailTemplate->delete($id);
    }

    public function getTemplatesByType($type) {
        return $this->emailTemplate->getByType($type);
    }

    // Email Configuration Methods
    public function getAllConfigurations() {
        return $this->emailConfiguration->getAll();
    }

    public function getConfigurationById($id) {
        return $this->emailConfiguration->getById($id);
    }

    public function createConfiguration($data) {
        return $this->emailConfiguration->create($data);
    }

    public function updateConfiguration($id, $data) {
        return $this->emailConfiguration->update($id, $data);
    }

    public function deleteConfiguration($id) {
        return $this->emailConfiguration->delete($id);
    }

    public function getActiveConfiguration() {
        return $this->emailConfiguration->getActive();
    }

    public function testConfiguration($data) {
        return $this->emailConfiguration->testConnection($data);
    }
}
?>