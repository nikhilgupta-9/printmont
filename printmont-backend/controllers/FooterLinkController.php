<?php
require_once 'config/constants.php';
require_once 'models/Database.php';
require_once 'models/FooterLink.php';

class FooterLinkController {
    private $db;
    private $footerLink;

    public function __construct() {
        $this->db = new Database();
        $this->footerLink = new FooterLink($this->db->getConnection());
    }

    public function getAllLinks() {
        return $this->footerLink->getAll();
    }

    public function getLinksBySection($section_id) {
        return $this->footerLink->getBySection($section_id);
    }

    public function getLinkById($id) {
        return $this->footerLink->getById($id);
    }

    public function createLink($data) {
        return $this->footerLink->create($data);
    }

    public function updateLink($id, $data) {
        return $this->footerLink->update($id, $data);
    }

    public function deleteLink($id) {
        return $this->footerLink->delete($id);
    }

    public function getActiveLinks() {
        return $this->footerLink->getActiveLinks();
    }
}
?>