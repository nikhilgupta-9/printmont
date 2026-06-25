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
$review = $reviewController->getReviewById($reviewId);

if (!$review) {
    $_SESSION['error_message'] = "Review not found!";
    header("Location: reviews.php");
    exit();
}

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    if ($reviewController->updateReviewStatus($reviewId, $status)) {
        $_SESSION['success_message'] = "Review status updated successfully!";
        header("Location: view-review.php?id=" . $reviewId);
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update review status.";
    }
    
    // Refresh review data
    $review = $reviewController->getReviewById($reviewId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Review #<?php echo $review['id']; ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .review-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .rating-stars { color: #ffc107; font-size: 1.5rem; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .verified-badge { color: #28a745; }
        .product-image { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Review Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card review-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h2 class="card-title text-white mb-1">Review #<?php echo $review['id']; ?></h2>
                                            <p class="text-white-50 mb-0">
                                                Submitted on <?php echo date('F j, Y \a\t g:i A', strtotime($review['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <span class="status-badge status-<?php echo $review['status']; ?> me-2">
                                                <?php echo ucfirst($review['status']); ?>
                                            </span>
                                            <?php if ($review['is_verified']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Verified
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Review Details -->
                        <div class="col-lg-8">
                            <!-- Product Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Product Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <?php if (!empty($review['product_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($review['product_image']); ?>" 
                                                     class="product-image" 
                                                     alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                            <?php else: ?>
                                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-box fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-10">
                                            <h4><?php echo htmlspecialchars($review['product_name']); ?></h4>
                                            <?php if (!empty($review['product_sku'])): ?>
                                                <p class="text-muted mb-1">SKU: <?php echo htmlspecialchars($review['product_sku']); ?></p>
                                            <?php endif; ?>
                                            <p class="mb-0">Product ID: <?php echo $review['product_id']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Content -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Review Content</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <div class="rating-stars mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-empty'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-2">(<?php echo $review['rating']; ?>/5)</span>
                                        </div>
                                        
                                        <?php if (!empty($review['title'])): ?>
                                            <h4 class="text-primary"><?php echo htmlspecialchars($review['title']); ?></h4>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($review['comment'])): ?>
                                            <div class="border rounded p-3 bg-light">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted fst-italic">No comment provided.</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Helpful Votes -->
                                    <div class="row text-center">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3">
                                                <h5 class="text-success"><?php echo $review['helpful_votes']; ?></h5>
                                                <p class="mb-0 text-muted">Helpful Votes</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3">
                                                <h5 class="text-info"><?php echo $review['total_votes']; ?></h5>
                                                <p class="mb-0 text-muted">Total Votes</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Customer Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <h6><?php echo htmlspecialchars($review['customer_name']); ?></h6>
                                    <?php if (!empty($review['customer_email'])): ?>
                                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($review['customer_email']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($review['user_id'])): ?>
                                        <p class="mb-0"><i class="fas fa-user me-2"></i>User ID: <?php echo $review['user_id']; ?></p>
                                    <?php else: ?>
                                        <p class="mb-0"><i class="fas fa-user me-2"></i>Guest Customer</p>
                                    <?php endif; ?>
                                    
                                    <?php if ($review['is_verified']): ?>
                                        <div class="mt-2">
                                            <span class="verified-badge">
                                                <i class="fas fa-check-circle"></i> Verified Purchase
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Review Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Review Actions</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Status Update Form -->
                                    <form method="POST" class="mb-3">
                                        <div class="mb-3">
                                            <label class="form-label">Update Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="pending" <?php echo $review['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $review['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $review['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                    </form>

                                    <hr>
                                    <div class="d-grid gap-2">
                                        <a href="edit-review.php?id=<?php echo $reviewId; ?>" class="btn btn-outline-primary">Edit Review</a>
                                        <a href="delete-review.php?id=<?php echo $reviewId; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this review?')">Delete Review</a>
                                        <a href="reviews.php" class="btn btn-outline-secondary">Back to Reviews</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Metadata -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Review Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Created:</strong><br>
                                        <span class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Last Updated:</strong><br>
                                        <span class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($review['updated_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="mb-0">
                                        <strong>Review ID:</strong><br>
                                        <span class="text-muted">#<?php echo $review['id']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>