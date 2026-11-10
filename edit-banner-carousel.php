<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/constants.php');
require_once(__DIR__ . '/includes/banner-helpers.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$database = new Database();
$db       = $database->getConnection();

$bannerController = new BannerController();
$categories       = $bannerController->getAllCategories();
$carousels        = $bannerController->getAllCarousels();

$carousel = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $carousel = $bannerController->getBannerCarouselById((int)$_GET['id']);
    if (!$carousel) {
        $_SESSION['error_message'] = 'Banner carousel not found.';
        header('Location: banner-carousels.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = 'Invalid carousel ID.';
    header('Location: banner-carousels.php');
    exit;
}

$subtype = $carousel['slider_subtype'] ?? 'top_slider';
if (!in_array($subtype, ['top_slider', 'small_slider', 'offer_slider', 'service_slider', 'product_detail_slider', 'dashboard_slider'])) {
    $subtype = 'top_slider';
}

$subtypeTitles = [
    'top_slider'            => '1. Top Banner Slider',
    'small_slider'          => '2. Banner Small Slider',
    'offer_slider'          => '3. Banner Offer Slider',
    'service_slider'        => '4. Category Service Slider',
    'product_detail_slider' => '5. Product Details Pages Banner Slider',
    'dashboard_slider'      => '6. User Dashboard Banner Slider',
];

// Load Anchor Section Options for Before/After Relative Positioning
$anchorSectionTypes = ANCHOR_SECTION_TYPES();
$anchorOptionsByDevice = [
    'desktop' => [],
    'mobile'  => [],
];
foreach (array_keys($anchorSectionTypes) as $anchorType) {
    $anchorOptionsByDevice['desktop'][$anchorType] = getAnchorSectionOptions($db, $anchorType, 'desktop');
    $anchorOptionsByDevice['mobile'][$anchorType]  = getAnchorSectionOptions($db, $anchorType, 'mobile');
}

// Load products for "Home pages product select / extra product"
$extraProductsRes = $db->query("SELECT id, name, sku FROM products WHERE status = 'active' ORDER BY name ASC LIMIT 100");
$productList = [];
if ($extraProductsRes) {
    while ($p = $extraProductsRes->fetch_assoc()) $productList[] = $p;
}

$error_message   = '';
$success_message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $result = $bannerController->updateBannerCarousel((int)$_GET['id'], $_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: banner-carousels.php');
        exit;
    } else {
        $error_message = $result['error'];
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);

$desktopDesign = $carousel['desktop_design'] ?: 'desktop_format_1';
if ($desktopDesign === 'desktop_format_2') {
    $desktopDesign = 'desktop_format_1';
} elseif ($desktopDesign === 'desktop_format_5') {
    $desktopDesign = 'desktop_format_small';
}
$mobileDesign  = $carousel['mobile_design'] ?: 'mobile_format_1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Edit <?php echo htmlspecialchars($subtypeTitles[$subtype]); ?>: <?php echo htmlspecialchars($carousel['title']); ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .required-field::after { content: " *"; color: #dc3545; font-weight: bold; }
        
        .admin-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .admin-card-header {
            padding: 16px 20px;
            background: #fafbfc;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .admin-card-header h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-card-body { padding: 24px 20px; }

        .design-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .design-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .design-card:hover {
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.08);
        }
        .design-card.active {
            border-color: #0b53a1;
            background: #f0f7ff;
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.15);
        }
        .design-card .check-indicator {
            position: absolute;
            top: 8px; right: 8px;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        .design-card.active .check-indicator { background: #0b53a1; }

        .wireframe-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;
            min-height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .design-card.active .wireframe-box { border-color: #0b53a1; background: #ffffff; }

        .wf-format-1 { width: 100%; height: 46px; background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #1d4ed8; font-weight: 700; }
        .wf-format-1-5 { display: flex; gap: 6px; width: 100%; height: 46px; align-items: stretch; }
        .wf-format-1-5 .wf-main-slide { flex: 1 1 70%; background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #1d4ed8; font-weight: 700; }
        .wf-format-1-5 .wf-peek-slide { width: 28%; background: #e0e7ff; border: 1.5px dashed #6366f1; border-right: none; border-radius: 6px 0 0 6px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #4f46e5; font-weight: 700; }
        .wf-format-3 { display: flex; gap: 4px; width: 100%; height: 46px; align-items: stretch; }
        .wf-format-3 .wf-card { flex: 1; background: #fdf2f8; border: 1.5px solid #ec4899; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #be185d; font-weight: 700; }
        .wf-format-2-5 { display: flex; gap: 4px; width: 100%; height: 46px; align-items: stretch; }
        .wf-format-2-5 .wf-card { flex: 1; background: #fffbeb; border: 1.5px solid #f59e0b; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #b45309; font-weight: 700; }
        .wf-format-2-5 .wf-peek-slide { width: 20%; background: #fef3c7; border: 1.5px dashed #d97706; border-right: none; border-radius: 5px 0 0 5px; display: flex; align-items: center; justify-content: center; font-size: 7px; color: #b45309; font-weight: 700; }
        .wf-format-4 { display: flex; gap: 3px; width: 100%; height: 46px; align-items: stretch; }
        .wf-format-4 .wf-card { flex: 1; background: #f5f3ff; border: 1.5px solid #8b5cf6; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #6d28d9; font-weight: 700; }
        .wf-format-small { width: 100%; height: 24px; background: #ecfdf5; border: 1.5px solid #10b981; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #047857; font-weight: 700; }

        .wf-single-square { width: 50px; height: 50px; background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #1d4ed8; margin: 0 auto; }
        .wf-full-rect { width: 90px; height: 42px; background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #1d4ed8; margin: 0 auto; }
        .wf-two-col { display: flex; gap: 4px; width: 90px; margin: 0 auto; }
        .wf-two-col span { flex: 1; height: 44px; background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 4px; font-size: 7px; color: #1d4ed8; display: flex; align-items: center; justify-content: center; }
        .wf-grid-2x3 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; width: 80px; margin: 0 auto; }
        .wf-grid-2x3 span { height: 20px; background: #eff6ff; border: 1px solid #3b82f6; border-radius: 2px; font-size: 6px; color: #1d4ed8; display: flex; align-items: center; justify-content: center; }
        .wf-slim-strip { width: 100%; height: 20px; background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 3px; display: flex; align-items: center; justify-content: center; font-size: 7px; color: #1d4ed8; }
        .wf-grid-3x2 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; width: 90px; margin: 0 auto; }
        .wf-grid-3x2 span { height: 22px; background: #eff6ff; border: 1px solid #3b82f6; border-radius: 2px; font-size: 6px; color: #1d4ed8; display: flex; align-items: center; justify-content: center; }

        .design-title { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .design-badge { font-size: 11px; color: #64748b; font-weight: 500; }

        .control-pill-box {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            user-select: none;
        }
        .control-pill-box:hover {
            border-color: #93c5fd;
            background: #f8fafc;
        }

        .dimension-info-pill {
            background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af;
            border-radius: 8px; padding: 10px 14px; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
        }

        .upload-dropzone {
            border: 2px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center;
            background: #f8fafc; transition: all 0.2s; cursor: pointer; position: relative;
        }
        .upload-dropzone:hover { border-color: #0b53a1; background: #f0f7ff; }
        .upload-dropzone input[type="file"] {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
        .upload-preview-img {
            max-width: 100%; max-height: 180px; border-radius: 6px; object-fit: contain;
            margin-top: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: block;
        }

        .multi-slot-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;
        }
        .slot-box {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;
        }

        .device-nav-pills {
            display: flex; gap: 10px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px;
        }
        .device-pill-btn {
            background: transparent; border: none; padding: 10px 20px; font-weight: 600;
            font-size: 14px; color: #64748b; border-bottom: 3px solid transparent;
            margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .device-pill-btn.active { color: #0b53a1; border-bottom-color: #0b53a1; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <!-- Header Actions -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-edit text-primary me-2"></i>Edit <?php echo htmlspecialchars($subtypeTitles[$subtype]); ?>
                        </h3>
                        <p class="text-muted small mb-0">Update layout, slide images, placement anchors, and responsive display rules.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <a href="banner-carousels.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Banner Sliders
                        </a>
                        <a href="add-banner-carousel.php?type=<?php echo urlencode($subtype); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> Add Another Slider
                        </a>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="editCarouselForm">
                    <input type="hidden" name="slider_subtype" value="<?php echo htmlspecialchars($subtype); ?>">

                    <!-- Card 1: Title & Scope -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5><i class="fas fa-heading text-primary"></i> 1. Banner Slider Scope &amp; Title Name</h5>
                            <span class="badge bg-primary text-white fw-bold px-3 py-1"><?php echo htmlspecialchars($subtypeTitles[$subtype]); ?></span>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required-field fw-semibold">Banner Slider Title Name</label>
                                    <input type="text" class="form-control form-control-lg" name="title" required
                                           value="<?php echo htmlspecialchars($carousel['title']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required-field fw-semibold">Primary Target Page</label>
                                    <?php $bstp = $carousel['target_page'] ?? 'home'; ?>
                                    <select name="target_page" class="form-select form-select-lg">
                                        <option value="home" <?php echo ($bstp === 'home' || empty($bstp)) ? 'selected' : ''; ?>>Home Page (Main Storefront)</option>
                                        <option value="category" <?php echo ($bstp === 'category') ? 'selected' : ''; ?>>Category Page</option>
                                        <option value="product" <?php echo ($bstp === 'product') ? 'selected' : ''; ?>>Product Page</option>
                                        <option value="product_detail" <?php echo ($bstp === 'product_detail') ? 'selected' : ''; ?>>Product Detail Page</option>
                                        <option value="franchise" <?php echo ($bstp === 'franchise') ? 'selected' : ''; ?>>Franchise Page</option>
                                        <option value="affiliate_program" <?php echo ($bstp === 'affiliate_program') ? 'selected' : ''; ?>>Affiliate Program Page</option>
                                        <option value="career" <?php echo ($bstp === 'career') ? 'selected' : ''; ?>>Career Page</option>
                                        <option value="business_solutions" <?php echo ($bstp === 'business_solutions') ? 'selected' : ''; ?>>Business Solutions Page</option>
                                        <option value="advertising" <?php echo ($bstp === 'advertising') ? 'selected' : ''; ?>>Advertising Page</option>
                                        <option value="about_us" <?php echo ($bstp === 'about_us' || $bstp === 'about') ? 'selected' : ''; ?>>About Us Page</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Show in Text Tag Name (optional)</label>
                                    <input type="text" class="form-control form-control-lg" name="text_tag_name"
                                           value="<?php echo htmlspecialchars($carousel['text_tag_name'] ?? ''); ?>"
                                           placeholder="e.g. Trending, Best Deal, Hot">
                                    <small class="text-muted">Shown on banner sliders requiring tag labels.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Image Alt Text (SEO &amp; Accessibility)</label>
                                    <input type="text" class="form-control" name="alt_text" value="<?php echo htmlspecialchars($carousel['alt_text'] ?? ''); ?>" placeholder="e.g. Hero Offers &amp; Festive Banner Slider">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Slider Description / Notes</label>
                                    <input type="text" class="form-control" name="description" value="<?php echo htmlspecialchars($carousel['description'] ?? ''); ?>" placeholder="Brief notes or promotional text...">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Home pages product select (Extra Featured Product link - optional)</label>
                                    <select name="extra_product_id" class="form-select">
                                        <option value="">— Select an Extra Product to anchor with —</option>
                                        <?php foreach ($productList as $p): ?>
                                            <option value="<?php echo (int)$p['id']; ?>" <?php echo (($carousel['extra_product_id'] ?? 0) == $p['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['name'] . (!empty($p['sku']) ? ' (' . $p['sku'] . ')' : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Page Visibility Card: Show on Other Pages -->
                    <?php
                    $bsopd = [];
                    if (!empty($carousel['other_pages_display'])) {
                        $decoded = json_decode($carousel['other_pages_display'], true);
                        if (is_array($decoded)) $bsopd = $decoded;
                    }
                    ?>
                    <div class="admin-card border-primary-subtle">
                        <div class="admin-card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-layer-group text-primary me-2"></i> Also Show on Other Pages (Multi-Page Display)</h5>
                                <small class="text-muted">Client Requirement: Use checkboxes to easily show this banner slider on other storefront pages.</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1">8 Specific Pages</span>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_category" id="ebsChkCat" value="yes" <?php echo (($carousel['show_on_category'] ?? '') === 'yes' || in_array('category', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkCat">
                                                <i class="fas fa-th-large text-primary me-1"></i> Category Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Shop category pages</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_product" id="ebsChkProd" value="yes" <?php echo (($carousel['show_on_product'] ?? '') === 'yes' || in_array('product', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkProd">
                                                <i class="fas fa-box text-success me-1"></i> Product Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">All products listing</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_product_detail" id="ebsChkPDet" value="yes" <?php echo (($carousel['show_on_product_detail'] ?? '') === 'yes' || in_array('product_detail', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkPDet">
                                                <i class="fas fa-tag text-info me-1"></i> Product Detail
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Product details page</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_franchise" id="ebsChkFran" value="yes" <?php echo (($carousel['show_on_franchise'] ?? '') === 'yes' || in_array('franchise', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkFran">
                                                <i class="fas fa-store text-warning me-1"></i> Franchise Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Franchise portal</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_affiliate" id="ebsChkAff" value="yes" <?php echo (($carousel['show_on_affiliate'] ?? '') === 'yes' || in_array('affiliate_program', $bsopd) || in_array('affiliate', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkAff">
                                                <i class="fas fa-handshake text-purple me-1"></i> Affiliate Program
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Affiliate landing</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_career" id="ebsChkCar" value="yes" <?php echo (($carousel['show_on_career'] ?? '') === 'yes' || in_array('career', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkCar">
                                                <i class="fas fa-user-tie text-danger me-1"></i> Career Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Jobs &amp; careers</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_business_solutions" id="ebsChkBiz" value="yes" <?php echo (($carousel['show_on_business_solutions'] ?? '') === 'yes' || in_array('business_solutions', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkBiz">
                                                <i class="fas fa-briefcase text-primary me-1"></i> Business Solutions
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Corporate gifting / B2B</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_advertising" id="ebsChkAdv" value="yes" <?php echo (($carousel['show_on_advertising'] ?? '') === 'yes' || in_array('advertising', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkAdv">
                                                <i class="fas fa-bullhorn text-secondary me-1"></i> Advertising Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Brand ads &amp; promo</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_about_us" id="ebsChkAbout" value="yes" <?php echo (($carousel['show_on_about_us'] ?? '') === 'yes' || in_array('about_us', $bsopd)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-1" for="ebsChkAbout">
                                                <i class="fas fa-info-circle text-info me-1"></i> About Us Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">About Printmont page</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Card -->
                    <div class="admin-card">
                        <div class="admin-card-header bg-light">
                            <h5><i class="fas fa-search text-primary me-2"></i> SEO Meta Tags &amp; Search Optimization</h5>
                            <span class="badge bg-secondary text-white fw-semibold px-2 py-1">Optional SEO</span>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Meta Title</label>
                                    <input type="text" class="form-control" name="meta_title" value="<?php echo htmlspecialchars($carousel['meta_title'] ?? ''); ?>" placeholder="e.g. Custom Corporate Gifts &amp; Premium Printing">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Meta Keywords</label>
                                    <input type="text" class="form-control" name="meta_keywords" value="<?php echo htmlspecialchars($carousel['meta_keywords'] ?? ''); ?>" placeholder="gifts, corporate, printing, bulk order">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Meta Description</label>
                                    <textarea class="form-control" name="meta_description" rows="2" placeholder="Brief SEO description snippet..."><?php echo htmlspecialchars($carousel['meta_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device Navigation Tabs -->
                    <div class="device-nav-pills">
                        <button type="button" class="device-pill-btn active" id="tabBtnDesktop" onclick="switchDeviceTab('desktop')">
                            <i class="fas fa-desktop"></i> Desktop Banner Slider
                        </button>
                        <button type="button" class="device-pill-btn" id="tabBtnMobile" onclick="switchDeviceTab('mobile')">
                            <i class="fas fa-mobile-alt"></i> Mobile Banner Slider
                        </button>
                    </div>

                    <!-- ======================================================== -->
                    <!-- 🖥️ DESKTOP CAROUSEL SECTION                              -->
                    <!-- ======================================================== -->
                    <div id="desktopSectionPane">
                        
                        <!-- 1. Select Desktop Design -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-primary"></i> Desktop Banner Slider Select Design</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="design-cards-grid">
                                    <!-- Format 1 -->
                                    <div class="design-card <?php echo ($desktopDesign === 'desktop_format_1') ? 'active' : ''; ?>" data-cformat="desktop_format_1" onclick="selectDesktopCarouselDesign('desktop_format_1')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box"><div class="wf-format-1">1 Full Width Slide</div></div>
                                        <div class="design-title">1 single image format design</div>
                                        <div class="design-badge">1 Full-Width Landscape Card</div>
                                    </div>

                                    <!-- Format 2 -->
                                    <div class="design-card <?php echo ($desktopDesign === 'desktop_format_1_5') ? 'active' : ''; ?>" data-cformat="desktop_format_1_5" onclick="selectDesktopCarouselDesign('desktop_format_1_5')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-format-1-5">
                                                <div class="wf-main-slide">1 Card</div>
                                                <div class="wf-peek-slide">0.5</div>
                                            </div>
                                        </div>
                                        <div class="design-title">1.5 image format design</div>
                                        <div class="design-badge">1 Card + 0.5 Peek Card</div>
                                    </div>

                                    <!-- Format 3 -->
                                    <div class="design-card <?php echo ($desktopDesign === 'desktop_format_3') ? 'active' : ''; ?>" data-cformat="desktop_format_3" onclick="selectDesktopCarouselDesign('desktop_format_3')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-format-3">
                                                <div class="wf-card">1</div><div class="wf-card">2</div><div class="wf-card">3</div>
                                            </div>
                                        </div>
                                        <div class="design-title">3 image format design</div>
                                        <div class="design-badge">3 Cards Side-by-Side</div>
                                    </div>

                                    <!-- Format 4 -->
                                    <div class="design-card <?php echo ($desktopDesign === 'desktop_format_2_5') ? 'active' : ''; ?>" data-cformat="desktop_format_2_5" onclick="selectDesktopCarouselDesign('desktop_format_2_5')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-format-2-5">
                                                <div class="wf-card">1</div><div class="wf-card">2</div><div class="wf-peek-slide">0.5</div>
                                            </div>
                                        </div>
                                        <div class="design-title">2.5 image format design</div>
                                        <div class="design-badge">2 Cards + 0.5 Peek Card</div>
                                    </div>

                                    <!-- Format 5 -->
                                    <div class="design-card <?php echo ($desktopDesign === 'desktop_format_4') ? 'active' : ''; ?>" data-cformat="desktop_format_4" onclick="selectDesktopCarouselDesign('desktop_format_4')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-format-4">
                                                <div class="wf-card">1</div><div class="wf-card">2</div><div class="wf-card">3</div><div class="wf-card">4</div>
                                            </div>
                                        </div>
                                        <div class="design-title">4 image format design</div>
                                        <div class="design-badge">4 Cards Side-by-Side</div>
                                    </div>

                                    <!-- Format 6 -->
                                    <div class="design-card <?php echo ($desktopDesign === 'desktop_format_small') ? 'active' : ''; ?>" data-cformat="desktop_format_small" onclick="selectDesktopCarouselDesign('desktop_format_small')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box"><div class="wf-format-small">1 Small Strip Banner</div></div>
                                        <div class="design-title">1 Small size format design</div>
                                        <div class="design-badge">Small Height Strip Slider</div>
                                    </div>
                                </div>

                                <input type="hidden" name="desktop_design" id="desktopCarouselDesignInput" value="<?php echo htmlspecialchars($desktopDesign); ?>">
                            </div>
                        </div>

                        <!-- 2. Desktop Controls & Placement -->
                        <div id="desktopCarouselFields">
                            
                            <div class="dimension-info-pill" id="desktopCarouselDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="desktopCarouselDimText">Recommended Size: 1920×600 px / 1200×450 px</span>
                            </div>

                            <!-- Carousel Navigation Buttons & Autoplay Controls -->
                            <div class="admin-card border-primary border-opacity-25 shadow-sm">
                                <div class="admin-card-header bg-light">
                                    <h5><i class="fas fa-sliders-h text-primary"></i> Slider Navigation Arrows &amp; Auto-running Controls</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-4">
                                        <!-- Left & Right Button Arrows -->
                                        <div class="col-md-6 border-end-md">
                                            <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                                                <i class="fas fa-arrows-alt-h text-primary"></i> Left &amp; Right Navigation Button Arrows (Aero Scroll)
                                            </label>
                                            <div class="d-flex flex-wrap gap-2">
                                                <label class="control-pill-box flex-fill">
                                                    <input type="radio" name="button_arrows" value="enable" <?php echo (($carousel['button_arrows'] ?? 'enable') === 'enable') ? 'checked' : ''; ?> class="form-check-input mt-0 me-2">
                                                    <div>
                                                        <strong class="d-block text-success small"><i class="fas fa-check-circle me-1"></i> Enable Arrows</strong>
                                                    </div>
                                                </label>
                                                <label class="control-pill-box flex-fill">
                                                    <input type="radio" name="button_arrows" value="disable" <?php echo (($carousel['button_arrows'] ?? 'enable') === 'disable') ? 'checked' : ''; ?> class="form-check-input mt-0 me-2">
                                                    <div>
                                                        <strong class="d-block text-danger small"><i class="fas fa-ban me-1"></i> Disable Arrows</strong>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Auto-running & Speed -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                                                <i class="fas fa-play-circle text-success"></i> Auto-running (Autoplay Slideshow)
                                            </label>
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <label class="control-pill-box flex-fill">
                                                    <input type="radio" name="auto_running" value="enable" <?php echo (($carousel['auto_running'] ?? 'enable') === 'enable') ? 'checked' : ''; ?> onchange="toggleAutoRunSpeed(true)" class="form-check-input mt-0 me-2">
                                                    <div>
                                                        <strong class="d-block text-success small"><i class="fas fa-play me-1"></i> Enable Auto-run</strong>
                                                    </div>
                                                </label>
                                                <label class="control-pill-box flex-fill">
                                                    <input type="radio" name="auto_running" value="disable" <?php echo (($carousel['auto_running'] ?? 'enable') === 'disable') ? 'checked' : ''; ?> onchange="toggleAutoRunSpeed(false)" class="form-check-input mt-0 me-2">
                                                    <div>
                                                        <strong class="d-block text-secondary small"><i class="fas fa-pause me-1"></i> Disable Auto-run</strong>
                                                    </div>
                                                </label>
                                            </div>

                                            <div id="autoRunSpeedWrapper" class="p-2 bg-light rounded border">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto">
                                                        <label class="form-label small fw-bold mb-0 text-dark">Speed (ms):</label>
                                                    </div>
                                                    <div class="col-auto">
                                                        <input type="number" class="form-control form-control-sm" name="auto_run_speed" id="autoRunSpeedInput" value="<?php echo (int)($carousel['auto_run_speed'] ?? 3000); ?>" min="1000" step="500" style="width: 105px;">
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(2000)">2.0s</button>
                                                            <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(3000)">3.0s</button>
                                                            <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(4000)">4.0s</button>
                                                            <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(5000)">5.0s</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Placement & Relative Positioning Settings -->
                            <div class="admin-card">
                                <div class="admin-card-header d-flex justify-content-between align-items-center">
                                    <h5><i class="fas fa-map-marker-alt text-primary"></i> Desktop Page, Category &amp; Relative Placement</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <!-- 1. Target Page -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Desktop Target Page Select</label>
                                            <?php $curDesktopPage = $carousel['desktop_page'] ?? 'home'; ?>
                                            <select name="desktop_page" id="desktopPageSelect" class="form-select" onchange="filterDesktopSections(this.value)">
                                                <option value="home" <?php echo ($curDesktopPage === 'home') ? 'selected' : ''; ?>>Home Page (home)</option>
                                                <option value="category" <?php echo ($curDesktopPage === 'category') ? 'selected' : ''; ?>>Category Page (category)</option>
                                                <option value="product" <?php echo ($curDesktopPage === 'product') ? 'selected' : ''; ?>>Product Page (product)</option>
                                                <option value="blog" <?php echo ($curDesktopPage === 'blog') ? 'selected' : ''; ?>>Blog Page (blog)</option>
                                                <option value="cart" <?php echo ($curDesktopPage === 'cart') ? 'selected' : ''; ?>>Cart Page (cart)</option>
                                                <option value="checkout" <?php echo ($curDesktopPage === 'checkout') ? 'selected' : ''; ?>>Checkout Page (checkout)</option>
                                                <option value="orders" <?php echo ($curDesktopPage === 'orders') ? 'selected' : ''; ?>>Orders Page (orders)</option>
                                                <option value="all" <?php echo ($curDesktopPage === 'all') ? 'selected' : ''; ?>>All Pages (Global Sitewide)</option>
                                                <option value="other" <?php echo ($curDesktopPage === 'other') ? 'selected' : ''; ?>>Other / Custom Pages</option>
                                            </select>
                                        </div>

                                        <!-- 2. Page Section / Slot Select -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Page Section / Slot Select</label>
                                            <select name="desktop_home_carousel_id" id="desktopCarouselSelect" class="form-select">
                                                <option value="">— Select Section / Carousel Slot —</option>
                                            </select>
                                        </div>

                                        <!-- 3. Target Category -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Desktop Category Select</label>
                                            <select name="desktop_home_category_id" id="desktopCategorySelect" class="form-select">
                                                <option value="">— All Categories (Global on this Page) —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo (($carousel['desktop_home_category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Multi-Page Application / Show on other pages -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Desktop Show on other pages</label>
                                                <?php $curDesktopOther = $carousel['desktop_show_other_pages'] ?? 'no'; ?>
                                                <select name="desktop_show_other_pages" id="desktopShowOtherSelect" class="form-select" onchange="toggleDesktopOtherPages(this.value)">
                                                    <option value="no" <?php echo ($curDesktopOther === 'no') ? 'selected' : ''; ?>>No (Selected Page Only)</option>
                                                    <option value="yes" <?php echo ($curDesktopOther === 'yes') ? 'selected' : ''; ?>>Yes (Also Apply on Category / Other Pages)</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 desktop-other-group" style="<?php echo ($curDesktopOther === 'yes') ? '' : 'display: none;'; ?>">
                                                <label class="form-label fw-semibold">Desktop Other pages Select category</label>
                                                <select name="desktop_other_category_id" class="form-select">
                                                    <option value="">— All Other Page Categories —</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>" <?php echo (($carousel['desktop_other_category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-4 desktop-other-group" style="<?php echo ($curDesktopOther === 'yes') ? '' : 'display: none;'; ?>">
                                                <label class="form-label fw-semibold">Desktop Other pages Select Carousel</label>
                                                <select name="desktop_other_carousel_id" class="form-select">
                                                    <option value="">— Select Other Page Section / Slot —</option>
                                                    <?php foreach ($carousels as $car): ?>
                                                        <option value="<?php echo $car['id']; ?>" <?php echo (($carousel['desktop_other_carousel_id'] ?? 0) == $car['id']) ? 'selected' : ''; ?>>
                                                            [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Desktop Relative Positioning -->
                                    <div class="col-12 mt-4 pt-3 border-top">
                                        <label class="form-label fw-bold mb-1"><i class="fas fa-arrows-alt-v text-primary me-1"></i> Position This Banner Slider (Relative Positioning)</label>
                                        <div class="text-muted small mb-2">Pick an existing Category, Product Carousel, Banner, or Extra Product block to place this slider before or after it.</div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Relative to section type</label>
                                            <?php $curDAnchorType = $carousel['desktop_anchor_type'] ?? ''; ?>
                                            <select class="form-select anchor-type-select" data-device="desktop" onchange="pmOnAnchorTypeChange('desktop', this.value)">
                                                <option value="">-- None (Manual Sort Order) --</option>
                                                <?php foreach ($anchorSectionTypes as $aKey => $aLabel): ?>
                                                    <option value="<?php echo $aKey; ?>" <?php echo ($curDAnchorType === $aKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($aLabel); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="desktop_anchor_type" id="desktopAnchorType" value="<?php echo htmlspecialchars($curDAnchorType); ?>">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">Specific section</label>
                                            <select name="desktop_anchor_id" id="desktopAnchorSection" class="form-select" onchange="pmApplyAnchorOrder('desktop')">
                                                <option value="">-- Pick a section type first --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Place</label>
                                            <?php $curDAnchorPos = $carousel['desktop_anchor_position'] ?? 'after'; ?>
                                            <select name="desktop_anchor_position" id="desktopAnchorPosition" class="form-select" onchange="pmApplyAnchorOrder('desktop')">
                                                <option value="after" <?php echo ($curDAnchorPos === 'after') ? 'selected' : ''; ?>>After</option>
                                                <option value="before" <?php echo ($curDAnchorPos === 'before') ? 'selected' : ''; ?>>Before</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <?php if ($subtype === 'service_slider'): ?>
                            <!-- Dual Slider Uploads: With Printing & Without Printing -->
                            <div class="admin-card border-info">
                                <div class="admin-card-header bg-info bg-opacity-10">
                                    <h5><i class="fas fa-concierge-bell text-info"></i> Category Service Slider Views (With Printing &amp; Without Printing)</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark">Desktop With Printing View Image</label>
                                            <div class="upload-dropzone mb-2" onclick="document.getElementById('dWithPrintInput').click()">
                                                <input type="file" name="desktop_with_printing_image" id="dWithPrintInput" accept="image/*" onchange="previewUpload(this, 'dWithPrintPrev')">
                                                <?php $dwp = resolveAdminThumb($carousel['desktop_with_printing_image'] ?? ''); ?>
                                                <?php if (!empty($dwp)): ?>
                                                    <img id="dWithPrintPrev" src="<?php echo htmlspecialchars($dwp); ?>" class="upload-preview-img" alt="With Printing Preview">
                                                <?php else: ?>
                                                    <i class="fas fa-cloud-upload-alt text-muted fa-2x mb-1"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Upload "With Printing" Slide Image</p>
                                                    <img id="dWithPrintPrev" class="upload-preview-img" alt="With Printing Preview" style="display:none;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark">Desktop Without Printing View Image</label>
                                            <div class="upload-dropzone mb-2" onclick="document.getElementById('dWithoutPrintInput').click()">
                                                <input type="file" name="desktop_without_printing_image" id="dWithoutPrintInput" accept="image/*" onchange="previewUpload(this, 'dWithoutPrintPrev')">
                                                <?php $dwop = resolveAdminThumb($carousel['desktop_without_printing_image'] ?? ''); ?>
                                                <?php if (!empty($dwop)): ?>
                                                    <img id="dWithoutPrintPrev" src="<?php echo htmlspecialchars($dwop); ?>" class="upload-preview-img" alt="Without Printing Preview">
                                                <?php else: ?>
                                                    <i class="fas fa-cloud-upload-alt text-muted fa-2x mb-1"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Upload "Without Printing" Slide Image</p>
                                                    <img id="dWithoutPrintPrev" class="upload-preview-img" alt="Without Printing Preview" style="display:none;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($subtype === 'product_detail_slider'): ?>
                            <!-- Product Details Pages Banner Slider: 4 Placement Checkboxes -->
                            <div class="admin-card border-warning">
                                <div class="admin-card-header bg-warning bg-opacity-10">
                                    <h5><i class="fas fa-check-double text-warning"></i> Product Details Pages Banner Placement Rules</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="show_product_category_carousel" value="yes" id="chkShowProdCatCarousel" <?php echo (($carousel['show_product_category_carousel'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="chkShowProdCatCarousel">
                                                    Show in Home pages Product category carousel
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="show_home_category" value="yes" id="chkShowHomeCategory" <?php echo (($carousel['show_home_category'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="chkShowHomeCategory">
                                                    Show in home pages category
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="show_category_carousel" value="yes" id="chkShowCategoryCarousel" <?php echo (($carousel['show_category_carousel'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="chkShowCategoryCarousel">
                                                    Show in Home pages category carousel
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="show_home_product" value="yes" id="chkShowHomeProduct" <?php echo (($carousel['show_home_product'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="chkShowHomeProduct">
                                                    Show in home pages product
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Multi-Slot Uploads -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-images text-primary"></i> Desktop Carousel Images &amp; URLs</h5>
                                    <span class="badge bg-secondary" id="dCarouselSlotsBadge">Slide Slots</span>
                                </div>
                                <div class="admin-card-body">
                                    <div id="dCarouselUploadSlots"></div>
                                </div>
                            </div>

                            <!-- Schedule & Status -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-calendar-alt text-primary"></i> Desktop Carousel Status, Order &amp; Schedule</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Status</label>
                                            <select name="desktop_status" class="form-select">
                                                <option value="active" <?php echo ($carousel['desktop_status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                                <option value="inactive" <?php echo ($carousel['desktop_status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop sort order number</label>
                                            <input type="number" step="0.01" class="form-control" name="desktop_sort_order" id="desktopSortOrder" value="<?php echo htmlspecialchars((string)$carousel['desktop_sort_order']); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Start Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_start_date" value="<?php echo !empty($carousel['desktop_start_date']) ? date('Y-m-d', strtotime($carousel['desktop_start_date'])) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop End Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_end_date" value="<?php echo !empty($carousel['desktop_end_date']) ? date('Y-m-d', strtotime($carousel['desktop_end_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- 📱 MOBILE CAROUSEL SECTION                               -->
                    <!-- ======================================================== -->
                    <div id="mobileSectionPane" style="display: none;">
                        
                        <!-- Mobile Same Select Design (Document 6 Spec) -->
                        <?php $curSameDesign = $carousel['mobile_same_design'] ?? 'no'; ?>
                        <div class="admin-card border-success border-opacity-25 shadow-sm">
                            <div class="admin-card-header bg-light">
                                <h5><i class="fas fa-sync-alt text-success"></i> Show in Mobile Same select design</h5>
                                <span class="badge bg-success-subtle text-success fw-semibold">Doc 6 Requirement</span>
                            </div>
                            <div class="admin-card-body">
                                <label class="form-label fw-bold text-dark mb-1">Show in Mobile Same select design Yes/No</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="control-pill-box flex-fill">
                                        <input type="radio" name="mobile_same_design" value="yes" <?php echo ($curSameDesign === 'yes') ? 'checked' : ''; ?> onchange="toggleMobileSameDesign(this.value)" class="form-check-input mt-0 me-2">
                                        <div>
                                            <strong class="d-block text-success small"><i class="fas fa-check-circle me-1"></i> Yes (Same Design)</strong>
                                            <span class="text-muted" style="font-size:11px;">Hide mobile uploads, reuse desktop</span>
                                        </div>
                                    </label>
                                    <label class="control-pill-box flex-fill">
                                        <input type="radio" name="mobile_same_design" value="no" <?php echo ($curSameDesign === 'no') ? 'checked' : ''; ?> onchange="toggleMobileSameDesign(this.value)" class="form-check-input mt-0 me-2">
                                        <div>
                                            <strong class="d-block text-primary small"><i class="fas fa-sliders-h me-1"></i> No (Custom Mobile Upload)</strong>
                                            <span class="text-muted" style="font-size:11px;">Open mobile layout &amp; upload tab</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Relative Section Positioning -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-arrows-alt-v text-success"></i> Mobile Relative Positioning &amp; Sort Order</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Relative to mobile section type</label>
                                        <?php $curMAnchorType = $carousel['mobile_anchor_type'] ?? ''; ?>
                                        <select class="form-select anchor-type-select" data-device="mobile" onchange="pmOnAnchorTypeChange('mobile', this.value)">
                                            <option value="">-- None (Manual Sort Order) --</option>
                                            <?php foreach ($anchorSectionTypes as $aKey => $aLabel): ?>
                                                <option value="<?php echo $aKey; ?>" <?php echo ($curMAnchorType === $aKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($aLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="mobile_anchor_type" id="mobileAnchorType" value="<?php echo htmlspecialchars($curMAnchorType); ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Specific section</label>
                                        <select name="mobile_anchor_id" id="mobileAnchorSection" class="form-select" onchange="pmApplyAnchorOrder('mobile')">
                                            <option value="">-- Pick a section type first --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Place</label>
                                        <?php $curMAnchorPos = $carousel['mobile_anchor_position'] ?? 'after'; ?>
                                        <select name="mobile_anchor_position" id="mobileAnchorPosition" class="form-select" onchange="pmApplyAnchorOrder('mobile')">
                                            <option value="after" <?php echo ($curMAnchorPos === 'after') ? 'selected' : ''; ?>>After</option>
                                            <option value="before" <?php echo ($curMAnchorPos === 'before') ? 'selected' : ''; ?>>Before</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Mobile Status show and hide</label>
                                        <select name="mobile_status" class="form-select">
                                            <option value="active" <?php echo ($carousel['mobile_status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                            <option value="inactive" <?php echo ($carousel['mobile_status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Mobile sort order number</label>
                                        <input type="number" step="0.01" class="form-control" name="mobile_sort_order" id="mobileSortOrder" value="<?php echo htmlspecialchars((string)$carousel['mobile_sort_order']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dedicated Mobile Content Block -->
                        <div id="mobileCustomDesignWrapper" style="<?php echo ($curSameDesign === 'no') ? 'block' : 'none'; ?>;">
                            
                            <!-- 1. Select Mobile Design -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-palette text-success"></i> Mobile Banner Carousel Select Design</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="design-cards-grid">
                                        <div class="design-card <?php echo ($mobileDesign === 'mobile_format_1') ? 'active' : ''; ?>" data-cmformat="mobile_format_1" onclick="selectMobileCarouselDesign('mobile_format_1')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box"><div class="wf-single-square">Square 1:1</div></div>
                                            <div class="design-title">1 Format Design</div>
                                            <div class="design-badge">Single Square Carousel</div>
                                        </div>

                                        <div class="design-card <?php echo ($mobileDesign === 'mobile_format_2') ? 'active' : ''; ?>" data-cmformat="mobile_format_2" onclick="selectMobileCarouselDesign('mobile_format_2')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box"><div class="wf-full-rect">Landscape</div></div>
                                            <div class="design-title">2 Format Design</div>
                                            <div class="design-badge">16:9 Landscape Slider</div>
                                        </div>

                                        <div class="design-card <?php echo ($mobileDesign === 'mobile_format_3') ? 'active' : ''; ?>" data-cmformat="mobile_format_3" onclick="selectMobileCarouselDesign('mobile_format_3')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box"><div class="wf-two-col"><span>Slide 1</span><span>Slide 2</span></div></div>
                                            <div class="design-title">3 Format Design</div>
                                            <div class="design-badge">2 Square Banners Carousel</div>
                                        </div>

                                        <div class="design-card <?php echo ($mobileDesign === 'mobile_format_4') ? 'active' : ''; ?>" data-cmformat="mobile_format_4" onclick="selectMobileCarouselDesign('mobile_format_4')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box"><div class="wf-grid-2x3"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span></div></div>
                                            <div class="design-title">4 Format Design</div>
                                            <div class="design-badge">2 Columns × 3 Rows Grid</div>
                                        </div>

                                        <div class="design-card <?php echo ($mobileDesign === 'mobile_format_5') ? 'active' : ''; ?>" data-cmformat="mobile_format_5" onclick="selectMobileCarouselDesign('mobile_format_5')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box"><div class="wf-slim-strip">Small Strip</div></div>
                                            <div class="design-title">5 Format Design</div>
                                            <div class="design-badge">Small Height Strip Slider</div>
                                        </div>

                                        <div class="design-card <?php echo ($mobileDesign === 'mobile_format_6') ? 'active' : ''; ?>" data-cmformat="mobile_format_6" onclick="selectMobileCarouselDesign('mobile_format_6')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box"><div class="wf-grid-3x2"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span></div></div>
                                            <div class="design-title">6 Format Design</div>
                                            <div class="design-badge">3 Columns × 2 Rows Grid</div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="mobile_design" id="mobileCarouselDesignInput" value="<?php echo htmlspecialchars($mobileDesign); ?>">
                                </div>
                            </div>

                            <!-- 2. Mobile Placement & Target Page Settings -->
                            <div class="admin-card">
                                <div class="admin-card-header d-flex justify-content-between align-items-center">
                                    <h5><i class="fas fa-map-marker-alt text-success"></i> Mobile Page, Category &amp; Section Placement</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <!-- 1. Target Page -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Mobile Target Page Select</label>
                                            <?php $curMobPage = $carousel['mobile_page'] ?? 'home'; ?>
                                            <select name="mobile_page" id="mobilePageSelect" class="form-select" onchange="filterMobileSections(this.value)">
                                                <option value="home" <?php echo ($curMobPage === 'home') ? 'selected' : ''; ?>>Home Page (home)</option>
                                                <option value="category" <?php echo ($curMobPage === 'category') ? 'selected' : ''; ?>>Category Page (category)</option>
                                                <option value="product" <?php echo ($curMobPage === 'product') ? 'selected' : ''; ?>>Product Page (product)</option>
                                                <option value="blog" <?php echo ($curMobPage === 'blog') ? 'selected' : ''; ?>>Blog Page (blog)</option>
                                                <option value="cart" <?php echo ($curMobPage === 'cart') ? 'selected' : ''; ?>>Cart Page (cart)</option>
                                                <option value="checkout" <?php echo ($curMobPage === 'checkout') ? 'selected' : ''; ?>>Checkout Page (checkout)</option>
                                                <option value="orders" <?php echo ($curMobPage === 'orders') ? 'selected' : ''; ?>>Orders Page (orders)</option>
                                                <option value="all" <?php echo ($curMobPage === 'all') ? 'selected' : ''; ?>>All Pages (Global Mobile)</option>
                                                <option value="other" <?php echo ($curMobPage === 'other') ? 'selected' : ''; ?>>Other / Custom Pages</option>
                                            </select>
                                        </div>

                                        <!-- 2. Target Section -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Mobile Carousel Section Select</label>
                                            <select name="mobile_carousel_id" id="mobileCarouselSelect" class="form-select">
                                                <option value="">— Select Mobile Carousel Section —</option>
                                            </select>
                                        </div>

                                        <!-- 3. Target Category -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Mobile Category Select</label>
                                            <select name="mobile_category_id" id="mobileCategorySelect" class="form-select">
                                                <option value="">— All Categories (Global) —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo (($carousel['mobile_category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($subtype === 'service_slider'): ?>
                            <!-- Mobile Dual Uploads for Service Slider -->
                            <div class="admin-card border-info">
                                <div class="admin-card-header bg-info bg-opacity-10">
                                    <h5><i class="fas fa-concierge-bell text-info"></i> Mobile Category Service Slider Views</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark">Mobile With Printing View Image</label>
                                            <div class="upload-dropzone mb-2" onclick="document.getElementById('mWithPrintInput').click()">
                                                <input type="file" name="mobile_with_printing_image" id="mWithPrintInput" accept="image/*" onchange="previewUpload(this, 'mWithPrintPrev')">
                                                <?php $mwp = resolveAdminThumb($carousel['mobile_with_printing_image'] ?? ''); ?>
                                                <?php if (!empty($mwp)): ?>
                                                    <img id="mWithPrintPrev" src="<?php echo htmlspecialchars($mwp); ?>" class="upload-preview-img" alt="Mobile With Printing Preview">
                                                <?php else: ?>
                                                    <i class="fas fa-mobile-alt text-muted fa-2x mb-1"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Upload Mobile "With Printing" Slide</p>
                                                    <img id="mWithPrintPrev" class="upload-preview-img" alt="Mobile With Printing Preview" style="display:none;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark">Mobile Without Printing View Image</label>
                                            <div class="upload-dropzone mb-2" onclick="document.getElementById('mWithoutPrintInput').click()">
                                                <input type="file" name="mobile_without_printing_image" id="mWithoutPrintInput" accept="image/*" onchange="previewUpload(this, 'mWithoutPrintPrev')">
                                                <?php $mwop = resolveAdminThumb($carousel['mobile_without_printing_image'] ?? ''); ?>
                                                <?php if (!empty($mwop)): ?>
                                                    <img id="mWithoutPrintPrev" src="<?php echo htmlspecialchars($mwop); ?>" class="upload-preview-img" alt="Mobile Without Printing Preview">
                                                <?php else: ?>
                                                    <i class="fas fa-mobile-alt text-muted fa-2x mb-1"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Upload Mobile "Without Printing" Slide</p>
                                                    <img id="mWithoutPrintPrev" class="upload-preview-img" alt="Mobile Without Printing Preview" style="display:none;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($subtype === 'product_detail_slider'): ?>
                            <!-- Mobile Placement Checkboxes -->
                            <div class="admin-card border-warning">
                                <div class="admin-card-header bg-warning bg-opacity-10">
                                    <h5><i class="fas fa-check-double text-warning"></i> Mobile Placement Rules</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="mobile_show_product_category_carousel" value="yes" id="mChkShowProdCatCarousel" <?php echo (($carousel['mobile_show_product_category_carousel'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="mChkShowProdCatCarousel">
                                                    Show in Home pages Product category carousel
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="mobile_show_home_category" value="yes" id="mChkShowHomeCategory" <?php echo (($carousel['mobile_show_home_category'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="mChkShowHomeCategory">
                                                    Show in home pages category
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="mobile_show_category_carousel" value="yes" id="mChkShowCategoryCarousel" <?php echo (($carousel['mobile_show_category_carousel'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="mChkShowCategoryCarousel">
                                                    Show in Home pages category carousel
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-2 border rounded">
                                                <input class="form-check-input ms-0 me-3" type="checkbox" name="mobile_show_home_product" value="yes" id="mChkShowHomeProduct" <?php echo (($carousel['mobile_show_home_product'] ?? 'no') === 'yes') ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="mChkShowHomeProduct">
                                                    Show in home pages product
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Mobile Image Upload & URL Slots -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-images text-success"></i> Mobile Carousel Images &amp; URLs</h5>
                                    <span class="badge bg-secondary" id="mCarouselSlotsBadge">Slots</span>
                                </div>
                                <div class="admin-card-body">
                                    <div id="mCarouselUploadSlots"></div>
                                </div>
                            </div>

                        </div>

                        <!-- Same Design Active Message Banner -->
                        <div id="mobileSameDesignActiveBanner" class="admin-card border-success text-center p-4" style="<?php echo ($curSameDesign === 'yes') ? 'block' : 'none'; ?>;">
                            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                            <h5 class="fw-bold text-dark">Using Desktop Design and Images on Mobile</h5>
                            <p class="text-muted small mb-0">The carousel will automatically adapt the desktop configuration, images, and slide links on mobile devices. Choose "No" above if you want custom mobile uploads.</p>
                        </div>

                    </div>

                    <!-- Submit Bar -->
                    <div class="admin-card">
                        <div class="admin-card-body d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Update will apply to carousel slideshow immediately.</span>
                            <div>
                                <a href="banner-carousels.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                                    <i class="fas fa-save me-1"></i> Update Banner Slider
                                </button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
const CAROUSEL_SPECS = {
    'desktop_format_1': { slots: 4, name: '1 single image format design', dim: '1920×600 px / 1200×450 px' },
    'desktop_format_1_5': { slots: 4, name: '1.5 image format design', dim: '1200×500 px (1 + 0.5 Peek)' },
    'desktop_format_3': { slots: 6, name: '3 image format design', dim: '600×400 px each (3 Cards)' },
    'desktop_format_2_5': { slots: 6, name: '2.5 image format design', dim: '600×400 px each (2 + 0.5 Peek)' },
    'desktop_format_4': { slots: 8, name: '4 image format design', dim: '450×320 px each (4 Cards)' },
    'desktop_format_small': { slots: 4, name: '1 Small size format design', dim: '1920×220 px / 1200×180 px' },

    'mobile_format_1': { slots: 4, name: '1 Single Mobile Image', dim: '600×600 px / 800×450 px' },
    'mobile_format_1_5': { slots: 4, name: '1.5 Mobile Image Peek', dim: '600×400 px (1 + 0.5 Peek)' },
    'mobile_format_2': { slots: 4, name: 'Mobile Landscape 16:9', dim: '800×450 px' },
    'mobile_format_3': { slots: 4, name: 'Mobile 2 Columns', dim: '600×600 px each' },
    'mobile_format_small': { slots: 4, name: 'Mobile Small Strip', dim: '800×200 px' },
    'mobile_format_4': { slots: 6, name: 'Mobile 2 Columns × 3 Rows', dim: '600×600 px each' },
    'mobile_format_5': { slots: 4, name: 'Mobile Small Strip', dim: '800×200 px' },
    'mobile_format_6': { slots: 6, name: 'Mobile 3 Columns × 2 Rows', dim: '600×600 px each' }
};

const PM_ANCHOR_OPTIONS = <?php echo json_encode($anchorOptionsByDevice); ?>;
const EXISTING_D_ANCHOR_ID = <?php echo (int)($carousel['desktop_anchor_id'] ?? 0); ?>;
const EXISTING_M_ANCHOR_ID = <?php echo (int)($carousel['mobile_anchor_id'] ?? 0); ?>;

function pmOnAnchorTypeChange(device, anchorType, preselectId = null) {
    const hiddenType = document.getElementById(device + 'AnchorType');
    if (hiddenType) hiddenType.value = anchorType;

    const selectEl = document.getElementById(device + 'AnchorSection');
    if (!selectEl) return;
    selectEl.innerHTML = '';

    if (!anchorType || !PM_ANCHOR_OPTIONS[device] || !PM_ANCHOR_OPTIONS[device][anchorType] || PM_ANCHOR_OPTIONS[device][anchorType].length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = anchorType ? '-- No items found in this section type --' : '-- Pick a section type first --';
        selectEl.appendChild(opt);
        return;
    }

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = '-- Select specific section --';
    selectEl.appendChild(defaultOpt);

    PM_ANCHOR_OPTIONS[device][anchorType].forEach(function(item) {
        const o = document.createElement('option');
        o.value = item.id;
        o.textContent = item.label + ' (order: ' + item.sort_order + ')';
        o.setAttribute('data-order', item.sort_order);
        if (preselectId && parseInt(preselectId) === parseInt(item.id)) {
            o.selected = true;
        }
        selectEl.appendChild(o);
    });
}

function pmApplyAnchorOrder(device) {
    const sectionSel = document.getElementById(device + 'AnchorSection');
    const posSel = document.getElementById(device + 'AnchorPosition');
    const sortInput = document.getElementById(device + 'SortOrder');
    if (!sectionSel || !posSel || !sortInput) return;

    const selOption = sectionSel.options[sectionSel.selectedIndex];
    if (!selOption || !selOption.value) return;

    const baseOrder = parseFloat(selOption.getAttribute('data-order') || 0);
    const pos = posSel.value;
    const computedOrder = pos === 'before' ? Math.max(0, baseOrder - 0.5) : (baseOrder + 0.5);
    sortInput.value = computedOrder.toFixed(2);
}

function toggleMobileSameDesign(val) {
    const customWrap = document.getElementById('mobileCustomDesignWrapper');
    const banner = document.getElementById('mobileSameDesignActiveBanner');
    if (val === 'yes') {
        if (customWrap) customWrap.style.display = 'none';
        if (banner) banner.style.display = 'block';
    } else {
        if (customWrap) customWrap.style.display = 'block';
        if (banner) banner.style.display = 'none';
    }
}

// Existing multi images and URLs
const EXISTING_DESKTOP_IMAGES = <?php 
    $dim = !empty($carousel['desktop_images']) ? json_decode($carousel['desktop_images'], true) : [];
    if (empty($dim) && !empty($carousel['desktop_image'])) {
        $dim = [$carousel['desktop_image']];
    }
    echo json_encode(is_array($dim) ? $dim : []); 
?>;
const EXISTING_DESKTOP_URLS = <?php 
    $du = !empty($carousel['desktop_urls']) ? json_decode($carousel['desktop_urls'], true) : [];
    if (empty($du) && !empty($carousel['desktop_url'])) {
        $du = [$carousel['desktop_url']];
    }
    echo json_encode(is_array($du) ? $du : []); 
?>;

const EXISTING_MOBILE_IMAGES = <?php 
    $mim = !empty($carousel['mobile_images']) ? json_decode($carousel['mobile_images'], true) : [];
    if (empty($mim) && !empty($carousel['mobile_image'])) {
        $mim = [$carousel['mobile_image']];
    }
    echo json_encode(is_array($mim) ? $mim : []); 
?>;
const EXISTING_MOBILE_URLS = <?php 
    $mu = !empty($carousel['mobile_urls']) ? json_decode($carousel['mobile_urls'], true) : [];
    if (empty($mu) && !empty($carousel['mobile_url'])) {
        $mu = [$carousel['mobile_url']];
    }
    echo json_encode(is_array($mu) ? $mu : []); 
?>;

let currentDesktopSlotsCount = 0;
let currentDesktopSpec = null;

function toggleAutoRunSpeed(enabled) {
    const wrap = document.getElementById('autoRunSpeedWrapper');
    const input = document.getElementById('autoRunSpeedInput');
    if (wrap) wrap.style.opacity = enabled ? '1' : '0.4';
    if (input) input.disabled = !enabled;
}

function setSpeed(ms) {
    const input = document.getElementById('autoRunSpeedInput');
    if (input) input.value = ms;
}

function switchDeviceTab(tab) {
    const dPane = document.getElementById('desktopSectionPane');
    const mPane = document.getElementById('mobileSectionPane');
    const dBtn  = document.getElementById('tabBtnDesktop');
    const mBtn  = document.getElementById('tabBtnMobile');

    if (tab === 'desktop') {
        dPane.style.display = 'block';
        mPane.style.display = 'none';
        dBtn.classList.add('active');
        mBtn.classList.remove('active');
    } else {
        dPane.style.display = 'none';
        mPane.style.display = 'block';
        dBtn.classList.remove('active');
        mBtn.classList.add('active');
    }
}

function selectDesktopCarouselDesign(fmt) {
    document.querySelectorAll('.design-card[data-cformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-cformat="${fmt}"]`);
    if (target) target.classList.add('active');

    document.getElementById('desktopCarouselDesignInput').value = fmt;

    const spec = CAROUSEL_SPECS[fmt];
    if (spec) {
        document.getElementById('desktopCarouselDimText').innerHTML = `<strong>${spec.name}:</strong> Recommended Dimensions: <strong>${spec.dim}</strong>`;
        renderDesktopCarouselSlots(spec);
    }
}

function renderDesktopCarouselSlots(spec) {
    currentDesktopSpec = spec;
    const container = document.getElementById('dCarouselUploadSlots');
    const badge = document.getElementById('dCarouselSlotsBadge');
    
    const countNeeded = Math.max(spec.slots || 4, EXISTING_DESKTOP_IMAGES.length);
    currentDesktopSlotsCount = countNeeded;
    badge.innerText = `${currentDesktopSlotsCount} Slide Slots`;

    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small"><i class="fas fa-info-circle text-primary me-1"></i> Each slide rotates in the carousel slider. Upload images and enter target links.</span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAnotherDesktopSlot()">
                <i class="fas fa-plus me-1"></i> Add Another Slide Slot
            </button>
        </div>
        <div class="multi-slot-grid" id="desktopSlotsGridContainer">
    `;

    for (let i = 1; i <= currentDesktopSlotsCount; i++) {
        const existingImg = EXISTING_DESKTOP_IMAGES[i - 1] || '';
        const existingUrl = EXISTING_DESKTOP_URLS[i - 1] || '';
        html += renderSingleDesktopSlotHtml(i, spec.dim, existingImg, existingUrl);
    }

    html += `</div>`;
    container.innerHTML = html;
}

function renderSingleDesktopSlotHtml(i, dim, existingImg = '', existingUrl = '') {
    const hasImg = existingImg && existingImg.trim() !== '';
    return `
        <div class="slot-box" id="dSlotCard_${i}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="text-dark small"><i class="fas fa-layer-group text-primary me-1"></i> Slide #${i}</strong>
                <span class="badge bg-light text-secondary border">${dim}</span>
            </div>
            <div class="upload-dropzone mb-2" onclick="document.getElementById('dCSlot_${i}').click()">
                <input type="file" name="desktop_images[]" id="dCSlot_${i}" accept="image/*" onchange="previewUpload(this, 'dCPrev_${i}')">
                <input type="hidden" name="existing_desktop_images[${i - 1}]" value="${existingImg}">
                ${hasImg ? `
                    <img id="dCPrev_${i}" src="${existingImg}" class="upload-preview-img" alt="Slide ${i} Preview">
                    <small class="text-muted d-block mt-1">Click to replace image</small>
                ` : `
                    <i class="fas fa-cloud-upload-alt text-muted fa-lg mb-1"></i>
                    <small class="d-block text-dark fw-semibold">Upload Slide ${i} Image</small>
                    <img id="dCPrev_${i}" class="upload-preview-img" alt="Slide ${i} Preview" style="display:none;">
                `}
            </div>
            <label class="form-label small fw-semibold mb-1">Slide ${i} URL</label>
            <input type="text" class="form-control form-control-sm" name="desktop_urls[]" value="${existingUrl}" placeholder="https://printmont.com/...">
        </div>
    `;
}

function addAnotherDesktopSlot() {
    currentDesktopSlotsCount++;
    const grid = document.getElementById('desktopSlotsGridContainer');
    const badge = document.getElementById('dCarouselSlotsBadge');
    if (grid && currentDesktopSpec) {
        const div = document.createElement('div');
        div.innerHTML = renderSingleDesktopSlotHtml(currentDesktopSlotsCount, currentDesktopSpec.dim);
        grid.appendChild(div.firstElementChild);
        badge.innerText = `${currentDesktopSlotsCount} Slide Slots`;
    }
}

function selectMobileCarouselDesign(fmt) {
    document.querySelectorAll('.design-card[data-cmformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-cmformat="${fmt}"]`);
    if (target) target.classList.add('active');

    document.getElementById('mobileCarouselDesignInput').value = fmt;

    const spec = CAROUSEL_SPECS[fmt];
    if (spec) {
        renderMobileCarouselSlots(spec);
    }
}

function renderMobileCarouselSlots(spec) {
    const container = document.getElementById('mCarouselUploadSlots');
    const badge = document.getElementById('mCarouselSlotsBadge');
    const countNeeded = Math.max(spec.slots || 4, EXISTING_MOBILE_IMAGES.length);
    badge.innerText = `${countNeeded} Slide Slots`;

    let html = `<div class="multi-slot-grid">`;
    for (let i = 1; i <= countNeeded; i++) {
        const existingImg = EXISTING_MOBILE_IMAGES[i - 1] || '';
        const existingUrl = EXISTING_MOBILE_URLS[i - 1] || '';
        const hasImg = existingImg && existingImg.trim() !== '';

        html += `
            <div class="slot-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-dark small">Slot #${i}</strong>
                    <span class="badge bg-light text-secondary border">${spec.dim}</span>
                </div>
                <div class="upload-dropzone mb-2" onclick="document.getElementById('mCSlot_${i}').click()">
                    <input type="file" name="mobile_images[]" id="mCSlot_${i}" accept="image/*" onchange="previewUpload(this, 'mCPrev_${i}')">
                    <input type="hidden" name="existing_mobile_images[${i - 1}]" value="${existingImg}">
                    ${hasImg ? `
                        <img id="mCPrev_${i}" src="${existingImg}" class="upload-preview-img" alt="Slot ${i} Preview">
                        <small class="text-muted d-block mt-1">Click to replace</small>
                    ` : `
                        <i class="fas fa-mobile text-muted"></i>
                        <small class="d-block text-dark mt-1">Upload Slot ${i}</small>
                        <img id="mCPrev_${i}" class="upload-preview-img" alt="Slot ${i} Preview" style="display:none;">
                    `}
                </div>
                <label class="form-label small fw-semibold mb-1">Slot ${i} URL</label>
                <input type="text" class="form-control form-control-sm" name="mobile_urls[]" value="${existingUrl}" placeholder="https://printmont.com/...">
            </div>
        `;
    }
    html += `</div>`;
    container.innerHTML = html;
}

function previewUpload(input, previewImgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewImgId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

const ALL_CAROUSEL_SECTIONS = <?php echo json_encode($carousels); ?>;

function filterDesktopSections(selectedPage, preselectedId = null) {
    const sel = document.getElementById('desktopCarouselSelect');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Select Section / Carousel Slot —</option>';

    let filtered = ALL_CAROUSEL_SECTIONS.filter(s => {
        if (!selectedPage || selectedPage === 'all') return true;
        if (selectedPage === 'other') return !['home', 'category', 'product', 'blog', 'cart', 'checkout', 'orders'].includes(s.page);
        return s.page === selectedPage;
    });

    if (filtered.length === 0) {
        filtered = ALL_CAROUSEL_SECTIONS;
    }

    filtered.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `[${s.page.toUpperCase()}] ${s.title} (${s.is_slider ? 'Slider' : (s.columns_per_row || 1) + ' Col'})`;
        if (preselectedId && parseInt(preselectedId) === parseInt(s.id)) {
            opt.selected = true;
        }
        sel.appendChild(opt);
    });
}

function filterMobileSections(selectedPage, preselectedId = null) {
    const sel = document.getElementById('mobileCarouselSelect');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Select Mobile Carousel Section —</option>';

    let filtered = ALL_CAROUSEL_SECTIONS.filter(s => {
        if (!selectedPage || selectedPage === 'all') return true;
        if (selectedPage === 'home') return s.page === 'home' || s.page === 'mobile';
        if (selectedPage === 'other') return !['home', 'mobile', 'category', 'product', 'blog', 'cart', 'checkout', 'orders'].includes(s.page);
        return s.page === selectedPage;
    });

    if (filtered.length === 0) {
        filtered = ALL_CAROUSEL_SECTIONS;
    }

    filtered.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `[${s.page.toUpperCase()}] ${s.title} (${s.is_slider ? 'Slider' : (s.columns_per_row || 1) + ' Col'})`;
        if (preselectedId && parseInt(preselectedId) === parseInt(s.id)) {
            opt.selected = true;
        }
        sel.appendChild(opt);
    });
}

function toggleDesktopOtherPages(val) {
    document.querySelectorAll('.desktop-other-group').forEach(el => {
        el.style.display = (val === 'yes') ? 'block' : 'none';
    });
}

function toggleMobileOtherPages(val) {
    document.querySelectorAll('.mobile-other-group').forEach(el => {
        el.style.display = (val === 'yes') ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    filterDesktopSections(
        document.getElementById('desktopPageSelect') ? document.getElementById('desktopPageSelect').value : 'home',
        <?php echo (int)($carousel['desktop_home_carousel_id'] ?? 0); ?>
    );
    filterMobileSections(
        document.getElementById('mobilePageSelect') ? document.getElementById('mobilePageSelect').value : 'home',
        <?php echo (int)($carousel['mobile_carousel_id'] ?? 0); ?>
    );

    // Initial desktop & mobile design selections
    selectDesktopCarouselDesign('<?php echo htmlspecialchars($desktopDesign); ?>');
    selectMobileCarouselDesign('<?php echo htmlspecialchars($mobileDesign); ?>');

    // Populate anchor dropdowns with existing saved values
    <?php if (!empty($curDAnchorType)): ?>
        pmOnAnchorTypeChange('desktop', '<?php echo addslashes($curDAnchorType); ?>', EXISTING_D_ANCHOR_ID);
    <?php endif; ?>
    <?php if (!empty($curMAnchorType)): ?>
        pmOnAnchorTypeChange('mobile', '<?php echo addslashes($curMAnchorType); ?>', EXISTING_M_ANCHOR_ID);
    <?php endif; ?>
});
</script>
</body>
</html>
