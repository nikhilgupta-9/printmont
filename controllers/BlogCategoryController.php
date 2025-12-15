<?php
require_once( __DIR__ . '/../models/BlogCategoryModel.php');

class BlogCategoryController {
    private $model;

    public function __construct() {
        $this->model = new BlogCategoryModel();
    }

    public function getAllCategories() {
        return $this->model->getAllCategories();
    }

    public function getActiveCategories() {
        return $this->model->getActiveCategories();
    }

    public function getCategoryById($id) {
        return $this->model->getCategoryById($id);
    }

    public function createCategory($data) {
        return $this->model->createCategory($data);
    }

    public function updateCategory($id, $data) {
        return $this->model->updateCategory($id, $data);
    }

    public function deleteCategory($id) {
        return $this->model->deleteCategory($id);
    }

    public function updateStatus($id, $status) {
        return $this->model->updateStatus($id, $status);
    }
}
?>