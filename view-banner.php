<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');
require_once(__DIR__ . '/includes/banner-helpers.php');

$bannerController = new BannerController();

// ----------------------------------------------------------------
// Action Handlers (Toggle Status, Delete Carousel)
// ----------------------------------------------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id'] ?? 0);

    if ($action === 'toggle_status' && $id > 0) {
        $targetStatus = ($_GET['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
        $res = $bannerController->toggleBannerStatus($id, $targetStatus);
        if ($res['success']) {
            $_SESSION['success_message'] = "Banner #{$id} status updated to " . ucfirst($targetStatus) . "!";
        } else {
            $_SESSION['error_message'] = $res['error'] ?? "Failed to update banner status.";
        }
        $pageParam = isset($_GET['page']) ? '?page=' . intval($_GET['page']) : '';
        header("Location: view-banner.php{$pageParam}");
        exit;
    }

    if ($action === 'toggle_carousel_status' && $id > 0) {
        $targetStatus = ($_GET['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
        $res = $bannerController->toggleBannerCarouselStatus($id, $targetStatus);
        if ($res['success']) {
            $_SESSION['success_message'] = "Banner Carousel #{$id} status updated to " . ucfirst($targetStatus) . "!";
        } else {
            $_SESSION['error_message'] = $res['error'] ?? "Failed to update carousel status.";
        }
        header("Location: view-banner.php?tab=carousels");
        exit;
    }

    if ($action === 'delete_carousel' && $id > 0) {
        $res = $bannerController->deleteBannerCarousel($id);
        if ($res['success']) {
            $_SESSION['success_message'] = "Banner Carousel #{$id} deleted successfully!";
        } else {
            $_SESSION['error_message'] = $res['error'] ?? "Failed to delete banner carousel.";
        }
        header("Location: view-banner.php?tab=carousels");
        exit;
    }
}

$tab           = $_GET['tab'] ?? 'banners'; // 'banners' or 'carousels'
$search        = trim($_GET['search'] ?? '');
$pageFilter    = trim($_GET['filter_page'] ?? '');
$sectionFilter = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$page          = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage       = 15;

$banners          = $bannerController->getBannersWithPagination($page, $perPage, $search, $pageFilter, $sectionFilter);
$totalBanners     = $bannerController->getBannersCount($search, $pageFilter, $sectionFilter);
$totalPages       = ceil($totalBanners / $perPage);
$stats            = $bannerController->getBannerStats();
$allSections      = $bannerController->getAllSections();
$allCarousels        = $bannerController->getAllBannerCarousels($tab === 'carousels' ? $pageFilter : '', $tab === 'carousels' ? $search : '', $tab === 'carousels' ? $sectionFilter : 0);
$totalCarouselsCount = count($bannerController->getAllBannerCarousels());
$deactivatedList     = $bannerController->getDeactivatedBanners();
$deactivatedCount    = count($deactivatedList);

// Extract unique pages from all active sections
$pages = array_values(array_unique(array_filter(array_column($allSections, 'page'))));
sort($pages);

$formatMap = [
    'desktop_format_1'     => ['label' => '1 single image (1920×600)', 'color' => '#2563eb', 'bg' => '#eff6ff', 'dims' => '1920×600'],
    'desktop_format_1_5'   => ['label' => '1.5 image peek (1200×500)', 'color' => '#4f46e5', 'bg' => '#e0e7ff', 'dims' => '1200×500'],
    'desktop_format_3'     => ['label' => '3 image design (600×400)', 'color' => '#db2777', 'bg' => '#fdf2f8', 'dims' => '600×400 (x3)'],
    'desktop_format_2_5'   => ['label' => '2.5 image peek (600×400)', 'color' => '#d97706', 'bg' => '#fef3c7', 'dims' => '600×400 (x2.5)'],
    'desktop_format_4'     => ['label' => '4 image design (450×320)', 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'dims' => '450×320 (x4)'],
    'desktop_format_small' => ['label' => '1 Small size strip (1920×220)', 'color' => '#059669', 'bg' => '#ecfdf5', 'dims' => '1920×220'],
    'desktop_format_2'     => ['label' => '1 single image (1920×600)', 'color' => '#2563eb', 'bg' => '#eff6ff', 'dims' => '1920×600'],
    'desktop_format_5'     => ['label' => '1 Small size strip (1920×220)', 'color' => '#059669', 'bg' => '#ecfdf5', 'dims' => '1920×220'],
    'desktop_format_6'     => ['label' => 'Format 6: 3x2 Grid (6)', 'color' => '#0891b2', 'bg' => '#ecfeff', 'dims' => '600×600 (x6)'],
    'mobile_format_1'      => ['label' => 'Mob 1: Single Slide', 'color' => '#2563eb', 'bg' => '#eff6ff', 'dims' => '600×600 / 800×450'],
    'mobile_format_1_5'    => ['label' => 'Mob 1.5: Peek Slide', 'color' => '#4f46e5', 'bg' => '#e0e7ff', 'dims' => '600×400'],
    'mobile_format_2'      => ['label' => 'Mob 2: Landscape 16:9', 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'dims' => '800×450'],
    'mobile_format_3'      => ['label' => 'Mob 3: 2 Columns', 'color' => '#db2777', 'bg' => '#fdf2f8', 'dims' => '600×600 (x2)'],
    'mobile_format_small'  => ['label' => 'Mob Small Strip', 'color' => '#059669', 'bg' => '#ecfdf5', 'dims' => '800×200'],
    'mobile_format_4'      => ['label' => 'Mob 4: 2x3 Grid (6)', 'color' => '#d97706', 'bg' => '#fffbeb', 'dims' => '600×600 (x6)'],
    'mobile_format_5'      => ['label' => 'Mob 5: Strip', 'color' => '#059669', 'bg' => '#ecfdf5', 'dims' => '750×180'],
    'mobile_format_6'      => ['label' => 'Mob 6: 3x2 Grid (6)', 'color' => '#0891b2', 'bg' => '#ecfeff', 'dims' => '500×500 (x6)'],
];

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
    <title>All Banners & Carousels | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .banner-thumb-container { display: flex; gap: 8px; align-items: center; }
        .banner-thumb-box { position: relative; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; transition: all .2s; }
        .banner-thumb-box:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.12); z-index: 2; }
        .banner-thumb { width: 70px; height: 48px; object-fit: cover; display: block; }
        .banner-thumb-mobile { width: 36px; height: 48px; object-fit: cover; display: block; }
        .thumb-tag { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: #fff; font-size: 8px; text-align: center; padding: 1px 0; font-weight: 700; text-transform: uppercase; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .status-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .status-active:hover { background: #bbf7d0; color: #14532d; }
        .status-inactive:hover { background: #fecaca; color: #991b1b; }
        
        .format-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; border: 1px solid transparent; }
        
        .page-badge-home     { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .page-badge-category { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .page-badge-product  { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .page-badge-blog     { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .page-badge-cart     { background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; }
        .page-badge-default  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .section-pill { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; }
        .slider-pill { background: #fef3c7; color: #92400e; font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 4px; }
        
        .stat-card-clickable { cursor: pointer; transition: transform .2s, box-shadow .2s; }
        .stat-card-clickable:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }

        .nav-tabs-custom { border-bottom: 2px solid #e2e8f0; }
        .nav-tabs-custom .nav-link { border: none; border-bottom: 2px solid transparent; color: #64748b; font-weight: 600; padding: 10px 18px; margin-bottom: -2px; }
        .nav-tabs-custom .nav-link.active { color: #2563eb; border-bottom-color: #2563eb; background: transparent; }
        
        .filter-nav-pills .nav-link { border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 500; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; margin-right: 6px; margin-bottom: 6px; transition: all .15s; }
        .filter-nav-pills .nav-link:hover { background: #e2e8f0; color: #1e293b; }
        .filter-nav-pills .nav-link.active { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600; box-shadow: 0 2px 6px rgba(37,99,235,0.3); }

        .table > tbody > tr > td { vertical-align: middle; padding: 12px 14px; }
        .order-badge { display: inline-block; width: 26px; height: 26px; line-height: 26px; text-align: center; border-radius: 50%; background: #f1f5f9; color: #334155; font-size: 11px; font-weight: 700; }
        .multi-img-stack { display: flex; align-items: center; }
        .multi-img-stack img { width: 34px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; margin-right: -10px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .multi-img-stack .more-count { width: 34px; height: 34px; border-radius: 4px; background: #334155; color: #fff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 1px solid #fff; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <!-- Header / Action Buttons -->
                <div class="row mb-3 align-items-center">
                    <div class="col-sm-6">
                        <h1 class="h3 mb-1 text-gray-800"><strong>Home Pages Banners</strong> &amp; Management</h1>
                        <p class="text-muted small mb-0">Manage single banners, grid layouts (Format 1-6), carousels &amp; responsive placements.</p>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                        <a href="deactivated-banners.php" class="btn btn-outline-danger btn-sm me-1">
                            <i class="fas fa-slash me-1"></i> Deactivated (<?php echo $deactivatedCount; ?>)
                        </a>
                        <a href="add-banner-carousel.php" class="btn btn-outline-primary btn-sm me-1">
                            <i class="fas fa-sliders-h me-1"></i> Add New Carousel
                        </a>
                        <a href="add-banner.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add New Banner
                        </a>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <a href="view-banner.php?tab=banners" class="text-decoration-none">
                            <div class="card stat-card-clickable border-0 shadow-sm mb-0 <?php echo ($tab === 'banners') ? 'border-primary' : ''; ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Single Banners</span>
                                            <h3 class="fw-bold mb-0 text-dark"><?php echo $stats['total'] ?? 0; ?></h3>
                                        </div>
                                        <div class="rounded-circle p-2" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:#dbeafe;color:#2563eb;">
                                            <i class="fas fa-images"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="view-banner.php?tab=carousels" class="text-decoration-none">
                            <div class="card stat-card-clickable border-0 shadow-sm mb-0 <?php echo ($tab === 'carousels') ? 'border-primary' : ''; ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Banner Carousels</span>
                                            <h3 class="fw-bold mb-0 text-primary"><?php echo $totalCarouselsCount; ?></h3>
                                        </div>
                                        <div class="rounded-circle p-2" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:#f3e8ff;color:#7e22ce;">
                                            <i class="fas fa-sliders-h"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Active Single Banners</span>
                                        <h3 class="fw-bold mb-0 text-success"><?php echo $stats['active'] ?? 0; ?></h3>
                                    </div>
                                    <div class="rounded-circle p-2" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:#dcfce7;color:#16a34a;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="deactivated-banners.php" class="text-decoration-none">
                            <div class="card stat-card-clickable border-0 shadow-sm mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-semibold" style="font-size:11px">Deactivated Banners</span>
                                            <h3 class="fw-bold mb-0 text-danger"><?php echo $deactivatedCount; ?></h3>
                                        </div>
                                        <div class="rounded-circle p-2" style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:#fee2e2;color:#dc2626;">
                                            <i class="fas fa-eye-slash"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Tabs: Banners vs Carousels -->
                <ul class="nav nav-tabs nav-tabs-custom mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab === 'banners') ? 'active' : ''; ?>" href="view-banner.php?tab=banners">
                            <i class="fas fa-images me-1"></i> Banners (<?php echo $totalBanners; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab === 'carousels') ? 'active' : ''; ?>" href="view-banner.php?tab=carousels">
                            <i class="fas fa-sliders-h me-1"></i> Banner Carousels (<?php echo $totalCarouselsCount; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="deactivated-banners.php">
                            <i class="fas fa-slash me-1"></i> Deactivated Banners (<?php echo $deactivatedCount; ?>)
                        </a>
                    </li>
                </ul>

                <?php if ($tab === 'banners'): ?>
                    <!-- Single Banners Table Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <!-- Page Filter Pills -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <ul class="nav filter-nav-pills mb-0">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo ($pageFilter === '') ? 'active' : ''; ?>"
                                           href="view-banner.php?tab=banners<?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                            All Pages <span class="badge bg-secondary ms-1"><?php echo $stats['total'] ?? 0; ?></span>
                                        </a>
                                    </li>
                                    <?php foreach ($pages as $pg): ?>
                                        <?php $pgCount = $stats['by_page'][$pg] ?? 0; ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($pageFilter === $pg) ? 'active' : ''; ?>"
                                               href="view-banner.php?tab=banners&filter_page=<?php echo urlencode($pg); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                                <?php echo ucfirst($pg); ?>
                                                <span class="badge <?php echo ($pageFilter === $pg) ? 'bg-light text-primary' : 'bg-secondary'; ?> ms-1">
                                                    <?php echo $pgCount; ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Search & Section Filter Bar -->
                            <form method="GET" class="row g-2 align-items-center">
                                <input type="hidden" name="tab" value="banners">
                                <?php if ($pageFilter !== ''): ?>
                                    <input type="hidden" name="filter_page" value="<?php echo htmlspecialchars($pageFilter); ?>">
                                <?php endif; ?>

                                <div class="col-md-4 col-sm-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0" name="search"
                                               placeholder="Search by title, URL, section..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-6">
                                    <select name="section_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="0">All Sections (<?php echo count($allSections); ?> available)</option>
                                        <?php foreach ($allSections as $sec): ?>
                                            <?php if ($pageFilter !== '' && $sec['page'] !== $pageFilter) continue; ?>
                                            <option value="<?php echo $sec['id']; ?>" <?php echo ($sectionFilter == $sec['id']) ? 'selected' : ''; ?>>
                                                [<?php echo strtoupper($sec['page']); ?>] <?php echo htmlspecialchars($sec['label'] ?? $sec['section_key']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 col-12 d-flex gap-2 justify-content-md-end">
                                    <button type="submit" class="btn btn-sm btn-primary px-3">
                                        <i class="fas fa-filter me-1"></i> Apply Filter
                                    </button>
                                    <?php if ($search !== '' || $pageFilter !== '' || $sectionFilter > 0): ?>
                                        <a href="view-banner.php?tab=banners" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase text-muted" style="font-size:11px;letter-spacing:0.5px">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">#</th>
                                            <th style="width: 140px;">Preview (Desk / Mob)</th>
                                            <th>Banner Details &amp; Design</th>
                                            <th>Placement &amp; Section</th>
                                            <th style="width: 70px;" class="text-center">Order</th>
                                            <th style="width: 110px;" class="text-center">Status</th>
                                            <th style="width: 130px;">Schedule</th>
                                            <th style="width: 110px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($banners)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="py-4">
                                                    <i class="fas fa-images text-muted fa-3x mb-3 opacity-50"></i>
                                                    <h5 class="text-muted fw-semibold">No Banners Found</h5>
                                                    <p class="text-muted small mb-3">No banners match the selected filter criteria.</p>
                                                    <a href="add-banner.php" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus me-1"></i> Add First Banner
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($banners as $b): 
                                            $bannerPage = $b['section_page'] ?? 'general';
                                            $deskDesign = $b['desktop_design'] ?? '';
                                            $mobDesign  = $b['mobile_design'] ?? '';
                                            $deskFmt    = $formatMap[$deskDesign] ?? null;

                                            // Multiple images decode
                                            $deskImages = !empty($b['desktop_images']) ? json_decode($b['desktop_images'], true) : [];
                                            $mobImages  = !empty($b['mobile_images']) ? json_decode($b['mobile_images'], true) : [];
                                            
                                            // Primary thumbnails
                                            $primaryDeskThumb = resolveAdminThumb(!empty($deskImages) ? $deskImages[0] : ($b['desktop_image'] ?? $b['image_url_desktop'] ?? ''));
                                            $primaryMobThumb  = resolveAdminThumb(!empty($mobImages) ? $mobImages[0] : ($b['mobile_image'] ?? $b['image_url_mobile'] ?? $primaryDeskThumb));
                                        ?>
                                            <tr>
                                                <td class="text-center text-muted fw-semibold" style="font-size:12px">
                                                    <?php echo $b['id']; ?>
                                                </td>
                                                <td>
                                                    <div class="banner-thumb-container">
                                                        <!-- Desktop Thumb -->
                                                        <div class="banner-thumb-box" title="Desktop Preview">
                                                            <?php if (!empty($primaryDeskThumb)): ?>
                                                                <a href="<?php echo htmlspecialchars($primaryDeskThumb); ?>" target="_blank">
                                                                    <img src="<?php echo htmlspecialchars($primaryDeskThumb); ?>" class="banner-thumb" alt="Desk" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                                </a>
                                                                <div class="thumb-tag"><?php echo count($deskImages) > 1 ? count($deskImages).' imgs' : 'Desk'; ?></div>
                                                            <?php else: ?>
                                                                <div class="banner-thumb d-flex align-items-center justify-content-center text-muted" style="font-size:9px">No Desk</div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Mobile Thumb -->
                                                        <div class="banner-thumb-box" title="Mobile Preview">
                                                            <?php if (!empty($primaryMobThumb)): ?>
                                                                <a href="<?php echo htmlspecialchars($primaryMobThumb); ?>" target="_blank">
                                                                    <img src="<?php echo htmlspecialchars($primaryMobThumb); ?>" class="banner-thumb-mobile" alt="Mob" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                                </a>
                                                                <div class="thumb-tag"><?php echo count($mobImages) > 1 ? count($mobImages).' imgs' : 'Mob'; ?></div>
                                                            <?php else: ?>
                                                                <div class="banner-thumb-mobile d-flex align-items-center justify-content-center text-muted" style="font-size:8px">No Mob</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['title'] ?: 'Untitled Banner'); ?></div>
                                                    <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                                        <?php if ($deskFmt): ?>
                                                            <span class="format-badge" style="background:<?php echo $deskFmt['bg']; ?>;color:<?php echo $deskFmt['color']; ?>;border-color:<?php echo $deskFmt['color']; ?>30;">
                                                                <i class="fas fa-desktop fa-xs"></i> <?php echo $deskFmt['label']; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($mobDesign && isset($formatMap[$mobDesign])): ?>
                                                            <span class="format-badge" style="background:<?php echo $formatMap[$mobDesign]['bg']; ?>;color:<?php echo $formatMap[$mobDesign]['color']; ?>;border-color:<?php echo $formatMap[$mobDesign]['color']; ?>30;">
                                                                <i class="fas fa-mobile-alt fa-xs"></i> <?php echo $formatMap[$mobDesign]['label']; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($b['target_url'])): ?>
                                                        <div class="mt-1">
                                                            <a href="<?php echo htmlspecialchars($b['target_url']); ?>" target="_blank" class="text-primary small text-decoration-none">
                                                                <i class="fas fa-link fa-xs me-1"></i><?php echo htmlspecialchars(mb_strimwidth($b['target_url'], 0, 35, '...')); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $badgeClass = 'page-badge-default';
                                                    if ($bannerPage === 'home')     $badgeClass = 'page-badge-home';
                                                    elseif ($bannerPage === 'category') $badgeClass = 'page-badge-category';
                                                    elseif ($bannerPage === 'product')  $badgeClass = 'page-badge-product';
                                                    elseif ($bannerPage === 'blog')     $badgeClass = 'page-badge-blog';
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase mb-1" style="font-size:10px;font-weight:600;">
                                                        <?php echo htmlspecialchars($bannerPage); ?>
                                                    </span>
                                                    <div class="section-pill mt-1">
                                                        <i class="fas fa-layer-group fa-xs text-muted me-1"></i>
                                                        <?php echo htmlspecialchars($b['section_label'] ?? $b['section_key'] ?? 'Unassigned Section'); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="order-badge"><?php echo $b['display_order']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($b['status'] === 'active'): ?>
                                                        <a href="view-banner.php?action=toggle_status&id=<?php echo $b['id']; ?>&status=inactive&page=<?php echo $page; ?>"
                                                           class="status-badge status-active" title="Click to Deactivate & Hide">
                                                            <i class="fas fa-check-circle"></i> Active
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="view-banner.php?action=toggle_status&id=<?php echo $b['id']; ?>&status=active&page=<?php echo $page; ?>"
                                                           class="status-badge status-inactive" title="Click to Activate & Show">
                                                            <i class="fas fa-times-circle"></i> Inactive
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <?php if (!empty($b['start_date']) || !empty($b['end_date'])): ?>
                                                            <?php if (!empty($b['start_date'])): ?>
                                                                <div class="text-muted"><i class="fas fa-calendar-alt fa-xs me-1 text-success"></i> <?php echo date('d M Y', strtotime($b['start_date'])); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($b['end_date'])): ?>
                                                                <div class="text-muted"><i class="fas fa-calendar-times fa-xs me-1 text-danger"></i> <?php echo date('d M Y', strtotime($b['end_date'])); ?></div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">— Always active —</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="edit-banner.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-primary" title="Edit Banner">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete-banner.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete banner &quot;<?php echo htmlspecialchars(addslashes($b['title'])); ?>&quot;?');"
                                                           title="Delete Banner">
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

                            <!-- Pagination -->
                            <?php if ($totalBanners > 0 && $totalPages > 1): ?>
                                <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-3 border-top bg-light-subtle">
                                    <div class="text-muted small">
                                        Showing <strong><?php echo count($banners); ?></strong> of <strong><?php echo $totalBanners; ?></strong> banners
                                    </div>
                                    <nav aria-label="Banner pagination">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?tab=banners&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter_page=<?php echo urlencode($pageFilter); ?>&section_id=<?php echo $sectionFilter; ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?tab=banners&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_page=<?php echo urlencode($pageFilter); ?>&section_id=<?php echo $sectionFilter; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php elseif (abs($i - $page) == 3): ?>
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?tab=banners&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter_page=<?php echo urlencode($pageFilter); ?>&section_id=<?php echo $sectionFilter; ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Carousels Table Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <h5 class="card-title mb-0">Banner Carousels</h5>
                                    <p class="text-muted small mb-0">Interactive rotating banner sliders configured with Format 1-6 designs.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="blog-page.php" class="btn btn-outline-warning btn-sm" title="Edit Blog Page Sections directly">
                                        <i class="fas fa-blog me-1"></i> Blog Page Editor
                                    </a>
                                    <a href="add-banner-carousel.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Add New Banner Carousel
                                    </a>
                                </div>
                            </div>

                            <!-- Page Filter Pills for Carousels -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <ul class="nav filter-nav-pills mb-0">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo ($pageFilter === '') ? 'active' : ''; ?>"
                                           href="view-banner.php?tab=carousels<?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                            All Pages <span class="badge bg-secondary ms-1"><?php echo $totalCarouselsCount; ?></span>
                                        </a>
                                    </li>
                                    <?php foreach ($pages as $pg): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($pageFilter === $pg) ? 'active' : ''; ?>"
                                               href="view-banner.php?tab=carousels&filter_page=<?php echo urlencode($pg); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                                <?php echo ucfirst($pg); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Search & Section Filter Bar for Carousels -->
                            <form method="GET" class="row g-2 align-items-center">
                                <input type="hidden" name="tab" value="carousels">
                                <?php if ($pageFilter !== ''): ?>
                                    <input type="hidden" name="filter_page" value="<?php echo htmlspecialchars($pageFilter); ?>">
                                <?php endif; ?>

                                <div class="col-md-4 col-sm-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0" name="search"
                                               placeholder="Search carousels by title..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4 col-sm-6">
                                    <select name="section_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="0">All Sections (<?php echo count($allSections); ?> available)</option>
                                        <?php foreach ($allSections as $sec): ?>
                                            <?php if ($pageFilter !== '' && $sec['page'] !== $pageFilter) continue; ?>
                                            <option value="<?php echo $sec['id']; ?>" <?php echo ($sectionFilter == $sec['id']) ? 'selected' : ''; ?>>
                                                [<?php echo strtoupper($sec['page']); ?>] <?php echo htmlspecialchars($sec['label'] ?? $sec['section_key']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 col-12 d-flex gap-2 justify-content-md-end">
                                    <button type="submit" class="btn btn-sm btn-primary px-3">
                                        <i class="fas fa-filter me-1"></i> Apply Filter
                                    </button>
                                    <?php if ($search !== '' || $pageFilter !== '' || $sectionFilter > 0): ?>
                                        <a href="view-banner.php?tab=carousels" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase text-muted" style="font-size:11px;letter-spacing:0.5px">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">#</th>
                                            <th style="width: 140px;">Slide Preview</th>
                                            <th>Carousel Title &amp; Format</th>
                                            <th>Placement &amp; Category</th>
                                            <th style="width: 70px;" class="text-center">Order</th>
                                            <th style="width: 110px;" class="text-center">Status</th>
                                            <th style="width: 130px;">Schedule</th>
                                            <th style="width: 110px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($allCarousels)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="py-4">
                                                    <i class="fas fa-sliders-h text-muted fa-3x mb-3 opacity-50"></i>
                                                    <h5 class="text-muted fw-semibold">No Banner Carousels Yet</h5>
                                                    <p class="text-muted small mb-3">Create rotating carousels with single slides or 2-grid &amp; 6-grid formats.</p>
                                                    <a href="add-banner-carousel.php" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus me-1"></i> Add First Carousel
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allCarousels as $c): 
                                            $deskFmt = $formatMap[$c['desktop_design'] ?? ''] ?? null;
                                            $deskImgs = !empty($c['desktop_images']) ? json_decode($c['desktop_images'], true) : [];
                                            $primaryThumb = resolveAdminThumb(!empty($deskImgs) ? $deskImgs[0] : ($c['desktop_image'] ?? ''));
                                            $cPage = $c['desktop_page'] ?? $c['section_page'] ?? 'home';
                                            $cBadgeClass = 'page-badge-default';
                                            if ($cPage === 'home')          $cBadgeClass = 'page-badge-home';
                                            elseif ($cPage === 'category')  $cBadgeClass = 'page-badge-category';
                                            elseif ($cPage === 'product')   $cBadgeClass = 'page-badge-product';
                                            elseif ($cPage === 'blog')      $cBadgeClass = 'page-badge-blog';
                                            elseif ($cPage === 'cart')      $cBadgeClass = 'page-badge-cart';
                                        ?>
                                            <tr>
                                                <td class="text-center text-muted fw-semibold" style="font-size:12px">
                                                    <?php echo $c['id']; ?>
                                                </td>
                                                <td>
                                                    <div class="banner-thumb-container">
                                                        <div class="banner-thumb-box" title="Carousel Slide Preview">
                                                            <?php if (!empty($primaryThumb)): ?>
                                                                <a href="<?php echo htmlspecialchars($primaryThumb); ?>" target="_blank">
                                                                    <img src="<?php echo htmlspecialchars($primaryThumb); ?>" class="banner-thumb" alt="Carousel" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                                </a>
                                                                <div class="thumb-tag"><?php echo count($deskImgs) > 1 ? count($deskImgs).' slides' : 'Slide'; ?></div>
                                                            <?php else: ?>
                                                                <div class="banner-thumb d-flex align-items-center justify-content-center text-muted" style="font-size:9px">No Slide</div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (count($deskImgs) > 1): ?>
                                                            <div class="multi-img-stack ms-1" title="<?php echo count($deskImgs); ?> total slides">
                                                                <?php foreach (array_slice($deskImgs, 1, 3) as $extraThumb): 
                                                                    $resolvedExtra = resolveAdminThumb($extraThumb);
                                                                ?>
                                                                    <img src="<?php echo htmlspecialchars($resolvedExtra); ?>" title="Slide" alt="Slide" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                                <?php endforeach; ?>
                                                                <?php if (count($deskImgs) > 4): ?>
                                                                    <span class="more-count">+<?php echo count($deskImgs) - 4; ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['title']); ?></div>
                                                    <div class="d-flex flex-wrap gap-1 mt-1 align-items-center">
                                                        <?php if ($deskFmt): ?>
                                                            <span class="format-badge" style="background:<?php echo $deskFmt['bg']; ?>;color:<?php echo $deskFmt['color']; ?>;border-color:<?php echo $deskFmt['color']; ?>30;">
                                                                <i class="fas fa-desktop fa-xs"></i> <?php echo $deskFmt['label']; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (($c['button_arrows'] ?? 'enable') === 'disable'): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:9px;" title="Left/Right navigation arrows disabled">
                                                                <i class="fas fa-ban me-1"></i>Arrows Off
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:9px;" title="Left/Right navigation arrows enabled">
                                                                <i class="fas fa-arrows-alt-h me-1"></i>Arrows On
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (($c['auto_running'] ?? 'enable') === 'disable'): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:9px;" title="Automatic rotation disabled">
                                                                <i class="fas fa-pause me-1"></i>Auto Off
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:9px;" title="Auto-run enabled (<?php echo (int)($c['auto_run_speed'] ?? 3000); ?>ms)">
                                                                <i class="fas fa-play me-1"></i>Auto (<?php echo round(((int)($c['auto_run_speed'] ?? 3000))/1000, 1); ?>s)
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $cBadgeClass; ?> text-uppercase mb-1" style="font-size:10px;">
                                                        <i class="fas fa-file-alt me-1"></i><?php echo htmlspecialchars($cPage); ?>
                                                    </span>
                                                    <?php if (!empty($c['desktop_category_name'])): ?>
                                                        <div class="small text-muted"><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($c['desktop_category_name']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($c['carousel_label'])): ?>
                                                        <div class="section-pill mt-1">
                                                            <i class="fas fa-sliders-h fa-xs text-muted me-1"></i> <?php echo htmlspecialchars($c['carousel_label']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="order-badge"><?php echo $c['desktop_sort_order']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($c['status'] === 'active'): ?>
                                                        <a href="view-banner.php?action=toggle_carousel_status&id=<?php echo $c['id']; ?>&status=inactive"
                                                           class="status-badge status-active" title="Click to Deactivate Carousel">
                                                            <i class="fas fa-check-circle"></i> Active
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="view-banner.php?action=toggle_carousel_status&id=<?php echo $c['id']; ?>&status=active"
                                                           class="status-badge status-inactive" title="Click to Activate Carousel">
                                                            <i class="fas fa-times-circle"></i> Inactive
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small text-muted">
                                                        <?php if (!empty($c['desktop_start_date'])): ?>
                                                            <div><i class="fas fa-calendar-alt fa-xs me-1 text-success"></i> <?php echo date('d M Y', strtotime($c['desktop_start_date'])); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($c['desktop_end_date'])): ?>
                                                            <div><i class="fas fa-calendar-times fa-xs me-1 text-danger"></i> <?php echo date('d M Y', strtotime($c['desktop_end_date'])); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (empty($c['desktop_start_date']) && empty($c['desktop_end_date'])): ?>
                                                            — Always active —
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="edit-banner-carousel.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-primary" title="Edit Carousel">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="view-banner.php?action=delete_carousel&id=<?php echo $c['id']; ?>" class="btn btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete carousel &quot;<?php echo htmlspecialchars(addslashes($c['title'])); ?>&quot;?');"
                                                           title="Delete Carousel">
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
                <?php endif; ?>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
