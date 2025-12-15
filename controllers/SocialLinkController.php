<?php
// require_once '../config/constants.php';
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/SocialLink.php');

class SocialLinkController {
    private $db;
    private $socialLink;

    public function __construct() {
        $this->db = new Database();
        $this->socialLink = new SocialLink($this->db->getConnection());
    }

    public function getAllSocialLinks($filters = []) {
        return $this->socialLink->getAll($filters);
    }

    public function getSocialLinkById($id) {
        return $this->socialLink->getById($id);
    }

    public function getSocialLinkByPlatform($platform) {
        return $this->socialLink->getByPlatform($platform);
    }

    public function createSocialLink($data) {
        return $this->socialLink->create($data);
    }

    public function updateSocialLink($id, $data) {
        return $this->socialLink->update($id, $data);
    }

    public function deleteSocialLink($id) {
        return $this->socialLink->delete($id);
    }

    public function updateSocialLinkStatus($id, $status) {
        return $this->socialLink->updateStatus($id, $status);
    }

    public function getActiveSocialLinks() {
        return $this->socialLink->getActiveLinks();
    }

    public function platformExists($platform, $exclude_id = null) {
        return $this->socialLink->platformExists($platform, $exclude_id);
    }

    public function getPlatformOptions() {
        return $this->socialLink->getPlatformOptions();
    }

    public function validateUrl($platform, $url) {
        $platforms = $this->getPlatformOptions();
        
        if (!isset($platforms[$platform])) {
            return false;
        }
        
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        return true;
    }
}
?>