<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/ProductLayoutController.php');

$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$layoutController = new ProductLayoutController();
$mobileSections = $layoutController->getSections('mobile');

// Calculate stats
$totalCount = count($mobileSections);
$activeCount = count(array_filter($mobileSections, fn($s) => $s['status'] === 'active'));
$carouselCount = count(array_filter($mobileSections, fn($s) => in_array($s['section_type'], ['product_carousel', 'recently_viewed'])));
$bannerCount = count(array_filter($mobileSections, fn($s) => in_array($s['section_type'], ['slider', 'banner', 'small_banner_carousel', 'small_banner_slider', 'marketing_banners'])));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Product Detail Page Layout Manager (Mobile) | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            opacity: 1 !important;
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
        }

        .stat-card {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .drag-handle {
            cursor: grab;
            padding: 8px 12px;
            color: #94a3b8;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .drag-handle:hover {
            color: #3b82f6;
            background-color: #eff6ff;
        }
        .drag-handle:active {
            cursor: grabbing;
        }

        .sortable-item {
            transition: all 0.2s ease;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #0284c7;
            border-radius: 8px;
        }
        .sortable-item:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }
        .sortable-item.sortable-ghost {
            opacity: 0.35;
            background: #e2e8f0;
            border: 2px dashed #0284c7;
        }

        .type-pill {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .pill-carousel { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .pill-banner { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .pill-slider { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .pill-core { background-color: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
        .pill-reviews { background-color: #fdf2f8; color: #be185d; border: 1px solid #fce7f3; }
        .pill-offers { background-color: #fefce8; color: #a16207; border: 1px solid #fef08a; }

        .order-badge {
            width: 28px;
            height: 28px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }
    </style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include('includes/side-navbar.php'); ?>

        <div class="main">
            <?php include('includes/top-navbar.php'); ?>

            <main class="content p-3 p-md-4">
                <div class="container-fluid p-0">

                    <!-- Header -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-primary px-2.5 py-1 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Mobile PDP CMS</span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 text-uppercase fw-semibold" style="font-size: 0.7rem;">Mobile Format Only</span>
                            </div>
                            <h1 class="h3 text-gray-800 fw-bold mb-1">Product Detail Page Layout Manager</h1>
                            <p class="text-muted small mb-0">Control the ordering, visibility, product carousels, banners, and widgets on the Mobile Product Detail Page.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="small-banner-slider.php?page=product&device=mobile" class="btn btn-outline-info shadow-sm btn-sm d-flex align-items-center gap-1.5">
                                <i class="fas fa-images"></i> Manage Product Banners
                            </a>
                            <button type="button" class="btn btn-primary shadow-sm btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                                <i class="fas fa-plus-circle"></i> Add New Section
                            </button>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="stat-card p-3 d-flex align-items-center gap-3">
                                <div class="rounded-circle p-2.5 bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-layer-group fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark"><?php echo $totalCount; ?></div>
                                    <div class="text-muted text-xs">Total Sections</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card p-3 d-flex align-items-center gap-3">
                                <div class="rounded-circle p-2.5 bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark"><?php echo $activeCount; ?></div>
                                    <div class="text-muted text-xs">Active on Mobile</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card p-3 d-flex align-items-center gap-3">
                                <div class="rounded-circle p-2.5 bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-boxes fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark"><?php echo $carouselCount; ?></div>
                                    <div class="text-muted text-xs">Product Carousels</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card p-3 d-flex align-items-center gap-3">
                                <div class="rounded-circle p-2.5 bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-image fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark"><?php echo $bannerCount; ?></div>
                                    <div class="text-muted text-xs">Banners &amp; Sliders</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Card -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-mobile-alt text-primary fs-5"></i>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">Mobile Product Details Layout</h5>
                                    <span class="text-muted text-xs">Drag and drop sections to rearrange order. Changes save instantly.</span>
                                </div>
                            </div>
                            <span id="saveStatus" class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1" style="display: none;">
                                <i class="fas fa-check me-1"></i> Saved
                            </span>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div id="sortableSectionList" class="d-flex flex-column gap-2.5">
                                <?php foreach ($mobileSections as $index => $sec): 
                                    $sType = $sec['section_type'];
                                    $pillClass = 'pill-core';
                                    $typeIcon = 'fa-cube';

                                    if (in_array($sType, ['product_carousel', 'recently_viewed'])) {
                                        $pillClass = 'pill-carousel';
                                        $typeIcon = 'fa-boxes';
                                    } elseif (in_array($sType, ['slider', 'small_banner_carousel', 'small_banner_slider', 'one_and_half_carousel'])) {
                                        $pillClass = 'pill-slider';
                                        $typeIcon = 'fa-sliders-h';
                                    } elseif (in_array($sType, ['banner', 'banner_grid', 'marketing_banners'])) {
                                        $pillClass = 'pill-banner';
                                        $typeIcon = 'fa-image';
                                    } elseif (in_array($sType, ['ratings_reviews', 'questions_answers'])) {
                                        $pillClass = 'pill-reviews';
                                        $typeIcon = 'fa-star';
                                    } elseif (in_array($sType, ['offers_coupons', 'loyalty_coins_reward'])) {
                                        $pillClass = 'pill-offers';
                                        $typeIcon = 'fa-tag';
                                    }
                                ?>
                                    <div class="sortable-item p-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" data-id="<?php echo $sec['id']; ?>">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="drag-handle" title="Drag to reorder">
                                                <i class="fas fa-grip-vertical fa-lg"></i>
                                            </div>
                                            <div class="order-badge"><?php echo $index + 1; ?></div>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($sec['label']); ?></h6>
                                                    <span class="type-pill <?php echo $pillClass; ?>">
                                                        <i class="fas <?php echo $typeIcon; ?>"></i> <?php echo str_replace('_', ' ', $sType); ?>
                                                    </span>
                                                    <?php if (!empty($sec['api_action'])): ?>
                                                        <span class="badge bg-light text-secondary border text-xs">
                                                            action: <?php echo htmlspecialchars($sec['api_action']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted text-xs mt-1 d-flex align-items-center gap-3">
                                                    <code>key: <?php echo htmlspecialchars($sec['section_key']); ?></code>
                                                    <?php if (!empty($sec['product_limit'])): ?>
                                                        <span><i class="fas fa-list-ol me-1"></i>Limit: <?php echo $sec['product_limit']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end gap-2.5 mt-2 mt-md-0">
                                            <!-- Status Toggle -->
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input status-toggle cursor-pointer" type="checkbox" role="switch"
                                                    data-id="<?php echo $sec['id']; ?>"
                                                    <?php echo ($sec['status'] === 'active') ? 'checked' : ''; ?>>
                                            </div>

                                            <?php if (in_array($sType, ['small_banner_carousel', 'small_banner_slider'])): ?>
                                                <a href="small-banner-slider.php?page=product&device=mobile" class="btn btn-sm btn-outline-info" title="Manage Small Banners">
                                                    <i class="fas fa-images"></i> Banners
                                                </a>
                                            <?php endif; ?>

                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary edit-section-btn"
                                                data-section='<?php echo json_encode($sec, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                                <i class="fas fa-pen"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-section-btn"
                                                data-id="<?php echo $sec['id']; ?>" data-label="<?php echo htmlspecialchars($sec['label']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         MODAL: ADD NEW SECTION
    ══════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-plus-circle text-primary me-2"></i>Add New Product Page Section (Mobile)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addSectionForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs">Section Type *</label>
                                <select class="form-select" name="section_type" id="add_section_type" required>
                                    <optgroup label="Product Carousels">
                                        <option value="product_carousel" selected>Product Carousel (Similar / Discount / You Might Like)</option>
                                        <option value="recently_viewed">Recently Viewed Products</option>
                                    </optgroup>
                                    <optgroup label="Banners &amp; Sliders">
                                        <option value="small_banner_carousel">Small Banner Slider (Product Page)</option>
                                        <option value="banner">Single Promo Banner</option>
                                        <option value="marketing_banners">Marketing Showcase Banners</option>
                                        <option value="one_and_half_carousel">Peeking Banner Slider (1.5)</option>
                                    </optgroup>
                                    <optgroup label="Product Widgets">
                                        <option value="ratings_reviews">Customer Ratings &amp; Reviews</option>
                                        <option value="questions_answers">Questions &amp; Answers (Q&amp;A)</option>
                                        <option value="about_product_tabs">About the Product Tabs</option>
                                        <option value="offers_coupons">Offers &amp; Coupons Strip</option>
                                        <option value="delivery_pincode_check">Delivery Pincode Checker</option>
                                        <option value="loyalty_coins_reward">Printmont Coins Loyalty Box</option>
                                        <option value="custom_html">Custom HTML / Announcement Block</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs">Section Label / Title *</label>
                                <input type="text" class="form-control" name="label" id="add_label" placeholder="e.g. Discount on Similar Products (Carousel)" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs">Section Key (Unique Identifier)</label>
                                <input type="text" class="form-control" name="section_key" id="add_section_key" placeholder="Auto-generated if left blank">
                            </div>

                            <div class="col-md-6" id="apiActionWrapper">
                                <label class="form-label fw-bold text-xs">Data Source / API Action</label>
                                <select class="form-select" name="api_action" id="add_api_action">
                                    <option value="related">Related / Similar Products</option>
                                    <option value="discount">Discount on Similar Products</option>
                                    <option value="top_selection">Top Selection / You Might Like</option>
                                    <option value="recently_viewed">Recently Viewed</option>
                                    <option value="bestseller">Bestsellers</option>
                                    <option value="top_rated">Top Rated</option>
                                    <option value="handpicked">Handpicked Product IDs</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs">Product Limit</label>
                                <input type="number" class="form-control" name="product_limit" value="8" min="1" max="24">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs">Badge Text</label>
                                <input type="text" class="form-control" name="badge_text" placeholder="e.g. Bestseller, 50% Off">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs">Initial Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12" id="bannerUrlWrapper" style="display: none;">
                                <label class="form-label fw-bold text-xs">Banner Image URL / Target URL</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="background_image_url" placeholder="Image URL (e.g. /uploads/banner.jpg)">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="banner_target_url" placeholder="Target Link (e.g. /allproducts?category=...)">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12" id="customHtmlWrapper" style="display: none;">
                                <label class="form-label fw-bold text-xs">Custom HTML / Text</label>
                                <textarea class="form-control" name="custom_html" rows="3" placeholder="Enter custom HTML markup"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="addSectionSubmitBtn">Save Section</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         MODAL: EDIT SECTION
    ══════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editSectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit text-primary me-2"></i>Edit Product Page Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editSectionForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs">Section Type *</label>
                                <select class="form-select" name="section_type" id="edit_section_type" required>
                                    <optgroup label="Product Carousels">
                                        <option value="product_carousel">Product Carousel (Similar / Discount / You Might Like)</option>
                                        <option value="recently_viewed">Recently Viewed Products</option>
                                    </optgroup>
                                    <optgroup label="Banners &amp; Sliders">
                                        <option value="small_banner_carousel">Small Banner Slider (Product Page)</option>
                                        <option value="banner">Single Promo Banner</option>
                                        <option value="marketing_banners">Marketing Showcase Banners</option>
                                        <option value="one_and_half_carousel">Peeking Banner Slider (1.5)</option>
                                    </optgroup>
                                    <optgroup label="Product Widgets">
                                        <option value="ratings_reviews">Customer Ratings &amp; Reviews</option>
                                        <option value="questions_answers">Questions &amp; Answers (Q&amp;A)</option>
                                        <option value="about_product_tabs">About the Product Tabs</option>
                                        <option value="offers_coupons">Offers &amp; Coupons Strip</option>
                                        <option value="delivery_pincode_check">Delivery Pincode Checker</option>
                                        <option value="loyalty_coins_reward">Printmont Coins Loyalty Box</option>
                                        <option value="custom_html">Custom HTML / Announcement Block</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs">Section Label / Title *</label>
                                <input type="text" class="form-control" name="label" id="edit_label" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-xs">Section Key</label>
                                <input type="text" class="form-control" name="section_key" id="edit_section_key" readonly>
                            </div>

                            <div class="col-md-6" id="editApiActionWrapper">
                                <label class="form-label fw-bold text-xs">Data Source / API Action</label>
                                <select class="form-select" name="api_action" id="edit_api_action">
                                    <option value="">None / Default</option>
                                    <option value="related">Related / Similar Products</option>
                                    <option value="discount">Discount on Similar Products</option>
                                    <option value="top_selection">Top Selection / You Might Like</option>
                                    <option value="recently_viewed">Recently Viewed</option>
                                    <option value="bestseller">Bestsellers</option>
                                    <option value="top_rated">Top Rated</option>
                                    <option value="handpicked">Handpicked Product IDs</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs">Product Limit</label>
                                <input type="number" class="form-control" name="product_limit" id="edit_product_limit" min="1" max="24">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs">Badge Text</label>
                                <input type="text" class="form-control" name="badge_text" id="edit_badge_text">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-xs">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12" id="editBannerUrlWrapper" style="display: none;">
                                <label class="form-label fw-bold text-xs">Banner Image URL / Target URL</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="background_image_url" id="edit_background_image_url" placeholder="Image URL">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="banner_target_url" id="edit_banner_target_url" placeholder="Target Link">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12" id="editCustomHtmlWrapper" style="display: none;">
                                <label class="form-label fw-bold text-xs">Custom HTML / Text</label>
                                <textarea class="form-control" name="custom_html" id="edit_custom_html" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="editSectionSubmitBtn">Update Section</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const listEl = document.getElementById("sortableSectionList");
            const saveStatus = document.getElementById("saveStatus");

            // Initialize SortableJS
            if (listEl) {
                new Sortable(listEl, {
                    handle: ".drag-handle",
                    animation: 200,
                    ghostClass: "sortable-ghost",
                    onEnd: function () {
                        updateOrderBadges();
                        saveOrder();
                    }
                });
            }

            function updateOrderBadges() {
                document.querySelectorAll(".sortable-item").forEach((el, index) => {
                    const badge = el.querySelector(".order-badge");
                    if (badge) badge.textContent = index + 1;
                });
            }

            function saveOrder() {
                const sectionIds = Array.from(document.querySelectorAll(".sortable-item")).map(el => el.dataset.id);
                fetch("api/product-layout-api.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: "reorder", section_ids: sectionIds })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        saveStatus.style.display = "inline-flex";
                        setTimeout(() => { saveStatus.style.display = "none"; }, 2000);
                    } else {
                        Swal.fire("Error", data.error || "Failed to reorder sections.", "error");
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire("Error", "Network error while saving order.", "error");
                });
            }

            // Status Toggle
            document.querySelectorAll(".status-toggle").forEach(toggle => {
                toggle.addEventListener("change", function () {
                    const id = this.dataset.id;
                    const status = this.checked ? "active" : "inactive";
                    fetch("api/product-layout-api.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ action: "toggle_status", id: id, status: status })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            toggle.checked = !toggle.checked;
                            Swal.fire("Error", data.error || "Failed to toggle status.", "error");
                        }
                    })
                    .catch(() => {
                        toggle.checked = !toggle.checked;
                        Swal.fire("Error", "Network error.", "error");
                    });
                });
            });

            // Toggle dynamic fields on type change
            function handleTypeChange(typeSelect, apiWrapper, bannerWrapper, htmlWrapper) {
                const val = typeSelect.value;
                if (val === 'product_carousel' || val === 'recently_viewed') {
                    apiWrapper.style.display = 'block';
                    bannerWrapper.style.display = 'none';
                    htmlWrapper.style.display = 'none';
                } else if (val === 'banner' || val === 'banner_grid' || val === 'marketing_banners') {
                    apiWrapper.style.display = 'none';
                    bannerWrapper.style.display = 'block';
                    htmlWrapper.style.display = 'none';
                } else if (val === 'custom_html') {
                    apiWrapper.style.display = 'none';
                    bannerWrapper.style.display = 'none';
                    htmlWrapper.style.display = 'block';
                } else {
                    apiWrapper.style.display = 'none';
                    bannerWrapper.style.display = 'none';
                    htmlWrapper.style.display = 'none';
                }
            }

            const addType = document.getElementById("add_section_type");
            addType.addEventListener("change", () => {
                handleTypeChange(
                    addType,
                    document.getElementById("apiActionWrapper"),
                    document.getElementById("bannerUrlWrapper"),
                    document.getElementById("customHtmlWrapper")
                );
            });

            const editType = document.getElementById("edit_section_type");
            editType.addEventListener("change", () => {
                handleTypeChange(
                    editType,
                    document.getElementById("editApiActionWrapper"),
                    document.getElementById("editBannerUrlWrapper"),
                    document.getElementById("editCustomHtmlWrapper")
                );
            });

            // Add Section Submit
            document.getElementById("addSectionForm").addEventListener("submit", function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const payload = Object.fromEntries(formData.entries());
                payload.action = "create";
                payload.page_target = "mobile";

                fetch("api/product-layout-api.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            text: "Section added successfully.",
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire("Error", data.error || "Failed to create section.", "error");
                    }
                })
                .catch(() => Swal.fire("Error", "Network error.", "error"));
            });

            // Edit Section Click
            document.querySelectorAll(".edit-section-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    const sec = JSON.parse(this.dataset.section);
                    document.getElementById("edit_id").value = sec.id;
                    document.getElementById("edit_label").value = sec.label;
                    document.getElementById("edit_section_key").value = sec.section_key;
                    document.getElementById("edit_section_type").value = sec.section_type;
                    document.getElementById("edit_api_action").value = sec.api_action || "";
                    document.getElementById("edit_product_limit").value = sec.product_limit || 8;
                    document.getElementById("edit_badge_text").value = sec.badge_text || "";
                    document.getElementById("edit_background_image_url").value = sec.background_image_url || "";
                    document.getElementById("edit_banner_target_url").value = sec.banner_target_url || "";
                    document.getElementById("edit_custom_html").value = sec.custom_html || "";
                    document.getElementById("edit_status").value = sec.status;

                    handleTypeChange(
                        document.getElementById("edit_section_type"),
                        document.getElementById("editApiActionWrapper"),
                        document.getElementById("editBannerUrlWrapper"),
                        document.getElementById("editCustomHtmlWrapper")
                    );

                    new bootstrap.Modal(document.getElementById("editSectionModal")).show();
                });
            });

            // Edit Section Submit
            document.getElementById("editSectionForm").addEventListener("submit", function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const payload = Object.fromEntries(formData.entries());
                payload.action = "update";

                fetch("api/product-layout-api.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Updated!",
                            text: "Section updated successfully.",
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire("Error", data.error || "Failed to update section.", "error");
                    }
                })
                .catch(() => Swal.fire("Error", "Network error.", "error"));
            });

            // Delete Section Click
            document.querySelectorAll(".delete-section-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    const id = this.dataset.id;
                    const label = this.dataset.label;
                    Swal.fire({
                        title: "Delete Section?",
                        text: `Are you sure you want to remove "${label}"?`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Yes, delete it!"
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch("api/product-layout-api.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ action: "delete", id: id })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: "success",
                                        title: "Deleted!",
                                        text: "Section removed successfully.",
                                        timer: 1200,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire("Error", data.error || "Failed to delete section.", "error");
                                }
                            })
                            .catch(() => Swal.fire("Error", "Network error.", "error"));
                        }
                    });
                });
            });

        });
    </script>
</body>

</html>
