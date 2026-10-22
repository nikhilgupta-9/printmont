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

// Handle AJAX top selection toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_top_selection') {
    header('Content-Type: application/json');
    $productId = intval($_POST['product_id'] ?? 0);
    $result = $productController->toggleTopSelection($productId);
    echo json_encode($result);
    exit;
}

// -------------------------------------------------------------
// FILTER & PAGINATION PARAMETERS
// -------------------------------------------------------------
$search_query = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$top_selection_only = isset($_GET['only_selected']) ? trim($_GET['only_selected']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 15;
$offset = ($page - 1) * $limit;

// -------------------------------------------------------------
// KPI STATS OVERVIEW (Database Real Totals)
// -------------------------------------------------------------
$stats_sql = "SELECT 
    COUNT(*) as total_count,
    COUNT(CASE WHEN top_selection = 1 THEN 1 END) as top_selection_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_count
    FROM products";
$stats = $database->fetch($stats_sql) ?: [
    'total_count' => 0, 'top_selection_count' => 0, 'active_count' => 0, 'featured_count' => 0
];

// -------------------------------------------------------------
// GET PRODUCTS QUERY
// -------------------------------------------------------------
$where = ["1=1"];
$params = [];

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
if (!empty($status_filter)) {
    $where[] = "p.status = ?";
    $params[] = $status_filter;
}
if ($top_selection_only === '1') {
    $where[] = "p.top_selection = 1";
} elseif ($top_selection_only === '0') {
    $where[] = "(p.top_selection = 0 OR p.top_selection IS NULL)";
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
        ORDER BY p.top_selection DESC, p.created_at DESC
        LIMIT ? OFFSET ?";
$fetchParams = array_merge($params, [$limit, $offset]);
$products = $database->fetchAll($sql, $fetchParams) ?: [];

// Get categories for filter dropdown
$categories = $productController->getCategories();

// Helper function to build query strings
function buildTopSelQuery($newParams = []) {
    $current = $_GET;
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return 'top-selection-products.php?' . http_build_query($current);
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
    <title>Top Selection Products | Printmont Admin</title>
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
            color: #0d6efd;
        }
        .badge-sku {
            font-family: monospace;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        .badge-category {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 500;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .kpi-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.3em;
            cursor: pointer;
        }
        .form-switch .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }
        .action-icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            text-decoration: none;
            transition: all 0.15s;
        }
        .action-icon-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .action-icon-btn.btn-edit:hover {
            background: #e0f2fe;
            color: #0284c7;
            border-color: #bae6fd;
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
                    
                    <!-- Page Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h3 class="mb-0"><strong>Top Selection</strong> Products</h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0 text-muted" style="font-size: 12px;">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="view-products.php">Products</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Top Selection</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="view-products.php" class="btn btn-outline-secondary d-flex align-items-center gap-1.5">
                                <i class="align-middle" data-feather="arrow-left"></i> All Products
                            </a>
                            <a href="product.php" class="btn btn-primary d-flex align-items-center gap-1.5 shadow-sm">
                                <i class="align-middle" data-feather="plus"></i> Add Product
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                <div><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Stat Overview Strip -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Total Products</span>
                                    <h3 class="fw-bold mb-0 text-dark mt-1"><?php echo number_format($stats['total_count']); ?></h3>
                                    <span class="text-muted text-xs">Catalog total</span>
                                </div>
                                <div class="kpi-icon-wrap bg-primary-subtle text-primary">
                                    <i class="align-middle" data-feather="package"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Top Selected</span>
                                    <h3 class="fw-bold mb-0 text-success mt-1"><?php echo number_format($stats['top_selection_count']); ?></h3>
                                    <span class="text-muted text-xs">Featured in top section</span>
                                </div>
                                <div class="kpi-icon-wrap bg-success-subtle text-success">
                                    <i class="align-middle" data-feather="award"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Active Live</span>
                                    <h3 class="fw-bold mb-0 text-info mt-1"><?php echo number_format($stats['active_count']); ?></h3>
                                    <span class="text-muted text-xs">Store products</span>
                                </div>
                                <div class="kpi-icon-wrap bg-info-subtle text-info">
                                    <i class="align-middle" data-feather="check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Featured Flag</span>
                                    <h3 class="fw-bold mb-0 text-warning mt-1"><?php echo number_format($stats['featured_count']); ?></h3>
                                    <span class="text-muted text-xs">Home highlighted</span>
                                </div>
                                <div class="kpi-icon-wrap bg-warning-subtle text-warning">
                                    <i class="align-middle" data-feather="star"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Filter Card -->
                    <div class="filter-card p-3 mb-4">
                        <form method="GET" action="top-selection-products.php" id="topSelFilter">
                            <div class="row g-2 align-items-end">
                                
                                <div class="col-12 col-md-4 col-lg-3">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Search Products</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0" name="search" 
                                               value="<?php echo htmlspecialchars($search_query); ?>" 
                                               placeholder="Name, SKU, brand...">
                                    </div>
                                </div>

                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Category</label>
                                    <select class="form-select form-select-sm" name="category">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-6 col-md-2 col-lg-2">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Top Selection Filter</label>
                                    <select class="form-select form-select-sm" name="only_selected">
                                        <option value="">All Products</option>
                                        <option value="1" <?php echo $top_selection_only === '1' ? 'selected' : ''; ?>>Top Selection Only</option>
                                        <option value="0" <?php echo $top_selection_only === '0' ? 'selected' : ''; ?>>Not Top Selection</option>
                                    </select>
                                </div>

                                <div class="col-6 col-md-2 col-lg-2">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Status</label>
                                    <select class="form-select form-select-sm" name="status">
                                        <option value="">All Status</option>
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="col-6 col-md-1 col-lg-1">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Per Page</label>
                                    <select class="form-select form-select-sm" name="limit" onchange="document.getElementById('topSelFilter').submit()">
                                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-2 col-lg-2 d-flex gap-1.5">
                                    <button type="submit" class="btn btn-sm btn-primary w-50 fw-semibold">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <a href="top-selection-products.php" class="btn btn-sm btn-outline-secondary w-50">Reset</a>
                                </div>

                            </div>
                        </form>
                    </div>

                    <!-- Products Table Card -->
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-0 fw-bold">Top Selection Management (<?php echo number_format($total_products); ?>)</h5>
                                <div class="text-muted" style="font-size: 12px;">Toggle switch to mark products in top selection carousel</div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted" style="font-size: 12px; font-weight: 600;">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">Image</th>
                                            <th style="min-width: 220px;">Product Details</th>
                                            <th style="width: 140px;">SKU &amp; Category</th>
                                            <th style="width: 120px;">Price (₹)</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 150px;" class="text-center">Top Selection</th>
                                            <th style="width: 100px;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 13px;">
                                        <?php if (empty($products)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="fas fa-award fs-1 opacity-50 mb-2"></i>
                                                    <h6>No products found</h6>
                                                    <a href="top-selection-products.php" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($products as $p): 
                                                $regPrice = (float)($p['regular_price'] ?? $p['price'] ?? 0);
                                                $offPrice = !empty($p['offer_price']) ? (float)$p['offer_price'] : ((float)($p['discount_price'] ?? 0));
                                                $activePrice = ($offPrice > 0 && $offPrice < $regPrice) ? $offPrice : $regPrice;
                                                $isTopSel = !empty($p['top_selection']);
                                                $imgSrc = $p['primary_image'] ?? $p['thumbnail_image'] ?? 'https://via.placeholder.com/100x100?text=No+Image';
if (!empty($imgSrc) && strpos($imgSrc, 'http') !== 0) { $imgSrc = ltrim($imgSrc, '/'); }
                                            ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <div class="product-thumb-box mx-auto">
                                                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <a href="edit-product.php?id=<?php echo $p['id']; ?>" class="product-name-link mb-1">
                                                            <?php echo htmlspecialchars((string)($p['name'] ?? '')); ?>
                                                        </a>
                                                        <?php if (!empty($p['brand'])): ?>
                                                            <div><span class="badge bg-light text-secondary border" style="font-size: 10px;"><?php echo htmlspecialchars((string)$p['brand']); ?></span></div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td>
                                                        <div class="badge-sku mb-1"><?php echo htmlspecialchars((string)($p['sku'] ?: ('ID-' . $p['id']))); ?></div>
                                                        <span class="badge-category"><?php echo htmlspecialchars((string)($p['category_name'] ?? 'Uncategorized')); ?></span>
                                                    </td>

                                                    <td>
                                                        <strong class="text-dark">₹<?php echo number_format($activePrice, 2); ?></strong>
                                                    </td>

                                                    <td>
                                                        <span class="badge <?php echo $p['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?> rounded-pill px-2 py-1">
                                                            ● <?php echo ucfirst($p['status']); ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input top-selection-switch" type="checkbox" role="switch"
                                                                   id="topSelSwitch<?php echo $p['id']; ?>"
                                                                   data-product-id="<?php echo $p['id']; ?>"
                                                                   <?php echo $isTopSel ? 'checked' : ''; ?>
                                                                   onchange="toggleTopSelectionAjax(<?php echo $p['id']; ?>, this)">
                                                        </div>
                                                        <span id="topSelLabel<?php echo $p['id']; ?>" class="badge <?php echo $isTopSel ? 'bg-success text-white' : 'bg-light text-muted border'; ?> ms-1" style="font-size: 10.5px;">
                                                            <?php echo $isTopSel ? 'Selected' : 'Off'; ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-end">
                                                        <div class="d-flex justify-content-end gap-1">
                                                            <a href="http://localhost:5173/product/<?php echo urlencode($p['product_slug'] ?: 'item'); ?>-p<?php echo $p['id']; ?>" target="_blank" class="action-icon-btn" title="View Frontend">
                                                                <i class="fas fa-external-link-alt" style="font-size: 11px;"></i>
                                                            </a>
                                                            <a href="edit-product.php?id=<?php echo $p['id']; ?>" class="action-icon-btn btn-edit" title="Edit Product">
                                                                <i class="fas fa-pen" style="font-size: 11px;"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer bg-white border-top py-3 d-flex flex-wrap align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong> (<?php echo $total_products; ?> total products)
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildTopSelQuery(['page' => $page - 1]); ?>">
                                                <i class="fas fa-chevron-left" style="font-size: 10px;"></i>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="<?php echo buildTopSelQuery(['page' => $i]); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildTopSelQuery(['page' => $page + 1]); ?>">
                                                <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
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

    <script src="js/app.js"></script>
    <script>
        function toggleTopSelectionAjax(productId, switchEl) {
            var isChecked = switchEl.checked;
            var labelEl = document.getElementById('topSelLabel' + productId);
            
            switchEl.disabled = true;
            
            var formData = new FormData();
            formData.append('action', 'toggle_top_selection');
            formData.append('product_id', productId);

            fetch('top-selection-products.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                switchEl.disabled = false;
                if (data.success) {
                    if (data.is_top_selection == 1) {
                        switchEl.checked = true;
                        labelEl.className = 'badge bg-success text-white ms-1';
                        labelEl.innerText = 'Selected';
                    } else {
                        switchEl.checked = false;
                        labelEl.className = 'badge bg-light text-muted border ms-1';
                        labelEl.innerText = 'Off';
                    }
                } else {
                    switchEl.checked = !isChecked;
                    alert(data.error || 'Failed to update status');
                }
            })
            .catch(err => {
                switchEl.disabled = false;
                switchEl.checked = !isChecked;
                console.error(err);
            });
        }
    </script>
</body>
</html>