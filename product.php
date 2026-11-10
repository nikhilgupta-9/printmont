<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');
require_once(__DIR__ . '/controllers/ProductController.php');
require_once(__DIR__ . '/config/customization-options-schema.php');
require_once(__DIR__ . '/config/product-info-banners-schema.php');

$database = new Database();
$db       = $database->getConnection();
ensureProductInfoBannersTable($db);

$productController = new ProductController($db);
$categoryCtrl      = new CategoryController($db);
$mainCategories    = $categoryCtrl->getMainCategories();
// All Sub Categories / Sub-Sub Categories (any parent) — used to build the "Multi-Category
// Placement" checkbox grids for Section 4, grouped by their parent name.
$allSubCategories    = $categoryCtrl->getSubCategories();
$allSubSubCategories = $categoryCtrl->getSubSubCategories();

// ── Customization library (customization-options.php) — section 7 ticks options from here ──
ensureCustomizationOptionsTable($db);
$customLibrary = [];
$libRes = $db->query("SELECT id, name, input_type, option_values, help, is_required FROM customization_options WHERE status = 'active' ORDER BY display_order ASC, name ASC");
while ($libRes && ($row = $libRes->fetch_assoc())) {
    $customLibrary[] = [
        'id'          => (int) $row['id'],
        'name'        => $row['name'],
        'type'        => $row['input_type'],
        'help'        => $row['help'] ?? '',
        'is_required' => (int) $row['is_required'],
        'values'      => json_decode($row['option_values'] ?? '[]', true) ?: [],
    ];
}

// ── Add vs Edit: product.php?id=123 opens this same form pre-filled for editing ──
$editId      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit      = $editId > 0;
$editProduct = null;
if ($isEdit) {
    $editProduct = $productController->getProductById($editId);
    if (!$editProduct) {
        $_SESSION['error_message'] = 'Product not found.';
        header('Location: view-products.php');
        exit;
    }
}

$error_message   = '';
$success_message = '';

