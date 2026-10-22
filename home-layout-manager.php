<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/HomeLayoutController.php');

$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$layoutController = new HomeLayoutController();
$desktopSections = $layoutController->getSections('desktop');
$mobileSections = $layoutController->getSections('mobile');

// Fetch active categories with parent info
$allCategories = $db->fetchAll("
    SELECT c.id, c.name, c.slug, c.parent_id, c.level, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    WHERE c.status = 'active' 
    ORDER BY c.level ASC, c.name ASC
");
$level1Categories = array_filter($allCategories, fn($c) => (int)($c['level'] ?? 0) === 1 || (int)($c['parent_id'] ?? 0) === 0);
$level2Categories = array_filter($allCategories, fn($c) => (int)($c['level'] ?? 0) === 2 && (int)($c['parent_id'] ?? 0) > 0);
$mainCategories = $level1Categories;

// Calculate stats
$desktopCount = count($desktopSections);
$mobileCount = count($mobileSections);
$activeDesktop = count(array_filter($desktopSections, fn($s) => $s['status'] === 'active'));
$activeMobile = count(array_filter($mobileSections, fn($s) => $s['status'] === 'active'));
$totalBanners = $db->fetch("SELECT COUNT(*) as count FROM banners WHERE status = 'active'")['count'] ?? 0;
$totalBannerSections = $db->fetch("SELECT COUNT(*) as count FROM banner_sections WHERE status = 'active'")['count'] ?? 0;

$activeTabParam = $_GET['tab'] ?? '';
$isMobileActive = ($activeTabParam === 'mobile');
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
    <title>Homepage Layout & Product Manager | Printmont Admin</title>
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
            border-left: 5px solid #64748b;
            border-radius: 8px;
        }
        .sortable-item:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }
        .sortable-item.sortable-ghost {
            opacity: 0.35;
            background: #e2e8f0;
            border: 2px dashed #3b82f6;
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
        .pill-list { background-color: #faf5ff; color: #7e22ce; border: 1px solid #f3e8ff; }
        .pill-grid { background-color: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }
        .pill-banner { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .pill-slider { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .pill-mosaic { background-color: #fdf2f8; color: #be185d; border: 1px solid #fce7f3; }
        .pill-widget { background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
        .pill-reviews { background-color: #fefce8; color: #a16207; border: 1px solid #fef08a; }

        .meta-tag {
            font-size: 0.78rem;
            padding: 2px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .preview-img-thumbnail {
            width: 54px;
            height: 38px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: transform 0.2s;
        }
        .preview-img-thumbnail:hover {
            transform: scale(1.3);
            z-index: 5;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider-switch {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 34px;
        }
        .slider-switch:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        input:checked + .slider-switch {
            background-color: #10b981;
        }
        input:checked + .slider-switch:before {
            transform: translateX(20px);
        }

        .preset-badge-btn {
            font-size: 0.78rem;
            padding: 4px 10px;
            border-radius: 15px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s;
        }
        .preset-badge-btn:hover {
            background: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
        }

        .slot-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 12px;
            position: relative;
        }

        .order-badge {
            width: 32px;
            height: 32px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
        }

        /* Product Search & Picker Styles */
        .product-search-wrapper {
            position: relative;
        }
        .product-search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            max-height: 280px;
            overflow-y: auto;
            z-index: 1060;
            display: none;
        }
        .product-search-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .product-search-item:hover {
            background-color: #eff6ff;
        }
        .product-search-item:last-child {
            border-bottom: none;
        }
        .prod-thumb-mini {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .selected-prod-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
            max-height: 360px;
            overflow-y: auto;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .selected-prod-item {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .selected-prod-item .prod-thumb {
            width: 44px;
            height: 44px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .selected-prod-item .prod-info {
            flex-grow: 1;
            min-width: 0;
        }
        .selected-prod-item .prod-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .selected-prod-item .prod-meta {
            font-size: 0.72rem;
            color: #64748b;
        }
        .selected-prod-item .prod-actions {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex-shrink: 0;
        }

        /* Source Strategy Tabs */
        .source-tab-btn {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-weight: 600;
            font-size: 0.82rem;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .source-tab-btn.active {
            background: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
        }

        /* Subcategory Selector Chips & Color Presets */
        .subcat-chips-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 240px;
            overflow-y: auto;
            padding: 12px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        .subcat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            margin-bottom: 0;
        }
        .subcat-chip:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .subcat-chip.selected {
            background: #3b82f6;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
        }
        .subcat-chip input[type="checkbox"] {
            display: none;
        }
        .color-preset-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 1px #cbd5e1;
            cursor: pointer;
            transition: transform 0.15s;
        }
        .color-preset-btn:hover {
            transform: scale(1.15);
        }

        /* Floating Toast */
        .toast-container-custom {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 9999;
        }
    </style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>

        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content p-4">
                <div class="container-fluid p-0">
                    
                    <!-- Page Header & Actions -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">
                                <i class="fas fa-layer-group text-primary me-2"></i>Homepage Layout & Product Manager
                            </h1>
                            <p class="text-muted mb-0">
                                Complete CMS control over desktop and mobile sections: rearrange order, switch between Carousel / Vertical Grid, handpick products, and manage banner slots.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="dropdown">
                                <button class="btn btn-primary px-3 shadow-sm dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-plus me-1"></i> Add Section / Banner
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 340px; max-height: 480px; overflow-y: auto;">
                                    <li><h6 class="dropdown-header text-uppercase fw-bold text-success"><i class="fas fa-image me-1"></i> Static Banners</h6></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('banner', '1 Full-Width Banner', 1)"><i class="fas fa-image text-success me-2"></i><strong>1 Single Full-Width Banner</strong> <small class="text-muted d-block ms-4">1 Wide banner across the row</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('banner', '2 Square Banners in One Row', 2)"><i class="fas fa-table-cells-large text-success me-2"></i><strong>2 Square Banners in One Row</strong> <small class="text-muted d-block ms-4">Side-by-side 1:1 square banners</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('banner', '3 Banners in One Row', 3)"><i class="fas fa-table-columns text-success me-2"></i><strong>3 Banners in One Row (Grid)</strong> <small class="text-muted d-block ms-4">3 Columns equal banner cards</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('banner', '4 Banners in One Row', 4)"><i class="fas fa-border-all text-success me-2"></i><strong>4 Banners in One Row (Grid)</strong> <small class="text-muted d-block ms-4">4 Columns equal banner cards</small></a></li>
                                    
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header text-uppercase fw-bold text-primary"><i class="fas fa-sliders me-1"></i> Carousels &amp; Sliders</h6></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('slider', 'Main Hero Banner Slider', 1)"><i class="fas fa-tv text-danger me-2"></i><strong>Main Hero Banner Slider</strong> <small class="text-muted d-block ms-4">Top full-width sliding carousel</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('one_and_half_carousel', '1.5 Peeking Banner Carousel', 1)"><i class="fas fa-arrows-split-up-and-left text-primary me-2"></i><strong>1.5 Peeking Banner Carousel</strong> <small class="text-muted d-block ms-4">1 Slide + 50% next banner peek</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('small_banner_carousel', 'Small Banner Carousel', 1)"><i class="fas fa-film text-info me-2"></i><strong>Small Height Banner Strip Carousel</strong> <small class="text-muted d-block ms-4">Compact height promo slider</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('slider', '3-Card Banner Carousel', 3)"><i class="fas fa-sliders text-primary me-2"></i><strong>3-Card Banner Carousel (Sliding)</strong> <small class="text-muted d-block ms-4">3 Cards visible sliding carousel</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('slider', '4-Card Banner Carousel', 4)"><i class="fas fa-sliders text-primary me-2"></i><strong>4-Card Banner Carousel (Sliding)</strong> <small class="text-muted d-block ms-4">4 Cards visible sliding carousel</small></a></li>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header text-uppercase fw-bold text-warning"><i class="fas fa-boxes-stacked me-1"></i> Products &amp; Owl Carousels</h6></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('product_carousel', 'Product Owl Carousel')"><i class="fas fa-sliders-h text-warning me-2"></i><strong>Product Owl Carousel (Sliding)</strong> <small class="text-muted d-block ms-4">Horizontal sliding product carousel</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('compact_product_grid', 'Bestsellers (4-Row Owl Carousel)', 4)"><i class="fas fa-fire text-danger me-2"></i><strong>4-Row Bestseller Owl Carousel</strong> <small class="text-muted d-block ms-4">Multi-row sliding cards</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('mobile_list', 'Vertical 2-Col Product Grid', 2)"><i class="fas fa-th-large text-purple me-2"></i><strong>Vertical 2-Col Product Grid</strong> <small class="text-muted d-block ms-4">2-Column responsive product grid</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('featured_grid', 'Top Selection (Category)', 2, 3)"><i class="fas fa-table-cells text-primary me-2"></i><strong>Top Selection (1 Large + 2 Stacked)</strong> <small class="text-muted d-block ms-4">1 Large featured item + 2 stacked side items</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('featured_grid', 'Featured Product Grid', 3)"><i class="fas fa-th text-info me-2"></i><strong>3-Col Featured Product Grid</strong> <small class="text-muted d-block ms-4">Compact 3-column grid</small></a></li>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header text-uppercase fw-bold text-success"><i class="fas fa-folder-tree me-1"></i> Categories &amp; Sub-Categories</h6></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x2)', 2, 4)"><i class="fas fa-table-cells text-success me-2"></i><strong>Category Grid (2x2 - 4 Items)</strong> <small class="text-muted d-block ms-4">Pick Main Category + 4 Sub-Categories</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x4)', 2, 8)"><i class="fas fa-table-cells-large text-success me-2"></i><strong>Category Grid (2x4 - 8 Items)</strong> <small class="text-muted d-block ms-4">Pick Main Category + 8 Sub-Categories</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x6)', 2, 12)"><i class="fas fa-table-columns text-success me-2"></i><strong>Category Grid (2x6 - 12 Items)</strong> <small class="text-muted d-block ms-4">Pick Main Category + 12 Sub-Categories</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x8)', 2, 16)"><i class="fas fa-border-all text-success me-2"></i><strong>Category Grid (2x8 - 16 Items)</strong> <small class="text-muted d-block ms-4">Pick Main Category + 16 Sub-Categories</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (3x3)', 3, 9)"><i class="fas fa-th text-success me-2"></i><strong>Category Grid (3x3 - 9 Items)</strong> <small class="text-muted d-block ms-4">Pick Main Category + 9 Sub-Categories</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('category_slide_row', 'Category Slide Strip', 1, 8)"><i class="fas fa-circle-notch text-info me-2"></i><strong>Category Circles Slide Strip</strong> <small class="text-muted d-block ms-4">Circular icons category slider</small></a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('category_mosaic', 'Category Product Mosaic', 2, 8)"><i class="fas fa-puzzle-piece text-danger me-2"></i><strong>Category Product Mosaic</strong> <small class="text-muted d-block ms-4">Grouped categories with products</small></a></li>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header text-uppercase fw-bold text-warning"><i class="fas fa-star me-1"></i> Customer Reviews</h6></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openPresetModal('customer_reviews', 'Customer Reviews Carousel', 1)"><i class="fas fa-star text-warning me-2"></i><strong>Customer Reviews Carousel</strong> <small class="text-muted d-block ms-4">Top Rated 4-5★ / Handpicked Reviews</small></a></li>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openCreateModal()"><i class="fas fa-cog text-secondary me-2"></i><strong>Custom Blank Section...</strong></a></li>
                                </ul>
                            </div>
                            <a href="view-banner.php" class="btn btn-outline-secondary px-3">
                                <i class="fas fa-image me-1"></i> All Banners
                            </a>
                        </div>
                    </div>

                    <!-- Quick Preset Cards Toolbar -->
                    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fw-bold text-dark small">
                                    <i class="fas fa-bolt text-warning me-1"></i> 1-Click Quick Add Sections to Homepage:
                                </div>
                                <span class="badge bg-light text-secondary border">Desktop &amp; Mobile Independent</span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <!-- Banners -->
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('banner', '1 Full-Width Banner', 1)" title="1 Full-Width Banner">
                                    <i class="fas fa-image me-1"></i> 1 Full Banner
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('banner', '2 Square Banners Row', 2)" title="2 Square Banners in One Row">
                                    <i class="fas fa-table-cells-large me-1"></i> 2 Square Banners
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('banner', '3 Banners in 1 Row', 3)" title="3 Banners in 1 Row">
                                    <i class="fas fa-table-columns me-1"></i> 3 Banners
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('banner', '4 Banners in 1 Row', 4)" title="4 Banners in 1 Row">
                                    <i class="fas fa-border-all me-1"></i> 4 Banners
                                </button>
                                
                                <!-- Carousels -->
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="openPresetModal('slider', 'Main Hero Slider', 1)" title="Hero Banner Slider">
                                    <i class="fas fa-tv me-1"></i> Hero Slider
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="openPresetModal('one_and_half_carousel', '1.5 Peeking Carousel', 1)" title="1.5 Peeking Banner Carousel">
                                    <i class="fas fa-arrows-split-up-and-left me-1"></i> 1.5 Peeking Carousel
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info fw-semibold" onclick="openPresetModal('small_banner_carousel', 'Small Banner Carousel', 1)" title="Small Height Banner Carousel">
                                    <i class="fas fa-film me-1"></i> Small Banner Carousel
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="openPresetModal('slider', '3-Card Banner Carousel', 3)" title="3-Card Banner Carousel">
                                    <i class="fas fa-sliders me-1"></i> 3-Card Carousel
                                </button>

                                <!-- Products & Owl Carousels -->
                                <button type="button" class="btn btn-sm btn-outline-warning fw-semibold text-dark" onclick="openPresetModal('product_carousel', 'Product Owl Carousel')" title="Product Owl Carousel">
                                    <i class="fas fa-sliders-h me-1 text-warning"></i> Product Owl Carousel
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" onclick="openPresetModal('compact_product_grid', '4-Row Bestseller Carousel', 4, 16)" title="4-Row Bestseller Carousel">
                                    <i class="fas fa-fire me-1"></i> 4-Row Bestsellers
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-purple fw-semibold" onclick="openPresetModal('mobile_list', 'Vertical Product Grid', 2, 8)" title="Vertical 2-Col Product Grid">
                                    <i class="fas fa-th-large me-1"></i> 2-Col Product Grid
                                </button>

                                <!-- Category & Subcategories -->
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x2)', 2, 4)" title="Category & Subcategories (2x2)">
                                    <i class="fas fa-table-cells me-1"></i> Category 2x2
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x4)', 2, 8)" title="Category & Subcategories (2x4)">
                                    <i class="fas fa-table-cells-large me-1"></i> Category 2x4
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x6)', 2, 12)" title="Category & Subcategories (2x6)">
                                    <i class="fas fa-table-columns me-1"></i> Category 2x6
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (2x8)', 2, 16)" title="Category & Subcategories (2x8)">
                                    <i class="fas fa-border-all me-1"></i> Category 2x8
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="openPresetModal('compact_grid', 'Category & Subcategories Grid (3x3)', 3, 9)" title="Category & Subcategories (3x3)">
                                    <i class="fas fa-th me-1"></i> Category 3x3
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info fw-semibold" onclick="openPresetModal('category_slide_row', 'Category Slide Strip', 1, 8)" title="Category Slide Strip">
                                    <i class="fas fa-circle-notch me-1"></i> Category Circles
                                </button>

                                <!-- Reviews -->
                                <button type="button" class="btn btn-sm btn-outline-warning fw-semibold text-dark" onclick="openPresetModal('customer_reviews', 'Customer Reviews Carousel')" title="Customer Reviews Carousel">
                                    <i class="fas fa-star text-warning me-1"></i> Customer Reviews
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Overview Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">Desktop Sections</div>
                                    <div class="fs-4 fw-bold text-dark mt-1"><?php echo $desktopCount; ?> <span class="text-success fs-6 fw-normal">(<?php echo $activeDesktop; ?> Active)</span></div>
                                </div>
                                <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                                    <i class="fas fa-desktop fa-lg"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">Mobile Sections</div>
                                    <div class="fs-4 fw-bold text-dark mt-1"><?php echo $mobileCount; ?> <span class="text-success fs-6 fw-normal">(<?php echo $activeMobile; ?> Active)</span></div>
                                </div>
                                <div class="p-3 bg-success-subtle text-success rounded-circle">
                                    <i class="fas fa-mobile-alt fa-lg"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">Active Banner Sections</div>
                                    <div class="fs-4 fw-bold text-dark mt-1"><?php echo $totalBannerSections; ?></div>
                                </div>
                                <div class="p-3 bg-info-subtle text-info rounded-circle">
                                    <i class="fas fa-images fa-lg"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">Live Banners Uploaded</div>
                                    <div class="fs-4 fw-bold text-dark mt-1"><?php echo $totalBanners; ?></div>
                                </div>
                                <div class="p-3 bg-warning-subtle text-warning rounded-circle">
                                    <i class="fas fa-photo-film fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Manager Card with Tabs & Live Search -->
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white border-bottom p-3">
                            <div class="row g-3 align-items-center">
                                <!-- Layout Tabs -->
                                <div class="col-lg-5 col-12">
                                    <ul class="nav nav-pills" id="layoutTabs" role="tablist">
                                        <li class="nav-item me-2" role="presentation">
                                            <button class="nav-link <?php echo !$isMobileActive ? 'active' : ''; ?> px-4 py-2 fw-semibold" id="desktop-tab"
                                                data-bs-toggle="tab" data-bs-target="#desktop-pane" type="button"
                                                role="tab">
                                                <i class="fas fa-desktop me-2"></i>Desktop Homepage (<?php echo $desktopCount; ?>)
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php echo $isMobileActive ? 'active' : ''; ?> px-4 py-2 fw-semibold" id="mobile-tab" data-bs-toggle="tab"
                                                data-bs-target="#mobile-pane" type="button" role="tab">
                                                <i class="fas fa-mobile-alt me-2"></i>Mobile Homepage (<?php echo $mobileCount; ?>)
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Live Search Bar -->
                                <div class="col-lg-4 col-md-7 col-12">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                        <input type="text" id="sectionSearchInput" class="form-control border-start-0 bg-light"
                                            placeholder="Search sections by name, key, action..." oninput="filterSections()">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()" title="Clear search">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Type Filter -->
                                <div class="col-lg-3 col-md-5 col-12">
                                    <select id="typeFilterSelect" class="form-select bg-light" onchange="filterSections()">
                                        <option value="">All Section Types</option>
                                        <option value="product">🛍️ Product Carousels &amp; Grids</option>
                                        <option value="banner">🖼️ Banner &amp; Slider Sections</option>
                                        <option value="category">📂 Categories &amp; Mosaics</option>
                                        <option value="reviews">⭐ Customer Reviews</option>
                                        <option value="widget">🧩 Widgets (Gift Finder, Bulk, etc.)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-3 p-md-4">
                            <div class="tab-content" id="layoutTabContent">
                                
                                <!-- ================= DESKTOP TAB PANE ================= -->
                                <div class="tab-pane fade <?php echo !$isMobileActive ? 'show active' : ''; ?>" id="desktop-pane" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-muted small">
                                            <i class="fas fa-arrows-up-down me-1 text-primary"></i>
                                            Drag & drop using the handle <i class="fas fa-grip-vertical text-muted"></i> to reorder. Changes save instantly.
                                        </div>
                                        <div class="badge bg-light text-dark border px-3 py-2">
                                            Showing <span id="desktop-count"><?php echo $desktopCount; ?></span> sections
                                        </div>
                                    </div>

                                    <div id="desktop-list" class="list-group">
                                        <?php foreach ($desktopSections as $index => $sec): ?>
                                            <?php renderSectionItem($sec, $index + 1); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- ================= MOBILE TAB PANE ================= -->
                                <div class="tab-pane fade <?php echo $isMobileActive ? 'show active' : ''; ?>" id="mobile-pane" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-muted small">
                                            <i class="fas fa-arrows-up-down me-1 text-success"></i>
                                            Drag & drop using the handle <i class="fas fa-grip-vertical text-muted"></i> to reorder. Changes save instantly.
                                        </div>
                                        <div class="badge bg-light text-dark border px-3 py-2">
                                            Showing <span id="mobile-count"><?php echo $mobileCount; ?></span> sections
                                        </div>
                                    </div>

                                    <div id="mobile-list" class="list-group">
                                        <?php foreach ($mobileSections as $index => $sec): ?>
                                            <?php renderSectionItem($sec, $index + 1); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ================= ADD/EDIT SECTION MODAL ================= -->
    <div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form id="sectionForm" onsubmit="saveSection(event)" enctype="multipart/form-data">
                    <input type="hidden" id="section-id" name="id">
                    <input type="hidden" id="section-page-target" name="page_target" value="desktop">
                    <input type="hidden" id="product-specific-ids" name="product_ids" value="">

                    <div class="modal-header bg-light border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark" id="modalTitle">
                            <i class="fas fa-sliders-h text-primary me-2"></i>Configure Section
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        
                        <!-- Core Settings -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Section Label (Display Title) <span class="text-muted small fw-normal">(Optional)</span></label>
                                <input type="text" class="form-control" id="section-label" name="label"
                                    placeholder="e.g. Top Selection / Bestseller (Leave empty if no title needed)">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Section Type / Layout Format <span class="text-danger">*</span></label>
                                <select class="form-select" id="section-type" name="section_type" onchange="handleTypeChange(this.value)">
                                    <optgroup label="🖼️ Static Banners">
                                        <option value="banner">Static Banner Grid (1 Full, 2 Square, 3-Col, 4-Col Grid)</option>
                                    </optgroup>
                                    <optgroup label="🎠 Carousels &amp; Sliders">
                                        <option value="slider">Hero / Multi-Slide Banner Carousel (1, 2, 3, or 4 visible)</option>
                                        <option value="one_and_half_carousel">1.5 Peeking Banner Carousel (1 Banner + 50% Next Peek)</option>
                                        <option value="small_banner_carousel">Small Height Banner Strip Carousel</option>
                                        <option value="banner_category_slider">Banner &amp; Category Slider</option>
                                    </optgroup>
                                    <optgroup label="🛍️ Product Sections &amp; Grids">
                                        <option value="mobile_list">Vertical 2-Column Product Grid (Responsive List)</option>
                                        <option value="featured_grid">Featured Product Grid (3-Col)</option>
                                    </optgroup>
                                    <optgroup label="🦉 Product Owl Carousels">
                                        <option value="product_carousel">Product Owl Carousel (Horizontal Sliding)</option>
                                        <option value="compact_product_grid">Multi-Row Bestseller Carousel (4 Rows / Owl Carousel)</option>
                                    </optgroup>
                                    <optgroup label="📂 Categories &amp; Sub-Categories">
                                        <option value="compact_grid">Category &amp; Subcategories Grid (2x2 or 3x3 with Main Cat)</option>
                                        <option value="category_slide_row">Category Circles Slide Strip</option>
                                        <option value="category_mosaic">Category Product Mosaic (Grouped/Flat)</option>
                                    </optgroup>
                                    <optgroup label="⭐ Customer Reviews">
                                        <option value="customer_reviews">Customer Reviews Carousel (Top Rated / Handpicked)</option>
                                    </optgroup>
                                    <optgroup label="🧩 Widgets &amp; Special Sections">
                                        <option value="gift_finder">Gift Finder Widget</option>
                                        <option value="bulk_widget">Bulk Order Widget</option>
                                        <option value="brand_directory">Brand Directory Section</option>
                                        <option value="why_us">Why Us Section</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4" id="section-key-container">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Section Key Identifier</label>
                                <input type="text" class="form-control font-monospace" id="section-key" name="section_key"
                                    placeholder="e.g. mobile_top_selection_custom">
                                <div class="form-text text-muted">Internal system key used to fetch banners and sync state.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="section-status" name="status">
                                    <option value="active">Active (Visible)</option>
                                    <option value="inactive">Inactive (Hidden)</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4 text-muted">

                        <!-- ================= PRODUCT SECTION CONFIGURATIONS ================= -->
                        <div id="product-options" style="display: none;">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-boxes-stacked text-warning me-2"></i>Product Display & Source Settings
                            </h6>

                            <!-- Product Limit & Grid Columns & Badge Text -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        Product Count <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group mb-2">
                                        <input type="number" class="form-control fw-bold fs-5 text-primary" id="product-limit" name="product_limit"
                                            value="8" min="1" max="50" oninput="updateRequiredCountDisplay()">
                                        <span class="input-group-text bg-light">Items</span>
                                    </div>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="text-muted small me-1 align-self-center">Presets:</span>
                                        <button type="button" class="preset-badge-btn" onclick="setProductLimit(4)">4</button>
                                        <button type="button" class="preset-badge-btn" onclick="setProductLimit(6)">6</button>
                                        <button type="button" class="preset-badge-btn" onclick="setProductLimit(8)">8</button>
                                        <button type="button" class="preset-badge-btn" onclick="setProductLimit(10)">10</button>
                                        <button type="button" class="preset-badge-btn" onclick="setProductLimit(12)">12</button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-th text-info me-1"></i> Grid Layout (Cards/Row)
                                    </label>
                                    <select class="form-select fw-semibold mb-2" id="product-columns" onchange="syncColumnsField(this.value)">
                                        <option value="1">1 Card / Row (Full Width List)</option>
                                        <option value="2">2 Cards / Row (Standard 2-Col Grid)</option>
                                        <option value="3">3 Cards / Row (Compact 3-Col Grid)</option>
                                        <option value="4">4 Cards / Row (4-Col Grid)</option>
                                    </select>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="text-muted small me-1 align-self-center">Presets:</span>
                                        <button type="button" class="preset-badge-btn" onclick="syncColumnsField(1)">1 Col</button>
                                        <button type="button" class="preset-badge-btn" onclick="syncColumnsField(2)">2 Col</button>
                                        <button type="button" class="preset-badge-btn" onclick="syncColumnsField(3)">3 Col</button>
                                        <button type="button" class="preset-badge-btn" onclick="syncColumnsField(4)">4 Col</button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Badge Text (Top Card Tag)</label>
                                    <input type="text" class="form-control" id="product-badge-text" name="badge_text"
                                        placeholder="e.g. CUSTOMIZABLE, BESTSELLER">
                                    <div class="d-flex gap-1 flex-wrap mt-2">
                                        <span class="text-muted small me-1 align-self-center">Presets:</span>
                                        <button type="button" class="preset-badge-btn" onclick="setBadge('CUSTOMIZABLE')">CUSTOMIZABLE</button>
                                        <button type="button" class="preset-badge-btn" onclick="setBadge('BESTSELLER')">BESTSELLER</button>
                                        <button type="button" class="preset-badge-btn" onclick="setBadge('MIN 30% OFF')">MIN 30% OFF</button>
                                        <button type="button" class="preset-badge-btn" onclick="setBadge('TRENDING')">TRENDING</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Source Strategy Switcher -->
                            <div class="card bg-light border p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-bold text-dark mb-0">
                                        <i class="fas fa-filter text-primary me-1"></i> Product Source Strategy
                                    </label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="source-tab-btn active" id="btn-mode-auto" onclick="setProductSourceMode('auto')">
                                            <i class="fas fa-bolt text-warning me-1"></i> Auto-Filter (By Tag/Category)
                                        </button>
                                        <button type="button" class="source-tab-btn" id="btn-mode-handpicked" onclick="setProductSourceMode('handpicked')">
                                            <i class="fas fa-hand-pointer text-success me-1"></i> Handpicked Products (Curated)
                                        </button>
                                    </div>
                                </div>

                                <!-- Mode A: Auto-Filter Fields -->
                                <div id="source-mode-auto-fields">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Filter Tag / Action</label>
                                            <select class="form-select" id="product-api-action" name="api_action">
                                                <option value="bestseller">Bestseller Products</option>
                                                <option value="top_selection">Top Selection Products</option>
                                                <option value="discount_for_you">Discount For You / Deals</option>
                                                <option value="top_rated">Top Rated Products</option>
                                                <option value="top_deal">Top Deals by Categories</option>
                                                <option value="new_arrivals">New Arrivals</option>
                                                <option value="grouped_categories">Grouped Categories</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Category Filter (Optional)</label>
                                            <select class="form-select" id="product-category-id" name="category_ids">
                                                <option value="">-- All Categories (Default) --</option>
                                                <optgroup label="🌟 Main Categories (Level 1)">
                                                    <?php foreach ($level1Categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>">
                                                            <?php echo htmlspecialchars($cat['name']); ?> (ID: <?php echo $cat['id']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                                <optgroup label="📂 Sub-Parent Categories (Level 2)">
                                                    <?php foreach ($level2Categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>">
                                                            <?php echo htmlspecialchars($cat['name']); ?><?php echo !empty($cat['parent_name']) ? ' &bull; [' . htmlspecialchars($cat['parent_name']) . ']' : ''; ?> (ID: <?php echo $cat['id']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mode B: Handpicked Search & Multi-Select Picker -->
                                <div id="source-mode-handpicked-fields" style="display: none;">
                                    <div class="alert alert-info py-2 px-3 mb-3 small d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-info-circle me-1"></i>
                                            Type product name, SKU or ID to search. Click on a product to add it.
                                        </div>
                                        <div class="fw-bold" id="selectionStatusBadge">
                                            Selected: <span id="selectedCountText" class="text-primary">0</span> / <span id="requiredCountText">8</span>
                                        </div>
                                    </div>

                                    <!-- Search Input with Dropdown -->
                                    <div class="product-search-wrapper mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" class="form-control" id="productPickerSearch"
                                                placeholder="Search product by title, SKU, or ID (e.g. Mug, T-Shirt, 102)..."
                                                autocomplete="off"
                                                oninput="onProductSearchInput(this.value)"
                                                onfocus="onProductSearchFocus()">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearProductSearch()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <!-- Results Dropdown -->
                                        <div id="productSearchResults" class="product-search-dropdown shadow">
                                            <!-- Dynamically populated via AJAX -->
                                        </div>
                                    </div>

                                    <!-- Selected Products Tray -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark">
                                            <i class="fas fa-check-double text-success me-1"></i> Selected Products Tray
                                        </span>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 small" onclick="clearAllSelectedProducts()">
                                            <i class="fas fa-trash me-1"></i> Clear All
                                        </button>
                                    </div>

                                    <div id="selectedProductsContainer" class="selected-prod-grid">
                                        <div class="text-center text-muted p-4 w-100" id="emptySelectedNotice">
                                            <i class="fas fa-box-open fa-2x mb-2 text-muted"></i>
                                            <div>No products selected yet. Search above to handpick products!</div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Background Image URL (Optional)</label>
                                    <input type="text" class="form-control" id="product-bg-image" name="background_image_url"
                                        placeholder="e.g. ./bg/flashsale.png">
                                </div>
                            </div>
                        </div>

                        <!-- ================= BANNER & SLIDER CONFIGURATIONS ================= -->
                        <div id="banner-slider-options" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-images text-success me-2"></i>Banner Grid & Carousel Slots
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-slide-btn" onclick="addNewSlideSlot()">
                                    <i class="fas fa-plus me-1"></i> Add New Slide / Banner Slot
                                </button>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" id="columns-label">Columns Per Row (Layout Grid)</label>
                                    <select class="form-select" id="section-columns" name="columns_per_row">
                                        <option value="1">1 Column (Full Width Single Banner)</option>
                                        <option value="2">2 Columns (Square 1:1 Side-by-Side Banners)</option>
                                        <option value="3">3 Columns (3 Banners Grid)</option>
                                        <option value="4">4 Columns (4 Columns Grid)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info py-2 px-3 mb-0 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        For <strong>2 Square Banners in One Row</strong>, set <strong>2 Columns</strong>. Desktop and Mobile images can be uploaded individually.
                                    </div>
                                </div>
                            </div>

                            <div class="fw-semibold text-muted small mb-2" id="slots-header-text">Banner Slots & Uploaded Images:</div>
                            <div id="slots-container">
                                <!-- Dynamically generated slots -->
                            </div>
                        </div>

                        <!-- ================= CUSTOMER REVIEWS CONFIGURATIONS ================= -->
                        <div id="review-options" style="display: none;">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-star text-warning me-2"></i>Customer Reviews Settings
                            </h6>

                            <!-- Review Limit -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Number of Reviews to Display <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group mb-2">
                                        <input type="number" class="form-control fw-bold fs-5 text-warning" id="review-limit" name="review_limit_input"
                                            value="5" min="1" max="20" oninput="updateRequiredReviewCountDisplay()">
                                        <span class="input-group-text bg-light">Reviews</span>
                                    </div>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="text-muted small me-1 align-self-center">Presets:</span>
                                        <button type="button" class="preset-badge-btn" onclick="setReviewLimit(4)">4</button>
                                        <button type="button" class="preset-badge-btn" onclick="setReviewLimit(5)">5 (Default)</button>
                                        <button type="button" class="preset-badge-btn" onclick="setReviewLimit(6)">6</button>
                                        <button type="button" class="preset-badge-btn" onclick="setReviewLimit(8)">8</button>
                                        <button type="button" class="preset-badge-btn" onclick="setReviewLimit(10)">10</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info py-2 px-3 mb-0 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Mobile view me ek row me <strong>2 customer reviews</strong> side-by-side show hote hain. 4 ya 5-6 reviews recommend kiya jata he.
                                    </div>
                                </div>
                            </div>

                            <!-- Review Source Strategy Switcher -->
                            <div class="card bg-light border p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-bold text-dark mb-0">
                                        <i class="fas fa-filter text-primary me-1"></i> Reviews Source Strategy
                                    </label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="source-tab-btn active" id="btn-review-mode-auto" onclick="setReviewSourceMode('auto')">
                                            <i class="fas fa-bolt text-warning me-1"></i> Top 4-5 Rated (Default Auto)
                                        </button>
                                        <button type="button" class="source-tab-btn" id="btn-review-mode-handpicked" onclick="setReviewSourceMode('handpicked')">
                                            <i class="fas fa-hand-pointer text-success me-1"></i> Select Specific Reviews
                                        </button>
                                    </div>
                                </div>

                                <!-- Mode A: Auto Top Rated Notice -->
                                <div id="review-source-mode-auto-fields">
                                    <div class="alert alert-success py-3 px-3 mb-0">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-check-circle text-success fs-5"></i>
                                            <strong class="text-dark">Top Rated 4-5 Star Approved Reviews Mode (Active)</strong>
                                        </div>
                                        <div class="small text-muted">
                                            Backend automatically highest-rated (5-star & 4-star) verified customer reviews ko fetch karke home page pr display karega.
                                        </div>
                                    </div>
                                </div>

                                <!-- Mode B: Handpicked Search & Multi-Select Picker -->
                                <div id="review-source-mode-handpicked-fields" style="display: none;">
                                    <div class="alert alert-info py-2 px-3 mb-3 small d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-info-circle me-1"></i>
                                            Customer name, review comment ya product search karein aur home page pr dikhane ke liye add karein.
                                        </div>
                                        <div class="fw-bold" id="reviewSelectionStatusBadge">
                                            Selected: <span id="selectedReviewCountText" class="text-primary">0</span> / <span id="requiredReviewCountText">5</span>
                                        </div>
                                    </div>

                                    <!-- Search Input with Dropdown -->
                                    <div class="product-search-wrapper mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" class="form-control" id="reviewPickerSearch"
                                                placeholder="Search review by customer name, rating, product or text (e.g. Ankur, Fast delivery, 5)..."
                                                autocomplete="off"
                                                oninput="onReviewSearchInput(this.value)"
                                                onfocus="onReviewSearchFocus()">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearReviewSearch()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <!-- Results Dropdown -->
                                        <div id="reviewSearchResults" class="product-search-dropdown shadow">
                                            <!-- Dynamically populated via AJAX -->
                                        </div>
                                    </div>

                                    <!-- Selected Reviews Tray -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark">
                                            <i class="fas fa-check-double text-success me-1"></i> Selected Reviews Tray (Custom Sequence)
                                        </span>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 small" onclick="clearAllSelectedReviews()">
                                            <i class="fas fa-trash me-1"></i> Clear All
                                        </button>
                                    </div>

                                    <div id="selectedReviewsContainer" class="selected-prod-grid">
                                        <div class="text-center text-muted p-4 w-100" id="emptySelectedReviewsNotice">
                                            <i class="fas fa-comments fa-2x mb-2 text-muted"></i>
                                            <div>No specific reviews selected yet. Search above to handpick reviews!</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ================= CATEGORY & SUBCATEGORY CONFIGURATIONS ================= -->
                        <div id="category-options" style="display: none;">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-folder-tree text-success me-2"></i>Category &amp; Sub-Category Settings
                            </h6>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-folder-open text-primary me-1"></i> Main Parent Category <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select fw-semibold" id="category-main-select" name="category_ids" onchange="onMainCategoryChange(this.value)">
                                        <option value="">-- Choose Main Category --</option>
                                        <optgroup label="🌟 Main Parent Categories (Level 1)">
                                            <?php foreach ($level1Categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>">
                                                    <?php echo htmlspecialchars($cat['name']); ?> (ID: <?php echo $cat['id']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="📂 Sub-Parent Categories (Level 2)">
                                            <?php foreach ($level2Categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>">
                                                    <?php echo htmlspecialchars($cat['name']); ?><?php echo !empty($cat['parent_name']) ? ' &bull; [' . htmlspecialchars($cat['parent_name']) . ']' : ''; ?> (ID: <?php echo $cat['id']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <div class="form-text text-muted">Is main category ke sabhi subcategories automatically neeche load honge.</div>
                                </div>

                                <div class="col-md-3 col-6">
                                    <label class="form-label fw-semibold">Layout Format</label>
                                    <select class="form-select fw-semibold" id="category-grid-columns" onchange="syncCategoryColumns(this.value)">
                                        <option value="2x2">2x2 Grid (4 Items)</option>
                                        <option value="2x3">2x3 Grid (6 Items)</option>
                                        <option value="2x4">2x4 Grid (8 Items)</option>
                                        <option value="2x6">2x6 Grid (12 Items)</option>
                                        <option value="2x8">2x8 Grid (16 Items)</option>
                                        <option value="3x2">3x2 Grid (6 Items)</option>
                                        <option value="3x3">3x3 Grid (9 Items)</option>
                                        <option value="1x1">1 Row (Circles Slide Strip)</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-6">
                                    <label class="form-label fw-semibold">Offer / Badge Tag</label>
                                    <input type="text" class="form-control" id="category-offer-text" name="badge_text" placeholder="e.g. Min. 30% off">
                                    <div class="d-flex gap-1 flex-wrap mt-1">
                                        <button type="button" class="preset-badge-btn" onclick="setCategoryOffer('Min. 30% off')">Min 30%</button>
                                        <button type="button" class="preset-badge-btn" onclick="setCategoryOffer('Up to 50% off')">Up to 50%</button>
                                        <button type="button" class="preset-badge-btn" onclick="setCategoryOffer('Flat 40% off')">Flat 40%</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Subcategories Multi-Selector -->
                            <div class="card bg-light border p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-dark mb-0">
                                        <i class="fas fa-sitemap text-primary me-1"></i> Select Sub-Categories to Display
                                    </label>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge bg-primary px-2 py-1" id="subcatSelectedCountBadge">Selected: 0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="selectAllSubcategories(true)">Select All</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="selectAllSubcategories(false)">Deselect All</button>
                                    </div>
                                </div>
                                <input type="hidden" id="category-sub-ids" name="sub_category_ids" value="">
                                <div id="subcategoriesContainer" class="subcat-chips-container">
                                    <div class="text-muted small p-3 text-center w-100">
                                        <i class="fas fa-info-circle me-1"></i> Upar Main Category choose karein to sub-categories load honge.
                                    </div>
                                </div>
                            </div>

                            <!-- Background Styling -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Card Background Color Code / Preset</label>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control font-monospace" id="category-bg-color" name="background_image_url" placeholder="#00a8ec or image URL">
                                        <input type="color" class="form-control form-control-color" id="category-color-picker" value="#00a8ec" title="Choose color" onchange="setCategoryBgColor(this.value)">
                                    </div>
                                    <div class="d-flex gap-1 flex-wrap align-items-center">
                                        <span class="text-muted small me-1">Presets:</span>
                                        <button type="button" class="color-preset-btn" style="background:#00a8ec;" onclick="setCategoryBgColor('#00a8ec')" title="Blue"></button>
                                        <button type="button" class="color-preset-btn" style="background:#4ba3b0;" onclick="setCategoryBgColor('#4ba3b0')" title="Teal"></button>
                                        <button type="button" class="color-preset-btn" style="background:#c3bfdb;" onclick="setCategoryBgColor('#c3bfdb')" title="Purple"></button>
                                        <button type="button" class="color-preset-btn" style="background:#668bbd;" onclick="setCategoryBgColor('#668bbd')" title="Slate"></button>
                                        <button type="button" class="color-preset-btn" style="background:#f59e0b;" onclick="setCategoryBgColor('#f59e0b')" title="Amber"></button>
                                        <button type="button" class="color-preset-btn" style="background:#10b981;" onclick="setCategoryBgColor('#10b981')" title="Green"></button>
                                        <button type="button" class="color-preset-btn" style="background:#ffebee;" onclick="setCategoryBgColor('#ffebee')" title="Soft Pink"></button>
                                        <button type="button" class="color-preset-btn" style="background:#f8fafc;" onclick="setCategoryBgColor('#f8fafc')" title="Light Gray"></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info py-2 px-3 mb-0 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>2x2 Grid</strong> me 4 sub-categories and <strong>3x3 Grid</strong> me 9 sub-categories display hote hain. Agar custom sub-categories select nahi karenge to default sub-categories automatically display hongi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= WIDGET CONFIGURATIONS ================= -->
                        <div id="widget-options" style="display: none;">
                            <div class="alert alert-primary py-3 px-3 mb-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-cube text-primary fs-5"></i>
                                    <strong class="text-dark">Dynamic Interactive Widget Section</strong>
                                </div>
                                <div class="small text-muted">
                                    Yeh section homepage pr dynamic widget component ke roop me render hota he. Iska title aur status upar se change kiya ja sakta he.
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer bg-light border-top py-3">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4 fw-semibold" id="saveBtn">
                            <i class="fas fa-save me-1"></i> Save Layout Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container-custom">
        <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fs-6 py-2 px-3" id="toastMessage">
                    Action executed successfully.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        let slideIndexCounter = 0;
        let selectedProducts = [];
        let productSourceMode = 'auto'; // 'auto' or 'handpicked'
        let searchDebounceTimer = null;

        let selectedReviews = [];
        let reviewSourceMode = 'auto'; // 'auto' or 'handpicked'
        let reviewSearchDebounceTimer = null;

        document.addEventListener("DOMContentLoaded", function () {
            // Initialize Sortable for Desktop and Mobile Lists
            const desktopList = document.getElementById('desktop-list');
            const mobileList = document.getElementById('mobile-list');

            if (desktopList) {
                new Sortable(desktopList, {
                    handle: '.drag-handle',
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        saveOrder('desktop');
                        updateOrderBadges('desktop-list');
                    }
                });
            }

            if (mobileList) {
                new Sortable(mobileList, {
                    handle: '.drag-handle',
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        saveOrder('mobile');
                        updateOrderBadges('mobile-list');
                    }
                });
            }

            // Sync page target and persist active tab on tab switch
            const tabElList = document.querySelectorAll('#layoutTabs button[data-bs-toggle="tab"]');
            tabElList.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', event => {
                    const targetId = event.target.id;
                    const pageTarget = targetId.includes('mobile') ? 'mobile' : 'desktop';
                    document.getElementById('section-page-target').value = pageTarget;
                    localStorage.setItem('pm_active_layout_tab', pageTarget);
                    if (window.history && window.history.replaceState) {
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('tab', pageTarget);
                        window.history.replaceState(null, '', nextUrl.toString());
                    }
                    filterSections();
                });
            });

            // Restore active tab from localStorage if not specified in URL
            const urlTab = new URLSearchParams(window.location.search).get('tab');
            if (!urlTab) {
                const savedTab = localStorage.getItem('pm_active_layout_tab');
                if (savedTab === 'mobile') {
                    const mobBtn = document.getElementById('mobile-tab');
                    if (mobBtn && !mobBtn.classList.contains('active')) {
                        const tab = new bootstrap.Tab(mobBtn);
                        tab.show();
                    }
                }
            }

            // Close search dropdowns on outside click
            document.addEventListener('click', function(e) {
                const prodWrapper = document.querySelector('#product-options .product-search-wrapper');
                if (prodWrapper && !prodWrapper.contains(e.target)) {
                    const res = document.getElementById('productSearchResults');
                    if (res) res.style.display = 'none';
                }
                const revWrapper = document.querySelector('#review-options .product-search-wrapper');
                if (revWrapper && !revWrapper.contains(e.target)) {
                    const revRes = document.getElementById('reviewSearchResults');
                    if (revRes) revRes.style.display = 'none';
                }
            });

            // Highlight newly created or duplicated section
            const highlightId = sessionStorage.getItem('pm_highlight_section');
            if (highlightId) {
                sessionStorage.removeItem('pm_highlight_section');
                setTimeout(() => {
                    const item = document.querySelector(`[data-id="${highlightId}"]`);
                    if (item) {
                        item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        item.style.transition = 'all 0.4s ease';
                        item.style.boxShadow = '0 0 0 3px #10b981, 0 8px 24px rgba(16, 185, 129, 0.25)';
                        item.style.borderColor = '#10b981';
                        showToast('Section duplicated and placed right below successfully!');
                        setTimeout(() => {
                            item.style.boxShadow = '';
                            item.style.borderColor = '';
                        }, 3500);
                    }
                }, 300);
            }

            document.body.style.opacity = '1';
        });

        // Show toast notification
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');
            toastEl.className = `toast align-items-center text-white ${isSuccess ? 'bg-success' : 'bg-danger'} border-0 shadow-lg`;
            toastMessage.innerHTML = `<i class="fas ${isSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i> ${message}`;
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        // Live search and filter sections
        function filterSections() {
            const query = (document.getElementById('sectionSearchInput').value || '').toLowerCase().trim();
            const typeFilter = document.getElementById('typeFilterSelect').value;
            const activeTab = document.querySelector('#layoutTabs .nav-link.active').id;
            const listId = activeTab.includes('desktop') ? 'desktop-list' : 'mobile-list';
            const countId = activeTab.includes('desktop') ? 'desktop-count' : 'mobile-count';

            const items = document.querySelectorAll(`#${listId} .sortable-item`);
            let visibleCount = 0;

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                const type = (item.dataset.type || '').toLowerCase();

                let matchesQuery = query === '' || text.includes(query);
                let matchesType = true;

                if (typeFilter === 'product') {
                    matchesType = ['product_carousel', 'mobile_list', 'compact_product_grid', 'featured_grid'].includes(type);
                } else if (typeFilter === 'banner') {
                    matchesType = ['banner', 'slider', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel'].includes(type);
                } else if (typeFilter === 'category') {
                    matchesType = ['compact_grid', 'compact_category_grid', 'category_mosaic', 'category_slide_row', 'category_strip'].includes(type);
                } else if (typeFilter === 'reviews') {
                    matchesType = ['customer_reviews', 'reviews', 'customer_review_carousel'].includes(type);
                } else if (typeFilter === 'widget') {
                    matchesType = ['gift_finder', 'bulk_widget', 'brand_directory', 'why_us', 'footer'].includes(type);
                }

                if (matchesQuery && matchesType) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById(countId).textContent = visibleCount;
        }

        function clearSearch() {
            document.getElementById('sectionSearchInput').value = '';
            document.getElementById('typeFilterSelect').value = '';
            filterSections();
        }

        function updateOrderBadges(listId) {
            const items = document.querySelectorAll(`#${listId} .sortable-item`);
            items.forEach((item, index) => {
                const badge = item.querySelector('.order-badge');
                if (badge) {
                    badge.textContent = `#${index + 1}`;
                }
            });
        }

        function saveOrder(target) {
            const listId = target === 'desktop' ? 'desktop-list' : 'mobile-list';
            const ids = Array.from(document.querySelectorAll(`#${listId} [data-id]`)).map(el => el.dataset.id);

            fetch('api/home-layout-api.php?action=reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Section order reordered successfully!');
                } else {
                    showToast(data.error || 'Failed to reorder sections', false);
                }
            })
            .catch(err => {
                showToast('Network error while reordering', false);
            });
        }

        function toggleSection(id, checked) {
            const status = checked ? 'active' : 'inactive';
            fetch('api/home-layout-api.php?action=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(`Section is now ${status}`);
                } else {
                    showToast(data.error || 'Failed to toggle status', false);
                }
            })
            .catch(err => showToast('Error toggling status', false));
        }

        function duplicateSection(id) {
            if (confirm('Duplicate this section structure?')) {
                fetch('api/home-layout-api.php?action=duplicate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const activeTab = document.querySelector('#layoutTabs button.active')?.id || 'desktop-tab';
                        const target = data.page_target || (activeTab.includes('mobile') ? 'mobile' : 'desktop');
                        localStorage.setItem('pm_active_layout_tab', target);
                        if (data.id) {
                            sessionStorage.setItem('pm_highlight_section', data.id);
                        }
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('tab', target);
                        window.location.href = nextUrl.toString();
                    } else {
                        showToast(data.error || 'Failed to duplicate section', false);
                    }
                })
                .catch(err => showToast('Error duplicating section', false));
            }
        }

        function deleteSection(id) {
            if (confirm('Are you sure you want to delete this section? All associated layout configs will be removed.')) {
                fetch('api/home-layout-api.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const activeTab = document.querySelector('#layoutTabs button.active')?.id || 'desktop-tab';
                        const target = activeTab.includes('mobile') ? 'mobile' : 'desktop';
                        localStorage.setItem('pm_active_layout_tab', target);
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('tab', target);
                        window.location.href = nextUrl.toString();
                    } else {
                        showToast(data.error || 'Failed to delete section', false);
                    }
                })
                .catch(err => showToast('Error deleting section', false));
            }
        }

        function handleTypeChange(type) {
            const isBannerOrSlider = ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel'].includes(type);
            const isProduct = ['product_carousel', 'compact_product_grid', 'mobile_list', 'featured_grid'].includes(type);
            const isCategory = ['compact_grid', 'compact_category_grid', 'category_slide_row', 'category_mosaic'].includes(type);
            const isReview = ['customer_reviews', 'reviews', 'customer_review_carousel'].includes(type);
            const isWidget = ['gift_finder', 'bulk_widget', 'brand_directory', 'why_us', 'footer'].includes(type);

            const bannerOpts = document.getElementById('banner-slider-options');
            if (bannerOpts) bannerOpts.style.display = isBannerOrSlider ? 'block' : 'none';

            const productOpts = document.getElementById('product-options');
            if (productOpts) productOpts.style.display = isProduct ? 'block' : 'none';

            const catOpts = document.getElementById('category-options');
            if (catOpts) catOpts.style.display = isCategory ? 'block' : 'none';

            const reviewOpts = document.getElementById('review-options');
            if (reviewOpts) reviewOpts.style.display = isReview ? 'block' : 'none';

            const widgetOpts = document.getElementById('widget-options');
            if (widgetOpts) widgetOpts.style.display = isWidget ? 'block' : 'none';

            const addSlideBtn = document.getElementById('add-slide-btn');
            const columnsLabel = document.getElementById('columns-label');
            const slotsHeaderText = document.getElementById('slots-header-text');

            if (type === 'slider' || type === 'one_and_half_carousel' || type === 'peeking_slider' || type === 'small_banner_carousel' || type === 'small_banner_slider' || type === 'small_height_carousel') {
                if (addSlideBtn) addSlideBtn.style.display = 'inline-block';
                if (columnsLabel) columnsLabel.textContent = 'Visible Slides / Layout';
                if (slotsHeaderText) slotsHeaderText.textContent = 'Upload Carousel / Slider Slide Images:';
            } else if (type === 'banner') {
                if (addSlideBtn) addSlideBtn.style.display = 'none';
                if (columnsLabel) columnsLabel.textContent = 'Grid Columns Per Row';
                if (slotsHeaderText) slotsHeaderText.textContent = 'Upload Banner Images (Equal to Column count):';

                const cols = document.getElementById('section-columns').value;
                const currentSlots = document.querySelectorAll('#slots-container .slot-card').length;
                if (currentSlots === 0) {
                    generateSlotsForBanner(cols);
                }
            } else if (isCategory) {
                const currentMainCat = document.getElementById('category-main-select')?.value;
                if (currentMainCat) {
                    loadSubcategories(currentMainCat, document.getElementById('category-sub-ids')?.value || '');
                }
            }
        }

        document.getElementById('section-columns').addEventListener('change', function () {
            const type = document.getElementById('section-type').value;
            if (type === 'banner') {
                generateSlotsForBanner(this.value);
            }
        });

        function generateSlotsForBanner(count) {
            const pageTarget = document.getElementById('section-page-target')?.value || 'desktop';
            const container = document.getElementById('slots-container');
            container.innerHTML = '';
            slideIndexCounter = 0;

            for (let i = 0; i < count; i++) {
                appendSlotHTML(i, `Banner Image #${i + 1}`, false, false, {}, pageTarget);
            }
            slideIndexCounter = count;
        }

        function generateSlotsForSlider(count) {
            const pageTarget = document.getElementById('section-page-target')?.value || 'desktop';
            const container = document.getElementById('slots-container');
            container.innerHTML = '';
            slideIndexCounter = 0;

            for (let i = 0; i < count; i++) {
                appendSlotHTML(i, `Slide #${i + 1}`, false, true, {}, pageTarget);
            }
            slideIndexCounter = count;
        }

        function addNewSlideSlot() {
            const pageTarget = document.getElementById('section-page-target')?.value || 'desktop';
            appendSlotHTML(slideIndexCounter, `Slide #${slideIndexCounter + 1}`, false, true, {}, pageTarget);
            slideIndexCounter++;
        }

        function removeSlideSlot(elementId) {
            const slotEl = document.getElementById(elementId);
            if (slotEl) slotEl.remove();
        }

        function appendSlotHTML(index, labelText, isRequired, isDeletable, banner = {}, pageTarget = 'desktop') {
            const container = document.getElementById('slots-container');
            const elementId = `slot-card-${index}`;

            let deleteBtnHtml = '';
            if (isDeletable) {
                deleteBtnHtml = `
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeSlideSlot('${elementId}')">
                        <i class="fas fa-times me-1"></i> Remove
                    </button>
                `;
            }

            const existingDesktop = banner.image_url_desktop || banner.large || '';
            const existingMobile = banner.image_url_mobile || banner.small || '';
            const currentImg = (pageTarget === 'mobile') ? (existingMobile || existingDesktop) : (existingDesktop || existingMobile);

            const previewImg = currentImg 
                ? `<img src="${currentImg}" class="preview-img-thumbnail ms-2" id="${elementId}-preview-img" alt="preview">` 
                : `<img src="" class="preview-img-thumbnail ms-2 d-none" id="${elementId}-preview-img" alt="preview">`;

            let imageUploadInputs = '';
            if (pageTarget === 'mobile') {
                imageUploadInputs = `
                    <input type="hidden" name="existing_mobile[${index}]" value="${existingMobile || existingDesktop}">
                    <input type="hidden" name="existing_desktop[${index}]" value="${existingDesktop || existingMobile}">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">
                            <i class="fas fa-mobile-alt text-primary me-1"></i> Upload Mobile Banner Image
                            ${currentImg ? '<span class="badge bg-success ms-1">Saved</span>' : ''}
                        </label>
                        <input type="file" class="form-control form-control-sm" name="images_mobile[${index}]" accept="image/*" onchange="previewSlotFile(this, '${elementId}-preview-img')">
                    </div>
                `;
            } else {
                imageUploadInputs = `
                    <input type="hidden" name="existing_desktop[${index}]" value="${existingDesktop}">
                    <input type="hidden" name="existing_mobile[${index}]" value="${existingMobile}">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            <i class="fas fa-desktop text-primary me-1"></i> Upload Desktop Image
                            ${existingDesktop ? '<span class="badge bg-success ms-1">Saved</span>' : ''}
                        </label>
                        <input type="file" class="form-control form-control-sm" name="images_desktop[${index}]" accept="image/*" onchange="previewSlotFile(this, '${elementId}-preview-img')">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            <i class="fas fa-mobile-alt text-secondary me-1"></i> Upload Mobile Image (Optional)
                            ${existingMobile ? '<span class="badge bg-info ms-1">Saved</span>' : ''}
                        </label>
                        <input type="file" class="form-control form-control-sm" name="images_mobile[${index}]" accept="image/*">
                    </div>
                `;
            }

            const slotHtml = `
                <div class="slot-card shadow-sm border p-3 mb-2 rounded bg-light" id="${elementId}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-dark d-flex align-items-center">
                            ${labelText}
                            ${previewImg}
                        </div>
                        ${deleteBtnHtml}
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Title / Caption</label>
                            <input type="text" class="form-control form-control-sm" name="banner_titles[${index}]" value="${banner.title || ''}" placeholder="Banner Title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target URL / Redirect Link</label>
                            <input type="text" class="form-control form-control-sm" name="banner_targets[${index}]" value="${banner.target_url || banner.target || ''}" placeholder="e.g. /allproducts?category=gifts">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        ${imageUploadInputs}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', slotHtml);
        }

        function previewSlotFile(input, imgElementId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(imgElementId);
                    if (img) {
                        img.src = e.target.result;
                        img.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ================= PRODUCT PICKER & SOURCE MANAGEMENT =================
        function setProductSourceMode(mode) {
            productSourceMode = mode;
            const btnAuto = document.getElementById('btn-mode-auto');
            const btnHandpicked = document.getElementById('btn-mode-handpicked');
            const fieldsAuto = document.getElementById('source-mode-auto-fields');
            const fieldsHandpicked = document.getElementById('source-mode-handpicked-fields');

            if (mode === 'handpicked') {
                btnHandpicked.classList.add('active');
                btnAuto.classList.remove('active');
                fieldsHandpicked.style.display = 'block';
                fieldsAuto.style.display = 'none';
                updateRequiredCountDisplay();
            } else {
                btnAuto.classList.add('active');
                btnHandpicked.classList.remove('active');
                fieldsAuto.style.display = 'block';
                fieldsHandpicked.style.display = 'none';
            }
        }

        function setProductLimit(limit) {
            document.getElementById('product-limit').value = limit;
            updateRequiredCountDisplay();
        }

        function setBadge(text) {
            document.getElementById('product-badge-text').value = text;
        }

        function updateRequiredCountDisplay() {
            const limit = parseInt(document.getElementById('product-limit').value) || 0;
            const reqEl = document.getElementById('requiredCountText');
            if (reqEl) reqEl.textContent = limit;
            
            const selEl = document.getElementById('selectedCountText');
            if (selEl) selEl.textContent = selectedProducts.length;

            const badge = document.getElementById('selectionStatusBadge');
            if (badge) {
                if (selectedProducts.length >= limit && limit > 0) {
                    badge.className = 'fw-bold text-success';
                } else {
                    badge.className = 'fw-bold text-danger';
                }
            }
        }

        function onProductSearchFocus() {
            const val = document.getElementById('productPickerSearch').value;
            fetchProductSearchResults(val);
        }

        function onProductSearchInput(val) {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                fetchProductSearchResults(val);
            }, 250);
        }

        function clearProductSearch() {
            document.getElementById('productPickerSearch').value = '';
            document.getElementById('productSearchResults').style.display = 'none';
        }

        function fetchProductSearchResults(query) {
            const resContainer = document.getElementById('productSearchResults');
            resContainer.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Searching products...</div>';
            resContainer.style.display = 'block';

            fetch(`api/home-layout-api.php?action=search_products&q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        renderProductSearchResults(data.data);
                    } else {
                        resContainer.innerHTML = '<div class="p-3 text-center text-muted small">No products found matching query.</div>';
                    }
                })
                .catch(err => {
                    resContainer.innerHTML = '<div class="p-3 text-center text-danger small">Error searching products.</div>';
                });
        }

        function renderProductSearchResults(products) {
            const resContainer = document.getElementById('productSearchResults');
            resContainer.innerHTML = '';

            products.forEach(p => {
                const isSelected = selectedProducts.some(sp => sp.id == p.id);
                const item = document.createElement('div');
                item.className = 'product-search-item';
                
                const thumb = p.thumbnail_image || 'img/icons/icon-48x48.png';
                const price = p.offer_price || p.price || '0.00';
                
                item.innerHTML = `
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <img src="${thumb}" class="prod-thumb-mini" alt="${p.name}">
                        <div class="min-w-0">
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 320px;">${p.name}</div>
                            <div class="small text-muted">ID: #${p.id} | SKU: ${p.sku || 'N/A'} | ₹${price}</div>
                        </div>
                    </div>
                    <div>
                        ${isSelected ? 
                            '<button type="button" class="btn btn-sm btn-success py-0 px-2" disabled><i class="fas fa-check"></i> Added</button>' : 
                            `<button type="button" class="btn btn-sm btn-primary py-0 px-2" onclick='addProductToSelection(${JSON.stringify(p)})'><i class="fas fa-plus"></i> Add</button>`
                        }
                    </div>
                `;
                resContainer.appendChild(item);
            });
        }

        function addProductToSelection(p) {
            if (!selectedProducts.some(sp => sp.id == p.id)) {
                selectedProducts.push(p);
                renderSelectedProductsTray();
                syncProductIdsHidden();
                fetchProductSearchResults(document.getElementById('productPickerSearch').value);
                showToast(`Added: ${p.name}`);
            }
        }

        function removeSelectedProduct(id) {
            selectedProducts = selectedProducts.filter(sp => sp.id != id);
            renderSelectedProductsTray();
            syncProductIdsHidden();
            fetchProductSearchResults(document.getElementById('productPickerSearch').value);
        }

        function clearAllSelectedProducts() {
            if (confirm('Clear all handpicked products from this section?')) {
                selectedProducts = [];
                renderSelectedProductsTray();
                syncProductIdsHidden();
            }
        }

        function moveSelectedProduct(index, direction) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < selectedProducts.length) {
                const temp = selectedProducts[index];
                selectedProducts[index] = selectedProducts[newIndex];
                selectedProducts[newIndex] = temp;
                renderSelectedProductsTray();
                syncProductIdsHidden();
            }
        }

        function renderSelectedProductsTray() {
            const container = document.getElementById('selectedProductsContainer');
            container.innerHTML = '';

            if (selectedProducts.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted p-4 w-100" id="emptySelectedNotice">
                        <i class="fas fa-box-open fa-2x mb-2 text-muted"></i>
                        <div>No products selected yet. Search above to handpick products!</div>
                    </div>
                `;
                updateRequiredCountDisplay();
                return;
            }

            selectedProducts.forEach((p, idx) => {
                const thumb = p.thumbnail_image || p.image || 'img/icons/icon-48x48.png';
                const price = p.offer_price || p.selling_price || p.price || '0.00';
                const card = document.createElement('div');
                card.className = 'selected-prod-item';

                const canMoveLeft = idx > 0;
                const canMoveRight = idx < selectedProducts.length - 1;

                card.innerHTML = `
                    <span class="badge bg-primary position-absolute top-0 start-0 m-1" style="font-size: 0.65rem;">#${idx + 1}</span>
                    <img src="${thumb}" class="prod-thumb" alt="${p.name}">
                    <div class="prod-info">
                        <div class="prod-name" title="${p.name}">${p.name}</div>
                        <div class="prod-meta">ID: #${p.id} | ₹${price}</div>
                    </div>
                    <div class="prod-actions">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-light py-0 px-1" title="Move earlier" onclick="moveSelectedProduct(${idx}, -1)" ${canMoveLeft ? '' : 'disabled'}>
                                <i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i>
                            </button>
                            <button type="button" class="btn btn-light py-0 px-1" title="Move later" onclick="moveSelectedProduct(${idx}, 1)" ${canMoveRight ? '' : 'disabled'}>
                                <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 mt-1" title="Remove" onclick="removeSelectedProduct(${p.id})">
                            <i class="fas fa-times" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });

            updateRequiredCountDisplay();
        }

        function syncProductIdsHidden() {
            const hidden = document.getElementById('product-specific-ids');
            const secType = document.getElementById('section-type').value;
            const isReview = ['customer_reviews', 'reviews', 'customer_review_carousel'].includes(secType);

            if (isReview) {
                if (reviewSourceMode === 'handpicked') {
                    hidden.value = selectedReviews.map(r => r.id).join(',');
                } else {
                    hidden.value = '';
                }
            } else {
                if (productSourceMode === 'handpicked') {
                    hidden.value = selectedProducts.map(p => p.id).join(',');
                } else {
                    hidden.value = '';
                }
            }
        }

        // ================= REVIEW PICKER & SOURCE MANAGEMENT =================
        function setReviewSourceMode(mode) {
            reviewSourceMode = mode;
            const btnAuto = document.getElementById('btn-review-mode-auto');
            const btnHandpicked = document.getElementById('btn-review-mode-handpicked');
            const fieldsAuto = document.getElementById('review-source-mode-auto-fields');
            const fieldsHandpicked = document.getElementById('review-source-mode-handpicked-fields');

            if (mode === 'handpicked') {
                btnHandpicked.classList.add('active');
                btnAuto.classList.remove('active');
                fieldsHandpicked.style.display = 'block';
                fieldsAuto.style.display = 'none';
                updateRequiredReviewCountDisplay();
            } else {
                btnAuto.classList.add('active');
                btnHandpicked.classList.remove('active');
                fieldsAuto.style.display = 'block';
                fieldsHandpicked.style.display = 'none';
            }
            syncProductIdsHidden();
        }

        function setReviewLimit(limit) {
            document.getElementById('review-limit').value = limit;
            document.getElementById('product-limit').value = limit;
            updateRequiredReviewCountDisplay();
        }

        function updateRequiredReviewCountDisplay() {
            const limit = parseInt(document.getElementById('review-limit').value) || 0;
            const reqEl = document.getElementById('requiredReviewCountText');
            if (reqEl) reqEl.textContent = limit;
            
            const selEl = document.getElementById('selectedReviewCountText');
            if (selEl) selEl.textContent = selectedReviews.length;

            const badge = document.getElementById('reviewSelectionStatusBadge');
            if (badge) {
                if (selectedReviews.length >= limit && limit > 0) {
                    badge.className = 'fw-bold text-success';
                } else {
                    badge.className = 'fw-bold text-danger';
                }
            }
        }

        function onReviewSearchFocus() {
            const val = document.getElementById('reviewPickerSearch').value;
            fetchReviewSearchResults(val);
        }

        function onReviewSearchInput(val) {
            clearTimeout(reviewSearchDebounceTimer);
            reviewSearchDebounceTimer = setTimeout(() => {
                fetchReviewSearchResults(val);
            }, 250);
        }

        function clearReviewSearch() {
            document.getElementById('reviewPickerSearch').value = '';
            document.getElementById('reviewSearchResults').style.display = 'none';
        }

        function fetchReviewSearchResults(query) {
            const resContainer = document.getElementById('reviewSearchResults');
            resContainer.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Searching reviews...</div>';
            resContainer.style.display = 'block';

            fetch(`api/home-layout-api.php?action=search_reviews&q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        renderReviewSearchResults(data.data);
                    } else {
                        resContainer.innerHTML = '<div class="p-3 text-center text-muted small">No reviews found matching query.</div>';
                    }
                })
                .catch(err => {
                    resContainer.innerHTML = '<div class="p-3 text-center text-danger small">Error searching reviews.</div>';
                });
        }

        function renderReviewSearchResults(reviews) {
            const resContainer = document.getElementById('reviewSearchResults');
            resContainer.innerHTML = '';

            reviews.forEach(r => {
                const isSelected = selectedReviews.some(sr => sr.id == r.id);
                const item = document.createElement('div');
                item.className = 'product-search-item';
                
                const ratingStars = '★'.repeat(Math.round(r.rating || 5)) + '☆'.repeat(5 - Math.round(r.rating || 5));
                const commentPreview = (r.comment || r.title || '').replace(/"/g, '&quot;');
                const prodName = r.product_name ? `<span class="badge bg-light text-dark border ms-1">${r.product_name}</span>` : '';
                
                item.innerHTML = `
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width:36px; height:36px; font-size: 0.8rem; flex-shrink: 0;">
                            ${(r.customer_name || 'C').substring(0, 2).toUpperCase()}
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 320px;">
                                ${r.customer_name} <span class="text-warning small">${ratingStars}</span> ${prodName}
                            </div>
                            <div class="small text-muted text-truncate" style="max-width: 320px;">
                                "${commentPreview}" ${r.city ? `• ${r.city}` : ''}
                            </div>
                        </div>
                    </div>
                    <div>
                        ${isSelected ? 
                            '<button type="button" class="btn btn-sm btn-success py-0 px-2" disabled><i class="fas fa-check"></i> Added</button>' : 
                            `<button type="button" class="btn btn-sm btn-warning py-0 px-2 text-dark fw-semibold" onclick='addReviewToSelection(${JSON.stringify(r).replace(/'/g, "&#39;")})'><i class="fas fa-plus"></i> Add</button>`
                        }
                    </div>
                `;
                resContainer.appendChild(item);
            });
        }

        function addReviewToSelection(r) {
            if (!selectedReviews.some(sr => sr.id == r.id)) {
                selectedReviews.push(r);
                renderSelectedReviewsTray();
                syncProductIdsHidden();
                fetchReviewSearchResults(document.getElementById('reviewPickerSearch').value);
                showToast(`Added review from: ${r.customer_name}`);
            }
        }

        function removeSelectedReview(id) {
            selectedReviews = selectedReviews.filter(sr => sr.id != id);
            renderSelectedReviewsTray();
            syncProductIdsHidden();
            fetchReviewSearchResults(document.getElementById('reviewPickerSearch').value);
        }

        function clearAllSelectedReviews() {
            if (confirm('Clear all handpicked reviews from this section?')) {
                selectedReviews = [];
                renderSelectedReviewsTray();
                syncProductIdsHidden();
            }
        }

        function moveSelectedReview(index, direction) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < selectedReviews.length) {
                const temp = selectedReviews[index];
                selectedReviews[index] = selectedReviews[newIndex];
                selectedReviews[newIndex] = temp;
                renderSelectedReviewsTray();
                syncProductIdsHidden();
            }
        }

        function renderSelectedReviewsTray() {
            const container = document.getElementById('selectedReviewsContainer');
            container.innerHTML = '';

            if (selectedReviews.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted p-4 w-100" id="emptySelectedReviewsNotice">
                        <i class="fas fa-comments fa-2x mb-2 text-muted"></i>
                        <div>No specific reviews selected yet. Search above to handpick reviews!</div>
                    </div>
                `;
                updateRequiredReviewCountDisplay();
                return;
            }

            selectedReviews.forEach((r, idx) => {
                const card = document.createElement('div');
                card.className = 'selected-prod-item';

                const canMoveLeft = idx > 0;
                const canMoveRight = idx < selectedReviews.length - 1;
                const ratingStars = '★'.repeat(Math.round(r.rating || 5));

                card.innerHTML = `
                    <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-1 fw-bold" style="font-size: 0.65rem;">#${idx + 1}</span>
                    <div class="rounded-circle bg-warning bg-opacity-25 text-dark fw-bold d-flex align-items-center justify-content-center" style="width:36px; height:36px; font-size: 0.75rem; flex-shrink: 0;">
                        ${(r.customer_name || 'C').substring(0, 2).toUpperCase()}
                    </div>
                    <div class="prod-info">
                        <div class="prod-name" title="${r.customer_name}">
                            ${r.customer_name} <span class="text-warning small">${ratingStars}</span>
                        </div>
                        <div class="prod-meta text-truncate" title="${r.comment || ''}">
                            "${r.comment || r.title || ''}"
                        </div>
                    </div>
                    <div class="prod-actions">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-light py-0 px-1" title="Move earlier" onclick="moveSelectedReview(${idx}, -1)" ${canMoveLeft ? '' : 'disabled'}>
                                <i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i>
                            </button>
                            <button type="button" class="btn btn-light py-0 px-1" title="Move later" onclick="moveSelectedReview(${idx}, 1)" ${canMoveRight ? '' : 'disabled'}>
                                <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 mt-1" title="Remove" onclick="removeSelectedReview(${r.id})">
                            <i class="fas fa-times" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });

            updateRequiredReviewCountDisplay();
        }

        // ================= CATEGORY & SUBCATEGORY HELPERS =================
        let currentSubcategories = [];

        function onMainCategoryChange(parentId) {
            loadSubcategories(parentId, '');
        }

        function loadSubcategories(parentId, preselectedIdsStr = '') {
            const container = document.getElementById('subcategoriesContainer');
            if (!parentId) {
                container.innerHTML = '<div class="text-muted small p-3 text-center w-100"><i class="fas fa-info-circle me-1"></i> Upar Main Category choose karein to sub-categories load honge.</div>';
                document.getElementById('category-sub-ids').value = '';
                updateSubcatCountBadge();
                return;
            }

            container.innerHTML = '<div class="text-muted small p-3 text-center w-100"><i class="fas fa-spinner fa-spin me-1"></i> Sub-categories loading...</div>';

            fetch(`api/home-layout-api.php?action=get_subcategories&parent_id=${encodeURIComponent(parentId)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        currentSubcategories = data.data;
                        renderSubcategoryChips(currentSubcategories, preselectedIdsStr);
                    } else {
                        container.innerHTML = '<div class="text-muted small p-3 text-center w-100">No sub-categories found for this parent category.</div>';
                        document.getElementById('category-sub-ids').value = '';
                        updateSubcatCountBadge();
                    }
                })
                .catch(err => {
                    container.innerHTML = '<div class="text-danger small p-3 text-center w-100">Error loading sub-categories.</div>';
                });
        }

        function renderSubcategoryChips(subcats, preselectedIdsStr) {
            const container = document.getElementById('subcategoriesContainer');
            container.innerHTML = '';

            const selectedIds = preselectedIdsStr ? preselectedIdsStr.split(',').map(s => s.trim()) : [];

            subcats.forEach(sub => {
                const isChecked = selectedIds.includes(String(sub.id));
                const chip = document.createElement('label');
                chip.className = `subcat-chip ${isChecked ? 'selected' : ''}`;
                chip.innerHTML = `
                    <input type="checkbox" value="${sub.id}" ${isChecked ? 'checked' : ''} onchange="toggleSubcatChip(this)">
                    <i class="fas ${isChecked ? 'fa-check-circle' : 'fa-circle-notch'}"></i>
                    <span>${sub.name}</span>
                `;
                container.appendChild(chip);
            });

            syncSubcatHidden();
        }

        function toggleSubcatChip(checkbox) {
            const chip = checkbox.closest('.subcat-chip');
            if (checkbox.checked) {
                chip.classList.add('selected');
                chip.querySelector('i').className = 'fas fa-check-circle';
            } else {
                chip.classList.remove('selected');
                chip.querySelector('i').className = 'fas fa-circle-notch';
            }
            syncSubcatHidden();
        }

        function selectAllSubcategories(select) {
            const checkboxes = document.querySelectorAll('#subcategoriesContainer input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = select;
                const chip = cb.closest('.subcat-chip');
                if (select) {
                    chip.classList.add('selected');
                    chip.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    chip.classList.remove('selected');
                    chip.querySelector('i').className = 'fas fa-circle-notch';
                }
            });
            syncSubcatHidden();
        }

        function syncSubcatHidden() {
            const checked = Array.from(document.querySelectorAll('#subcategoriesContainer input[type="checkbox"]:checked')).map(cb => cb.value);
            document.getElementById('category-sub-ids').value = checked.join(',');
            updateSubcatCountBadge();
        }

        function updateSubcatCountBadge() {
            const count = document.querySelectorAll('#subcategoriesContainer input[type="checkbox"]:checked').length;
            const badge = document.getElementById('subcatSelectedCountBadge');
            if (badge) {
                badge.textContent = `Selected: ${count}`;
                badge.className = count > 0 ? 'badge bg-success px-2 py-1' : 'badge bg-secondary px-2 py-1';
            }
        }

        function syncCategoryColumns(format) {
            let cols = 2;
            let limit = 4;
            if (format === '2x2') { cols = 2; limit = 4; }
            else if (format === '2x3') { cols = 2; limit = 6; }
            else if (format === '2x4') { cols = 2; limit = 8; }
            else if (format === '2x6') { cols = 2; limit = 12; }
            else if (format === '2x8') { cols = 2; limit = 16; }
            else if (format === '3x2') { cols = 3; limit = 6; }
            else if (format === '3x3') { cols = 3; limit = 9; }
            else if (format === '1x1') { cols = 1; limit = 8; }
            else {
                cols = parseInt(format) || 2;
                limit = (cols === 3) ? 9 : 4;
            }
            document.getElementById('section-columns').value = cols;
            document.getElementById('product-limit').value = limit;
        }

        function setCategoryOffer(text) {
            document.getElementById('category-offer-text').value = text;
            document.getElementById('product-badge-text').value = text;
        }

        function setCategoryBgColor(color) {
            document.getElementById('category-bg-color').value = color;
            document.getElementById('category-color-picker').value = color.startsWith('#') ? color : '#00a8ec';
            document.getElementById('product-bg-image').value = color;
        }

        function copyKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                showToast(`Copied key: ${key}`);
            });
        }

        function openPresetModal(type, defaultLabel, defaultCols = 1, defaultLimit = null) {
            openCreateModal();
            document.getElementById('section-type').value = type;
            document.getElementById('section-label').value = defaultLabel;
            document.getElementById('section-columns').value = defaultCols;

            if (['compact_grid', 'compact_category_grid'].includes(type)) {
                const catCols = document.getElementById('category-grid-columns');
                let catFormat = '2x2';
                if (defaultCols === 2 && defaultLimit === 8) catFormat = '2x4';
                else if (defaultCols === 2 && defaultLimit === 12) catFormat = '2x6';
                else if (defaultCols === 2 && defaultLimit === 16) catFormat = '2x8';
                else if (defaultCols === 3 && defaultLimit === 9) catFormat = '3x3';
                else if (defaultCols === 1) catFormat = '1x1';
                else if (defaultCols === 2) catFormat = '2x2';

                if (catCols) catCols.value = catFormat;
                syncCategoryColumns(catFormat);
                document.getElementById('category-offer-text').value = 'Min. 30% off';
                document.getElementById('category-bg-color').value = '#00a8ec';
                document.getElementById('category-color-picker').value = '#00a8ec';

                // Preselect first main category if available
                const mainSelect = document.getElementById('category-main-select');
                if (mainSelect && mainSelect.options.length > 1) {
                    mainSelect.selectedIndex = 1;
                    loadSubcategories(mainSelect.value, '');
                }
            } else if (type === 'compact_product_grid') {
                document.getElementById('product-limit').value = defaultLimit || 16;
                document.getElementById('product-badge-text').value = 'BESTSELLER';
            } else if (type === 'product_carousel') {
                document.getElementById('product-limit').value = defaultLimit || 8;
                document.getElementById('product-badge-text').value = 'CUSTOMIZABLE';
            }

            handleTypeChange(type);
            if (type === 'banner') {
                generateSlotsForBanner(defaultCols);
            } else if (['slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel'].includes(type)) {
                generateSlotsForSlider(Math.max(2, defaultCols));
            }
        }

        function syncColumnsField(val) {
            const num = parseInt(val) || 1;
            document.getElementById('section-columns').value = num;
            const prodCols = document.getElementById('product-columns');
            if (prodCols) prodCols.value = num;

            const type = document.getElementById('section-type').value;
            if (type === 'banner') {
                generateSlotsForBanner(num);
            }
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus text-primary me-2"></i>Add New Section';
            document.getElementById('section-id').value = '';
            document.getElementById('section-label').value = '';
            document.getElementById('section-key').value = '';
            document.getElementById('section-key').readOnly = false;
            document.getElementById('section-type').value = 'product_carousel';
            document.getElementById('section-status').value = 'active';

            syncColumnsField(2);

            const activeTab = document.querySelector('#layoutTabs .nav-link.active').id;
            document.getElementById('section-page-target').value = activeTab.includes('desktop') ? 'desktop' : 'mobile';

            selectedProducts = [];
            setProductSourceMode('auto');
            renderSelectedProductsTray();

            selectedReviews = [];
            setReviewSourceMode('auto');
            renderSelectedReviewsTray();

            // Clear category fields
            document.getElementById('category-main-select').value = '';
            document.getElementById('category-sub-ids').value = '';
            document.getElementById('subcategoriesContainer').innerHTML = '<div class="text-muted small p-3 text-center w-100"><i class="fas fa-info-circle me-1"></i> Upar Main Category choose karein to sub-categories load honge.</div>';
            updateSubcatCountBadge();
            document.getElementById('category-bg-color').value = '#00a8ec';
            document.getElementById('category-color-picker').value = '#00a8ec';
            document.getElementById('category-offer-text').value = 'Min. 30% off';

            syncProductIdsHidden();

            handleTypeChange('product_carousel');
            document.getElementById('slots-container').innerHTML = '';

            const modal = new bootstrap.Modal(document.getElementById('sectionModal'));
            modal.show();
        }

        function openEditModal(sec) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-primary me-2"></i>Edit Section Configurations';
            document.getElementById('section-id').value = sec.id;
            document.getElementById('section-page-target').value = sec.page_target;
            document.getElementById('section-label').value = sec.label;
            document.getElementById('section-key').value = sec.section_key;
            document.getElementById('section-type').value = sec.section_type;
            document.getElementById('section-status').value = sec.status || 'active';

            const cols = parseInt(sec.columns_per_row) || (['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider'].includes(sec.section_type) ? 1 : 2);
            syncColumnsField(cols);

            handleTypeChange(sec.section_type);

            if (['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel'].includes(sec.section_type)) {
                generateSlotsForEdit(sec.banners || []);
            } else if (['customer_reviews', 'reviews', 'customer_review_carousel'].includes(sec.section_type)) {
                const limit = sec.product_limit || 5;
                document.getElementById('review-limit').value = limit;
                document.getElementById('product-limit').value = limit;

                selectedReviews = [];

                if (sec.product_ids && sec.product_ids.trim() !== '') {
                    setReviewSourceMode('handpicked');
                    // Fetch reviews details
                    fetch(`api/home-layout-api.php?action=get_reviews_by_ids&ids=${encodeURIComponent(sec.product_ids.trim())}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && Array.isArray(data.data)) {
                                selectedReviews = data.data;
                                renderSelectedReviewsTray();
                                syncProductIdsHidden();
                            }
                        })
                        .catch(() => {
                            renderSelectedReviewsTray();
                        });
                } else {
                    setReviewSourceMode('auto');
                    renderSelectedReviewsTray();
                    syncProductIdsHidden();
                }
            } else if (['compact_grid', 'compact_category_grid', 'category_slide_row', 'category_mosaic'].includes(sec.section_type)) {
                document.getElementById('category-main-select').value = sec.category_ids || '';
                
                const secCols = parseInt(sec.columns_per_row) || 2;
                const secLimit = parseInt(sec.product_limit) || 4;
                let detectedFormat = '2x2';
                if (secCols === 2 && secLimit === 4) detectedFormat = '2x2';
                else if (secCols === 2 && secLimit === 6) detectedFormat = '2x3';
                else if (secCols === 2 && secLimit === 8) detectedFormat = '2x4';
                else if (secCols === 2 && secLimit === 12) detectedFormat = '2x6';
                else if (secCols === 2 && secLimit === 16) detectedFormat = '2x8';
                else if (secCols === 3 && secLimit === 6) detectedFormat = '3x2';
                else if (secCols === 3 && secLimit === 9) detectedFormat = '3x3';
                else if (secCols === 1) detectedFormat = '1x1';
                else detectedFormat = (secCols === 3) ? '3x3' : '2x2';

                document.getElementById('category-grid-columns').value = detectedFormat;
                document.getElementById('section-columns').value = secCols;
                document.getElementById('product-limit').value = secLimit;

                document.getElementById('category-offer-text').value = sec.badge_text || 'Min. 30% off';
                document.getElementById('category-bg-color').value = sec.background_image_url || '#00a8ec';
                document.getElementById('category-color-picker').value = (sec.background_image_url && sec.background_image_url.startsWith('#')) ? sec.background_image_url : '#00a8ec';
                document.getElementById('category-sub-ids').value = sec.sub_category_ids || '';

                loadSubcategories(sec.category_ids, sec.sub_category_ids || '');
            } else {
                document.getElementById('product-api-action').value = sec.api_action || 'bestseller';
                document.getElementById('product-limit').value = sec.product_limit || 8;
                document.getElementById('product-badge-text').value = sec.badge_text || '';
                document.getElementById('product-category-id').value = sec.category_ids || '';
                document.getElementById('product-bg-image').value = sec.background_image_url || '';

                selectedProducts = [];

                if (sec.product_ids && sec.product_ids.trim() !== '') {
                    setProductSourceMode('handpicked');
                    // Fetch products details
                    fetch(`api/home-layout-api.php?action=get_products_by_ids&ids=${encodeURIComponent(sec.product_ids.trim())}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && Array.isArray(data.data)) {
                                selectedProducts = data.data;
                                renderSelectedProductsTray();
                                syncProductIdsHidden();
                            }
                        })
                        .catch(() => {
                            renderSelectedProductsTray();
                        });
                } else {
                    setProductSourceMode('auto');
                    renderSelectedProductsTray();
                    syncProductIdsHidden();
                }
            }

            const modal = new bootstrap.Modal(document.getElementById('sectionModal'));
            modal.show();
        }

        function generateSlotsForEdit(banners) {
            const pageTarget = document.getElementById('section-page-target')?.value || 'desktop';
            const secKey = document.getElementById('section-key')?.value || '';
            const container = document.getElementById('slots-container');
            container.innerHTML = '';
            const type = document.getElementById('section-type').value;
            const isSlider = (type === 'slider' || type === 'one_and_half_carousel' || type === 'peeking_slider' || type === 'small_banner_carousel' || type === 'small_banner_slider' || type === 'small_height_carousel');
            const isSmallBanner = (type === 'small_banner_carousel' || type === 'small_banner_slider' || type === 'small_height_carousel' || secKey.includes('small_banner'));

            if (isSmallBanner) {
                const redirectCard = document.createElement('div');
                redirectCard.className = 'alert alert-info border-info d-flex align-items-center justify-content-between p-3 mb-3 shadow-sm rounded-3';
                redirectCard.innerHTML = `
                    <div>
                        <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-layer-group text-primary me-2"></i>Small Banner Carousel Manager</h6>
                        <p class="mb-0 small text-muted">Is section ke banners ko <strong>Small Banner Carousel</strong> page-wise manager se direct manage kar sakte hain.</p>
                    </div>
                    <a href="small-banner-slider.php?tab=${encodeURIComponent(pageTarget)}&section=${encodeURIComponent(secKey)}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Open Small Banner Manager
                    </a>
                `;
                container.appendChild(redirectCard);
            }

            if (banners.length === 0) {
                const cols = parseInt(document.getElementById('section-columns').value) || 1;
                if (isSlider) {
                    generateSlotsForSlider(Math.max(2, cols));
                } else {
                    generateSlotsForBanner(cols);
                }
                return;
            }

            banners.forEach((banner, i) => {
                appendSlotHTML(i, `${isSlider ? 'Slide' : 'Banner'} #${i + 1}`, false, isSlider, banner, pageTarget);
            });

            slideIndexCounter = banners.length;
        }

        function saveSection(e) {
            e.preventDefault();

            const secType = document.getElementById('section-type').value;
            const isProduct = ['product_carousel', 'mobile_list', 'compact_grid', 'compact_product_grid', 'featured_grid'].includes(secType);
            const isReview = ['customer_reviews', 'reviews', 'customer_review_carousel'].includes(secType);

            // Strict Validation for Handpicked Products Count
            if (isProduct && productSourceMode === 'handpicked') {
                const productLimit = parseInt(document.getElementById('product-limit').value) || 0;
                const currentSelectedCount = selectedProducts.length;

                if (currentSelectedCount === 0) {
                    alert(`⚠️ Alert: Aapne "Handpicked Products" mode select kiya hai lekin koi bhi product add nahi kiya hai.\n\nKripya search bar me product search karke poore ${productLimit} products add karein!`);
                    document.getElementById('productPickerSearch').focus();
                    return false;
                }

                if (currentSelectedCount < productLimit) {
                    const remaining = productLimit - currentSelectedCount;
                    const confirmAdjust = confirm(
                        `⚠️ Validation Alert: Product Limit ${productLimit} set kiya hai lekin sirf ${currentSelectedCount} products select kiye hain (${remaining} products baki hain).\n\n` +
                        `• "OK" dabayein agar aap Product Limit ko ${currentSelectedCount} par auto-adjust karke save karna chahte hain.\n` +
                        `• "Cancel" dabayein agar aap baki ${remaining} products ko search karke add karna chahte hain.`
                    );

                    if (confirmAdjust) {
                        document.getElementById('product-limit').value = currentSelectedCount;
                    } else {
                        document.getElementById('productPickerSearch').focus();
                        return false;
                    }
                }
            }

            // Strict Validation for Handpicked Reviews Count
            if (isReview) {
                const reviewLimit = parseInt(document.getElementById('review-limit').value) || 5;
                document.getElementById('product-limit').value = reviewLimit;

                if (reviewSourceMode === 'handpicked') {
                    const currentSelectedCount = selectedReviews.length;
                    if (currentSelectedCount === 0) {
                        alert(`⚠️ Alert: Aapne "Select Specific Reviews" mode select kiya hai lekin koi bhi review add nahi kiya hai.\n\nKripya search bar se reviews select karein ya default "Top 4-5 Rated" mode select karein!`);
                        document.getElementById('reviewPickerSearch').focus();
                        return false;
                    }
                }
            }

            syncProductIdsHidden();

            const form = document.getElementById('sectionForm');
            const formData = new FormData(form);
            const isEdit = document.getElementById('section-id').value !== '';
            const url = isEdit ? 'api/home-layout-api.php?action=update' : 'api/home-layout-api.php?action=create';

            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Layout Section';

                if (data.success) {
                    const currentTarget = document.getElementById('section-page-target').value || 'desktop';
                    localStorage.setItem('pm_active_layout_tab', currentTarget);
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.set('tab', currentTarget);
                    window.location.href = nextUrl.toString();
                } else {
                    showToast(data.error || 'Failed to save section', false);
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Layout Section';
                showToast('Error saving section', false);
            });
        }
    </script>
