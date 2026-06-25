<?php
// controllers/AboutUsController.php
require_once(__DIR__ . '/../models/AboutUsModel.php');

class AboutUsController {
    private $model;

    public function __construct() {
        $this->model = new AboutUsModel();
    }

    // Section Methods
    public function getAllSections() {
        return $this->model->getAllSections();
    }

    public function getSectionById($id) {
        return $this->model->getSectionById($id);
    }

    public function createSection($data) {
        return $this->model->createSection($data);
    }

    public function updateSection($id, $data) {
        return $this->model->updateSection($id, $data);
    }

    public function deleteSection($id) {
        return $this->model->deleteSection($id);
    }

    // Team Member Methods
    public function getAllTeamMembers() {
        return $this->model->getAllTeamMembers();
    }

    public function getTeamMemberById($id) {
        return $this->model->getTeamMemberById($id);
    }

    public function createTeamMember($data) {
        return $this->model->createTeamMember($data);
    }

    public function updateTeamMember($id, $data) {
        return $this->model->updateTeamMember($id, $data);
    }

    public function deleteTeamMember($id) {
        return $this->model->deleteTeamMember($id);
    }

    // File Upload Method
    public function uploadImage($file) {
        $targetDir = "uploads/about-us/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Generate unique filename
        $fileName = time() . '_' . uniqid() . '_' . basename($file["name"]);
        $targetFilePath = $targetDir . $fileName;

        // Check if file is an actual image
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            throw new Exception("File is not an image.");
        }

        // Check file size (5MB limit)
        if ($file["size"] > 5000000) {
            throw new Exception("Sorry, your file is too large.");
        }

        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            throw new Exception("Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.");
        }

        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return $targetFilePath;
        }
        
        throw new Exception("Sorry, there was an error uploading your file.");
    }

    
}
?>