<?php
require_once(__DIR__ . '/../models/BannerLayoutModel.php');
require_once(__DIR__. '/../models/Review.php');

class ReviewController {
    private $db;
    private $review;

    public function __construct() {
        $this->db = new Database();
        $this->review = new Review($this->db->getConnection());
    }

    public function getAllReviews($page = 1, $limit = 10, $filters = []) {
        return $this->review->getAllReviews($page, $limit, $filters);
    }

    public function getReviewById($id) {
        return $this->review->getReviewById($id);
    }

    public function createReview($data) {
        return $this->review->create($data);
    }

    public function updateReview($id, $data) {
        return $this->review->update($id, $data);
    }

    public function deleteReview($id) {
        return $this->review->delete($id);
    }

    public function updateReviewStatus($id, $status) {
        return $this->review->updateStatus($id, $status);
    }

    public function getReviewStats() {
        return $this->review->getReviewStats();
    }

    public function getProductReviews($product_id, $status = 'approved') {
        return $this->review->getProductReviews($product_id, $status);
    }

    public function getProductRatingStats($product_id) {
        return $this->review->getProductRatingStats($product_id);
    }
}
?>