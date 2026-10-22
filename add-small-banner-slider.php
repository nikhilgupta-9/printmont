<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SmallBannerSliderController.php';

$controller = new SmallBannerSliderController();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_small_slider'])) {
    try {
        $createdCount = $controller->createSeparateSliders($_POST, $_FILES);
        if ($createdCount > 0) {
            $_SESSION['success_message'] = "{$createdCount} Small banner slider(s) created successfully!";
            header("Location: small-banner-slider.php");
            exit;
        } else {
            throw new Exception("Please upload at least one image in Desktop or Mobile slides.");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Small Banner Slider | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section-card { border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-section-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 18px; font-weight: 700; color: #1e293b; border-radius: 8px 8px 0 0; }
        
        .slide-item-card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: all 0.2s ease-in-out;
        }
        .slide-item-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 12px rgba(37,99,235,0.08);
        }
        .slide-header-desktop {
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            padding: 10px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .slide-header-mobile {
            background: #f0fdf4;
            border-bottom: 1px solid #dcfce7;
            padding: 10px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-img-box {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            display: none;
            margin-top: 8px;
        }
        .btn-remove-slide {
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            color: #64748b;
            border-radius: 8px 8px 0 0;
        }
        .nav-tabs .nav-link.active {
            color: #2563eb;
            font-weight: 700;
            border-bottom-color: #fff;
        }

        /* Checkbox & Selection Card Styles */
        .section-check-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 8px 12px !important;
            margin: 0 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            cursor: pointer !important;
            background: #ffffff;
            transition: background 0.15s ease-in-out;
        }
        .section-check-item:hover {
            background: #f8fafc !important;
        }
        .section-check-item:last-child {
            border-bottom: none !important;
        }
        .placement-sec-checkbox {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            margin: 0 !important;
            float: none !important;
            cursor: pointer !important;
            accent-color: #2563eb !important;
            position: static !important;
            opacity: 1 !important;
            display: inline-block !important;
            -webkit-appearance: checkbox !important;
            appearance: checkbox !important;
        }
        .page-checkbox {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            margin-right: 8px !important;
            cursor: pointer !important;
            accent-color: #2563eb !important;
            opacity: 1 !important;
            display: inline-block !important;
            -webkit-appearance: checkbox !important;
            appearance: checkbox !important;
        }
        .section-check-label {
            cursor: pointer !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            user-select: none;
            line-height: 1.35;
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
                    <div class="row mb-3 align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-0 text-gray-800 fw-bold">Add Small Banner Slider</h1>
                            <p class="text-muted mb-0">Manage and upload Desktop and Mobile small banner sliders completely separately.</p>
                        </div>
                        <div class="col-auto">
                            <a href="small-banner-slider.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Sliders
                            </a>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="add-small-banner-slider.php" method="POST" enctype="multipart/form-data" id="slider-form">
                        
                        <!-- 1. Target Pages & Global Scheduling -->
                        <div class="card form-section-card">
                            <div class="form-section-header d-flex align-items-center">
                                <i class="fas fa-layer-group text-primary me-2"></i> 1. Target Pages & Publishing
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Apply On Pages Selection -->
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>Apply On Pages (Kis Kis Page Par Apply Karna Hai)</span>
                                            <div>
                                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="toggleAllPages(true)">Select All</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAllPages(false)">Clear</button>
                                            </div>
                                        </label>
                                        <div class="p-3 bg-light rounded border">
                                            <div class="row g-2">
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="home" id="page_home" <?php echo (!isset($_POST['target_pages']) || in_array('home', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_home">🏠 Home Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="category" id="page_category" <?php echo (in_array('category', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_category">📁 Category Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="product" id="page_product" <?php echo (in_array('product', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_product">🛍️ Product Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="cart" id="page_cart" <?php echo (in_array('cart', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_cart">🛒 Cart Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="checkout" id="page_checkout" <?php echo (in_array('checkout', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_checkout">💳 Checkout Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="orders" id="page_orders" <?php echo (in_array('orders', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_orders">📦 Orders Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="blog" id="page_blog" <?php echo (in_array('blog', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_blog">📰 Blog Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="about" id="page_about" <?php echo (in_array('about', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_about">ℹ️ About Us Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="contact" id="page_contact" <?php echo (in_array('contact', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_contact">📞 Contact Us Page</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Section Placement & Positioning (Konse Section Ke Pehle Ya Baad Me Dikhana Hai) -->
                                    <div class="col-12 mt-3" id="section-placement-wrapper">
                                        <div class="card border-primary border-opacity-25 bg-white shadow-none">
                                            <div class="card-header bg-primary bg-opacity-10 py-2.5 px-3 d-flex justify-content-between align-items-center">
                                                <div class="fw-bold text-primary">
                                                    <i class="fas fa-arrows-split-up-and-left me-1"></i> Section Placement & Positioning (Konse Section Ke Pehle Ya Baad Me Dikhana Hai)
                                                </div>
                                                <span class="badge bg-primary rounded-pill px-2.5" id="placement-count-badge">1 Page Configured</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <p class="text-muted small mb-3">
                                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                                    Select karein ki har page par ye small banner slider kis section ke <strong>pehle (before)</strong> ya <strong>baad (after)</strong> me appear hoga.
                                                </p>

                                                <div id="placement-cards-container" class="row g-3">
                                                    <!-- Dynamic placement cards for selected pages inserted by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label fw-semibold">Start Date (Optional)</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label fw-semibold">End Date (Optional)</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. SEPARATE DESKTOP & MOBILE TABS / SECTIONS -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom p-0">
                                <ul class="nav nav-tabs px-3 pt-2" id="sliderTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active d-flex align-items-center gap-2" id="desktop-tab" data-bs-toggle="tab" data-bs-target="#desktop-pane" type="button" role="tab">
                                            <i class="fas fa-desktop text-primary"></i> 🖥️ Desktop Small Banner Sliders
                                            <span class="badge bg-primary rounded-pill ms-1" id="badge-desk-count">1</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link d-flex align-items-center gap-2" id="mobile-tab" data-bs-toggle="tab" data-bs-target="#mobile-pane" type="button" role="tab">
                                            <i class="fas fa-mobile-alt text-success"></i> 📱 Mobile Small Banner Sliders
                                            <span class="badge bg-success rounded-pill ms-1" id="badge-mob-count">1</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-body p-4">
                                <div class="tab-content" id="sliderTabsContent">
                                    
                                    <!-- ================= DESKTOP TAB PANE ================= -->
                                    <div class="tab-pane fade show active" id="desktop-pane" role="tabpanel">
                                        
                                        <!-- Desktop Settings -->
                                        <div class="row g-3 mb-4 p-3 bg-light rounded border border-blue-100">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Desktop Auto-running Time (ms)</label>
                                                <div class="input-group">
                                                    <input type="number" name="desktop_auto_play_speed" class="form-control" placeholder="3000" value="3000" min="500" step="100">
                                                    <span class="input-group-text">ms (3000 = 3s)</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                                <button type="button" class="btn btn-primary fw-bold" onclick="addDesktopSlide()">
                                                    <i class="fas fa-plus me-1"></i> + Add Desktop Slide
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Desktop Slides Repeater Container -->
                                        <div id="desktop-slides-container">
                                            <!-- Desktop slide cards injected here -->
                                        </div>

                                        <div class="text-center p-3 border border-dashed rounded bg-light mt-3">
                                            <button type="button" class="btn btn-outline-primary fw-bold px-4" onclick="addDesktopSlide()">
                                                <i class="fas fa-plus me-1"></i> + Add Another Desktop Slide
                                            </button>
                                        </div>
                                    </div>


                                    <!-- ================= MOBILE TAB PANE ================= -->
                                    <div class="tab-pane fade" id="mobile-pane" role="tabpanel">
                                        
                                        <!-- Mobile Settings -->
                                        <div class="row g-3 mb-4 p-3 bg-light rounded border border-green-100">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Mobile Default Format Design</label>
                                                <select name="mobile_design" class="form-select">
                                                    <option value="small_banner_standard">Small Banner Carousel (Standard)</option>
                                                    <option value="small_banner_rounded">Rounded Card Carousel</option>
                                                    <option value="small_banner_slim">Slim Strip Slider</option>
                                                    <option value="small_banner_peeking">Peeking Carousel Slider</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Mobile Auto-running (ms)</label>
                                                <div class="input-group">
                                                    <input type="number" name="mobile_auto_play_speed" class="form-control" placeholder="3000" value="3000" min="500" step="100">
                                                    <span class="input-group-text">ms</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end justify-content-end">
                                                <button type="button" class="btn btn-success fw-bold" onclick="addMobileSlide()">
                                                    <i class="fas fa-plus me-1"></i> + Add Mobile Slide
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Mobile Slides Repeater Container -->
                                        <div id="mobile-slides-container">
                                            <!-- Mobile slide cards injected here -->
                                        </div>

                                        <div class="text-center p-3 border border-dashed rounded bg-light mt-3">
                                            <button type="button" class="btn btn-outline-success fw-bold px-4" onclick="addMobileSlide()">
                                                <i class="fas fa-plus me-1"></i> + Add Another Mobile Slide
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2 mb-5">
                            <a href="small-banner-slider.php" class="btn btn-secondary px-4">Cancel</a>
                            <button type="submit" name="add_small_slider" class="btn btn-primary px-5 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Save Small Banner Sliders
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
    <script>
        let deskCounter = 0;
        let mobCounter = 0;

        const PAGE_SECTIONS = {
            home_desktop: {
                name: '🖥️ Home Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'home',
                sections: [
                    { optgroup: '🛍️ Product Sliders & Carousels' },
                    { id: 'desktop_top_deal', name: 'Top Deals and Categories (Product Slider)' },
                    { id: 'desktop_top_selection', name: 'Top Selection / Best Sellers Carousel' },
                    { id: 'desktop_top_rated', name: 'Top Rated Products Carousel' },
                    { id: 'desktop_discount_for_you', name: 'Discount For You Carousel' },
                    { id: 'desktop_womens_outfits', name: "Women's Outfits Carousel" },
                    { id: 'desktop_mens_outfits', name: "Men's Outfits Carousel" },
                    { id: 'featured_grid_1', name: 'Featured Product Grid 1' },
                    { id: 'featured_grid_2', name: 'Featured Product Grid 2' },
                    { id: 'desktop_recently_viewed', name: 'Recently Viewed Products' },
                    { optgroup: '📁 Category Mosaics & Slide Bars' },
                    { id: 'desktop_categories', name: 'Category Slide Row / Icons Bar' },
                    { id: 'desktop_mosaic_grouped_1', name: 'Category Grouped Mosaic 1' },
                    { id: 'desktop_mosaic_grouped_2', name: 'Category Grouped Mosaic 2' },
                    { id: 'desktop_mosaic_grouped_3', name: 'Category Grouped Mosaic 3' },
                    { id: 'desktop_mosaic_flat_mens', name: "Men's Flat Category Mosaic" },
                    { id: 'desktop_mosaic_flat_womens', name: "Women's Flat Category Mosaic" },
                    { id: 'brand_directory', name: 'Brand Directory Section' },
                    { id: 'bulk_widget', name: 'Bulk Order Widget' },
                    { optgroup: '🖼️ Banners & Sliders' },
                    { id: 'home_hero', name: 'Hero Slider (Top Carousel)' },
                    { id: 'home_banner_api6', name: 'Desktop Banners API 6 (3 Columns)' },
                    { id: 'home_banner_api7', name: 'Responsive Banners API 7' },
                    { id: 'home_banner_api8', name: 'Responsive Banners API 8' },
                    { id: 'home_banner_api9', name: 'Responsive Banners API 9' },
                    { id: 'home_banner_api11', name: 'Desktop Banners API 11 (3 Columns)' },
                    { id: 'home_banner_api13', name: 'Desktop Banners API 13 (3 Columns)' },
                    { id: 'home_banner_api14', name: 'Responsive Banners API 14' },
                    { id: 'home_banner_static_single_1', name: 'Static Single Banner 1' },
                    { id: 'home_banner_static_single_2', name: 'Static Single Banner 2' },
                    { id: 'footer', name: 'Footer Section' }
                ]
            },
            home_mobile: {
                name: '📱 Home Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'home',
                sections: [
                    { optgroup: '🛍️ Product Sliders & Grids' },
                    { id: 'mobile_bestsellers', name: 'Bestseller Section (Product Grid)' },
                    { id: 'mobile_top_deals_cat', name: 'Top Deals and Categories (Product Slider)' },
                    { id: 'mobile_top_selection', name: 'Top Selection Carousel' },
                    { id: 'mobile_top_rated', name: 'Top Rated Carousel' },
                    { id: 'mobile_discount_for_you', name: 'Discount For You Carousel' },
                    { id: 'mobile_new_arrivals', name: 'New Arrival Section' },
                    { id: 'mobile_womens_outfits', name: "Women's Outfits Carousel" },
                    { id: 'mobile_product_grid_1', name: 'Now Get (Product List 1)' },
                    { id: 'mobile_product_grid_2', name: 'Mobile Product Grid 2' },
                    { id: 'mobile_product_grid_3', name: 'Mobile Product Grid 3' },
                    { id: 'mobile_product_grid_4', name: 'Mobile Product Grid 4' },
                    { id: 'mobile_product_grid_5', name: 'Mobile Product Grid 5' },
                    { id: 'mobile_product_grid_6', name: 'Mobile Product Grid 6' },
                    { id: 'mobile_gift_finder', name: 'Gift Finder Section' },
                    { id: 'mobile_end_of_season_sale', name: 'End of Season Sale' },
                    { id: 'mobile_recently_viewed', name: 'Recently Viewed Products' },
                    { id: 'mobile_customer_reviews', name: 'Customer Reviews Section' },
                    { optgroup: '📁 Categories & Category Grids' },
                    { id: 'mobile_categories_slide', name: 'Category Circles / Slide Row' },
                    { id: 'mobile_home_decor', name: 'Home Decor Items (Category)' },
                    { id: 'mobile_table_dinnerware', name: 'Table & Dinnerware Grid' },
                    { id: 'mobile_buds', name: 'Buds Category Grid' },
                    { id: 'mobile_mobile_cat', name: 'Mobile Accessories Grid' },
                    { id: 'mobile_earphones', name: 'Earphones Category Grid' },
                    { id: 'mobile_womens_fashion', name: "Women's Fashion Mosaic" },
                    { id: 'mobile_mens_fashion', name: "Men's Fashion Mosaic" },
                    { id: 'mobile_womens_clothing_big', name: "Women's Clothing Big Mosaic" },
                    { id: 'mobile_why_us', name: 'Why Us? Section' },
                    { optgroup: '🖼️ Small Banner Carousels & Ribbons' },
                    { id: 'small_banner_slider', name: '1. Small Banner Carousel (Top #1)' },
                    { id: 'small_banner_slider_copy_1791504167', name: '3. Small Banner Carousel Copy (Mid #3)' },
                    { id: 'small_banner_slider_copy_1791504429', name: '6. Small Banner Carousel Copy (Lower #6)' },
                    { optgroup: '🖼️ Banners & Sliders' },
                    { id: 'home_hero', name: 'Mobile Hero Slider (Top Carousel)' },
                    { id: 'home_above_fold', name: 'Special Offer (Above Fold)' },
                    { id: 'home_mid_section_1', name: 'Offer Banners' },
                    { id: 'home_mid_section_2', name: 'Square Banners' },
                    { id: 'home_mid_section_3', name: 'Feature Banner' },
                    { id: 'home_below_selection', name: 'Feature Banner (Below Selection)' },
                    { id: 'home_after_rated', name: 'Trending Banner Slider' },
                    { id: 'mobile_womens_outfits_peek_slider', name: 'Peeking Banner Slider' },
                    { id: 'mobile_after_home_decor_slider', name: 'Banner and Slider' },
                    { id: 'mobile_after_top_selection_square', name: '2 Square Banners (After Top Selection)' },
                    { id: 'mobile_after_now_get_square', name: '2 Square Banners (After Now Get)' },
                    { id: 'mobile_after_discount_square', name: '2 Square Banners (After Discount)' },
                    { id: 'mobile_after_top_rated_single_square', name: 'Single Square Banner (After Top Rated)' },
                    { id: 'mobile_after_womens_single_rect', name: 'Collection Banner' },
                    { id: 'mobile_footer', name: 'Mobile Footer' }
                ]
            },
            category_desktop: {
                name: '🖥️ Category Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'category',
                sections: [
                    { optgroup: '🖼️ Banners & Visual Sliders' },
                    { id: 'category_small_banner_1', name: '1. Small Banner (First 2 Images - BannerSmall)' },
                    { id: 'category_before_after', name: '2. Before / After Comparison Slider (With/Without Printing)' },
                    { id: 'category_small_banner_2', name: '3. Small Banner (Next 2 Images - BannerSmall)' },
                    { id: 'category_hero_slider', name: '4. Main Category Slider (Hero Carousel)' },
                    { id: 'category_responsive_banner_7', name: '5. Responsive Banner Set (API 7 - 2 Cols)' },
                    { id: 'category_four_img_banner', name: '6. 4-Image Banner Carousel' },
                    { id: 'category_stats_banner', name: '7. Stats Banner (Why Choose Printmont)' },
                    { optgroup: '🛍️ Category Grids & Product Carousels' },
                    { id: 'category_grid_section', name: '8. Sub-Categories & Products Grid (CategoryGridSection)' },
                    { id: 'category_new_arrivals', name: '9. New Arrivals (Product Carousel)' },
                    { id: 'category_bestsellers', name: '10. Best Sellers (Product Carousel)' },
                    { id: 'category_personalized_gifts', name: '11. Personalized Gifts Section' },
                    { id: 'category_special_offers', name: '12. Special Offers (Product Carousel)' },
                    { id: 'category_customer_reviews', name: '13. Customer Reviews Section' }
                ]
            },
            category_mobile: {
                name: '📱 Category Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'category',
                sections: [
                    { optgroup: '🖼️ Top Banner Sliders' },
                    { id: 'category_mob_slider_1', name: '1. Top Single Banner Slider' },
                    { id: 'category_mob_double_1', name: '2. Double Banner Carousel (Side-by-Side 1)' },
                    { id: 'category_mob_banner_slider_1', name: '3. Banner and Slider 1' },
                    { optgroup: '🛍️ Best Sellers & Subcategory 1' },
                    { id: 'category_mob_bestseller', name: '4. Best Seller (Product Carousel)' },
                    { id: 'category_mob_banner_slider_2', name: '5. Banner and Slider 2' },
                    { id: 'category_mob_grid_1', name: '6. Subcategory 1: 3-Col Grid (Collection 1)' },
                    { id: 'category_mob_prod_list_1', name: '7. 2-Col Product List (Top Selection Tag)' },
                    { id: 'category_mob_double_2', name: '8. Double Banner Carousel 2' },
                    { optgroup: '🔥 Offer Zone & Subcategory 2' },
                    { id: 'category_mob_offer_1', name: '9. 🔥 Offer Zone Activated 1 (Top Discounts)' },
                    { id: 'category_mob_banner_slider_3', name: '10. Banner and Slider 3' },
                    { id: 'category_mob_grid_2', name: '11. Subcategory 2: 3-Col Grid (Collection 2)' },
                    { id: 'category_mob_double_3', name: '12. Double Banner Carousel 3' },
                    { optgroup: '🔥 Offer Zone & Subcategory 3 & 4' },
                    { id: 'category_mob_offer_2', name: '13. 🔥 Offer Zone Activated 2 (Discount For You)' },
                    { id: 'category_mob_banner_slider_4', name: '14. Banner and Slider 4' },
                    { id: 'category_mob_grid_3', name: '15. Subcategory 3: 3-Col Grid (Collection 3)' },
                    { id: 'category_mob_double_4', name: '16. Double Banner Carousel 4' },
                    { id: 'category_mob_offer_3', name: '17. 🔥 Offer Zone Activated 3 (Top Selection)' },
                    { id: 'category_mob_banner_slider_5', name: '18. Banner and Slider 5' },
                    { id: 'category_mob_grid_4', name: '19. Subcategory 4: 2-Col Grid (With View All)' },
                    { id: 'category_mob_double_5', name: '20. Double Banner Carousel 5' },
                    { id: 'category_mob_offer_4', name: '21. 🔥 Offer Zone Activated 4 (Top Rated)' },
                    { id: 'category_mob_prod_list_2', name: '22. 2-Col Product List 2 (Top Deals)' },
                    { optgroup: '📝 Category Info & Reviews' },
                    { id: 'category_mob_about', name: '23. About this Category & Tag Pills' },
                    { id: 'category_mob_reviews', name: '24. Customer Reviews Section' },
                    { id: 'category_mob_info_text', name: '25. Category Information Live Text Box' }
                ]
            },
            product_desktop: {
                name: '🖥️ Product Details — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'product',
                sections: [
                    { optgroup: '🛍️ Product & Customization' },
                    { id: 'product_gallery_info', name: 'Product Gallery & Main Info (Title / Price)' },
                    { id: 'product_customization_form', name: 'Customization & Upload Artwork Form' },
                    { id: 'product_buy_actions', name: 'Add to Cart / Buy Now Action Buttons' },
                    { id: 'product_offers_badges', name: 'Offers, Coupon Codes & Trust Badges' },
                    { optgroup: '⭐ Reviews, Q&A & Related' },
                    { id: 'product_frequently_bought', name: 'Frequently Bought Together' },
                    { id: 'product_reviews', name: 'Customer Ratings & Reviews Section' },
                    { id: 'product_qa', name: 'Questions & Answers (Q&A) Section' },
                    { id: 'product_related_carousel', name: 'Related / Recommended Products Carousel' }
                ]
            },
            product_mobile: {
                name: '📱 Product Details — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'product',
                sections: [
                    { optgroup: '🛍️ Mobile Product Details' },
                    { id: 'product_gallery_info', name: 'Mobile Product Image Carousel & Title' },
                    { id: 'product_customization_form', name: 'Mobile Customization / Artwork Options' },
                    { id: 'product_buy_actions', name: 'Mobile Sticky Add to Cart & Buy Now Bar' },
                    { id: 'product_offers_badges', name: 'Mobile Offers & Pincode Checker' },
                    { optgroup: '⭐ Reviews & Related' },
                    { id: 'product_frequently_bought', name: 'Mobile Frequently Bought Together' },
                    { id: 'product_reviews', name: 'Mobile Ratings & Customer Reviews' },
                    { id: 'product_related_carousel', name: 'Mobile Related Products Slider' }
                ]
            },
            cart_desktop: {
                name: '🖥️ Cart Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'cart',
                sections: [
                    { id: 'cart_header', name: 'Cart Header & Navigation' },
                    { id: 'cart_items_list', name: 'Cart Items List Table' },
                    { id: 'cart_coupons', name: 'Apply Coupon / Discount Code Box' },
                    { id: 'cart_summary', name: 'Order Price Summary & Checkout Button' },
                    { id: 'cart_cross_sell', name: 'Recommended Products (You May Also Like)' }
                ]
            },
            cart_mobile: {
                name: '📱 Cart Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'cart',
                sections: [
                    { id: 'cart_header', name: 'Mobile Cart Header' },
                    { id: 'cart_items_list', name: 'Mobile Cart Items Cards' },
                    { id: 'cart_coupons', name: 'Mobile Coupon Code Box' },
                    { id: 'cart_summary', name: 'Mobile Sticky Checkout Bar' },
                    { id: 'cart_cross_sell', name: 'Mobile Recommended Products' }
                ]
            },
            checkout_desktop: {
                name: '🖥️ Checkout — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'checkout',
                sections: [
                    { id: 'checkout_stepper', name: 'Checkout Step Header' },
                    { id: 'checkout_address', name: 'Delivery Address Section' },
                    { id: 'checkout_order_review', name: 'Order Review Items' },
                    { id: 'checkout_payment_methods', name: 'Payment Method Selection' },
                    { id: 'checkout_summary', name: 'Order Total & Place Order Button' }
                ]
            },
            checkout_mobile: {
                name: '📱 Checkout — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'checkout',
                sections: [
                    { id: 'checkout_stepper', name: 'Mobile Checkout Step Header' },
                    { id: 'checkout_address', name: 'Mobile Delivery Address Card' },
                    { id: 'checkout_order_review', name: 'Mobile Order Summary Accordion' },
                    { id: 'checkout_payment_methods', name: 'Mobile Payment Options' },
                    { id: 'checkout_summary', name: 'Mobile Sticky Place Order Bar' }
                ]
            },
            blog_desktop: {
                name: '🖥️ Blog Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'blog',
                sections: [
                    { id: 'blog_header', name: 'Blog Header & Hero Article' },
                    { id: 'blog_posts_grid', name: 'Recent Articles 3-Column Grid' },
                    { id: 'blog_categories_sidebar', name: 'Blog Categories & Tags Sidebar' },
                    { id: 'blog_newsletter', name: 'Newsletter Subscription Box' }
                ]
            },
            blog_mobile: {
                name: '📱 Blog Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'blog',
                sections: [
                    { id: 'blog_header', name: 'Mobile Blog Hero Article' },
                    { id: 'blog_posts_grid', name: 'Mobile Single-Column Articles List' },
                    { id: 'blog_newsletter', name: 'Mobile Newsletter Subscription' }
                ]
            },
            about_desktop: {
                name: '🖥️ About Us — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'about',
                sections: [
                    { id: 'about_hero', name: 'About Us Hero Banner' },
                    { id: 'about_story', name: 'Our Story / Mission & Vision' },
                    { id: 'about_features', name: 'Why Choose Us / Features Grid' },
                    { id: 'about_team', name: 'Team Members Section' }
                ]
            },
            about_mobile: {
                name: '📱 About Us — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'about',
                sections: [
                    { id: 'about_hero', name: 'Mobile About Us Banner' },
                    { id: 'about_story', name: 'Mobile Our Story & Stats' },
                    { id: 'about_features', name: 'Mobile Why Choose Us List' }
                ]
            },
            contact_desktop: {
                name: '🖥️ Contact Us — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'contact',
                sections: [
                    { id: 'contact_header', name: 'Contact Header & Location Map' },
                    { id: 'contact_form', name: 'Send Us a Message Form' },
                    { id: 'contact_info_cards', name: 'Contact Info & Address Cards' }
                ]
            },
            contact_mobile: {
                name: '📱 Contact Us — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'contact',
                sections: [
                    { id: 'contact_header', name: 'Mobile Contact Header & Call Button' },
                    { id: 'contact_form', name: 'Mobile Send Message Form' },
                    { id: 'contact_info_cards', name: 'Mobile Contact & Location Cards' }
                ]
            }
        };

        // Track live selected sections in memory so changes are never lost when toggling pages
        const CURRENT_SELECTIONS = {};

        function captureCurrentSelections() {
            document.querySelectorAll('.placement-pos-select').forEach(sel => {
                const page = sel.getAttribute('data-page');
                if (page) {
                    if (!CURRENT_SELECTIONS[page]) CURRENT_SELECTIONS[page] = {};
                    CURRENT_SELECTIONS[page].position = sel.value;
                }
            });
            document.querySelectorAll('.placement-sec-checkbox').forEach(cb => {
                const page = cb.getAttribute('data-page');
                if (page) {
                    if (!CURRENT_SELECTIONS[page]) CURRENT_SELECTIONS[page] = { sections: [] };
                    if (!Array.isArray(CURRENT_SELECTIONS[page].sections)) CURRENT_SELECTIONS[page].sections = [];
                    
                    if (cb.checked && !CURRENT_SELECTIONS[page].sections.includes(cb.value)) {
                        CURRENT_SELECTIONS[page].sections.push(cb.value);
                    } else if (!cb.checked && CURRENT_SELECTIONS[page].sections.includes(cb.value)) {
                        CURRENT_SELECTIONS[page].sections = CURRENT_SELECTIONS[page].sections.filter(v => v !== cb.value);
                    }
                }
            });
        }

        function renderPlacementCards() {
            captureCurrentSelections();

            const container = document.getElementById('placement-cards-container');
            const checkedBoxes = Array.from(document.querySelectorAll('.page-checkbox:checked'));
            const selectedPages = checkedBoxes.map(cb => cb.value);

            if (selectedPages.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-warning mb-0 py-2 px-3 small">
                            <i class="fas fa-exclamation-circle me-1"></i> Koi page select nahi kiya gaya hai. Please upar se kam se kam ek page select karein.
                        </div>
                    </div>
                `;
                const badge = document.getElementById('placement-count-badge');
                if (badge) badge.textContent = `0 Page(s) Configured`;
                return;
            }

            // Expand every selected page into Desktop and Mobile layout cards
            const activeCardKeys = [];
            selectedPages.forEach(p => {
                const deskKey = `${p}_desktop`;
                const mobKey = `${p}_mobile`;
                if (PAGE_SECTIONS[deskKey]) activeCardKeys.push(deskKey);
                if (PAGE_SECTIONS[mobKey]) activeCardKeys.push(mobKey);
                // Fallback for single key if any
                if (!PAGE_SECTIONS[deskKey] && !PAGE_SECTIONS[mobKey] && PAGE_SECTIONS[p]) {
                    activeCardKeys.push(p);
                }
            });

            const badge = document.getElementById('placement-count-badge');
            if (badge) {
                badge.textContent = `${activeCardKeys.length} Placement Slot(s) Active (${selectedPages.length} Page(s))`;
            }

            let html = '';
            activeCardKeys.forEach((key) => {
                const config = PAGE_SECTIONS[key];
                if (!config) return;

                const savedPos = CURRENT_SELECTIONS[key]?.position || 'after';
                const hasExistingSelections = Array.isArray(CURRENT_SELECTIONS[key]?.sections);
                const savedSecs = hasExistingSelections ? CURRENT_SELECTIONS[key].sections : [];

                let checkboxesHtml = '';
                let currentGroup = '';

                config.sections.forEach((sec, sIdx) => {
                    if (sec.optgroup) {
                        currentGroup = sec.optgroup;
                        checkboxesHtml += `
                            <div class="fw-bold text-uppercase text-secondary px-2 pt-2 pb-1 small border-bottom bg-light d-flex align-items-center gap-1" style="font-size: 11px; letter-spacing: 0.5px;">
                                ${currentGroup}
                            </div>
                        `;
                    } else {
                        // Check if in savedSecs, or if first time initializing check default
                        let isChecked = false;
                        if (hasExistingSelections) {
                            isChecked = savedSecs.includes(sec.id);
                        } else {
                            isChecked = (sIdx === 1 || sIdx === 0 || sec.id === 'desktop_top_deal' || sec.id === 'mobile_top_deals_cat' || sec.id === 'home_hero' || sec.id === 'category_header' || sec.id === 'product_gallery_info' || sec.id === 'cart_header' || sec.id === 'checkout_stepper' || sec.id === 'blog_header' || sec.id === 'about_hero' || sec.id === 'contact_header');
                        }

                        checkboxesHtml += `
                            <label class="section-check-item">
                                <input type="checkbox" name="page_placement[${key}][sections][]" value="${sec.id}" class="placement-sec-checkbox" data-page="${key}" data-name="${sec.name.replace(/"/g, '&quot;')}" ${isChecked ? 'checked' : ''} onchange="updatePlacementBadge('${key}')">
                                <span class="section-check-label">${sec.name}</span>
                            </label>
                        `;
                    }
                });

                html += `
                    <div class="col-md-6 col-lg-6">
                        <div class="card h-100 border shadow-none bg-light" style="border-radius: 8px;">
                            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                                <span class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                    <span class="badge ${config.badgeClass || 'bg-primary'} rounded-pill px-2.5 py-1" style="font-size: 11.5px;">
                                        ${config.name}
                                    </span>
                                </span>
                                <span class="badge bg-light text-dark border px-2 py-1 small" id="sec-count-badge-${key}">0 Section(s) Selected</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-dark mb-1">Placement / Order</label>
                                    <select name="page_placement[${key}][position]" class="form-select form-select-sm placement-pos-select" data-page="${key}" onchange="updatePlacementBadge('${key}')">
                                        <option value="after" ${savedPos === 'after' ? 'selected' : ''}>➕ Insert AFTER (Ke Baad)</option>
                                        <option value="before" ${savedPos === 'before' ? 'selected' : ''}>➖ Insert BEFORE (Ke Pehle)</option>
                                        <option value="top" ${savedPos === 'top' ? 'selected' : ''}>⬆️ Top of Page (Sabse Upar)</option>
                                        <option value="bottom" ${savedPos === 'bottom' ? 'selected' : ''}>⬇️ Bottom of Page (Sabse Niche)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-1 d-flex justify-content-between align-items-center">
                                    <label class="form-label small fw-bold text-dark mb-0">
                                        Target Sections <span class="text-muted fw-normal">(Multiple checkboxes)</span>
                                    </label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1.5" style="font-size: 10.5px;" onclick="toggleSectionGroup('${key}', true)">Select All</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1.5" style="font-size: 10.5px;" onclick="toggleSectionGroup('${key}', false)">Clear</button>
                                    </div>
                                </div>

                                <div class="border rounded bg-white overflow-auto shadow-sm" style="max-height: 200px; border-color: #dee2e6 !important;">
                                    ${checkboxesHtml}
                                </div>

                                <div class="mt-2.5 p-2 rounded bg-white border small text-muted d-flex align-items-center gap-1.5" id="placement-preview-${key}">
                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                    <span>Placement preview loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            activeCardKeys.forEach(key => {
                updatePlacementBadge(key);
            });
        }

        function toggleSectionGroup(key, select) {
            document.querySelectorAll(`.placement-sec-checkbox[data-page="${key}"]`).forEach(cb => {
                cb.checked = select;
            });
            updatePlacementBadge(key);
        }

        function updatePlacementBadge(key) {
            const posSelect = document.querySelector(`.placement-pos-select[data-page="${key}"]`);
            const checkedSecs = Array.from(document.querySelectorAll(`.placement-sec-checkbox[data-page="${key}"]:checked`));
            const previewBox = document.getElementById(`placement-preview-${key}`);
            const countBadge = document.getElementById(`sec-count-badge-${key}`);
            const configName = PAGE_SECTIONS[key]?.name || key;

            if (countBadge) {
                countBadge.textContent = `${checkedSecs.length} Section(s) Selected`;
                if (checkedSecs.length > 0) {
                    countBadge.className = 'badge bg-success text-white px-2 py-1 small';
                } else {
                    countBadge.className = 'badge bg-danger text-white px-2 py-1 small';
                }
            }

            if (previewBox && posSelect) {
                const posText = posSelect.options[posSelect.selectedIndex]?.text.replace(/[➕➖⬆️⬇️]/g, '').trim();
                if (checkedSecs.length === 0) {
                    previewBox.innerHTML = `
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <span class="text-danger fw-semibold">Koi section select nahi hai! Kripya kam se kam ek section check karein.</span>
                    `;
                } else {
                    const secNames = checkedSecs.map(cb => cb.getAttribute('data-name') || cb.value);
                    const previewText = secNames.length <= 2 ? secNames.join(', ') : `${secNames.slice(0, 2).join(', ')} + ${secNames.length - 2} more`;
                    previewBox.innerHTML = `
                        <i class="fas fa-map-pin text-primary"></i>
                        <span><strong>${configName}</strong>: [<strong>${previewText}</strong>] ke <strong>${posText}</strong> lagega (${checkedSecs.length} places).</span>
                    `;
                }
            }
        }

        function toggleAllPages(select) {
            document.querySelectorAll('.page-checkbox').forEach(cb => {
                cb.checked = select;
            });
            renderPlacementCards();
        }

        document.querySelectorAll('.page-checkbox').forEach(cb => {
            cb.addEventListener('change', renderPlacementCards);
        });


        // ================= DESKTOP SLIDE MANAGEMENT =================
        function addDesktopSlide() {
            const container = document.getElementById('desktop-slides-container');
            const index = deskCounter++;
            const num = container.children.length + 1;

            const card = document.createElement('div');
            card.className = 'slide-item-card';
            card.id = `desk-slide-${index}`;

            card.innerHTML = `
                <div class="slide-header-desktop">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill px-2.5 py-1 fw-bold desk-num-badge">Desktop Slide #${num}</span>
                        <span class="fw-bold text-dark desk-title-preview">Slide Details</span>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-remove-slide" onclick="removeDesktopSlide(${index})">
                        <i class="fas fa-trash-alt me-1"></i> Remove
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Desktop Slider Image Upload <span class="text-danger">*</span></label>
                            <input type="file" name="desktop_slide_image_${index}" class="form-control" accept="image/*" onchange="previewImg(this, 'preview-desk-${index}')" required>
                            <div class="form-text">Recommended resolution: 1200 x 200 px.</div>
                            <img id="preview-desk-${index}" class="preview-img-box" alt="Desktop Preview">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Desktop Slide Title / Name</label>
                            <input type="text" name="desktop_slides[${index}][title]" class="form-control" placeholder="e.g. Special Offer Desktop Slide #${num}" onkeyup="updateDeskTitle(${index}, this.value)">
                            <div class="form-text">Promotional banner title.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Desktop Target URL</label>
                            <input type="text" name="desktop_slides[${index}][desktop_url]" class="form-control" placeholder="e.g. /allproducts?category=home-decor">
                            <div class="form-text">Redirect URL when desktop banner is clicked.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Desktop Sort Order</label>
                            <input type="number" name="desktop_slides[${index}][desktop_sort_order]" class="form-control" value="${num - 1}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Desktop Status</label>
                            <select name="desktop_slides[${index}][desktop_status]" class="form-select">
                                <option value="active">Show (Active)</option>
                                <option value="inactive">Hide (Inactive)</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(card);
            updateDeskCount();
        }

        function removeDesktopSlide(index) {
            const row = document.getElementById(`desk-slide-${index}`);
            if (row) {
                row.remove();
                renumberDeskSlides();
                updateDeskCount();
            }
        }

        function renumberDeskSlides() {
            const container = document.getElementById('desktop-slides-container');
            Array.from(container.children).forEach((card, idx) => {
                const badge = card.querySelector('.desk-num-badge');
                if (badge) badge.textContent = `Desktop Slide #${idx + 1}`;
            });
        }

        function updateDeskCount() {
            const count = document.getElementById('desktop-slides-container').children.length;
            document.getElementById('badge-desk-count').textContent = count;
        }

        function updateDeskTitle(index, val) {
            const row = document.getElementById(`desk-slide-${index}`);
            if (row) {
                const preview = row.querySelector('.desk-title-preview');
                if (preview) preview.textContent = val.trim() !== '' ? `- ${val}` : 'Slide Details';
            }
        }


        // ================= MOBILE SLIDE MANAGEMENT =================
        function addMobileSlide() {
            const container = document.getElementById('mobile-slides-container');
            const index = mobCounter++;
            const num = container.children.length + 1;

            const card = document.createElement('div');
            card.className = 'slide-item-card';
            card.id = `mob-slide-${index}`;

            card.innerHTML = `
                <div class="slide-header-mobile">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill px-2.5 py-1 fw-bold mob-num-badge">Mobile Slide #${num}</span>
                        <span class="fw-bold text-dark mob-title-preview">Slide Details</span>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-remove-slide" onclick="removeMobileSlide(${index})">
                        <i class="fas fa-trash-alt me-1"></i> Remove
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile Slider Image Upload <span class="text-danger">*</span></label>
                            <input type="file" name="mobile_slide_image_${index}" class="form-control" accept="image/*" onchange="previewImg(this, 'preview-mob-${index}')" required>
                            <div class="form-text">Recommended mobile resolution: 800 x 200 px.</div>
                            <img id="preview-mob-${index}" class="preview-img-box" alt="Mobile Preview">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile Slide Title / Name</label>
                            <input type="text" name="mobile_slides[${index}][title]" class="form-control" placeholder="e.g. Special Offer Mobile Slide #${num}" onkeyup="updateMobTitle(${index}, this.value)">
                            <div class="form-text">Mobile banner title.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mobile Format Design</label>
                            <select name="mobile_slides[${index}][mobile_design]" class="form-select">
                                <option value="small_banner_standard">Small Banner Carousel (Standard)</option>
                                <option value="small_banner_rounded">Rounded Card Carousel</option>
                                <option value="small_banner_slim">Slim Strip Slider</option>
                                <option value="small_banner_peeking">Peeking Carousel Slider</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mobile Target URL</label>
                            <input type="text" name="mobile_slides[${index}][mobile_url]" class="form-control" placeholder="e.g. /allproducts?category=home-decor">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" name="mobile_slides[${index}][mobile_sort_order]" class="form-control" value="${num - 1}" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="mobile_slides[${index}][mobile_status]" class="form-select">
                                <option value="active">Show</option>
                                <option value="inactive">Hide</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(card);
            updateMobCount();
        }

        function removeMobileSlide(index) {
            const row = document.getElementById(`mob-slide-${index}`);
            if (row) {
                row.remove();
                renumberMobSlides();
                updateMobCount();
            }
        }

        function renumberMobSlides() {
            const container = document.getElementById('mobile-slides-container');
            Array.from(container.children).forEach((card, idx) => {
                const badge = card.querySelector('.mob-num-badge');
                if (badge) badge.textContent = `Mobile Slide #${idx + 1}`;
            });
        }

        function updateMobCount() {
            const count = document.getElementById('mobile-slides-container').children.length;
            document.getElementById('badge-mob-count').textContent = count;
        }

        function updateMobTitle(index, val) {
            const row = document.getElementById(`mob-slide-${index}`);
            if (row) {
                const preview = row.querySelector('.mob-title-preview');
                if (preview) preview.textContent = val.trim() !== '' ? `- ${val}` : 'Slide Details';
            }
        }

        function previewImg(input, targetId) {
            const preview = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }

        // Initialize with 1 Desktop slide, 1 Mobile slide, and placement cards
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const pageParam = urlParams.get('page') || urlParams.get('tab');
            const sectionParam = urlParams.get('section');

            if (pageParam && pageParam !== 'all') {
                document.querySelectorAll('.page-checkbox').forEach(cb => {
                    cb.checked = (cb.value === pageParam);
                });
            }

            if (sectionParam) {
                const targetKey = (pageParam === 'home' || !pageParam) ? 'home_mobile' : `${pageParam}_mobile`;
                CURRENT_SELECTIONS[targetKey] = {
                    position: 'after',
                    sections: [sectionParam]
                };
                CURRENT_SELECTIONS['home_desktop'] = {
                    position: 'after',
                    sections: [sectionParam]
                };
            }

            addDesktopSlide();
            addMobileSlide();
            renderPlacementCards();

            if (sectionParam) {
                setTimeout(() => {
                    const matchedEl = document.querySelector(`.placement-sec-checkbox[value="${sectionParam}"]`);
                    if (matchedEl) {
                        matchedEl.checked = true;
                        matchedEl.closest('.section-check-item')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
            }
        });
    </script>
</body>
</html>
