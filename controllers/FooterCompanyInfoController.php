<?php
// require_once 'config/constants.php';
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/FooterCompanyInfo.php');

class FooterCompanyInfoController {
    private $db;
    private $footerCompanyInfo;

    public function __construct() {
        $this->db = new Database();
        $this->footerCompanyInfo = new FooterCompanyInfo($this->db->getConnection());
    }

    public function getCompanyInfo() {
        return $this->footerCompanyInfo->get();
    }

    public function updateCompanyInfo($data) {
        return $this->footerCompanyInfo->update($data);
    }
}
?>