</body>
</html>

<?php
/**
 * Renders a single draggable section card in the list.
 */
function renderSectionItem($sec, $orderNum) {
    $type = $sec['section_type'];
    $pillClass = 'pill-widget';
    $icon = 'fa-cube';
    $typeLabel = str_replace('_', ' ', $type);

    switch ($type) {
        case 'product_carousel':
            $pillClass = 'pill-carousel';
            $icon = 'fa-sliders-h';
            $typeLabel = 'Product Carousel (Horizontal)';
            break;
        case 'mobile_list':
            $pillClass = 'pill-list';
            $icon = 'fa-th-large';
            $typeLabel = 'Vertical Product Grid (2-Col)';
            break;
        case 'compact_grid':
        case 'compact_product_grid':
            $pillClass = 'pill-grid';
            $icon = 'fa-table-cells';
            $typeLabel = 'Compact Grid (' . ($sec['columns_per_row'] ?? 2) . '-Col)';
            break;
        case 'banner':
            $cols = (int)($sec['columns_per_row'] ?? 1);
            $pillClass = 'pill-banner';
            $icon = ($cols == 2) ? 'fa-table-cells-large' : 'fa-image';
            $typeLabel = ($cols == 2) ? '2 Square Banners in 1 Row' : (($cols == 1) ? '1 Full-Width Banner' : ($cols . ' Banners in 1 Row'));
            break;
        case 'slider':
            $cols = (int)($sec['columns_per_row'] ?? 1);
            $pillClass = 'pill-slider';
            $icon = 'fa-sliders';
            $typeLabel = ($cols > 1) ? ($cols . '-Card Banner Carousel') : 'Hero / Slider Carousel';
            break;
        case 'one_and_half_carousel':
        case 'peeking_slider':
            $pillClass = 'pill-slider';
            $icon = 'fa-arrows-split-up-and-left';
            $typeLabel = '1.5 Peeking Banner Carousel';
            break;
        case 'small_banner_carousel':
        case 'small_banner_slider':
        case 'small_height_carousel':
            $pillClass = 'pill-slider';
            $icon = 'fa-film';
            $typeLabel = 'Small Banner Carousel';
            break;
        case 'banner_category_slider':
            $pillClass = 'pill-carousel';
            $icon = 'fa-layer-group';
            $typeLabel = 'Banner & Category Slider';
            break;
        case 'category_mosaic':
            $pillClass = 'pill-mosaic';
            $icon = 'fa-puzzle-piece';
            $typeLabel = 'Category Mosaic';
            break;
        case 'category_slide_row':
            $pillClass = 'pill-carousel';
            $icon = 'fa-circle-notch';
            $typeLabel = 'Category Slide Strip';
            break;
        case 'gift_finder':
            $pillClass = 'pill-widget';
            $icon = 'fa-gift';
            $typeLabel = 'Gift Finder Widget';
            break;
        case 'bulk_widget':
            $pillClass = 'pill-widget';
            $icon = 'fa-truck-fast';
            $typeLabel = 'Bulk Order Widget';
            break;
        case 'customer_reviews':
        case 'reviews':
        case 'customer_review_carousel':
            $pillClass = 'pill-reviews';
            $icon = 'fa-star';
            $typeLabel = 'Customer Reviews';
            break;
        case 'featured_grid':
            $pillClass = 'pill-grid';
            $icon = 'fa-table-cells';
            $typeLabel = 'Featured 1+2 Grid (1 Large + 2 Stacked)';
            break;
    }

    $borderColor = getBorderColor($type);
    ?>
    <div class="list-group-item sortable-item d-flex align-items-center justify-content-between p-3 mb-2 shadow-sm"
        data-id="<?php echo $sec['id']; ?>"
        data-type="<?php echo htmlspecialchars($type); ?>"
        style="border-left-color: <?php echo $borderColor; ?>;">
        
        <div class="d-flex align-items-center flex-grow-1 me-3">
            <div class="drag-handle me-2" title="Drag to reorder"><i class="fas fa-grip-vertical fa-lg"></i></div>
            
            <div class="order-badge me-3">
                #<?php echo $orderNum; ?>
            </div>

            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold fs-5 text-dark">
                        <?php echo htmlspecialchars($sec['label'] ?: 'Untitled Section'); ?>
                    </span>
                    <span class="type-pill <?php echo $pillClass; ?>">
                        <i class="fas <?php echo $icon; ?>"></i> <?php echo $typeLabel; ?>
                    </span>
                    <?php if ($sec['status'] === 'inactive'): ?>
                        <span class="badge bg-secondary text-white">Hidden / Inactive</span>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                    <span class="meta-tag cursor-pointer" onclick="copyKey('<?php echo htmlspecialchars($sec['section_key']); ?>')" title="Click to copy key">
                        <i class="fas fa-key text-muted"></i> <code><?php echo htmlspecialchars($sec['section_key']); ?></code>
                    </span>

                    <?php if (in_array($type, ['customer_reviews', 'reviews', 'customer_review_carousel'])): ?>
                        <?php if (!empty($sec['product_ids'])): ?>
                            <span class="meta-tag" style="background-color: #fefce8; border-color: #fef08a; color: #854d0e;">
                                <i class="fas fa-hand-pointer text-warning"></i> Handpicked: <strong><?php echo count(explode(',', $sec['product_ids'])); ?> Reviews</strong>
                            </span>
                        <?php else: ?>
                            <span class="meta-tag" style="background-color: #f0fdf4; border-color: #bbf7d0; color: #15803d;">
                                <i class="fas fa-bolt text-success"></i> Source: <strong>Top Rated 4-5★ (Default)</strong>
                            </span>
                        <?php endif; ?>
                        <span class="meta-tag">
                            <i class="fas fa-list-ol text-primary"></i> Limit: <strong><?php echo $sec['product_limit'] ?: 5; ?> Reviews</strong>
                        </span>
                    <?php else: ?>
                        <?php if (!empty($sec['product_ids'])): ?>
                            <span class="meta-tag" style="background-color: #f0fdf4; border-color: #bbf7d0; color: #15803d;">
                                <i class="fas fa-hand-pointer text-success"></i> Handpicked: <strong><?php echo count(explode(',', $sec['product_ids'])); ?> Products</strong>
                            </span>
                        <?php elseif ($sec['api_action']): ?>
                            <span class="meta-tag">
                                <i class="fas fa-bolt text-warning"></i> Action: <strong><?php echo htmlspecialchars($sec['api_action']); ?></strong>
                            </span>
                        <?php endif; ?>

                        <?php if ($sec['product_limit']): ?>
                            <span class="meta-tag">
                                <i class="fas fa-box text-primary"></i> Limit: <strong><?php echo $sec['product_limit']; ?> Products</strong>
                            </span>
                        <?php endif; ?>

                        <?php if ($sec['badge_text']): ?>
                            <span class="meta-tag">
                                <i class="fas fa-tag text-danger"></i> Badge: "<strong><?php echo htmlspecialchars($sec['badge_text']); ?></strong>"
                            </span>
                        <?php endif; ?>

                        <?php if ($sec['columns_per_row'] > 1): ?>
                            <span class="meta-tag">
                                <i class="fas fa-columns text-info"></i> <?php echo $sec['columns_per_row']; ?> Columns
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <?php if (!empty($sec['banners'])): ?>
                <div class="d-none d-lg-flex align-items-center gap-1 me-2">
                    <?php foreach (array_slice($sec['banners'], 0, 3) as $banner): 
                        $thumbSrc = ($sec['page_target'] === 'mobile')
                            ? (!empty($banner['image_url_mobile']) ? $banner['image_url_mobile'] : ($banner['image_url_desktop'] ?? $banner['large'] ?? ''))
                            : (!empty($banner['image_url_desktop']) ? $banner['image_url_desktop'] : ($banner['image_url_mobile'] ?? $banner['large'] ?? ''));
                    ?>
                        <img src="<?php echo htmlspecialchars($thumbSrc); ?>"
                            class="preview-img-thumbnail" alt="thumbnail"
                            title="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>">
                    <?php endforeach; ?>
                    <?php if (count($sec['banners']) > 3): ?>
                        <span class="badge bg-secondary text-white">+<?php echo count($sec['banners']) - 3; ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Active / Inactive Switch -->
            <label class="switch m-0" title="Toggle Active / Inactive">
                <input type="checkbox"
                    onchange="toggleSection(<?php echo $sec['id']; ?>, this.checked)"
                    <?php echo $sec['status'] === 'active' ? 'checked' : ''; ?>>
                <span class="slider-switch"></span>
            </label>

            <!-- Direct Small Banner Manager link for small banner carousel sections -->
            <?php if (in_array($sec['section_type'], ['small_banner_carousel', 'small_banner_slider', 'small_height_carousel']) || strpos($sec['section_key'], 'small_banner') !== false): ?>
                <a href="small-banner-slider.php?tab=<?php echo urlencode($sec['page_target'] ?? 'mobile'); ?>&section=<?php echo urlencode($sec['section_key']); ?>" 
                   class="btn btn-sm btn-info text-white px-2 fw-semibold shadow-sm" 
                   title="Open & Manage Small Banners for this section">
                    <i class="fas fa-sliders-h me-1"></i> Small Banners
                </a>
            <?php endif; ?>

            <!-- Edit Button -->
            <button class="btn btn-sm btn-outline-primary px-3 fw-semibold"
                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sec)); ?>)">
                <i class="fas fa-edit me-1"></i> Edit
            </button>

            <!-- Duplicate Button -->
            <button class="btn btn-sm btn-outline-secondary px-2"
                onclick="duplicateSection(<?php echo $sec['id']; ?>)" title="Duplicate / Clone Section">
                <i class="fas fa-copy"></i>
            </button>

            <!-- Delete Button -->
            <button class="btn btn-sm btn-outline-danger px-2"
                onclick="deleteSection(<?php echo $sec['id']; ?>)" title="Delete Section">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>

    </div>
    <?php
}

function getBorderColor($type) {
    switch ($type) {
        case 'product_carousel': return '#ea580c';
        case 'mobile_list': return '#9333ea';
        case 'compact_grid':
        case 'compact_product_grid': return '#059669';
        case 'banner': return '#16a34a';
        case 'slider':
        case 'one_and_half_carousel':
        case 'peeking_slider': return '#2563eb';
        case 'category_mosaic': return '#db2777';
        case 'category_slide_row': return '#0891b2';
        case 'gift_finder': return '#4f46e5';
        case 'customer_reviews':
        case 'reviews':
        case 'customer_review_carousel': return '#eab308';
        case 'featured_grid': return '#0284c7';
        default: return '#64748b';
    }
}
?>