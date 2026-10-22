<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$productController = new ProductController($db);

// Handle AJAX status activate/toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    header('Content-Type: application/json');
    $productId = (int)($_POST['id'] ?? 0);
    $newStatus = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
    
    if ($productId > 0) {
        $stmt = $db->prepare("UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $productId);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    }
    exit;
}

// -------------------------------------------------------------
// FILTER & PAGINATION PARAMETERS
// -------------------------------------------------------------
$search_query = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? 'inactive'); // Default show inactive
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 15;
$offset = ($page - 1) * $limit;

// -------------------------------------------------------------
// KPI STATS OVERVIEW (Database Real Totals)
// -------------------------------------------------------------
$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_count,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(*) as total_count
    FROM products";
$stats = $database->fetch($stats_sql) ?: [
    'inactive_count' => 0, 'draft_count' => 0, 'active_count' => 0, 'total_count' => 0
];

// -------------------------------------------------------------
// GET DEACTIVATED PRODUCTS QUERY
// -------------------------------------------------------------
$where = ["1=1"];
$params = [];

if (!empty($status_filter)) {
    if ($status_filter === 'all_deactivated') {
        $where[] = "p.status IN ('inactive', 'draft')";
    } else {
        $where[] = "p.status = ?";
        $params[] = $status_filter;
    }
} else {
    $where[] = "p.status != 'active'";
}

if (!empty($search_query)) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
    $s = "%$search_query%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
}
if (!empty($category_filter)) {
    $where[] = "p.category_id = ?";
    $params[] = $category_filter;
}

$whereClause = implode(" AND ", $where);

// Count total
$countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
$countResult = $database->fetch($countSql, $params);
$total_products = $countResult['total'] ?? 0;
$total_pages = ceil($total_products / $limit);

