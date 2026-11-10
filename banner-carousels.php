<?php
/**
 * Banner Carousels Management — Printmont Admin
 * 
 * View, manage, and toggle multi-banner sliders, subtypes, and format carousels.
 * Complies with Document 6: "6 no. Home Pages Banner Slider Setting".
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
    $delStmt = $db->prepare("DELETE FROM banner_carousels WHERE id = ?");
    $delStmt->bind_param('i', $delId);
    if ($delStmt->execute()) {
        $message = "Banner Slider #$delId deleted successfully!";
    } else {
        $error = "Failed to delete banner slider: " . $delStmt->error;
    }
    $delStmt->close();
}

// Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && !empty($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    $tStmt = $db->prepare("UPDATE banner_carousels SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $tStmt->bind_param('i', $toggleId);
    if ($tStmt->execute()) {
        $message = "Banner Slider #$toggleId status updated!";
    }
    $tStmt->close();
}

$subtypeTitles = [
    'top_slider'            => '1. Top Banner Slider',
    'small_slider'          => '2. Banner Small Slider',
    'offer_slider'          => '3. Banner Offer Slider',
    'service_slider'        => '4. Category Service Slider',
    'product_detail_slider' => '5. Product Details Slider',
    'dashboard_slider'      => '6. User Dashboard Slider',
];

$typeFilter = $_GET['type'] ?? '';
if (!empty($typeFilter) && !array_key_exists($typeFilter, $subtypeTitles)) {
    $typeFilter = '';
}

// Fetch stats
$stats = [
    'total' => 0,
    'active_desktop' => 0,
    'active_mobile' => 0
];
$statRes = $db->query("SELECT 
    COUNT(*) as total,
    SUM(IF(desktop_status = 'active', 1, 0)) as active_desktop,
    SUM(IF(mobile_status = 'active', 1, 0)) as active_mobile
FROM banner_carousels");
if ($statRes && $r = $statRes->fetch_assoc()) {
    $stats = $r;
}

// Fetch banner carousels
$whereClause = "1=1";
$params = [];
$types = "";

if (!empty($typeFilter)) {
    $whereClause .= " AND bc.slider_subtype = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

$query = "SELECT bc.*, 
    c.name as desktop_cat_name, 
    car.title as desktop_carousel_name 
FROM banner_carousels bc
LEFT JOIN categories c ON bc.desktop_home_category_id = c.id
LEFT JOIN carousels car ON bc.desktop_home_carousel_id = car.id
WHERE {$whereClause}
ORDER BY bc.desktop_sort_order ASC, bc.id DESC";

$bannerCarousels = [];
if (!empty($params)) {
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($r = $res->fetch_assoc()) $bannerCarousels[] = $r;
    }
    $stmt->close();
} else {
    $res = $db->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) $bannerCarousels[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Banner Sliders &amp; Carousels | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: #0d6efd; background: #e0edff; }
        .module-nav-link.active { color: #fff; background: #0d6efd; }

        .stat-card {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 16px;
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
        .badge-design {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            background: #e0f2fe;
            color: #0369a1;
            display: inline-block;
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

                    <!-- Document 6 Banner Slider Navigation Pills -->
                    <div class="module-nav-pills mb-4">
                        <a href="banner-carousels.php" class="module-nav-link <?php echo empty($typeFilter) ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Banner Sliders
                        </a>
                        <a href="banner-carousels.php?type=top_slider" class="module-nav-link <?php echo $typeFilter === 'top_slider' ? 'active' : ''; ?>">
                            <i class="fas fa-desktop"></i> Top Slider
                        </a>
                        <a href="banner-carousels.php?type=small_slider" class="module-nav-link <?php echo $typeFilter === 'small_slider' ? 'active' : ''; ?>">
                            <i class="fas fa-compress-arrows-alt"></i> Small Slider
                        </a>
                        <a href="banner-carousels.php?type=offer_slider" class="module-nav-link <?php echo $typeFilter === 'offer_slider' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i> Offer Slider
                        </a>
                        <a href="banner-carousels.php?type=service_slider" class="module-nav-link <?php echo $typeFilter === 'service_slider' ? 'active' : ''; ?>">
                            <i class="fas fa-concierge-bell"></i> Service Slider
                        </a>
                        <a href="banner-carousels.php?type=product_detail_slider" class="module-nav-link <?php echo $typeFilter === 'product_detail_slider' ? 'active' : ''; ?>">
                            <i class="fas fa-boxes"></i> Product Detail Slider
                        </a>
                        <a href="banner-carousels.php?type=dashboard_slider" class="module-nav-link <?php echo $typeFilter === 'dashboard_slider' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard Slider
                        </a>
                        <a href="add-banner-carousel.php<?php echo !empty($typeFilter) ? '?type=' . urlencode($typeFilter) : ''; ?>" class="module-nav-link ms-auto text-primary fw-bold">
                            <i class="fas fa-plus-circle"></i> Add New Slider
                        </a>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong>Banner</strong> Sliders &amp; Carousels</h1>
                            <p class="text-muted small">Multi-banner promotional sliders, subtypes &amp; responsive display rules (Document 6).</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="add-banner-carousel.php<?php echo !empty($typeFilter) ? '?type=' . urlencode($typeFilter) : ''; ?>" class="btn btn-primary px-3">
                                <i class="fas fa-plus me-1"></i> Add <?php echo !empty($typeFilter) ? htmlspecialchars($subtypeTitles[$typeFilter]) : 'Banner Slider'; ?>
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
                        <div class="col-md-4">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-primary bg-opacity-10 rounded text-primary fs-3">
                                    <i class="fas fa-sliders-h"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['total']; ?></h4>
                                    <div class="text-muted small">Total Sliders</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-success bg-opacity-10 rounded text-success fs-3">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['active_desktop']; ?></h4>
                                    <div class="text-muted small">Active on Desktop</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-info bg-opacity-10 rounded text-info fs-3">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['active_mobile']; ?></h4>
                                    <div class="text-muted small">Active on Mobile</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Banner Slider List (<?php echo count($bannerCarousels); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($bannerCarousels)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-images text-muted mb-3" style="font-size: 48px;"></i>
                                    <h5>No Banner Sliders Found</h5>
                                    <p class="text-muted small mb-3">Create sliding promotional banners across home and other pages.</p>
                                    <a href="add-banner-carousel.php<?php echo !empty($typeFilter) ? '?type=' . urlencode($typeFilter) : ''; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Add Banner Slider
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Type &amp; Title</th>
                                                <th>Scope &amp; Anchor Placement</th>
                                                <th>Desktop Design</th>
                                                <th>Mobile Design</th>
                                                <th>Order</th>
                                                <th>Status</th>
                                                <th class="text-end" style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bannerCarousels as $bc): ?>
                                                <tr>
                                                    <td><span class="text-muted fw-bold">#<?php echo $bc['id']; ?></span></td>
                                                    <td>
                                                        <span class="badge bg-primary-subtle text-primary fw-semibold mb-1">
                                                            <?php echo htmlspecialchars($subtypeTitles[$bc['slider_subtype']] ?? 'Top Slider'); ?>
                                                        </span>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($bc['title']); ?></div>
                                                        <?php if (!empty($bc['text_tag_name'])): ?>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning" style="font-size:10px;">
                                                                Tag: <?php echo htmlspecialchars($bc['text_tag_name']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($bc['page_target'] ?? 'both'); ?></span>
                                                            Page: <strong><?php echo htmlspecialchars($bc['desktop_page'] ?? 'home'); ?></strong>
                                                        </div>
                                                        <?php if (!empty($bc['desktop_anchor_type'])): ?>
                                                            <small class="text-muted d-block mt-1">
                                                                <i class="fas fa-arrows-alt-v text-primary"></i> 
                                                                <?php echo ucfirst($bc['desktop_anchor_position'] ?? 'after'); ?> <?php echo htmlspecialchars($bc['desktop_anchor_type']); ?> #<?php echo (int)$bc['desktop_anchor_id']; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge-design"><?php echo strtoupper($bc['desktop_design'] ?: 'DEFAULT'); ?></span>
                                                        <span class="badge <?php echo $bc['desktop_status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 10px;">
                                                            <?php echo $bc['desktop_status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (($bc['mobile_same_design'] ?? 'no') === 'yes'): ?>
                                                            <span class="badge bg-info-subtle text-info border border-info mb-1" style="font-size:10px;">Same as Desktop</span>
                                                        <?php else: ?>
                                                            <span class="badge-design"><?php echo strtoupper($bc['mobile_design'] ?: 'DEFAULT'); ?></span>
                                                        <?php endif; ?>
                                                        <span class="badge <?php echo $bc['mobile_status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 10px;">
                                                            <?php echo $bc['mobile_status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?php echo (float)$bc['desktop_sort_order']; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="banner-carousels.php?action=toggle&id=<?php echo $bc['id']; ?>" class="status-badge <?php echo $bc['status']; ?>" title="Toggle status">
                                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?php echo ucfirst($bc['status']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="edit-banner-carousel.php?id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="banner-carousels.php?action=delete&id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this banner carousel?');" title="Delete">
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
