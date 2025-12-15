<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/FooterSocialLink.php');

class FooterSocialLinkController {
    private $db;
    private $footerSocialLink;

    public function __construct() {
        $this->db = new Database();
        $this->footerSocialLink = new FooterSocialLink($this->db->getConnection());
    }

    public function getAllSocialLinks() {
        return $this->footerSocialLink->getAll();
    }

    public function getSocialLinkById($id) {
        return $this->footerSocialLink->getById($id);
    }

    public function createSocialLink($data) {
        return $this->footerSocialLink->create($data);
    }

    public function updateSocialLink($id, $data) {
        return $this->footerSocialLink->update($id, $data);
    }

    public function deleteSocialLink($id) {
        return $this->footerSocialLink->delete($id);
    }

    public function getActiveSocialLinks() {
        return $this->footerSocialLink->getActiveLinks();
    }

    public function getPlatformOptions() {
        return $this->footerSocialLink->getPlatformOptions();
    }
}
?>