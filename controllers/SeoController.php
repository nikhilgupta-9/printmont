<?php
// require_once 'config/constants.php';
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../models/GoogleAnalytics.php');
require_once(__DIR__ . '/../models/FacebookPixel.php');
require_once(__DIR__ . '/../models/MetaKeyword.php');

class SeoController {
    private $db;
    private $googleAnalytics;
    private $facebookPixel;
    private $metaKeyword;

    public function __construct() {
        $this->db = new Database();
        $this->googleAnalytics = new GoogleAnalytics($this->db->getConnection());
        $this->facebookPixel = new FacebookPixel($this->db->getConnection());
        $this->metaKeyword = new MetaKeyword($this->db->getConnection());
    }

    // Google Analytics Methods
    public function getAllAnalytics() {
        return $this->googleAnalytics->getAll();
    }

    public function getAnalyticsById($id) {
        return $this->googleAnalytics->getById($id);
    }

    public function createAnalytics($data) {
        return $this->googleAnalytics->create($data);
    }

    public function updateAnalytics($id, $data) {
        return $this->googleAnalytics->update($id, $data);
    }

    public function deleteAnalytics($id) {
        return $this->googleAnalytics->delete($id);
    }

    public function getActiveAnalytics() {
        return $this->googleAnalytics->getActive();
    }

    // Facebook Pixel Methods
    public function getAllPixels() {
        return $this->facebookPixel->getAll();
    }

    public function getPixelById($id) {
        return $this->facebookPixel->getById($id);
    }

    public function createPixel($data) {
        return $this->facebookPixel->create($data);
    }

    public function updatePixel($id, $data) {
        return $this->facebookPixel->update($id, $data);
    }

    public function deletePixel($id) {
        return $this->facebookPixel->delete($id);
    }

    public function getActivePixel() {
        return $this->facebookPixel->getActive();
    }

    // Meta Keywords Methods
    public function getAllMetaKeywords() {
        return $this->metaKeyword->getAll();
    }

    public function getMetaKeywordById($id) {
        return $this->metaKeyword->getById($id);
    }

    public function createMetaKeyword($data) {
        return $this->metaKeyword->create($data);
    }

    public function updateMetaKeyword($id, $data) {
        return $this->metaKeyword->update($id, $data);
    }

    public function deleteMetaKeyword($id) {
        return $this->metaKeyword->delete($id);
    }

    public function getMetaKeywordByUrl($url) {
        return $this->metaKeyword->getByUrl($url);
    }
}
?>