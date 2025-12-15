<?php
// controllers/HelpCenterController.php
require_once 'models/HelpCenterModel.php';

class HelpCenterController {
    private $model;

    public function __construct() {
        $this->model = new HelpCenterModel();
    }

    // Categories Methods
    public function getAllCategories() {
        return $this->model->getAllCategories();
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

    // Articles Methods
    public function getAllArticles($categoryId = null) {
        return $this->model->getAllArticles($categoryId);
    }

    public function getArticleById($id) {
        return $this->model->getArticleById($id);
    }

    public function getArticleBySlug($slug) {
        return $this->model->getArticleBySlug($slug);
    }

    public function createArticle($data) {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->model->generateSlug($data['title']);
        }
        return $this->model->createArticle($data);
    }

    public function updateArticle($id, $data) {
        return $this->model->updateArticle($id, $data);
    }

    public function deleteArticle($id) {
        return $this->model->deleteArticle($id);
    }

    // FAQs Methods
    public function getAllFaqs($categoryId = null) {
        return $this->model->getAllFaqs($categoryId);
    }

    public function getFaqById($id) {
        return $this->model->getFaqById($id);
    }

    public function createFaq($data) {
        return $this->model->createFaq($data);
    }

    public function updateFaq($id, $data) {
        return $this->model->updateFaq($id, $data);
    }

    public function deleteFaq($id) {
        return $this->model->deleteFaq($id);
    }

    // Statistics
    public function getHelpCenterStats() {
        return $this->model->getHelpCenterStats();
    }

    // Icon options for categories
    public function getIconOptions() {
        return [
            'fas fa-question-circle' => 'Question Circle',
            'fas fa-book' => 'Book',
            'fas fa-file-alt' => 'File',
            'fas fa-cog' => 'Settings',
            'fas fa-shopping-cart' => 'Shopping Cart',
            'fas fa-truck' => 'Shipping',
            'fas fa-credit-card' => 'Payment',
            'fas fa-user' => 'User',
            'fas fa-lock' => 'Security',
            'fas fa-mobile-alt' => 'Mobile',
            'fas fa-desktop' => 'Desktop',
            'fas fa-print' => 'Print',
            'fas fa-palette' => 'Design',
            'fas fa-images' => 'Images',
            'fas fa-download' => 'Download',
            'fas fa-upload' => 'Upload',
            'fas fa-sync' => 'Sync',
            'fas fa-info-circle' => 'Info',
            'fas fa-exclamation-triangle' => 'Warning',
            'fas fa-check-circle' => 'Check'
        ];
    }
}
?>