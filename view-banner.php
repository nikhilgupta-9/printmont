<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');
require_once(__DIR__ . '/includes/banner-helpers.php');

$bannerController = new BannerController();

// ----------------------------------------------------------------
// AJAX Action Handlers (Instant On/Off Toggle & Page Visibility Toggle)
// ----------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'ajax_toggle_status') {
        $itemType = trim($_POST['item_type'] ?? 'banner');
        $id       = (int)($_POST['id'] ?? 0);
        $status   = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        $res = $bannerController->toggleMasterStatus($itemType, $id, $status);
        echo json_encode($res);
        exit;
    }

    if ($action === 'ajax_toggle_page') {
        $itemType = trim($_POST['item_type'] ?? 'banner');
        $id       = (int)($_POST['id'] ?? 0);
        $pageKey  = trim($_POST['page_key'] ?? '');
        $val      = ($_POST['val'] ?? 'yes') === 'yes' ? 'yes' : 'no';

        if ($id <= 0 || empty($pageKey)) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        $res = $bannerController->togglePageVisibility($itemType, $id, $pageKey, $val);
        echo json_encode($res);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// ----------------------------------------------------------------
// Standard GET Actions (Delete, Non-AJAX Fallbacks)
// ----------------------------------------------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $itemType = trim($_GET['item_type'] ?? 'banner');
    $id = (int)($_GET['id'] ?? 0);

    if ($action === 'delete_item' && $id > 0) {
        $res = $bannerController->deleteMasterItem($itemType, $id);
        if ($res['success']) {
            $_SESSION['success_message'] = ucfirst(str_replace('_', ' ', $itemType)) . " #{$id} deleted successfully!";
        } else {
            $_SESSION['error_message'] = $res['error'] ?? "Failed to delete item.";
        }
        $query = $_GET;
        unset($query['action'], $query['id'], $query['item_type']);
        $qs = http_build_query($query);
        header("Location: view-banner.php" . ($qs ? "?{$qs}" : ''));
        exit;
    }

    if ($action === 'toggle_status' && $id > 0) {
        $status = ($_GET['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
        $res = $bannerController->toggleMasterStatus($itemType, $id, $status);
        if ($res['success']) {
            $_SESSION['success_message'] = ucfirst(str_replace('_', ' ', $itemType)) . " #{$id} status updated to " . ucfirst($status) . "!";
        } else {
            $_SESSION['error_message'] = $res['error'] ?? "Failed to update status.";
        }
        $query = $_GET;
        unset($query['action'], $query['id'], $query['status']);
        $qs = http_build_query($query);
        header("Location: view-banner.php" . ($qs ? "?{$qs}" : ''));
        exit;
    }
}

// ----------------------------------------------------------------
// Filter Parameters
// ----------------------------------------------------------------
$filterPage   = trim($_GET['filter_page'] ?? 'all');
$filterType   = trim($_GET['filter_type'] ?? 'all');
$filterStatus = trim($_GET['filter_status'] ?? 'all');
$search       = trim($_GET['search'] ?? '');

// 8 Specific Other Pages specified by client + Home + All
$otherPagesList = [
    'category'           => ['label' => 'Category Page',          'icon' => 'fa-th-large',    'badge' => 'CAT'],
    'product'            => ['label' => 'Product Page',           'icon' => 'fa-box',         'badge' => 'PROD'],
    'product_detail'     => ['label' => 'Product Detail Page',    'icon' => 'fa-tag',         'badge' => 'DETAIL'],
    'franchise'          => ['label' => 'Franchise Page',         'icon' => 'fa-store',       'badge' => 'FRAN'],
    'affiliate_program'  => ['label' => 'Affiliate Program Page', 'icon' => 'fa-handshake',   'badge' => 'AFF'],
    'career'             => ['label' => 'Career Page',            'icon' => 'fa-user-tie',    'badge' => 'CAREER'],
    'business_solutions' => ['label' => 'Business Solutions',     'icon' => 'fa-briefcase',   'badge' => 'BIZ'],
    'advertising'        => ['label' => 'Advertising Page',       'icon' => 'fa-bullhorn',    'badge' => 'ADS'],
    'about_us'           => ['label' => 'About Us Page',          'icon' => 'fa-info-circle', 'badge' => 'ABOUT'],
];

// Fetch all unfiltered items for statistics calculation
$allItemsForStats = $bannerController->getAllMasterItems('all', 'all', '', 'all');
$totalAllCount    = count($allItemsForStats);
$activeHomeCount  = 0;
$activeOtherCount = 0;
$inactiveCount    = 0;

foreach ($allItemsForStats as $it) {
    if ($it['status'] === 'inactive') {
        $inactiveCount++;
        continue;
    }
    $tPage = $it['target_page'] ?? 'home';
    if ($tPage === 'home' || $tPage === 'both' || empty($tPage)) {
        $activeHomeCount++;
    }
    $hasOther = false;
    foreach (array_keys($otherPagesList) as $opk) {
        $colKey = ($opk === 'affiliate_program') ? 'show_on_affiliate' : 'show_on_' . $opk;
        if (!empty($it[$colKey]) && $it[$colKey] === 'yes') {
            $hasOther = true;
            break;
        }
    }
    if (!$hasOther && !empty($it['other_pages_display']) && $it['other_pages_display'] !== '[]') {
        $hasOther = true;
    }
    if ($hasOther || ($tPage !== 'home' && $tPage !== 'both' && !empty($tPage))) {
        $activeOtherCount++;
    }
}

// Fetch Filtered Items for Display
$masterItems = $bannerController->getAllMasterItems($filterPage, $filterType, $search, $filterStatus);
$displayedCount = count($masterItems);

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message']   ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Banners &amp; Carousels Master Module | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f8fafc; }

        /* Top Module Navigation Pills */
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: #0d6efd; background: #e0edff; }
        .module-nav-link.active { color: #fff; background: #0d6efd; }

        /* Page Filter Pills */
        .page-filter-scroll { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 12px; }
        .page-filter-pill { white-space: nowrap; padding: 7px 14px; font-size: 12px; font-weight: 600; border-radius: 20px; border: 1px solid #cbd5e1; background: #fff; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .page-filter-pill:hover { background: #e2e8f0; color: #1e293b; }
        .page-filter-pill.active { background: #0d6efd; color: #fff; border-color: #0d6efd; box-shadow: 0 2px 6px rgba(13,110,253,0.3); }

        /* Type Filter Pills */
        .type-filter-btn { padding: 6px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s; }
        .type-filter-btn:hover { background: #f1f5f9; color: #1e293b; }
        .type-filter-btn.active { background: #1e293b; color: #fff; border-color: #1e293b; }

        /* Modern Table Card */
        .master-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); overflow: hidden; }
        .table > tbody > tr > td { vertical-align: middle; padding: 12px 14px; font-size: 13px; }
        .table > thead > tr > th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; padding: 12px 14px; }

        /* Thumbnails */
        .thumb-box { width: 72px; height: 48px; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; background: #f1f5f9; position: relative; display: flex; align-items: center; justify-content: center; }
        .thumb-box img { width: 100%; height: 100%; object-fit: cover; }
        .thumb-box-mobile { width: 34px; height: 48px; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; background: #f1f5f9; position: relative; }
        .thumb-box-mobile img { width: 100%; height: 100%; object-fit: cover; }

        /* Multi-Page Clickable Badges */
        .page-badge-pill {
            display: inline-flex;
            align-items: center;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
            margin: 2px;
        }
        .page-badge-pill.on {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .page-badge-pill.on:hover {
            background: #bbf7d0;
            color: #14532d;
            transform: scale(1.05);
        }
        .page-badge-pill.off {
            background: #f1f5f9;
            color: #94a3b8;
            border-color: #cbd5e1;
        }
        .page-badge-pill.off:hover {
            background: #e2e8f0;
            color: #475569;
            transform: scale(1.05);
        }
        .page-badge-pill.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Order Badge */
        .order-circle { width: 28px; height: 28px; line-height: 28px; text-align: center; border-radius: 50%; background: #f1f5f9; color: #334155; font-size: 11px; font-weight: 700; display: inline-block; }

        /* Toast Container */
        .toast-container-fixed { position: fixed; bottom: 20px; right: 20px; z-index: 9999; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <!-- Module Breadcrumb & Navigation Pills -->
                <div class="module-nav-pills mb-4">
                    <a href="add-banner.php" class="module-nav-link">
                        <i class="fas fa-plus-circle text-primary"></i> Add Banner
                    </a>
                    <a href="add-carousel.php" class="module-nav-link">
                        <i class="fas fa-plus-circle text-purple"></i> Add Carousel
                    </a>
                    <a href="add-banner-carousel.php" class="module-nav-link">
                        <i class="fas fa-sliders-h text-warning"></i> Add Banner Slider
                    </a>
                    <a href="carousels.php" class="module-nav-link">
                        <i class="fas fa-boxes text-info"></i> Product Carousels
                    </a>
                    <a href="banner-carousels.php" class="module-nav-link">
                        <i class="fas fa-layer-group text-success"></i> Banner Sliders
                    </a>
                    <a href="view-banner.php" class="module-nav-link active ms-auto">
                        <i class="fas fa-th-list"></i> Master Module
                    </a>
                </div>

                <!-- Page Header & Action Buttons -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-7">
                        <h1 class="h3 mb-1 text-gray-800">
                            <strong>Banners &amp; Carousels Master Module</strong>
                        </h1>
                        <p class="text-muted small mb-0">
                            Unified management for all promotional banners and carousels. Turn items On/Off and control multi-page display across Home and 8 Other Storefront Pages.
                        </p>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                        <a href="add-banner.php" class="btn btn-primary btn-sm px-3 shadow-sm">
                            <i class="fas fa-plus me-1"></i> Add New Banner
                        </a>
                        <a href="add-carousel.php" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                            <i class="fas fa-sliders-h me-1"></i> Add New Carousel
                        </a>
                        <a href="about-us-page.php#band-carousels" class="btn btn-outline-info btn-sm px-3 shadow-sm">
                            <i class="fas fa-info-circle me-1"></i> About Us Carousels
                        </a>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <a href="view-banner.php?filter_page=all&filter_type=all" class="text-decoration-none">
                            <div class="card border-0 shadow-sm mb-0 h-100 <?php echo ($filterPage === 'all') ? 'border-primary' : ''; ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Total Items</span>
                                            <h3 class="fw-bold mb-0 text-dark"><?php echo $totalAllCount; ?></h3>
                                        </div>
                                        <div class="rounded-circle p-2 bg-primary-subtle text-primary" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-boxes fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="view-banner.php?filter_page=home" class="text-decoration-none">
                            <div class="card border-0 shadow-sm mb-0 h-100 <?php echo ($filterPage === 'home') ? 'border-primary' : ''; ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Active on Home</span>
                                            <h3 class="fw-bold mb-0 text-primary"><?php echo $activeHomeCount; ?></h3>
                                        </div>
                                        <div class="rounded-circle p-2 bg-info-subtle text-info" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-home fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm mb-0 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">On Other Pages</span>
                                        <h3 class="fw-bold mb-0 text-success"><?php echo $activeOtherCount; ?></h3>
                                    </div>
                                    <div class="rounded-circle p-2 bg-success-subtle text-success" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-layer-group fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="view-banner.php?filter_status=inactive" class="text-decoration-none">
                            <div class="card border-0 shadow-sm mb-0 h-100 <?php echo ($filterStatus === 'inactive') ? 'border-danger' : ''; ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Deactivated / Off</span>
                                            <h3 class="fw-bold mb-0 text-danger"><?php echo $inactiveCount; ?></h3>
                                        </div>
                                        <div class="rounded-circle p-2 bg-danger-subtle text-danger" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-power-off fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Primary Page Filter Pills (Home & 8 Specific Other Pages) -->
                <div class="master-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark small text-uppercase">
                            <i class="fas fa-map-marker-alt text-primary me-1"></i> Filter by Target Page:
                        </span>
                        <?php if ($filterPage !== 'all' || $filterType !== 'all' || $filterStatus !== 'all' || $search !== ''): ?>
                            <a href="view-banner.php" class="text-danger small fw-semibold text-decoration-none">
                                <i class="fas fa-times-circle me-1"></i> Reset All Filters
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="page-filter-scroll">
                        <a href="view-banner.php?filter_page=all&filter_type=<?php echo urlencode($filterType); ?>&filter_status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>"
                           class="page-filter-pill <?php echo ($filterPage === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-globe"></i> All Pages (<?php echo $totalAllCount; ?>)
                        </a>
                        <a href="view-banner.php?filter_page=home&filter_type=<?php echo urlencode($filterType); ?>&filter_status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>"
                           class="page-filter-pill <?php echo ($filterPage === 'home') ? 'active' : ''; ?>">
                            <i class="fas fa-home text-warning"></i> Home Page (Main)
                        </a>
                        <?php foreach ($otherPagesList as $opKey => $opMeta): ?>
                            <a href="view-banner.php?filter_page=<?php echo urlencode($opKey); ?>&filter_type=<?php echo urlencode($filterType); ?>&filter_status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>"
                               class="page-filter-pill <?php echo ($filterPage === $opKey) ? 'active' : ''; ?>">
                                <i class="fas <?php echo $opMeta['icon']; ?>"></i> <?php echo htmlspecialchars($opMeta['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Secondary Filter Row: Types, Status, and Search -->
                    <div class="row g-2 align-items-center pt-2 border-top">
                        <div class="col-lg-6 col-md-12 d-flex gap-2 flex-wrap align-items-center">
                            <span class="text-muted small me-1">Type:</span>
                            <a href="view-banner.php?filter_page=<?php echo urlencode($filterPage); ?>&filter_type=all&filter_status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>"
                               class="type-filter-btn <?php echo ($filterType === 'all') ? 'active' : ''; ?>">
                                All Items
                            </a>
                            <a href="view-banner.php?filter_page=<?php echo urlencode($filterPage); ?>&filter_type=banner&filter_status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>"
                               class="type-filter-btn <?php echo ($filterType === 'banner') ? 'active' : ''; ?>">
                                <i class="fas fa-image text-primary"></i> Banners Only
                            </a>
                            <a href="view-banner.php?filter_page=<?php echo urlencode($filterPage); ?>&filter_type=carousel&filter_status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>"
                               class="type-filter-btn <?php echo ($filterType === 'carousel') ? 'active' : ''; ?>">
                                <i class="fas fa-sliders-h text-purple"></i> Carousels &amp; Sliders
                            </a>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <form method="GET" action="view-banner.php" class="row g-2 justify-content-lg-end">
                                <input type="hidden" name="filter_page" value="<?php echo htmlspecialchars($filterPage); ?>">
                                <input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($filterType); ?>">
                                <div class="col-auto">
                                    <select name="filter_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="all" <?php echo ($filterStatus === 'all') ? 'selected' : ''; ?>>All Status</option>
                                        <option value="active" <?php echo ($filterStatus === 'active') ? 'selected' : ''; ?>>Active Only</option>
                                        <option value="inactive" <?php echo ($filterStatus === 'inactive') ? 'selected' : ''; ?>>Inactive Only</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <div class="input-group input-group-sm" style="max-width: 260px;">
                                        <input type="text" name="search" class="form-control" placeholder="Search title, alt, seo..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Master Items Table Card -->
                <div class="master-card">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-list text-primary me-2"></i> All Configured Items (<?php echo $displayedCount; ?>)
                        </h5>
                        <div class="small text-muted">
                            <i class="fas fa-mouse-pointer text-primary me-1"></i> Click on page badges below to instantly toggle visibility on other pages
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;" class="text-center">#ID</th>
                                    <th style="width: 140px;">Thumb Preview</th>
                                    <th>Title, Alt &amp; SEO Meta</th>
                                    <th style="width: 130px;">Item Type</th>
                                    <th style="width: 110px;">Primary Page</th>
                                    <th style="min-width: 240px;">Show on Other Pages (Click to Toggle)</th>
                                    <th style="width: 70px;" class="text-center">Order</th>
                                    <th style="width: 90px;" class="text-center">On / Off</th>
                                    <th style="width: 100px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($masterItems)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-folder-open text-muted fa-3x mb-3 opacity-50"></i>
                                            <h5 class="text-muted fw-semibold">No Banners or Carousels Found</h5>
                                            <p class="text-muted small mb-3">Try clearing filters or add your first banner or carousel.</p>
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a href="add-banner.php" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-plus me-1"></i> Add Banner
                                                </a>
                                                <a href="add-carousel.php" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-sliders-h me-1"></i> Add Carousel
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($masterItems as $item): 
                                    $itemType = $item['item_type'];
                                    $thumb = resolveAdminThumb($item['thumb'] ?? '');
                                    $mobileThumb = resolveAdminThumb($item['image_url_mobile'] ?? $item['mobile_image'] ?? $thumb);

                                    // Parse decoded other pages display JSON
                                    $itemOpd = [];
                                    if (!empty($item['other_pages_display'])) {
                                        $dec = json_decode($item['other_pages_display'], true);
                                        if (is_array($dec)) $itemOpd = $dec;
                                    }

                                    // Edit URL
                                    $editUrl = 'edit-banner.php?id=' . $item['id'];
                                    if ($itemType === 'carousel') {
                                        $editUrl = 'add-carousel.php?id=' . $item['id'];
                                    } elseif ($itemType === 'banner_carousel') {
                                        $editUrl = 'edit-banner-carousel.php?id=' . $item['id'];
                                    }
                                ?>
                                    <tr id="row-<?php echo $itemType; ?>-<?php echo $item['id']; ?>">
                                        <td class="text-center text-muted fw-bold">
                                            #<?php echo $item['id']; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="thumb-box" title="Desktop: <?php echo htmlspecialchars($item['alt_text'] ?: $item['title']); ?>">
                                                    <?php if (!empty($thumb)): ?>
                                                        <a href="<?php echo htmlspecialchars($thumb); ?>" target="_blank">
                                                            <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($item['alt_text'] ?? ''); ?>" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size:9px;">No Desk</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="thumb-box-mobile" title="Mobile preview">
                                                    <?php if (!empty($mobileThumb)): ?>
                                                        <a href="<?php echo htmlspecialchars($mobileThumb); ?>" target="_blank">
                                                            <img src="<?php echo htmlspecialchars($mobileThumb); ?>" alt="Mob" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size:8px;">No Mob</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($item['alt_text'])): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-light text-muted border text-truncate d-inline-block" style="max-width: 130px; font-size: 9px;" title="Alt: <?php echo htmlspecialchars($item['alt_text']); ?>">
                                                        <i class="fas fa-tag fa-xs me-1 text-primary"></i>Alt: <?php echo htmlspecialchars($item['alt_text']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark mb-1">
                                                <?php echo htmlspecialchars($item['title'] ?: 'Untitled Item'); ?>
                                            </div>
                                            <?php if (!empty($item['description'])): ?>
                                                <div class="text-muted small mb-1 text-truncate" style="max-width: 300px;">
                                                    <?php echo htmlspecialchars($item['description']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <?php if (!empty($item['meta_title'])): ?>
                                                    <span class="badge bg-light text-secondary border" style="font-size: 9px;" title="Meta Title: <?php echo htmlspecialchars($item['meta_title']); ?>">
                                                        <i class="fas fa-search fa-xs me-1 text-info"></i>SEO: <?php echo htmlspecialchars(mb_strimwidth($item['meta_title'], 0, 28, '...')); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['target_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($item['target_url']); ?>" target="_blank" class="text-primary small text-decoration-none">
                                                        <i class="fas fa-link fa-xs me-1"></i>Link
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($itemType === 'banner'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                                    <i class="fas fa-image me-1"></i> Banner
                                                </span>
                                            <?php elseif ($itemType === 'carousel'): ?>
                                                <span class="badge bg-purple-subtle text-purple border border-purple-subtle fw-bold" style="background:#f3e8ff; color:#7e22ce;">
                                                    <i class="fas fa-boxes me-1"></i> Carousel
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold">
                                                    <i class="fas fa-sliders-h me-1"></i> Slider
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['layout_format'])): ?>
                                                <div class="text-muted mt-1" style="font-size: 10px;">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', $item['layout_format'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $primaryP = $item['target_page'] ?? 'home';
                                            if (empty($primaryP)) $primaryP = 'home';
                                            ?>
                                            <span class="badge bg-light text-dark border fw-bold text-uppercase" style="font-size: 10px;">
                                                <?php echo htmlspecialchars($primaryP); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Interactive Multi-Page Clickable Badges -->
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <?php foreach ($otherPagesList as $opKey => $opMeta): 
                                                    $colKey = ($opKey === 'affiliate_program') ? 'show_on_affiliate' : 'show_on_' . $opKey;
                                                    $isOn = (!empty($item[$colKey]) && $item[$colKey] === 'yes') || in_array($opKey, $itemOpd);
                                                ?>
                                                    <span class="page-badge-pill <?php echo $isOn ? 'on' : 'off'; ?>"
                                                          data-type="<?php echo $itemType; ?>"
                                                          data-id="<?php echo $item['id']; ?>"
                                                          data-page="<?php echo $opKey; ?>"
                                                          data-is-on="<?php echo $isOn ? 'yes' : 'no'; ?>"
                                                          title="Click to toggle display on <?php echo htmlspecialchars($opMeta['label']); ?>"
                                                          onclick="togglePageVisibility(this)">
                                                        <i class="fas <?php echo $isOn ? 'fa-check-circle' : 'fa-plus-circle'; ?> me-1"></i><?php echo $opMeta['badge']; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="order-circle" title="Desktop Sort Order: <?php echo (float)($item['desktop_sort_order'] ?? 0); ?>">
                                                <?php echo round((float)($item['desktop_sort_order'] ?? 0), 1); ?>
                                            </span>
                                            <?php if (!empty($item['desktop_anchor_type'])): ?>
                                                <div class="text-muted mt-1" style="font-size: 9px;" title="Anchored relative to <?php echo htmlspecialchars($item['desktop_anchor_type']); ?>">
                                                    <?php echo ucfirst($item['desktop_anchor_position'] ?? 'after'); ?> <?php echo htmlspecialchars($item['desktop_anchor_type']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Master On/Off Switch -->
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input master-status-switch"
                                                       type="checkbox"
                                                       role="switch"
                                                       id="sw-<?php echo $itemType; ?>-<?php echo $item['id']; ?>"
                                                       data-type="<?php echo $itemType; ?>"
                                                       data-id="<?php echo $item['id']; ?>"
                                                       <?php echo ($item['status'] === 'active') ? 'checked' : ''; ?>
                                                       onchange="toggleMasterStatus(this)"
                                                       title="Turn On / Off">
                                            </div>
                                            <div class="status-label small text-muted" id="status-label-<?php echo $itemType; ?>-<?php echo $item['id']; ?>" style="font-size: 10px;">
                                                <?php echo ucfirst($item['status']); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo htmlspecialchars($editUrl); ?>" class="btn btn-outline-primary" title="Edit Item">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (($item['target_page'] ?? '') === 'about_us' || ($item['target_page'] ?? '') === 'about' || (!empty($item['show_on_about_us']) && $item['show_on_about_us'] === 'yes')): ?>
                                                    <a href="about-us-page.php#band-carousels" class="btn btn-outline-info" title="Manage in About Us Page Editor">
                                                        <i class="fas fa-info-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="view-banner.php?action=delete_item&item_type=<?php echo $itemType; ?>&id=<?php echo $item['id']; ?>"
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this <?php echo $itemType; ?> (#<?php echo $item['id']; ?>)?');"
                                                   title="Delete Item">
                                                    <i class="fas fa-trash-alt"></i>
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

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<!-- Toast Container for Live Feedback -->
<div class="toast-container-fixed" id="toastContainer"></div>

<script src="js/app.js"></script>
<script>
// Show toast notification
function showToast(message, isSuccess = true) {
    const container = document.getElementById('toastContainer');
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white ${isSuccess ? 'bg-success' : 'bg-danger'} border-0 show shadow-lg mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body small fw-semibold">
                <i class="fas ${isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toastEl);
    setTimeout(() => {
        toastEl.remove();
    }, 3500);
}

// Instant AJAX Toggle Master Status (Active / Inactive)
function toggleMasterStatus(switchEl) {
    const itemType = switchEl.dataset.type;
    const id = switchEl.dataset.id;
    const isChecked = switchEl.checked;
    const targetStatus = isChecked ? 'active' : 'inactive';
    const labelEl = document.getElementById(`status-label-${itemType}-${id}`);

    switchEl.disabled = true;

    const fd = new FormData();
    fd.append('action', 'ajax_toggle_status');
    fd.append('item_type', itemType);
    fd.append('id', id);
    fd.append('status', targetStatus);

    fetch('view-banner.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        switchEl.disabled = false;
        if (data.success) {
            if (labelEl) labelEl.textContent = isChecked ? 'Active' : 'Inactive';
            showToast(`${itemType.replace('_', ' ').toUpperCase()} #${id} is now ${targetStatus.toUpperCase()}`);
        } else {
            switchEl.checked = !isChecked; // Revert
            showToast(data.error || 'Failed to update status', false);
        }
    })
    .catch(err => {
        switchEl.disabled = false;
        switchEl.checked = !isChecked; // Revert
        showToast('Network error updating status', false);
    });
}

// Instant AJAX Toggle Show on Other Pages Checkbox / Badge
function togglePageVisibility(pillEl) {
    const itemType = pillEl.dataset.type;
    const id       = pillEl.dataset.id;
    const pageKey  = pillEl.dataset.page;
    const currentlyOn = pillEl.dataset.isOn === 'yes';
    const targetVal = currentlyOn ? 'no' : 'yes';

    pillEl.classList.add('loading');

    const fd = new FormData();
    fd.append('action', 'ajax_toggle_page');
    fd.append('item_type', itemType);
    fd.append('id', id);
    fd.append('page_key', pageKey);
    fd.append('val', targetVal);

    fetch('view-banner.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        pillEl.classList.remove('loading');
        if (data.success) {
            const newIsOn = (targetVal === 'yes');
            pillEl.dataset.isOn = targetVal;
            if (newIsOn) {
                pillEl.classList.remove('off');
                pillEl.classList.add('on');
                pillEl.querySelector('i').className = 'fas fa-check-circle me-1';
            } else {
                pillEl.classList.remove('on');
                pillEl.classList.add('off');
                pillEl.querySelector('i').className = 'fas fa-plus-circle me-1';
            }
            showToast(`${pageKey.toUpperCase()} display ${newIsOn ? 'ENABLED' : 'DISABLED'} for #${id}`);
        } else {
            showToast(data.error || 'Failed to update page visibility', false);
        }
    })
    .catch(err => {
        pillEl.classList.remove('loading');
        showToast('Network error updating page visibility', false);
    });
}
</script>
</body>
</html>