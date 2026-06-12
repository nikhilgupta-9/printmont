<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Pass the database connection to the controller
$productController = new ProductController($db);

// Get filter parameters
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$featured = $_GET['featured'] ?? '';
$bestseller = $_GET['bestseller'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate page number
if ($page < 1) $page = 1;

// Get filtered products with pagination
$filterParams = [
    'search' => $search,
    'category_id' => $category_id,
    'status' => $status,
    'featured' => $featured,
    'bestseller' => $bestseller,
    'page' => $page,
    'limit' => $limit
];

$products = $productController->getProductsWithFilters($filterParams);
$totalProducts = $productController->getTotalProductsCount($search, $category_id, $status, $featured, $bestseller);
$totalPages = ceil($totalProducts / $limit);

// Get categories for filter dropdown
$categories = $productController->getAllCategoriesForFilter();

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
    <title>Products List | Printmont</title>
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
        .bestseller-badge { background-color: #ffedd5; color: #9a3412; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .filter-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .pagination { margin: 0; }
        .results-info { color: #6c757d; font-size: 0.875rem; }
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
                            <h3><strong>Product</strong> Management</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="product.php" class="btn btn-primary">Add New Product</a>
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

                    <!-- Search and Filter Section -->
                    <div class="filter-section">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name, SKU, or brand">
                            </div>
                            <div class="col-md-2">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="featured" class="form-label">Featured</label>
                                <select class="form-select" id="featured" name="featured">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $featured === '1' ? 'selected' : ''; ?>>Featured Only</option>
                                    <option value="0" <?php echo $featured === '0' ? 'selected' : ''; ?>>Not Featured</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="bestseller" class="form-label">Bestseller</label>
                                <select class="form-select" id="bestseller" name="bestseller">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $bestseller === '1' ? 'selected' : ''; ?>>Bestseller Only</option>
                                    <option value="0" <?php echo $bestseller === '0' ? 'selected' : ''; ?>>Not Bestseller</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <a href="?" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Products</h5>
                                    <h6 class="card-subtitle text-muted">Manage your store products.</h6>
                                    <div class="results-info mt-2">
                                        Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                                        <?php if ($search || $category_id || $status || $featured !== '' || $bestseller !== ''): ?>
                                            (filtered results)
                                        <?php endif; ?>
                                    </div>
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
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($products)): ?>
                                                    <tr>
                                                        <td colspan="11" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <?php if ($search || $category_id || $status || $featured !== '' || $bestseller !== ''): ?>
                                                                    No products found matching your filters. 
                                                                    <a href="?">Clear filters</a>
                                                                <?php else: ?>
                                                                    No products found. <a href="product.php">Add your first product</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($products as $product): ?>
                                                        <tr>
                                                            <td>
                                                                <?php if (!empty($product['primary_image'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($product['primary_image']); ?>" 
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
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="single-product-version.php?id=<?php echo $product['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="View">
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
                                    <?php if ($totalPages > 1): ?>
                                        <nav aria-label="Product pagination" class="mt-4">
                                            <ul class="pagination justify-content-center">
                                                <!-- Previous Page -->
                                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                                       aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>

                                                <!-- Page Numbers -->
                                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                            <a class="page-link" 
                                                               href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                                <?php echo $i; ?>
                                                            </a>
                                                        </li>
                                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                        <li class="page-item disabled">
                                                            <span class="page-link">...</span>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endfor; ?>

                                                <!-- Next Page -->
                                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
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
</body>
</html>