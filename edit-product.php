<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');
require_once(__DIR__ . '/controllers/ProductController.php');

$database = new Database();
$db       = $database->getConnection();

$productController = new ProductController($db);
$categoryCtrl      = new CategoryController($db);

// ── Load product ──────────────────────────────────────────────────────────────
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$productId) {
    $_SESSION['error_message'] = 'No product ID provided.';
    header('Location: view-products.php');
    exit;
}

$p = $productController->getProductById($productId);
if (!$p) {
    $_SESSION['error_message'] = 'Product not found.';
    header('Location: view-products.php');
    exit;
}

$mainCategories = $categoryCtrl->getMainCategories();

// ── Handle form submission ────────────────────────────────────────────────────
$error_message   = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $productController->updateProduct($productId, $_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = 'Product updated successfully!';
        header('Location: view-products.php');
        exit;
    } else {
        $error_message = $result['error'];
        $p = $productController->getProductById($productId);
    }
}

// ── Decode JSON fields ────────────────────────────────────────────────────────
$bulkPricing    = json_decode($p['bulk_price_variance'] ?? '[]', true) ?: [];
$customFields   = json_decode($p['customization_fields'] ?? '[]', true) ?: [];
$sizeAttrs      = json_decode($p['size_attributes'] ?? '[]', true) ?: [];
$colorAttrs     = json_decode($p['color_attributes'] ?? '[]', true) ?: [];
$galleryImages  = json_decode($p['gallery_images'] ?? '[]', true) ?: [];
$addonIds       = json_decode($p['addon_product_ids'] ?? '[]', true) ?: [];
$qtyPriceBreaks = json_decode($p['quantity_price_breaks'] ?? '[]', true) ?: [];
$matAttr        = json_decode($p['material_attributes'] ?? '[]', true) ?: [];
$lamAttr        = json_decode($p['lamination_attributes'] ?? '[]', true) ?: [];
$oriAttr        = json_decode($p['orientation_attributes'] ?? '[]', true) ?: [];

$existingMainImgs = $p['main_images'] ?? [];

