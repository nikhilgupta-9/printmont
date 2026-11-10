<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/banner-helpers.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$database = new Database();
$db       = $database->getConnection();

$bannerController = new BannerController();
$categories       = $bannerController->getAllCategories();
$carousels        = $bannerController->getAllCarousels();

// Document 7 Banner Subtypes:
// 1. small (Small Banner)
// 2. normal (Normal Banner)
// 3. service (Service Banner)
// 4. product_detail (Product details pages banner)
// 5. dashboard (User Dashboard Banner setting)
$subtype = $_GET['type'] ?? 'normal';
if (!in_array($subtype, ['small', 'normal', 'service', 'product_detail', 'dashboard'])) {
    $subtype = 'normal';
}

$subtypeTitles = [
    'small'          => '1. Small Banner',
    'normal'         => '2. Normal Banner',
    'service'        => '3. Service Banner',
    'product_detail' => '4. Product Details Pages Banner',
    'dashboard'      => '5. User Dashboard Banner',
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

// Load products for "Home pages product select (Jo home page pe extra product show ho rha he uska hai ye)"
$extraProductsRes = $db->query("SELECT id, name, sku FROM products WHERE status = 'active' ORDER BY name ASC LIMIT 100");
$productList = [];
if ($extraProductsRes) {
    while ($p = $extraProductsRes->fetch_assoc()) $productList[] = $p;
}

$error_message   = '';
$success_message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $result = $bannerController->createBanner($_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: view-banner.php');
        exit;
    } else {
        $error_message = $result['error'];
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add New Banner | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .required-field::after { content: " *"; color: #dc3545; font-weight: bold; }

        /* Module Top Navigation Pills (Standard across Document 7 Home Pages Banners) */
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: #0d6efd; background: #e0edff; }
        .module-nav-link.active { color: #fff; background: #0d6efd; }
        
        /* Modern Glass/Card Styling */
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
        .admin-card-body {
            padding: 24px 20px;
        }

        /* Format Design Selector Cards */
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
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all 0.2s;
        }
        .design-card.active .check-indicator {
            background: #0b53a1;
        }

        /* Diagram Wireframes matching user reference image */
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
        .design-card.active .wireframe-box {
            border-color: #3b82f6;
            background: #ffffff;
        }
        .wf-single-square {
            width: 50px;
            height: 50px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #b91c1c;
            font-weight: 700;
        }
        .wf-full-rect {
            width: 100%;
            height: 44px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #b91c1c;
            font-weight: 700;
        }
        .wf-two-col {
            display: flex;
            gap: 6px;
            width: 100%;
            justify-content: center;
        }
        .wf-two-col span {
            width: 45px;
            height: 45px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #b91c1c;
            font-weight: 700;
            text-align: center;
        }
        .wf-grid-2x3 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
            width: 70px;
            margin: 0 auto;
        }
        .wf-grid-2x3 span {
            height: 22px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 2px;
            font-size: 6px;
            color: #b91c1c;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wf-slim-strip {
            width: 100%;
            height: 22px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #b91c1c;
            font-weight: 700;
        }
        .wf-grid-3x2 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            width: 90px;
            margin: 0 auto;
        }
        .wf-grid-3x2 span {
            height: 24px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 2px;
            font-size: 6px;
            color: #b91c1c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .design-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .design-badge {
            font-size: 11px;
            color: #64748b;
        }

        /* Dimension Alert Notice */
        .dimension-info-pill {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* Image Upload Box with Drag & Drop Preview */
        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.2s;
            cursor: pointer;
            position: relative;
        }
        .upload-dropzone:hover {
            border-color: #0b53a1;
            background: #f0f7ff;
        }
        .upload-dropzone input[type="file"] {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .upload-preview-img {
            max-width: 100%;
            max-height: 180px;
            border-radius: 6px;
            object-fit: contain;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: none;
        }

        /* Multi upload slots container */
        .multi-slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .slot-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        /* Empty state prompt before design selected */
        .empty-design-prompt {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
        }
        .empty-design-prompt i {
            font-size: 36px;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        /* Device Tab Pills */
        .device-nav-pills {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .device-pill-btn {
            background: transparent;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .device-pill-btn.active {
            color: #0b53a1;
            border-bottom-color: #0b53a1;
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

                <!-- Document 7 Banner Navigation Pills -->
                <div class="module-nav-pills mb-4">
                    <a href="add-banner.php?type=small" class="module-nav-link <?php echo $subtype === 'small' ? 'active' : ''; ?>">
                        <i class="fas fa-compress-arrows-alt"></i> 1. Small Banner
                    </a>
                    <a href="add-banner.php?type=normal" class="module-nav-link <?php echo $subtype === 'normal' ? 'active' : ''; ?>">
                        <i class="fas fa-image"></i> 2. Normal Banner
                    </a>
                    <a href="add-banner.php?type=service" class="module-nav-link <?php echo $subtype === 'service' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i> 3. Service Banner
                    </a>
                    <a href="add-banner.php?type=product_detail" class="module-nav-link <?php echo $subtype === 'product_detail' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> 4. Product Details Pages Banner
                    </a>
                    <a href="add-banner.php?type=dashboard" class="module-nav-link <?php echo $subtype === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> 5. User Dashboard Banner
                    </a>
                    <a href="view-banner.php" class="module-nav-link ms-auto">
                        <i class="fas fa-list"></i> All Banners
                    </a>
                </div>

                <!-- Header Breadcrumb & Actions -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-plus-circle text-primary me-2"></i>Add New <?php echo htmlspecialchars($subtypeTitles[$subtype]); ?>
                        </h3>
                        <p class="text-muted small mb-0">Create and configure desktop &amp; mobile responsive banners across pages.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <a href="view-banner.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Banners
                        </a>
                        <a href="add-banner-carousel.php" class="btn btn-outline-primary">
                            <i class="fas fa-layer-group me-1"></i> Add Banner Carousel
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

                <form method="POST" enctype="multipart/form-data" id="addBannerForm">
                    <input type="hidden" name="banner_type" value="single">
                    <input type="hidden" name="banner_subtype" value="<?php echo htmlspecialchars($subtype); ?>">

                    <!-- Basic Banner Information Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5><i class="fas fa-heading text-primary"></i> 1. Banner Scope &amp; General Information</h5>
                            <span class="badge bg-primary text-white fw-bold px-3 py-1"><?php echo htmlspecialchars($subtypeTitles[$subtype]); ?></span>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label required-field fw-semibold">Banner Title Name</label>
                                    <input type="text" class="form-control form-control-lg" name="title" required
                                           placeholder="e.g. Mega Festival Offer - Up to 50% Off Personalised Gifts">
                                    <small class="text-muted">Descriptive name for admin reference and storefront display.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required-field fw-semibold">Primary Target Page</label>
                                    <select name="target_page" id="targetPageSelect" class="form-select form-select-lg">
                                        <option value="home" selected>Home Page (Main Storefront)</option>
                                        <option value="category">Category Page</option>
                                        <option value="product">Product Page</option>
                                        <option value="product_detail">Product Detail Page</option>
                                        <option value="franchise">Franchise Page</option>
                                        <option value="affiliate_program">Affiliate Program Page</option>
                                        <option value="career">Career Page</option>
                                        <option value="business_solutions">Business Solutions Page</option>
                                        <option value="advertising">Advertising Page</option>
                                        <option value="about_us">About Us Page</option>
                                    </select>
                                    <small class="text-muted">Choose where this banner primarily belongs.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Image Alt Text (SEO &amp; Accessibility)</label>
                                    <input type="text" class="form-control" name="alt_text" placeholder="e.g. Corporate Gifts Promotional Offer Banner">
                                    <small class="text-muted">Search engine image alt description.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Banner Slogan / Subtitle</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="slogan" placeholder="e.g. Fast 24-Hour Dispatch Available">
                                        <div class="input-group-text bg-light">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_slogan" id="showSlogan" value="1" checked>
                                                <label class="form-check-label small ms-1" for="showSlogan">Show</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Banner Description / Promotional Copy</label>
                                    <textarea class="form-control" name="description" rows="2" placeholder="Brief banner description, promo text, or admin notes..."></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Home page Extra Product Select (Optional)</label>
                                    <select name="extra_product_id" class="form-select">
                                        <option value="">— None (No extra featured product) —</option>
                                        <?php foreach ($productList as $p): ?>
                                            <option value="<?php echo (int)$p['id']; ?>">
                                                <?php echo htmlspecialchars($p['name'] . (!empty($p['sku']) ? ' (' . $p['sku'] . ')' : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Links banner relative to extra featured product.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Page Visibility Card: Show on Other Pages -->
                    <div class="admin-card border-primary-subtle">
                        <div class="admin-card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-layer-group text-primary me-2"></i> Also Show on Other Pages (Multi-Page Display)</h5>
                                <small class="text-muted">Client Requirement: Use checkboxes to easily display this Home page banner on other storefront pages.</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1">8 Specific Pages</span>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_category" id="chkCat" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkCat">
                                                <i class="fas fa-th-large text-primary me-1"></i> Category Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Shop category pages</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_product" id="chkProd" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkProd">
                                                <i class="fas fa-box text-success me-1"></i> Product Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">All products listing</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_product_detail" id="chkPDet" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkPDet">
                                                <i class="fas fa-tag text-info me-1"></i> Product Detail
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Product details page</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_franchise" id="chkFran" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkFran">
                                                <i class="fas fa-store text-warning me-1"></i> Franchise Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Franchise portal</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_affiliate" id="chkAff" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkAff">
                                                <i class="fas fa-handshake text-purple me-1"></i> Affiliate Program
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Affiliate landing</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_career" id="chkCar" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkCar">
                                                <i class="fas fa-user-tie text-danger me-1"></i> Career Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Jobs &amp; careers</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_business_solutions" id="chkBiz" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkBiz">
                                                <i class="fas fa-briefcase text-primary me-1"></i> Business Solutions
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Corporate gifting / B2B</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_advertising" id="chkAdv" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkAdv">
                                                <i class="fas fa-bullhorn text-secondary me-1"></i> Advertising Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">Brand ads &amp; promo</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_on_about_us" id="chkAbout" value="yes">
                                            <label class="form-check-label fw-bold text-dark ms-1" for="chkAbout">
                                                <i class="fas fa-info-circle text-info me-1"></i> About Us Page
                                            </label>
                                        </div>
                                        <small class="text-muted ps-4">About Printmont page</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO & Search Optimization Card -->
                    <div class="admin-card">
                        <div class="admin-card-header bg-light">
                            <h5><i class="fas fa-search text-primary me-2"></i> SEO Meta Tags &amp; Search Optimization</h5>
                            <span class="badge bg-secondary text-white fw-semibold px-2 py-1">Optional SEO</span>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Meta Title</label>
                                    <input type="text" class="form-control" name="meta_title" placeholder="e.g. Personalized Gifts & Corporate Printing | Printmont">
                                    <small class="text-muted">Search engine title tag.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Meta Keywords</label>
                                    <input type="text" class="form-control" name="meta_keywords" placeholder="e.g. gifts, corporate printing, mug printing, t-shirt printing">
                                    <small class="text-muted">Comma-separated keywords.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Meta Description</label>
                                    <textarea class="form-control" name="meta_description" rows="2" placeholder="Brief SEO description snippet (up to 160 characters)..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device Navigation Tabs (Desktop & Mobile) -->
                    <div class="device-nav-pills">
                        <button type="button" class="device-pill-btn active" id="tabBtnDesktop" onclick="switchDeviceTab('desktop')">
                            <i class="fas fa-desktop"></i> Desktop Banner Configuration
                        </button>
                        <button type="button" class="device-pill-btn" id="tabBtnMobile" onclick="switchDeviceTab('mobile')">
                            <i class="fas fa-mobile-alt"></i> Mobile Banner Configuration
                        </button>
                    </div>

                    <!-- ======================================================== -->
                    <!-- 🖥️ DESKTOP SECTION CONFIGURATION                         -->
                    <!-- ======================================================== -->
                    <div id="desktopSectionPane">
                        
                        <!-- 1. Select Desktop Design -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-primary"></i> Desktop Banner Select Design</h5>
                                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">Required to reveal fields</span>
                            </div>
                            <div class="admin-card-body">
                                <p class="text-muted small mb-3">Choose the layout format design as per the diagram. The upload boxes and URL fields will adapt automatically.</p>

                                <div class="design-cards-grid">
                                    <!-- Format 1 -->
                                    <div class="design-card" data-format="desktop_format_1" onclick="selectDesktopDesign('desktop_format_1')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-single-square">Square Offer</div>
                                        </div>
                                        <div class="design-title">1 Format Design</div>
                                        <div class="design-badge">Single Square (1:1)</div>
                                    </div>

                                    <!-- Format 2 -->
                                    <div class="design-card" data-format="desktop_format_2" onclick="selectDesktopDesign('desktop_format_2')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-full-rect">Offer Banner (Wide)</div>
                                        </div>
                                        <div class="design-title">2 Format Design</div>
                                        <div class="design-badge">Full Width Rectangular</div>
                                    </div>

                                    <!-- Format 3 -->
                                    <div class="design-card" data-format="desktop_format_3" onclick="selectDesktopDesign('desktop_format_3')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-two-col">
                                                <span>Square 1</span>
                                                <span>Square 2</span>
                                            </div>
                                        </div>
                                        <div class="design-title">3 Format Design</div>
                                        <div class="design-badge">2 Square Banners Side-by-Side</div>
                                    </div>

                                    <!-- Format 4 -->
                                    <div class="design-card" data-format="desktop_format_4" onclick="selectDesktopDesign('desktop_format_4')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-2x3">
                                                <span>1</span><span>2</span>
                                                <span>3</span><span>4</span>
                                                <span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">4 Format Design</div>
                                        <div class="design-badge">2 Columns × 3 Rows Grid</div>
                                    </div>

                                    <!-- Format 5 -->
                                    <div class="design-card" data-format="desktop_format_5" onclick="selectDesktopDesign('desktop_format_5')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-slim-strip">Small Offer Banner (Strip)</div>
                                        </div>
                                        <div class="design-title">5 Format Design</div>
                                        <div class="design-badge">Small Slim Strip Banner</div>
                                    </div>

                                    <!-- Format 6 -->
                                    <div class="design-card" data-format="desktop_format_6" onclick="selectDesktopDesign('desktop_format_6')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-3x2">
                                                <span>1</span><span>2</span><span>3</span>
                                                <span>4</span><span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">6 Format Design</div>
                                        <div class="design-badge">3 Columns × 2 Rows Grid</div>
                                    </div>
                                </div>

                                <input type="hidden" name="desktop_design" id="desktopDesignInput" value="">
                            </div>
                        </div>

                        <!-- 2. Conditional Section: Reveals ONLY when Desktop Design is chosen -->
                        <div id="desktopDependentFields" style="display: none;">

                            <!-- Dimension Recommendation Badge -->
                            <div class="dimension-info-pill" id="desktopDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="desktopDimText">Recommended Size: 600×600 px (Square 1:1)</span>
                            </div>

                            <!-- Placement Settings (Home Category, Carousel & Other Pages) -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-map-marker-alt text-primary"></i> Desktop Placement &amp; Category Settings</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <!-- Home page Category Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop Home pages Category Select</label>
                                            <select name="desktop_home_category_id" class="form-select">
                                                <option value="">— Select Category for Home Page —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Category context under which banner is displayed.</small>
                                        </div>

                                        <!-- Home page Carousel Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop Home page Carousel Select</label>
                                            <select name="desktop_home_carousel_id" class="form-select">
                                                <option value="">— Select Placement Carousel / Section —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Target section slot on home page.</small>
                                        </div>

                                        <!-- Show on other pages Switch -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Desktop Show on other pages</label>
                                            <select name="desktop_show_other_pages" id="desktopShowOtherSelect" class="form-select" onchange="toggleDesktopOtherPages(this.value)">
                                                <option value="no">No (Home Page Only)</option>
                                                <option value="yes">Yes (Show on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <!-- Other pages Select category -->
                                        <div class="col-md-4 desktop-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Desktop Other pages Select category</label>
                                            <select name="desktop_other_category_id" class="form-select">
                                                <option value="">— Select Other Page Category —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Other pages Select Carousel -->
                                        <div class="col-md-4 desktop-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Desktop Other pages Select Carousel</label>
                                            <select name="desktop_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Carousel —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Desktop Relative Positioning (Position Before / After an existing section) -->
                                        <div class="col-12 mt-3 pt-3 border-top">
                                            <label class="form-label fw-bold mb-1"><i class="fas fa-arrows-alt-v text-primary me-1"></i> Position This Banner (optional)</label>
                                            <div class="text-muted small mb-2">Pick an existing section to position this banner before or after it. Leave blank to manually set the Sort Order number below.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Relative to section type</label>
                                            <select class="form-select anchor-type-select" data-device="desktop" onchange="pmOnAnchorTypeChange('desktop', this.value)">
                                                <option value="">-- None --</option>
                                                <?php foreach ($anchorSectionTypes as $aKey => $aLabel): ?>
                                                    <option value="<?php echo $aKey; ?>"><?php echo htmlspecialchars($aLabel); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="desktop_anchor_type" id="desktopAnchorType" value="">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">Specific section</label>
                                            <select name="desktop_anchor_id" id="desktopAnchorSection" class="form-select" onchange="pmApplyAnchorOrder('desktop')">
                                                <option value="">-- Pick a section type first --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Place</label>
                                            <select name="desktop_anchor_position" id="desktopAnchorPosition" class="form-select" onchange="pmApplyAnchorOrder('desktop')">
                                                <option value="after">After</option>
                                                <option value="before">Before</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Box(es) & URL(s) matching selected design -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-cloud-upload-alt text-primary"></i> Desktop Banner Image &amp; URL Configuration</h5>
                                    <span class="badge bg-secondary" id="desktopSlotsBadge">1 Image Slot</span>
                                </div>
                                <div class="admin-card-body">
                                    <!-- Dynamic Upload Containers populated by JS based on selected design -->
                                    <div id="desktopUploadContainer">
                                        <!-- Default Single Image Upload Slot -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label required-field fw-semibold">Desktop Banner Image Uploading</label>
                                                <div class="upload-dropzone" onclick="document.getElementById('desktopImgInput1').click()">
                                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Click to upload desktop image</p>
                                                    <small class="text-muted d-block" id="desktopDropzoneHint">Recommended size: 600×600 px (Square)</small>
                                                    <input type="file" name="desktop_image" id="desktopImgInput1" accept="image/*" onchange="previewUpload(this, 'desktopPreview1')">
                                                    <img id="desktopPreview1" class="upload-preview-img" alt="Desktop Preview">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Desktop URL</label>
                                                <input type="text" class="form-control" name="desktop_url" placeholder="https://printmont.com/category/visiting-cards">
                                                <small class="text-muted">Target landing page URL when user clicks this banner.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop Schedule, Status & Sort Order -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-calendar-alt text-primary"></i> Desktop Banner Status, Order &amp; Schedule</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Status</label>
                                            <select name="desktop_status" class="form-select">
                                                <option value="active">Show (Active)</option>
                                                <option value="inactive">Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop banner Sort Order number</label>
                                            <input type="number" step="0.01" class="form-control" name="desktop_sort_order" id="desktopSortOrder" value="0">
                                            <small class="text-muted">Auto-filled from section pick; editable by hand.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Start Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_start_date">
                                            <small class="text-muted">Leave blank for immediate show.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop End Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_end_date">
                                            <small class="text-muted">Leave blank for no expiration.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Empty state when no desktop design is selected -->
                        <div id="desktopEmptyPrompt" class="empty-design-prompt">
                            <i class="fas fa-mouse-pointer d-block"></i>
                            <h5 class="fw-bold text-dark">Please Select a Desktop Format Design Above</h5>
                            <p class="mb-0 text-muted">Click on any of the 6 Format Designs above to unlock image uploading, URL setup, and page placements.</p>
                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- 📱 MOBILE SECTION CONFIGURATION                          -->
                    <!-- ======================================================== -->
                    <div id="mobileSectionPane" style="display: none;">
                        
                        <!-- Service Banner: Show in Same Design as Desktop Option -->
                        <?php if ($subtype === 'service'): ?>
                        <div class="admin-card border-warning mb-3">
                            <div class="admin-card-body p-3 bg-light">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-clone text-warning me-2"></i>Show in Banner Same Design as Desktop?</h6>
                                        <p class="text-muted small mb-0">For Service Banners, if Yes, mobile view uses desktop images &amp; layout; separate mobile uploads are hidden.</p>
                                    </div>
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" role="switch" name="mobile_same_design" id="mobileSameDesign" value="yes" onchange="toggleMobileSameDesign(this.checked)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 1. Select Mobile Design -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-success"></i> Mobile Banner Select Design</h5>
                                <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">Required to reveal fields</span>
                            </div>
                            <div class="admin-card-body">
                                <p class="text-muted small mb-3">Select the mobile banner layout format. You can customize mobile independently from desktop.</p>

                                <div class="design-cards-grid">
                                    <!-- Mobile Format 1 -->
                                    <div class="design-card" data-mformat="mobile_format_1" onclick="selectMobileDesign('mobile_format_1')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-single-square">Square 1:1</div>
                                        </div>
                                        <div class="design-title">1 Format Design</div>
                                        <div class="design-badge">Single Square (1:1)</div>
                                    </div>

                                    <!-- Mobile Format 2 -->
                                    <div class="design-card" data-mformat="mobile_format_2" onclick="selectMobileDesign('mobile_format_2')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-full-rect">Landscape Banner</div>
                                        </div>
                                        <div class="design-title">2 Format Design</div>
                                        <div class="design-badge">16:9 Landscape Banner</div>
                                    </div>

                                    <!-- Mobile Format 3 -->
                                    <div class="design-card" data-mformat="mobile_format_3" onclick="selectMobileDesign('mobile_format_3')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-two-col">
                                                <span>Sq 1</span>
                                                <span>Sq 2</span>
                                            </div>
                                        </div>
                                        <div class="design-title">3 Format Design</div>
                                        <div class="design-badge">2 Square Banners Grid</div>
                                    </div>

                                    <!-- Mobile Format 4 -->
                                    <div class="design-card" data-mformat="mobile_format_4" onclick="selectMobileDesign('mobile_format_4')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-2x3">
                                                <span>1</span><span>2</span>
                                                <span>3</span><span>4</span>
                                                <span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">4 Format Design</div>
                                        <div class="design-badge">2 Columns × 3 Rows Grid</div>
                                    </div>

                                    <!-- Mobile Format 5 -->
                                    <div class="design-card" data-mformat="mobile_format_5" onclick="selectMobileDesign('mobile_format_5')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-slim-strip">Small Strip Banner</div>
                                        </div>
                                        <div class="design-title">5 Format Design</div>
                                        <div class="design-badge">Small Height Strip Banner</div>
                                    </div>

                                    <!-- Mobile Format 6 -->
                                    <div class="design-card" data-mformat="mobile_format_6" onclick="selectMobileDesign('mobile_format_6')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-3x2">
                                                <span>1</span><span>2</span><span>3</span>
                                                <span>4</span><span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">6 Format Design</div>
                                        <div class="design-badge">3 Columns × 2 Rows Grid</div>
                                    </div>
                                </div>

                                <input type="hidden" name="mobile_design" id="mobileDesignInput" value="">
                            </div>
                        </div>

                        <!-- 2. Conditional Section: Reveals ONLY when Mobile Design is chosen -->
                        <div id="mobileDependentFields" style="display: none;">

                            <!-- Dimension Recommendation Badge -->
                            <div class="dimension-info-pill" id="mobileDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="mobileDimText">Recommended Size: 600×600 px (Square 1:1)</span>
                            </div>

                            <!-- Mobile Placement Settings -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-map-marker-alt text-success"></i> Mobile Placement &amp; Category Settings</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <!-- Mobile Home page Category Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile Home page category select</label>
                                            <select name="mobile_home_category_id" class="form-select">
                                                <option value="">— Select Category for Mobile Home Page —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Home page carousel Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile Home page carousel Select</label>
                                            <select name="mobile_home_carousel_id" class="form-select">
                                                <option value="">— Select Mobile Home Carousel / Section —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Show on Other pages Select -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Mobile Show on Other pages Select</label>
                                            <select name="mobile_show_other_pages" id="mobileShowOtherSelect" class="form-select" onchange="toggleMobileOtherPages(this.value)">
                                                <option value="no">No (Home Page Only)</option>
                                                <option value="yes">Yes (Show on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <!-- Mobile Other pages Select category -->
                                        <div class="col-md-4 mobile-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Mobile Other pages Select category</label>
                                            <select name="mobile_other_category_id" class="form-select">
                                                <option value="">— Select Other Page Category —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Other pages Select Carousel -->
                                        <div class="col-md-4 mobile-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Mobile Other pages Select Carousel</label>
                                            <select name="mobile_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Carousel —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Relative Positioning (Position Before / After an existing section) -->
                                        <div class="col-12 mt-3 pt-3 border-top">
                                            <label class="form-label fw-bold mb-1"><i class="fas fa-arrows-alt-v text-success me-1"></i> Position This Banner on Mobile (optional)</label>
                                            <div class="text-muted small mb-2">Pick an existing section to position this banner before or after it on mobile. Leave blank to manually set the Sort Order number below.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Relative to section type</label>
                                            <select class="form-select anchor-type-select" data-device="mobile" onchange="pmOnAnchorTypeChange('mobile', this.value)">
                                                <option value="">-- None --</option>
                                                <?php foreach ($anchorSectionTypes as $aKey => $aLabel): ?>
                                                    <option value="<?php echo $aKey; ?>"><?php echo htmlspecialchars($aLabel); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="mobile_anchor_type" id="mobileAnchorType" value="">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">Specific section</label>
                                            <select name="mobile_anchor_id" id="mobileAnchorSection" class="form-select" onchange="pmApplyAnchorOrder('mobile')">
                                                <option value="">-- Pick a section type first --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Place</label>
                                            <select name="mobile_anchor_position" id="mobileAnchorPosition" class="form-select" onchange="pmApplyAnchorOrder('mobile')">
                                                <option value="after">After</option>
                                                <option value="before">Before</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Upload Box(es) & URL(s) -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-cloud-upload-alt text-success"></i> Mobile Banner Image &amp; URL Configuration</h5>
                                    <span class="badge bg-secondary" id="mobileSlotsBadge">1 Image Slot</span>
                                </div>
                                <div class="admin-card-body">
                                    <div id="mobileUploadContainer">
                                        <!-- Default Single Mobile Image Upload -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Mobile Banner image uploading</label>
                                                <div class="upload-dropzone" onclick="document.getElementById('mobileImgInput1').click()">
                                                    <i class="fas fa-mobile-alt fa-2x text-muted mb-2"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Click to upload mobile image</p>
                                                    <small class="text-muted d-block" id="mobileDropzoneHint">Recommended size: 600×600 px (Square)</small>
                                                    <input type="file" name="mobile_image" id="mobileImgInput1" accept="image/*" onchange="previewUpload(this, 'mobilePreview1')">
                                                    <img id="mobilePreview1" class="upload-preview-img" alt="Mobile Preview">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Mobile URL</label>
                                                <input type="text" class="form-control" name="mobile_url" placeholder="https://printmont.com/category/mugs">
                                                <small class="text-muted">Target landing page on mobile devices.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Schedule, Status & Sort Order -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-calendar-alt text-success"></i> Mobile Banner Status, Order &amp; Schedule</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile Banner status</label>
                                            <select name="mobile_status" class="form-select">
                                                <option value="active">Show (Active)</option>
                                                <option value="inactive">Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile banner Short Order number</label>
                                            <input type="number" step="0.01" class="form-control" name="mobile_sort_order" id="mobileSortOrder" value="0">
                                            <small class="text-muted">Auto-filled from section pick; editable by hand.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile Start date Calendar</label>
                                            <input type="date" class="form-control" name="mobile_start_date">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile End date Calendar</label>
                                            <input type="date" class="form-control" name="mobile_end_date">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Empty state when no mobile design is selected -->
                        <div id="mobileEmptyPrompt" class="empty-design-prompt">
                            <i class="fas fa-mobile-alt d-block"></i>
                            <h5 class="fw-bold text-dark">Please Select a Mobile Format Design Above</h5>
                            <p class="mb-0 text-muted">Click on any of the Mobile Format Designs above to configure mobile banner images and placements.</p>
                        </div>

                    </div>

                    <!-- Submit & Cancel Bar -->
                    <div class="admin-card">
                        <div class="admin-card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Please verify all selected formats, images, and target links before saving.</span>
                            </div>
                            <div>
                                <a href="view-banner.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                                    <i class="fas fa-save me-1"></i> Save &amp; Publish Banner
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
// Format Specifications metadata (Recommended dimensions & slot counts)
const FORMAT_SPECS = {
    'desktop_format_1': { slots: 1, name: '1 Format Design', dim: '600×600 px (Square 1:1)', label: 'Single Square Offer Banner' },
    'desktop_format_2': { slots: 1, name: '2 Format Design', dim: '1200×450 px / 1920×600 px (Landscape)', label: 'Full Width Offer Banner' },
    'desktop_format_3': { slots: 2, name: '3 Format Design', dim: '600×600 px each (2 Items)', label: '2 Square Banners Side-by-Side' },
    'desktop_format_4': { slots: 6, name: '4 Format Design', dim: '600×600 px each (2 Columns × 3 Rows)', label: '2×3 Square Grid (6 Banners)' },
    'desktop_format_5': { slots: 1, name: '5 Format Design', dim: '1920×220 px / 1200×180 px (Slim Strip)', label: 'Small Offer Banner Strip (Low height, full width)' },
    'desktop_format_6': { slots: 6, name: '6 Format Design', dim: '600×600 px each (3 Columns × 2 Rows)', label: '3×2 Square Grid (6 Banners)' },

    'mobile_format_1': { slots: 1, name: '1 Format Design', dim: '600×600 px (Square 1:1)', label: 'Single Square Mobile Banner' },
    'mobile_format_2': { slots: 1, name: '2 Format Design', dim: '800×450 px (16:9 Landscape)', label: 'Mobile Full Width Banner' },
    'mobile_format_3': { slots: 2, name: '3 Format Design', dim: '600×600 px each (2 Items)', label: '2 Square Mobile Banners' },
    'mobile_format_4': { slots: 6, name: '4 Format Design', dim: '600×600 px each (2 Columns × 3 Rows)', label: '2×3 Mobile Grid (6 Banners)' },
    'mobile_format_5': { slots: 1, name: '5 Format Design', dim: '800×200 px (Slim Ribbon)', label: 'Small Height Mobile Strip Banner' },
    'mobile_format_6': { slots: 6, name: '6 Format Design', dim: '600×600 px each (3 Columns × 2 Rows)', label: '3×2 Mobile Grid (6 Banners)' }
};

// Switch between Desktop & Mobile configuration tabs
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

// 🖥️ Select Desktop Design
function selectDesktopDesign(fmt) {
    document.querySelectorAll('.design-card[data-format]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-format="${fmt}"]`);
    if (target) target.classList.add('active');

    document.getElementById('desktopDesignInput').value = fmt;

    // Show dependent fields & hide empty prompt
    document.getElementById('desktopDependentFields').style.display = 'block';
    document.getElementById('desktopEmptyPrompt').style.display = 'none';

    // Update dimensions pill
    const spec = FORMAT_SPECS[fmt];
    if (spec) {
        document.getElementById('desktopDimText').innerHTML = `<strong>${spec.name}:</strong> ${spec.label} &bull; Recommended Dimensions: <strong>${spec.dim}</strong>`;
        renderDesktopSlots(spec);
    }
}

// Render dynamic upload slots based on format design
function renderDesktopSlots(spec) {
    const container = document.getElementById('desktopUploadContainer');
    const badge = document.getElementById('desktopSlotsBadge');
    badge.innerText = `${spec.slots} Image Slot${spec.slots > 1 ? 's' : ''}`;

    if (spec.slots === 1) {
        container.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required-field fw-semibold">Desktop Banner Image Uploading</label>
                    <div class="upload-dropzone" onclick="document.getElementById('desktopImgInput1').click()">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-1 text-dark fw-semibold">Click to upload desktop image</p>
                        <small class="text-muted d-block">Recommended size: ${spec.dim}</small>
                        <input type="file" name="desktop_image" id="desktopImgInput1" accept="image/*" onchange="previewUpload(this, 'desktopPreview1')">
                        <img id="desktopPreview1" class="upload-preview-img" alt="Desktop Preview">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Desktop URL</label>
                    <input type="text" class="form-control" name="desktop_url" placeholder="https://printmont.com/category/visiting-cards">
                    <small class="text-muted">Target landing page URL when user clicks this banner.</small>
                </div>
            </div>
        `;
    } else {
        // Multi-image slots (e.g. 2 for Format 3, 6 for Format 4 and 6)
        let html = `<div class="multi-slot-grid">`;
        for (let i = 1; i <= spec.slots; i++) {
            html += `
                <div class="slot-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small">Slot #${i}</strong>
                        <span class="badge bg-light text-secondary border">${spec.dim}</span>
                    </div>
                    <div class="upload-dropzone mb-2" onclick="document.getElementById('dSlotInput_${i}').click()">
                        <i class="fas fa-image text-muted"></i>
                        <small class="d-block text-dark mt-1">Upload Slot ${i}</small>
                        <input type="file" name="desktop_images[]" id="dSlotInput_${i}" accept="image/*" onchange="previewUpload(this, 'dSlotPrev_${i}')">
                        <img id="dSlotPrev_${i}" class="upload-preview-img" alt="Slot ${i} Preview">
                    </div>
                    <label class="form-label small fw-semibold mb-1">Slot ${i} URL</label>
                    <input type="text" class="form-control form-control-sm" name="desktop_urls[]" placeholder="https://printmont.com/...">
                </div>
            `;
        }
        html += `</div>`;
        container.innerHTML = html;
    }
}

// 📱 Select Mobile Design
function selectMobileDesign(fmt) {
    document.querySelectorAll('.design-card[data-mformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-mformat="${fmt}"]`);
    if (target) target.classList.add('active');

    document.getElementById('mobileDesignInput').value = fmt;

    // Show dependent fields & hide empty prompt
    document.getElementById('mobileDependentFields').style.display = 'block';
    document.getElementById('mobileEmptyPrompt').style.display = 'none';

    // Update dimensions pill
    const spec = FORMAT_SPECS[fmt];
    if (spec) {
        document.getElementById('mobileDimText').innerHTML = `<strong>${spec.name}:</strong> ${spec.label} &bull; Recommended Dimensions: <strong>${spec.dim}</strong>`;
        renderMobileSlots(spec);
    }
}

function renderMobileSlots(spec) {
    const container = document.getElementById('mobileUploadContainer');
    const badge = document.getElementById('mobileSlotsBadge');
    badge.innerText = `${spec.slots} Image Slot${spec.slots > 1 ? 's' : ''}`;

    if (spec.slots === 1) {
        container.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mobile Banner image uploading</label>
                    <div class="upload-dropzone" onclick="document.getElementById('mobileImgInput1').click()">
                        <i class="fas fa-mobile-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-1 text-dark fw-semibold">Click to upload mobile image</p>
                        <small class="text-muted d-block">Recommended size: ${spec.dim}</small>
                        <input type="file" name="mobile_image" id="mobileImgInput1" accept="image/*" onchange="previewUpload(this, 'mobilePreview1')">
                        <img id="mobilePreview1" class="upload-preview-img" alt="Mobile Preview">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mobile URL</label>
                    <input type="text" class="form-control" name="mobile_url" placeholder="https://printmont.com/category/mugs">
                    <small class="text-muted">Target landing page on mobile devices.</small>
                </div>
            </div>
        `;
    } else {
        let html = `<div class="multi-slot-grid">`;
        for (let i = 1; i <= spec.slots; i++) {
            html += `
                <div class="slot-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small">Mobile Slot #${i}</strong>
                        <span class="badge bg-light text-secondary border">${spec.dim}</span>
                    </div>
                    <div class="upload-dropzone mb-2" onclick="document.getElementById('mSlotInput_${i}').click()">
                        <i class="fas fa-mobile text-muted"></i>
                        <small class="d-block text-dark mt-1">Upload Slot ${i}</small>
                        <input type="file" name="mobile_images[]" id="mSlotInput_${i}" accept="image/*" onchange="previewUpload(this, 'mSlotPrev_${i}')">
                        <img id="mSlotPrev_${i}" class="upload-preview-img" alt="Slot ${i} Preview">
                    </div>
                    <label class="form-label small fw-semibold mb-1">Slot ${i} URL</label>
                    <input type="text" class="form-control form-control-sm" name="mobile_urls[]" placeholder="https://printmont.com/...">
                </div>
            `;
        }
        html += `</div>`;
        container.innerHTML = html;
    }
}

// Live image preview helper
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

// Toggle other pages dropdowns
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

// Relative section positioning (Document 7 & 8 spec)
const PM_ANCHOR_OPTIONS = <?php echo json_encode($anchorOptionsByDevice, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function pmOnAnchorTypeChange(device, anchorType) {
    document.getElementById(`${device}AnchorType`).value = anchorType;
    const sectionSelect = document.getElementById(`${device}AnchorSection`);
    const options = (PM_ANCHOR_OPTIONS[device] && PM_ANCHOR_OPTIONS[device][anchorType]) || [];
    sectionSelect.innerHTML = '<option value="">-- Select --</option>' +
        options.map(o => `<option value="${o.id}">${o.label.replace(/</g, '&lt;')}</option>`).join('');
    pmApplyAnchorOrder(device);
}

function pmApplyAnchorOrder(device) {
    const anchorType = document.getElementById(`${device}AnchorType`).value;
    const anchorId = document.getElementById(`${device}AnchorSection`).value;
    if (!anchorType || !anchorId) return;
    const position = document.getElementById(`${device}AnchorPosition`).value;
    const options = (PM_ANCHOR_OPTIONS[device] && PM_ANCHOR_OPTIONS[device][anchorType]) || [];
    const idx = options.findIndex(o => String(o.id) === String(anchorId));
    if (idx === -1) return;
    const anchorOrder = parseFloat(options[idx].sort_order) || 0;
    let suggested;
    if (position === 'before') {
        const prevOrder = idx > 0 ? parseFloat(options[idx - 1].sort_order) || 0 : null;
        suggested = prevOrder !== null ? (prevOrder + anchorOrder) / 2 : anchorOrder - 1;
    } else {
        const nextOrder = idx < options.length - 1 ? parseFloat(options[idx + 1].sort_order) || 0 : null;
        suggested = nextOrder !== null ? (anchorOrder + nextOrder) / 2 : anchorOrder + 1;
    }
    document.getElementById(`${device}SortOrder`).value = suggested.toFixed(2);
}

function toggleMobileSameDesign(isSame) {
    const uploadCard = document.getElementById('mobileUploadContainer') ? document.getElementById('mobileUploadContainer').closest('.admin-card') : null;
    if (uploadCard) {
        uploadCard.style.display = isSame ? 'none' : '';
    }
}
</script>
</body>
</html>
