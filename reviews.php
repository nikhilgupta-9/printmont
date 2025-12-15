<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/ReviewController.php';

$reviewController = new ReviewController();

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$filters = [
    'status' => $_GET['status'] ?? '',
    'rating' => $_GET['rating'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$reviews_data = $reviewController->getAllReviews($page, $limit, $filters);
$reviews = $reviews_data['reviews'];
$total_pages = $reviews_data['total_pages'];
$current_page = $reviews_data['current_page'];

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get review statistics
$stats = $reviewController->getReviewStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Product Reviews | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .review-table th { font-weight: 600; background-color: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .rating-stars { color: #ffc107; }
        .verified-badge { color: #28a745; font-size: 12px; }
        .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .stat-card { border-left: 4px solid #007bff; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .filter-card { background-color: #f8f9fa; }
        .comment-text { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Product</strong> Reviews</h3>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Total</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-primary">
                                                <i class="fas fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['total_reviews']); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Approved</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-success">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['approved_reviews']); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Pending</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-warning">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['pending_reviews']); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Avg Rating</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-info">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['average_rating'] ?? 0, 1); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Verified</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-danger">
                                                <i class="fas fa-badge-check"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['verified_reviews']); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Rejected</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-secondary">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['rejected_reviews']); ?></h1>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo $filters['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $filters['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $filters['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Rating</label>
                                    <select name="rating" class="form-control">
                                        <option value="">All Ratings</option>
                                        <option value="5" <?php echo $filters['rating'] == '5' ? 'selected' : ''; ?>>5 Stars</option>
                                        <option value="4" <?php echo $filters['rating'] == '4' ? 'selected' : ''; ?>>4 Stars</option>
                                        <option value="3" <?php echo $filters['rating'] == '3' ? 'selected' : ''; ?>>3 Stars</option>
                                        <option value="2" <?php echo $filters['rating'] == '2' ? 'selected' : ''; ?>>2 Stars</option>
                                        <option value="1" <?php echo $filters['rating'] == '1' ? 'selected' : ''; ?>>1 Star</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control" placeholder="Customer name, review title, or product name" value="<?php echo htmlspecialchars($filters['search']); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Reviews</h5>
                                    <h6 class="card-subtitle text-muted">Manage and moderate product reviews.</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover review-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Customer</th>
                                                    <th>Rating</th>
                                                    <th>Review</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($reviews)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-4">
                                                            <div class="text-muted">No reviews found.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($reviews as $review): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($review['product_image'])): ?>
                                                                        <img src="<?php echo htmlspecialchars($review['product_image']); ?>" 
                                                                             class="product-image me-3" 
                                                                             alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                                                    <?php else: ?>
                                                                        <div class="product-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                                            <i class="fas fa-box text-muted"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                                                        <?php if (!empty($review['product_sku'])): ?>
                                                                            <br><small class="text-muted">SKU: <?php echo htmlspecialchars($review['product_sku']); ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                                                <?php if (!empty($review['customer_email'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($review['customer_email']); ?></small>
                                                                <?php endif; ?>
                                                                <?php if ($review['is_verified']): ?>
                                                                    <br><span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="rating-stars">
                                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-empty'; ?>"></i>
                                                                    <?php endfor; ?>
                                                                    <br>
                                                                    <small class="text-muted"><?php echo $review['rating']; ?>/5</small>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($review['title'])): ?>
                                                                    <strong><?php echo htmlspecialchars($review['title']); ?></strong><br>
                                                                <?php endif; ?>
                                                                <div class="comment-text" title="<?php echo htmlspecialchars($review['comment']); ?>">
                                                                    <?php echo !empty($review['comment']) ? htmlspecialchars($review['comment']) : '<em class="text-muted">No comment</em>'; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $review['status']; ?>">
                                                                    <?php echo ucfirst($review['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="view-review.php?id=<?php echo $review['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="View">
                                                                   <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="edit-review.php?id=<?php echo $review['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-review.php?id=<?php echo $review['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this review?')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center mt-4">
                                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">Previous</a>
                                            </li>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>