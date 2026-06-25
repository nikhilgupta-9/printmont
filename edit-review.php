<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/ReviewController.php';
require_once 'controllers/ProductController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid review ID!";
    header("Location: reviews.php");
    exit();
}

$reviewId = (int)$_GET['id'];
$reviewController = new ReviewController();
$productController = new ProductController();
$review = $reviewController->getReviewById($reviewId);

if (!$review) {
    $_SESSION['error_message'] = "Review not found!";
    header("Location: reviews.php");
    exit();
}

// Get products for dropdown
$products = $productController->getAllProducts();

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'product_id' => (int)$_POST['product_id'],
            'customer_name' => trim($_POST['customer_name']),
            'customer_email' => trim($_POST['customer_email']),
            'rating' => (int)$_POST['rating'],
            'title' => trim($_POST['title']),
            'comment' => trim($_POST['comment']),
            'status' => $_POST['status'],
            'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
            'helpful_votes' => (int)$_POST['helpful_votes'],
            'total_votes' => (int)$_POST['total_votes']
        ];

        if ($reviewController->updateReview($reviewId, $data)) {
            $_SESSION['success_message'] = "Review updated successfully!";
            header("Location: view-review.php?id=" . $reviewId);
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to update review.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
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
    <title>Edit Review #<?php echo $review['id']; ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .review-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .rating-stars { color: #ffc107; font-size: 1.5rem; cursor: pointer; }
        .rating-stars .fas { transition: all 0.2s ease; }
        .rating-stars .fas:hover { transform: scale(1.2); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .verified-badge { color: #28a745; }
        .product-image { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
        .form-label { font-weight: 500; }
        .required:after { content: " *"; color: red; }
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
                                            <h2 class="card-title text-white mb-1">Edit Review #<?php echo $review['id']; ?></h2>
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
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <form method="POST" id="reviewForm">
                        <div class="row">
                            <!-- Review Details -->
                            <div class="col-lg-8">
                                <!-- Product Selection -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Product Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label required">Select Product</label>
                                            <select name="product_id" id="product_id" class="form-control" required>
                                                <option value="">Select a product...</option>
                                                <?php if (!empty($products)): ?>
                                                    <?php foreach ($products as $product): ?>
                                                        <option value="<?php echo $product['id']; ?>" 
                                                            <?php echo $product['id'] == $review['product_id'] ? 'selected' : ''; ?>
                                                            data-image="<?php echo !empty($product['images'][0]['image_url']) ? htmlspecialchars($product['images'][0]['image_url']) : ''; ?>"
                                                            data-sku="<?php echo htmlspecialchars($product['sku']); ?>">
                                                            <?php echo htmlspecialchars($product['name']); ?> (SKU: <?php echo htmlspecialchars($product['sku']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Selected Product Preview -->
                                        <div id="productPreview" class="border rounded p-3 bg-light" style="<?php echo empty($review['product_name']) ? 'display: none;' : ''; ?>">
                                            <div class="row align-items-center">
                                                <div class="col-md-2">
                                                    <img id="productImage" src="<?php echo !empty($review['product_image']) ? htmlspecialchars($review['product_image']) : ''; ?>" 
                                                         class="product-image" 
                                                         alt="Product image"
                                                         onerror="this.style.display='none';">
                                                    <div id="noProductImage" class="product-image bg-light d-flex align-items-center justify-content-center" style="<?php echo !empty($review['product_image']) ? 'display: none;' : ''; ?>">
                                                        <i class="fas fa-box text-muted"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-10">
                                                    <h6 id="productName"><?php echo htmlspecialchars($review['product_name']); ?></h6>
                                                    <p id="productSku" class="text-muted mb-0">SKU: <?php echo htmlspecialchars($review['product_sku']); ?></p>
                                                </div>
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
                                        <!-- Rating -->
                                        <div class="mb-4">
                                            <label class="form-label required">Rating</label>
                                            <div class="rating-stars mb-3" id="ratingStars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-empty'; ?>" 
                                                       data-rating="<?php echo $i; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2">(<span id="ratingValue"><?php echo $review['rating']; ?></span>/5)</span>
                                            </div>
                                            <input type="hidden" name="rating" id="ratingInput" value="<?php echo $review['rating']; ?>" required>
                                        </div>

                                        <!-- Review Title -->
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Review Title</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($review['title']); ?>"
                                                   placeholder="Brief summary of your review (optional)"
                                                   maxlength="255">
                                        </div>

                                        <!-- Review Comment -->
                                        <div class="mb-3">
                                            <label for="comment" class="form-label">Review Comment</label>
                                            <textarea class="form-control" id="comment" name="comment" 
                                                      rows="6" placeholder="Share your experience with this product..."
                                                      maxlength="1000"><?php echo htmlspecialchars($review['comment']); ?></textarea>
                                            <div class="form-text">
                                                <span id="charCount">0</span>/1000 characters
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
                                        <div class="mb-3">
                                            <label for="customer_name" class="form-label required">Customer Name</label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                                   value="<?php echo htmlspecialchars($review['customer_name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="customer_email" class="form-label">Customer Email</label>
                                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                                   value="<?php echo htmlspecialchars($review['customer_email']); ?>"
                                                   placeholder="customer@example.com">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified" value="1"
                                                       <?php echo $review['is_verified'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_verified">
                                                    Verified Purchase
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Check if this review is from a verified purchase
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Review Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Review Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="status" class="form-label required">Status</label>
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="pending" <?php echo $review['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $review['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $review['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="helpful_votes" class="form-label">Helpful Votes</label>
                                                    <input type="number" class="form-control" id="helpful_votes" name="helpful_votes" 
                                                           value="<?php echo $review['helpful_votes']; ?>" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="total_votes" class="form-label">Total Votes</label>
                                                    <input type="number" class="form-control" id="total_votes" name="total_votes" 
                                                           value="<?php echo $review['total_votes']; ?>" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle"></i>
                                                Helpful votes should be less than or equal to total votes.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Review Metadata -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Review Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <strong>Review ID:</strong><br>
                                            <span class="text-muted">#<?php echo $review['id']; ?></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Created:</strong><br>
                                            <span class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="mb-0">
                                            <strong>Last Updated:</strong><br>
                                            <span class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($review['updated_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">Update Review</button>
                                            <a href="view-review.php?id=<?php echo $reviewId; ?>" class="btn btn-secondary">Cancel</a>
                                            <a href="reviews.php" class="btn btn-outline-secondary">Back to Reviews</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Rating stars functionality
        const ratingStars = document.querySelectorAll('#ratingStars .fas');
        const ratingInput = document.getElementById('ratingInput');
        const ratingValue = document.getElementById('ratingValue');

        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingInput.value = rating;
                ratingValue.textContent = rating;
                
                // Update stars display
                ratingStars.forEach(s => {
                    const starRating = parseInt(s.getAttribute('data-rating'));
                    if (starRating <= rating) {
                        s.classList.remove('fa-star-empty');
                        s.classList.add('fa-star');
                    } else {
                        s.classList.remove('fa-star');
                        s.classList.add('fa-star-empty');
                    }
                });
            });
        });

        // Character count for comment
        const commentTextarea = document.getElementById('comment');
        const charCount = document.getElementById('charCount');

        commentTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });

        // Initialize character count
        charCount.textContent = commentTextarea.value.length;

        // Product selection preview
        const productSelect = document.getElementById('product_id');
        const productPreview = document.getElementById('productPreview');
        const productImage = document.getElementById('productImage');
        const noProductImage = document.getElementById('noProductImage');
        const productName = document.getElementById('productName');
        const productSku = document.getElementById('productSku');

        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (this.value) {
                productPreview.style.display = 'block';
                productName.textContent = selectedOption.text.split(' (SKU:')[0];
                productSku.textContent = 'SKU: ' + selectedOption.getAttribute('data-sku');
                
                const imageUrl = selectedOption.getAttribute('data-image');
                if (imageUrl) {
                    productImage.src = imageUrl;
                    productImage.style.display = 'block';
                    noProductImage.style.display = 'none';
                } else {
                    productImage.style.display = 'none';
                    noProductImage.style.display = 'flex';
                }
            } else {
                productPreview.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            const customerName = document.getElementById('customer_name').value.trim();
            const productId = document.getElementById('product_id').value;
            const rating = document.getElementById('ratingInput').value;
            
            if (!customerName) {
                e.preventDefault();
                alert('Please enter customer name');
                document.getElementById('customer_name').focus();
                return;
            }
            
            if (!productId) {
                e.preventDefault();
                alert('Please select a product');
                document.getElementById('product_id').focus();
                return;
            }
            
            if (!rating || rating < 1 || rating > 5) {
                e.preventDefault();
                alert('Please select a rating between 1 and 5 stars');
                return;
            }

            // Validate votes
            const helpfulVotes = parseInt(document.getElementById('helpful_votes').value) || 0;
            const totalVotes = parseInt(document.getElementById('total_votes').value) || 0;
            
            if (helpfulVotes > totalVotes) {
                e.preventDefault();
                alert('Helpful votes cannot be greater than total votes');
                document.getElementById('helpful_votes').focus();
                return;
            }
        });

        // Initialize product preview if product is already selected
        document.addEventListener('DOMContentLoaded', function() {
            if (productSelect.value) {
                productSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>