$assignedCatIds = [];
$existingInfoBanners = [];
if ($isEdit && !empty($editId)) {
    $assignedRes = $db->query("SELECT category_id FROM product_categories WHERE product_id = " . (int)$editId);
    if ($assignedRes) {
        while ($ar = $assignedRes->fetch_assoc()) {
            $assignedCatIds[] = (int)$ar['category_id'];
        }
    }

    $infoBannerRes = $db->query("SELECT id, title, description, button_text, button_link, image
        FROM product_info_banners WHERE product_id = " . (int)$editId . " ORDER BY display_order ASC, id ASC");
    if ($infoBannerRes) {
        while ($ib = $infoBannerRes->fetch_assoc()) {
            $existingInfoBanners[] = $ib;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $isEdit
        ? $productController->updateProduct($editId, $_POST, $_FILES)
        : $productController->addProduct($_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = $isEdit ? 'Product updated successfully!' : 'Product added successfully!';
        header('Location: view-products.php');
        exit;
    } else {
        $error_message = $result['error'];
        if ($isEdit) {
            $editProduct = $productController->getProductById($editId);
        }
    }
}

// Existing images for the edit form
$editThumb   = $isEdit ? ($editProduct['thumbnail_image'] ?? '') : '';
$editMain    = $isEdit ? ($editProduct['main_images'] ?? []) : [];
$editGallery = $isEdit ? (json_decode($editProduct['gallery_images'] ?? '[]', true) ?: []) : [];

// Multi-select Sub Category / Sub Sub Category placement (Section 4) — JSON arrays of extra ids
$assignedSubCatIds = $isEdit ? array_map('intval', json_decode($editProduct['additional_sub_category_ids'] ?? '[]', true) ?: []) : [];
$assignedSubSubCatIds = $isEdit ? array_map('intval', json_decode($editProduct['additional_sub_sub_category_ids'] ?? '[]', true) ?: []) : [];

// Existing variant data for Tab 2 (Colors with dedicated images, Sizes, Bulk Tiers)
$existingColorVariants = [];
if ($isEdit && !empty($editProduct['color_attributes'])) {
    $existingColorVariants = json_decode($editProduct['color_attributes'], true) ?: [];
}
if (empty($existingColorVariants) && $isEdit && !empty($editProduct['color'])) {
    $cParts = explode(',', $editProduct['color']);
    foreach ($cParts as $cp) {
        $cp = trim($cp);
        if ($cp) {
            $existingColorVariants[] = ['name' => $cp, 'hex' => '#000000', 'price' => 0, 'image' => '', 'text' => ''];
        }
    }
}
if ($isEdit && !empty($editProduct['color_images'])) {
    $ciMap = json_decode($editProduct['color_images'], true) ?: [];
    foreach ($existingColorVariants as &$ecv) {
        if (empty($ecv['image']) && !empty($ciMap[$ecv['name']])) {
            $ecv['image'] = $ciMap[$ecv['name']];
        }
    }
    unset($ecv);
}

$existingSizeVariants = [];
if ($isEdit && !empty($editProduct['size_attributes'])) {
    $existingSizeVariants = json_decode($editProduct['size_attributes'], true) ?: [];
}

$existingBulkTiers = [];
if ($isEdit && !empty($editProduct['bulk_price_variance'])) {
    $existingBulkTiers = json_decode($editProduct['bulk_price_variance'], true) ?: [];
} elseif ($isEdit && !empty($editProduct['quantity_price_breaks'])) {
    $existingBulkTiers = json_decode($editProduct['quantity_price_breaks'], true) ?: [];
}

function pmImgSrc($path) {
    $path = (string) $path;
    return (strpos($path, 'http') === 0 || strpos($path, '//') === 0) ? $path : ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title><?php echo $isEdit ? 'Edit Product' : 'Add Product'; ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 1 !important; }

        /* ── Sticky Quick Navigation Bar ── */
        .product-sticky-header {
            position: -webkit-sticky;
            position: sticky;
            top: 56px;
            z-index: 1010;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .section-quick-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }
        .quick-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 500;
            color: #495057;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            cursor: pointer;
            border: none;
        }
        .quick-pill:hover {
            background: #e9ecef;
            color: #0d6efd;
            text-decoration: none;
        }
        .quick-pill.active {
            background: #0d6efd !important;
            color: #ffffff !important;
            border-color: #0d6efd !important;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.35);
        }
        .quick-pill .pill-num {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(0,0,0,0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }
        .quick-pill.active .pill-num {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }

        /* ── Product Section Cards ── */
        .product-section-card {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
            scroll-margin-top: 180px;
            transition: border-color 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        .product-section-card:hover {
            border-color: #b0c4de;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .product-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .product-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 600;
            color: #212529;
            margin: 0;
        }
        .product-section-badge {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #e9ecef;
            color: #495057;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s;
        }
        .product-section-card:focus-within .product-section-badge {
            background: #0d6efd;
            color: #fff;
        }
        .product-section-body {
            padding: 22px 24px;
        }

        /* ── Utility ── */
        .required-star { color: #dc3545; font-weight: bold; }
        .section-heading { font-size: 15px; font-weight: 600; color: #343a40; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; margin-bottom: 20px; }

        /* ── Dynamic rows ── */
        .dynamic-table thead th { font-size: 12px; font-weight: 600; background: #f8f9fa; }
        .dynamic-table .btn-remove-row { padding: 2px 8px; }
        .add-row-btn { font-size: 13px; }

        /* ── Toggle switch ── */
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fafafa;
            margin-bottom: 10px;
            transition: background 0.15s, border-color 0.15s;
        }
        .toggle-row:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
        }
        .toggle-row:last-child { margin-bottom: 0; }
        .toggle-label-wrap { flex: 1; }
        .toggle-label-wrap strong { display: block; font-size: 13.5px; margin-bottom: 2px; color: #212529; }
        .toggle-label-wrap small { color: #6c757d; font-size: 12px; }
        .form-switch .form-check-input { width: 2.8em; height: 1.4em; cursor: pointer; }

        /* ── Image upload zones ── */
        .upload-zone {
            border: 2px dashed #ced4da;
            border-radius: 8px;
            padding: 26px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background-color .2s;
            position: relative;
            background: #fafafa;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: #0d6efd; background: #f0f4ff; }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-zone-icon { font-size: 32px; color: #adb5bd; margin-bottom: 6px; }
        .upload-zone p { margin: 0; color: #6c757d; font-size: 13px; }
        .image-preview-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .image-thumb { position: relative; width: 90px; height: 90px; }
        .image-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6; }
        .image-thumb .remove-img { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%; background: #dc3545; color: #fff; border: none; font-size: 11px; display: flex; align-items: center; justify-content: center; cursor: pointer; }

        /* ── SEO preview ── */
        .google-preview { border: 1px solid #dee2e6; border-radius: 8px; padding: 18px 20px; background: #fff; margin-top: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .gp-title { font-size: 18px; color: #1a0dab; font-family: Arial, sans-serif; cursor: pointer; }
        .gp-title:hover { text-decoration: underline; }
        .gp-url { color: #006621; font-size: 13px; margin: 2px 0; }
        .gp-desc { color: #545454; font-size: 14px; font-family: Arial, sans-serif; }
        .char-counter { font-size: 12px; }
        .char-counter.ok { color: #198754; font-weight: 600; }
        .char-counter.warn { color: #ffc107; font-weight: 600; }
        .char-counter.over { color: #dc3545; font-weight: 600; }
        .seo-score { font-size: 28px; font-weight: 700; }

        /* ── Addon search ── */
        .addon-search-results { position: absolute; z-index: 100; width: 100%; background: #fff; border: 1px solid #dee2e6; border-radius: 0 0 6px 6px; max-height: 260px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .addon-result-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .addon-result-item:hover { background: #f8f9fa; }
        .addon-result-item img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
        .addon-cards { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .addon-card { display: flex; align-items: center; gap: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px 12px; }
        .addon-card img { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
        .addon-card .remove-addon { background: none; border: none; color: #dc3545; font-size: 16px; cursor: pointer; margin-left: auto; }

        /* ── Bottom action bar ── */
        .product-bottom-bar {
            position: -webkit-sticky;
            position: sticky;
            bottom: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            border: 1px solid #dee2e6;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
            padding: 14px 22px;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        /* ── Back to Top Floating Button ── */
        .btn-back-to-top {
            position: fixed;
            bottom: 75px;
            right: 25px;
            z-index: 1050;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #0d6efd;
            color: #fff;
            border: none;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.35);
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }
        .btn-back-to-top:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
            color: #fff;
        }

        /* ── Customization key-value fields (section 7) ── */
        .kv-head, .kv-row { display: grid; grid-template-columns: minmax(160px, 1fr) minmax(220px, 1.3fr) minmax(200px, 1fr) 70px; gap: 12px; align-items: start; }
        .kv-head { padding: 0 12px 6px; font-size: 12px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: .3px; }
        .kv-list { display: flex; flex-direction: column; gap: 8px; }
        .kv-row { padding: 12px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; }
        .kv-row .kv-values { margin-top: 8px; }
        .kv-row .kv-values textarea { font-size: 13px; }
        .kv-row .kv-hint { font-size: 11px; color: #6c757d; margin-top: 4px; }
        .kv-preview { display: grid; grid-template-columns: 90px 1fr; gap: 8px; align-items: start; padding: 8px; border-radius: 6px; background: #f8f9fa; font-size: 12px; min-height: 38px; }
        .kv-preview .kv-p-label { color: #495057; padding-top: 4px; word-break: break-word; }
        .kv-preview .kv-p-ctrl { min-width: 0; }
        .kv-preview .kv-chip { display: inline-block; padding: 2px 10px; margin: 0 4px 4px 0; border: 1px solid #ced4da; border-radius: 12px; background: #fff; }
        .kv-preview .kv-chip:first-child { background: #5b2a86; border-color: #5b2a86; color: #fff; }
        .kv-tools { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        @media (max-width: 767px) { .kv-row { grid-template-columns: 1fr; } .kv-tools { flex-direction: row; justify-content: space-between; align-items: center; } }

        /* ── Customization library picker (section 7) ── */
        .custom-library-list { display: flex; flex-direction: column; gap: 8px; max-height: 640px; overflow-y: auto; padding-right: 4px; }
        .cl-card { border: 1px solid #dee2e6; border-radius: 8px; background: #fff; }
        .cl-card.is-on { border-color: #3b7ddd; box-shadow: 0 0 0 1px #3b7ddd inset; }
        .cl-head { display: flex; align-items: center; gap: 12px; padding: 8px 12px; }
        .cl-head .cl-use { width: 18px; height: 18px; margin: 0; cursor: pointer; flex-shrink: 0; }
        .cl-name { font-weight: 600; flex: 1; min-width: 0; cursor: pointer; margin: 0; }
        .cl-type { font-size: 11px; color: #6c757d; background: #f1f3f5; border-radius: 4px; padding: 2px 8px; }
        .cl-body { padding: 0 12px 12px 42px; }
        .cl-help { font-size: 12px; color: #6c757d; margin-bottom: 8px; }
        .cl-values { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px; }
        .cl-value { display: flex; align-items: center; gap: 8px; border: 1px solid #e9ecef; border-radius: 6px; padding: 4px 8px; }
        .cl-value label { flex: 1; min-width: 0; margin: 0; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .cl-dot { width: 14px; height: 14px; border-radius: 50%; border: 1px solid #ccc; flex-shrink: 0; }
        .cl-value .input-group { width: 120px; flex-shrink: 0; }
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
                <div class="row mb-3">
                    <div class="col-auto d-none d-sm-block">
                        <?php if ($isEdit): ?>
                            <h3><strong>Edit</strong> Product <span class="text-muted fs-5">#<?php echo $editId; ?></span></h3>
                            <div class="text-muted small"><?php echo htmlspecialchars($editProduct['name'] ?? ''); ?></div>
                        <?php else: ?>
                            <h3><strong>Add</strong> New Product</h3>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="view-products.php" class="btn btn-outline-secondary me-2">View Products</a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="productForm" novalidate>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="full_form" value="1">
                    <?php endif; ?>

                    <!-- ── Sticky Quick-Action & Section Navigation Header ── -->
                    <div class="product-sticky-header">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="fa fa-layer-group me-1"></i> Single-Page Form
                                </span>
                                <span class="text-muted small d-none d-md-inline">All 12 sections on one page. Jump to any section or save anytime.</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <a href="view-products.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fa fa-arrow-left me-1"></i> Cancel
                                </a>
                                <button type="submit" name="status" value="draft" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-floppy-disk me-1"></i> Save Draft
                                </button>
                                <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                                    <i class="fa fa-check me-1"></i> <?php echo $isEdit ? 'Update Product' : 'Publish Product'; ?>
                                </button>
                            </div>
                        </div>

                        <!-- Quick Jump Section Pills (Document 1-12 Architecture) -->
                        <div class="section-quick-pills" id="quickPillsBar">
                            <button type="button" class="quick-pill active" data-target="tab-1"><span class="pill-num">1</span> <i class="fa fa-circle-info"></i> General</button>
                            <button type="button" class="quick-pill" data-target="tab-2"><span class="pill-num">2</span> <i class="fa fa-sliders"></i> Product Attributes</button>
                            <button type="button" class="quick-pill" data-target="tab-3"><span class="pill-num">3</span> <i class="fa fa-list-check"></i> Features</button>
                            <button type="button" class="quick-pill" data-target="tab-4"><span class="pill-num">4</span> <i class="fa fa-sitemap"></i> Links</button>
                            <button type="button" class="quick-pill" data-target="tab-5"><span class="pill-num">5</span> <i class="fa fa-images"></i> Media</button>
                            <button type="button" class="quick-pill" data-target="tab-6"><span class="pill-num">6</span> <i class="fa fa-magnifying-glass-chart"></i> SEO</button>
                            <button type="button" class="quick-pill" data-target="tab-7"><span class="pill-num">7</span> <i class="fa fa-toggle-on"></i> Status Show &amp; Hide</button>
                            <button type="button" class="quick-pill" data-target="tab-8"><span class="pill-num">8</span> <i class="fa fa-wand-magic-sparkles"></i> Customize</button>
                            <button type="button" class="quick-pill" data-target="tab-9"><span class="pill-num">9</span> <i class="fa fa-truck-fast"></i> Shipping</button>
                            <button type="button" class="quick-pill" data-target="tab-10"><span class="pill-num">10</span> <i class="fa fa-rotate-left"></i> Cancellation</button>
                            <button type="button" class="quick-pill" data-target="tab-11"><span class="pill-num">11</span> <i class="fa fa-money-bill-wave"></i> COD</button>
                            <button type="button" class="quick-pill" data-target="tab-12"><span class="pill-num">12</span> <i class="fa fa-puzzle-piece"></i> Addons</button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════════════════
                         SECTION 1 – GENERAL INFO & PRICING
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-1">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">1</span>
                                <i class="fa fa-circle-info text-primary"></i> General Information &amp; Pricing
                            </h5>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa fa-asterisk me-1"></i>Required Fields</span>
                        </div>
                        <div class="product-section-body">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Product Name <span class="required-star">*</span></label>
                                        <input type="text" class="form-control" name="product_name" id="product_name"
                                               placeholder="Enter full product name (min 10 chars)" required
                                               value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SKU Code <span class="required-star">*</span></label>
                                        <input type="text" class="form-control" name="sku_code" id="sku_code"
                                               placeholder="e.g. PM-MUG-001" required
                                               value="<?php echo htmlspecialchars($_POST['sku_code'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Product Slug <span class="required-star">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted" style="font-size:12px">yoursite.com/product/</span>
                                            <input type="text" class="form-control" name="product_slug" id="product_slug"
                                                   placeholder="product-url-slug" required
                                                   value="<?php echo htmlspecialchars($_POST['product_slug'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label">Regular Price / MRP (₹) <span class="required-star">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="regular_price" id="regular_price"
                                                   placeholder="0.00" required min="0"
                                                   value="<?php echo htmlspecialchars($_POST['regular_price'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Offer Price (₹)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="offer_price" id="offer_price"
                                                   placeholder="0.00" min="0"
                                                   value="<?php echo htmlspecialchars($_POST['offer_price'] ?? ''); ?>">
                                        </div>
                                        <div id="discount_pct" class="text-success mt-1" style="font-size:12px"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Stock Quantity <span class="required-star">*</span></label>
                                        <input type="number" class="form-control" name="product_quantity" required min="0"
                                               value="<?php echo htmlspecialchars($_POST['product_quantity'] ?? '0'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Min Order Qty</label>
                                        <input type="number" class="form-control" name="minimum_quantity" min="1"
                                               value="<?php echo htmlspecialchars($_POST['minimum_quantity'] ?? '1'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Out of Stock Status</label>
                                        <select class="form-select" name="out_of_stock_status">
                                            <option value="in_stock">In Stock</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                            <option value="pre_order">Pre Order</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Bulk Order Pricing Tiers now live on Tab 2 (Filters & Master Attributes → "bulk_quantity" attribute),
                                     so they aren't duplicated here. The old copy here and the Tab 2 copy shared the same
                                     bulk_min_qty[]/bulk_max_qty[]/bulk_price[] field names, so every save merged both tables'
                                     rows into one array and tiers doubled each time the product was edited and saved. -->

                                <!-- Rich text editors -->
                                <div class="mt-4">
                                    <label class="form-label">Short Description <span class="required-star">*</span></label>
                                    <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Product Long Description</label>
                                    <textarea class="form-control" name="long_description" id="long_description" rows="5"></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Instructions / Usage</label>
                                    <textarea class="form-control" name="instructions" id="instructions" rows="4"></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Delivery Information</label>
                                    <textarea class="form-control" name="delivery_info" id="delivery_info" rows="4"></textarea>
                                </div>

                                <!-- Point 13: Product Information Image & Text Upload -->
                                <div class="mt-4">
                                    <label class="form-label fw-semibold">13. Product Information Image &amp; Text Upload</label>
                                    <p class="text-muted small mt-0 mb-2">
                                        Shown on the product page above Ratings &amp; Reviews. Add one or more banners — each can have an image, title, description and a button. Leave a row blank to skip it.
                                    </p>
                                    <div id="infoBannerList">
                                        <?php foreach ($existingInfoBanners as $ib): ?>
                                            <div class="info-banner-row border rounded p-3 mb-3">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Image</label>
                                                        <?php if (!empty($ib['image'])): ?>
                                                            <div class="image-thumb mb-2"><img src="<?php echo htmlspecialchars(pmImgSrc($ib['image'])); ?>" alt="" onerror="this.src='img/icons/icon-48x48.png'"></div>
                                                        <?php endif; ?>
                                                        <input type="hidden" name="info_banner_existing_image[]" value="<?php echo htmlspecialchars($ib['image'] ?? ''); ?>">
                                                        <input type="file" class="form-control form-control-sm" name="info_banner_image[]" accept="image/*">
                                                    </div>
                                                    <div class="col-md-8">
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small">Title</label>
                                                                <input type="text" class="form-control form-control-sm" name="info_banner_title[]" value="<?php echo htmlspecialchars($ib['title'] ?? ''); ?>" placeholder="Banner title">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small">Button Text</label>
                                                                <input type="text" class="form-control form-control-sm" name="info_banner_button_text[]" value="<?php echo htmlspecialchars($ib['button_text'] ?? ''); ?>" placeholder="e.g. Learn More">
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label small">Description</label>
                                                                <textarea class="form-control form-control-sm" name="info_banner_description[]" rows="2" placeholder="Short description"><?php echo htmlspecialchars($ib['description'] ?? ''); ?></textarea>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label small">Button Link</label>
                                                                <input type="text" class="form-control form-control-sm" name="info_banner_button_link[]" value="<?php echo htmlspecialchars($ib['button_link'] ?? ''); ?>" placeholder="/category/... or https://...">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end mt-2">
                                                    <input type="hidden" class="info-banner-remove-flag" name="info_banner_remove[]" value="0">
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-info-banner">Remove this banner</button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="addInfoBannerRow"><i class="fa fa-plus me-1"></i> Add Banner</button>
                                </div>
                                <script>
                                (function () {
                                    var list = document.getElementById('infoBannerList');
                                    var addBtn = document.getElementById('addInfoBannerRow');
                                    function rowTemplate() {
                                        var div = document.createElement('div');
                                        div.className = 'info-banner-row border rounded p-3 mb-3';
                                        div.innerHTML =
                                            '<div class="row g-3">' +
                                                '<div class="col-md-4">' +
                                                    '<label class="form-label small">Image</label>' +
                                                    '<input type="hidden" name="info_banner_existing_image[]" value="">' +
                                                    '<input type="file" class="form-control form-control-sm" name="info_banner_image[]" accept="image/*">' +
                                                '</div>' +
                                                '<div class="col-md-8">' +
                                                    '<div class="row g-2">' +
                                                        '<div class="col-md-6"><label class="form-label small">Title</label><input type="text" class="form-control form-control-sm" name="info_banner_title[]" placeholder="Banner title"></div>' +
                                                        '<div class="col-md-6"><label class="form-label small">Button Text</label><input type="text" class="form-control form-control-sm" name="info_banner_button_text[]" placeholder="e.g. Learn More"></div>' +
                                                        '<div class="col-12"><label class="form-label small">Description</label><textarea class="form-control form-control-sm" name="info_banner_description[]" rows="2" placeholder="Short description"></textarea></div>' +
                                                        '<div class="col-12"><label class="form-label small">Button Link</label><input type="text" class="form-control form-control-sm" name="info_banner_button_link[]" placeholder="/category/... or https://..."></div>' +
                                                    '</div>' +
                                                '</div>' +
                                            '</div>' +
                                            '<div class="text-end mt-2">' +
                                                '<input type="hidden" class="info-banner-remove-flag" name="info_banner_remove[]" value="0">' +
                                                '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-info-banner">Remove this banner</button>' +
                                            '</div>';
                                        return div;
                                    }
                                    addBtn.addEventListener('click', function () { list.appendChild(rowTemplate()); });
                                    list.addEventListener('click', function (e) {
                                        var btn = e.target.closest('.btn-remove-info-banner');
                                        if (!btn) return;
                                        var row = btn.closest('.info-banner-row');
                                        row.querySelector('.info-banner-remove-flag').value = '1';
                                        row.style.display = 'none';
                                    });
                                })();
                                </script>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════════════════
                         SECTION 2 – PRODUCT ATTRIBUTES & VARIATIONS
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-2">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">2</span>
                                <i class="fa fa-sliders text-primary"></i> Product Attributes
                            </h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Sizes, Colors, Variants &amp; Specs</span>
                        </div>
                        <div class="product-section-body">
                            <p class="text-muted small mb-3">
                                Manage all physical product variants and specifications. Colors have dedicated variant images that swap live when selected on the storefront. Admin has full freedom to add custom attributes with prices.
                            </p>

                            <!-- 1. SIZES & SIZE CHART -->
                            <div class="border rounded p-3 bg-light shadow-sm mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="fa fa-ruler-combined text-primary"></i> Product Sizes &amp; Dimensions
                                    </label>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_sizes" id="tab2_show_sizes" value="1" <?php echo (!empty($editProduct['show_sizes']) || !empty($existingSizeVariants)) ? 'checked' : ''; ?> onchange="document.getElementById('sizeVariantsWrapper').style.display = this.checked ? 'block' : 'none';">
                                        <label class="form-check-label small fw-semibold" for="tab2_show_sizes">Enable Sizes on Product</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">
                                    Configure sizes (e.g. S, M, L, XL, XXL) available for this product with optional price add-ons.
                                </p>

                                <div id="sizeVariantsWrapper" style="<?php echo (!empty($editProduct['show_sizes']) || !empty($existingSizeVariants)) ? '' : 'display:none;'; ?>">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm dynamic-table bg-white align-middle mb-2" id="tab2SizeTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:60%;">Size Name / Label</th>
                                                    <th style="width:30%;">Price Add-on (₹)</th>
                                                    <th style="width:10%; text-align:center;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab2SizeBody">
                                                <?php if (!empty($existingSizeVariants)): ?>
                                                    <?php foreach ($existingSizeVariants as $sRow): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm" name="size_names[]" value="<?php echo htmlspecialchars($sRow['name'] ?? (is_string($sRow) ? $sRow : '')); ?>" placeholder="e.g. M / Large" required>
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text">₹</span>
                                                                    <input type="number" step="0.01" min="0" class="form-control" name="size_prices[]" value="<?php echo htmlspecialchars($sRow['price'] ?? 0); ?>" placeholder="0.00">
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()" title="Remove size">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="pmAddSizeRow()">
                                        <i class="fa fa-plus me-1"></i> Add Another Size
                                    </button>

                                    <!-- Size Image Upload (Size Chart) -->
                                    <div class="mt-3 p-3 bg-white rounded border">
                                        <label class="form-label fw-bold d-flex align-items-center gap-2 mb-1">
                                            <i class="fa fa-chart-simple text-primary"></i> Size Guide / Size Chart Image Upload
                                        </label>
                                        <p class="text-muted small mb-2">Upload a size chart / measurement guide image. Customers will see a "Size Guide" button on the product details page.</p>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($editProduct['size_chart_image'])): ?>
                                                <div class="d-flex align-items-center gap-2 border p-1 rounded bg-light">
                                                    <a href="<?php echo htmlspecialchars(pmImgSrc($editProduct['size_chart_image'])); ?>" target="_blank" title="View Current Size Chart">
                                                        <img src="<?php echo htmlspecialchars(pmImgSrc($editProduct['size_chart_image'])); ?>" alt="Size Chart" style="width:50px;height:50px;object-fit:cover;border-radius:4px;border:1px solid #cbd5e1;">
                                                    </a>
                                                    <div class="form-check m-0">
                                                        <input class="form-check-input" type="checkbox" name="remove_size_chart_image" value="1" id="removeSizeChart">
                                                        <label class="form-check-label small text-danger" for="removeSizeChart">Remove</label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control form-control-sm" name="size_chart_image" accept="image/png,image/jpeg,image/gif,image/webp">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. COLORS & VARIANT IMAGES -->
                            <div class="border rounded p-3 bg-light shadow-sm mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="fa fa-palette text-primary"></i> Product Colors (Dedicated Color, Image, Price &amp; Text)
                                        <span class="badge bg-primary text-white" style="font-size:10px;">Printo-Style Variants</span>
                                    </label>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_colors" id="tab2_show_colors" value="1" <?php echo (!empty($editProduct['show_colors']) || !empty($existingColorVariants)) ? 'checked' : ''; ?> onchange="document.getElementById('colorVariantsWrapper').style.display = this.checked ? 'block' : 'none';">
                                        <label class="form-check-label small fw-semibold" for="tab2_show_colors">Enable Colors on Product</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">
                                    Each product can have its own colors. For each color, upload a dedicated image (which automatically swaps when the customer selects that color on the storefront, like Printo.in), swatch hex code, price add-on, and custom notes.
                                </p>

                                <div id="colorVariantsWrapper" style="<?php echo (!empty($editProduct['show_colors']) || !empty($existingColorVariants)) ? '' : 'display:none;'; ?>">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm dynamic-table bg-white align-middle mb-2" id="tab2ColorTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:22%;">Color Name</th>
                                                    <th style="width:16%;">Swatch / Hex</th>
                                                    <th style="width:28%;">Variant Image (Swaps on Storefront)</th>
                                                    <th style="width:14%;">Price Add-on (₹)</th>
                                                    <th style="width:15%;">Notes / Spec</th>
                                                    <th style="width:5%; text-align:center;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab2ColorBody">
                                                <?php if (!empty($existingColorVariants)): ?>
                                                    <?php foreach ($existingColorVariants as $cIdx => $cRow): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm" name="variant_color_name[]" value="<?php echo htmlspecialchars($cRow['name'] ?? ''); ?>" placeholder="e.g. Royal Blue" required>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <input type="color" class="form-control form-control-sm form-control-color p-0" name="variant_color_hex[]" value="<?php echo htmlspecialchars($cRow['hex'] ?? '#000000'); ?>" style="width:32px;height:31px;" onchange="this.nextElementSibling.value=this.value">
                                                                    <input type="text" class="form-control form-control-sm font-monospace p-1" value="<?php echo htmlspecialchars($cRow['hex'] ?? '#000000'); ?>" style="font-size:11px;width:70px;" oninput="this.previousElementSibling.value=this.value">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <?php if (!empty($cRow['image'])): ?>
                                                                        <a href="<?php echo htmlspecialchars(pmImgSrc($cRow['image'])); ?>" target="_blank" title="View variant image">
                                                                            <img src="<?php echo htmlspecialchars(pmImgSrc($cRow['image'])); ?>" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:4px;border:1px solid #cbd5e1;flex-shrink:0;">
                                                                        </a>
                                                                        <input type="hidden" name="existing_variant_color_images[]" value="<?php echo htmlspecialchars($cRow['image']); ?>">
                                                                    <?php else: ?>
                                                                        <input type="hidden" name="existing_variant_color_images[]" value="">
                                                                    <?php endif; ?>
                                                                    <input type="file" class="form-control form-control-sm" name="variant_color_images[]" accept="image/png,image/jpeg,image/gif,image/webp">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text">₹</span>
                                                                    <input type="number" step="0.01" min="0" class="form-control" name="variant_color_price[]" value="<?php echo htmlspecialchars($cRow['price'] ?? 0); ?>" placeholder="0.00">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm" name="variant_color_text[]" value="<?php echo htmlspecialchars($cRow['text'] ?? ''); ?>" placeholder="e.g. 100% Bio-Wash">
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()" title="Remove color">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="pmAddColorVariantRow()">
                                        <i class="fa fa-plus me-1"></i> Add Another Color
                                    </button>
                                </div>
                            </div>

                            <!-- 3. PRINTING & MATERIAL SPECIFICATIONS -->
                            <p class="section-heading mb-2">Printing &amp; Material Specifications</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Materials Text Select</label>
                                    <input type="text" class="form-control" name="attr_material" value="<?php echo htmlspecialchars($editProduct['material'] ?? ''); ?>" placeholder="e.g. Cotton, Plastic, Canvas, Ceramic">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Lamination Text Select</label>
                                    <input type="text" class="form-control" name="attr_lamination" value="<?php echo htmlspecialchars(is_array($editProduct['lamination_attributes'] ?? null) ? implode(', ', $editProduct['lamination_attributes']) : ($editProduct['lamination_attributes'] ?? '')); ?>" placeholder="e.g. Glossy, Matte, Soft-touch">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Orientation Text Select</label>
                                    <select class="form-select" name="attr_orientation">
                                        <option value="">— Select —</option>
                                        <option value="portrait" <?php echo ($editProduct['attr_orientation'] ?? '') === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                                        <option value="landscape" <?php echo ($editProduct['attr_orientation'] ?? '') === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                                        <option value="square" <?php echo ($editProduct['attr_orientation'] ?? '') === 'square' ? 'selected' : ''; ?>>Square</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Printing Location Text Select</label>
                                    <input type="text" class="form-control" name="attr_printing_location" value="<?php echo htmlspecialchars(is_array($editProduct['printing_location'] ?? null) ? implode(', ', $editProduct['printing_location']) : ($editProduct['printing_location'] ?? '')); ?>" placeholder="e.g. Front, Back, Left Sleeve, Both Sides">
                                </div>
                            </div>

                            <!-- 4. QUANTITY-BASED PRICING TIERS -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Quantity-Based Pricing Tiers (Quantity Text &amp; Price Add)</label>
                                <p class="text-muted small mb-2">Offer automated volume discounts when customers purchase higher quantities.</p>
                                <table class="table table-bordered table-sm dynamic-table bg-white align-middle" id="qtyTable">
                                    <thead class="table-light"><tr><th>Minimum Quantity</th><th>Price per unit (₹)</th><th style="width:40px"></th></tr></thead>
                                    <tbody id="qtyBody">
                                        <?php 
                                        $qtyTiers = !empty($editProduct['quantity_price_breaks']) ? (is_array($editProduct['quantity_price_breaks']) ? $editProduct['quantity_price_breaks'] : json_decode($editProduct['quantity_price_breaks'], true)) : [];
                                        ?>
                                        <?php if (!empty($qtyTiers)): ?>
                                            <?php foreach ($qtyTiers as $qt): ?>
                                                <tr>
                                                    <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" value="<?php echo htmlspecialchars($qt['qty'] ?? ''); ?>" placeholder="e.g. 50" min="1"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" value="<?php echo htmlspecialchars($qt['price'] ?? ''); ?>" placeholder="0.00" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()">×</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" placeholder="e.g. 50" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" placeholder="0.00" min="0"></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()">×</button></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-outline-success add-row-btn" onclick="addRow('qtyBody', qtyRowTemplate)">+ Add Tier</button>
                            </div>

                            <!-- 5. DYNAMIC CUSTOM ATTRIBUTES & PERSONALIZATION FIELDS BUILDER -->
                            <div class="kv-section mt-4 p-3 border rounded bg-white shadow-sm mb-4">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div>
                                        <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                            <i class="fa fa-layer-group text-primary"></i> Custom Product Attributes &amp; Personalization Fields
                                            <span class="badge bg-success text-white" style="font-size:10px;">Admin Freedom</span>
                                        </label>
                                        <div class="text-muted small">
                                            Add ANY custom product attribute or input field you want (e.g. Paper GSM, Corner Radius, Finish, Packaging Type, Custom Name, Instructions). Each option can carry a price add-on that dynamically updates the total price on the product page!
                                        </div>
                                    </div>
                                </div>
                                <div class="kv-head d-none d-md-grid">
                                    <span>Label (left side)</span>
                                    <span>Customer input (right side &amp; +price)</span>
                                    <span>Preview on product page</span>
                                    <span>Settings</span>
                                </div>
                                <div id="customKvList" class="kv-list"></div>
                                <button type="button" class="btn btn-sm btn-outline-success add-row-btn mt-2" onclick="addKvRow()">
                                    <i class="fa fa-plus me-1"></i> Add Attribute / Field
                                </button>
                            </div>

                            <!-- 6. OR PICK SAVED OPTIONS (LIBRARY PICKER) -->
                            <div class="border rounded p-3 bg-light shadow-sm">
                                <input type="hidden" name="customization_assignments" id="customAssignments" value="[]">

                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div>
                                        <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                            <i class="fa fa-bookmark text-primary"></i> Or Pick Saved Options <span class="fw-normal text-muted">(Optional)</span>
                                        </label>
                                        <div class="text-muted small">Options used on multiple products can be saved once in the library and ticked here with optional custom price overrides. Manage master options in
                                            <a href="customization-options.php" target="_blank" class="fw-semibold">Customization Options Manager <i class="fa fa-arrow-up-right-from-square"></i></a>.</div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="customSelectedCount">0 selected</span>
                                </div>

                                <?php if (empty($customLibrary)): ?>
                                    <div class="alert alert-warning mb-0">
                                        No saved library options yet. Create them in
                                        <a href="customization-options.php" target="_blank" class="alert-link">Customization Options Manager</a>
                                        (use "Import starter set" for common ones), then reload this page.
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <input type="search" class="form-control form-control-sm" id="customSearch" placeholder="Search options..." style="max-width:280px" oninput="filterCustomLibrary()">
                                        <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                            <input class="form-check-input" type="checkbox" id="customOnlySelected" onchange="filterCustomLibrary()">
                                            <label class="form-check-label small" for="customOnlySelected">Show selected only</label>
                                        </div>
                                    </div>
                                    <div id="customLibraryList" class="custom-library-list"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

<!-- ══════════════════════════════════════════════
                         SECTION 3 – FEATURES
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-3">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">3</span>
                                <i class="fa fa-list-check text-info"></i> Features
                            </h5>
                        </div>
                        <div class="product-section-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Features</label>
                                    <textarea class="form-control" name="features" rows="2" placeholder="e.g. Waterproof, Eco-friendly, Washable"><?php echo $isEdit ? htmlspecialchars($editProduct['features'] ?? '') : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

<!-- ══════════════════════════════════════════════
                         SECTION 4 – CATEGORY & PLACEMENT LINKS
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-4">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">4</span>
                                <i class="fa fa-sitemap text-warning"></i> Link Section Tab (Categories &amp; Carousels)
                            </h5>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa fa-asterisk me-1"></i>Category Required</span>
                        </div>
                        <div class="product-section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Main Category <span class="required-star">*</span></label>
                                        <select class="form-select" name="category_id" id="category_id" required onchange="loadSubCategories(this.value)">
                                            <option value="">— Select Main Category —</option>
                                            <?php foreach ($mainCategories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sub Category</label>
                                        <select class="form-select" name="sub_category_id" id="sub_category_id" onchange="loadSubSubCategories(this.value)">
                                            <option value="">— Select Main Category First —</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sub Sub Category</label>
                                        <select class="form-select" name="sub_sub_category_id" id="sub_sub_category_id">
                                            <option value="">— Select Sub Category First —</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Homepage Category</label>
                                        <select class="form-select" name="homepage_category_id">
                                            <option value="">— None —</option>
                                            <?php foreach ($mainCategories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Product Status</label>
                                        <select class="form-select" name="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold"><i class="fa fa-tags text-primary me-1"></i> Multi-Category Placement</label>
                                        <div class="text-muted small mb-2">Assign this product across multiple catalog categories (e.g. Same Day Delivery, Apparel, Corporate Gifts).</div>
                                        <div class="row g-2 p-3 bg-light rounded border" style="max-height: 170px; overflow-y: auto;">
                                            <?php foreach ($mainCategories as $mCat): ?>
                                                <div class="col-md-4 col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="additional_category_ids[]" value="<?php echo $mCat['id']; ?>" id="add_cat_<?php echo $mCat['id']; ?>" <?php echo in_array((int)$mCat['id'], $assignedCatIds ?? []) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small" for="add_cat_<?php echo $mCat['id']; ?>">
                                                            <?php echo htmlspecialchars($mCat['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold"><i class="fa fa-tags text-primary me-1"></i> Multi Sub-Category Placement</label>
                                        <div class="text-muted small mb-2">Assign this product across multiple sub-categories, in addition to the primary Sub Category above.</div>
                                        <div class="row g-2 p-3 bg-light rounded border" style="max-height: 220px; overflow-y: auto;">
                                            <?php
                                            $mainCatNamesById = [];
                                            foreach ($mainCategories as $mCat) { $mainCatNamesById[(int) $mCat['id']] = $mCat['name']; }
                                            $lastSubParent = null;
                                            ?>
                                            <?php foreach ($allSubCategories as $sCat): ?>
                                                <?php if ($lastSubParent !== (int) $sCat['parent_id']): $lastSubParent = (int) $sCat['parent_id']; ?>
                                                    <div class="col-12 mt-2 mb-1">
                                                        <small class="text-uppercase fw-bold text-muted"><?php echo htmlspecialchars($mainCatNamesById[(int) $sCat['parent_id']] ?? 'Other'); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="col-md-4 col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="additional_sub_category_ids[]" value="<?php echo $sCat['id']; ?>" id="add_subcat_<?php echo $sCat['id']; ?>" <?php echo in_array((int) $sCat['id'], $assignedSubCatIds ?? []) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small" for="add_subcat_<?php echo $sCat['id']; ?>">
                                                            <?php echo htmlspecialchars($sCat['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold"><i class="fa fa-tags text-primary me-1"></i> Multi Sub-Sub-Category Placement</label>
                                        <div class="text-muted small mb-2">Assign this product across multiple sub-sub-categories, in addition to the primary Sub Sub Category above.</div>
                                        <div class="row g-2 p-3 bg-light rounded border" style="max-height: 220px; overflow-y: auto;">
                                            <?php
                                            $subCatNamesById = [];
                                            foreach ($allSubCategories as $sCat) { $subCatNamesById[(int) $sCat['id']] = $sCat['name']; }
                                            $lastSubSubParent = null;
                                            ?>
                                            <?php foreach ($allSubSubCategories as $ssCat): ?>
                                                <?php if ($lastSubSubParent !== (int) $ssCat['parent_id']): $lastSubSubParent = (int) $ssCat['parent_id']; ?>
                                                    <div class="col-12 mt-2 mb-1">
                                                        <small class="text-uppercase fw-bold text-muted"><?php echo htmlspecialchars($subCatNamesById[(int) $ssCat['parent_id']] ?? 'Other'); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="col-md-4 col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="additional_sub_sub_category_ids[]" value="<?php echo $ssCat['id']; ?>" id="add_subsubcat_<?php echo $ssCat['id']; ?>" <?php echo in_array((int) $ssCat['id'], $assignedSubSubCatIds ?? []) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small" for="add_subsubcat_<?php echo $ssCat['id']; ?>">
                                                            <?php echo htmlspecialchars($ssCat['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <p class="section-heading mt-4">Product Flags</p>
                                <div class="row g-2">
                                    <?php foreach ([
                                        'featured'               => 'Featured Product',
                                        'top_selection'          => 'Top Selection',
                                        'our_bestseller'         => 'Our Bestseller',
                                        'top_rated'              => 'Top Rated',
                                        'top_deal_by_categories' => 'Top Deal by Categories',
                                        'is_assured'             => '⭐ PM Assured (Printmont Assured)',
                                    ] as $fname => $flabel): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="<?php echo $fname; ?>" value="1" id="<?php echo $fname; ?>">
                                            <label class="form-check-label" for="<?php echo $fname; ?>"><?php echo $flabel; ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 5 – IMAGES & MEDIA
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-5">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">5</span>
                                <i class="fa fa-images text-success"></i> Image Section Tab (Media &amp; Gallery)
                            </h5>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa fa-asterisk me-1"></i>Thumbnail &amp; ALT Required</span>
                        </div>
                        <div class="product-section-body">

                                <div class="mb-3">
                                    <label class="form-label">Image ALT Tag <span class="required-star">*</span></label>
                                    <input type="text" class="form-control" name="alt_tag" required placeholder="Descriptive alt text for all images (SEO)">
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Thumbnail Image <span class="required-star">*</span></label>
                                        <p class="text-muted small mt-0">Shown in listings. Recommended: 400×400 px, max 500 KB.</p>
                                        <?php if ($isEdit && $editThumb): ?>
                                            <div class="existing-images mb-2">
                                                <div class="text-muted small mb-1">Current thumbnail (uploading a new one replaces it):</div>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="image-thumb"><img src="<?php echo htmlspecialchars(pmImgSrc($editThumb)); ?>" alt="Current thumbnail" onerror="this.src='img/icons/icon-48x48.png'"></div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="remove_thumbnail" value="1" id="rmThumb">
                                                        <label class="form-check-label text-danger small" for="rmThumb">Remove this thumbnail</label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="upload-zone" id="thumbZone">
                                            <input type="file" name="thumbnail_image" id="thumbnail_image" accept="image/*" onchange="previewSingle(this,'thumbPreview')">
                                            <div class="upload-zone-icon">🖼️</div>
                                            <p>Drag &amp; drop or click to select thumbnail</p>
                                            <p class="mt-1"><small>JPEG / PNG / WebP · max 5 MB</small></p>
                                        </div>
                                        <div class="image-preview-grid" id="thumbPreview"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Main Product Images</label>
                                        <p class="text-muted small mt-0">Shown on product page. First image = primary. Recommended: 1000×1000 px.</p>
                                        <?php if ($isEdit && !empty($editMain)): ?>
                                            <div class="existing-images mb-2">
                                                <div class="text-muted small mb-1">Current images (click × to remove, new uploads are added after these):</div>
                                                <div class="image-preview-grid">
                                                    <?php foreach ($editMain as $idx => $img): ?>
                                                        <div class="image-thumb existing-img-wrap">
                                                            <img src="<?php echo htmlspecialchars(pmImgSrc($img['image_url'])); ?>" alt="" onerror="this.src='img/icons/icon-48x48.png'">
                                                            <input type="hidden" name="existing_main_images[]" value="<?php echo htmlspecialchars($img['image_url']); ?>">
                                                            <button type="button" class="remove-img" onclick="removeExistingImg(this)" title="Remove">×</button>
                                                            <?php if ($idx === 0): ?><span class="badge bg-primary badge-primary">Primary</span><?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="upload-zone" id="mainZone">
                                            <input type="file" name="main_images[]" id="main_images" accept="image/*" multiple onchange="previewMultiple(this,'mainPreview')">
                                            <div class="upload-zone-icon">📷</div>
                                            <p>Drag &amp; drop or click to select main images</p>
                                            <p class="mt-1"><small>Multiple allowed · max 5 MB each</small></p>
                                        </div>
                                        <div class="image-preview-grid" id="mainPreview"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Product Gallery</label>
                                        <p class="text-muted small mt-0">Additional lifestyle / detail images. Max 10 images, 1000×1000 px recommended.</p>
                                        <?php if ($isEdit && !empty($editGallery)): ?>
                                            <div class="existing-images mb-2">
                                                <div class="text-muted small mb-1">Current gallery (click × to remove):</div>
                                                <div class="image-preview-grid">
                                                    <?php foreach ($editGallery as $gimg): ?>
                                                        <div class="image-thumb existing-img-wrap">
                                                            <img src="<?php echo htmlspecialchars(pmImgSrc($gimg)); ?>" alt="" onerror="this.src='img/icons/icon-48x48.png'">
                                                            <input type="hidden" name="existing_gallery_images[]" value="<?php echo htmlspecialchars($gimg); ?>">
                                                            <button type="button" class="remove-img" onclick="removeExistingImg(this)" title="Remove">×</button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="upload-zone" id="galleryZone">
                                            <input type="file" name="gallery_images[]" id="gallery_images" accept="image/*" multiple onchange="previewMultiple(this,'galleryPreview')">
                                            <div class="upload-zone-icon">🗂️</div>
                                            <p>Drag &amp; drop or click to select gallery images</p>
                                        </div>
                                        <div class="image-preview-grid" id="galleryPreview"></div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 6 – SEO CONTENT & META
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-6">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">6</span>
                                <i class="fa fa-magnifying-glass-chart text-primary"></i> SEO Content Section Tab
                            </h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Search Optimization</span>
                        </div>
                        <div class="product-section-body">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label">Meta Title <span class="required-star">*</span></label>
                                            <span class="char-counter" id="titleCounter">0 / 60</span>
                                        </div>
                                        <input type="text" class="form-control" name="meta_title" id="meta_title" maxlength="70"
                                               placeholder="Page title shown in Google (50–60 chars ideal)" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SEO Score</label>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <div class="seo-score" id="seoScore">0</div>
                                            <div style="font-size:12px;color:#6c757d">/100</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" name="meta_keywords" id="meta_keywords"
                                               placeholder="keyword1, keyword2, keyword3, ...">
                                        <div class="form-text">Comma-separated. 5–10 keywords recommended.</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label">Meta Description <span class="required-star">*</span></label>
                                            <span class="char-counter" id="descCounter">0 / 160</span>
                                        </div>
                                        <textarea class="form-control" name="meta_description" id="meta_description" rows="3"
                                                  maxlength="180" placeholder="Description shown in Google results (140–160 chars ideal)" required></textarea>
                                    </div>
                                </div>

                                <!-- Live Google Preview -->
                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Live Google Search Preview</label>
                                    <div class="google-preview">
                                        <div class="gp-title" id="gpTitle">Your product title here</div>
                                        <div class="gp-url">yoursite.com › product › <span id="gpSlug">product-slug</span></div>
                                        <div class="gp-desc" id="gpDesc">Your meta description will appear here. Make it compelling to increase click-through rates...</div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 7 – STATUS SHOW AND HIDE BUTTON
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-7">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">7</span>
                                <i class="fa fa-toggle-on text-primary"></i> Status Show and Hide Button
                            </h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Visibility &amp; Single Product Actions</span>
                        </div>
                        <div class="product-section-body">
                            <p class="text-muted small mb-3">
                                Control which buttons, forms, stock counters, and trust badges are visible to customers on the single product details page.
                            </p>

                            <!-- 1. Add to Cart and Buy Now Button Show and Hide -->
                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>1. Add to Cart and Buy Now Button Show and Hide</strong>
                                    <small>Yes / No switch. When turned OFF (No), direct checkout buttons are hidden on the single product page and replaced with an "Enquire Now / Request Quote" button.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="show_cart_buttons" id="show_cart_buttons" value="1" <?php echo (!isset($editProduct['show_cart_buttons']) || !empty($editProduct['show_cart_buttons'])) ? 'checked' : ''; ?>>
                                </div>
                            </div>

                            <!-- 2. Hide and Show Product Left Quantity -->
                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>2. Hide and Show Product Left Quantity</strong>
                                    <small>Displays stock urgency count (e.g. "Only 3 left in stock!") on the single product details page.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="show_quantity" id="show_quantity" value="1" <?php echo (!isset($editProduct['show_quantity']) || !empty($editProduct['show_quantity'])) ? 'checked' : ''; ?>>
                                </div>
                            </div>

                            <!-- 3. Show Help Button -->
                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>3. Show Help Button</strong>
                                    <small>Displays customer support WhatsApp / call assistance button on the single product details page.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="show_help_button" id="show_help_button" value="1" <?php echo !empty($editProduct['show_help_button']) ? 'checked' : ''; ?> onchange="document.getElementById('helpBtnConfig').style.display = this.checked ? 'block' : 'none';">
                                </div>
                            </div>
                            <div id="helpBtnConfig" class="p-3 bg-light rounded border mb-3" style="<?php echo !empty($editProduct['show_help_button']) ? '' : 'display:none;'; ?>">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Help Button Text</label>
                                        <input type="text" class="form-control form-control-sm" name="help_button_text" value="<?php echo htmlspecialchars($editProduct['help_button_text'] ?? 'Need Help? Chat with us'); ?>" placeholder="Need Help? Chat with us">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Help Button Link / WhatsApp / Phone</label>
                                        <input type="text" class="form-control form-control-sm" name="help_button_link" value="<?php echo htmlspecialchars($editProduct['help_button_link'] ?? 'https://wa.me/919999999999'); ?>" placeholder="https://wa.me/... or tel:...">
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Bulk Quantity Form Show -->
                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>4. Bulk Quantity Form Show</strong>
                                    <small>Displays B2B bulk quantity inquiry / quotation request form on the single product details page.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="show_bulk_form" id="show_bulk_form" value="1" <?php echo !empty($editProduct['show_bulk_form']) ? 'checked' : ''; ?>>
                                </div>
                            </div>

                            <!-- 5. Show me the Inquire Fill Form -->
                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>5. Show me the Inquire Fill Form</strong>
                                    <small>Displays a dedicated "Enquire Now" inquiry form button on the single product details page for corporate &amp; custom inquiries.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="show_inquiry_form" id="show_inquiry_form" value="1" <?php echo !empty($editProduct['show_inquiry_form']) ? 'checked' : ''; ?>>
                                </div>
                            </div>

                            <!-- 6. PM Assured Badge -->
                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>6. Printmont Assured (PM Assured) Badge</strong>
                                    <small>Displays verified quality and trust PM Assured badge on the single product page and catalog cards.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_assured" id="is_assured" value="1" <?php echo (!isset($editProduct['is_assured']) || !empty($editProduct['is_assured'])) ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                    </div>

<!-- ══════════════════════════════════════════════
                         SECTION 8 – CUSTOMIZE STATUS
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-8">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">8</span>
                                <i class="fa fa-wand-magic-sparkles text-warning"></i> Customize Status Section Tab
                            </h5>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Design Buttons &amp; Studio</span>
                        </div>
                        <div class="product-section-body">
                            <p class="text-muted small mb-3">
                                Control the design action buttons shown to customers under the Total price on the single product page (<strong>"Upload your design"</strong> and <strong>"Create your design"</strong>), and configure upload slot limits.
                            </p>

                            <!-- 1. Upload Your Design Button -->
                            <div class="border rounded p-3 bg-light shadow-sm mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="toggle-label-wrap">
                                        <strong class="d-flex align-items-center gap-2">
                                            <i class="fa fa-cloud-arrow-up text-primary"></i> 1. "Upload Your Design" Button
                                            <span class="badge bg-primary text-white" style="font-size:10px;">Customer File Upload</span>
                                        </strong>
                                        <small>Enable or disable the "Upload your design" popup button under Total on the single product details page.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="enable_upload_design" id="enable_upload_design" value="1" <?php echo (!isset($editProduct['enable_upload_design']) || !empty($editProduct['enable_upload_design'])) ? 'checked' : ''; ?> onchange="document.getElementById('uploadDesignConfig').style.display = this.checked ? 'block' : 'none';">
                                    </div>
                                </div>

                                <div id="uploadDesignConfig" style="<?php echo (!isset($editProduct['enable_upload_design']) || !empty($editProduct['enable_upload_design'])) ? '' : 'display:none;'; ?>" class="mt-3 p-3 bg-white rounded border">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa fa-images text-primary"></i> Image Upload Inputs Count
                                                <span class="badge bg-light text-muted border">Max Files</span>
                                            </label>
                                            <p class="text-muted small mb-2">Kitna image upload karne ka input dena he (Customer can upload up to this many design files in the popup):</p>
                                            <div class="input-group input-group-sm" style="max-width: 240px;">
                                                <span class="input-group-text"><i class="fa fa-file-image"></i></span>
                                                <input type="number" class="form-control" name="upload_design_max_images" id="upload_design_max_images" min="1" max="14" value="<?php echo htmlspecialchars($editProduct['upload_design_max_images'] ?? 4); ?>" required>
                                                <span class="input-group-text">Image Slot(s)</span>
                                            </div>
                                            <small class="text-muted d-block mt-1">e.g. 1 (Single Logo/Art), 2 (Front &amp; Back), 4 (Multi-view), up to 14.</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa fa-font text-info"></i> Custom Text Inputs Count
                                                <span class="badge bg-light text-muted border">Text Fields</span>
                                            </label>
                                            <p class="text-muted small mb-2">Kitne input dena he (Customer can enter custom texts/instructions in the popup):</p>
                                            <div class="input-group input-group-sm" style="max-width: 240px;">
                                                <span class="input-group-text"><i class="fa fa-keyboard"></i></span>
                                                <input type="number" class="form-control" name="upload_design_max_texts" id="upload_design_max_texts" min="0" max="12" value="<?php echo htmlspecialchars($editProduct['upload_design_max_texts'] ?? 2); ?>" required>
                                                <span class="input-group-text">Text Input(s)</span>
                                            </div>
                                            <small class="text-muted d-block mt-1">Set 0 to hide text tab, or 1–12 for custom texts (e.g. Name, Number, Tagline).</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Create Your Design (Visual Studio) Button -->
                            <div class="border rounded p-3 bg-light shadow-sm mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="toggle-label-wrap">
                                        <strong class="d-flex align-items-center gap-2">
                                            <i class="fa fa-paint-brush text-warning"></i> 2. "Create Your Design" Button
                                            <span class="badge bg-warning-subtle text-warning-emphasis border" style="font-size:10px;">Visual Studio</span>
                                        </strong>
                                        <small>Enable or disable the interactive online design customizer studio button under Total on the single product page.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="enable_customizer" id="enable_customizer" value="1" <?php echo (!isset($editProduct['enable_customizer']) || !empty($editProduct['enable_customizer'])) ? 'checked' : ''; ?> onchange="document.getElementById('customizerConfig').style.display = this.checked ? 'block' : 'none';">
                                    </div>
                                </div>

                                <div id="customizerConfig" style="<?php echo (!isset($editProduct['enable_customizer']) || !empty($editProduct['enable_customizer'])) ? '' : 'display:none;'; ?>" class="mt-3 p-3 bg-white rounded border">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold small text-dark">Visual Customizer Canvas Template</label>
                                            <select class="form-select form-select-sm" name="customizer_template_id" id="customizer_template_id">
                                                <option value="">Auto-Detect by Category / Title (Recommended)</option>
                                                <?php 
                                                $allTpls = $db->query("SELECT id, title, category_keyword FROM customizer_templates WHERE status='active' ORDER BY is_default DESC, display_order ASC");
                                                while ($allTpls && ($tpl = $allTpls->fetch_assoc())) {
                                                    $sel = (!empty($editProduct['customizer_template_id']) && (int)$editProduct['customizer_template_id'] === (int)$tpl['id']) ? 'selected' : '';
                                                    echo "<option value='{$tpl['id']}' {$sel}>" . htmlspecialchars($tpl['title']) . " ({$tpl['category_keyword']})</option>";
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted d-block mt-1">Manage canvas mockups &amp; printable zones in <a href="customizer-manager.php" target="_blank">Design Customizer Studio</a>.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Customizable Trust Badge -->
                            <div class="border rounded p-3 bg-light shadow-sm">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="toggle-label-wrap">
                                        <strong class="d-flex align-items-center gap-2">
                                            <i class="fa fa-award text-success"></i> 3. "Customizable" Trust Badge
                                        </strong>
                                        <small>Shows the "Customizable" badge pill above the title on the product page and catalog cards.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_customization_label" id="show_customization_label" value="1" <?php echo (!isset($editProduct['show_customization_label']) || !empty($editProduct['show_customization_label'])) ? 'checked' : ''; ?> onchange="document.getElementById('badgeTextConfig').style.display = this.checked ? 'block' : 'none';">
                                    </div>
                                </div>

                                <div id="badgeTextConfig" style="<?php echo (!isset($editProduct['show_customization_label']) || !empty($editProduct['show_customization_label'])) ? '' : 'display:none;'; ?>" class="mt-3 p-3 bg-white rounded border">
                                    <label class="form-label fw-bold small text-dark">Customization Badge Label Text</label>
                                    <input type="text" class="form-control form-control-sm" name="customization_label" id="customization_label" value="<?php echo htmlspecialchars($editProduct['customization_label'] ?? 'Customizable'); ?>" placeholder="e.g. Fully Customizable, Add Your Name">
                                </div>
                            </div>

                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 9 – SHIPPING CHARGES
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-9">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">9</span>
                                <i class="fa fa-truck-fast text-dark"></i> Shipping Charges Section Tab
                            </h5>
                            <span class="badge bg-light text-secondary border">Delivery Rates</span>
                        </div>
                        <div class="product-section-body">

                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Enable Custom Shipping for this Product</strong>
                                        <small>If OFF, site-wide default shipping rates apply</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="shipping_method_status" id="shipping_method_status" value="1" onchange="toggleSection('shippingFields',this)">
                                    </div>
                                </div>

                                <div id="shippingFields" style="display:none" class="mt-3">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">🏙️ Local Shipping Price (₹)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" class="form-control" name="local_shipping_charge" placeholder="0.00" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Local Shipping Message</label>
                                            <input type="text" class="form-control" name="local_shipping_message" placeholder="e.g. Delivered in 1-2 days">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">🗺️ Regional Shipping Price (₹)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" class="form-control" name="regional_shipping_charge" placeholder="0.00" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Regional Shipping Message</label>
                                            <input type="text" class="form-control" name="regional_shipping_message" placeholder="e.g. Delivered in 2-4 days">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">🇮🇳 National Shipping Price (₹)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" class="form-control" name="national_shipping_charge" placeholder="0.00" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">National Shipping Message</label>
                                            <input type="text" class="form-control" name="national_shipping_message" placeholder="e.g. Delivered in 4-7 days">
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 10 – CANCEL CONDITIONS
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-10">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">10</span>
                                <i class="fa fa-rotate-left text-danger"></i> Cancel Condition Section Tab
                            </h5>
                            <span class="badge bg-light text-secondary border">Order Window</span>
                        </div>
                        <div class="product-section-body">

                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Allow Order Cancellation</strong>
                                        <small>Customer can cancel this order within the defined window</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="cancel_available" id="cancel_available" value="1" checked onchange="toggleSection('cancelFields',this)">
                                    </div>
                                </div>

                                <div id="cancelFields" class="mt-3">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Window Value</label>
                                            <input type="number" class="form-control" name="cancel_time" id="cancel_time" min="1" value="24" onchange="updateCancelPreview()">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Window Unit</label>
                                            <select class="form-select" name="cancel_type" id="cancel_type" onchange="updateCancelPreview()">
                                                <option value="hours">Hours</option>
                                                <option value="days">Days</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="alert alert-info mb-0 py-2" id="cancelPreview">
                                                This product can be cancelled within <strong>24 hours</strong> of order placement.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 11 – CASH ON DELIVERY
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-11">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">11</span>
                                <i class="fa fa-money-bill-wave text-success"></i> Cash on Delivery Section Tab
                            </h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Review &amp; Submit</span>
                        </div>
                        <div class="product-section-body">

                            <div class="toggle-row">
                                <div class="toggle-label-wrap">
                                    <strong>Enable Cash on Delivery for this Product</strong>
                                    <small>When ON, customer can pay cash at time of delivery</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="cod_available" id="cod_available" value="1" checked>
                                </div>
                            </div>

                            <div class="alert alert-light border mt-4">
                                <strong>Note:</strong> This setting overrides the site-wide COD configuration for this specific product only.
                                Ensure your payment gateway supports COD before enabling.
                            </div>

                            <!-- Final summary before submit -->
                            <div class="mt-4 p-3 bg-light rounded border">
                                <h6 class="fw-bold mb-3"><i class="fa fa-clipboard-check text-primary me-2"></i>Quick Product Summary</h6>
                                <div class="row" id="quickSummary">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Product Name:</strong> <span id="sumName" class="text-dark">—</span></p>
                                        <p class="mb-2"><strong>SKU Code:</strong> <span id="sumSku" class="badge bg-secondary">—</span></p>
                                        <p class="mb-2"><strong>Regular Price:</strong> ₹<span id="sumMrp" class="fw-bold">—</span></p>
                                        <p class="mb-2"><strong>Offer Price:</strong> ₹<span id="sumOffer" class="text-success fw-bold">—</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Stock Quantity:</strong> <span id="sumQty" class="badge bg-info text-dark">—</span></p>
                                        <p class="mb-2"><strong>Status:</strong> <span id="sumStatus" class="badge bg-success">—</span></p>
                                        <p class="mb-2"><strong>Category:</strong> <span id="sumCat" class="text-primary fw-semibold">—</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
<!-- ══════════════════════════════════════════════
                         SECTION 12 – ADDONS & CROSS-SELLS
                    ══════════════════════════════════════════════ -->
                    <div class="product-section-card" id="tab-12">
                        <div class="product-section-header">
                            <h5 class="product-section-title">
                                <span class="product-section-badge">12</span>
                                <i class="fa fa-puzzle-piece text-info"></i> Addons &amp; Cross-sells Section
                            </h5>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Upsell / Cross-sell</span>
                        </div>
                        <div class="product-section-body">
                            <p class="text-muted small mb-3">Search and add products to show as "Frequently Bought Together" on the product detail page.</p>

                                <input type="hidden" name="addon_product_ids" id="addonIdsInput" value="[]">

                                <div class="position-relative">
                                    <label class="form-label">Search Products</label>
                                    <input type="text" class="form-control" id="addonSearch" placeholder="Type product name or SKU..." autocomplete="off">
                                    <div class="addon-search-results d-none" id="addonResults"></div>
                                </div>

                                <div class="addon-cards mt-3" id="addonCards"></div>
                        </div>
                    </div>

                    
<!-- ── Sticky Bottom Action Bar ── -->
                    <div class="product-bottom-bar mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <a href="view-products.php" class="btn btn-outline-secondary">
                                <i class="fa fa-arrow-left me-1"></i> Cancel &amp; Back
                            </a>
                            <button type="button" class="btn btn-light border text-muted d-none d-sm-inline-flex" onclick="window.scrollTo({top:0, behavior:'smooth'})">
                                <i class="fa fa-arrow-up me-1"></i> Back to Top
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" name="status" value="draft" class="btn btn-outline-primary px-3">
                                <i class="fa fa-floppy-disk me-1"></i> Save Draft
                            </button>
                            <button type="submit" class="btn btn-success px-4 py-2 fw-semibold" id="publishBtn">
                                <i class="fa fa-check me-1"></i> <?php echo $isEdit ? 'Update Product' : 'Publish Product'; ?>
                            </button>
                        </div>
                    </div>

                </form>

                <!-- Floating Back to Top Button -->
                <button type="button" class="btn-back-to-top" id="backToTopBtn" title="Back to top">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
// Edit mode: the product being edited (null when adding a new product)
const EDIT_PRODUCT = <?php echo $isEdit ? json_encode($editProduct, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR) : 'null'; ?>;
// Tab 2 "+ Add New Option" needs a saved product id — 0 while adding a brand-new product
const PRODUCT_EDIT_ID = <?php echo $isEdit ? (int) $editId : 0; ?>;

// ─── State & Addons ──────────────────────────────────────────────────────────
const TOTAL_TABS    = 12;
const addonProducts = {};   // id → {id,name,sku,price,thumb}

// ─── Smooth Section Navigation ────────────────────────────────────────────────
function goToTab(n) {
    n = parseInt(n);
    if (isNaN(n) || n < 1 || n > TOTAL_TABS) return;

    const targetEl = document.getElementById('tab-' + n);
    if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelectorAll('.quick-pill').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-target') === 'tab-' + n);
        });
    }
}

// Quick Pill Button Click Listeners
document.querySelectorAll('.quick-pill').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('data-target');
        const n = parseInt(targetId.replace('tab-', ''));
        goToTab(n);
    });
});

// ScrollSpy via IntersectionObserver
const sectionCards = document.querySelectorAll('.product-section-card');
if ('IntersectionObserver' in window && sectionCards.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                document.querySelectorAll('.quick-pill').forEach(btn => {
                    btn.classList.toggle('active', btn.getAttribute('data-target') === id);
                });
            }
        });
    }, {
        rootMargin: '-80px 0px -55% 0px',
        threshold: 0
    });
    sectionCards.forEach(card => observer.observe(card));
}

// Back to Top button
const bttBtn = document.getElementById('backToTopBtn');
if (bttBtn) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 350) {
            bttBtn.style.display = 'inline-flex';
        } else {
            bttBtn.style.display = 'none';
        }
    });
    bttBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ─── Slug auto-generation ─────────────────────────────────────────────────────
document.getElementById('product_name').addEventListener('input', function() {
    const slugEl = document.getElementById('product_slug');
    if (EDIT_PRODUCT && slugEl.value.trim()) return; // keep the live URL of an existing product
    slugEl.value = this.value.toLowerCase()
        .replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').replace(/--+/g, '-').trim();
    document.getElementById('gpSlug').textContent = slugEl.value || 'product-slug';
});

document.getElementById('product_slug').addEventListener('input', function() {
    document.getElementById('gpSlug').textContent = this.value || 'product-slug';
});

// ─── Discount % display ───────────────────────────────────────────────────────
function updateDiscount() {
    const mrp   = parseFloat(document.getElementById('regular_price').value) || 0;
    const offer = parseFloat(document.getElementById('offer_price').value)   || 0;
    const el    = document.getElementById('discount_pct');
    if (mrp > 0 && offer > 0 && offer < mrp) {
        const pct = Math.round(((mrp - offer) / mrp) * 100);
        el.textContent = pct + '% OFF';
    } else { el.textContent = ''; }
}
document.getElementById('regular_price').addEventListener('input', updateDiscount);
document.getElementById('offer_price').addEventListener('input',   updateDiscount);

// Bulk Order Pricing Tiers now live only on Tab 2 (pmAddBulkTierRow / tab2BulkBody) — see note above.

// ─── Generic add/remove row ───────────────────────────────────────────────────
function addRow(tbodyId, template) {
    const tbody = document.getElementById(tbodyId);
    const div   = document.createElement('tbody');
    div.innerHTML = template;
    tbody.appendChild(div.firstElementChild);
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-row')) {
        const row = e.target.closest('tr');
        if (row && row.parentElement.children.length > 1) row.remove();
    }
});

// Size & color row templates
const sizeRowTemplate = `<tr>
    <td><input type="text" class="form-control form-control-sm" name="size_names[]" placeholder="e.g. M"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm" name="size_prices[]" placeholder="0.00" min="0"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
</tr>`;

const colorRowTemplate = `<tr>
    <td><input type="text" class="form-control form-control-sm" name="color_names[]" placeholder="e.g. Red"></td>
    <td><input type="color" class="form-control form-control-sm form-control-color" name="color_hexes[]" value="#000000"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm" name="color_prices[]" placeholder="0.00" min="0"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
</tr>`;

const qtyRowTemplate = `<tr>
    <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" placeholder="e.g. 100" min="1"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" placeholder="0.00" min="0"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
</tr>`;

// ─── Toggle sections ──────────────────────────────────────────────────────────
function toggleSection(sectionId, checkbox) {
    document.getElementById(sectionId).style.display = checkbox.checked ? '' : 'none';
}

function toggleCustomFields(checkbox) {
    document.getElementById('customizationDetails').style.display = checkbox.checked ? '' : 'none';
}

// ─── Customization key-value fields (section 7) ─────────────────────────────
// One row = one field on the product page: label on the left, the chosen input on the right.
const KV_TYPES = [
    { v: 'text',     l: 'Simple input (text)' },
    { v: 'textarea', l: 'Long text (multi-line)' },
    { v: 'number',   l: 'Number input' },
    { v: 'select',   l: 'Dropdown' },
    { v: 'multi',    l: 'Multi-select chips' },
    { v: 'radio',    l: 'Radio buttons' },
    { v: 'image',    l: 'Image / file upload' }
];
const KV_CHOICE = ['select', 'multi', 'radio'];

// "Back | 50" → {label:'Back', price:50}; one option per line
function parseKvValues(text) {
    return text.split('\n').map(l => l.trim()).filter(Boolean).map(l => {
        const [label, price] = l.split('|').map(x => x.trim());
        return { label, hex: '', price: parseFloat(price) || 0 };
    }).filter(o => o.label);
}
function kvValuesToText(options) {
    return (options || []).map(o => o.price > 0 ? `${o.label} | ${o.price}` : o.label).join('\n');
}

function addKvRow(field) {
    field = field || {};
    const type = KV_TYPES.some(t => t.v === field.type) ? field.type : 'text';
    const req = Number(field.required) ? 1 : 0;
    const row = document.createElement('div');
    row.className = 'kv-row';
    row.innerHTML = `
        <div>
            <input type="text" class="form-control form-control-sm kv-label" name="customization_labels[]" maxlength="80"
                   placeholder="e.g. Fabric Colour" value="${escHtml(field.label || '')}" oninput="renderKvPreview(this)">
            <input type="text" class="form-control form-control-sm mt-2" name="customization_help[]" maxlength="160"
                   placeholder="Help text (optional)" value="${escHtml(field.help || '')}">
        </div>
        <div>
            <select class="form-select form-select-sm kv-type" name="customization_types[]" onchange="onKvTypeChange(this)">
                ${KV_TYPES.map(t => `<option value="${t.v}" ${t.v === type ? 'selected' : ''}>${t.l}</option>`).join('')}
            </select>
            <div class="kv-values">
                <textarea class="form-control form-control-sm kv-values-text" rows="3" placeholder="One option per line, e.g.&#10;White&#10;Black&#10;Back | 50" oninput="renderKvPreview(this)">${escHtml(kvValuesToText(field.options))}</textarea>
                <div class="kv-hint">One option per line. Add an extra price per piece with <b>| price</b>, e.g. <code>Back | 50</code>.</div>
            </div>
            <input type="hidden" name="customization_options[]" class="kv-options-json" value="[]">
        </div>
        <div class="kv-preview"><span class="kv-p-label"></span><span class="kv-p-ctrl"></span></div>
        <div class="kv-tools">
            <div class="form-check form-switch m-0" title="Customer must fill this">
                <input type="hidden" name="customization_required[]" value="${req}">
                <input class="form-check-input" type="checkbox" ${req ? 'checked' : ''} onchange="this.previousElementSibling.value = this.checked ? 1 : 0">
                <label class="form-check-label small">Req.</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.kv-row').remove()" title="Remove field">×</button>
        </div>`;
    document.getElementById('customKvList').appendChild(row);
    onKvTypeChange(row.querySelector('.kv-type'));
    return row;
}

function onKvTypeChange(select) {
    const row = select.closest('.kv-row');
    row.querySelector('.kv-values').style.display = KV_CHOICE.includes(select.value) ? '' : 'none';
    renderKvPreview(select);
}

// Small preview of how the field looks on the product page
function renderKvPreview(el) {
    const row = el.closest('.kv-row');
    const label = row.querySelector('.kv-label').value.trim() || 'Label';
    const type = row.querySelector('.kv-type').value;
    const opts = parseKvValues(row.querySelector('.kv-values-text').value);
    const tag = o => escHtml(o.label) + (o.price > 0 ? ` (+₹${o.price})` : '');
    let ctrl = '';
    if (type === 'select') ctrl = `<select class="form-select form-select-sm" disabled>${opts.map(o => `<option>${tag(o)}</option>`).join('') || '<option>Add options</option>'}</select>`;
    else if (type === 'multi') ctrl = opts.map(o => `<span class="kv-chip">${tag(o)}</span>`).join('') || '<span class="text-muted">Add options</span>';
    else if (type === 'radio') ctrl = opts.map((o, i) => `<div><input type="radio" disabled ${i === 0 ? 'checked' : ''}> ${tag(o)}</div>`).join('') || '<span class="text-muted">Add options</span>';
    else if (type === 'textarea') ctrl = '<textarea class="form-control form-control-sm" rows="2" disabled></textarea>';
    else if (type === 'image') ctrl = '<span class="btn btn-sm btn-outline-primary disabled">⬆ Choose file</span>';
    else ctrl = `<input class="form-control form-control-sm" disabled placeholder="${type === 'number' ? '0' : 'Customer types here'}">`;
    row.querySelector('.kv-p-label').textContent = label;
    row.querySelector('.kv-p-ctrl').innerHTML = ctrl;
}

// Before submit: each row's option lines → hidden JSON
function serializeKvRows() {
    document.querySelectorAll('#customKvList .kv-row').forEach(row => {
        const type = row.querySelector('.kv-type').value;
        row.querySelector('.kv-options-json').value = JSON.stringify(KV_CHOICE.includes(type) ? parseKvValues(row.querySelector('.kv-values-text').value) : []);
    });
}

// ─── Tab 9 "Additional Product Attributes" — same field builder as Tab 7, separate data ────
function addAttrKvRow(field) {
    field = field || {};
    const type = KV_TYPES.some(t => t.v === field.type) ? field.type : 'text';
    const req = Number(field.required) ? 1 : 0;
    const row = document.createElement('div');
    row.className = 'kv-row';
    row.innerHTML = `
        <div>
            <input type="text" class="form-control form-control-sm kv-label" name="attribute_labels[]" maxlength="80"
                   placeholder="e.g. Weight, Finish" value="${escHtml(field.label || '')}" oninput="renderAttrKvPreview(this)">
            <input type="text" class="form-control form-control-sm mt-2" name="attribute_help[]" maxlength="160"
                   placeholder="Help text (optional)" value="${escHtml(field.help || '')}">
        </div>
        <div>
            <select class="form-select form-select-sm kv-type" name="attribute_types[]" onchange="onAttrKvTypeChange(this)">
                ${KV_TYPES.map(t => `<option value="${t.v}" ${t.v === type ? 'selected' : ''}>${t.l}</option>`).join('')}
            </select>
            <div class="kv-values">
                <textarea class="form-control form-control-sm kv-values-text" rows="3" placeholder="One option per line, e.g.&#10;Matte&#10;Glossy" oninput="renderAttrKvPreview(this)">${escHtml(kvValuesToText(field.options))}</textarea>
                <div class="kv-hint">One option per line.</div>
            </div>
            <input type="hidden" name="attribute_options[]" class="kv-options-json" value="[]">
        </div>
        <div class="kv-preview"><span class="kv-p-label"></span><span class="kv-p-ctrl"></span></div>
        <div class="kv-tools">
            <div class="form-check form-switch m-0" title="Customer must fill this">
                <input type="hidden" name="attribute_required[]" value="${req}">
                <input class="form-check-input" type="checkbox" ${req ? 'checked' : ''} onchange="this.previousElementSibling.value = this.checked ? 1 : 0">
                <label class="form-check-label small">Req.</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.kv-row').remove()" title="Remove attribute">×</button>
        </div>`;
    const targetKv = document.getElementById('attrKvList') || document.getElementById('customKvList');
    if (targetKv) targetKv.appendChild(row);
    onAttrKvTypeChange(row.querySelector('.kv-type'));
    return row;
}

function onAttrKvTypeChange(select) {
    const row = select.closest('.kv-row');
    row.querySelector('.kv-values').style.display = KV_CHOICE.includes(select.value) ? '' : 'none';
    renderAttrKvPreview(select);
}

function renderAttrKvPreview(el) {
    const row = el.closest('.kv-row');
    const label = row.querySelector('.kv-label').value.trim() || 'Label';
    const type = row.querySelector('.kv-type').value;
    const opts = parseKvValues(row.querySelector('.kv-values-text').value);
    const tag = o => escHtml(o.label) + (o.price > 0 ? ` (+₹${o.price})` : '');
    let ctrl = '';
    if (type === 'select') ctrl = `<select class="form-select form-select-sm" disabled>${opts.map(o => `<option>${tag(o)}</option>`).join('') || '<option>Add options</option>'}</select>`;
    else if (type === 'multi') ctrl = opts.map(o => `<span class="kv-chip">${tag(o)}</span>`).join('') || '<span class="text-muted">Add options</span>';
    else if (type === 'radio') ctrl = opts.map((o, i) => `<div><input type="radio" disabled ${i === 0 ? 'checked' : ''}> ${tag(o)}</div>`).join('') || '<span class="text-muted">Add options</span>';
    else if (type === 'textarea') ctrl = '<textarea class="form-control form-control-sm" rows="2" disabled></textarea>';
    else if (type === 'image') ctrl = '<span class="btn btn-sm btn-outline-primary disabled">⬆ Choose file</span>';
    else ctrl = `<input class="form-control form-control-sm" disabled placeholder="${type === 'number' ? '0' : 'Value'}">`;
    row.querySelector('.kv-p-label').textContent = label;
    row.querySelector('.kv-p-ctrl').innerHTML = ctrl;
}

function serializeAttrKvRows() {
    document.querySelectorAll('#attrKvList .kv-row').forEach(row => {
        const type = row.querySelector('.kv-type').value;
        row.querySelector('.kv-options-json').value = JSON.stringify(KV_CHOICE.includes(type) ? parseKvValues(row.querySelector('.kv-values-text').value) : []);
    });
}

// ─── Customization library picker (section 7) ────────────────────────────────
const CUSTOM_LIBRARY = <?php echo json_encode($customLibrary, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const CHOICE_TYPES = ['select', 'color', 'multi', 'radio'];
const CUSTOM_TYPE_LABELS = <?php echo json_encode(customizationInputTypes()); ?>;

function renderCustomLibrary() {
    const list = document.getElementById('customLibraryList');
    if (!list) return;
    list.innerHTML = CUSTOM_LIBRARY.map(o => {
        const isChoice = CHOICE_TYPES.includes(o.type);
        const values = isChoice ? `<div class="cl-values">${o.values.map((v, i) => `
            <div class="cl-value">
                <input class="form-check-input cl-v-check" type="checkbox" id="clv-${o.id}-${i}" data-label="${escHtml(v.label)}" checked onchange="updateCustomCount()">
                <label for="clv-${o.id}-${i}">${o.type === 'color' && v.hex ? `<span class="cl-dot" style="background:${escHtml(v.hex)}"></span>` : ''}${escHtml(v.label)}</label>
                <div class="input-group input-group-sm" title="Extra price per piece for this product (empty = library price)">
                    <span class="input-group-text">+₹</span>
                    <input type="number" step="0.01" min="0" class="form-control cl-v-price" placeholder="${Number(v.price) || 0}">
                </div>
            </div>`).join('')}</div>` : '<div class="text-muted small">Customer enters this on the product page.</div>';
        return `<div class="cl-card" data-id="${o.id}" data-name="${escHtml(o.name.toLowerCase())}">
            <div class="cl-head">
                <input class="form-check-input cl-use" type="checkbox" id="clu-${o.id}" onchange="onCustomUseChange(this)">
                <label class="cl-name" for="clu-${o.id}">${escHtml(o.name)}</label>
                <span class="cl-type">${escHtml(CUSTOM_TYPE_LABELS[o.type] || o.type)}</span>
                <div class="form-check form-switch m-0 d-flex align-items-center gap-1">
                    <input class="form-check-input cl-required" type="checkbox" id="clr-${o.id}" ${o.is_required ? 'checked' : ''}>
                    <label class="form-check-label small" for="clr-${o.id}">Required</label>
                </div>
            </div>
            <div class="cl-body" style="display:none">
                ${o.help ? `<div class="cl-help">${escHtml(o.help)}</div>` : ''}
                ${values}
            </div>
        </div>`;
    }).join('');
    updateCustomCount();
}

function onCustomUseChange(cb) {
    const card = cb.closest('.cl-card');
    card.classList.toggle('is-on', cb.checked);
    card.querySelector('.cl-body').style.display = cb.checked ? '' : 'none';
    updateCustomCount();
}

function updateCustomCount() {
    const n = document.querySelectorAll('#customLibraryList .cl-use:checked').length;
    const el = document.getElementById('customSelectedCount');
    if (el) el.textContent = `${n} selected`;
}

function filterCustomLibrary() {
    const q = (document.getElementById('customSearch')?.value || '').trim().toLowerCase();
    const onlySel = document.getElementById('customOnlySelected')?.checked;
    document.querySelectorAll('#customLibraryList .cl-card').forEach(card => {
        const on = card.querySelector('.cl-use').checked;
        card.style.display = (!q || card.dataset.name.includes(q)) && (!onlySel || on) ? '' : 'none';
    });
}

// Restore a saved product's ticks, required flags, values and price overrides
function applyCustomAssignments(entries) {
    entries.forEach(e => {
        if (!e || !e.option_id) return;
        const card = document.querySelector(`#customLibraryList .cl-card[data-id="${Number(e.option_id)}"]`);
        if (!card) return;
        const use = card.querySelector('.cl-use');
        use.checked = true;
        onCustomUseChange(use);
        card.querySelector('.cl-required').checked = Number(e.required) === 1;
        if (Array.isArray(e.values)) {
            const saved = {};
            e.values.forEach(v => { saved[String(v.label).toLowerCase()] = v; });
            card.querySelectorAll('.cl-value').forEach(row => {
                const check = row.querySelector('.cl-v-check');
                const hit = saved[check.dataset.label.toLowerCase()];
                check.checked = !!hit;
                row.querySelector('.cl-v-price').value = hit && hit.price !== null && hit.price !== undefined ? hit.price : '';
            });
        }
    });
    updateCustomCount();
}

// Writes the ticked options into the hidden JSON input (runs on submit)
function serializeCustomOptions() {
    const out = [];
    document.querySelectorAll('#customLibraryList .cl-card').forEach(card => {
        if (!card.querySelector('.cl-use').checked) return;
        const entry = { option_id: Number(card.dataset.id), required: card.querySelector('.cl-required').checked ? 1 : 0 };
        const rows = card.querySelectorAll('.cl-value');
        if (rows.length) {
            entry.values = [...rows]
                .filter(r => r.querySelector('.cl-v-check').checked)
                .map(r => {
                    const price = r.querySelector('.cl-v-price').value;
                    return { label: r.querySelector('.cl-v-check').dataset.label, price: price === '' ? null : parseFloat(price) };
                });
        }
        out.push(entry);
    });
    const input = document.getElementById('customAssignments');
    if (input) input.value = JSON.stringify(out);
}

// ─── Image preview ────────────────────────────────────────────────────────────
function previewSingle(input, containerId) {
    const c = document.getElementById(containerId);
    c.innerHTML = '';
    if (input.files?.[0]) {
        const r = new FileReader();
        r.onload = e => { c.innerHTML = `<div class="image-thumb"><img src="${e.target.result}" alt="preview"></div>`; };
        r.readAsDataURL(input.files[0]);
    }
}

function previewMultiple(input, containerId) {
    const c = document.getElementById(containerId);
    c.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const r = new FileReader();
        r.onload = e => { c.innerHTML += `<div class="image-thumb"><img src="${e.target.result}" alt="preview"></div>`; };
        r.readAsDataURL(file);
    });
}

// Drag-over styling
document.querySelectorAll('.upload-zone').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', () => zone.classList.remove('dragover'));
});

// ─── SEO live preview & score ─────────────────────────────────────────────────
function updateSeo() {
    const title = document.getElementById('meta_title').value;
    const desc  = document.getElementById('meta_description').value;
    const kw    = document.getElementById('meta_keywords').value;

    document.getElementById('gpTitle').textContent = title || 'Your product title here';
    document.getElementById('gpDesc').textContent  = desc  || 'Your meta description will appear here...';

    // Counters
    const tc = document.getElementById('titleCounter');
    tc.textContent = title.length + ' / 60';
    tc.className   = 'char-counter ' + (title.length >= 50 && title.length <= 60 ? 'ok' : title.length > 60 ? 'over' : 'warn');

    const dc = document.getElementById('descCounter');
    dc.textContent = desc.length + ' / 160';
    dc.className   = 'char-counter ' + (desc.length >= 140 && desc.length <= 160 ? 'ok' : desc.length > 160 ? 'over' : 'warn');

    // Score
    let score = 0;
    if (title.length >= 50 && title.length <= 60) score += 30;
    else if (title.length > 0) score += 15;
    if (desc.length >= 140 && desc.length <= 160) score += 30;
    else if (desc.length > 0) score += 15;
    const kwCount = kw.split(',').filter(k => k.trim()).length;
    if (kwCount >= 3) score += 20; else if (kwCount > 0) score += 10;
    const name = document.getElementById('product_name').value.toLowerCase();
    if (name && title.toLowerCase().includes(name.substring(0, 10))) score += 20;

    const scoreEl = document.getElementById('seoScore');
    scoreEl.textContent = score;
    scoreEl.style.color = score >= 70 ? '#198754' : score >= 40 ? '#ffc107' : '#dc3545';
}

['meta_title','meta_description','meta_keywords','product_name'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateSeo);
});

// ─── Cancel preview ───────────────────────────────────────────────────────────
function updateCancelPreview() {
    const val  = document.getElementById('cancel_time').value  || '24';
    const unit = document.getElementById('cancel_type').value  || 'hours';
    document.getElementById('cancelPreview').innerHTML =
        `This product can be cancelled within <strong>${val} ${unit}</strong> of order placement.`;
}
document.getElementById('cancel_available').addEventListener('change', function() {
    const f = document.getElementById('cancelFields');
    f.style.display = this.checked ? '' : 'none';
    if (!this.checked) {
        document.getElementById('cancelPreview').innerHTML = '<strong>This product cannot be cancelled once ordered.</strong>';
    } else { updateCancelPreview(); }
});

// ─── Addon product search ─────────────────────────────────────────────────────
let addonSearchTimer;
document.getElementById('addonSearch').addEventListener('input', function() {
    clearTimeout(addonSearchTimer);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('addonResults').classList.add('d-none'); return; }
    addonSearchTimer = setTimeout(() => {
        fetch(`ajax/search-products.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                const res = document.getElementById('addonResults');
                if (!data.length) { res.classList.add('d-none'); return; }
                res.innerHTML = data.map(p => `<div class="addon-result-item" onclick="addAddon(${p.id},'${escHtml(p.name)}','${escHtml(p.sku)}',${p.price},'${escHtml(p.thumb || '')}')">
                    ${p.thumb ? `<img src="${escHtml(p.thumb)}" alt="">` : '<div style="width:40px;height:40px;background:#eee;border-radius:4px"></div>'}
                    <div><div style="font-size:13px;font-weight:600">${escHtml(p.name)}</div><div style="font-size:11px;color:#6c757d">SKU: ${escHtml(p.sku)} · ₹${p.price}</div></div>
                </div>`).join('');
                res.classList.remove('d-none');
            });
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!document.getElementById('addonSearch').contains(e.target)) {
        document.getElementById('addonResults').classList.add('d-none');
    }
});

function addAddon(id, name, sku, price, thumb) {
    if (addonProducts[id]) return;
    addonProducts[id] = { id, name, sku, price, thumb };
    renderAddonCards();
    document.getElementById('addonSearch').value = '';
    document.getElementById('addonResults').classList.add('d-none');
}

function removeAddon(id) {
    delete addonProducts[id];
    renderAddonCards();
}

function renderAddonCards() {
    const ids = Object.keys(addonProducts);
    document.getElementById('addonIdsInput').value = JSON.stringify(ids.map(Number));
    document.getElementById('addonCards').innerHTML = ids.map(id => {
        const p = addonProducts[id];
        return `<div class="addon-card">
            ${p.thumb ? `<img src="${escHtml(p.thumb)}" alt="">` : '<div style="width:44px;height:44px;background:#eee;border-radius:4px"></div>'}
            <div><div style="font-size:13px;font-weight:600">${escHtml(p.name)}</div><div style="font-size:11px;color:#6c757d">₹${p.price}</div></div>
            <button type="button" class="remove-addon" onclick="removeAddon(${id})">✕</button>
        </div>`;
    }).join('');
}

function escHtml(s) { return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

// ─── Quick summary (Tab 12) ───────────────────────────────────────────────────
function populateSummary() {
    const v = id => document.getElementById(id)?.value || '—';
    document.getElementById('sumName').textContent   = v('product_name');
    document.getElementById('sumSku').textContent    = v('sku_code');
    document.getElementById('sumMrp').textContent    = v('regular_price');
    document.getElementById('sumOffer').textContent  = v('offer_price') || '—';
    document.getElementById('sumQty').textContent    = v('product_quantity');
    // select[...] — the "Save Draft" submit button is also named "status" and comes first in the page
    const statEl = document.querySelector('select[name="status"]');
    document.getElementById('sumStatus').textContent = statEl ? statEl.options[statEl.selectedIndex].text : '—';
    const catEl  = document.getElementById('category_id');
    document.getElementById('sumCat').textContent    = catEl?.options[catEl.selectedIndex]?.text || '—';
}

// ─── Category cascades ────────────────────────────────────────────────────────
function loadSubCategories(parentId, selectedId) {
    const sub    = document.getElementById('sub_category_id');
    const subsub = document.getElementById('sub_sub_category_id');
    sub.innerHTML    = '<option value="">Loading...</option>';
    subsub.innerHTML = '<option value="">— Select Sub First —</option>';
    if (!parentId) { sub.innerHTML = '<option value="">— Select Main Category First —</option>'; return Promise.resolve(); }
    return fetch(`ajax/get-sub-categories.php?parent_id=${parentId}`)
        .then(r => r.json())
        .then(data => {
            sub.innerHTML = '<option value="">— Select Sub Category —</option>';
            data.forEach(c => sub.innerHTML += `<option value="${c.id}">${escHtml(c.name)}</option>`);
            if (selectedId) sub.value = String(selectedId);
        })
        .catch(() => { sub.innerHTML = '<option value="">— Could not load —</option>'; });
}

function loadSubSubCategories(parentId, selectedId) {
    const subsub = document.getElementById('sub_sub_category_id');
    subsub.innerHTML = '<option value="">Loading...</option>';
    if (!parentId) { subsub.innerHTML = '<option value="">— Select Sub Category First —</option>'; return Promise.resolve(); }
    return fetch(`ajax/get-sub-sub-categories.php?parent_id=${parentId}`)
        .then(r => r.json())
        .then(data => {
            subsub.innerHTML = '<option value="">— Select Sub Sub Category —</option>';
            data.forEach(c => subsub.innerHTML += `<option value="${c.id}">${escHtml(c.name)}</option>`);
            if (selectedId) subsub.value = String(selectedId);
        })
        .catch(() => { subsub.innerHTML = '<option value="">— Could not load —</option>'; });
}

// ─── Edit mode: remove an already-saved image ────────────────────────────────
function removeExistingImg(btn) {
    const wrap = btn.closest('.existing-img-wrap');
    if (wrap) wrap.remove(); // its hidden input goes with it, so the image is dropped on save
}

// ─── Edit mode: fill the form from EDIT_PRODUCT ──────────────────────────────
function parseList(v) {
    if (Array.isArray(v)) return v;
    if (!v) return [];
    try { const out = JSON.parse(v); return Array.isArray(out) ? out : []; } catch (e) { return []; }
}

function fillRows(tbodyId, items, rowHtml) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody || !items.length) return;
    tbody.innerHTML = items.map(rowHtml).join('');
}

function pmImgSrc(p) {
    if (!p) return '';
    return (p.startsWith('http') || p.startsWith('//')) ? p : p.replace(/^\/+/, '');
}

function pmAddColorVariantRow(name = '', hex = '#000000', price = 0, existingImg = '', text = '') {
    const tbody = document.getElementById('tab2ColorBody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" name="variant_color_name[]" value="${escHtml(name)}" placeholder="e.g. Royal Blue" required>
        </td>
        <td>
            <div class="d-flex align-items-center gap-1">
                <input type="color" class="form-control form-control-sm form-control-color p-0" name="variant_color_hex[]" value="${escHtml(hex || '#000000')}" style="width:32px;height:31px;" onchange="this.nextElementSibling.value=this.value">
                <input type="text" class="form-control form-control-sm font-monospace p-1" value="${escHtml(hex || '#000000')}" style="font-size:11px;width:70px;" oninput="this.previousElementSibling.value=this.value">
            </div>
        </td>
        <td>
            <div class="d-flex align-items-center gap-2">
                ${existingImg ? `
                    <a href="${escHtml(pmImgSrc(existingImg))}" target="_blank" title="View variant image">
                        <img src="${escHtml(pmImgSrc(existingImg))}" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:4px;border:1px solid #cbd5e1;flex-shrink:0;">
                    </a>
                    <input type="hidden" name="existing_variant_color_images[]" value="${escHtml(existingImg)}">
                ` : `<input type="hidden" name="existing_variant_color_images[]" value="">`}
                <input type="file" class="form-control form-control-sm" name="variant_color_images[]" accept="image/png,image/jpeg,image/gif,image/webp">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" min="0" class="form-control" name="variant_color_price[]" value="${escHtml(price || 0)}" placeholder="0.00">
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="variant_color_text[]" value="${escHtml(text || '')}" placeholder="e.g. 100% Bio-Wash">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()" title="Remove color">
                <i class="fa fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    const sw = document.getElementById('tab2_show_colors');
    if (sw) sw.checked = true;
    const wrp = document.getElementById('colorVariantsWrapper');
    if (wrp) wrp.style.display = 'block';
}

function pmAddSizeRow(name = '', price = 0) {
    const tbody = document.getElementById('tab2SizeBody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" name="size_names[]" value="${escHtml(name)}" placeholder="e.g. M / Large" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" min="0" class="form-control" name="size_prices[]" value="${escHtml(price || 0)}" placeholder="0.00">
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()" title="Remove size">
                <i class="fa fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    const sw = document.getElementById('tab2_show_sizes');
    if (sw) sw.checked = true;
    const wrp = document.getElementById('sizeVariantsWrapper');
    if (wrp) wrp.style.display = 'block';
}

function pmAddBulkTierRow(minQty = '', maxQty = '', price = '') {
    const tbody = document.getElementById('tab2BulkBody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="number" min="1" class="form-control form-control-sm" name="bulk_min_qty[]" value="${escHtml(minQty || '')}" placeholder="1" required>
        </td>
        <td>
            <input type="number" min="0" class="form-control form-control-sm" name="bulk_max_qty[]" value="${escHtml(maxQty || 0)}" placeholder="0">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" min="0" class="form-control" name="bulk_price[]" value="${escHtml(price || '')}" placeholder="0.00" required>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" onclick="this.closest('tr').remove()" title="Remove tier">
                <i class="fa fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    const sw = document.getElementById('tab2_show_bulk');
    if (sw) sw.checked = true;
    const wrp = document.getElementById('bulkTiersWrapper');
    if (wrp) wrp.style.display = 'block';
}

function prefillProductForm(p) {
    const form = document.getElementById('productForm');
    const has = v => v !== null && v !== undefined && v !== '';

    const setVal = (name, val) => {
        if (!has(val)) return;
        form.querySelectorAll(`[name="${name}"]`).forEach(el => {
            if (el.tagName === 'SELECT' && ![...el.options].some(o => o.value == val)) {
                el.add(new Option(val, val)); // keep a saved value even if it is not in the preset list
            }
            el.value = val;
        });
    };
    const setCheck = (name, val, sectionId) => {
        form.querySelectorAll(`[name="${name}"]`).forEach(el => { el.checked = !!Number(val); });
        if (sectionId) {
            const box = document.getElementById(sectionId);
            if (box) box.style.display = Number(val) ? '' : 'none';
        }
    };

    // 1. General & pricing
    setVal('product_name', p.name);
    setVal('sku_code', p.sku);
    setVal('product_slug', p.product_slug);
    setVal('sort_order', p.sort_order);
    setVal('regular_price', has(p.regular_price) && Number(p.regular_price) > 0 ? p.regular_price : p.price);
    const offer = Number(p.offer_price) > 0 ? p.offer_price : (Number(p.discount_price) > 0 ? p.discount_price : '');
    setVal('offer_price', offer);
    setVal('product_quantity', has(p.stock_quantity) ? p.stock_quantity : p.product_quantity);
    setVal('minimum_quantity', p.minimum_quantity);
    setVal('out_of_stock_status', p.out_of_stock_status);
    ['description', 'long_description', 'instructions', 'delivery_info'].forEach(f => setVal(f, p[f]));

    // 2. Filters
    ['color', 'type', 'material', 'occasion', 'brand', 'discount_type', 'shape', 'gender', 'gift_type', 'ideal_for',
     'customization_tech', 'customization_location', 'capacity', 'ink_color', 'features'].forEach(f => setVal(f, p[f]));

    // 3. Categories & flags
    setVal('category_id', p.category_id);
    if (p.category_id) {
        loadSubCategories(p.category_id, p.sub_category_id).then(() => {
            if (p.sub_category_id) loadSubSubCategories(p.sub_category_id, p.sub_sub_category_id);
        });
    }
    setVal('homepage_category_id', p.homepage_category_id);
    setVal('status', p.status);
    ['featured', 'top_selection', 'our_bestseller', 'top_rated', 'top_deal_by_categories', 'is_assured'].forEach(f => setCheck(f, p[f]));

    // 4. Media
    setVal('alt_tag', p.alt_tag);

    // 5. SEO
    setVal('meta_title', p.meta_title);
    setVal('meta_keywords', p.meta_keywords);
    setVal('meta_description', p.meta_description);

    // 7. Status Show and Hide Button
    setCheck('show_cart_buttons', p.show_cart_buttons !== false && p.show_cart_buttons !== 0 && p.show_cart_buttons !== '0');
    setCheck('show_quantity', p.show_quantity !== false && p.show_quantity !== 0 && p.show_quantity !== '0');
    setCheck('show_help_button', p.show_help_button, 'helpBtnConfig');
    setVal('help_button_text', p.help_button_text);
    setVal('help_button_link', p.help_button_link);
    setCheck('show_bulk_form', p.show_bulk_form);
    setCheck('show_inquiry_form', p.show_inquiry_form);

    // 8. Customization & Design Buttons (Tab 8)
    setCheck('enable_upload_design', p.enable_upload_design !== false && p.enable_upload_design !== 0 && p.enable_upload_design !== '0', 'uploadDesignConfig');
    setVal('upload_design_max_images', p.upload_design_max_images ?? 4);
    setVal('upload_design_max_texts', p.upload_design_max_texts ?? 2);
    setCheck('enable_customizer', p.enable_customizer !== false && p.enable_customizer !== 0 && p.enable_customizer !== '0', 'customizerConfig');
    setVal('customizer_template_id', p.customizer_template_id || '');
    setCheck('show_customization_label', p.show_customization_label, 'badgeTextConfig');
    setVal('customization_label', p.customization_label);

    // Tab 2: Product Attributes, Custom Fields & Saved Library Options
    const savedCustom = parseList(p.customization_fields);
    applyCustomAssignments(savedCustom);
    savedCustom.filter(f => f && !f.option_id && f.label).forEach(f => addKvRow(f));
    parseList(p.product_attributes_fields).filter(f => f && f.label).forEach(f => addKvRow(f));

    // 8. Addons: show saved ones straight away, then fill in names/prices
    parseList(p.addon_product_ids).map(Number).filter(Boolean).forEach(id => {
        addonProducts[id] = { id, name: `Product #${id}`, sku: '', price: '—', thumb: '' };
        fetch(`api/product-api.php?id=${id}`)
            .then(r => r.json())
            .then(res => {
                const d = res && (res.data || res.product);
                if (!d || !addonProducts[id]) return;
                addonProducts[id] = { id, name: d.name || `Product #${id}`, sku: d.sku || '', price: d.selling_price ?? d.price ?? '—', thumb: d.thumbnail || d.primary_image || '' };
                renderAddonCards();
            })
            .catch(() => {});
    });
    renderAddonCards();

    // Tab 2 Variants
    if (document.getElementById('tab2ColorBody') && !document.getElementById('tab2ColorBody').children.length) {
        parseList(p.color_attributes).forEach(r => {
            pmAddColorVariantRow(r.name ?? r ?? '', r.hex ?? '#000000', r.price ?? 0, r.image ?? '', r.text ?? '');
        });
    }
    if (document.getElementById('tab2SizeBody') && !document.getElementById('tab2SizeBody').children.length) {
        parseList(p.size_attributes).forEach(r => {
            pmAddSizeRow(r.name ?? r ?? '', r.price ?? 0);
        });
    }
    if (document.getElementById('tab2BulkBody') && !document.getElementById('tab2BulkBody').children.length) {
        parseList(p.bulk_price_variance || p.quantity_price_breaks).forEach(r => {
            pmAddBulkTierRow(r.min_qty ?? r.qty ?? '', r.max_qty ?? '', r.price ?? '');
        });
    }

    // 9. Attributes
    setCheck('show_sizes', p.show_sizes, 'sizesSection');
    fillRows('sizesBody', parseList(p.size_attributes), r => `<tr>
        <td><input type="text" class="form-control form-control-sm" name="size_names[]" value="${escHtml(r.name ?? r ?? '')}"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="size_prices[]" min="0" value="${escHtml(r.price ?? 0)}"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
    </tr>`);
    setCheck('show_colors', p.show_colors, 'colorsSection');
    fillRows('colorsBody', parseList(p.color_attributes), r => `<tr>
        <td><input type="text" class="form-control form-control-sm" name="color_names[]" value="${escHtml(r.name ?? r ?? '')}"></td>
        <td><input type="color" class="form-control form-control-sm form-control-color" name="color_hexes[]" value="${escHtml(/^#[0-9a-f]{6}$/i.test(r.hex || '') ? r.hex : '#000000')}"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="color_prices[]" min="0" value="${escHtml(r.price ?? 0)}"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
    </tr>`);
    setVal('attr_material', parseList(p.material_attributes).join(', '));
    setVal('attr_lamination', parseList(p.lamination_attributes).join(', '));
    setVal('attr_orientation', parseList(p.orientation_attributes)[0]);
    setVal('attr_printing_location', parseList(p.printing_location).join(', '));
    fillRows('qtyBody', parseList(p.quantity_price_breaks), r => `<tr>
        <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" min="1" value="${escHtml(r.qty ?? '')}"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" min="0" value="${escHtml(r.price ?? '')}"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
    </tr>`);

    // 10. Shipping
    setCheck('shipping_method_status', p.shipping_method_status, 'shippingFields');
    ['local', 'regional', 'national'].forEach(z => {
        setVal(`${z}_shipping_charge`, p[`${z}_shipping_charge`]);
        setVal(`${z}_shipping_message`, p[`${z}_shipping_message`]);
    });

    // 11. Cancellation
    setCheck('cancel_available', p.cancel_available, 'cancelFields');
    setVal('cancel_time', p.cancel_time);
    setVal('cancel_type', p.cancel_type);

    // 12. COD
    setCheck('cod_available', p.cod_available);

    // Refresh the live helpers that depend on these values
    document.getElementById('gpSlug').textContent = document.getElementById('product_slug').value || 'product-slug';
    updateDiscount();
    updateSeo();
    if (document.getElementById('cancel_available').checked) {
        updateCancelPreview();
    } else {
        document.getElementById('cancelPreview').innerHTML = '<strong>This product cannot be cancelled once ordered.</strong>';
    }
}

// ─── CKEditor & Form Init ───────────────────────────────────────────────────
function initCKEditors() {
    if (typeof CKEDITOR === 'undefined') return;
    ['description', 'long_description', 'instructions', 'delivery_info'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !CKEDITOR.instances[id]) {
            try {
                CKEDITOR.replace(id);
            } catch (e) {
                console.warn('CKEditor init warning for ' + id, e);
            }
        }
    });
}

function initAddProductPage() {
    renderCustomLibrary();
    // Fill the form before CKEditor takes over the textareas
    if (EDIT_PRODUCT) prefillProductForm(EDIT_PRODUCT);
    if (!document.querySelector('#customKvList .kv-row')) addKvRow();
    initCKEditors();

    // Populate live summary initially
    populateSummary();

    // Live update summary as user types
    ['product_name', 'sku_code', 'regular_price', 'offer_price', 'product_quantity', 'category_id'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', populateSummary);
            el.addEventListener('change', populateSummary);
        }
    });
    const statSelect = document.querySelector('select[name="status"]');
    if (statSelect) statSelect.addEventListener('change', populateSummary);

    const form = document.getElementById('productForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // 0. Customization: key-value rows + ticked library options → hidden JSON
            serializeKvRows();
            serializeCustomOptions();
            serializeAttrKvRows();

            // 1. Sync CKEditors
            if (typeof CKEDITOR !== 'undefined') {
                for (const instanceName in CKEDITOR.instances) {
                    if (CKEDITOR.instances[instanceName]) {
                        CKEDITOR.instances[instanceName].updateElement();
                    }
                }
            }

            // 2. Draft submission handling
            if (e.submitter && e.submitter.value === 'draft') {
                if (statSelect) statSelect.value = 'draft';
                return true;
            }

            // 3. Form Validation for publishing
            const requiredFields = form.querySelectorAll('[required]');
            let firstInvalid = null;
            requiredFields.forEach(el => {
                el.classList.remove('is-invalid');
                if (!el.value || !el.value.trim()) {
                    el.classList.add('is-invalid');
                    if (!firstInvalid) firstInvalid = el;
                }
            });

            if (firstInvalid) {
                e.preventDefault();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
                const parentCard = firstInvalid.closest('.product-section-card');
                const titleText = parentCard ? parentCard.querySelector('.product-section-title')?.textContent?.trim() : 'a required field';
                alert('Please fill out all required fields before publishing.\nMissing field in: ' + titleText);
                return false;
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAddProductPage);
} else {
    initAddProductPage();
}

</script>
</body>
</html>