// Fetch page products
$sql = "SELECT p.*, c.name as category_name,
        (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, display_order ASC LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
        ORDER BY p.updated_at DESC, p.created_at DESC
        LIMIT ? OFFSET ?";
$fetchParams = array_merge($params, [$limit, $offset]);
$products = $database->fetchAll($sql, $fetchParams) ?: [];

// Get categories for filter dropdown
$categories = $productController->getCategories();

// Helper function to build query strings
function buildDeactiveQuery($newParams = []) {
    $current = $_GET;
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return 'deactive-products.php?' . http_build_query($current);
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
    <title>Deactivated Products | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-thumb-box {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
            filter: grayscale(40%);
            transition: filter 0.2s;
        }
        .product-thumb-box:hover {
            filter: grayscale(0%);
        }
        .product-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .product-name-link {
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
            line-height: 1.35;
        }
        .product-name-link:hover {
            color: #ef4444;
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
        .badge-cat {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
        }
        .badge-sku {
            font-family: monospace;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
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
        }
        .action-icon-btn:hover {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }
        .action-icon-btn.btn-activate:hover {
            background: #10b981;
            color: #fff;
            border-color: #10b981;
        }
        .action-icon-btn.btn-delete:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
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
                            <h1 class="h3 mb-1 text-gray-800"><strong>Deactivated</strong> Products</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="view-products.php">Products</a></li>
                                    <li class="breadcrumb-item active">Deactivated</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="view-products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> All Products
                            </a>
                            <a href="product.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add Product
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

                    <!-- Real Database KPI Stats Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #fef2f2; color: #ef4444;">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Inactive Products</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['inactive_count']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #fffbeb; color: #d97706;">
                                    <i class="fas fa-file-pen"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Draft Products</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['draft_count']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Live Active Store</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['active_count']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #f8fafc; color: #475569;">
                                    <i class="fas fa-boxes-stacked"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Total Catalog</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($stats['total_count']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter / Search Card -->
                    <div class="filter-card">
                        <form method="GET" action="deactive-products.php" class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control form-control-sm border-start-0" 
                                           placeholder="Search inactive name, SKU, brand..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <select name="category" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive Products Only</option>
                                    <option value="draft" <?php echo ($status_filter === 'draft') ? 'selected' : ''; ?>>Draft Products Only</option>
                                    <option value="all_deactivated" <?php echo ($status_filter === 'all_deactivated') ? 'selected' : ''; ?>>All Non-Active (Draft + Inactive)</option>
                                </select>
                            </div>

                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-danger btn-sm flex-fill">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="deactive-products.php" class="btn btn-light btn-sm border" title="Reset Filters">
                                    <i class="fas fa-rotate-left"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Products Table Card -->
                    <div class="table-card mb-4">
                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-light">
                            <span class="text-muted small">
                                Showing <strong><?php echo count($products) > 0 ? ($offset + 1) : 0; ?></strong> to 
                                <strong><?php echo min($offset + $limit, $total_products); ?></strong> of 
                                <strong><?php echo $total_products; ?></strong> deactivated products
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">Show:</span>
                                <select class="form-select form-select-sm" style="width: 75px;" onchange="location.href=this.value;">
                                    <?php foreach ([10, 15, 25, 50, 100] as $lim): ?>
                                        <option value="<?php echo buildDeactiveQuery(['limit' => $lim, 'page' => 1]); ?>" <?php echo ($limit == $lim) ? 'selected' : ''; ?>>
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
                                        <th style="width: 70px;">Image</th>
                                        <th>Product Details</th>
                                        <th>Category</th>
                                        <th>Price (₹)</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th class="text-end" style="width: 140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <div class="py-4">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3 opacity-50"></i>
                                                    <h5>No Deactivated Products Found</h5>
                                                    <p class="text-muted small mb-3">All products in your selected filter are currently active in store.</p>
                                                    <a href="view-products.php" class="btn btn-sm btn-outline-primary">
                                                        View All Products
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $prod): 
                                            $imgUrl = !empty($prod['primary_image']) ? $prod['primary_image'] : 'img/icons/icon-48x48.png';
                                            $stock = (int)($prod['stock_quantity'] ?? $prod['product_quantity'] ?? $prod['stock'] ?? 0);
                                        ?>
                                            <tr id="row-prod-<?php echo $prod['id']; ?>">
                                                <td>
                                                    <div class="product-thumb-box cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($imgUrl); ?>', '<?php echo htmlspecialchars(addslashes($prod['name'])); ?>')">
                                                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" 
                                                             alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                                             onerror="this.src='img/icons/icon-48x48.png'">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <a href="edit-product.php?id=<?php echo $prod['id']; ?>" class="product-name-link mb-1">
                                                            <?php echo htmlspecialchars((string)($prod['name'] ?? '')); ?>
                                                        </a>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge-sku"><?php echo htmlspecialchars((string)($prod['sku'] ?: 'NO-SKU')); ?></span>
                                                            <?php if (!empty($prod['brand'])): ?>
                                                                <small class="text-muted"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars((string)$prod['brand']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge-cat">
                                                        <?php echo htmlspecialchars((string)($prod['category_name'] ?? 'Uncategorized')); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark">
                                                        ₹<?php echo number_format($prod['discount_price'] > 0 ? $prod['discount_price'] : $prod['price'], 2); ?>
                                                    </div>
                                                    <?php if ($prod['discount_price'] > 0 && $prod['discount_price'] < $prod['price']): ?>
                                                        <small class="text-muted text-decoration-line-through">₹<?php echo number_format($prod['price'], 2); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($stock > 10): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-2 py-1">
                                                            <?php echo $stock; ?> in stock
                                                        </span>
                                                    <?php elseif ($stock > 0): ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold px-2 py-1">
                                                            <?php echo $stock; ?> low stock
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold px-2 py-1">
                                                            Out of stock
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-medium" id="status-badge-<?php echo $prod['id']; ?>">
                                                        <?php echo ucfirst($prod['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($prod['updated_at'] ?: $prod['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" 
                                                                class="action-icon-btn btn-activate" 
                                                                title="Quick Activate to Live Store" 
                                                                onclick="activateProduct(<?php echo $prod['id']; ?>)">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                        <a href="single-product-version.php?id=<?php echo $prod['id']; ?>" class="action-icon-btn" title="Preview Product">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit-product.php?id=<?php echo $prod['id']; ?>" class="action-icon-btn" title="Edit Product">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        <a href="delete-product.php?id=<?php echo $prod['id']; ?>" 
                                                           class="action-icon-btn btn-delete" 
                                                           onclick="return confirm('Are you sure you want to permanently delete this product?')"
                                                           title="Delete Product">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex align-items-center justify-content-between p-3 border-top bg-light">
                                <div class="text-muted small">
                                    Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
                                </div>
                                <nav aria-label="Product pagination">
                                    <ul class="pagination pagination-sm mb-0">
                                        <!-- Prev -->
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildDeactiveQuery(['page' => $page - 1]); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>

                                        <!-- Pages -->
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="' . buildDeactiveQuery(['page' => 1]) . '">1</a></li>';
                                            if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        for ($p = $start_page; $p <= $end_page; $p++) {
                                            $active = ($p == $page) ? 'active' : '';
                                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . buildDeactiveQuery(['page' => $p]) . '">' . $p . '</a></li>';
                                        }
                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            echo '<li class="page-item"><a class="page-link" href="' . buildDeactiveQuery(['page' => $total_pages]) . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>

                                        <!-- Next -->
                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildDeactiveQuery(['page' => $page + 1]); ?>">
                                                <i class="fas fa-chevron-right"></i>
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

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="imageModalLabel">Product Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="modalImgPreview" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-height: 400px; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- Live Toast -->
    <div class="toast-container">
        <div id="liveToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                    <i class="fas fa-check-circle text-success"></i> Updated successfully.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function showImageModal(url, title) {
            document.getElementById('modalImgPreview').src = url;
            document.getElementById('imageModalLabel').innerText = title;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

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

        function activateProduct(productId) {
            if (!confirm('Activate this product to make it visible on the live store?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', productId);
            formData.append('status', 'active');

            fetch('deactive-products.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Product successfully activated!');
                    const row = document.getElementById('row-prod-' + productId);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.backgroundColor = '#ecfdf5';
                        setTimeout(() => {
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 400);
                        }, 500);
                    }
                } else {
                    showToast(data.error || 'Failed to activate product', false);
                }
            })
            .catch(err => {
                showToast('Network error while activating product', false);
            });
        }
    </script>
</body>
</html>