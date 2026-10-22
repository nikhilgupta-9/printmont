<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/controllers/ReviewController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$reviewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reviewId <= 0) {
    header("Location: reviews.php");
    exit();
}

$reviewController = new ReviewController();
$review = $reviewController->getReviewById($reviewId);

if (!$review) {
    $_SESSION['error_message'] = "Review not found!";
    header("Location: reviews.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? 'pending';
    $adminReply = trim($_POST['admin_reply'] ?? '');

    if (in_array($status, ['pending', 'approved', 'rejected'])) {
        $reviewController->updateReviewStatus($reviewId, $status);
        
        // Save admin reply if column exists
        if (!empty($adminReply)) {
            $stmt = $db->prepare("UPDATE product_reviews SET admin_reply = ? WHERE id = ?");
            $stmt->bind_param("si", $adminReply, $reviewId);
            $stmt->execute();
        }

        $_SESSION['success_message'] = "Review status updated successfully!";
        header("Location: view-review.php?id=" . $reviewId);
        exit();
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$prodImg = !empty($review['product_image']) ? $review['product_image'] : 'img/icons/icon-48x48.png';
$rating = (int)($review['rating'] ?? 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Review Details #<?php echo $review['id']; ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-thumb-preview {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-thumb-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 1.25rem;
        }
        .review-quote-card {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            border-radius: 0 8px 8px 0;
            padding: 1.25rem;
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <!-- Top Header -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800"><strong>Review</strong> Details #<?php echo $review['id']; ?></h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="reviews.php">Reviews</a></li>
                                    <li class="breadcrumb-item active">Review #<?php echo $review['id']; ?></li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="reviews.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Reviews
                            </a>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <!-- Main Column: Review & Feedback -->
                        <div class="col-lg-8">
                            <!-- Product Overview Card -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="product-thumb-preview">
                                            <img src="<?php echo htmlspecialchars($prodImg); ?>" 
                                                 alt="Product Image"
                                                 onerror="this.src='img/icons/icon-48x48.png'">
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="badge bg-light text-secondary border font-monospace mb-1" style="font-size: 0.75rem;">
                                                SKU: <?php echo htmlspecialchars($review['product_sku'] ?: 'N/A'); ?>
                                            </span>
                                            <h5 class="fw-bold text-dark mb-1">
                                                <a href="single-product-version.php?id=<?php echo $review['product_id']; ?>" class="text-dark text-decoration-none">
                                                    <?php echo htmlspecialchars($review['product_name'] ?: 'Product #' . $review['product_id']); ?>
                                                </a>
                                            </h5>
                                            <small class="text-muted">Product ID: #<?php echo $review['product_id']; ?></small>
                                        </div>
                                        <div>
                                            <a href="edit-product.php?id=<?php echo $review['product_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-pen me-1"></i> Edit Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Body Card -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?php echo ($i <= $rating) ? 'fas fa-star' : 'far fa-star text-muted opacity-50'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="fw-bold text-dark fs-5"><?php echo $rating; ?>.0</span>
                                    </div>

                                    <div>
                                        <span class="badge <?php 
                                            echo ($review['status'] === 'approved') ? 'bg-success bg-opacity-10 text-success' : 
                                                (($review['status'] === 'pending') ? 'bg-warning bg-opacity-10 text-warning' : 'bg-danger bg-opacity-10 text-danger'); 
                                        ?> fw-semibold px-3 py-2 text-uppercase" style="font-size: 0.75rem;">
                                            ● <?php echo ucfirst($review['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <?php if (!empty($review['title'])): ?>
                                        <h4 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($review['title']); ?></h4>
                                    <?php endif; ?>

                                    <div class="review-quote-card mb-4">
                                        <p class="text-secondary mb-0 leading-relaxed" style="font-size: 1rem; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($review['comment'] ?: ($review['review_text'] ?? 'No text provided.'))); ?>
                                        </p>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between text-muted small border-top pt-3">
                                        <span><i class="fas fa-clock me-1"></i> Submitted on <?php echo date('F d, Y \a\t h:i A', strtotime($review['created_at'])); ?></span>
                                        <span><i class="fas fa-thumbs-up me-1 text-success"></i> <?php echo (int)$review['helpful_votes']; ?> helpful votes</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar: Moderation & Customer -->
                        <div class="col-lg-4">
                            <!-- Moderation Action Card -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white border-bottom p-3">
                                    <h6 class="fw-bold text-dark mb-0">Moderation Action</h6>
                                </div>
                                <div class="card-body p-3">
                                    <form method="POST" action="view-review.php?id=<?php echo $review['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-uppercase text-muted">Review Status</label>
                                            <select name="status" class="form-select">
                                                <option value="approved" <?php echo ($review['status'] === 'approved') ? 'selected' : ''; ?>>Approved (Public on Live Site)</option>
                                                <option value="pending" <?php echo ($review['status'] === 'pending') ? 'selected' : ''; ?>>Pending Moderation</option>
                                                <option value="rejected" <?php echo ($review['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected (Hidden)</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-uppercase text-muted">Admin Reply (Optional)</label>
                                            <textarea name="admin_reply" rows="3" class="form-control" placeholder="Write a response from Printmont customer support..."><?php echo htmlspecialchars($review['admin_reply'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="submit" name="update_status" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-1"></i> Save Moderation Changes
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Customer Information Card -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom p-3">
                                    <h6 class="fw-bold text-dark mb-0">Customer Details</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                            <?php echo strtoupper(substr($review['customer_name'] ?? 'C', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($review['customer_name']); ?></h6>
                                            <?php if (!empty($review['is_verified'])): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success p-1" style="font-size: 0.65rem;">
                                                    <i class="fas fa-check-circle"></i> Verified Buyer
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($review['customer_email'])): ?>
                                        <div class="small text-muted mb-2">
                                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($review['customer_email']); ?>
                                        </div>
                                    <?php endif; ?>
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
</body>
</html>