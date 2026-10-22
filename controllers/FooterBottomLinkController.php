<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FooterBottomLink.php';

class FooterBottomLinkController {
    private $db;
    private $footerBottomLink;

    public function __construct($db = null) {
        if ($db instanceof mysqli) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        $this->footerBottomLink = new FooterBottomLink($this->db);
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