<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/ReviewController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid review ID!";
    header("Location: reviews.php");
    exit();
}

$reviewId = (int)$_GET['id'];
$reviewController = new ReviewController();

if ($reviewController->deleteReview($reviewId)) {
    $_SESSION['success_message'] = "Review deleted successfully!";
} else {
    $_SESSION['error_message'] = "Failed to delete review.";
}

header("Location: reviews.php");
exit();
?>