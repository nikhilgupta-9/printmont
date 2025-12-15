<?php

require_once(__DIR__. '/../config/database.php');
require_once(__DIR__. '/../models/CategoryModel.php');

class CategoryController {
    private $db;
    private $category;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->category = new Category($this->db);
    }

    public function getAllCategories() {
        return $this->category->getAllWithHierarchy();
    }

    public function getCategoryById($id) {
        return $this->category->getById($id);
    }

    public function createCategory($data) {
        return $this->category->create($data);
    }

    public function updateCategory($id, $data) {
        return $this->category->update($id, $data);
    }

    public function deleteCategory($id) {
        return $this->category->delete($id);
    }

    public function getParentCategories() {
        return $this->category->getParentCategories();
    }

    public function getSubcategories($parent_id) {
        return $this->category->getSubcategories($parent_id);
    }


     public function getAllCategoriesAPI() {
    try {
        $categories = $this->category->getAllWithHierarchy();
        return $categories; // Only return data, DO NOT echo response
    } catch (Exception $e) {
        return []; // or handle safely
    }
}


    // Add these helper methods for consistent responses
    private function sendSuccessResponse($data = [], $message = 'Success') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    private function sendErrorResponse($error = 'An error occurred') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
        exit;
    }

}
?>