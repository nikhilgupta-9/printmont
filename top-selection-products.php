<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

$productController = new ProductController($db);

// Pagination settings
$products_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $products_per_page;

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get products with filters and pagination
$products_result = $productController->getProductsWithPagination(
    $search_query, 
    $category_filter, 
    $status_filter, 
    $offset, 
    $products_per_page
);

$products = $products_result['products'];
$total_products = $products_result['total'];
$total_pages = ceil($total_products / $products_per_page);

// Get categories for filter dropdown
$categories = $productController->getCategories();

// Handle AJAX top selection toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_top_selection') {
    header('Content-Type: application/json');
    $productId = intval($_POST['product_id']);
    $result = $productController->toggleTopSelection($productId);
    echo json_encode($result);
    exit;
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
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="Nikhil">
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Top Selection Products | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }
        .status-draft { background-color: #fef3c7; color: #92400e; }
        .featured-badge { background-color: #e0e7ff; color: #3730a3; }
        .top-selection-badge { background-color: #dcfce7; color: #166534; }
        .bestseller-badge { background-color: #ffedd5; color: #9a3412; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        
        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #10b981;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .toggle-label {
            margin-left: 10px;
            font-size: 12px;
            color: #6c757d;
        }
        .search-box {
            max-width: 400px;
        }
        .pagination .page-link {
            color: #495057;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
        .results-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
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
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Top Selection</strong> Products</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-products.php" class="btn btn-light bg-white me-2">All Products</a>
                            <a href="bestseller-products.php" class="btn btn-warning me-2">Bestsellers</a>
                            <a href="top-rated-products.php" class="btn btn-info">Top Rated</a>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link" href="view-products.php">All Products</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="bestseller-products.php">Bestsellers</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="top-selection-products.php">Top Selection</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="top-rated-products.php">Top Rated</a>
                                </li>
                            </ul>
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

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $total_products; ?></h4>
                                            <span>Total Products</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">
                                                <?php 
                                                $top_selection_count = 0;
                                                foreach ($products as $product) {
                                                    if ($product['top_selection']) {
                                                        $top_selection_count++;
                                                    }
                                                }
                                                echo $top_selection_count;
                                                ?>
                                            </h4>
                                            <span>Top Selection</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-star fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">
                                                <?php 
                                                $active_count = 0;
                                                foreach ($products as $product) {
                                                    if ($product['status'] === 'active') {
                                                        $active_count++;
                                                    }
                                                }
                                                echo $active_count;
                                                ?>
                                            </h4>
                                            <span>Active Products</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">
                                                <?php 
                                                $featured_count = 0;
                                                foreach ($products as $product) {
                                                    if ($product['featured']) {
                                                        $featured_count++;
                                                    }
                                                }
                                                echo $featured_count;
                                                ?>
                                            </h4>
                                            <span>Featured</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-crown fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="filter-section">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Products</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Search by name, SKU, or brand..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                            </div>
                            <?php if ($search_query || $category_filter || $status_filter): ?>
                                <div class="col-12">
                                    <a href="top-selection-products.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                                    <span class="results-info ms-2">
                                        Filtered results: <?php echo $total_products; ?> product(s) found
                                    </span>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Manage Top Selection Products</h5>
                                    <h6 class="card-subtitle text-muted">
                                        Mark products as "Top Selection" to highlight them on your website.
                                        Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
                                        <?php if ($current_page > 1): ?>
                                            - Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Product Name</th>
                                                    <th>SKU</th>
                                                    <th>Category</th>
                                                    <th>Price</th>
                                                    <th>Stock</th>
                                                    <th>Status</th>
                                                    <th>Featured</th>
                                                    <th>Bestseller</th>
                                                    <th>Top Selection</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($products)): ?>
                                                    <tr>
                                                        <td colspan="12" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <?php if ($search_query || $category_filter || $status_filter): ?>
                                                                    No products found matching your filters. 
                                                                    <a href="top-selection-products.php">Clear filters</a>
                                                                <?php else: ?>
                                                                    No products found. <a href="product.php">Add your first product</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($products as $product): ?>
                                                        <tr id="product-<?php echo $product['id']; ?>">
                                                            <td>
                                                                <?php if (!empty($product['images'][0]['image_url'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($product['images'][0]['image_url']); ?>" 
                                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                         class="product-image">
                                                                <?php else: ?>
                                                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                                        <small class="text-muted">No Image</small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                                <?php if (!empty($product['brand'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($product['brand']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <strong>$<?php echo number_format($product['price'], 2); ?></strong>
                                                                <?php if (!empty($product['discount_price']) && $product['discount_price'] > 0): ?>
                                                                    <br><small class="text-danger"><s>$<?php echo number_format($product['discount_price'], 2); ?></s></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="<?php echo $product['stock_quantity'] > 10 ? 'text-success' : ($product['stock_quantity'] > 0 ? 'text-warning' : 'text-danger'); ?>">
                                                                    <?php echo $product['stock_quantity']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                                                    <?php echo ucfirst($product['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($product['featured']): ?>
                                                                    <span class="status-badge featured-badge">Featured</span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($product['our_bestseller']): ?>
                                                                    <span class="status-badge bestseller-badge">Bestseller</span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="toggle-container">
                                                                <label class="toggle-switch">
                                                                    <input type="checkbox" 
                                                                           class="top-selection-toggle" 
                                                                           data-product-id="<?php echo $product['id']; ?>"
                                                                           <?php echo $product['top_selection'] ? 'checked' : ''; ?>>
                                                                    <span class="toggle-slider"></span>
                                                                </label>
                                                                <span class="toggle-label" id="top-selection-label-<?php echo $product['id']; ?>">
                                                                    <?php echo $product['top_selection'] ? 'Yes' : 'No'; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="single-product-version.php?id=<?php echo $product['id']; ?>" 
                                                                   class="btn btn-sm btn-info" title="View">
                                                                   <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-product.php?id=<?php echo $product['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this product?')"
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
                                        <nav aria-label="Product pagination">
                                            <ul class="pagination justify-content-center">
                                                <!-- Previous Page -->
                                                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?page=<?php echo $current_page - 1; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                                                       aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>

                                                <!-- Page Numbers -->
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                                                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                                            <a class="page-link" 
                                                               href="?page=<?php echo $i; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">
                                                                <?php echo $i; ?>
                                                            </a>
                                                        </li>
                                                    <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                                        <li class="page-item disabled">
                                                            <span class="page-link">...</span>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endfor; ?>

                                                <!-- Next Page -->
                                                <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?page=<?php echo $current_page + 1; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                                                       aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle top selection toggle
            document.querySelectorAll('.top-selection-toggle').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const productId = this.getAttribute('data-product-id');
                    const isChecked = this.checked;
                    const toggleLabel = document.getElementById('top-selection-label-' + productId);
                    
                    // Show loading state
                    toggleLabel.textContent = 'Updating...';
                    this.disabled = true;
                    
                    // Send AJAX request
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=toggle_top_selection&product_id=' + productId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update label
                            toggleLabel.textContent = data.is_top_selection ? 'Yes' : 'No';
                            
                            // Update stats card
                            updateTopSelectionStats(data.is_top_selection);
                            
                            // Show success message
                            showNotification('Top Selection status updated successfully!', 'success');
                        } else {
                            // Revert toggle state
                            this.checked = !isChecked;
                            toggleLabel.textContent = isChecked ? 'No' : 'Yes';
                            showNotification(data.error || 'Failed to update top selection status', 'error');
                        }
                    })
                    .catch(error => {
                        // Revert toggle state on error
                        this.checked = !isChecked;
                        toggleLabel.textContent = isChecked ? 'No' : 'Yes';
                        showNotification('Network error occurred', 'error');
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        this.disabled = false;
                    });
                });
            });
            
            function updateTopSelectionStats(isAdded) {
                const statsElement = document.querySelector('.card.bg-success h4');
                if (statsElement) {
                    let currentCount = parseInt(statsElement.textContent) || 0;
                    currentCount = isAdded ? currentCount + 1 : currentCount - 1;
                    statsElement.textContent = currentCount;
                }
            }
            
            function showNotification(message, type) {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
                notification.innerHTML = `
                    <div class="alert-message">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Insert at the top of the content area
                const contentArea = document.querySelector('main.content .container-fluid');
                contentArea.insertBefore(notification, contentArea.firstChild);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }

            // Auto-submit form when category or status filter changes
            document.getElementById('category').addEventListener('change', function() {
                this.form.submit();
            });

            document.getElementById('status').addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>