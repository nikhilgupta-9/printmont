<?php
// require_once 'config/constants.php';
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/FooterSection.php');

class FooterSectionController {
    private $db;
    private $footerSection;

    public function __construct() {
        $this->db = new Database();
        $this->footerSection = new FooterSection($this->db->getConnection());
    }

    public function getAllSections() {
        return $this->footerSection->getAll();
    }

    public function getSectionById($id) {
        return $this->footerSection->getById($id);
    }

    public function createSection($data) {
        return $this->footerSection->create($data);
    }

    public function updateSection($id, $data) {
        return $this->footerSection->update($id, $data);
    }

    public function deleteSection($id) {
        return $this->footerSection->delete($id);
    }

    public function getActiveSections() {
        return $this->footerSection->getActiveSections();
    }
}
?>