<?php
require_once 'config/constants.php';
require_once 'models/Database.php';
require_once 'models/FooterBottomLink.php';

class FooterBottomLinkController {
    private $db;
    private $footerBottomLink;

    public function __construct() {
        $this->db = new Database();
        $this->footerBottomLink = new FooterBottomLink($this->db->getConnection());
    }

    public function getAllBottomLinks() {
        return $this->footerBottomLink->getAll();
    }

    public function getBottomLinkById($id) {
        return $this->footerBottomLink->getById($id);
    }

    public function createBottomLink($data) {
        return $this->footerBottomLink->create($data);
    }

    public function updateBottomLink($id, $data) {
        return $this->footerBottomLink->update($id, $data);
    }

    public function deleteBottomLink($id) {
        return $this->footerBottomLink->delete($id);
    }

    public function getActiveBottomLinks() {
        return $this->footerBottomLink->getActiveLinks();
    }
}
?>