function chk($val)            { return $val ? 'checked' : ''; }
function sel($current, $opt)  { return $current == $opt ? 'selected' : ''; }
function esc($v)              { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Edit Product | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-tabs-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .product-tabs { display: flex; flex-wrap: nowrap; gap: 4px; padding: 0; margin: 0; list-style: none; border-bottom: 2px solid #dee2e6; min-width: max-content; }
        .product-tabs .tab-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 10px 16px; border: none; background: #f8f9fa;
            border-radius: 6px 6px 0 0; cursor: pointer; white-space: nowrap;
            font-size: 13px; font-weight: 500; color: #495057;
            border: 1px solid #dee2e6; border-bottom: none;
            transition: background .15s, color .15s;
        }
        .product-tabs .tab-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .product-tabs .tab-btn .tab-num {
            width: 22px; height: 22px; border-radius: 50%;
            background: rgba(0,0,0,.15); display: inline-flex;
            align-items: center; justify-content: center; font-size: 11px; font-weight: 700;
        }
        .product-tabs .tab-btn.active .tab-num { background: rgba(255,255,255,.25); }
        .tab-panel { display: none; padding: 28px 0 0; }
        .tab-panel.active { display: block; }
        .form-progress { height: 6px; border-radius: 3px; background: #e9ecef; margin-bottom: 20px; }
        .form-progress-bar { height: 100%; border-radius: 3px; background: #0d6efd; transition: width .3s; }
        .required-star { color: #dc3545; }
        .section-heading { font-size: 15px; font-weight: 600; color: #343a40; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; margin-bottom: 20px; }
        .dynamic-table thead th { font-size: 12px; font-weight: 600; background: #f8f9fa; }
        .dynamic-table .btn-remove-row { padding: 2px 8px; }
        .add-row-btn { font-size: 13px; }
        .toggle-row { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f0f0f0; }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-label-wrap { flex: 1; }
        .toggle-label-wrap strong { display: block; font-size: 14px; margin-bottom: 2px; }
        .toggle-label-wrap small { color: #6c757d; }
        .form-switch .form-check-input { width: 3em; height: 1.5em; }
        .upload-zone {
            border: 2px dashed #ced4da; border-radius: 8px; padding: 20px;
            text-align: center; cursor: pointer; transition: border-color .2s;
            position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: #0d6efd; background: #f0f4ff; }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-zone-icon { font-size: 26px; color: #adb5bd; margin-bottom: 4px; }
        .upload-zone p { margin: 0; color: #6c757d; font-size: 12px; }
        .image-preview-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .image-thumb { position: relative; width: 90px; height: 90px; }
        .image-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6; }
        .image-thumb .remove-img { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%; background: #dc3545; color: #fff; border: none; font-size: 11px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .existing-img-wrap .badge-primary { position: absolute; bottom: 2px; left: 2px; font-size: 9px; }
        .google-preview { border: 1px solid #dee2e6; border-radius: 8px; padding: 18px 20px; background: #fff; margin-top: 16px; }
        .gp-title { font-size: 18px; color: #1a0dab; font-family: Arial, sans-serif; }
        .gp-url { color: #006621; font-size: 13px; margin: 2px 0; }
        .gp-desc { color: #545454; font-size: 14px; font-family: Arial, sans-serif; }
        .char-counter { font-size: 12px; }
        .char-counter.ok { color: #198754; }
        .char-counter.warn { color: #ffc107; }
        .char-counter.over { color: #dc3545; }
        .seo-score { font-size: 28px; font-weight: 700; }
        .addon-search-results { position: absolute; z-index: 100; width: 100%; background: #fff; border: 1px solid #dee2e6; border-radius: 0 0 6px 6px; max-height: 260px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .addon-result-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .addon-result-item:hover { background: #f8f9fa; }
        .addon-result-item img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
        .addon-cards { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .addon-card { display: flex; align-items: center; gap: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px 12px; }
        .addon-card img { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
        .addon-card .remove-addon { background: none; border: none; color: #dc3545; font-size: 16px; cursor: pointer; margin-left: auto; }
        .form-nav { display: flex; justify-content: space-between; align-items: center; padding-top: 24px; margin-top: 24px; border-top: 1px solid #dee2e6; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3><strong>Edit</strong> Product <small class="text-muted" style="font-size:14px">ID #<?php echo $productId; ?></small></h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="view-products.php" class="btn btn-outline-secondary">View Products</a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body pb-0">

                        <div class="form-progress mb-1">
                            <div class="form-progress-bar" id="progressBar" style="width:8%"></div>
                        </div>

                        <div class="product-tabs-wrap">
                            <ul class="product-tabs" id="productTabs">
                                <?php
                                $tabs = [
                                    1  => 'General Info',
                                    2  => 'Filters & Tags',
                                    3  => 'Category Links',
                                    4  => 'Images & Media',
                                    5  => 'SEO Content',
                                    6  => 'Visibility',
                                    7  => 'Customization',
                                    8  => 'Addon Products',
                                    9  => 'Attributes',
                                    10 => 'Shipping',
                                    11 => 'Cancellation',
                                    12 => 'Cash on Delivery',
                                ];
                                foreach ($tabs as $num => $name) {
                                    $active = $num === 1 ? 'active' : '';
                                    echo "<li><button type='button' class='tab-btn $active' data-tab='$num'><span class='tab-num'>$num</span> $name</button></li>";
                                }
                                ?>
                            </ul>
                        </div>

                    </div>

                    <form method="POST" enctype="multipart/form-data" id="productForm" novalidate>

                        <div class="card-body">

                            <!-- ══ TAB 1 – GENERAL INFO ══ -->
                            <div class="tab-panel active" id="tab-1">
                                <p class="section-heading">Tab 1 — General Information</p>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Product Name <span class="required-star">*</span></label>
                                        <input type="text" class="form-control" name="product_name" id="product_name" required
                                               value="<?php echo esc($p['name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SKU Code <span class="required-star">*</span></label>
                                        <input type="text" class="form-control" name="sku_code" required
                                               value="<?php echo esc($p['sku']); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Product Slug <span class="required-star">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted" style="font-size:12px">yoursite.com/product/</span>
                                            <input type="text" class="form-control" name="product_slug" id="product_slug" required
                                                   value="<?php echo esc($p['product_slug']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" name="sort_order" min="0"
                                               value="<?php echo (int)($p['sort_order'] ?? 0); ?>">
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label">MRP (₹) <span class="required-star">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="regular_price" id="regular_price" required min="0"
                                                   value="<?php echo esc($p['regular_price'] ?? $p['price']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Offer Price (₹)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="offer_price" id="offer_price" min="0"
                                                   value="<?php echo esc($p['offer_price'] ?? $p['discount_price'] ?? ''); ?>">
                                        </div>
                                        <div id="discount_pct" class="text-success mt-1" style="font-size:12px"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Stock Qty <span class="required-star">*</span></label>
                                        <input type="number" class="form-control" name="product_quantity" required min="0"
                                               value="<?php echo (int)($p['stock_quantity'] ?? $p['product_quantity'] ?? 0); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Min Order Qty</label>
                                        <input type="number" class="form-control" name="minimum_quantity" min="1"
                                               value="<?php echo (int)($p['minimum_quantity'] ?? 1); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Out of Stock Status</label>
                                        <select class="form-select" name="out_of_stock_status">
                                            <option value="in_stock"    <?php echo sel($p['out_of_stock_status'], 'in_stock');    ?>>In Stock</option>
                                            <option value="out_of_stock"<?php echo sel($p['out_of_stock_status'], 'out_of_stock');?>>Out of Stock</option>
                                            <option value="pre_order"   <?php echo sel($p['out_of_stock_status'], 'pre_order');   ?>>Pre Order</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Bulk Order Pricing Tiers</label>
                                    <table class="table table-bordered table-sm dynamic-table">
                                        <thead><tr><th>Min Qty</th><th>Max Qty</th><th>Price / unit (₹)</th><th style="width:60px">% Off</th><th style="width:40px"></th></tr></thead>
                                        <tbody id="bulkPricingBody">
                                            <?php if (!empty($bulkPricing)): foreach ($bulkPricing as $bp): ?>
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="bulk_min_qty[]" value="<?php echo (int)$bp['min_qty']; ?>" min="1"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="bulk_max_qty[]" value="<?php echo (int)$bp['max_qty']; ?>" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm bulk-price" name="bulk_price[]" value="<?php echo (float)$bp['price']; ?>" min="0"></td>
                                                <td><span class="bulk-disc text-success fw-bold" style="font-size:12px"></span></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                            <?php endforeach; else: ?>
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="bulk_min_qty[]" placeholder="10" min="1"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="bulk_max_qty[]" placeholder="50" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm bulk-price" name="bulk_price[]" placeholder="0.00" min="0"></td>
                                                <td><span class="bulk-disc text-success fw-bold" style="font-size:12px"></span></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="addBulkRow">+ Add Tier</button>
                                </div>

                                <div class="mt-4">
                                    <label class="form-label">Short Description <span class="required-star">*</span></label>
                                    <textarea class="form-control" name="description" id="description" rows="3" required><?php echo esc($p['description']); ?></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Long Description</label>
                                    <textarea class="form-control" name="long_description" id="long_description" rows="5"><?php echo esc($p['long_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Instructions / Usage</label>
                                    <textarea class="form-control" name="instructions" id="instructions" rows="4"><?php echo esc($p['instructions'] ?? ''); ?></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Delivery Information</label>
                                    <textarea class="form-control" name="delivery_info" id="delivery_info" rows="4"><?php echo esc($p['delivery_info'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- ══ TAB 2 – FILTERS ══ -->
                            <div class="tab-panel" id="tab-2">
                                <p class="section-heading">Tab 2 — Filters & Tags</p>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">Color</label><input type="text" class="form-control" name="color" value="<?php echo esc($p['color']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Type</label><input type="text" class="form-control" name="type" value="<?php echo esc($p['type']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Material</label><input type="text" class="form-control" name="material" value="<?php echo esc($p['material']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Occasion</label><input type="text" class="form-control" name="occasion" value="<?php echo esc($p['occasion']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Brand</label><input type="text" class="form-control" name="brand" value="<?php echo esc($p['brand']); ?>"></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Discount Range</label>
                                        <select class="form-select" name="discount_type">
                                            <option value="">— Select —</option>
                                            <option value="upto_10" <?php echo sel($p['discount_type'], 'upto_10'); ?>>Up to 10%</option>
                                            <option value="10_25"   <?php echo sel($p['discount_type'], '10_25');   ?>>10% – 25%</option>
                                            <option value="25_50"   <?php echo sel($p['discount_type'], '25_50');   ?>>25% – 50%</option>
                                            <option value="above_50"<?php echo sel($p['discount_type'], 'above_50');?>>Above 50%</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label">Shape</label><input type="text" class="form-control" name="shape" value="<?php echo esc($p['shape']); ?>"></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender">
                                            <option value="">— Select —</option>
                                            <option value="male"   <?php echo sel($p['gender'], 'male');   ?>>Male</option>
                                            <option value="female" <?php echo sel($p['gender'], 'female'); ?>>Female</option>
                                            <option value="unisex" <?php echo sel($p['gender'], 'unisex'); ?>>Unisex</option>
                                            <option value="kids"   <?php echo sel($p['gender'], 'kids');   ?>>Kids</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label">Gift Type</label><input type="text" class="form-control" name="gift_type" value="<?php echo esc($p['gift_type']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Ideal For</label><input type="text" class="form-control" name="ideal_for" value="<?php echo esc($p['ideal_for']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Customization Tech</label><input type="text" class="form-control" name="customization_tech" value="<?php echo esc($p['customization_tech']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Customization Location</label><input type="text" class="form-control" name="customization_location" value="<?php echo esc($p['customization_location']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Capacity</label><input type="text" class="form-control" name="capacity" value="<?php echo esc($p['capacity']); ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Ink Color</label><input type="text" class="form-control" name="ink_color" value="<?php echo esc($p['ink_color']); ?>"></div>
                                    <div class="col-12"><label class="form-label">Features</label><textarea class="form-control" name="features" rows="2"><?php echo esc($p['features']); ?></textarea></div>
                                </div>
                            </div>

                            <!-- ══ TAB 3 – CATEGORIES ══ -->
                            <div class="tab-panel" id="tab-3">
                                <p class="section-heading">Tab 3 — Category & Carousel Links</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Main Category <span class="required-star">*</span></label>
                                        <select class="form-select" name="category_id" id="category_id" required>
                                            <option value="">— Select Main Category —</option>
                                            <?php foreach ($mainCategories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo sel($p['category_id'], $cat['id']); ?>>
                                                    <?php echo esc($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sub Category</label>
                                        <select class="form-select" name="sub_category_id" id="sub_category_id">
                                            <option value="">Loading...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sub Sub Category</label>
                                        <select class="form-select" name="sub_sub_category_id" id="sub_sub_category_id">
                                            <option value="">Loading...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Homepage Category</label>
                                        <select class="form-select" name="homepage_category_id">
                                            <option value="">— None —</option>
                                            <?php foreach ($mainCategories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo sel($p['homepage_category_id'] ?? '', $cat['id']); ?>><?php echo esc($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Product Status</label>
                                        <select class="form-select" name="status">
                                            <option value="active"   <?php echo sel($p['status'], 'active');   ?>>Active</option>
                                            <option value="inactive" <?php echo sel($p['status'], 'inactive'); ?>>Inactive</option>
                                            <option value="draft"    <?php echo sel($p['status'], 'draft');    ?>>Draft</option>
                                        </select>
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
                                    ] as $fname => $flabel): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="<?php echo $fname; ?>" value="1" id="<?php echo $fname; ?>" <?php echo chk($p[$fname] ?? 0); ?>>
                                            <label class="form-check-label" for="<?php echo $fname; ?>"><?php echo $flabel; ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ══ TAB 4 – IMAGES ══ -->
                            <div class="tab-panel" id="tab-4">
                                <p class="section-heading">Tab 4 — Images & Media</p>
                                <div class="mb-3">
                                    <label class="form-label">Image ALT Tag</label>
                                    <input type="text" class="form-control" name="alt_tag" value="<?php echo esc($p['alt_tag']); ?>">
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Thumbnail Image</label>
                                        <?php if (!empty($p['thumbnail_image'])): ?>
                                            <div class="mb-2 d-flex align-items-center gap-3">
                                                <img src="<?php echo esc($p['thumbnail_image']); ?>" alt="Current thumbnail"
                                                     style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6">
                                                <div>
                                                    <div class="text-muted small">Current thumbnail</div>
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="remove_thumbnail" value="1" id="rmThumb">
                                                        <label class="form-check-label text-danger small" for="rmThumb">Remove this thumbnail</label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="upload-zone">
                                            <input type="file" name="thumbnail_image" accept="image/*" onchange="previewSingle(this,'thumbPreview')">
                                            <div class="upload-zone-icon">🖼️</div>
                                            <p>Upload new thumbnail (replaces current)</p>
                                            <p class="mt-1"><small>JPEG / PNG / WebP · max 5 MB</small></p>
                                        </div>
                                        <div class="image-preview-grid" id="thumbPreview"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Main Product Images</label>
                                        <?php if (!empty($existingMainImgs)): ?>
                                            <div class="mb-2">
                                                <div class="text-muted small mb-1">Current images — click × to remove:</div>
                                                <div class="image-preview-grid">
                                                    <?php foreach ($existingMainImgs as $idx => $img): ?>
                                                    <div class="image-thumb existing-img-wrap">
                                                        <img src="<?php echo esc($img['image_url']); ?>" alt="">
                                                        <input type="hidden" name="existing_main_images[]" value="<?php echo esc($img['image_url']); ?>" id="exImg<?php echo $idx; ?>">
                                                        <button type="button" class="remove-img" onclick="removeExistingImg(this,'exImg<?php echo $idx; ?>')">×</button>
                                                        <?php if (!empty($img['is_primary'])): ?>
                                                            <span class="badge bg-primary badge-primary">Primary</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="upload-zone">
                                            <input type="file" name="main_images[]" accept="image/*" multiple onchange="previewMultiple(this,'mainPreview')">
                                            <div class="upload-zone-icon">📷</div>
                                            <p>Upload additional main images</p>
                                        </div>
                                        <div class="image-preview-grid" id="mainPreview"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Product Gallery</label>
                                        <?php if (!empty($galleryImages)): ?>
                                            <div class="mb-2">
                                                <div class="text-muted small mb-1">Current gallery — click × to remove:</div>
                                                <div class="image-preview-grid">
                                                    <?php foreach ($galleryImages as $gidx => $gimg): ?>
                                                    <div class="image-thumb existing-img-wrap">
                                                        <img src="<?php echo esc($gimg); ?>" alt="">
                                                        <input type="hidden" name="existing_gallery_images[]" value="<?php echo esc($gimg); ?>" id="exGal<?php echo $gidx; ?>">
                                                        <button type="button" class="remove-img" onclick="removeExistingImg(this,'exGal<?php echo $gidx; ?>')">×</button>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="upload-zone">
                                            <input type="file" name="gallery_images[]" accept="image/*" multiple onchange="previewMultiple(this,'galleryPreview')">
                                            <div class="upload-zone-icon">🗂️</div>
                                            <p>Upload additional gallery images</p>
                                        </div>
                                        <div class="image-preview-grid" id="galleryPreview"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ TAB 5 – SEO ══ -->
                            <div class="tab-panel" id="tab-5">
                                <p class="section-heading">Tab 5 — SEO Content</p>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label">Meta Title</label>
                                            <span class="char-counter" id="titleCounter">0 / 60</span>
                                        </div>
                                        <input type="text" class="form-control" name="meta_title" id="meta_title" maxlength="70"
                                               value="<?php echo esc($p['meta_title']); ?>">
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
                                               value="<?php echo esc($p['meta_keywords']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label">Meta Description</label>
                                            <span class="char-counter" id="descCounter">0 / 160</span>
                                        </div>
                                        <textarea class="form-control" name="meta_description" id="meta_description" rows="3" maxlength="180"><?php echo esc($p['meta_description']); ?></textarea>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Live Google Preview</label>
                                    <div class="google-preview">
                                        <div class="gp-title" id="gpTitle"><?php echo esc($p['meta_title'] ?: $p['name']); ?></div>
                                        <div class="gp-url">yoursite.com › product › <span id="gpSlug"><?php echo esc($p['product_slug']); ?></span></div>
                                        <div class="gp-desc" id="gpDesc"><?php echo esc($p['meta_description'] ?: $p['description']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ TAB 6 – VISIBILITY ══ -->
                            <div class="tab-panel" id="tab-6">
                                <p class="section-heading">Tab 6 — Visibility & Status</p>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show Remaining Stock Count</strong>
                                        <small>Displays "Only 3 left!" urgency on product page</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_quantity" value="1" <?php echo chk($p['show_quantity'] ?? 1); ?>>
                                    </div>
                                </div>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show Help / Support Button</strong>
                                        <small>WhatsApp or call support on product page</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_help_button" value="1" <?php echo chk($p['show_help_button'] ?? 0); ?>>
                                    </div>
                                </div>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show Bulk Quantity Request Form</strong>
                                        <small>Bulk order inquiry form for B2B customers</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_bulk_form" value="1" <?php echo chk($p['show_bulk_form'] ?? 0); ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ TAB 7 – CUSTOMIZATION ══ -->
                            <div class="tab-panel" id="tab-7">
                                <p class="section-heading">Tab 7 — Customization Options</p>
                                <?php $showCustom = !empty($p['show_customization_label']); ?>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap"><strong>Show "Customizable" Badge</strong></div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_customization_label"
                                               value="1" <?php echo chk($showCustom); ?> onchange="toggleCustomFields(this)">
                                    </div>
                                </div>
                                <div id="customizationDetails" style="display:<?php echo $showCustom ? '' : 'none'; ?>">
                                    <div class="mb-3 mt-3">
                                        <label class="form-label">Customization Badge Label</label>
                                        <input type="text" class="form-control" name="customization_label" value="<?php echo esc($p['customization_label']); ?>">
                                    </div>
                                    <label class="form-label fw-semibold">Customization Variants</label>
                                    <table class="table table-bordered table-sm dynamic-table">
                                        <thead><tr><th>Label</th><th>Input Type</th><th style="width:100px">Required</th><th style="width:40px"></th></tr></thead>
                                        <tbody id="customBody">
                                            <?php if (!empty($customFields)): foreach ($customFields as $cf): ?>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="customization_labels[]" value="<?php echo esc($cf['label']); ?>"></td>
                                                <td><select class="form-select form-select-sm" name="customization_types[]">
                                                    <option value="text"  <?php echo sel($cf['type'], 'text');  ?>>Text Input</option>
                                                    <option value="image" <?php echo sel($cf['type'], 'image'); ?>>Image Upload</option>
                                                    <option value="both"  <?php echo sel($cf['type'], 'both');  ?>>Text + Image</option>
                                                </select></td>
                                                <td><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="customization_required[]" value="1" <?php echo chk($cf['required'] ?? 0); ?>></div></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                            <?php endforeach; else: ?>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="customization_labels[]" placeholder="e.g. Front Text"></td>
                                                <td><select class="form-select form-select-sm" name="customization_types[]">
                                                    <option value="text">Text Input</option>
                                                    <option value="image">Image Upload</option>
                                                    <option value="both">Text + Image</option>
                                                </select></td>
                                                <td><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="customization_required[]" value="1"></div></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="addCustomRow()">+ Add More</button>
                                </div>
                            </div>

                            <!-- ══ TAB 8 – ADDONS ══ -->
                            <div class="tab-panel" id="tab-8">
                                <p class="section-heading">Tab 8 — Addon / Related Products</p>
                                <input type="hidden" name="addon_product_ids" id="addonIdsInput"
                                       value="<?php echo esc($p['addon_product_ids'] ?? '[]'); ?>">
                                <div class="position-relative">
                                    <label class="form-label">Search Products</label>
                                    <input type="text" class="form-control" id="addonSearch" placeholder="Type product name or SKU..." autocomplete="off">
                                    <div class="addon-search-results d-none" id="addonResults"></div>
                                </div>
                                <div class="addon-cards mt-3" id="addonCards"></div>
                            </div>

                            <!-- ══ TAB 9 – ATTRIBUTES ══ -->
                            <div class="tab-panel" id="tab-9">
                                <p class="section-heading">Tab 9 — Product Attributes</p>

                                <?php $showSizes = !empty($p['show_sizes']); ?>
                                <div class="mb-4">
                                    <div class="toggle-row">
                                        <div class="toggle-label-wrap"><strong>Show Size Selector</strong></div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_sizes"
                                                   value="1" <?php echo chk($showSizes); ?> onchange="toggleSection('sizesSection',this)">
                                        </div>
                                    </div>
                                    <div id="sizesSection" style="display:<?php echo $showSizes ? '' : 'none'; ?>" class="mt-2">
                                        <table class="table table-bordered table-sm dynamic-table">
                                            <thead><tr><th>Size Name</th><th>Price Add-on (₹)</th><th style="width:40px"></th></tr></thead>
                                            <tbody id="sizesBody">
                                                <?php if (!empty($sizeAttrs)): foreach ($sizeAttrs as $sa): ?>
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm" name="size_names[]" value="<?php echo esc($sa['name']); ?>"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="size_prices[]" value="<?php echo (float)($sa['price'] ?? 0); ?>" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                                </tr>
                                                <?php endforeach; else: ?>
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm" name="size_names[]" placeholder="e.g. S, M, L"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="size_prices[]" placeholder="0.00" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow('sizesBody',sizeRowTemplate)">+ Add Size</button>
                                    </div>
                                </div>

                                <?php $showColors = !empty($p['show_colors']); ?>
                                <div class="mb-4">
                                    <div class="toggle-row">
                                        <div class="toggle-label-wrap"><strong>Show Color Selector</strong></div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_colors"
                                                   value="1" <?php echo chk($showColors); ?> onchange="toggleSection('colorsSection',this)">
                                        </div>
                                    </div>
                                    <div id="colorsSection" style="display:<?php echo $showColors ? '' : 'none'; ?>" class="mt-2">
                                        <table class="table table-bordered table-sm dynamic-table">
                                            <thead><tr><th>Color Name</th><th>Hex</th><th>Price Add-on (₹)</th><th style="width:40px"></th></tr></thead>
                                            <tbody id="colorsBody">
                                                <?php if (!empty($colorAttrs)): foreach ($colorAttrs as $ca): ?>
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm" name="color_names[]" value="<?php echo esc($ca['name']); ?>"></td>
                                                    <td><input type="color" class="form-control form-control-sm form-control-color" name="color_hexes[]" value="<?php echo esc($ca['hex'] ?? '#000000'); ?>"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="color_prices[]" value="<?php echo (float)($ca['price'] ?? 0); ?>" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                                </tr>
                                                <?php endforeach; else: ?>
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm" name="color_names[]" placeholder="e.g. Red"></td>
                                                    <td><input type="color" class="form-control form-control-sm form-control-color" name="color_hexes[]" value="#000000"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="color_prices[]" placeholder="0.00" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow('colorsBody',colorRowTemplate)">+ Add Color</button>
                                    </div>
                                </div>

                                <p class="section-heading">Printing & Material</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="attr_material" value="<?php echo esc($matAttr[0] ?? $p['material'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Lamination</label>
                                        <input type="text" class="form-control" name="attr_lamination" value="<?php echo esc($lamAttr[0] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Orientation</label>
                                        <select class="form-select" name="attr_orientation">
                                            <option value="">— Select —</option>
                                            <?php $ori = $oriAttr[0] ?? ''; ?>
                                            <option value="portrait"  <?php echo sel($ori, 'portrait');  ?>>Portrait</option>
                                            <option value="landscape" <?php echo sel($ori, 'landscape'); ?>>Landscape</option>
                                            <option value="square"    <?php echo sel($ori, 'square');    ?>>Square</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Quantity-Based Pricing Tiers</label>
                                    <table class="table table-bordered table-sm dynamic-table">
                                        <thead><tr><th>Quantity</th><th>Price per unit (₹)</th><th style="width:40px"></th></tr></thead>
                                        <tbody id="qtyBody">
                                            <?php if (!empty($qtyPriceBreaks)): foreach ($qtyPriceBreaks as $qb): ?>
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" value="<?php echo (int)$qb['qty']; ?>" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" value="<?php echo (float)$qb['price']; ?>" min="0"></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                            <?php endforeach; else: ?>
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" placeholder="50" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" placeholder="0.00" min="0"></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow('qtyBody',qtyRowTemplate)">+ Add Tier</button>
                                </div>
                            </div>

                            <!-- ══ TAB 10 – SHIPPING ══ -->
                            <div class="tab-panel" id="tab-10">
                                <p class="section-heading">Tab 10 — Shipping Charges</p>
                                <?php $shipOn = !empty($p['shipping_method_status']); ?>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Enable Custom Shipping</strong>
                                        <small>If OFF, site-wide default shipping rates apply</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="shipping_method_status"
                                               value="1" <?php echo chk($shipOn); ?> onchange="toggleSection('shippingFields',this)">
                                    </div>
                                </div>
                                <div id="shippingFields" style="display:<?php echo $shipOn ? '' : 'none'; ?>" class="mt-3">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label">🏙️ Local Shipping (₹)</label>
                                            <div class="input-group"><span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="local_shipping_charge" min="0" value="<?php echo (float)($p['local_shipping_charge'] ?? 0); ?>"></div></div>
                                        <div class="col-md-8"><label class="form-label">Local Message</label>
                                            <input type="text" class="form-control" name="local_shipping_message" value="<?php echo esc($p['local_shipping_message']); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">🗺️ Regional Shipping (₹)</label>
                                            <div class="input-group"><span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="regional_shipping_charge" min="0" value="<?php echo (float)($p['regional_shipping_charge'] ?? 0); ?>"></div></div>
                                        <div class="col-md-8"><label class="form-label">Regional Message</label>
                                            <input type="text" class="form-control" name="regional_shipping_message" value="<?php echo esc($p['regional_shipping_message']); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">🇮🇳 National Shipping (₹)</label>
                                            <div class="input-group"><span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" name="national_shipping_charge" min="0" value="<?php echo (float)($p['national_shipping_charge'] ?? 0); ?>"></div></div>
                                        <div class="col-md-8"><label class="form-label">National Message</label>
                                            <input type="text" class="form-control" name="national_shipping_message" value="<?php echo esc($p['national_shipping_message']); ?>"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ TAB 11 – CANCEL ══ -->
                            <div class="tab-panel" id="tab-11">
                                <p class="section-heading">Tab 11 — Cancellation Policy</p>
                                <?php $cancelOn = !empty($p['cancel_available']); ?>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Allow Order Cancellation</strong>
                                        <small>Customer can cancel within the defined window</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="cancel_available"
                                               id="cancel_available" value="1" <?php echo chk($cancelOn); ?> onchange="toggleSection('cancelFields',this)">
                                    </div>
                                </div>
                                <div id="cancelFields" style="display:<?php echo $cancelOn ? '' : 'none'; ?>" class="mt-3">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Window Value</label>
                                            <input type="number" class="form-control" name="cancel_time" id="cancel_time"
                                                   min="1" value="<?php echo (int)($p['cancel_time'] ?? 24); ?>" onchange="updateCancelPreview()">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Window Unit</label>
                                            <select class="form-select" name="cancel_type" id="cancel_type" onchange="updateCancelPreview()">
                                                <option value="hours" <?php echo sel($p['cancel_type'] ?? 'hours', 'hours'); ?>>Hours</option>
                                                <option value="days"  <?php echo sel($p['cancel_type'] ?? 'hours', 'days');  ?>>Days</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="alert alert-info mb-0 py-2" id="cancelPreview">
                                                Can cancel within <strong><?php echo (int)($p['cancel_time'] ?? 24); ?> <?php echo esc($p['cancel_type'] ?? 'hours'); ?></strong> of ordering.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ TAB 12 – COD ══ -->
                            <div class="tab-panel" id="tab-12">
                                <p class="section-heading">Tab 12 — Cash on Delivery</p>
                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Enable Cash on Delivery</strong>
                                        <small>Customer pays cash at delivery</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="cod_available"
                                               value="1" <?php echo chk($p['cod_available'] ?? 1); ?>>
                                    </div>
                                </div>
                                <div class="mt-4 p-3 bg-light rounded border">
                                    <h6 class="fw-bold mb-3">Product Summary</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Name:</strong> <?php echo esc($p['name']); ?></p>
                                            <p class="mb-1"><strong>SKU:</strong> <?php echo esc($p['sku']); ?></p>
                                            <p class="mb-1"><strong>MRP:</strong> ₹<?php echo number_format((float)($p['regular_price'] ?? $p['price']), 2); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Stock:</strong> <?php echo (int)($p['stock_quantity'] ?? 0); ?></p>
                                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst(esc($p['status'] ?? 'active')); ?></p>
                                            <p class="mb-1"><strong>Updated:</strong> <?php echo esc($p['updated_at'] ?? '—'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /card-body -->

                        <div class="card-footer">
                            <div class="form-nav">
                                <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="changeTab(-1)" style="display:none">
                                    ← Previous
                                </button>
                                <div class="d-flex gap-2 ms-auto">
                                    <button type="submit" class="btn btn-success">✓ Update Product</button>
                                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeTab(1)">Next →</button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div><!-- /card -->
            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script>
const EXISTING_ADDON_IDS  = <?php echo json_encode($addonIds); ?>;
const CATEGORY_ID_INIT    = <?php echo json_encode($p['category_id'] ?? null); ?>;
const SUB_CAT_ID_INIT     = <?php echo json_encode($p['sub_category_id'] ?? null); ?>;
const SUB_SUB_CAT_ID_INIT = <?php echo json_encode($p['sub_sub_category_id'] ?? null); ?>;
</script>
<script src="js/app.js"></script>
<script>
// ─── Tab state ────────────────────────────────────────────────────────────────
const TOTAL_TABS    = 12;
let   currentTab    = 1;
const addonProducts = {};

function changeTab(dir) {
    const target = currentTab + dir;
    if (target < 1 || target > TOTAL_TABS) return;
    goToTab(target);
}

function goToTab(n) {
    document.getElementById('tab-' + currentTab)?.classList.remove('active');
    document.querySelector(`[data-tab="${currentTab}"]`)?.classList.remove('active');
    currentTab = n;
    document.getElementById('tab-' + n)?.classList.add('active');
    document.querySelector(`[data-tab="${n}"]`)?.classList.add('active');
    document.getElementById('prevBtn').style.display = n > 1 ? '' : 'none';
    document.getElementById('nextBtn').style.display = n < TOTAL_TABS ? '' : 'none';
    document.getElementById('progressBar').style.width = ((n / TOTAL_TABS) * 100) + '%';
    document.querySelector('.product-tabs-wrap').scrollLeft =
        (document.querySelector(`[data-tab="${n}"]`)?.offsetLeft || 0) - 20;
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => goToTab(parseInt(btn.dataset.tab)));
});

// ─── Slug preview ─────────────────────────────────────────────────────────────
document.getElementById('product_slug').addEventListener('input', function() {
    document.getElementById('gpSlug').textContent = this.value || 'product-slug';
});

// ─── Discount % ───────────────────────────────────────────────────────────────
function updateDiscount() {
    const mrp   = parseFloat(document.getElementById('regular_price').value) || 0;
    const offer = parseFloat(document.getElementById('offer_price').value)   || 0;
    const el    = document.getElementById('discount_pct');
    if (mrp > 0 && offer > 0 && offer < mrp) {
        el.textContent = Math.round(((mrp - offer) / mrp) * 100) + '% OFF';
    } else { el.textContent = ''; }
}
document.getElementById('regular_price').addEventListener('input', updateDiscount);
document.getElementById('offer_price').addEventListener('input', updateDiscount);
updateDiscount();

// ─── Bulk pricing ─────────────────────────────────────────────────────────────
const bulkRowTemplate = `<tr>
    <td><input type="number" class="form-control form-control-sm" name="bulk_min_qty[]" placeholder="10" min="1"></td>
    <td><input type="number" class="form-control form-control-sm" name="bulk_max_qty[]" placeholder="50" min="1"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm bulk-price" name="bulk_price[]" placeholder="0.00" min="0"></td>
    <td><span class="bulk-disc text-success fw-bold" style="font-size:12px"></span></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
</tr>`;
document.getElementById('addBulkRow').addEventListener('click', () => addRow('bulkPricingBody', bulkRowTemplate));
document.getElementById('bulkPricingBody').addEventListener('input', function(e) {
    if (!e.target.classList.contains('bulk-price')) return;
    const row   = e.target.closest('tr');
    const mrp   = parseFloat(document.getElementById('regular_price').value) || 0;
    const price = parseFloat(e.target.value) || 0;
    const discEl = row.querySelector('.bulk-disc');
    if (mrp > 0 && price > 0 && price < mrp) discEl.textContent = Math.round(((mrp - price) / mrp) * 100) + '% OFF';
    else discEl.textContent = '';
});

// ─── Generic table row helpers ────────────────────────────────────────────────
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
    <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" placeholder="50" min="1"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" placeholder="0.00" min="0"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
</tr>`;

// ─── Toggle helpers ───────────────────────────────────────────────────────────
function toggleSection(sectionId, checkbox) {
    document.getElementById(sectionId).style.display = checkbox.checked ? '' : 'none';
}
function toggleCustomFields(checkbox) {
    document.getElementById('customizationDetails').style.display = checkbox.checked ? '' : 'none';
}
function addCustomRow() {
    addRow('customBody', `<tr>
        <td><input type="text" class="form-control form-control-sm" name="customization_labels[]" placeholder="Label"></td>
        <td><select class="form-select form-select-sm" name="customization_types[]">
            <option value="text">Text Input</option>
            <option value="image">Image Upload</option>
            <option value="both">Text + Image</option>
        </select></td>
        <td><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="customization_required[]" value="1"></div></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
    </tr>`);
}

// ─── Remove existing images ───────────────────────────────────────────────────
function removeExistingImg(btn, inputId) {
    const input = document.getElementById(inputId);
    if (input) input.remove();
    btn.closest('.image-thumb').remove();
}

// ─── New image preview ────────────────────────────────────────────────────────
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
document.querySelectorAll('.upload-zone').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', () => zone.classList.remove('dragover'));
});

// ─── SEO ─────────────────────────────────────────────────────────────────────
function updateSeo() {
    const title = document.getElementById('meta_title').value;
    const desc  = document.getElementById('meta_description').value;
    const kw    = document.getElementById('meta_keywords').value;

    document.getElementById('gpTitle').textContent = title || document.getElementById('product_name').value || 'Product title';
    document.getElementById('gpDesc').textContent  = desc  || '...';

    const tc = document.getElementById('titleCounter');
    tc.textContent = title.length + ' / 60';
    tc.className   = 'char-counter ' + (title.length >= 50 && title.length <= 60 ? 'ok' : title.length > 60 ? 'over' : 'warn');

    const dc = document.getElementById('descCounter');
    dc.textContent = desc.length + ' / 160';
    dc.className   = 'char-counter ' + (desc.length >= 140 && desc.length <= 160 ? 'ok' : desc.length > 160 ? 'over' : 'warn');

    let score = 0;
    if (title.length >= 50 && title.length <= 60) score += 30; else if (title.length > 0) score += 15;
    if (desc.length >= 140 && desc.length <= 160) score += 30; else if (desc.length > 0) score += 15;
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
    const val  = document.getElementById('cancel_time')?.value || '24';
    const unit = document.getElementById('cancel_type')?.value || 'hours';
    const prev = document.getElementById('cancelPreview');
    if (prev) prev.innerHTML = `Can cancel within <strong>${val} ${unit}</strong> of ordering.`;
}

// ─── Addon search ─────────────────────────────────────────────────────────────
let addonSearchTimer;
document.getElementById('addonSearch').addEventListener('input', function() {
    clearTimeout(addonSearchTimer);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('addonResults').classList.add('d-none'); return; }
    addonSearchTimer = setTimeout(() => {
        fetch(`ajax/search-products.php?q=${encodeURIComponent(q)}&exclude=<?php echo $productId; ?>`)
            .then(r => r.json())
            .then(data => {
                const res = document.getElementById('addonResults');
                if (!data.length) { res.classList.add('d-none'); return; }
                res.innerHTML = data.map(pd => `<div class="addon-result-item" onclick="addAddon(${pd.id},'${escHtml(pd.name)}','${escHtml(pd.sku)}',${pd.price},'${escHtml(pd.thumb || '')}')">
                    ${pd.thumb ? `<img src="${escHtml(pd.thumb)}" alt="">` : '<div style="width:40px;height:40px;background:#eee;border-radius:4px"></div>'}
                    <div><div style="font-size:13px;font-weight:600">${escHtml(pd.name)}</div><div style="font-size:11px;color:#6c757d">SKU: ${escHtml(pd.sku)} · ₹${pd.price}</div></div>
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
        const pd = addonProducts[id];
        return `<div class="addon-card">
            ${pd.thumb ? `<img src="${escHtml(pd.thumb)}" alt="">` : '<div style="width:44px;height:44px;background:#eee;border-radius:4px"></div>'}
            <div><div style="font-size:13px;font-weight:600">${escHtml(pd.name)}</div><div style="font-size:11px;color:#6c757d">₹${pd.price}</div></div>
            <button type="button" class="remove-addon" onclick="removeAddon(${id})">✕</button>
        </div>`;
    }).join('');
}

function escHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// ─── Category cascades ────────────────────────────────────────────────────────
function loadSubCategories(parentId, preSelectSub, preSelectSubSub) {
    const sub    = document.getElementById('sub_category_id');
    const subsub = document.getElementById('sub_sub_category_id');
    sub.innerHTML    = '<option value="">Loading...</option>';
    subsub.innerHTML = '<option value="">— Select Sub Category First —</option>';
    if (!parentId) { sub.innerHTML = '<option value="">— Select Main Category First —</option>'; return; }
    fetch(`ajax/get-sub-categories.php?parent_id=${parentId}`)
        .then(r => r.json())
        .then(data => {
            sub.innerHTML = '<option value="">— Select Sub Category —</option>';
            data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id; opt.textContent = c.name;
                if (preSelectSub && c.id == preSelectSub) opt.selected = true;
                sub.appendChild(opt);
            });
            if (preSelectSub && preSelectSubSub) loadSubSubCategories(preSelectSub, preSelectSubSub);
        });
}

function loadSubSubCategories(parentId, preSelectVal) {
    const subsub = document.getElementById('sub_sub_category_id');
    subsub.innerHTML = '<option value="">Loading...</option>';
    if (!parentId) { subsub.innerHTML = '<option value="">— Select Sub Category First —</option>'; return; }
    fetch(`ajax/get-sub-sub-categories.php?parent_id=${parentId}`)
        .then(r => r.json())
        .then(data => {
            subsub.innerHTML = '<option value="">— Select Sub Sub Category —</option>';
            data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id; opt.textContent = c.name;
                if (preSelectVal && c.id == preSelectVal) opt.selected = true;
                subsub.appendChild(opt);
            });
        });
}

document.getElementById('category_id').addEventListener('change', function() {
    loadSubCategories(this.value, null, null);
});
document.getElementById('sub_category_id').addEventListener('change', function() {
    loadSubSubCategories(this.value, null);
});

// ─── CKEditor init ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    ['description','long_description','instructions','delivery_info'].forEach(id => {
        if (document.getElementById(id)) CKEDITOR.replace(id);
    });

    goToTab(1);
    updateSeo();

    // Restore cascading categories
    if (CATEGORY_ID_INIT) {
        loadSubCategories(CATEGORY_ID_INIT, SUB_CAT_ID_INIT, SUB_SUB_CAT_ID_INIT);
    }

    // Restore existing addon cards with stub data
    if (EXISTING_ADDON_IDS && EXISTING_ADDON_IDS.length > 0) {
        EXISTING_ADDON_IDS.forEach(id => {
            addonProducts[id] = { id, name: `Product #${id}`, sku: '', price: '—', thumb: '' };
        });
        renderAddonCards();
    }
});
</script>
</body>
</html>
