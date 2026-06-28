<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');
require_once(__DIR__ . '/controllers/ProductController.php');

$database = new Database();
$db       = $database->getConnection();

$productController = new ProductController($db);
$categoryCtrl      = new CategoryController($db);
$mainCategories    = $categoryCtrl->getMainCategories();

$error_message   = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $productController->addProduct($_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = "Product added successfully!";
        header('Location: view-products.php');
        exit;
    } else {
        $error_message = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add Product | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }

        /* ── Tab navigation ── */
        .product-tabs-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .product-tabs { display: flex; flex-wrap: nowrap; gap: 4px; padding: 0; margin: 0 0 0 0; list-style: none; border-bottom: 2px solid #dee2e6; min-width: max-content; }
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
        .product-tabs .tab-btn .tab-status { font-size: 12px; }
        .product-tabs .tab-btn.done { background: #198754; color: #fff; border-color: #198754; }
        .product-tabs .tab-btn.error { background: #dc3545; color: #fff; border-color: #dc3545; }

        /* ── Tab content ── */
        .tab-panel { display: none; padding: 28px 0 0; }
        .tab-panel.active { display: block; }

        /* ── Progress bar ── */
        .form-progress { height: 6px; border-radius: 3px; background: #e9ecef; margin-bottom: 20px; }
        .form-progress-bar { height: 100%; border-radius: 3px; background: #0d6efd; transition: width .3s; }

        /* ── Utility ── */
        .required-star { color: #dc3545; }
        .section-heading { font-size: 15px; font-weight: 600; color: #343a40; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; margin-bottom: 20px; }

        /* ── Dynamic rows ── */
        .dynamic-table thead th { font-size: 12px; font-weight: 600; background: #f8f9fa; }
        .dynamic-table .btn-remove-row { padding: 2px 8px; }
        .add-row-btn { font-size: 13px; }

        /* ── Toggle switch ── */
        .toggle-row { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f0f0f0; }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-label-wrap { flex: 1; }
        .toggle-label-wrap strong { display: block; font-size: 14px; margin-bottom: 2px; }
        .toggle-label-wrap small { color: #6c757d; }
        .form-switch .form-check-input { width: 3em; height: 1.5em; }

        /* ── Image upload zones ── */
        .upload-zone {
            border: 2px dashed #ced4da; border-radius: 8px; padding: 30px;
            text-align: center; cursor: pointer; transition: border-color .2s;
            position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: #0d6efd; background: #f0f4ff; }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-zone-icon { font-size: 32px; color: #adb5bd; margin-bottom: 8px; }
        .upload-zone p { margin: 0; color: #6c757d; font-size: 13px; }
        .image-preview-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .image-thumb { position: relative; width: 90px; height: 90px; }
        .image-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6; }
        .image-thumb .remove-img { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%; background: #dc3545; color: #fff; border: none; font-size: 11px; display: flex; align-items: center; justify-content: center; cursor: pointer; }

        /* ── SEO preview ── */
        .google-preview { border: 1px solid #dee2e6; border-radius: 8px; padding: 18px 20px; background: #fff; margin-top: 16px; }
        .gp-title { font-size: 18px; color: #1a0dab; font-family: Arial, sans-serif; cursor: pointer; }
        .gp-title:hover { text-decoration: underline; }
        .gp-url { color: #006621; font-size: 13px; margin: 2px 0; }
        .gp-desc { color: #545454; font-size: 14px; font-family: Arial, sans-serif; }
        .char-counter { font-size: 12px; }
        .char-counter.ok { color: #198754; }
        .char-counter.warn { color: #ffc107; }
        .char-counter.over { color: #dc3545; }
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

        /* ── Navigation ── */
        .form-nav { display: flex; justify-content: space-between; align-items: center; padding-top: 24px; margin-top: 24px; border-top: 1px solid #dee2e6; }
        .sticky-save { position: sticky; top: 10px; z-index: 50; }
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
                        <h3><strong>Add</strong> New Product</h3>
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

                <div class="card">
                    <div class="card-body pb-0">

                        <!-- Progress -->
                        <div class="form-progress mb-1">
                            <div class="form-progress-bar" id="progressBar" style="width:8%"></div>
                        </div>

                        <!-- Tab navigation -->
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
                                    echo "<li><button type='button' class='tab-btn $active' data-tab='$num'><span class='tab-num'>$num</span> $name <span class='tab-status'></span></button></li>";
                                }
                                ?>
                            </ul>
                        </div>

                    </div><!-- /card-body -->

                    <form method="POST" enctype="multipart/form-data" id="productForm" novalidate>

                        <div class="card-body">

                            <!-- ══════════════════════════════════════════════
                                 TAB 1 – GENERAL INFO
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel active" id="tab-1">
                                <p class="section-heading">Tab 1 — General Information</p>

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

                                <!-- Bulk Pricing Table -->
                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Bulk Order Pricing Tiers</label>
                                    <table class="table table-bordered table-sm dynamic-table" id="bulkPricingTable">
                                        <thead>
                                            <tr>
                                                <th>Min Qty</th>
                                                <th>Max Qty</th>
                                                <th>Price per unit (₹)</th>
                                                <th style="width:60px">% Off</th>
                                                <th style="width:40px"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="bulkPricingBody">
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="bulk_min_qty[]" placeholder="e.g. 10" min="1"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="bulk_max_qty[]" placeholder="e.g. 50" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm bulk-price" name="bulk_price[]" placeholder="0.00" min="0"></td>
                                                <td><span class="bulk-disc text-success fw-bold" style="font-size:12px"></span></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-outline-success add-row-btn" id="addBulkRow">+ Add Tier</button>
                                </div>

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
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 2 – FILTERS & TAGS
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-2">
                                <p class="section-heading">Tab 2 — Filters & Tags</p>
                                <p class="text-muted small mb-3">These values appear as filterable attributes on the product listing page.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Color</label>
                                        <input type="text" class="form-control" name="color" placeholder="e.g. Red, Blue, Multi">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" name="type" placeholder="e.g. T-shirt, Mug, Notebook">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="material" placeholder="e.g. Cotton, Ceramic">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Occasion</label>
                                        <input type="text" class="form-control" name="occasion" placeholder="e.g. Birthday, Wedding">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Brand</label>
                                        <input type="text" class="form-control" name="brand" placeholder="Brand name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Discount Range</label>
                                        <select class="form-select" name="discount_type">
                                            <option value="">— Select —</option>
                                            <option value="upto_10">Up to 10%</option>
                                            <option value="10_25">10% – 25%</option>
                                            <option value="25_50">25% – 50%</option>
                                            <option value="above_50">Above 50%</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Shape</label>
                                        <input type="text" class="form-control" name="shape" placeholder="e.g. Round, Square">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender">
                                            <option value="">— Select —</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="unisex">Unisex</option>
                                            <option value="kids">Kids</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gift Type</label>
                                        <input type="text" class="form-control" name="gift_type" placeholder="e.g. Personalized, Packaged">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ideal For</label>
                                        <input type="text" class="form-control" name="ideal_for" placeholder="e.g. Men, Women, Students">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Customization Technology</label>
                                        <input type="text" class="form-control" name="customization_tech" placeholder="e.g. Screen Print, Embroidery">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Customization Location</label>
                                        <input type="text" class="form-control" name="customization_location" placeholder="e.g. Front, Back, Sleeve">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Capacity</label>
                                        <input type="text" class="form-control" name="capacity" placeholder="e.g. 500ml, A4, 100 pages">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ink Color</label>
                                        <input type="text" class="form-control" name="ink_color" placeholder="e.g. Black, CMYK, Gold">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Features</label>
                                        <textarea class="form-control" name="features" rows="2" placeholder="e.g. Waterproof, Eco-friendly, Washable"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 3 – CATEGORY & CAROUSEL LINKS
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-3">
                                <p class="section-heading">Tab 3 — Category & Carousel Links</p>
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
                                            <input class="form-check-input" type="checkbox" name="<?php echo $fname; ?>" value="1" id="<?php echo $fname; ?>">
                                            <label class="form-check-label" for="<?php echo $fname; ?>"><?php echo $flabel; ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 4 – IMAGES & MEDIA
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-4">
                                <p class="section-heading">Tab 4 — Images & Media</p>

                                <div class="mb-3">
                                    <label class="form-label">Image ALT Tag <span class="required-star">*</span></label>
                                    <input type="text" class="form-control" name="alt_tag" required placeholder="Descriptive alt text for all images (SEO)">
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Thumbnail Image <span class="required-star">*</span></label>
                                        <p class="text-muted small mt-0">Shown in listings. Recommended: 400×400 px, max 500 KB.</p>
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
                                        <div class="upload-zone" id="galleryZone">
                                            <input type="file" name="gallery_images[]" id="gallery_images" accept="image/*" multiple onchange="previewMultiple(this,'galleryPreview')">
                                            <div class="upload-zone-icon">🗂️</div>
                                            <p>Drag &amp; drop or click to select gallery images</p>
                                        </div>
                                        <div class="image-preview-grid" id="galleryPreview"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 5 – SEO CONTENT
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-5">
                                <p class="section-heading">Tab 5 — SEO Content</p>

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

                            <!-- ══════════════════════════════════════════════
                                 TAB 6 – VISIBILITY & STATUS
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-6">
                                <p class="section-heading">Tab 6 — Visibility & Status Controls</p>

                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show Remaining Stock Count</strong>
                                        <small>Displays "Only 3 left!" urgency message on the product page</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_quantity" id="show_quantity" value="1" checked>
                                    </div>
                                </div>

                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show Help / Support Button</strong>
                                        <small>Displays a WhatsApp or call support button on the product page</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_help_button" id="show_help_button" value="1">
                                    </div>
                                </div>

                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show Bulk Quantity Request Form</strong>
                                        <small>Displays a bulk order inquiry form on the product page for B2B customers</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_bulk_form" id="show_bulk_form" value="1">
                                    </div>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 7 – CUSTOMIZATION OPTIONS
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-7">
                                <p class="section-heading">Tab 7 — Customization Options</p>

                                <div class="toggle-row">
                                    <div class="toggle-label-wrap">
                                        <strong>Show "Customizable" Badge</strong>
                                        <small>Displays a customizable badge on the product listing and detail page</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_customization_label" id="show_customization_label" value="1" onchange="toggleCustomFields(this)">
                                    </div>
                                </div>

                                <div id="customizationDetails" style="display:none">
                                    <div class="mb-3 mt-3">
                                        <label class="form-label">Customization Badge Label</label>
                                        <input type="text" class="form-control" name="customization_label" placeholder="e.g. Fully Customizable, Add Your Name">
                                    </div>

                                    <label class="form-label fw-semibold">Customization Variants</label>
                                    <table class="table table-bordered table-sm dynamic-table" id="customTable">
                                        <thead>
                                            <tr>
                                                <th>Label / Name</th>
                                                <th>Input Type</th>
                                                <th style="width:100px">Required</th>
                                                <th style="width:40px"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="customBody">
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="customization_labels[]" placeholder="e.g. Front Side Text"></td>
                                                <td>
                                                    <select class="form-select form-select-sm" name="customization_types[]">
                                                        <option value="text">Text Input</option>
                                                        <option value="image">Image Upload</option>
                                                        <option value="both">Text + Image</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="customization_required[]" value="1">
                                                    </div>
                                                </td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-outline-success add-row-btn" onclick="addCustomRow()">+ Add More</button>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 8 – ADDON PRODUCTS
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-8">
                                <p class="section-heading">Tab 8 — Addon / Related Products</p>
                                <p class="text-muted small">Search and add products to show as "Frequently Bought Together".</p>

                                <input type="hidden" name="addon_product_ids" id="addonIdsInput" value="[]">

                                <div class="position-relative">
                                    <label class="form-label">Search Products</label>
                                    <input type="text" class="form-control" id="addonSearch" placeholder="Type product name or SKU..." autocomplete="off">
                                    <div class="addon-search-results d-none" id="addonResults"></div>
                                </div>

                                <div class="addon-cards mt-3" id="addonCards"></div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 9 – PRODUCT ATTRIBUTES
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-9">
                                <p class="section-heading">Tab 9 — Product Attributes</p>

                                <!-- Sizes -->
                                <div class="mb-4">
                                    <div class="toggle-row">
                                        <div class="toggle-label-wrap">
                                            <strong>Show Size Selector on Product Page</strong>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_sizes" id="show_sizes" value="1" onchange="toggleSection('sizesSection',this)">
                                        </div>
                                    </div>
                                    <div id="sizesSection" style="display:none" class="mt-2">
                                        <table class="table table-bordered table-sm dynamic-table" id="sizesTable">
                                            <thead><tr><th>Size Name</th><th>Price Add-on (₹)</th><th style="width:40px"></th></tr></thead>
                                            <tbody id="sizesBody">
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm" name="size_names[]" placeholder="e.g. S, M, L, XL, A4"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="size_prices[]" placeholder="0.00" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-sm btn-outline-success add-row-btn" onclick="addRow('sizesBody', sizeRowTemplate)">+ Add Size</button>
                                    </div>
                                </div>

                                <!-- Colors -->
                                <div class="mb-4">
                                    <div class="toggle-row">
                                        <div class="toggle-label-wrap">
                                            <strong>Show Color Selector on Product Page</strong>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_colors" id="show_colors" value="1" onchange="toggleSection('colorsSection',this)">
                                        </div>
                                    </div>
                                    <div id="colorsSection" style="display:none" class="mt-2">
                                        <table class="table table-bordered table-sm dynamic-table" id="colorsTable">
                                            <thead><tr><th>Color Name</th><th>Hex Code</th><th>Price Add-on (₹)</th><th style="width:40px"></th></tr></thead>
                                            <tbody id="colorsBody">
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm" name="color_names[]" placeholder="e.g. Red"></td>
                                                    <td><input type="color" class="form-control form-control-sm form-control-color" name="color_hexes[]" value="#000000"></td>
                                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="color_prices[]" placeholder="0.00" min="0"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <button type="button" class="btn btn-sm btn-outline-success add-row-btn" onclick="addRow('colorsBody', colorRowTemplate)">+ Add Color</button>
                                    </div>
                                </div>

                                <!-- Other attributes -->
                                <p class="section-heading">Printing & Material</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="attr_material" placeholder="e.g. Cotton, Plastic, Paper">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Lamination</label>
                                        <input type="text" class="form-control" name="attr_lamination" placeholder="e.g. Matte, Gloss, None">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Orientation</label>
                                        <select class="form-select" name="attr_orientation">
                                            <option value="">— Select —</option>
                                            <option value="portrait">Portrait</option>
                                            <option value="landscape">Landscape</option>
                                            <option value="square">Square</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Printing Location</label>
                                        <input type="text" class="form-control" name="attr_printing_location" placeholder="e.g. Front, Back, Left Sleeve, Both Sides">
                                    </div>
                                </div>

                                <!-- Quantity pricing tiers -->
                                <div class="mt-4">
                                    <label class="form-label fw-semibold">Quantity-Based Pricing Tiers</label>
                                    <table class="table table-bordered table-sm dynamic-table" id="qtyTable">
                                        <thead><tr><th>Quantity</th><th>Price per unit (₹)</th><th style="width:40px"></th></tr></thead>
                                        <tbody id="qtyBody">
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm" name="qty_tier_qty[]" placeholder="e.g. 50" min="1"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="qty_tier_price[]" placeholder="0.00" min="0"></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-outline-success add-row-btn" onclick="addRow('qtyBody', qtyRowTemplate)">+ Add Tier</button>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════════════════
                                 TAB 10 – SHIPPING CHARGES
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-10">
                                <p class="section-heading">Tab 10 — Shipping Charges</p>

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

                            <!-- ══════════════════════════════════════════════
                                 TAB 11 – CANCEL CONDITIONS
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-11">
                                <p class="section-heading">Tab 11 — Cancellation Policy</p>

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

                            <!-- ══════════════════════════════════════════════
                                 TAB 12 – CASH ON DELIVERY
                            ══════════════════════════════════════════════ -->
                            <div class="tab-panel" id="tab-12">
                                <p class="section-heading">Tab 12 — Cash on Delivery</p>

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
                                    <h6 class="fw-bold mb-3">Quick Review</h6>
                                    <div class="row" id="quickSummary">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Product Name:</strong> <span id="sumName">—</span></p>
                                            <p class="mb-1"><strong>SKU:</strong> <span id="sumSku">—</span></p>
                                            <p class="mb-1"><strong>MRP:</strong> ₹<span id="sumMrp">—</span></p>
                                            <p class="mb-1"><strong>Offer Price:</strong> ₹<span id="sumOffer">—</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Quantity:</strong> <span id="sumQty">—</span></p>
                                            <p class="mb-1"><strong>Status:</strong> <span id="sumStatus">—</span></p>
                                            <p class="mb-1"><strong>Category:</strong> <span id="sumCat">—</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /card-body -->

                        <!-- ── Navigation ── -->
                        <div class="card-footer">
                            <div class="form-nav">
                                <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="changeTab(-1)" style="display:none">
                                    ← Previous
                                </button>
                                <div class="d-flex gap-2 ms-auto">
                                    <button type="submit" name="status" value="draft" class="btn btn-outline-secondary" id="draftBtn" style="display:none">
                                        Save Draft
                                    </button>
                                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeTab(1)">
                                        Next →
                                    </button>
                                    <button type="submit" class="btn btn-success" id="publishBtn" style="display:none">
                                        ✓ Publish Product
                                    </button>
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

<script src="js/app.js"></script>
<script>
// ─── State ────────────────────────────────────────────────────────────────────
const TOTAL_TABS   = 12;
let   currentTab   = 1;
const addonProducts = {};   // id → {id,name,sku,price,thumb}

// ─── Tab switching ────────────────────────────────────────────────────────────
function changeTab(dir) {
    const target = currentTab + dir;
    if (target < 1 || target > TOTAL_TABS) return;
    if (dir > 0 && !validateTab(currentTab)) return;
    goToTab(target);
}

function goToTab(n) {
    document.getElementById('tab-' + currentTab)?.classList.remove('active');
    document.querySelector(`[data-tab="${currentTab}"]`)?.classList.remove('active');
    currentTab = n;
    document.getElementById('tab-' + n)?.classList.add('active');
    document.querySelector(`[data-tab="${n}"]`)?.classList.add('active');

    document.getElementById('prevBtn').style.display  = n > 1 ? '' : 'none';
    document.getElementById('nextBtn').style.display  = n < TOTAL_TABS ? '' : 'none';
    document.getElementById('draftBtn').style.display = n > 1 ? '' : 'none';
    document.getElementById('publishBtn').style.display = n === TOTAL_TABS ? '' : 'none';

    document.getElementById('progressBar').style.width = ((n / TOTAL_TABS) * 100) + '%';

    if (n === TOTAL_TABS) populateSummary();
    document.querySelector('.product-tabs-wrap').scrollLeft =
        (document.querySelector(`[data-tab="${n}"]`)?.offsetLeft || 0) - 20;
}

// Allow clicking tab buttons directly
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => goToTab(parseInt(btn.dataset.tab)));
});

// ─── Validation ───────────────────────────────────────────────────────────────
function validateTab(n) {
    const panel    = document.getElementById('tab-' + n);
    const required = panel.querySelectorAll('[required]');
    let ok = true;
    required.forEach(el => {
        el.classList.remove('is-invalid');
        if (!el.value.trim()) { el.classList.add('is-invalid'); ok = false; }
    });
    if (!ok) { alert('Please fill in all required fields before continuing.'); }
    return ok;
}

// ─── Slug auto-generation ─────────────────────────────────────────────────────
document.getElementById('product_name').addEventListener('input', function() {
    const slugEl = document.getElementById('product_slug');
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

// ─── Bulk pricing ── dynamic rows ────────────────────────────────────────────
const bulkRowTemplate = `<tr>
    <td><input type="number" class="form-control form-control-sm" name="bulk_min_qty[]" placeholder="e.g. 10" min="1"></td>
    <td><input type="number" class="form-control form-control-sm" name="bulk_max_qty[]" placeholder="e.g. 50" min="1"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm bulk-price" name="bulk_price[]" placeholder="0.00" min="0"></td>
    <td><span class="bulk-disc text-success fw-bold" style="font-size:12px"></span></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
</tr>`;
document.getElementById('addBulkRow').addEventListener('click', () => addRow('bulkPricingBody', bulkRowTemplate));

// Auto-calc bulk discount %
document.getElementById('bulkPricingBody').addEventListener('input', function(e) {
    if (!e.target.classList.contains('bulk-price')) return;
    const row  = e.target.closest('tr');
    const mrp  = parseFloat(document.getElementById('regular_price').value) || 0;
    const price = parseFloat(e.target.value) || 0;
    const discEl = row.querySelector('.bulk-disc');
    if (mrp > 0 && price > 0 && price < mrp) {
        discEl.textContent = Math.round(((mrp - price) / mrp) * 100) + '% OFF';
    } else { discEl.textContent = ''; }
});

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

function addCustomRow() {
    addRow('customBody', `<tr>
        <td><input type="text" class="form-control form-control-sm" name="customization_labels[]" placeholder="e.g. Back Side"></td>
        <td><select class="form-select form-select-sm" name="customization_types[]">
            <option value="text">Text Input</option>
            <option value="image">Image Upload</option>
            <option value="both">Text + Image</option>
        </select></td>
        <td><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="customization_required[]" value="1"></div></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">×</button></td>
    </tr>`);
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
    const statEl = document.querySelector('[name="status"]');
    document.getElementById('sumStatus').textContent = statEl ? statEl.options[statEl.selectedIndex].text : '—';
    const catEl  = document.getElementById('category_id');
    document.getElementById('sumCat').textContent    = catEl?.options[catEl.selectedIndex]?.text || '—';
}

// ─── Category cascades ────────────────────────────────────────────────────────
function loadSubCategories(parentId) {
    const sub    = document.getElementById('sub_category_id');
    const subsub = document.getElementById('sub_sub_category_id');
    sub.innerHTML    = '<option value="">Loading...</option>';
    subsub.innerHTML = '<option value="">— Select Sub First —</option>';
    if (!parentId) { sub.innerHTML = '<option value="">— Select Main Category First —</option>'; return; }
    fetch(`ajax/get-sub-categories.php?parent_id=${parentId}`)
        .then(r => r.json())
        .then(data => {
            sub.innerHTML = '<option value="">— Select Sub Category —</option>';
            data.forEach(c => sub.innerHTML += `<option value="${c.id}">${c.name}</option>`);
        });
}

function loadSubSubCategories(parentId) {
    const subsub = document.getElementById('sub_sub_category_id');
    subsub.innerHTML = '<option value="">Loading...</option>';
    if (!parentId) { subsub.innerHTML = '<option value="">— Select Sub Category First —</option>'; return; }
    fetch(`ajax/get-sub-sub-categories.php?parent_id=${parentId}`)
        .then(r => r.json())
        .then(data => {
            subsub.innerHTML = '<option value="">— Select Sub Sub Category —</option>';
            data.forEach(c => subsub.innerHTML += `<option value="${c.id}">${c.name}</option>`);
        });
}

// ─── CKEditor init ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    ['description','long_description','instructions','delivery_info'].forEach(id => {
        if (document.getElementById(id)) CKEDITOR.replace(id);
    });
    goToTab(1);
});
</script>
</body>
</html>
