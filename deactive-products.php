<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Pass the database connection to the controller
$productController = new ProductController($db);
$products = $productController->getDeactiveProducts();

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
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
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

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Products</h5>
                                    <h6 class="card-subtitle text-muted">Manage your store products.</h6>
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
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($products)): ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center py-4">
                                                            <div class="text-muted">No products found. <a href="add-product.php">Add your first product</a></div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($products as $product): ?>
                                                        <tr>
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
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
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