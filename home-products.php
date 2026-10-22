<?php
/**
 * Home Products Management — Printmont Admin
 * 
 * View, filter, and manage products assigned to Home Page carousels & grids.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $delStmt = $db->prepare("DELETE FROM home_products WHERE id = ?");
    $delStmt->bind_param('i', $delId);
    if ($delStmt->execute()) {
        $message = "Home product assignment #$delId removed successfully!";
    } else {
        $error = "Failed to remove assignment: " . $delStmt->error;
    }
    $delStmt->close();
}

// Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && !empty($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    $tStmt = $db->prepare("UPDATE home_products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $tStmt->bind_param('i', $toggleId);
    if ($tStmt->execute()) {
        $message = "Home product #$toggleId status updated!";
    }
    $tStmt->close();
}

// Stats
$stats = [
    'total' => 0,
    'desktop_active' => 0,
    'mobile_active' => 0,
    'sponsored' => 0
];
$statRes = $db->query("SELECT 
    COUNT(*) as total,
    SUM(IF(desktop_home_show = 'yes' AND status = 'active', 1, 0)) as desktop_active,
    SUM(IF(mobile_home_show = 'yes' AND status = 'active', 1, 0)) as mobile_active,
    SUM(IF(is_sponsored = 1, 1, 0)) as sponsored
FROM home_products");
if ($statRes && $r = $statRes->fetch_assoc()) {
    $stats = $r;
}

// Fetch home products
$query = "SELECT hp.*,
    p.name as product_name,
    p.sku as product_sku,
    p.price as product_price,
    p.offer_price as product_offer_price,
    p.thumbnail_image as product_image,
    c.title as carousel_title,
    cat.name as category_name
FROM home_products hp
LEFT JOIN products p ON hp.product_id = p.id
LEFT JOIN carousels c ON (hp.home_carousel_id = c.id OR hp.desktop_home_carousel_id = c.id)
LEFT JOIN categories cat ON hp.home_category_id = cat.id
ORDER BY hp.sort_order ASC, hp.id DESC";

$homeProducts = [];
$res = $db->query($query);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $homeProducts[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Home Products | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .prod-thumb {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
        .status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-decoration: none;
        }
        .status-badge.active {
            background-color: #dcfce7;
            color: #15803d;
        }
        .status-badge.inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .stat-card {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 16px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3 align-items-center">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong>Home Page</strong> Products</h1>
                            <p class="text-muted small">Products configured to show in featured carousels, grids, and spotlight sections on Home.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="add-home-product.php" class="btn btn-primary px-3">
                                <i class="fas fa-plus me-1"></i> Assign Product to Home
                            </a>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-primary bg-opacity-10 rounded text-primary fs-3">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['total']; ?></h4>
                                    <div class="text-muted small">Total Assigned</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-success bg-opacity-10 rounded text-success fs-3">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['desktop_active']; ?></h4>
                                    <div class="text-muted small">Active on Desktop</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-info bg-opacity-10 rounded text-info fs-3">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['mobile_active']; ?></h4>
                                    <div class="text-muted small">Active on Mobile</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-warning bg-opacity-10 rounded text-warning fs-3">
                                    <i class="fas fa-ad"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['sponsored']; ?></h4>
                                    <div class="text-muted small">Sponsored Items</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Assigned Products (<?php echo count($homeProducts); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($homeProducts)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open text-muted mb-3" style="font-size: 48px;"></i>
                                    <h5>No Products Assigned to Home Yet</h5>
                                    <p class="text-muted small mb-3">Assign individual catalog products to specific carousels or home sections.</p>
                                    <a href="add-home-product.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Assign First Product
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Product</th>
                                                <th>Carousel</th>
                                                <th>Category</th>
                                                <th>Desktop View</th>
                                                <th>Mobile View</th>
                                                <th>Order</th>
                                                <th>Status</th>
                                                <th class="text-end" style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($homeProducts as $hp): ?>
                                                <tr>
                                                    <td><span class="text-muted fw-bold">#<?php echo $hp['id']; ?></span></td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="<?php echo htmlspecialchars($hp['product_image'] ?: 'img/photos/unsplash-1.jpg'); ?>" class="prod-thumb" alt="Product">
                                                            <div>
                                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($hp['product_name'] ?: 'Product #' . $hp['product_id']); ?></div>
                                                                <small class="text-muted">₹<?php echo number_format($hp['product_offer_price'] ?: $hp['product_price'], 2); ?> | SKU: <?php echo htmlspecialchars($hp['product_sku'] ?? 'N/A'); ?></small>
                                                                <?php if (!empty($hp['is_sponsored'])): ?>
                                                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;"><i class="fas fa-star me-1"></i>Sponsored</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                            <?php echo htmlspecialchars($hp['carousel_title'] ?: 'Standalone'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted small"><?php echo htmlspecialchars($hp['category_name'] ?: 'All / Default'); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $hp['desktop_home_show'] === 'yes' ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 10px;">
                                                            <?php echo strtoupper($hp['desktop_home_show']); ?>
                                                        </span>
                                                        <small class="text-muted ms-1"><?php echo $hp['desktop_home_design']; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $hp['mobile_home_show'] === 'yes' ? 'bg-info text-dark' : 'bg-secondary'; ?>" style="font-size: 10px;">
                                                            <?php echo strtoupper($hp['mobile_home_show']); ?>
                                                        </span>
                                                        <small class="text-muted ms-1"><?php echo $hp['mobile_home_design']; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?php echo (int)$hp['sort_order']; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="home-products.php?action=toggle&id=<?php echo $hp['id']; ?>" class="status-badge <?php echo $hp['status']; ?>" title="Click to toggle">
                                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?php echo ucfirst($hp['status']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="add-home-product.php?id=<?php echo $hp['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="home-products.php?action=delete&id=<?php echo $hp['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this product from home?');" title="Remove">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
