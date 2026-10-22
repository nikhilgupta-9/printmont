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

$reviewController = new ReviewController();

// -------------------------------------------------------------
// AJAX HANDLERS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $reviewId = (int)($_POST['review_id'] ?? 0);

    if ($reviewId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
        exit;
    }

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
            exit;
        }

        $ok = $reviewController->updateReviewStatus($reviewId, $status);
        echo json_encode(['success' => $ok, 'status' => $status, 'message' => "Review marked as " . ucfirst($status)]);
        exit;
    }

    if ($action === 'delete_review') {
        $ok = $reviewController->deleteReview($reviewId);
        echo json_encode(['success' => $ok, 'message' => 'Review deleted successfully!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// -------------------------------------------------------------
// FILTER & PAGINATION PARAMETERS
// -------------------------------------------------------------
$search_query = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$rating_filter = trim($_GET['rating'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 15;

$filters = [
    'status' => $status_filter,
    'rating' => $rating_filter,
    'search' => $search_query
];

$reviews_data = $reviewController->getAllReviews($page, $limit, $filters);
$reviews = $reviews_data['reviews'];
$total_reviews = $reviews_data['total_count'];
$total_pages = $reviews_data['total_pages'];
$current_page = $reviews_data['current_page'];
$offset = ($page - 1) * $limit;

// KPI Stats
$stats = $reviewController->getReviewStats();

// Helper to build URLs
function buildReviewQuery($newParams = []) {
    $current = $_GET;
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return 'reviews.php?' . http_build_query($current);
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Customer Reviews | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-thumb-box {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .product-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 0.85rem;
        }
        .action-icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            text-decoration: none;
            cursor: pointer;
        }
        .action-icon-btn:hover {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }
        .action-icon-btn.btn-approve:hover {
            background: #10b981;
            color: #fff;
            border-color: #10b981;
        }
        .action-icon-btn.btn-reject:hover {
            background: #f59e0b;
            color: #fff;
            border-color: #f59e0b;
        }
        .action-icon-btn.btn-delete:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }
        .comment-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.4;
        }
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
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
                    
                    <!-- Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800"><strong>Product</strong> Reviews</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="view-products.php">Products</a></li>
                                    <li class="breadcrumb-item active">Reviews</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="add-review.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add Review
                            </a>
                            <a href="view-products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> All Products
                            </a>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Stats Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #eef2ff; color: #4f46e5;">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Total Reviews</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['total_reviews'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #fffbeb; color: #d97706;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Pending Approval</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['pending_reviews'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Approved Reviews</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['approved_reviews'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #fdf2f8; color: #db2777;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Average Rating</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['average_rating'] ?? 5, 1); ?> ★</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Toolbar -->
                    <div class="filter-card">
                        <form method="GET" action="reviews.php" class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control form-control-sm border-start-0" 
                                           placeholder="Search customer, title, product..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Review Statuses</option>
                                    <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending Approval Only</option>
                                    <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved Only</option>
                                    <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected Only</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="rating" class="form-select form-select-sm">
                                    <option value="">All Star Ratings</option>
                                    <option value="5" <?php echo ($rating_filter === '5') ? 'selected' : ''; ?>>5 Stars (★★★★★)</option>
                                    <option value="4" <?php echo ($rating_filter === '4') ? 'selected' : ''; ?>>4 Stars (★★★★☆)</option>
                                    <option value="3" <?php echo ($rating_filter === '3') ? 'selected' : ''; ?>>3 Stars (★★★☆☆)</option>
                                    <option value="2" <?php echo ($rating_filter === '2') ? 'selected' : ''; ?>>2 Stars (★★☆☆☆)</option>
                                    <option value="1" <?php echo ($rating_filter === '1') ? 'selected' : ''; ?>>1 Star (★☆☆☆☆)</option>
                                </select>
                            </div>

                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="reviews.php" class="btn btn-light btn-sm border" title="Reset">
                                    <i class="fas fa-rotate-left"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Reviews Table -->
                    <div class="table-card mb-4">
                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-light">
                            <span class="text-muted small">
                                Showing <strong><?php echo count($reviews) > 0 ? ($offset + 1) : 0; ?></strong> to 
                                <strong><?php echo min($offset + $limit, $total_reviews); ?></strong> of 
                                <strong><?php echo $total_reviews; ?></strong> reviews
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">Show:</span>
                                <select class="form-select form-select-sm" style="width: 75px;" onchange="location.href=this.value;">
                                    <?php foreach ([10, 15, 25, 50] as $lim): ?>
                                        <option value="<?php echo buildReviewQuery(['limit' => $lim, 'page' => 1]); ?>" <?php echo ($limit == $lim) ? 'selected' : ''; ?>>
                                            <?php echo $lim; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <tr>
                                        <th style="width: 60px;">Product</th>
                                        <th>Product Name</th>
                                        <th>Customer</th>
                                        <th>Rating</th>
                                        <th style="max-width: 320px;">Review &amp; Feedback</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-end" style="width: 140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reviews)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <div class="py-4">
                                                    <i class="fas fa-star-half-stroke fa-3x text-warning mb-3 opacity-50"></i>
                                                    <h5>No Reviews Found</h5>
                                                    <p class="text-muted small">Customer reviews will appear here once submitted on your store.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reviews as $rev): 
                                            $prodImg = !empty($rev['product_image']) ? $rev['product_image'] : 'img/icons/icon-48x48.png';
                                            $rScore = (int)($rev['rating'] ?? 5);
                                        ?>
                                            <tr id="review-row-<?php echo $rev['id']; ?>">
                                                <td>
                                                    <div class="product-thumb-box">
                                                        <img src="<?php echo htmlspecialchars($prodImg); ?>" 
                                                             alt="Product"
                                                             onerror="this.src='img/icons/icon-48x48.png'">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark mb-0" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($rev['product_name'] ?: 'Product #' . $rev['product_id']); ?>
                                                    </div>
                                                    <small class="text-muted font-monospace"><?php echo htmlspecialchars($rev['product_sku'] ?: ''); ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($rev['customer_name']); ?></div>
                                                    <?php if (!empty($rev['customer_email'])): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($rev['customer_email']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($rev['is_verified'])): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success p-1 ms-1" style="font-size: 0.65rem;">
                                                            <i class="fas fa-check-circle"></i> Verified
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="<?php echo ($i <= $rScore) ? 'fas fa-star' : 'far fa-star text-muted opacity-50'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <small class="fw-bold text-dark"><?php echo $rScore; ?>.0 / 5.0</small>
                                                </td>
                                                <td style="max-width: 320px;">
                                                    <?php if (!empty($rev['title'])): ?>
                                                        <strong class="d-block text-dark small mb-1"><?php echo htmlspecialchars($rev['title']); ?></strong>
                                                    <?php endif; ?>
                                                    <div class="comment-clamp">
                                                        <?php echo htmlspecialchars($rev['comment'] ?: ($rev['review_text'] ?? 'No text provided.')); ?>
                                                    </div>
                                                    <?php if (!empty($rev['occasion']) || !empty($rev['city'])): ?>
                                                        <div class="mt-1 d-flex gap-1 flex-wrap">
                                                            <?php if (!empty($rev['occasion'])): ?>
                                                                <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.7rem;">Occasion: <?php echo htmlspecialchars($rev['occasion']); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($rev['city'])): ?>
                                                                <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.7rem;">City: <?php echo htmlspecialchars($rev['city']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php 
                                                    $revImages = !empty($rev['images']) ? (is_array(json_decode($rev['images'], true)) ? json_decode($rev['images'], true) : [$rev['images']]) : [];
                                                    if (!empty($revImages)): ?>
                                                        <div class="mt-2 d-flex gap-1 flex-wrap">
                                                            <?php foreach ($revImages as $imgItem): 
                                                                $src = (strpos($imgItem, 'http') === 0) ? $imgItem : $imgItem;
                                                            ?>
                                                                <a href="<?php echo htmlspecialchars($src); ?>" target="_blank" title="View attached photo">
                                                                    <img src="<?php echo htmlspecialchars($src); ?>" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span id="badge-status-<?php echo $rev['id']; ?>" class="badge <?php 
                                                        echo ($rev['status'] === 'approved') ? 'bg-success bg-opacity-10 text-success' : 
                                                            (($rev['status'] === 'pending') ? 'bg-warning bg-opacity-10 text-warning' : 'bg-danger bg-opacity-10 text-danger'); 
                                                    ?> fw-semibold px-2 py-1">
                                                        <?php echo ucfirst($rev['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <?php if ($rev['status'] !== 'approved'): ?>
                                                            <button class="action-icon-btn btn-approve" 
                                                                    title="Approve Review"
                                                                    onclick="changeStatus(<?php echo $rev['id']; ?>, 'approved')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($rev['status'] !== 'rejected'): ?>
                                                            <button class="action-icon-btn btn-reject" 
                                                                    title="Reject Review"
                                                                    onclick="changeStatus(<?php echo $rev['id']; ?>, 'rejected')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="view-review.php?id=<?php echo $rev['id']; ?>" class="action-icon-btn" title="View Full Review">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button class="action-icon-btn btn-delete" 
                                                                title="Delete Review"
                                                                onclick="deleteReview(<?php echo $rev['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <?php if ($total_pages > 1): 
                            $adjacents = 2;
                            $start_page = max(1, $page - $adjacents);
                            $end_page = min($total_pages, $page + $adjacents);
                        ?>
                            <div class="d-flex flex-wrap align-items-center justify-content-between p-3 border-top bg-light gap-2">
                                <div class="text-muted small">
                                    Showing <strong><?php echo min($total_reviews, $offset + 1); ?></strong> to <strong><?php echo min($total_reviews, $offset + $limit); ?></strong> of <strong><?php echo number_format($total_reviews); ?></strong> reviews (Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>)
                                </div>
                                <nav aria-label="Review pagination">
                                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                                        <!-- First & Prev -->
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildReviewQuery(['page' => 1]); ?>" title="First Page">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildReviewQuery(['page' => $page - 1]); ?>" title="Previous Page">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>

                                        <!-- Leading page 1 + ellipsis -->
                                        <?php if ($start_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo buildReviewQuery(['page' => 1]); ?>">1</a>
                                            </li>
                                            <?php if ($start_page > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Window of Pages -->
                                        <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                            <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo buildReviewQuery(['page' => $p]); ?>"><?php echo $p; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Trailing ellipsis + last page -->
                                        <?php if ($end_page < $total_pages): ?>
                                            <?php if ($end_page < $total_pages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo buildReviewQuery(['page' => $total_pages]); ?>"><?php echo $total_pages; ?></a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Next & Last -->
                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildReviewQuery(['page' => $page + 1]); ?>" title="Next Page">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildReviewQuery(['page' => $total_pages]); ?>" title="Last Page">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Live Toast -->
    <div class="toast-container">
        <div id="liveToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                    <i class="fas fa-check-circle text-success"></i> Action completed.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('liveToast');
            const toastBody = document.getElementById('toastMessage');
            if (isSuccess) {
                toastBody.innerHTML = `<i class="fas fa-check-circle text-success me-2"></i> ${message}`;
                toastEl.className = 'toast align-items-center text-white bg-dark border-0';
            } else {
                toastBody.innerHTML = `<i class="fas fa-exclamation-circle text-danger me-2"></i> ${message}`;
                toastEl.className = 'toast align-items-center text-white bg-danger border-0';
            }
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        function changeStatus(reviewId, newStatus) {
            const fd = new FormData();
            fd.append('action', 'update_status');
            fd.append('review_id', reviewId);
            fd.append('status', newStatus);

            fetch('reviews.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    const badge = document.getElementById('badge-status-' + reviewId);
                    if (badge) {
                        badge.className = 'badge fw-semibold px-2 py-1 ' + (newStatus === 'approved' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger');
                        badge.innerText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    }
                } else {
                    showToast(data.message || 'Failed to update review status', false);
                }
            })
            .catch(() => showToast('Network error', false));
        }

        function deleteReview(reviewId) {
            if (!confirm('Are you sure you want to permanently delete this customer review?')) return;

            const fd = new FormData();
            fd.append('action', 'delete_review');
            fd.append('review_id', reviewId);

            fetch('reviews.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    const row = document.getElementById('review-row-' + reviewId);
                    if (row) row.remove();
                } else {
                    showToast(data.message || 'Error deleting review', false);
                }
            })
            .catch(() => showToast('Network error', false));
        }
    </script>
</body>
</html>